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
    <title>登录到在线聊天室</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/res/css/user.auth.css?v0.2.0">
    <link rel="stylesheet" href="/res/css/rest.css">
    <meta name="keywords" content="在线聊天,聊天室,小枫QWQ,子辰">
    <meta name="description" content="用户登录,这是一个用bootstrapV5写的子辰在线聊天室">
</head>

<body>
    <div class="container">
        <div class="user-auth-container">
            <h1 class="h4 mb-4 fw-normal text-center">登录到子辰在线聊天室V0.11.1(测试版)</h1>
            <form id="loginForm" method="POST">
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
                <div class="register mt-3 text-center">
                    没有账号？<a href="register">点击注册</a>
                </div>
            </form>
            <hr>
            <!-- 登录消息框 -->
            <div class="mt-3" id="messageBox"></div>
        </div>
        <!-- 电脑端右侧图片显示部分 -->
        <div class="user-login-auth-image"></div>
    </div>
    <script src="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="/res/js/user.auth.js"></script>
</body>

</html>