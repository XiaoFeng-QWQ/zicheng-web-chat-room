<?php

/**
 * 定义核心
 */
define('FRAMEWORK_CORE', true);

/**
 * 当前程序根目录
 * 
 * @var string
 */
define('FRAMEWORK_DIR', dirname(__DIR__, 2));

/**
 * 定义版本
 * 
 * @var int
 */
define('FRAMEWORK_VERSION', '1.1.2(测试)');

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