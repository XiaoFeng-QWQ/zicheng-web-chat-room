<?php

use Gregwar\Captcha\PhraseBuilder;
use ChatRoom\Core\Helpers\Helpers;
use ChatRoom\Core\Database\SqlLite;
use ChatRoom\Core\Helpers\SystemSetting;
use ChatRoom\Core\Controller\UserController;

header('Content-Type: application/json');

// 获取 HTTP 请求参数
$method = $_GET['method'] ?? null;

// 获取配置
$setting = new SystemSetting(SqlLite::getInstance()->getConnection());
if (!$method) {
    respondWithJson(400, UserController::METHOD_NOT_PROVIDED_MESSAGE);
}

// 工具函数
/**
 * 使用 JSON 或纯文本响应并设置 HTTP 状态码
 * 内置exit()
 *
 * @param [type] $statusCode
 * @param [type] $message
 * @return void
 */
function respondWithJson($statusCode, $message)
{
    $helpers = new Helpers;
    $responseType = $_GET['responseType'] ?? 'json';
    if ($responseType === 'json') {
        echo $helpers->jsonResponse($message, $statusCode);
    } else {
        header('Content-Type: text/plain');
        echo "Code: $statusCode\nMessage: $message";
    }
    exit();
}

// 处理请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 读取和解析输入的 JSON 数据
    $input = json_decode(file_get_contents('php://input'), true);
    $captcha = $input['captcha'] ?? '';
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    $confirmPassword = $method === 'register' ? $input['confirm_password'] ?? '' : null;
    $userController = new UserController();

    switch ($method) {
        case 'register':
            if ($setting->getSetting('enable_user_registration') === true) {
                if (isset($_SESSION['captcha']) && PhraseBuilder::comparePhrases($_SESSION['captcha'], $captcha)) {
                    unset($_SESSION['captcha']);
                    exit($userController->register($username, $password, $confirmPassword));
                } else {
                    unset($_SESSION['captcha']);
                    respondWithJson(400, UserController::CAPTCHA_ERROR);
                }
            }
            respondWithJson(403, UserController::DISABLE_USER_REGIDTRATION);
        case 'login':
            if (isset($_SESSION['captcha']) && PhraseBuilder::comparePhrases($_SESSION['captcha'], $captcha)) {
                unset($_SESSION['captcha']);
                exit($userController->login($username, $password));
            } else {
                unset($_SESSION['captcha']);
                respondWithJson(400, UserController::CAPTCHA_ERROR);
            }
        default:
            respondWithJson(400, UserController::INVALID_METHOD_MESSAGE);
    }
} else {
    respondWithJson(405, UserController::INVALID_REQUEST_METHOD_MESSAGE);
}
