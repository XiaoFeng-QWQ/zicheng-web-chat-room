<?php

/**
 * 初始化设置
 */
// 强制设置时区为国内
date_default_timezone_set("Asia/Shanghai");
session_start();

/**
 * 文件引入区
 */
require_once __DIR__ . '/../../config.global.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/database_connection.php';

/**
 * 初始化变量
 */
$cookieLoginToken = isset($_COOKIE['admin_login_info']) ? json_decode($_COOKIE['admin_login_info'], true)['login_token'] ?? '' : '';

/**
 * 验证权限
 */
// 验证会话中的用户ID
$userId = $_SESSION['admin_login_info']['user_id'] ?? null;
if ($userId === null) {
    logoutAndRedirect();
}
// 查询数据库中的login_token和group_id
$stmt = $db->prepare('SELECT admin_login_token, group_id FROM users WHERE user_id = :user_id');
$stmt->execute(['user_id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
// 检查数据库中的login_token是否与会话和cookie中的一致，以及用户是否为管理员
if (
    $user === false ||
    $user['admin_login_token'] !== $_SESSION['admin_login_info']['login_token'] ?? null ||
    $user['admin_login_token'] !== $cookieLoginToken ?? null ||
    $user['group_id'] != 1
) {
    logoutAndRedirect();
}
/**
 * 登出并重定向到登录页面
 */
function logoutAndRedirect()
{
    unset($_SESSION['admin_login_info']);
    setcookie('admin_login_info', '', time() - 3600, '/'); // 删除cookie

    // 确保没有之前的输出，以便能够成功重定向
    ob_clean();
    header('Location: /Admin/login.php');
    exit;
}
