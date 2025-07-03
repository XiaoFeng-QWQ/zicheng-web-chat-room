<?php

use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Controller\Events;

class EventAPI
{
    private $event;
    private $userHelpers;
    private $helpers;

    public function __construct($helpers)
    {
        $this->event = new Events();
        $this->userHelpers = new User();
        $this->helpers = $helpers;
    }

    public function handleRequest()
    {
        $this->authenticateUser();

        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $this->getMethodFromUri($uri);

        if (!$this->validateMethod($method)) {
            $this->helpers->jsonResponse(400, "Invalid API method");
            return;
        }

        switch ($method) {
            case 'count':
                $this->handleEventCount();
                break;
            case 'get':
                $this->handleGetEvents();
                break;
            default:
                $this->helpers->jsonResponse(406, false, ['error' => 'Invalid method']);
        }
    }

    private function authenticateUser()
    {
        if (!$this->userHelpers->getUserInfoByEnv()) {
            $this->helpers->jsonResponse(401, "未登录或登录已过期");
            exit;
        }
    }

    private function getMethodFromUri($uri)
    {
        $parts = explode('/', trim($uri, '/'));
        return $parts[3] ?? null;
    }

    private function validateMethod($method)
    {
        return preg_match('/^[a-zA-Z0-9]{1,30}$/', $method);
    }

    private function handleEventCount()
    {
        $count = $this->event->getEventsCount($_GET['type'] ?? null);
        $this->helpers->jsonResponse(200, true, ['count' => $count]);
    }

    private function handleGetEvents()
    {
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

        $result = $this->event->getEvents($offset, $limit);
        $this->helpers->jsonResponse(200, true, ['event' => $result]);
    }
}

// 实例化并处理请求
(new EventAPI($helpers))->handleRequest();