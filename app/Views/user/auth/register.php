<?php
// 检查 $_SESSION['userinfo'] 是否存在且为数组
if (isset($_SESSION['userinfo']) && is_array($_SESSION['userinfo'])) {
    header('Location: /'); // 重定向到首页
    exit(); // 终止脚本执行
}

require_once FRAMEWORK_APP_PATH . '/Views/module/user.auth.head.php'
?>

<div class="user-auth-container">
    <h1 class="h4 fw-normal text-center">注册到子辰在线聊天室V<?php echo FRAMEWORK_VERSION ?></h1>
    <form id="registerForm" action="/api/user?method=register" method="POST">
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
                <img src="/Admin/generate_captcha.php" alt="验证码消失啦" onclick="this.src='/Admin/generate_captcha.php?'+Math.random()">
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100">注册并登录</button>
        <div class="register mt-3 text-center">
            已有账号？<a href="login">点击登录</a>
        </div>
    </form>
    <hr>
    <!-- 消息框 -->
    <div class="mt-3" id="messageBox">
        <!-- 错误或成功消息将在这里显示 -->
    </div>
</div>
<!-- 电脑端右侧图片显示部分 -->
<div class="user-register-auth-image"></div>

<?php
require_once FRAMEWORK_APP_PATH . '/Views/module/user.auth.footer.php';
