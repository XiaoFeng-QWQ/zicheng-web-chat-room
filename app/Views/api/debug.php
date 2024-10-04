<?php
header('Content-Type: application/json'); // 设置返回的内容类型为 JSON

use ChatRoom\Core\Auth\CheckUserLoginStatus;

// 初始化用户登录状态检查类
$isLogin = new CheckUserLoginStatus();

// 检查用户是否已登录以及是否属于特定用户组
if (!$isLogin->check() || ($_SESSION['user_login_info']['group_id'] ?? '') !== '1') {
    exit(json_encode(['error' => 'Forbidden'], JSON_UNESCAPED_UNICODE));
}

// 检查是否处于调试模式
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
} else {
    // 如果不处于调试模式，返回标准的响应
    echo json_encode(['code' => 200, 'message' => 'No debug info available'], JSON_UNESCAPED_UNICODE);
}
