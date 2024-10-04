<?php
require_once __DIR__ . '/../../config.global.php';
// 检查是否安装
if (!defined('FRAMEWORK_DATABASE_PATH')) {
    header('Location: /Admin/install/index.php');
    exit;
}
// 数据库连接
try {
    $db = new PDO('sqlite:' . FRAMEWORK_DATABASE_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA journal_mode=WAL;');
    $db->beginTransaction();
} catch (PDOException $e) {
    throw new Exception('数据库错误：'. $e->getMessage());
}