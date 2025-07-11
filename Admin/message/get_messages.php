<?php
require_once __DIR__ . '/../helper/common.php';
header('Content-Type: application/json');

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 30; // 每页显示的消息数量
$offset = ($page - 1) * $limit;

// 获取消息总数
$totalStmt = $db->query("SELECT COUNT(*) FROM messages WHERE status = 'active'");
$totalMessages = $totalStmt->fetchColumn();
$totalPages = ceil($totalMessages / $limit);

// 获取当前页的消息并按ID排序
$stmt = $db->prepare('SELECT * FROM messages WHERE status = "active" ORDER BY id DESC LIMIT ? OFFSET ?');
$stmt->bindParam(1, $limit, PDO::PARAM_INT);
$stmt->bindParam(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 返回JSON数据
echo json_encode([
    'totalPages' => $totalPages,
    'currentPage' => $page,
    'messages' => $messages
]);
