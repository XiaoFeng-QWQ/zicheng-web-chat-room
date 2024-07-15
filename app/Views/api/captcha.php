<?php
use Gregwar\Captcha\CaptchaBuilder;

// 创建验证码实例并将会话中的短语设置为存储
// 当表单提交时用于检查
$captcha = new CaptchaBuilder;
$_SESSION['captcha'] = $captcha->getPhrase();

// 将header设置为image png
header('Content-Type: image/png');

// 运行验证码图像的实际渲染
$captcha
    ->build(150, 54, FRAMEWORK_SYSTEM_DIR . '/Font/JetBrainsMono-Italic.ttf')
    ->output();