<?php
require_once __DIR__ . '/../config.global.php';
require_once __DIR__ . '/../vendor/autoload.php';

date_default_timezone_set("Asia/Shanghai");
session_start();

use ChatRoom\Core\Helpers\User;
use Gregwar\Captcha\PhraseBuilder;
use ChatRoom\Core\Helpers\Helpers;
use ChatRoom\Core\Helpers\SystemLog;
use ChatRoom\Core\Modules\TokenManager;
use ChatRoom\Core\Controller\UserController;
use ChatRoom\Core\Database\Base;

// 声明全局变量
global $errorMessage;
$errorMessage = '';

// 处理表单提交逻辑
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $captcha = trim($_POST['captcha']);

    // 验证验证码
    if (isset($_SESSION['captcha']) && PhraseBuilder::comparePhrases($_SESSION['captcha'], $captcha)) {
        try {
            authenticateUser($username, $password);
        } catch (Exception $e) {
            $errorMessage = '系统错误: ' . $e->getMessage();
        }
    } else {
        $errorMessage = '验证码错误';
    }
}

// 处理用户认证逻辑
function authenticateUser($username, $password)
{
    // 引入全局变量
    global $errorMessage;
    $db = Base::getInstance()->getConnection();

    $helpers = new Helpers;
    $UserHelpers = new User();
    $log = new SystemLog($db);
    $tokenManager = new TokenManager;
    $ip_address = $UserHelpers->getIp();

    $userController = new UserController;

    try {
        // 检查IP是否被封禁
        $stmt = $db->prepare('SELECT attempts, is_blocked FROM admin_login_attempts WHERE ip_address = :ip_address');
        $stmt->execute(['ip_address' => $ip_address]);
        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($attempt && $attempt['is_blocked']) {
            $errorMessage = '您的IP已被封禁，请联系管理员解封';
        } else {
            $loginMsg = $userController->login($username, $password, true);

            if ($loginMsg === true) {
                $user = $UserHelpers->getUserInfo($username);

                if ($user['group_id'] === 1) {
                    clearLoginAttempts($ip_address);
                    $log->insertLog('INFO', "管理员 $username 在IP $ip_address 登录成功");

                    // 移除无用信息
                    unset($user['email']);
                    unset($user['password']);
                    unset($user['register_ip']);
                    unset($_SESSION['captcha']);
                    $user['token'] = $tokenManager->generateToken($user['user_id'], '+1 year');
                    setcookie('user_login_info', json_encode($user), time() + 86400 * 365, '/');

                    // 重定向
                    exit(header('Location: ' . $helpers->getGetParams('callBack', false)));
                } else {
                    $errorMessage = '权限不足';
                }
            } else {
                handleFailedLogin($ip_address, $username, $log);
                $errorMessage = $loginMsg;
            }
        }
    } catch (Exception $e) {
        throw new Exception($e);
    }
}

// 清理登录尝试记录
function clearLoginAttempts($ip_address)
{
    // 引入全局变量
    $db = Base::getInstance()->getConnection();

    try {
        $db->prepare('DELETE FROM admin_login_attempts WHERE ip_address = :ip_address')->execute(['ip_address' => $ip_address]);
        return; // 如果操作成功，直接返回
    } catch (PDOException $e) {
        throw new Exception($e);
    }
}

// 处理失败的登录尝试
function handleFailedLogin($ip_address, $username, $log)
{
    // 引入全局变量
    $db = Base::getInstance()->getConnection();
    $last_attempt = date('Y-m-d H:i:s');

    try {
        // 查询尝试记录
        $stmt = $db->prepare('SELECT * FROM admin_login_attempts WHERE ip_address = :ip_address');
        $stmt->execute(['ip_address' => $ip_address]);
        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($attempt) {
            // 更新尝试记录
            $stmt = $db->prepare('UPDATE admin_login_attempts SET attempts = attempts + 1, last_attempt = :last_attempt WHERE ip_address = :ip_address');
            $stmt->execute(['ip_address' => $ip_address, 'last_attempt' => $last_attempt]);

            if ($attempt['attempts'] + 1 >= 3) {
                // 阻止IP地址
                $stmt = $db->prepare('UPDATE admin_login_attempts SET is_blocked = 1 WHERE ip_address = :ip_address');
                $stmt->execute(['ip_address' => $ip_address]);
                $log->insertLog('WARNING', "IP $ip_address 登录失败过多已封禁");
            }
        } else {
            // 插入新的尝试记录
            $stmt = $db->prepare('INSERT INTO admin_login_attempts (ip_address, attempts, last_attempt) VALUES (:ip_address, 1, :last_attempt)');
            $stmt->execute(['ip_address' => $ip_address, 'last_attempt' => $last_attempt]);
        }

        $log->insertLog('WARNING', "管理员 $username 在IP $ip_address 登录失败: 用户名或密码错误");
        return;
    } catch (PDOException $e) {
        throw new Exception($e);
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录</title>
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

        #captcha {
            padding: 0.8rem;
            margin-top: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .captcha-image {
            margin-left: 10px;
            cursor: pointer;
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
        <?php if (!empty($errorMessage)) : ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
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
            <div class="mb-3">
                <label for="captcha" class="form-label">验证码</label>
                <div class="captcha-container">
                    <input type="text" class="form-control" id="captcha" name="captcha" required>
                    <img src="/api/v1/captcha" alt="captcha" id="captchaImage" class="captcha-image mt-2" onclick="this.src='/api/v1/captcha?'+Math.random()">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">登录</button>
        </form>
    </div>
    <?php require_once FRAMEWORK_APP_PATH . '/Views/module/common.php' ?>
    <script src="/StaticResources/js/jquery.min.js"></script>
    <script src="/StaticResources/js/bootstrap.bundle.min.js"></script>
</body>

</html>