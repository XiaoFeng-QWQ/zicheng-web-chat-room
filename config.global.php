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
define('FRAMEWORK_VERSION', '2.2.1.0');

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
 * SqlLite数据库文件路径(如果你觉得默认路径不安全请更改)
 * 
 * @var string
 */
define('FRAMEWORK_DATABASE_PATH', FRAMEWORK_DIR . '/Writable/data.db');

/**
 * 安装锁 为true时表示已经安装
 * 
 * @var bool
 */
define('FRAMEWORK_INSTALL_LOCK', false);