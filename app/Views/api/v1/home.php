<?php

use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Database\SqlLite;
use ChatRoom\Core\Helpers\SystemSetting;

$db = SqlLite::getInstance()->getConnection();
$SystemSetting = new SystemSetting($db);
$user = new User;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = isset(explode('/', trim($uri, '/'))[3]) ? explode('/', trim($uri, '/'))[3] : null;

// 验证 API 名称是否符合字母和数字的格式，且长度不超过 30
if (preg_match('/^[a-zA-Z0-9]{1,30}$/', $method)) {
    switch ($method) {
        case 'user':
            $helpers->jsonResponse(200, 'true', ['registerUserCount' => $user->getUserCount(), 'loginStatus' => $user->checkUserLoginStatus()]);
            break;
        case 'config':
            $response = $SystemSetting->getAllSettings();
            $helpers->jsonResponse(200, 'true', $response);
            break;
        default:
            $helpers->jsonResponse(400, 'false', ['error' => 'Invalid method']);
            break;
    }
} else {
    // 如果 method 不符合字母数字格式，返回 400 错误
    $helpers->jsonResponse(400, 'false', ['error' => 'Invalid API method']);
}
