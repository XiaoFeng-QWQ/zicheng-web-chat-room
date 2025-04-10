<?php
require_once FRAMEWORK_APP_PATH . '/Views/module/user.auth.head.php';

use ChatRoom\Core\Database\Base;
use ChatRoom\Core\Helpers\SystemSetting;

$setting = new SystemSetting(Base::getInstance()->getConnection());
?>

<div class="user-login-auth-image"></div>
<div class="user-auth-container">
    <h1 class="h4 mb-4 fw-normal">登录到<?= $setting->getSetting('site_name') ?></h1>
    <div class="mt-3" id="messageBox"></div>
    <form id="loginForm" action="/api/user?method=login" method="POST">
        <div>
            <label for="username" class="form-label">用户名:</label>
            <input type="text" class="form-control" id="username" name="username" required>
        </div>
        <div>
            <label for="password" class="form-label">密码:</label>
            <input type="password" class="form-control" id="password" name="password" autocomplete="password" required>
        </div>
        <div>
            <label for="captcha" class="form-label">验证码:</label>
            <div class="captcha-container">
                <input type="text" class="form-control" id="captcha" name="captcha" required>
                <img src="/api/v1/captcha" id="captchaImage" alt="验证码消失啦" onclick="this.src='/api/v1/captcha?'+Math.random()">
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100">登录</button>
        <div class="register mt-3 text-end">
            没有账号？<a href="register<?= $helpers->getGetParams('callBack') ?>">点击注册</a>
        </div>
    </form>
</div>
<?php
require_once FRAMEWORK_APP_PATH . '/Views/module/user.auth.footer.php';
