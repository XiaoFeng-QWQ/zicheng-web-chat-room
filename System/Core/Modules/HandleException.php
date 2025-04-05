<?php

/**
 * 自定义错误处理
 *
 * @param Throwable $e 异常或错误对象
 * @param boolean $saveLog 是否仅保存日志而不输出错误
 * @param boolean $isFatal 是否为致命错误
 * @return void
 */
function HandleException($e, $saveLog = false, $isFatal = false)
{
    // 确保输出缓冲区是干净的
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    // 记录错误日志
    logException($e);

    if ($saveLog) {
        return;
    }

    // 如果是AJAX请求，返回JSON格式的错误信息
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'message' => $e->getMessage(),
            'trace' => defined('FRAMEWORK_DEBUG') && FRAMEWORK_DEBUG ? $e->getTrace() : null
        ]);
        exit;
    }

    // 输出错误信息到屏幕
    echo formatExceptionOutput($e);

    // 如果是致命错误，终止执行
    if ($isFatal) {
        exit(1);
    }
}

/**
 * 记录异常日志
 *
 * @param Throwable $e
 * @return void
 */
function logException($e)
{
    $logDir = FRAMEWORK_DIR . '/Writable/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    $logFile = $logDir . '/error_' . date('Y-m-d') . '.log';

    $logContent = sprintf(
        "[%s] %s: %s in %s on line %d\nStack trace:\n%s\n",
        date('Y-m-d H:i:s'),
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );

    // 添加上下文信息
    $logContent .= "Request URI: " . ($_SERVER['REQUEST_URI'] ?? '') . "\n";
    $logContent .= "Request Method: " . ($_SERVER['REQUEST_METHOD'] ?? '') . "\n";
    $logContent .= "IP Address: " . ($_SERVER['REMOTE_ADDR'] ?? '') . "\n";
    $logContent .= "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? '') . "\n";

    @file_put_contents($logFile, $logContent, FILE_APPEND);
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
        return '无法读取文件: ' . htmlspecialchars($file);
    }

    try {
        $lines = file($file);
        if ($lines === false) {
            return '无法读取文件内容';
        }

        $start = max(0, $line - $padding - 1);
        $end = min(count($lines), $line + $padding);

        $snippet = '';
        for ($i = $start; $i < $end; $i++) {
            $lineNumber = $i + 1;
            $lineContent = htmlspecialchars($lines[$i], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            if ($lineNumber === $line) {
                $snippet .= "<span class=\"error-line\">{$lineNumber}: {$lineContent}</span>";
            } else {
                $snippet .= "{$lineNumber}: {$lineContent}";
            }
        }

        return $snippet;
    } catch (Exception $e) {
        return '获取代码片段时出错: ' . htmlspecialchars($e->getMessage());
    }
}

/**
 * 安全地导出变量信息
 *
 * @param mixed $data
 * @return string
 */
function safeVarExport($data)
{
    if (is_array($data)) {
        // 过滤敏感信息
        $filteredData = array_map(function ($value) {
            if (is_string($value)) {
                // 过滤可能敏感的信息
                if (preg_match('/password|secret|key|token|auth/i', $value)) {
                    return '***FILTERED***';
                }
            }
            return $value;
        }, $data);

        return var_export($filteredData, true);
    }

    return var_export($data, true);
}

/**
 * 格式化异常输出
 *
 * @param Throwable $exception 异常对象
 * @return string
 */
