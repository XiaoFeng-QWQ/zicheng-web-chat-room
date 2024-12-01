<?php
// 允许所有来源
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

use ChatRoom\Core\Config\Chat;
use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Helpers\Helpers;
use ChatRoom\Core\Controller\ChatController;
use ChatRoom\Core\Controller\ChatCommandController;

$helpres = new Helpers;
$chatConfig = new Chat();
$chatController = new ChatController();
$chatCommandController = new ChatCommandController();

// 获取请求方法
$method = $_SERVER['REQUEST_METHOD'];

/**
 * 使用 JSON 或纯文本响应并设置 HTTP 状态码
 * 内置exit()
 *
 * @param [type] $statusCode
 * @param [type] $message
 * @param [type] $isCommnd
 * @return void
 */
function respondWithJson($status, $message = '', $isCommnd = false)
{
    exit(json_encode(['status' => $status, 'message' => $message, 'isCommnd' => $isCommnd], JSON_UNESCAPED_UNICODE));
}

switch ($method) {
    case 'POST':
        $userHelpers = new User;
        // 安全地处理和检查输入
        $message = htmlspecialchars(trim($_POST['message'] ?? ''), ENT_QUOTES, 'UTF-8');
        // 检查用户是否登录
        if (!$userHelpers->checkUserLoginStatus()) {
            respondWithJson(ChatController::STATUS_ERROR, ChatController::MESSAGE_NOT_LOGGED_IN);
        }
        // 获取当前用户信息
        $user = $_SESSION['user_login_info'] ?? null;
        $userInfo = $userHelpers->getUserInfo($user['username']);

        // 检查上传的文件
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            // 获取文件的 MIME 类型
            $fileType = mime_content_type($_FILES['file']['tmp_name']);
            // 检查文件 MIME 类型和扩展名是否匹配
            $allowed = false;
            if (in_array($fileType, $chatConfig->uploadFile['allowTypes'])) {
                $allowed = true;
            }
            if (!$allowed) {
                respondWithJson(ChatController::STATUS_WARNING, '无效的文件类型');
            }

            // 检查文件大小
            if ($_FILES['file']['size'] > $chatConfig->uploadFile['maxSize']) {
                respondWithJson(ChatController::STATUS_WARNING, '文件太大');
            }

            // 获取日期和时间
            $uploadDir = FRAMEWORK_DIR . "/StaticResources/uploads/" . date('Y/m/d') . "/u_{$userInfo['user_id']}/";
            // 创建目录（如果不存在）
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
                respondWithJson(ChatController::STATUS_ERROR, '无法创建上传目录');
            }
            // 生成唯一的文件名并保存
            $fileName = time() . "_" . uniqid() . '.' . pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
            $filePath = $uploadDir . $fileName;
            if (!move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
                respondWithJson(ChatController::STATUS_ERROR, '上传失败');
            }

            $relativeImagePath = $helpres->getCurrentUrl() . "/StaticResources/uploads/" . date('Y/m/d') . "/u_{$userInfo['user_id']}/$fileName";
            function generateFileTemplate($fileData)
            {
                $template = '[!file(';
                foreach ($fileData as $key => $value) {
                    $template .= $key . '="' . $value . '", ';
                }
                $template = rtrim($template, ', ');
                $template .= ')]';
                return $template;
            }
            $message .= generateFileTemplate([
                'path' => $relativeImagePath,
                'name' =>  $_FILES['file']['name'],
                'type' =>  $_FILES['file']['type'],
                'size' => round($_FILES['file']['size'] / 1024, 2) . 'KB',
                'download' => 'true'
            ]);
        }

        // 检查是否有消息或发送
        if (empty($message)) {
            respondWithJson(ChatController::STATUS_WARNING, '消息内容不能为空或选择的太大');
            return;
        }

        // 检查是否是命令，并由 ChatCommandController 执行相应操作
        if (strpos($message, '/') === 0) { // 以 '/' 开头
            $parts = explode(' ', $message);
            $command = $parts[0]; // 获取指令名
            $params = array_slice($parts, 1); // 获取参数列表
            if (isset($chatConfig->chatCommandList[$command])) {
                $commandConfig = $chatConfig->chatCommandList[$command];
                $action = $commandConfig['action'][0];
                $isAdminRequired = $commandConfig['isAdmin'];
                // 检查用户权限
                if ($isAdminRequired && $userInfo['group_id'] != 1) {
                    respondWithJson(ChatController::STATUS_ERROR, '权限不足');
                    return;
                }
                // 执行对应的命令函数
                try {
                    $response = '';
                    if (method_exists($chatCommandController, $action)) {
                        $response .= call_user_func_array([$chatCommandController, $action], $params);
                    } else {
                        $response .= '命令配置错误 函数不存在';
                    }
                    // 发送消息前确认是否需要管理员权限
                    if (!$isAdminRequired && !$commandConfig['iSelf']) {
                        $chatController->sendMessage($userInfo, $message);
                        $chatController->insertSystemMessage('system', $response, 'system');
                        respondWithJson(ChatController::STATUS_SUCCESS, ChatController::MESSAGE_SUCCESS);
                    }
                    respondWithJson(ChatController::STATUS_SUCCESS, $response, true);
                } catch (Exception $e) {
                    respondWithJson(ChatController::STATUS_ERROR, '执行命令时发生错误: ' . $e->getMessage());
                }
                $chatController->sendMessage($userInfo, $message);
            } else {
                $chatConfig = new Chat();
                respondWithJson(ChatController::STATUS_ERROR, '未知命令');
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
