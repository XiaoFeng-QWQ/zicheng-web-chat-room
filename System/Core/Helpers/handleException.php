<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

/**
 * 自定义错误处理
 *
 * @param [type] $e
 * @param boolean $saveLog 是否仅保存日志而不输出错误
 * @return void
 */
function HandleException($e, $saveLog = false)
{
    if (!$e instanceof Exception) {
        ob_clean();
        return $saveLog;
    }

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

    // 记录错误信息，确保 message 为字符串
    $errorMessage = sprintf(
        "Exception:\n%s in %s:%d \nStack trace:\n%s",
        is_string($e->getMessage()) ? $e->getMessage() : 'No message available',
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );

    $logger->error($errorMessage);

    if ($saveLog) {
        return;
    }

    ob_clean();

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
    $codeSnippet = getCodeSnippet($exception->getFile(), $exception->getLine());

    $output = '
        <style>
            .framework-error-container {
                position: absolute;
                background-color: #fff;
                box-shadow: 0px 0px 20px 20px rgba(0, 0, 0, 0.1);
                padding: 20px;
                margin: 20px;
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            }

            .framework-error-container .error-header {
                font-size: 24px;
                color: #b71c1c;
                margin-bottom: 20px;
            }

            .framework-error-container .error-section {
                margin-bottom: 20px;
            }

            .framework-error-container .error-section h3 {
                font-size: 18px;
                color: #333;
                margin-bottom: 10px;
            }

            .framework-error-container pre code {
                white-space: pre-wrap;
                word-break: break-word;
            }

            .framework-error-container .error-line {
                background-color: #ffcccc;
                display: block;
            }

            .framework-error-container .error-footer {
                text-align: right;
            }

            .framework-error-container-close {
                position: absolute;
                top: 0;
                right: 10px;
                font-size: 24px;
                color: #333;
                cursor: pointer;
            }
        </style>
        <div class="framework-error-container">
            <span class="framework-error-container-close">×</span>
            <div class="error-header">抛出错误！</div>
            <div class="error-section">
                <h3>错误信息:</h3>
                <pre><code class="language-php">' . preg_replace('/Stack trace.*$/s', '', $exception->getMessage()) . '</code></pre>
            </div>';
    if (defined('FRAMEWORK_DEBUG') && FRAMEWORK_DEBUG) {
        $output .= '<div class="error-section">
                <h3>错误堆栈:</h3>
                <pre><code class="language-php">' . htmlspecialchars($exception->getTraceAsString()) . '</code></pre>
            </div>
            <div class="error-section">
                <h3>错误代码片段:</h3>
                <pre><code class="language-php">' . $codeSnippet . '</code></pre>
            </div>
            <div class="error-section">
                <h3>原始信息:</h3>
                <pre><code class="language-php">' . htmlspecialchars(var_export($exception, true)) . '</code></pre>
            </div>
            <div class="error-section">
                <h3>请求URL:</h3>
                <pre><code class="language-php">' . htmlspecialchars($_SERVER['REQUEST_URI']) . '</code></pre>
            </div>
            <div class="error-section">
                <h3>请求参数:</h3>
                <pre><code class="language-php">' . htmlspecialchars(var_export($_REQUEST, true)) . '</code></pre>
            </div>
            <div class="error-section">
                <h3>会话数据:</h3>
                <pre><code class="language-php">' . htmlspecialchars(var_export($_SESSION, true)) . '</code></pre>
            </div>
            <div class="error-section">
                <h3>环境信息:</h3>
                <pre><code class="language-php">服务器信息:' . htmlspecialchars(mb_convert_encoding(php_uname(), 'GB18030')) . PHP_EOL . 'PHP版本:' . phpversion() . '</code></pre>
            </div>
            <p>日志已记录到 ' . FRAMEWORK_DIR . '/Writable/logs/' . '</p>';
    }
    $output .= '<div class="error-footer">
                <hr>
                <p>ZiChen ChatRooM V:' . FRAMEWORK_VERSION . '</p>
            </div>
        </div>';
    $output .= "
    <script>
        document.querySelector('.framework-error-container-close').addEventListener('click', function(e) {
            document.querySelector('.framework-error-container').style.display = 'none';
        });
    </script>";
    require_once FRAMEWORK_APP_PATH . '/Views/module/module.highlight.php';
    http_response_code(500);
    return $output;
}
