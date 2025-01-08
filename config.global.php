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
define('FRAMEWORK_VERSION', '2.0.0.0');

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
 * SqlLite数据库文件路径
 * 默认情况下这里为空，需要执行安装程序后根据安装程序的提示在这里填写
 * 
 * @var string
 */
