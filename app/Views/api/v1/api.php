<?php

/**
 * API 入口
 */
// 允许所有域名跨域访问
header("Access-Control-Allow-Origin: *");
error_reporting(0);

use ChatRoom\Core\Helpers\Helpers;
use ChatRoom\Core\Helpers\User;

$helpers = new Helpers();
$userHelpers = new User();

$URI = parse_url($_SERVER['REQUEST_URI'])['path'];
$apiName = basename(explode('/', $URI)[3]);  // 提取 API 名称部分并避免路径穿越

// 验证 API 名称是否符合字母和数字的格式
if (!preg_match('/^[a-zA-Z0-9]+$/', $apiName)) {
    $helpers->jsonResponse(403, '无效的API名称!');
}

// 不需要验证token的API列表
$notRequiresValidationToken = ['user', 'captcha'];

$apiFile = __DIR__ . "/$apiName.php";
try {
    // 如果不是不需要验证token的API，需要验证用户是否登录或传递token
    if (!in_array($apiName, $notRequiresValidationToken)) {
        if (!$userHelpers->checkUserLoginStatus()) {
            if (empty($_POST['token'])) {
                $helpers->jsonResponse(401, '未传递TOKEN!');
            }
        }
    }
    // 确保文件存在
    if (!file_exists($apiFile)) {
        $helpers->jsonResponse(404, 'API 不存在!');
    }
    include $apiFile;
} catch (Exception $e) {
    throw new Exception($e);
}
