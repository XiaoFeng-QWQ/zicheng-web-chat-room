<?php

use ChatRoom\Core\Helpers\User;
use Gregwar\Captcha\PhraseBuilder;
use ChatRoom\Core\Database\SqlLite;
use ChatRoom\Core\Modules\TokenManager;
use ChatRoom\Core\Helpers\SystemSetting;
use ChatRoom\Core\Controller\UserController;

$isLogin = new User;
$tokenManager = new TokenManager;
$userController = new UserController;
$setting = new SystemSetting(SqlLite::getInstance()->getConnection());

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = isset(explode('/', trim($uri, '/'))[3]) ? explode('/', trim($uri, '/'))[3] : null;

// 验证 API 名称是否符合字母和数字的格式，且长度不超过 30
if (preg_match('/^[a-zA-Z0-9]{1,30}$/', $method)) {
    // 读取和解析输入的 JSON 数据
    $input = json_decode(file_get_contents('php://input'), true);
    $captcha = $input['captcha'] ?? '';
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    $confirmPassword = $method === 'register' ? $input['confirm_password'] ?? '' : null;
    switch ($method) {
        case 'register':
            if ($setting->getSetting('enable_user_registration') === true) {
                if (isset($_SESSION['captcha']) && PhraseBuilder::comparePhrases($_SESSION['captcha'], $captcha)) {
                    unset($_SESSION['captcha']);
                    exit($userController->register($username, $password, $confirmPassword));
                } else {
                    // 验证码错误
                    unset($_SESSION['captcha']);
                    $helpers->jsonResponse(400, UserController::CAPTCHA_ERROR);
                }
            }
            $helpers->jsonResponse(403, UserController::DISABLE_USER_REGIDTRATION);
        case 'login':
            if (isset($_SESSION['captcha']) && PhraseBuilder::comparePhrases($_SESSION['captcha'], $captcha)) {
                unset($_SESSION['captcha']);
                $userController->login($username, $password);
            } else {
                // 验证码错误
                unset($_SESSION['captcha']);
                $helpers->jsonResponse(400, UserController::CAPTCHA_ERROR);
            }
        case 'logout':
            // 判断是否已经退出登录
            if (!$isLogin->checkUserLoginStatus()) {
                exit(header("Location: /user/login" . $helpers->getGetParams('callBack'))); // 重定向到登录页面
            }
            setcookie('user_login_info', '', time() - 3600, '/');
            break;
        default:
            $helpers->jsonResponse(400, UserController::INVALID_METHOD_MESSAGE);
    }
} else {
    // 如果 method 不符合字母数字格式，返回 400 错误
    $helpers->jsonResponse(400, "Invalid API method");
}
