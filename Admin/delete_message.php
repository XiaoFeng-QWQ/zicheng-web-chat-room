<?php
require_once __DIR__ . '/database_connection.php'; // 请确保正确的数据库连接脚本路径

// 启动会话
session_start();

header('Content-Type: application/json');

// 验证登录状态和Token
// 查询数据库中的login_token和group_id
$stmt = $db->prepare('SELECT login_token, group_id FROM users WHERE user_id = :user_id');
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// 检查数据库中的login_token是否与会话和cookie中的一致，以及用户是否为管理员
if ($user['login_token'] !== $_SESSION['login_token'] || $user['login_token'] !== $cookieLoginToken || $user['group_id'] != 1) {
    // 若不一致或不是管理员，登出并重定向到登录页面
    session_unset();
    session_destroy();
    setcookie('login_token', '', time() - 3600, '/');
    header('Location: /Admin/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);

    $stmt = $db->prepare('DELETE FROM messages WHERE id = ?');
    if ($stmt->execute([$id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => '删除失败']);
    }
} else {
    echo json_encode(['success' => false, 'message' => '无效请求']);
}
