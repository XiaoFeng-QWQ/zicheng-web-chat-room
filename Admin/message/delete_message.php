<?php
require_once __DIR__ . '/../helper/common.php';

use ChatRoom\Core\Helpers\SystemLog;
use ChatRoom\Core\Helpers\User;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $log = new SystemLog($db);
    $User = new User();
    $ip = new User();
    $userIp = $ip->getIp();

    $stmt = $db->prepare('DELETE FROM messages WHERE id = ?');
    if ($stmt->execute([$id])) {
        echo json_encode(['success' => true]);

        $log->insertLog('WARNING', "管理员ID {$_SESSION['admin_login_info']['user_id']} 在IP $userIp 删除消息");
    } else {
        echo json_encode(['success' => false, 'message' => '删除失败']);
    }
} else {
    echo json_encode(['success' => false, 'message' => '无效请求']);
}
