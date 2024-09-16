<?php

use ChatRoom\Core\Config\Chat;
use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Controller\ChatController;
use ChatRoom\Core\Controller\ChatCommndController;


header('Content-Type: application/json');

$chatController = new ChatController();
$chatCommandController = new ChatCommndController();

$chatConfig = new Chat();

// 获取请求方法
$method = $_SERVER['REQUEST_METHOD'];

// 工具函数：响应 JSON 数据并退出
function respondWithJson($status, $message = '', $isCommnd = false)
{
    exit(json_encode(['status' => $status, 'message' => $message, 'isCommnd' => $isCommnd], JSON_UNESCAPED_UNICODE));
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
                respondWithJson(ChatController::STATUS_ERROR, '无法创建上传目录');
                return;
            }

            // 生成唯一的文件名并保存图片
            $imageName = time() . "_" . uniqid() . '.' . pathinfo($image['name'], PATHINFO_EXTENSION);
            $imagePath = $uploadDir . $imageName;

            if (!move_uploaded_file($image['tmp_name'], $imagePath)) {
                respondWithJson(ChatController::STATUS_ERROR, '图片上传失败');
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
            respondWithJson(ChatController::STATUS_WARNING, '消息内容不能为空或选择的图片太大');
            return;
        }

        // 检查是否是命令，并由ChatCommndController执行相应操作
        if (strpos($message, '/') === 0) { // 以'/'开头
            $command = explode(' ', $message)[0]; // 获取指令名
            if (array_key_exists($command, $chatConfig->ChatCommndList)) {
                $action = $chatConfig->ChatCommndList[$command]['action'][0];

                // 检查用户权限
                // 为true需要管理员否则不需要
                $requiredPermission = $chatConfig->ChatCommndList[$command]['isAdmin'];
                if ($requiredPermission && $userInfo['group_id'] != 1) {
                    respondWithJson(ChatController::STATUS_ERROR, '权限不足');
                } else {
                    // 执行对应的命令函数
                    $response = "
                    <style>
                        .CommndTitle {
                            color: #333;
                            background: #f4f4f4;
                            border: 2px solid #ddd;
                            border-radius: 10px;
                            padding: 7px;
                            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                            margin: auto;
                            line-height: 1.6;
                        }
                        .CommndTitle::before {
                            content: '✧ ';
                            color: #a0a0a0;
                        }
                        .CommndTitle::after {
                            content: ' ✧';
                            color: #a0a0a0;
                        }
                    </style>
                    <p class='CommndTitle'>---子辰指令系统V0.0.1---</p>";
                    $response .= call_user_func([$chatCommandController, $action]);
                    respondWithJson(ChatController::STATUS_SUCCESS, $response, true);
                }
            }
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

        if ($offset === 0) {
            $result = $chatController->getAllMessages();
        } else {
            $result = $chatController->getMessages($offset, $limit);
        }

        if (!$result) {
            respondWithJson(ChatController::STATUS_ERROR, ChatController::MESSAGE_FETCH_FAILED);
        } else {
            // 输出结果，包括消息和总数
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        }
        break;
    default:
        // 无效的请求方法
        respondWithJson(ChatController::STATUS_ERROR, ChatController::MESSAGE_INVALID_REQUEST);
}
