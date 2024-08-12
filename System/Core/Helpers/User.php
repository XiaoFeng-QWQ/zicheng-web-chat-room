<?php

namespace ChatRoom\Core\Helpers;

use PDO;
use Exception;
use Parsedown;
use ChatRoom\Core\Database\SqlLite;

/**
 * 用户辅助类
 */
class User
{
    private Parsedown $parsedown;
    private string $userAgreementFile;

    public function __construct()
    {
        $this->parsedown = new Parsedown();
        $this->userAgreementFile = FRAMEWORK_DIR . '/StaticResources/MarkDown/UserAgreement.md';
    }

    /**
     * 验证用户名
     *
     * @param string $username
     * @return bool
     */
    public function validateUsername(string $username): bool
    {
        // 检查用户名是否为空和长度是否在3到20字符之间
        if (strlen($username) < 3 || strlen($username) > 20) {
            return false;
        }

        // 检查用户名是否只包含字母、数字和下划线
        return (bool) preg_match('/^[a-zA-Z0-9_]+$/', $username);
    }

    /**
     * 获取用户信息，返回用户信息数组
     *
     * @param string|null $username
     * @param int|null $userId
     * @return array
     * @throws Exception
     */
    public function getUserInfo(?string $username = null, ?int $userId = null): array
    {
        $db = SqlLite::getInstance()->getConnection();

        try {
            if ($userId !== null) {
                // 通过用户ID查询
                $stmt = $db->prepare("SELECT * FROM users WHERE user_id = :user_id");
                $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            } elseif ($username !== null) {
                // 通过用户名查询
                $stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            } else {
                return [];
            }

            $stmt->execute();
            $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

            return $userInfo ?: [];
        } catch (Exception $e) {
            // 捕获并抛出异常
            throw new Exception("Error retrieving user information: " . $e->getMessage());
        }
    }

    /**
     * 获取用户协议文件内容
     *
     * @return string
     */
    public function readUserAgreement(): string
    {
        if (file_exists($this->userAgreementFile)) {
            $fileContents = file_get_contents($this->userAgreementFile);
            return $this->parsedown->text($fileContents);
        }

        return '用户协议文件不存在。';
    }

    /**
     * 检查用户名是否已被使用
     *
     * @param string $username
     * @return bool
     */
    public function isUsernameTaken(string $username): bool
    {
        $db = SqlLite::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchColumn() > 0;
    }

    /**
     * 获取用户IP
     *
     * @return string
     */
    public function getIp(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            foreach (explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        if (!empty($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return 'unknown';
    }
}