<?php
require_once __DIR__ . '/../config.global.php';
// 强制设置时区为国内
date_default_timezone_set("Asia/Shanghai");

$cookieLoginToken = $_COOKIE['login_token'] ?? '';
// 数据库连接
try {
    $db = new PDO('sqlite:' . FRAMEWORK_DATABASE_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "数据库连接失败: " . $e->getMessage();
    exit;
}