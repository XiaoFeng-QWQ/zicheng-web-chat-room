<?php
// 引入基本常量和库
require_once __DIR__ . '/../../config.global.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . "/../helper/database_connection.php";

// 设置时区
date_default_timezone_set("Asia/Shanghai");

session_start();

use ChatRoom\Core\Helpers\User;
use Gregwar\Captcha\PhraseBuilder;
use ChatRoom\Core\Helpers\SystemLog;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $captcha = trim($_POST['captcha']);

    // 实例化User和SystemLog类
    $UserHelpers = new User();
    $log = new SystemLog($db);

    // 验证验证码
    if (isset($_SESSION['captcha']) && PhraseBuilder::comparePhrases($_SESSION['captcha'], $captcha)) {
        try {
            // 检查IP是否被封禁
            $stmt = $db->prepare('SELECT attempts, is_blocked FROM admin_login_attempts WHERE ip_address = :ip_address');
            $stmt->execute(['ip_address' => $ip_address]);
            $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($attempt && $attempt['is_blocked']) {
                $errorMessage = '您的IP已被封禁，请联系管理员解封';
            } else {
                // 查询用户信息
                $stmt = $db->prepare('SELECT user_id, password, group_id FROM users WHERE username = :username');
                $stmt->execute(['username' => $username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // 验证用户名和密码
                if ($user && password_verify($password, $user['password'])) {
                    // 生成登录令牌
                    $loginToken = bin2hex(random_bytes(64));

                    // 更新数据库中的登录令牌
                    $updateStmt = $db->prepare('UPDATE users SET login_token = :login_token WHERE user_id = :user_id');
                    $updateStmt->execute([
                        'login_token' => $loginToken,
                        'user_id' => $user['user_id']
                    ]);

                    // 设置会话和cookie
                    $_SESSION['user_logged_in'] = true;
                    $_SESSION['login_token'] = $loginToken;
                    $_SESSION['user_id'] = $user['user_id'];
                    unset($user['captcha']);
                    setcookie('login_token', $loginToken, time() + 86400 * 30, "/"); // 30天过期

                    // 清除登录尝试记录
                    $db->prepare('DELETE FROM admin_login_attempts WHERE ip_address = :ip_address')->execute(['ip_address' => $ip_address]);
                    $log->insertLog('INFO', "管理员 $username 在IP $ip_address 登录成功");

                    // 重定向到管理仪表板
                    header('Location: /Admin/');
                    exit;
                } else {
                    // 记录登录尝试
                    handleFailedLogin($db, $ip_address, $username, $log);
                    $errorMessage = '用户名或密码错误';
                }
            }
        } catch (PDOException $e) {
            $errorMessage = '数据库连接失败: ' . $e->getMessage();
        }
    } else {
        $errorMessage = '验证码错误';
    }
}

// 处理失败的登录尝试
function handleFailedLogin($db, $ip_address, $username, $log)
{
    try {
        $last_attempt = date('Y-m-d H:i:s');

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
    } catch (PDOException $e) {
        throw new Exception('Database error: ' . $e->getMessage());
    }
}
