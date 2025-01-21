<?php

namespace ChatRoom\Core\Helpers;

use PDO;
use Exception;
use Parsedown;
use ChatRoom\Core\Database\SqlLite;
use ChatRoom\Core\Modules\TokenManager;

/**
 * 用户辅助类
 */
class User
{
    private Parsedown $parsedown;
    private $db;
    protected $tokenManager;
    private string $userAgreementFile;

    public function __construct()
    {
        $this->parsedown = new Parsedown();
        $this->userAgreementFile = FRAMEWORK_DIR . '/StaticResources/MarkDown/user.agreement.md';
        $this->db = SqlLite::getInstance()->getConnection();
        $this->tokenManager = new TokenManager;;
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
     * 获取特定用户信息，返回用户信息数组
     *
     * @param string|null $username
     * @param int|null $userId
     * @return array
     * @throws Exception
     */
    public function getUserInfo(?string $username = null, ?int $userId = null): array
    {

        try {
            if ($userId !== null) {
                // 通过用户ID查询
                $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = :user_id");
                $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            } elseif ($username !== null) {
                // 通过用户名查询
                $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username");
                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            } else {
                return [];
            }

            $stmt->execute();
            $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

            return $userInfo ?: [];
        } catch (Exception $e) {
            throw new ("查询用户信息出错:" . $e);
        }
    }

    /**
     * 获取数据库所有用户
     *
     * @return array
     * @throws Exception
     */
    public function getAllUsers(): array
    {
        try {
            $stmt = $this->db->query("SELECT user_id, username, email, created_at, group_id FROM users");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new ("获取所有用户出错:" . $e);
        }
    }

    /**
     * 获取用户数据，支持分页
     *
     * @param int $limit 每页显示的记录数
     * @param int $offset 偏移量
     * @return array
     * @throws Exception
     */
    public function getUsersWithPagination(int $limit, int $offset): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users LIMIT :limit OFFSET :offset");
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new ("查询带有分页的用户出错:" . $e);
        }
    }

    /**
     * 获取用户总数
     *
     * @return int
     * @throws Exception
     */
    public function getUserCount(): int
    {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM users");
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            throw new ("获取用户总数出错:" . $e);
        }
    }

    /**
     * 更新用户信息
     *
     * @param int $userId 用户ID
     * @param array $data 包含用户更新信息的关联数组
     *                    格式为 ['username' => '新用户名', 'email' => '新邮箱']
     * @return bool 更新是否成功
     * @throws Exception
     */
    public function updateUser(int $userId, array $data): bool
    {
        $db = SqlLite::getInstance()->getConnection();

        try {
            if (!$db->inTransaction()) {
                $db->beginTransaction();
            }

            $fields = [];
            $params = [':user_id' => $userId];

            foreach ($data as $key => $value) {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }

            if (empty($fields)) {
                return \null;
            }

            $fieldsString = implode(', ', $fields);
            $stmt = $db->prepare("UPDATE users SET $fieldsString WHERE user_id = :user_id");

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->execute();
            $db->commit();

            return true;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw new ("更新用户信息出错:" . $e);
        }
    }

    /**
     * 删除指定用户
     *
     * @param integer $userId
     * @return boolean
     */
    public function deleteUser(int $userId): bool
    {
        // 获取数据库连接实例
        $db = SqlLite::getInstance()->getConnection();

        try {
            if (!$db->inTransaction()) {
                $db->beginTransaction();
            }

            $stmt = $db->prepare("DELETE FROM users WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $db->commit();

            return true;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw new ("删除用户出错:" . $e);
        }
    }

    /**
     * 获取用户协议文件内容并解析
     *
     * @param bool $raw 是否返回原始内容
     * @return string
     */
    public function readUserAgreement(bool $raw = false): string
    {
        if (!file_exists($this->userAgreementFile)) {
            return '用户协议文件不存在。';
        }

        $fileContents = file_get_contents($this->userAgreementFile);
        return $raw ? $fileContents : $this->parsedown->text($fileContents);
    }

    /**
     * 检查用户名是否已被使用
     *
     * @param string $username
     * @return bool
     */
    public function isUsernameTaken(string $username): bool
    {
        try {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            throw new ("检查用户名是否被使用出错:" . $e);
        }
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

    /**
     * 获取用户登录状态
     *
     * @return bool
     */
    function checkUserLoginStatus(): bool
    {
        try {
            // 检查 cookie 是否存在
            if (empty($_COOKIE['user_login_info'])) {
                return false;
            }
            $cookieData = json_decode($_COOKIE['user_login_info'], true);
            // 检查会话信息是否完整
            if (empty($cookieData['token']) || empty($cookieData['user_id'])) {
                return false;
            }
            // 使用 TokenManager 验证令牌
            return $this->tokenManager->validateToken($cookieData['token']);
        } catch (Exception $e) {
            throw new ("获取用户登录状态出错:" . $e);
        }
    }
}
