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

use ChatRoom\Core\Helpers\SystemSetting;
use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Modules\TokenManager;

/**
 * 初始化变量
 */
$SystemSetting = new SystemSetting($db);
$loginStatus = new User;
$tokenManager = new TokenManager;

/**
 * 验证权限
 */
// 验证会话中的用户ID
if (!$loginStatus->checkUserLoginStatus()) {
    logoutAndRedirect();
}
$user_login_info = json_decode($_COOKIE['user_login_info'], true);
$userId = $tokenManager->getInfo($user_login_info['token'])['user_id'] ?? null;

if ($userId === null) {
    logoutAndRedirect();
}
// 查询数据库中的group_id
$stmt = $db->prepare('SELECT group_id FROM users WHERE user_id = :user_id');
$stmt->execute(['user_id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user === false || $user['group_id'] != 1) {
    logoutAndRedirect();
}
/**
 * 登出并重定向到登录页面
 */
function logoutAndRedirect()
{
    unset($_SESSION['user_login_info']);
    setcookie('user_login_info', '', time() - 3600, '/'); // 删除cookie

    // 确保没有之前的输出，以便能够成功重定向
    ob_clean();
    header('Location: /Admin/login.php?callBack=' . $_SERVER['REQUEST_URI']);
    exit;
}
