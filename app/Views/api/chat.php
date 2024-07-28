<?php
// 在某个文件中解析请求，例如 ajax.php
use ChatRoom\Core\Controller\ChatController;

header('Content-Type: application/json');
$ajaxController = new ChatController();
$ajaxController->handleRequest();