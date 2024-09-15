<?php

use ChatRoom\Core\Database\SqlLite;
use ChatRoom\Core\Helpers\User;

// 创建 UserHelper 实例
$UserHelpers = new User();

// 检查用户是否已登录
if (empty($_SESSION['user_login_info']) || !is_array($_SESSION['user_login_info'])) {
    // 销毁用户会话并重定向到登录页面
    terminateSessionAndRedirect();
}

$username = $_SESSION['user_login_info']['username'] ?? '';
$user = $UserHelpers->getUserInfo($username);

// 检查用户信息是否有效
if (empty($user)) {
    terminateSessionAndRedirect();
}

// 插入系统消息到数据库
$db = SqlLite::getInstance()->getConnection();

// 更新用户的登录令牌为 null
$updateStmt = $db->prepare('UPDATE users SET user_login_token = :login_token WHERE user_id = :user_id');
$updateStmt->execute([
    'login_token' => null,
    'user_id' => $user['user_id']
]);

// 销毁用户会话并重定向到登录页面
terminateSessionAndRedirect();

/**
 * 销毁用户会话并重定向到登录页面
 */
function terminateSessionAndRedirect()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        unset($_SESSION['user_login_info']); // 销毁特定的会话变量
    }
    header('Location: /'); // 重定向到登录页面
    exit();
}