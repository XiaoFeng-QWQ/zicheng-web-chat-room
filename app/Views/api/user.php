<?php

use Monolog\Logger;
use ChatRoom\Core\Helpers\Helpers;
use Gregwar\Captcha\PhraseBuilder;
use ChatRoom\Core\Controller\UserController;

header('Content-Type: application/json');
// 常量配置
define('LOG_LEVEL', Logger::ERROR);
define('CAPTCHA_ERROR', '验证码错误');
define('INVALID_METHOD_MESSAGE', '无效的方法。');
define('METHOD_NOT_PROVIDED_MESSAGE', '方法未提供。');
define('INVALID_REQUEST_METHOD_MESSAGE', '无效的请求方法。');
// 获取 HTTP 请求参数
$method = $_GET['method'] ?? null;
if (!$method) {
    respondWithJson(400, METHOD_NOT_PROVIDED_MESSAGE);
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

// 处理 API 请求
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
            if (isset($_SESSION['captcha']) && PhraseBuilder::comparePhrases($_SESSION['captcha'], $captcha)) {
                unset($_SESSION['captcha']);
                exit($userController->register($username, $password, $confirmPassword));
            } else {
                unset($_SESSION['captcha']);
                respondWithJson(400, CAPTCHA_ERROR);
            }
        case 'login':
            if (isset($_SESSION['captcha']) && PhraseBuilder::comparePhrases($_SESSION['captcha'], $captcha)) {
                unset($_SESSION['captcha']);
                exit($userController->login($username, $password));
            } else {
                unset($_SESSION['captcha']);
                respondWithJson(400, CAPTCHA_ERROR);
            }
        default:
            respondWithJson(400, INVALID_METHOD_MESSAGE);
    }
} else {
    respondWithJson(405, INVALID_REQUEST_METHOD_MESSAGE);
}
