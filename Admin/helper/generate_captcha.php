<?php
require_once __DIR__ . '/../../vendor/autoload.php';
session_start();

use Gregwar\Captcha\CaptchaBuilder;

// 生成新的验证码
$builder = new CaptchaBuilder;
$builder->build();

// 将验证码短语存储在会话中
$_SESSION['captcha'] = $builder->getPhrase();

// 输出验证码图像
header('Content-type: image/jpeg');
$builder->output();

// 你说得对，虽然在1.6.6.1[测试]版本中修复通过内置路由验证码无法正常输出问题，但是这个文件尽量还是别删