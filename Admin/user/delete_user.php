<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../helper/common.php';

use ChatRoom\Core\Helpers\User;

if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
    $deleteUser = $_GET['user_id'];
    if ($deleteUser == 1) {
        exit(json_encode(['success' => false, 'message' => '不能删除创始人！']));
    }
    if ($deleteUser == $userId) {
        exit(json_encode(['success' => false, 'message' => '不能删除自己！']));
    }
    $userHelper = new User();
    try {
        $userHelper->deleteUser($deleteUser);
        echo json_encode(['success' => true, 'message' => '成功']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'userid不能为空！']);
}
