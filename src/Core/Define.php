<?php

/**
 * 定义核心
 */
define('FRAMEWORK_CORE', true);

/**
 * 调试模式，0为关闭，-1为打开
 * 
 * @var int
 */
define('FRAMEWORK_DEBUG', -1);

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
define('FRAMEWORK_VERSION', '0.0.1');

/**
 * 当前核心目录
 * 
 * @var string
 */
define('FRAMEWORK_CORE_DIR', FRAMEWORK_DIR . '/src/Core');

/**
 * 应用文件目录
 * 
 * @var string
 */
define('FRAMEWORK_APP_PATH', FRAMEWORK_DIR . '/app');

/**
 * Cloudflare Turnstile
 * @var array
 */
define('FRAMEWORK_CLOUDFLARE_TURNSTILE', array(
    // 站点密钥
    'siteKey' => '0x4AAAAAAAe7x1_TiOY8Ys9j',
    // 密钥
    'secretKey' => '0x4AAAAAAAe7x1_TiOY8Ys9j'
));
