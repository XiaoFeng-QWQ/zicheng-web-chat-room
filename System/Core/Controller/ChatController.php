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
    private $user_helpers;
    public $Helpers;
    // 状态消息常量
    const STATUS_SUCCESS = 'success';
    const STATUS_WARNING = 'warning';
    const STATUS_ERROR = 'error';
    const MESSAGE_EMPTY = '消息内容不能为空';
    const MESSAGE_SUCCESS = '发送成功';
    const MESSAGE_NOT_LOGGED_IN = '用户未登录';
    const MESSAGE_SEND_FAILED = '发送消息失败';
    const MESSAGE_FETCH_FAILED = '获取消息失败';
    const MESSAGE_INVALID_REQUEST = '无效请求';
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->user_helpers = new User;
        $this->Helpers = new Helpers;
    }

    /**
     * 发送消息
     *
     * @param array $user 用户信息数组
     * @param string $message 发送的消息内容
     * @return bool
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

    /**
     * 获取消息
     *
     * @param int $offset 偏移量
     * @param int $limit 限制条数
     * @return array|bool
     */
    public function getMessages($offset = 0, $limit = 10)
    {
        try {
            $db = SqlLite::getInstance()->getConnection();
            $query = '
            SELECT 
                messages.id,
                messages.type,
                messages.content,
                messages.user_name,
                users.group_id AS user_group_id,
                messages.created_at AS created_at,
                users.avatar_url,
                groups.group_name
            FROM messages
            LEFT JOIN users ON messages.user_name = users.username
            LEFT JOIN groups ON users.group_id = groups.group_id
            ORDER BY messages.created_at ASC LIMIT :limit OFFSET :offset';

            $stmt = $db->prepare($query);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Get Messages Database operation failed: " . $e->getMessage(), [
                'stack_trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * 处理请求
     */
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

    /**
     * 处理 POST 请求
     */
    private function handlePostRequest()
    {
        $message = htmlspecialchars(trim($_POST['message']), ENT_QUOTES, 'UTF-8');
        if (empty($message)) {
            $this->response(self::STATUS_WARNING, self::MESSAGE_EMPTY);
            return;
        }

        // 获取当前 SESSION 的用户信息
        $user = $_SESSION['userinfo'] ?? null;
        // 从数据库获取用户信息
        $userInfo = $this->user_helpers->getUserInfo($user['username']);

        // 检查用户信息是否无效或不是数组
        if (!$user || !is_array($user)) {
            $this->response(self::STATUS_ERROR, self::MESSAGE_NOT_LOGGED_IN);
            return;
        }

        // 检查用户信息是否为 null
        if ($userInfo === null) {
            $this->response(self::STATUS_ERROR, self::MESSAGE_NOT_LOGGED_IN);
            return;
        }

        // 继续处理发送请求
        if ($this->sendMessage($userInfo, $message)) {
            $this->response(self::STATUS_SUCCESS, self::MESSAGE_SUCCESS);
        } else {
            $this->response(self::STATUS_ERROR, self::MESSAGE_SEND_FAILED);
        }
    }

    /**
     * 处理 GET 请求
     */
    private function handleGetRequest()
    {
        $offset = isset($_GET['offset']) ? filter_var($_GET['offset'], FILTER_VALIDATE_INT, ["options" => ["default" => 0, "min_range" => 0]]) : 0;
        $limit = isset($_GET['limit']) ? filter_var($_GET['limit'], FILTER_VALIDATE_INT, ["options" => ["default" => 10, "min_range" => 1, "max_range" => 100]]) : 10;

        $messages = ($offset === 0) ? $this->getAllMessages() : $this->getMessages($offset, $limit);

        if ($messages === false) {
            $this->response(self::STATUS_ERROR, self::MESSAGE_FETCH_FAILED);
        } else {
            echo json_encode($messages);
        }
    }

    /**
     * 获取所有消息
     *
     * @return array|false
     */
    private function getAllMessages()
    {
        try {
            $db = SqlLite::getInstance()->getConnection();
            $query = '
            SELECT 
            messages.id,
            messages.type,
            messages.content,
            messages.user_name,
            users.group_id AS user_group_id,
            messages.created_at AS created_at,
            users.avatar_url,
            groups.group_name
            FROM messages
            LEFT JOIN users ON messages.user_name = users.username
            LEFT JOIN groups ON users.group_id = groups.group_id';

            $stmt = $db->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Get All Messages Database operation failed: " . $e->getMessage(), [
                'stack_trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * 响应 JSON 数据
     *
     * @param string $status 状态
     * @param string $message 消息
     */
    private function response($status, $message = '')
    {
        exit(json_encode(['status' => $status, 'message' => $message]));
    }
}