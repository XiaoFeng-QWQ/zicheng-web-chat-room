<?php
// 输出验证码图像
header('Content-type: image/jpeg');

use Gregwar\Captcha\CaptchaBuilder;

// 生成新的验证码
$builder = new CaptchaBuilder;
$builder->build(170, 48);
$builder->setMaxBehindLines(10); // 增加背景干扰线
$builder->setMaxFrontLines(10);  // 增加前景干扰线

$_SESSION['captcha'] = $builder->getPhrase();

// 清除所有已经输出的内容，避免干扰图像生成
@ob_clean();

// 输出验证码图像
$builder->output();
exit;