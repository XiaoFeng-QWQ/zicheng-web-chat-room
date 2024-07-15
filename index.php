<?php

/**
 * -----------------------------------
 * 注意：所有有关于文件路径的必须遵守以下规则
 * 末尾不得有"/"文件路径开始必须有"/"
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
require_once __DIR__ . '/System/Core/Define.php';

use ChatRoom\Core\Main;

$App = new Main;
$App->run();