function formatExceptionOutput($exception)
{
    $codeSnippet = getCodeSnippet($exception->getFile(), $exception->getLine());
    $isDebug = defined('FRAMEWORK_DEBUG') && FRAMEWORK_DEBUG;

    $output = '
        <style>
            .framework-error-container {
                background-color: #fff;
                box-shadow: 0px 0px 20px 20px rgba(0, 0, 0, 0.1);
                padding: 20px;
                margin: 20px;
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                position: relative;
                border-left: 5px solid #b71c1c;
            }

            .framework-error-container .error-header {
                font-size: 24px;
                color: #b71c1c;
                margin-bottom: 20px;
                font-weight: bold;
            }

            .framework-error-container .error-section {
                margin-bottom: 20px;
                border-bottom: 1px solid #eee;
                padding-bottom: 15px;
            }

            .framework-error-container .error-section h3 {
                font-size: 18px;
                color: #333;
                margin-bottom: 10px;
                font-weight: 600;
            }

            .framework-error-container pre {
                background-color: #f5f5f5;
                padding: 10px;
                border-radius: 3px;
                overflow-x: auto;
            }

            .framework-error-container pre code {
                white-space: pre-wrap;
                word-break: break-word;
                font-family: Consolas, Monaco, "Andale Mono", monospace;
                font-size: 14px;
                line-height: 1.5;
                color: #333;
            }

            .framework-error-container .error-line {
                background-color: #ffebee;
                display: block;
                padding: 2px 4px;
                margin: 0 -4px;
                border-left: 3px solid #b71c1c;
            }

            .framework-error-container .error-footer {
                text-align: right;
                font-size: 12px;
                color: #777;
                margin-top: 20px;
            }

            .framework-error-container-close {
                position: absolute;
                font-size: 24px;
                color: #333;
                cursor: pointer;
                right: 20px;
                top: 20px;
                font-weight: bold;
            }

            .framework-error-container-close:hover {
                color: #b71c1c;
            }

            .error-type {
                display: inline-block;
                background-color: #b71c1c;
                color: white;
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 14px;
                margin-left: 10px;
                vertical-align: middle;
            }
        </style>
        <div class="framework-error-container">
            <div class="error-header">
                系统错误 <span class="error-type">' . get_class($exception) . '</span>
            </div>
            <div class="error-section">
                <h3>错误信息:</h3>
                <pre><code class="language-php">' . htmlspecialchars($exception->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>
            </div>';

    if ($isDebug) {
        $output .= '
            <div class="error-section">
                <h3>位置:</h3>
                <pre><code class="language-php">' . htmlspecialchars($exception->getFile(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ' 第 ' . $exception->getLine() . ' 行</code></pre>
            </div>
            <div class="error-section">
                <h3>错误堆栈:</h3>
                <pre><code class="language-php">' . htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>
            </div>
            <div class="error-section">
                <h3>错误代码片段:</h3>
                <pre><code class="language-php">' . $codeSnippet . '</code></pre>
            </div>
            <div class="error-section">
                <h3>异常详情:</h3>
                <pre><code class="language-php">' . htmlspecialchars(safeVarExport($exception), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>
            </div>
            <div class="error-section">
                <h3>请求信息:</h3>
                <pre><code class="language-php">URL: ' . htmlspecialchars($_SERVER['REQUEST_URI'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '
Method: ' . htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '
IP: ' . htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>
            </div>
            <div class="error-section">
                <h3>请求参数:</h3>
                <pre><code class="language-php">' . htmlspecialchars(safeVarExport($_REQUEST), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>
            </div>';

        if (!empty($_SESSION)) {
            $output .= '<div class="error-section">
                    <h3>会话数据:</h3>
                    <pre><code class="language-php">' . htmlspecialchars(safeVarExport($_SESSION), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>
                </div>';
        }

        $output .= '<div class="error-section">
                <h3>环境信息:</h3>
                <pre><code class="language-php">PHP 版本: ' . phpversion() . '
服务器: ' . htmlspecialchars(php_uname(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '
运行时间: ' . date('Y-m-d H:i:s') . '
内存使用: ' . round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB
峰值内存: ' . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB</code></pre>
            </div>';
    } else {
        $output .= '<div class="error-section">
                <h3>提示:</h3>
                <p>如需查看详细错误信息，请开启调试模式或联系系统管理员。</p>
            </div>';
    }

    $output .= '<div class="error-footer">
                <hr>
                <p>ZiChen ChatRooM V:' . (defined('FRAMEWORK_VERSION') ? FRAMEWORK_VERSION : '1.0') . ' | ' . date('Y-m-d H:i:s') . '</p>
            </div>
        </div>';

    http_response_code(500);
    return $output;
}
