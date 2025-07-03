<?php

/**
 * 当前程序根目录
 * 
 * @var string
 */
define('FRAMEWORK_DIR', dirname(__FILE__));

/**
 * 定义版本
 * 
 * @var int
 */
define('FRAMEWORK_VERSION', '[BETA]2.4.0.0');

/**
 * 当前系统文件目录
 * 
 * @var string
 */
define('FRAMEWORK_SYSTEM_DIR', FRAMEWORK_DIR . '/System');

/**
 * 当前核心目录
 * 
 * @var string
 */
define('FRAMEWORK_CORE_DIR', FRAMEWORK_DIR . '/System/Core');

/**
 * 应用文件目录
 * 
 * @var string
 */
define('FRAMEWORK_APP_PATH', FRAMEWORK_DIR . '/App');
/**
 * 数据库配置
 */
define('FRAMEWORK_DATABASE', [
    'driver' => 'sqlite',
    'host' => 'F:\web\wwwroot\Zichen Web chat room/Writable/6866a780bce7e.db',
    'port' => 3306,
    'dbname' => '6866a780bce7e.db',
    'username' => 'q1432777209@126.com',
    'password' => 's26AFMWNB:_TApR',
    'charset' => 'utf8mb4',
]);

/**
 * 安装锁
 */
define('FRAMEWORK_INSTALL_LOCK', true);
