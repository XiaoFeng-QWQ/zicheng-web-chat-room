<?php

use ChatRoom\Core\Config\Chat;
use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Controller\Events;
use ChatRoom\Core\Modules\FileUploader;
use ChatRoom\Core\Controller\ChatController;
use ChatRoom\Core\Controller\ChatCommandController;

class ChatAPI
{
    private $event;
    private $chatConfig;
    private $userHelpers;
    private $chatController;
    private $helpers;

    public function __construct($helpers)
    {
        $this->event = new Events();
        $this->chatConfig = new Chat();
        $this->userHelpers = new User();
        $this->chatController = new ChatController();
        $this->helpers = $helpers;
    }

    public function handleRequest()
    {
        $this->authenticateUser();

        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $this->getMethodFromUri($uri);

        if (!$this->validateMethod($method)) {
            $this->helpers->jsonResponse(400, "Invalid API method");
            return;
        }

        $userInfo = $this->userHelpers->getUserInfoByEnv();

        switch ($method) {
            case 'send':
                $this->handleSendMessage($userInfo);
                break;
            case 'delete':
                $this->handleDeleteMessage($userInfo);
                break;
            case 'get':
                $this->handleGetMessages();
                break;
            case 'get-message-count':
                $this->handleGetMessageCount();
                break;
            default:
                $this->helpers->jsonResponse(406, ChatController::MESSAGE_INVALID_REQUEST);
        }
    }

    private function authenticateUser()
    {
        if (!$this->userHelpers->getUserInfoByEnv()) {
            $this->helpers->jsonResponse(401, "未登录或登录已过期");
            exit;
        }
    }

    private function getMethodFromUri($uri)
    {
        $parts = explode('/', trim($uri, '/'));
        return $parts[3] ?? null;
    }

    private function validateMethod($method)
    {
        return preg_match('/^[a-zA-Z0-9]{1,30}$/', $method);
    }

    private function handleSendMessage($userInfo)
    {
        $message = htmlspecialchars(trim($_POST['message'] ?? ''), ENT_QUOTES, 'UTF-8');
        $isMarkdown = $_POST['isMarkdown'] ?? false;
        $replyTo = isset($_POST['replyTo']) ? (int)$_POST['replyTo'] : null;

        // 处理文件上传
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $message = $this->handleFileUpload($userInfo, $message);
        }

        if (empty($message)) {
            $this->helpers->jsonResponse(406, '消息内容不能为空');
            return;
        }

        if ($replyTo !== null && $replyTo <= 0) {
            $this->helpers->jsonResponse(406, '无效的回复标识符');
            return;
        }

        // 检查是否是命令
        if (strpos($message, '/') === 0) {
            $this->handleCommand($message, $userInfo);
            return;
        }

        // 发送普通消息
        if ($this->chatController->sendMessage($userInfo, $message, $isMarkdown, $replyTo)) {
            $this->helpers->jsonResponse(200, ChatController::MESSAGE_SUCCESS);
        } else {
            $this->helpers->jsonResponse(406, ChatController::MESSAGE_SEND_FAILED);
        }
    }

    private function handleFileUpload($userInfo, $message)
    {
        $uploader = new FileUploader(
            $this->chatConfig->uploadFile['allowTypes'],
            $this->chatConfig->uploadFile['maxSize']
        );

        $file = $uploader->upload($_FILES['file'], $userInfo['user_id']);
        if ($file === false) {
            $this->helpers->jsonResponse(406, '文件上传失败或此文件类型不允许');
            exit;
        }

        return $message . sprintf(
            '<br>[!file(path_"%s", name_"%s", type_"%s", size_"%s", download_"true")]',
            '/api/v1/files/' . $file['md5'],
            $file['name'],
            $file['type'],
            $file['size']
        );
    }

    private function handleCommand($message, $userInfo)
    {
        $commandController = new ChatCommandController();
        $response = $commandController->command($message, $userInfo);

        $status = $response ? 200 : 403;
        $message = $response ?: '执行失败或权限不足';
        $this->helpers->jsonResponse($status, $message);
    }

    private function handleDeleteMessage($userInfo)
    {
        $msgId = $_GET['id'] ?? '0';
        $msgData = $this->chatController->getMessagesByConditions(['messageId' => $msgId]);

        if ($msgData === null) {
            $this->helpers->jsonResponse(404, '消息未找到');
            return;
        }

        if ($userInfo['group_id'] === 1 || $msgData[0]['user_name'] === $userInfo['username']) {
            $messageContent = $this->getRevokeMessageContent($userInfo, $msgData[0]);

            $this->event->createEvent('message.revoke', $userInfo['user_id'], $msgId, $messageContent);
            $this->chatController->recycleMessage($msgId);
            $this->helpers->jsonResponse(200, ChatController::MESSAGE_SUCCESS);
        } else {
            $this->helpers->jsonResponse(406, '撤回失败：非自己消息');
        }
    }

    private function getRevokeMessageContent($userInfo, $msgData)
    {
        if ($userInfo['group_id'] === 1 && $msgData['user_name'] !== $userInfo['username']) {
            return '管理员 ' . $userInfo['username'] . ' 撤回了一条成员消息';
        }
        return $userInfo['username'] . ' 撤回了一条消息';
    }

    private function handleGetMessages()
    {
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $eventOffset = isset($_GET['eventOffset']) ? (int)$_GET['eventOffset'] : 0;
        $eventLimit = isset($_GET['eventLimit']) ? (int)$_GET['eventLimit'] : 10;

        $result = $this->chatController->getMessages($offset, $limit, $eventOffset, $eventLimit);

        if (!$result) {
            $this->helpers->jsonResponse(406, ChatController::MESSAGE_FETCH_FAILED);
        } else {
            $this->helpers->jsonResponse(200, true, $result);
        }
    }

    private function handleGetMessageCount()
    {
        $result = $this->chatController->getMessageCount();

        if (!$result) {
            $this->helpers->jsonResponse(406, '获取消息总数失败');
        } else {
            $this->helpers->jsonResponse(200, true, $result);
        }
    }
}

// 实例化并处理请求
(new ChatAPI($helpers))->handleRequest();