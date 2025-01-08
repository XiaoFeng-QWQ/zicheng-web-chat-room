<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

/**
 * 系统异常处理
 *
 * @param Exception $e 异常对象
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
 * 获取文件中指定行的代码片段
 *
 * @param string $file 文件路径
 * @param int $line 行号
 * @param int $padding 上下文行数
 * @return string
 */
function getCodeSnippet($file, $line, $padding = 5)
{
    if (!is_readable($file)) {
        return '';
    }

    $lines = file($file);
    $start = max(0, $line - $padding - 1);
    $end = min(count($lines), $line + $padding);

    $snippet = '';
    for ($i = $start; $i < $end; $i++) {
        $lineNumber = $i + 1;
        $lineContent = htmlspecialchars($lines[$i]);
        if ($lineNumber === $line) {
            $snippet .= "<span class=\"error-line\">$lineNumber: $lineContent</span>";
        } else {
            $snippet .= "$lineNumber: $lineContent";
        }
    }

    return $snippet;
}

/**
 * 格式化异常输出
 *
 * @param Exception $exception 异常对象
 * @return string
 */
function formatExceptionOutput($exception)
{
    // 设置字符编码
    header('Content-Type: text/html; charset=UTF-8');

    $codeSnippet = getCodeSnippet($exception->getFile(), $exception->getLine());

    $output = '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>BOW!!!</title><style>body {background-color: #f7f7f7;}.error-container {background-color: #fff;box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);padding: 20px;margin: 20px auto;font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;}.error-header {font-size: 24px;color: #b71c1c;margin-bottom: 20px;}.error-section {margin-bottom: 20px;}.error-section h3 {font-size: 18px;color: #333;margin-bottom: 10px;}pre code {white-space: pre-wrap;word-break: break-word;}.error-line {background-color: #ffcccc;display: block;}.error-footer {text-align: right;}</style></head><body><div class="error-container"><div class="error-header">抛出致命错误！</div><div class="error-section"><h3>错误信息:</h3><pre><code class="language-php">' .preg_replace('/Stack trace.*$/s', '', $exception->getMessage()) . '</code></pre></div>';
    if (defined('FRAMEWORK_DEBUG') && FRAMEWORK_DEBUG) {
        $output .= '<div class="error-section"><h3>错误堆栈:</h3><pre><code class="language-php">' . htmlspecialchars($exception->getTraceAsString()) . '</code></pre></div><div class="error-section"><h3>错误代码片段:</h3><pre><code class="language-php">' . $codeSnippet . '</code></pre></div><div class="error-section"><h3>原始信息:</h3><pre><code class="language-php">' . htmlspecialchars(var_export($exception, true)) . '</code></pre></div><div class="error-section"><h3>请求URL:</h3><pre><code class="language-php">' . htmlspecialchars($_SERVER['REQUEST_URI']) . '</code></pre></div><div class="error-section"><h3>请求参数:</h3><pre><code class="language-php">' . htmlspecialchars(var_export($_REQUEST, true)) . '</code></pre></div><div class="error-section"><h3>会话数据:</h3><pre><code class="language-php">' . htmlspecialchars(var_export($_SESSION, true)) . '</code></pre></div><div class="error-section"><h3>环境信息:</h3><pre><code class="language-php">服务器信息:' . htmlspecialchars(mb_convert_encoding(php_uname(), 'GB18030')) . PHP_EOL . 'PHP版本:' . phpversion() . '</code></pre></div><p>日志已记录到 ' . FRAMEWORK_DIR . '/Writable/logs/' . '</p>';
    }
    $output .= '<div class="error-footer"><hr><p>ZiChen ChatRooM V:' . FRAMEWORK_VERSION . '</p></div></div></body></html>';
    require_once FRAMEWORK_APP_PATH . '/Views/module/module.highlight.php';
    http_response_code(500);
    return $output;
}
