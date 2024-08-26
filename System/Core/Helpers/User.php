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
    private $db;
    private string $userAgreementFile;

    public function __construct()
    {
        $this->parsedown = new Parsedown();
        $this->userAgreementFile = FRAMEWORK_DIR . '/StaticResources/MarkDown/UserAgreement.md';
        $this->db = SqlLite::getInstance()->getConnection();
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
            // 捕获并抛出异常
            throw new Exception("Error retrieving user information: " . $e->getMessage());
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
            throw new Exception("Error retrieving users: " . $e->getMessage());
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
            $stmt = $this->db->prepare("SELECT user_id, username, email, created_at, group_id FROM users LIMIT :limit OFFSET :offset");
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error retrieving users with pagination: " . $e->getMessage());
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
            throw new Exception("Error retrieving user count: " . $e->getMessage());
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
                throw new Exception("没有提供更新的数据。");
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
            throw new Exception("Error updating user: " . $e->getMessage());
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
            // 开始事务处理
            if (!$db->inTransaction()) {
                $db->beginTransaction();
            }

            // 准备 SQL 语句以删除用户
            $stmt = $db->prepare("DELETE FROM users WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

            // 执行 SQL 语句
            $stmt->execute();

            // 提交事务
            $db->commit();

            return true; // 返回成功标志
        } catch (Exception $e) {
            // 在错误时回滚事务
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            // 抛出异常并返回错误信息
            throw new Exception("Error deleting user: " . $e->getMessage());
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

        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
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
