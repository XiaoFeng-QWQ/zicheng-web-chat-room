<?php

use ChatRoom\Core\Config\Chat;
use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Controller\Events;
use ChatRoom\Core\Modules\FileUploader;
use ChatRoom\Core\Modules\TokenManager;
use ChatRoom\Core\Controller\ChatController;
use ChatRoom\Core\Controller\ChatCommandController;

$event = new Events;
$chatConfig = new Chat;
$userHelpers = new User;
$tokenManager = new TokenManager;
$chatController = new ChatController;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = isset(explode('/', trim($uri, '/'))[3]) ? explode('/', trim($uri, '/'))[3] : null;

// 验证 API 名称是否符合字母和数字的格式，且长度不超过 30
if (preg_match('/^[a-zA-Z0-9]{1,30}$/', $method)) {

    $message = htmlspecialchars(trim($_POST['message'] ?? ''), ENT_QUOTES, 'UTF-8');
    $userInfo = $userHelpers->getUserInfoByEnv();

    switch ($method) {
        case 'send':
            $isMarkdown = $_POST['isMarkdown'];
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
                $chatCommandController = new ChatCommandController();
                $commandResponse = $chatCommandController->command($message, $userInfo);

                // 判断命令执行结果，返回相应的JSON响应
                $status = $commandResponse ? 200 : 403;
                $message = $commandResponse ?: '执行失败或权限不足';
                $helpers->jsonResponse($status, $message);
            }

            // 调用ChatController处理消息发送
            if ($chatController->sendMessage($userInfo, $message, $isMarkdown)) {
                $helpers->jsonResponse(200, ChatController::MESSAGE_SUCCESS);
            } else {
                $helpers->jsonResponse(406, ChatController::MESSAGE_SEND_FAILED);
            }
            break;
        case 'delete':
            // 撤回消息->检查权限如果是管理员可强制撤回->对应消息ID状态标记为delete->插入一条type为user.delete的消息类型
            $msgId = isset($_GET['id']) ? $_GET['id'] : '0';
            $msgData = $chatController->getMessagesByConditions(['messageId' => $msgId]);
            if ($msgData === null) {
                $helpers->jsonResponse(404, '消息未找到');
                exit;
            }
            if ($userInfo['group_id'] === 1 || $msgData[0]['user_name'] === $userInfo['username']) {
                // 根据撤回者的身份和消息的创建者判断撤回内容
                if ($userInfo['group_id'] === 1 && $msgData[0]['user_name'] !== $userInfo['username']) {
                    // 管理员撤回成员的消息
                    $messageContent = '管理员 ' . $userInfo['username'] . ' 撤回了一条成员消息';
                } elseif ($msgData[0]['user_name'] === $userInfo['username']) {
                    $messageContent = $userInfo['username'] . ' 撤回了一条消息';
                }
                // 创建事件
                $event->createEvent('message.revoke', $userInfo['user_id'], $msgId, $messageContent);
                $chatController->recycleMessage($msgId, $messageContent);
                $helpers->jsonResponse(200, ChatController::MESSAGE_SUCCESS);
            } else {
                $helpers->jsonResponse(406, '撤回失败：非自己消息');
            }
            break;
        case 'get':
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

            $result = $chatController->getMessages($offset, $limit);
            if (!$result) {
                $helpers->jsonResponse(406, ChatController::MESSAGE_FETCH_FAILED);
            } else {
                $helpers->jsonResponse(200, true, $result);
            }
            break;
        case 'get-message-count':
            $result = $chatController->getMessageCount();
            if (!$result) {
                $helpers->jsonResponse(406, '获取消息总数失败');
            } else {
                $helpers->jsonResponse(200, true, $result);
            }
            break;
        default:
            // 无效的请求方法
            $helpers->jsonResponse(406, ChatController::MESSAGE_INVALID_REQUEST);
    }
} else {
    // 如果 method 不符合字母数字格式，返回 400 错误
    $helpers->jsonResponse(400, "Invalid API method");
}
