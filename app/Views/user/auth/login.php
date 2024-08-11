<?php
// 检查 $_SESSION['user_login_info'] 是否存在且为数组
if (isset($_SESSION['user_login_info']) && is_array($_SESSION['user_login_info'])) {
    header('Location: /'); // 重定向到首页
    exit(); // 终止脚本执行
}

require_once FRAMEWORK_APP_PATH . '/Views/module/user.auth.head.php';

use ChatRoom\Core\Database\SqlLite;
use ChatRoom\Core\Helpers\SystemSetting;

$setting = new SystemSetting(SqlLite::getInstance()->getConnection());
?>

<div class="user-auth-container">
    <h1 class="h4 mb-4 fw-normal text-center">登录到<?= $setting->getSetting('site_name') ?></h1>
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
                <img src="/api/captcha" alt="验证码消失啦" onclick="this.src='/api/captcha?'+Math.random()">
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100">登录</button>
        <div class="register mt-3 text-end">
            没有账号？<a href="register">点击注册</a>
        </div>
    </form>
    <hr>
    <!-- 消息框 -->
    <div class="mt-3" id="messageBox">
        <!-- 错误或成功消息将在这里显示 -->
    </div>
</div>
<!-- 电脑端右侧图片显示部分 -->
<div class="user-login-auth-image"></div>
<?php
require_once FRAMEWORK_APP_PATH . '/Views/module/user.auth.footer.php';
