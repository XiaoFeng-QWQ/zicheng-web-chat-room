<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

/**
 * 系统异常处理
 * @param mixed $e
 * @return void
 */
function handleException($e)
{
    // 获取当前时间并格式化为文件名
    $timestamp = date('Y-m-d');
    $logFile = FRAMEWORK_DIR . '/Writable/logs/' . $timestamp . '.log';

    // 创建一个日志实例
    $logger = new Logger('logger');
    $streamHandler = new StreamHandler($logFile, Logger::ERROR);

    // 自定义日志格式
    $dateFormat = "Y-m-d\TH:i:s.uP"; // 例如：2024-07-28T15:27:42.984432+08:00
    $output = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
    $formatter = new LineFormatter($output, $dateFormat, true, true);
    $streamHandler->setFormatter($formatter);

    $logger->pushHandler($streamHandler);

    // 记录错误信息
    $errorMessage = sprintf(
        "Exception:\n%s in %s:%d \nStack trace:\n%s",
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );

    $logger->error($errorMessage);

    // 输出错误信息到屏幕
    echo sprintf('哎呀！程序出错了: %s %s %s', PHP_EOL, $e->getMessage(), PHP_EOL);
    if (FRAMEWORK_DEBUG) {
        echo sprintf('错误堆栈:%s %s ', PHP_EOL, $e->getTraceAsString());
        echo PHP_EOL . '原始信息:' . PHP_EOL;
        var_dump($e);
    }
    http_response_code(500);
}