<?php

use ChatRoom\Core\Helpers\Helpers;
use ChatRoom\Core\Auth\TokenManager;
use ChatRoom\Core\Auth\CheckUserLoginStatus;

$helpers = new Helpers;
$tokenManager = new TokenManager;
$isLogin = new CheckUserLoginStatus();

// 判断是否已经退出登录
if (!$isLogin->check()) {
    header("Location: /user/login" . $helpers->getGetParams('callBack')); // 重定向到登录页面
    exit();
}

/**
 * 销毁用户会话并重定向到登录页面
 */
$tokenManager->invalidateToken($_SESSION['user_login_info']['user_id']);
if (session_status() === PHP_SESSION_ACTIVE) {
    unset($_SESSION['user_login_info']); // 销毁特定的会话变量
    setcookie('user_login_info', '', time() - 3600, '/');
}
header('Location: /'); // 重定向到登录页面
exit();
