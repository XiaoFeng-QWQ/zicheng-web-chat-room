<?php

use ChatRoom\Core\Helpers\Helpers;

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


    $helpers = new Helpers;
    header('Content-Type: application/json');
    http_response_code(500);
    $helpers->jsonResponse(500, '内部错误，请稍后再试。', [
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine(),
        'trace' => defined('FRAMEWORK_DEBUG') && FRAMEWORK_DEBUG ? $e->getTrace() : null,
        'code_snippet' => getCodeSnippet($e->getFile(), $e->getLine()),
        'variables' => safeVarExport(get_defined_vars())
    ]);
    exit;

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