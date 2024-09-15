<?php

use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Controller\ChatController;

header('Content-Type: application/json');

$chatController = new ChatController();

// 获取请求方法
$method = $_SERVER['REQUEST_METHOD'];

// 工具函数：响应 JSON 数据并退出
function respondWithJson($status, $message = '')
{
    echo json_encode(['status' => $status, 'message' => $message]);
    exit();
}

switch ($method) {
    case 'POST':
        // 安全地处理和检查输入
        $message = htmlspecialchars(trim($_POST['message'] ?? ''), ENT_QUOTES, 'UTF-8');
        // 获取当前用户信息
        $user = $_SESSION['user_login_info'] ?? null;
        $userCookieInfo = json_decode($_COOKIE['user_login_info'] ?? '{}', true);
        // 检查用户是否登录
        if (empty($user) || empty($userCookieInfo)) {
            respondWithJson(ChatController::STATUS_ERROR, ChatController::MESSAGE_NOT_LOGGED_IN);
        }
        // 校验SESSION与Cookie中的用户信息是否一致
        if ($user !== $userCookieInfo) {
            respondWithJson(ChatController::STATUS_ERROR, ChatController::MESSAGE_NOT_LOGGED_IN);
        }
        // 从数据库获取用户信息
        $user_helpers = new User;
        $userInfo = $user_helpers->getUserInfo($user['username']);
        // 检查用户信息是否有效
        if (empty($userInfo)) {
            respondWithJson(ChatController::STATUS_ERROR, ChatController::MESSAGE_NOT_LOGGED_IN);
        }

        // 检查上传的图片文件
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image = $_FILES['image'];
            // 验证图片类型和大小
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($image['type'], $allowedTypes) || $image['size'] > 4097152) {
                respondWithJson(ChatController::STATUS_WARNING, '无效的图片类型或图片太大');
                return;
            }

            // 获取日期和时间
            $uploadDir = FRAMEWORK_DIR . "/StaticResources/uploads/" . date('Y/m/d') . "/u_{$userInfo['user_id']}/";

            // 创建目录（如果不存在）
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
                $this->response(self::STATUS_ERROR, '无法创建上传目录');
                return;
            }

            // 生成唯一的文件名并保存图片
            $imageName = time() . "_" . uniqid() . '.' . pathinfo($image['name'], PATHINFO_EXTENSION);
            $imagePath = $uploadDir . $imageName;

            if (!move_uploaded_file($image['tmp_name'], $imagePath)) {
                $this->response(self::STATUS_ERROR, '图片上传失败');
                return;
            }

            // 生成相对路径用于前端显示
            $relativeImagePath = "/StaticResources/uploads/" . date('Y/m/d') . "/u_{$userInfo['user_id']}/$imageName";

            // 将图片路径插入到消息内容中
            $message .= '
            <br>
            <a href="' . $relativeImagePath . '" data-fancybox>
                <img class="img-rounded" src="' . $relativeImagePath . '" alt="用户上传的图片">
            </a>
            ';
        }


        // 检查是否有消息或图片发送
        if (empty($message)) {
            $this->response(self::STATUS_WARNING, '消息内容不能为空或选择的图片太大');
            return;
        }

        // 调用ChatController处理消息发送
        if ($chatController->sendMessage($userInfo, $message)) {
            respondWithJson(ChatController::STATUS_SUCCESS, ChatController::MESSAGE_SUCCESS);
        } else {
            respondWithJson(ChatController::STATUS_ERROR, ChatController::MESSAGE_SEND_FAILED);
        }
        break;
    case 'GET':
        // 处理 GET 请求
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $messages = ($offset === 0) ? $chatController->getAllMessages() : $chatController->getMessages($offset, $limit);
        if ($messages === false) {
            respondWithJson(ChatController::STATUS_ERROR, ChatController::MESSAGE_FETCH_FAILED);
        } else {
            echo json_encode($messages);
        }
        break;
    default:
        // 无效的请求方法
        respondWithJson(ChatController::STATUS_ERROR, ChatController::MESSAGE_INVALID_REQUEST);
}
