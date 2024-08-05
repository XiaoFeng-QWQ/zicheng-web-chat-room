<?php
session_start();
require_once __DIR__ . '/database_connection.php'; // 请确保正确的数据库连接脚本路径
header('Content-Type: application/json');

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 30; // 每页显示的消息数量
$offset = ($page - 1) * $limit;

// 获取消息总数
$totalStmt = $db->query('SELECT COUNT(*) FROM messages');
$totalMessages = $totalStmt->fetchColumn();
$totalPages = ceil($totalMessages / $limit);

// 获取当前页的消息并按ID排序
$stmt = $db->prepare('SELECT * FROM messages ORDER BY id DESC LIMIT ? OFFSET ?');
$stmt->execute([$limit, $offset]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 返回JSON数据
echo json_encode([
    'totalPages' => $totalPages,
    'currentPage' => $page,
    'messages' => $messages
]);