<?php
// 在某个文件中解析请求，例如 ajax.php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use ChatRoom\Core\Controller\ChatController;

// 设置日志目录
$logDirectory = FRAMEWORK_DIR . '/writable/logs';
if (!is_dir($logDirectory)) {
    mkdir($logDirectory, 0777, true);
}

// 获取当前日期并生成日志文件名
$currentDate = date('Y-m-d');
$logFileName = "$logDirectory/ChatController[$currentDate].log";

$method = $_SERVER['REQUEST_METHOD'];

// 创建一个日志记录器实例
$logger = new Logger("ChatController.$method");
// 自定义日志格式
$output = "[%datetime%] %channel%.%level_name%: %message%\n%context.stack_trace%\n";
$dateFormat = 'Y-m-d\TH:i:s.uP';
$formatter = new LineFormatter($output, $dateFormat, true, true);

// 创建和配置 StreamHandler
$streamHandler = new StreamHandler($logFileName, Logger::ERROR);
$streamHandler->setFormatter($formatter);

// 添加 handler 到 logger
$logger->pushHandler($streamHandler);

header('Content-Type: application/json');
$ajaxController = new ChatController($logger);
$ajaxController->handleRequest();