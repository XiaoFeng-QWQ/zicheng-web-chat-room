<?php

/**
 *     _____   _ ________                     ________          __  ____  ____  ____  __  ___
 *    /__  /  (_) ____/ /_  ___  ____  ____ _/ ____/ /_  ____ _/ /_/ __ \/ __ \/ __ \/  |/  /
 *      / /  / / /   / __ \/ _ \/ __ \/ __ `/ /   / __ \/ __ `/ __/ /_/ / / / / / / / /|_/ / 
 *     / /__/ / /___/ / / /  __/ / / / /_/ / /___/ / / / /_/ / /_/ _, _/ /_/ / /_/ / /  / /  
 *    /____/_/\____/_/ /_/\___/_/ /_/\__, /\____/_/ /_/\__,_/\__/_/ |_|\____/\____/_/  /_/   
 *                                  /____/                                                   
 *                                                          Powered By:XiaoFeng_QWQ V:1.7.0.1
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
 * - 函数名使用小驼峰，函数内部变量也是
 * -----------------------------------
 */
// 必须在调用 session_start() 之前设置 session.cookie_lifetime
// 设置 PHPSESSID cookie 存活时长
ini_set('session.cookie_lifetime', 86400 * 365); // 设置为1年（86400秒 * 365天）
ini_set('session.gc_maxlifetime', 86400 * 365); // 垃圾回收最大生存时间（与cookie保持同步）
ini_set('session.use_strict_mode', 1); // 开启会话前，确保使用严格模式和安全的会话配置
session_start([
    'cookie_httponly' => true, // 防止通过JavaScript访问会话
    'cookie_secure' => isset($_SERVER['HTTPS']), // HTTPS连接时启用secure标志
    'cookie_samesite' => 'Strict', // 防止CSRF攻击
    'use_trans_sid' => false, // 禁用通过URL传递会话ID
]);

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

require __DIR__ . '/System/Core/Helpers/handleException.php';
// 注册自定义异常处理器
set_exception_handler('HandleException');

use ChatRoom\Core\Main;

$App = new Main;
$App->run();
