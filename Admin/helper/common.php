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
/**
 * 获取登录Token
 * @var mixed
 */
$cookieLoginToken = $_COOKIE['login_token'] ?? '';

/**
 * 验证权限
 */
// 验证登录状态和Token
// 查询数据库中的login_token和group_id
$stmt = $db->prepare('SELECT login_token, group_id FROM users WHERE user_id = :user_id');
$stmt->execute(['user_id' => $_SESSION['user_id'] ?? null]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// 检查数据库中的login_token是否与会话和cookie中的一致，以及用户是否为管理员
if (
    $user === false
    ||
    $user['login_token'] !== $_SESSION['login_token']
    ||
    $user['login_token'] !== $cookieLoginToken
    ||
    $user['group_id'] != 1
) {
    // 若不一致或不是管理员，登出并重定向到登录页面
    session_unset();
    session_destroy();
    setcookie('login_token', '', time() - 3600, '/');

    // 确保没有之前的输出，以便能够成功重定向
    ob_clean();
    header('Location: /Admin/login.php');
    exit;
}
