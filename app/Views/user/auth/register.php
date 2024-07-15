<?php
if (isset($_SESSION['userinfo'])) {
    if (is_array($_SESSION['userinfo'])) {
        header('Location: /');
        exit();
    }
    header('Location: /');
    exit();
}
?>

<!DOCTYPE html>
<html lang="zh-cn">

<head>
    <meta charset="UTF-8">
    <title>注册到在线聊天室</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/StaticResources/css/user.auth.css?v0.2.1">
    <link rel="stylesheet" href="/StaticResources/css/rest.css">
    <meta name="keywords" content="在线聊天,聊天室,小枫QWQ,子辰">
    <meta name="description" content="用户注册,这是一个用bootstrapV5写的子辰在线聊天室">
</head>

<body>
    <div class="container">
        <div class="user-auth-container">
            <h1 class="h4 fw-normal text-center">注册到子辰在线聊天室V<?php echo FRAMEWORK_VERSION ?></h1>
            <form id="registerForm" action="" method="POST">
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
                        <img src="/api/captcha" alt="验证码消失啦" onclick="this.src='/api/captcha?'+Math.random()">
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
    </div>
    <script src="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('registerForm').addEventListener('submit', function(event) {
            var password = document.getElementById('password').value;
            var confirmPassword = document.getElementById('confirm_password').value;
            var messageBox = document.getElementById('messageBox');

            if (password !== confirmPassword) {
                event.preventDefault(); // 阻止表单提交
                messageBox.innerHTML = '<div class="alert alert-danger">密码和确认密码不匹配。</div>';
            }
        });
    </script>
    <script src="https://cdn.bootcdn.net/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="/StaticResources/js/user.auth.js"></script>
</body>

</html>