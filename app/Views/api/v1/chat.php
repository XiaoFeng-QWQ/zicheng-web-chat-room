<?php

use ChatRoom\Core\Config\Chat;
use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Modules\FileUploader;
use ChatRoom\Core\Modules\TokenManager;
use ChatRoom\Core\Controller\ChatController;
use ChatRoom\Core\Controller\ChatCommandController;

$chatConfig = new Chat;
$userHelpers = new User;
$tokenManager = new TokenManager;
$chatController = new ChatController;
$chatCommandController = new ChatCommandController();

// 获取请求方法
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        // 安全地处理和检查输入
        $message = htmlspecialchars(trim($_POST['message'] ?? ''), ENT_QUOTES, 'UTF-8');
        $userCookieInfo = json_decode($_COOKIE['user_login_info'], true);
        $tokenInfo = $tokenManager->getInfo($userCookieInfo['token']);
        $userInfo = $userHelpers->getUserInfo(null, $tokenInfo['user_id']);

        // 处理上传文件
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $uploadedFile = new FileUploader($chatConfig->uploadFile['allowTypes'], $chatConfig->uploadFile['maxSize']);
            $uploadedFile = $uploadedFile->upload($_FILES['file'], $userInfo['user_id']);
            if ($uploadedFile === false) {
                $helpers->jsonResponse(406, '文件上传失败或此文件类型不允许');
            }

            // 将文件信息插入到消息模板中
            $message .= sprintf(
                '<br>[!file(path_"%s", name_"%s", type_"%s", size_"%s", download_"true")]',
                $uploadedFile['url'],
                $uploadedFile['name'],
                $uploadedFile['type'],
                $uploadedFile['size']
            );
        }

        // 检查是否有消息或发送
        if (empty($message)) {
            $helpers->jsonResponse(406, '消息内容不能为空或选择的太大');
            return;
        }

        // 检查是否是命令
        if (strpos($message, '/') === 0) {
            $commandResponse = $chatCommandController->command($message, $userInfo);

            // 判断命令执行结果，返回相应的JSON响应
            $status = $commandResponse ? 200 : 403;
            $message = $commandResponse ?: '执行失败或权限不足';
            $helpers->jsonResponse($status, $message);
        }

        // 调用ChatController处理消息发送
        if ($chatController->sendMessage($userInfo, $message)) {
            $helpers->jsonResponse(200, ChatController::MESSAGE_SUCCESS);
        } else {
            $helpers->jsonResponse(406, ChatController::MESSAGE_SEND_FAILED);
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
            $helpers->jsonResponse(406, ChatController::MESSAGE_FETCH_FAILED);
        } else {
            $helpers->jsonResponse(200, 'true', $result);
        }
        break;
    default:
        // 无效的请求方法
        $helpers->jsonResponse(406, ChatController::MESSAGE_INVALID_REQUEST);
}