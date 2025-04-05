<?php

use ChatRoom\Core\Controller\Events;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = isset(explode('/', trim($uri, '/'))[3]) ? explode('/', trim($uri, '/'))[3] : null;
$event = new Events;
// 验证 API 名称是否符合字母和数字的格式，且长度不超过 30
if (preg_match('/^[a-zA-Z0-9]{1,30}$/', $method)) {
    switch ($method) {
        case 'count':
            $count = $event->getEventsCount($_GET['type'] ?? null);
            $helpers->jsonResponse(200, true, ['count' => $count]);
            break;
        case 'get':
            // 处理分页参数
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $result = $event->getEvents($offset, $limit);
            $helpers->jsonResponse(200, true, ['event' => $result]);
            break;
        default:
            $helpers->jsonResponse(406, false, ['error' => 'Invalid method']);
            break;
    }
} else {
    // 如果 method 不符合字母数字格式，返回 400 错误
    $helpers->jsonResponse(400, "Invalid API method");
}
