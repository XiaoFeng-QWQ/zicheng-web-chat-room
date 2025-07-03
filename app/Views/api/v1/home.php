<?php

use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Database\Base;
use ChatRoom\Core\Helpers\SystemSetting;

class HomeAPI
{
    private $db;
    private $systemSetting;
    private $user;
    private $userHelpers;
    private $helpers;

    public function __construct($helpers)
    {
        $this->db = Base::getInstance()->getConnection();
        $this->systemSetting = new SystemSetting($this->db);
        $this->user = new User();
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
            case 'user':
                $this->handleUserInfo();
                break;
            case 'config':
                $this->handleSystemConfig();
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

    private function handleUserInfo()
    {
        $userData = $this->user->getUserInfoByEnv();
        unset($userData['password']);

        $this->helpers->jsonResponse(200, true, [
            'registerUserCount' => $this->user->getUserCount(),
            'userdata' => [
                'loginStatus' => $this->user->checkUserLoginStatus(),
                'data' => $userData,
            ]
        ]);
    }

    private function handleSystemConfig()
    {
        $response = $this->systemSetting->getAllSettings();
        $this->helpers->jsonResponse(200, true, $response);
    }
}

// 实例化并处理请求
(new HomeAPI($helpers))->handleRequest();
