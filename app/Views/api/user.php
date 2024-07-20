<?php

use Monolog\Logger;
use ChatRoom\Core\Helpers\Helpers;
use Monolog\Handler\StreamHandler;
use Gregwar\Captcha\PhraseBuilder;
use Monolog\Formatter\LineFormatter;
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
// 设置日志目录和日志文件名
$logDirectory = FRAMEWORK_DIR . '/Writable/logs';
ensureDirectoryExists($logDirectory);
$logFileName = sprintf('%s/UserController[%s].log', $logDirectory, date('Y-m-d'));
// 创建日志记录器
$logger = createLogger("UserController.$method", $logFileName, LOG_LEVEL);

// 工具函数
/**
 * 确保目录存在
 */
function ensureDirectoryExists($directory)
{
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
}
/**
 * 创建日志记录器
 *
 * @param [type] $channel
 * @param [type] $logFileName
 * @param [type] $logLevel
 * @return
 */
function createLogger($channel, $logFileName, $logLevel)
{
    $logger = new Logger($channel);
    $output = "[%datetime%] %channel%.%level_name%: %message%\n%context.stack_trace%\n";
    $dateFormat = 'Y-m-d\TH:i:s.uP';
    $formatter = new LineFormatter($output, $dateFormat, true, true);
    $streamHandler = new StreamHandler($logFileName, $logLevel);
    $streamHandler->setFormatter($formatter);
    $logger->pushHandler($streamHandler);

    return $logger;
}
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
/**
 * 日志记录错误
 *
 * @param [type] $logger
 * @param [type] $errors
 * @return void
 */
function logErrors($logger, $errors)
{
    foreach ($errors as $error) {
        $logger->error($error);
    }
}

// 处理 API 请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 读取和解析输入的 JSON 数据
    $input = json_decode(file_get_contents('php://input'), true);
    $captcha = $input['captcha'] ?? '';
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    $confirmPassword = $method === 'register' ? $input['confirm_password'] ?? '' : null;
    $userController = new UserController($logger);

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
