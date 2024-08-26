<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../helper/common.php';

use ChatRoom\Core\Helpers\User;

if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
    $userId = $_GET['user_id'];
    $userHelper = new User();
    try {
        $userInfo = $userHelper->getUserInfo(null, $userId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 获取提交的数据并构建数组
            $data = [
                'username' => $_POST['username'],
                'group_id' => $_POST['group_id'],
                'email' => $_POST['email']
            ];

            // 更新用户信息
            if ($userHelper->updateUser($userId, $data)) {
                echo json_encode(['success' => true, 'message' => '更新成功']);
            } else {
                echo json_encode(['success' => false, 'message' => '更新失败']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => '请求方法错误！']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'userid不能为空！']);
}
