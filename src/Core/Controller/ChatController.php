<?php

namespace ChatRoom\Core\Controller;

use PDO;
use Psr\Log\LoggerInterface;
use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Helpers\Helpers;
use ChatRoom\Core\Database\SqlLite;

class ChatController
{
    private $logger;

    // 定义状态消息常量
    const STATUS_SUCCESS = 'success';
    const STATUS_WARNING = 'warning';
    const STATUS_ERROR = 'error';

    const MESSAGE_EMPTY = '消息内容不能为空';
    const MESSAGE_SUCCESS = '发送成功';
    const MESSAGE_NOT_LOGGED_IN = '用户未登录';
    const MESSAGE_SEND_FAILED = '发送消息失败';
    const MESSAGE_FETCH_FAILED = '获取消息失败';
    const MESSAGE_INVALID_REQUEST = '无效请求';

    private $user_helpers;

    public $Helpers;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->user_helpers = new User;
        $this->Helpers = new Helpers;
    }

    /**
     * 发送消息
     *
     * @param [type] $user
     * @param [type] $message
     * @return void
     */
    public function sendMessage($user, $message)
    {
        try {
            $db = SqlLite::getInstance()->getConnection();
            $stmt = $db->prepare('INSERT INTO messages (user_name, content, type, created_at) VALUES (?, ?, ?, ?)');
            return $stmt->execute([$user['username'], $message, 'user', date('Y-m-d H:i:s')]);
        } catch (\Exception $e) {
            $this->logger->error("Send Message Database operation failed: " . $e->getMessage(), [
                'stack_trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public function getMessages($offset = 0, $limit = 10, $lastFetchedTimestamp = null)
    {
        try {
            $db = SqlLite::getInstance()->getConnection();

            // 构建查询语句，只选择需要的字段
            $query = '
            SELECT 
                messages.id,
                messages.type,
                messages.content,
                messages.user_name,
                users.group_id AS user_group_id, -- 从 users 表中获取 user_group_id
                messages.created_at AS created_at,
                users.avatar_url,
                groups.group_name
            FROM messages
            LEFT JOIN users ON messages.user_name = users.username
            LEFT JOIN groups ON users.group_id = groups.group_id'; // 根据 users 表中的 group_id 关联 groups 表

            $params = [];

            if ($lastFetchedTimestamp) {
                $query .= ' WHERE messages.created_at > :last_fetched';
                $params[':last_fetched'] = $lastFetchedTimestamp;
            }

            // 添加 ORDER BY, LIMIT 和 OFFSET
            $query .= ' ORDER BY messages.created_at ASC LIMIT :limit OFFSET :offset';

            $stmt = $db->prepare($query);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

            if ($lastFetchedTimestamp) {
                $stmt->bindValue(':last_fetched', $lastFetchedTimestamp, PDO::PARAM_STR);
            }

            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $results;
        } catch (\PDOException $e) {
            $this->logger->error("Get Messages Database operation failed: " . $e->getMessage(), [
                'stack_trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public function handleRequest()
    {
        header('Content-Type: application/json');

        switch ($_SERVER['REQUEST_METHOD']) {
            case 'POST':
                $this->handlePostRequest();
                break;
            case 'GET':
                $this->handleGetRequest();
                break;
            default:
                $this->response(self::STATUS_ERROR, self::MESSAGE_INVALID_REQUEST);
                break;
        }
    }

    private function handlePostRequest()
    {
        $message = htmlspecialchars(trim($_POST['message']), ENT_QUOTES, 'UTF-8');
        if (empty($message)) {
            $this->response(self::STATUS_WARNING, self::MESSAGE_EMPTY);
            return;
        }

        // 获取当前SESSION的用户信息
        $user = $_SESSION['userinfo'] ?? null;
        // 从数据库获取用户信息
        $userInfo = $this->user_helpers->getUserInfo($user['username']);

        // 检查用户信息是否无效或不是数组
        if (!$user || !is_array($user)) {
            $this->response(self::STATUS_ERROR, self::MESSAGE_NOT_LOGGED_IN);
            return;
        }

        // 检查用户信息是否为null
        if ($userInfo === null) {
            $this->response(self::STATUS_ERROR, self::MESSAGE_NOT_LOGGED_IN);
            return;
        }

        // 如果以上检查都通过，则你可以继续正常处理用户请求
        if ($this->sendMessage($userInfo, $message)) {
            $this->response(self::STATUS_SUCCESS, self::MESSAGE_SUCCESS);
        } else {
            $this->response(self::STATUS_ERROR, self::MESSAGE_SEND_FAILED);
        }
    }


    private function handleGetRequest()
    {
        // 解析并过滤前端传递的参数，确保这些参数是有效的数字
        $offset = isset($_GET['offset']) ? filter_var($_GET['offset'], FILTER_VALIDATE_INT, ["options" => ["default" => 0, "min_range" => 0]]) : 0;
        $limit = isset($_GET['limit']) ? filter_var($_GET['limit'], FILTER_VALIDATE_INT, ["options" => ["default" => 10, "min_range" => 1, "max_range" => 100]]) : 10;
        $lastFetched = isset($_GET['last_fetched']) ? filter_var($_GET['last_fetched'], FILTER_SANITIZE_FULL_SPECIAL_CHARS) : null;

        // 如果 offset 为 0 则返回所有消息
        if ($offset === 0) {
            $messages = $this->getAllMessages();
        } else {
            // 调用 getMessages 方法并传递分页参数和过滤参数
            $messages = $this->getMessages($offset, $limit, $lastFetched);
        }

        // 检查返回的结果并相应地回应
        if ($messages === false) {
            $this->response(self::STATUS_ERROR, self::MESSAGE_FETCH_FAILED);
        } else {
            header('Content-Type: application/json');
            echo json_encode($messages);
        }
    }

    private function getAllMessages()
    {
        try {
            $db = SqlLite::getInstance()->getConnection();

            // 构建查询语句以获取所有消息
            $query = '
            SELECT 
            messages.id,
            messages.type,
            messages.content,
            messages.user_name,
            users.group_id AS user_group_id, -- 从 users 表中获取 user_group_id
            messages.created_at AS created_at,
            users.avatar_url,
            groups.group_name
            FROM messages
            LEFT JOIN users ON messages.user_name = users.username
            LEFT JOIN groups ON users.group_id = groups.group_id'; // 根据 users 表中的 group_id 关联 groups 表

            $stmt = $db->query($query);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Get All Messages Database operation failed: " . $e->getMessage(), [
                'stack_trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    private function response($status, $message = '')
    {
        exit(json_encode(['status' => $status, 'message' => $message]));
    }
}
