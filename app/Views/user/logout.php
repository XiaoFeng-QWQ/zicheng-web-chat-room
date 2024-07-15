<?php
use ChatRoom\Core\Database\SqlLite;

$db = SqlLite::getInstance()->getConnection();
$stmt = $db->prepare('INSERT INTO messages (user_name, content, type) VALUES (?, ?, ?)');
$stmt->execute([$_SESSION['userinfo']['username'], '用户' . $_SESSION['userinfo']['username']  . '暂时退出了……', 'system']);

// 不需要再次调用session_start(); 
if (session_status() == PHP_SESSION_ACTIVE) {
    unset($_SESSION['userinfo']); // 销毁特定的会话变量
}

header('Location: /');
exit();