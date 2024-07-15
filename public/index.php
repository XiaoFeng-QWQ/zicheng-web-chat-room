<?php
// 必须在调用 session_start() 之前设置 session.cookie_lifetime
ini_set('session.cookie_lifetime', 86400 * 365); // 设置为1年（86400秒 * 365天）
ini_set('session.gc_maxlifetime', 86400 * 365); // 垃圾回收最大生存时间（与cookie保持同步）
session_start();

date_default_timezone_set("Asia/Shanghai");

// PHP 版本判断
if (version_compare(phpversion(), '5.4', '<')) {
    header('Content-type:text/html;charset=utf-8');
    http_response_code(500);
    echo sprintf(
        '<h3>程序运行失败：</h3><blockquote>您的 PHP 版本低于最低要求 5.4，当前版本为 %s</blockquote>',
        phpversion()
    );
    exit;
}

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Define.php';

use ChatRoom\Core\Main;

$App = new Main;
$App->run();
