<?php
require_once __DIR__ . '/../helper/common.php';

header('Content-Type: application/json');

use ChatRoom\Core\Controller\ChatController;
use ChatRoom\Core\Controller\Events;
use ChatRoom\Core\Helpers\SystemLog;
use ChatRoom\Core\Helpers\User;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $log = new SystemLog($db);
    $ip = new User();
    $userIp = $ip->getIp();
    $event = new Events;
    $chatController = new ChatController;

    if ($chatController->recycleMessage($id)) {
        echo json_encode(['success' => true]);
        $event->createEvent('message.revoke', $userId, $id, '管理员删除了一条消息');
        $log->insertLog('WARNING', "管理员ID {$userId} 在IP $userIp 删除消息");
    } else {
        echo json_encode(['success' => false, 'message' => '删除失败']);
    }
} else {
    echo json_encode(['success' => false, 'message' => '无效请求']);
}
