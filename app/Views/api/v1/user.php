<?php

use ChatRoom\Core\Helpers\User;
use Gregwar\Captcha\PhraseBuilder;
use ChatRoom\Core\Modules\TokenManager;
use ChatRoom\Core\Helpers\SystemSetting;
use ChatRoom\Core\Controller\UserController;
use ChatRoom\Core\Database\Base;

class UserAPI
{
    private $userHelpers;
    private $tokenManager;
    private $userController;
    private $setting;
    private $helpers;

    public function __construct($helpers)
    {
        $this->userHelpers = new User();
        $this->tokenManager = new TokenManager();
        $this->userController = new UserController();
        $this->setting = new SystemSetting(Base::getInstance()->getConnection());
        $this->helpers = $helpers;
    }

    public function handleRequest()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $this->getMethodFromUri($uri);

        if (!$this->validateMethod($method)) {
            $this->helpers->jsonResponse(400, "Invalid API method");
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: [];

        switch ($method) {
            case 'register':
                $this->handleRegister($input);
                break;
            case 'login':
                $this->handleLogin($input);
                break;
            case 'logout':
                $this->handleLogout();
                break;
            default:
                $this->helpers->jsonResponse(400, UserController::INVALID_METHOD_MESSAGE);
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

    private function handleRegister($input)
    {
        if ($this->setting->getSetting('enable_user_registration') !== true) {
            $this->helpers->jsonResponse(403, UserController::DISABLE_USER_REGIDTRATION);
            return;
        }

        if (!$this->validateCaptcha($input['captcha'] ?? '')) {
            $this->helpers->jsonResponse(400, UserController::CAPTCHA_ERROR);
            return;
        }

        $result = $this->userController->register(
            $input['username'] ?? '',
            $input['password'] ?? '',
            $input['confirm_password'] ?? ''
        );

        $this->helpers->jsonResponse($result['status'] ?? 400, $result['message'] ?? '');
    }

    private function handleLogin($input)
    {
        if (!$this->validateCaptcha($input['captcha'] ?? '')) {
            $this->helpers->jsonResponse(400, UserController::CAPTCHA_ERROR);
            return;
        }

        $result = $this->userController->login(
            $input['username'] ?? '',
            $input['password'] ?? ''
        );

        $this->helpers->jsonResponse($result['status'] ?? 400, $result['message'] ?? '');
    }

    private function handleLogout()
    {
        if (!$this->userHelpers->getUserInfoByEnv()) {
            $this->helpers->jsonResponse(401, "未登录或登录已过期");
            return;
        }

        if ($this->userHelpers->checkUserLoginStatus()) {
            setcookie('user_login_info', '', time() - 3600, '/');
            $this->helpers->jsonResponse(200, $this->tokenManager->delet($this->userHelpers->getUserInfoByEnv()['user_id']));
        } else {
            $this->helpers->jsonResponse(200, true);
        }
    }

    private function validateCaptcha($captcha)
    {
        if (!isset($_SESSION['captcha']) || !PhraseBuilder::comparePhrases($_SESSION['captcha'], $captcha)) {
            unset($_SESSION['captcha']);
            return false;
        }
        unset($_SESSION['captcha']);
        return true;
    }
}

// 实例化并处理请求
(new UserAPI($helpers))->handleRequest();