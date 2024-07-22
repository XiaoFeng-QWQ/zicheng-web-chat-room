<?php

use ChatRoom\Core\Database\SqlLite;
use ChatRoom\Core\Helpers\User;

// 创建 UserHelper 实例
$UserHelpers = new User;

// 检查用户是否已登录
if (!isset($_SESSION['userinfo']) || !is_array($_SESSION['userinfo'])) {
    // 销毁用户会话并重定向到登录页面
    if (session_status() === PHP_SESSION_ACTIVE) {
        unset($_SESSION['userinfo']); // 销毁特定的会话变量
    }
    header('Location: /'); // 重定向到登录页面
    exit();
}

$username = $_SESSION['userinfo']['username']; // 确保username从session中获取
$user = $UserHelpers->getUserInfo($username);

// 检查用户信息是否有效
if (empty($user)) {
    // 销毁用户会话并重定向到登录页面
    if (session_status() === PHP_SESSION_ACTIVE) {
        unset($_SESSION['userinfo']); // 销毁特定的会话变量
    }
    header('Location: /');
    exit();
}

// 插入系统消息到数据库
$db = SqlLite::getInstance()->getConnection();
$stmt = $db->prepare(
    'INSERT INTO messages (user_name, content, type, created_at) VALUES (?, ?, ?, ?)'
);
$stmt->execute([
    $username,
    "用户 $username 暂时退出了……",
    'system',
    date('Y-m-d H:i:s')
]);

// 销毁用户会话并重定向到登录页面
if (session_status() === PHP_SESSION_ACTIVE) {
    unset($_SESSION['userinfo']); // 销毁特定的会话变量
}

header('Location: /');
exit();
