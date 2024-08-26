<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../helper/common.php';

use ChatRoom\Core\Helpers\User;

if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
    $userHelper = new User();

    try {
        $userInfo = $userHelper->getUserInfo(null, $_GET['user_id']);

        if (!empty($userInfo)) {
            echo json_encode(['success' => true, 'data' => $userInfo]);
        } else {
            echo json_encode(['success' => false, 'message' => '未获取到用户信息！']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '获取用户信息失败', 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'userid不能为空！']);
}
