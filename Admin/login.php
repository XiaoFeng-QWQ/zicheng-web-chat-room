<?php
require_once __DIR__ . '/helper/login_verify.php';

use Gregwar\Captcha\CaptchaBuilder;

// 生成新验证码并存储在会话中
$builder = new CaptchaBuilder;
$builder->build();
$_SESSION['captcha'] = $builder->getPhrase();
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>子辰聊天室 - 管理员登录</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.1.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f8f9fa;
        }

        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            background-color: #ffffff;
        }

        .captcha-container {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }

        .refresh-captcha {
            margin-left: 10px;
            cursor: pointer;
            color: #007bff;
        }

        .refresh-captcha:hover {
            text-decoration: underline;
        }

        .form-label {
            font-weight: bold;
        }

        .form-control {
            border-radius: 5px;
        }

        .btn-primary {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border-radius: 5px;
        }

        .alert {
            font-size: 14px;
        }

        .captcha-image {
            border-radius: 5px;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #343a40;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <h1>管理员登录</h1>
        <?php
        if (isset($errorMessage)) : ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">用户名</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">密码</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3 captcha-container">
                <div>
                    <label for="captcha" class="form-label">验证码</label>
                    <input type="text" class="form-control" id="captcha" name="captcha" required>
                    <img src="/api/captcha" alt="captcha" id="captchaImage" class="captcha-image mt-2" onclick="this.src='/api/captcha?'+Math.random()">
                </div>
                <span class="refresh-captcha" id="refreshCaptcha">刷新验证码</span>
            </div>
            <button type="submit" class="btn btn-primary">登录</button>
        </form>
    </div>
    <script src="https://cdn.bootcdn.net/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.1.0/js/bootstrap.bundle.min.js"></script>
</body>

</html>