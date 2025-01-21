<?php

/**
 * API 入口
 */

use ChatRoom\Core\Helpers\SystemSetting;
use ChatRoom\Core\Database\SqlLite;
use ChatRoom\Core\Helpers\Helpers;
use ChatRoom\Core\Helpers\User;

try {
    $helpers = new Helpers();
    $userHelpers = new User();
    $systemSetting = new SystemSetting(SqlLite::getInstance()->getConnection());
    $enableCrossDomain = $systemSetting->getSetting('api_enable_cross_domain');
    $allowedDomains = explode(',', $systemSetting->getSetting('api_cross_domain_allowlist') ?? ''); // 获取允许的域名

    $URI = parse_url($_SERVER['REQUEST_URI'])['path'];
    $notRequiresValidationToken = ['user', 'captcha'];
    $apiName = basename(explode('/', $URI)[3]);  // 提取 API 名称部分并避免路径穿越

    if ($enableCrossDomain) {
        // 检查请求的Origin是否在允许的域名列表中
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (in_array('*', $allowedDomains) || in_array($origin, $allowedDomains)) {
            // 设置CORS头，允许跨域请求
            header("Access-Control-Allow-Origin: $origin");
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
            header("Access-Control-Allow-Headers: Content-Type, Authorization");
        }
    }

    // 验证 API 名称是否符合字母和数字的格式
    if (!preg_match('/^[a-zA-Z0-9]+$/', $apiName)) {
        $helpers->jsonResponse(403, '无效的API名称!');
    }

    $apiFile = __DIR__ . "/$apiName.php";

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
} catch (Throwable $e) {
    if (defined('FRAMEWORK_DEBUG') && FRAMEWORK_DEBUG) {
        $getTrace = ['message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()];
    } else {
        $getTrace = [];
    }

    $errorMessage = '内部错误，请稍后再试。';
    $statusCode = 500;

    $helpers->jsonResponse($statusCode, $errorMessage, $getTrace);
}
