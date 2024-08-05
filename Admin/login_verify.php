<?php
// 引入基本常量
require_once __DIR__ . '/../config.global.php';
require_once __DIR__ . '/../vendor/autoload.php';
// 强制设置时区为国内
date_default_timezone_set("Asia/Shanghai");

use ChatRoom\Core\Helpers\User;
use Gregwar\Captcha\PhraseBuilder;

// 启动会话
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $UserHelpers = new User();
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $captcha = trim($_POST['captcha']);
    $ip_address = $UserHelpers->getIp();

    // 验证码验证
    if (isset($_SESSION['captcha']) && PhraseBuilder::comparePhrases($_SESSION['captcha'], $captcha)) {
        // 数据库连接
        try {
            $db = new PDO('sqlite:' . FRAMEWORK_DATABASE_PATH);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
                    setcookie('login_token', $loginToken, time() + 86400 * 30, "/"); // 30天过期

                    // 清除登录尝试记录
                    $db->prepare('DELETE FROM admin_login_attempts WHERE ip_address = :ip_address')->execute(['ip_address' => $ip_address]);

                    // 重定向到管理仪表板
                    header('Location: /Admin/');
                    exit;
                } else {
                    // 记录登录尝试
                    try {
                        $ip_address = $_SERVER['REMOTE_ADDR']; // 假设 $ip_address 是从请求中获取的
                        $last_attempt = date('Y-m-d H:i:s');

                        // 查询尝试记录
                        $stmt = $db->prepare('SELECT * FROM admin_login_attempts WHERE ip_address = :ip_address');
                        $stmt->bindParam(':ip_address', $ip_address, PDO::PARAM_STR);
                        $stmt->execute();
                        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($attempt) {
                            // 更新尝试记录
                            $stmt = $db->prepare('UPDATE admin_login_attempts SET attempts = attempts + 1, last_attempt = :last_attempt WHERE ip_address = :ip_address');
                            $stmt->execute(['ip_address' => $ip_address, 'last_attempt' => $last_attempt]);

                            if ($attempt['attempts'] + 1 >= 3) {
                                // 阻止IP地址
                                $stmt = $db->prepare('UPDATE admin_login_attempts SET is_blocked = 1, last_attempt = :last_attempt WHERE ip_address = :ip_address');
                                $stmt->execute(['ip_address' => $ip_address, 'last_attempt' => $last_attempt]);
                            }
                        } else {
                            // 插入新的尝试记录
                            $stmt = $db->prepare('INSERT INTO admin_login_attempts (ip_address, attempts, last_attempt) VALUES (:ip_address, 1, :last_attempt)');
                            $stmt->execute(['ip_address' => $ip_address, 'last_attempt' => $last_attempt]);
                        }

                        $errorMessage = '用户名或密码错误';
                    } catch (PDOException $e) {
                        throw new Exception('Database error: ' . $e->getMessage());
                    }
                }
            }
        } catch (PDOException $e) {
            $errorMessage = '数据库连接失败: ' . $e->getMessage();
        }
    } else {
        $errorMessage = '验证码错误';
    }
}
