<?php

/**
 * -----------------------------------
 * 注意事项：
 * - 文件路径必须遵守以下规则：
 *   - 路径末尾不得有斜杠 ("/")。
 *   - 路径必须以斜杠 ("/") 开头。
 *   - URI 也需符合以上规则。
 * - 安装检测通过检查常量 `FRAMEWORK_DATABASE_PATH` 是否定义来完成。（别问我为什么💦）
 * -----------------------------------
 * 命名规范：
 * - `System` 目录下的所有文件使用大驼峰命名法。
 * - `App` 和 `StaticResources` 目录下的文件使用点号分隔的命名方式，目录使用大驼峰命名法。
 * - 项目根目录使用大驼峰命名法。
 * -----------------------------------
 */
// 必须在调用 session_start() 之前设置 session.cookie_lifetime
// 设置 PHPSESSID cookie 存活时长
ini_set('session.cookie_lifetime', 86400 * 365); // 设置为1年（86400秒 * 365天）
ini_set('session.gc_maxlifetime', 86400 * 365); // 垃圾回收最大生存时间（与cookie保持同步）
session_start();

// 强制设置时区为国内
date_default_timezone_set("Asia/Shanghai");

// PHP版本判断
if (version_compare(phpversion(), '8.2', '<')) {
    header('Content-type:text/html;charset=utf-8');
    http_response_code(500);
    echo sprintf(
        '<h3>程序运行失败：</h3><blockquote>您的 PHP 版本低于最低要求 8.2，当前版本为 %s</blockquote>',
        phpversion()
    );
    exit;
}

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.global.php';

// 检查请求头，避免在非图片请求时注册自定义异常处理器
if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'image/') === false) {
    require __DIR__ . '/System/Core/Helpers/handleException.php';
    // 注册自定义异常处理器
    set_exception_handler('HandleException');
}

use ChatRoom\Core\Main;

$App = new Main;
$App->run();
