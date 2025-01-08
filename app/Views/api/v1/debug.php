<?php
// 检查是否处于调试模式
if (defined('FRAMEWORK_DEBUG') && FRAMEWORK_DEBUG) {
    $response = [
        'server_time' => date('Y-m-d H:i:s'),
        'cookie_data' => $_COOKIE,
        'session_data' => $_SESSION,
        'server_data' => $_SERVER,
    ];
    $helpers->jsonResponse(200, 'true', $response);
} else {
    $helpers->jsonResponse(200, 'No debug info available');
}
