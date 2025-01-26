<?php

use ChatRoom\Core\Controller\Events;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = isset(explode('/', trim($uri, '/'))[3]) ? explode('/', trim($uri, '/'))[3] : null;
$event = new Events;

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$result = $event->getEvents($offset, $limit);
$helpers->jsonResponse(200, true, ['event' => $result]);
