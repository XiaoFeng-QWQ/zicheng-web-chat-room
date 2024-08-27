<?php

namespace ChatRoom\Core\Controller;

use PDO;
use Exception;
use PDOException;
use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Helpers\Helpers;
use ChatRoom\Core\Database\SqlLite;

class ChatController
{
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
    public function __construct()
    {
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
            $stmt = $db->prepare('INSERT INTO messages (user_name, content, type, created_at, user_ip) VALUES (?, ?, ?, ?, ?)');
            return $stmt->execute([$user['username'], $message, 'user', date('Y-m-d H:i:s'), $this->user_helpers->getIp()]);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
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
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
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
        // 安全地处理和检查输入
        $message = htmlspecialchars(trim($_POST['message']), ENT_QUOTES, 'UTF-8');

        // 获取当前用户信息
        $user = $_SESSION['user_login_info'] ?? null;
        $userCookieInfo = json_decode($_COOKIE['user_login_info'] ?? '{}', true);

        // 检查SESSION和Cookie的用户信息是否有效
        if (empty($user) || empty($userCookieInfo)) {
            $this->response(self::STATUS_ERROR, self::MESSAGE_NOT_LOGGED_IN);
            return;
        }

        // 校验SESSION与Cookie中的用户信息是否一致
        if ($user !== $userCookieInfo) {
            $this->response(self::STATUS_ERROR, self::MESSAGE_NOT_LOGGED_IN);
            return;
        }

        // 从数据库获取用户信息
        $userInfo = $this->user_helpers->getUserInfo($user['username']);

        // 检查数据库中的用户信息是否有效
        if (empty($userInfo)) {
            $this->response(self::STATUS_ERROR, self::MESSAGE_NOT_LOGGED_IN);
            return;
        }

        // 检查用户浏览器信息和数据库是否一致
        if ($user['user_login_token'] !== $userInfo['user_login_token'] || $user['user_id'] !== $userInfo['user_id']) {
            $this->response(self::STATUS_ERROR, self::MESSAGE_NOT_LOGGED_IN);
            return;
        }

        // 检查上传的图片文件
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image = $_FILES['image'];
            // 验证图片类型和大小
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            // 图片最大2MB，单位：bytes
            if (!in_array($image['type'], $allowedTypes) || $image['size'] > 2097152) {
                $this->response(self::STATUS_WARNING, '无效的图片类型或图片太大');
                return;
            }
            // 获取当前日期和时间戳
            $year = date('Y');
            $month = date('m');
            $day = date('d');
            $timestamp = time();
            $userId = $userInfo['user_id'];

            // 生成保存图片的目录路径
            $uploadDir = FRAMEWORK_DIR . "/StaticResources/uploads/$year/$month/$day/u_{$userId}/";
            // 如果目录不存在，则创建目录
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
                $this->response(self::STATUS_ERROR, '无法创建上传目录');
                return;
            }
            // 生成唯一的文件名并保存图片
            $imageName = $timestamp . "_" . uniqid() . '.' . pathinfo($image['name'], PATHINFO_EXTENSION);
            $imagePath = $uploadDir . $imageName;

            if (!move_uploaded_file($image['tmp_name'], $imagePath)) {
                $this->response(self::STATUS_ERROR, '图片上传失败');
                return;
            }
            // 生成相对路径用于前端显示
            $relativeImagePath = "/StaticResources/uploads/$year/$month/$day/u_{$userId}/$imageName";
            // 将图片路径插入到消息内容中
            $message .= '
            <br>
            <a href="' . $relativeImagePath . '"  data-fancybox>
                <img class="img-rounded" src="' . $relativeImagePath . '" alt="用户上传的图片">
            </a>
            ';
        }

        // 检查是否有消息或图片发送
        if (empty($message)) {
            $this->response(self::STATUS_WARNING, '消息内容不能为空或选择的图片太大');
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
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
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
