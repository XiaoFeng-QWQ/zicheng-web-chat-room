<?php
require_once FRAMEWORK_APP_PATH . '/Views/module/user.auth.head.php';
?>

<div class="user-register-auth-image"></div>
<div class="user-auth-container">
    <h1 class="h4 fw-normal">注册到<?= $setting->getSetting('site_name') ?></h1>
    <div class="mt-3" id="messageBox"></div>
    <form id="registerForm" action="/api/user?method=register" method="POST">
        <?php
        if ($setting->getSetting('enable_user_registration') === true) {
        ?>
            <div>
                <label for="username" class="form-label">用户名:</label>
                <input type="text" class="form-control" id="username" name="username" autocomplete="username" required>
            </div>
            <div>
                <label for="password" class="form-label">密码(请妥善保管好您的密码):</label>
                <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" required>
            </div>
            <div>
                <label for="confirm_password" class="form-label">确认密码:</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" autocomplete="new-password" required>
            </div>
            <div>
                <label for="captcha" class="form-label">验证码:</label>
                <div class="captcha-container">
                    <input type="text" class="form-control" id="captcha" name="captcha" required>
                    <img src="/api/v1/captcha" id="captchaImage" alt="验证码消失啦" onclick="this.src='/api/v1/captcha?'+Math.random()">
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100">注册并登录</button>
            <div class="form-check d-flex align-items-center">
                <input class="form-check-input" type="checkbox" id="UserAgreementCheckbox" value="option1">
                <label class="form-check-label ms-2" for="UserAgreementCheckbox">
                    已阅读并同意<a href="#UserAgreement" id="UserAgreement">《用户协议》</a>
                </label>
                <div class="register ms-auto">
                    <a href="login<?= $helpers->getGetParams('callBack') ?>">已有账号？点击登录</a>
                </div>
            </div>
        <?php
        } else {
        ?>
            <p>新用户注册已被禁用</p>
            <div class="form-check d-flex align-items-center">
                <div class="register ms-auto">
                    <a href="login<?= $helpers->getGetParams('callBack') ?>">已有账号，点击登录</a>
                </div>
            </div>
        <?php
        }
        ?>
    </form>
</div>
<div class="modal fade" id="UserAgreementModal" tabindex="-1" aria-labelledby="UserAgreementModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="UserAgreementModalLabel">用户协议</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
            </div>
            <div class="modal-body">
                <?= $UserHelpers->readUserAgreement() ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">确定</button>
            </div>
        </div>
    </div>
</div>


<?php
require_once FRAMEWORK_APP_PATH . '/Views/module/user.auth.footer.php';
