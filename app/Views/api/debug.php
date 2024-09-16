<?php
header('Content-Type: application/json'); // 设置返回的内容类型为 JSON
if (defined('FRAMEWORK_DEBUG') && FRAMEWORK_DEBUG) {
    // 获取需要返回的调试信息
    $response = [
        'code' => 200,
        'data' => [
            'server_time' => date('Y-m-d H:i:s'),
            'session_data' => $_SESSION,
            'server_data' => $_SERVER,
        ]
    ];

    // 返回 JSON 格式的调试信息
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
