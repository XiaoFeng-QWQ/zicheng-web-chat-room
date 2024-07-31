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
    echo formatExceptionOutput($e);
}

/**
 * 格式化异常输出
 * @param Exception $exception
 * @return string
 */
function formatExceptionOutput($exception)
{
    header('Content-Type: text/html; charset=UTF-8');

    $output = '
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>抱歉，程序崩了！</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.1.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/prism-themes/1.9.0/prism-a11y-dark.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f7f7f7;
        }
        .error-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            max-width: 800px;
            margin: 20px auto;
        }
        .error-header {
            font-size: 24px;
            color: #b71c1c;
            margin-bottom: 20px;
        }
        .error-section {
            margin-bottom: 20px;
        }
        .error-section h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 10px;
        }
        pre code {
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-header">哎呀，程序出错了！</div>
        <div class="error-section">
            <h3>错误信息:</h3>
            <pre><code class="language-php">' . htmlspecialchars($exception->getMessage()) . '</code></pre>
        </div>';

    if (defined('FRAMEWORK_DEBUG') && FRAMEWORK_DEBUG) {
        $output .= '
        <div class="error-section">
            <h3>错误堆栈:</h3>
            <pre><code class="language-php">' . htmlspecialchars($exception->getTraceAsString()) . '</code></pre>
        </div>
        <div class="error-section">
            <h3>原始信息:</h3>
            <pre><code class="language-php">' . htmlspecialchars(var_export($exception, true)) . '</code></pre>
        </div>
        <p>日志已记录到 ' . FRAMEWORK_DIR . '/Writable/logs/' . '</p>
        ';
    }

    $output .= '
        <div class="error-footer">
            <hr>
            <p style="position: relative;left: 520px;">ZiChen ChatRooM V:' . FRAMEWORK_VERSION . '</p>
        </div>
    </div>
    <script src="https://cdn.bootcdn.net/ajax/libs/prism/9000.0.1/prism.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/prism/9000.0.1/components/prism-php.min.js"></script>
</body>
</html>';

    http_response_code(500);

    return $output;
}