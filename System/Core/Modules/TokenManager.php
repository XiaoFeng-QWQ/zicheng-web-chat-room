<?php

namespace ChatRoom\Core\Modules;

use ChatRoom\Core\Database\Base;
use PDOException;
use Exception;
use PDO;

class TokenManager
{
    private $db;

    public function __construct()
    {
        $this->db = Base::getInstance()->getConnection();
    }

    /**
     * 生成一个新的 token，并将其插入到数据库中
     * @param int $userId 用户ID
     * @param string|null $expirationInterval 过期时间的间隔，默认是 '+1 hour'
     * @return string 生成的 token
     * @throws Exception
     */
    public function generateToken(int $userId, string $expirationInterval = '+1 hour'): string
    {
        try {
            $this->db->beginTransaction();
            // 随机选择一个加密算法
            $hashAlgorithms = [
                'sha256',
                'sha512',
                'md5',
                'sha1'
            ];
            $selectedAlgorithm = $hashAlgorithms[array_rand($hashAlgorithms)];
            $token = bin2hex(hash($selectedAlgorithm, random_bytes(32) . $userId . time(), true));

            $expiration = date('Y-m-d H:i:s', strtotime($expirationInterval));
            $createdAt = date('Y-m-d H:i:s');

            // 检查记录是否存在
            $sqlCheck = "SELECT COUNT(*) FROM user_tokens WHERE user_id = :user_id";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmtCheck->execute();
            $exists = $stmtCheck->fetchColumn() > 0;

            if ($exists) {
                // 如果存在，执行更新
                $sqlUpdate = "UPDATE user_tokens SET token = :token, expiration = :expiration, updated_at = :updated_at WHERE user_id = :user_id";
                $stmtUpdate = $this->db->prepare($sqlUpdate);
                $stmtUpdate->bindParam(':token', $token, PDO::PARAM_STR);
                $stmtUpdate->bindParam(':expiration', $expiration, PDO::PARAM_STR);
                $stmtUpdate->bindParam(':updated_at', $createdAt, PDO::PARAM_STR);
                $stmtUpdate->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $stmtUpdate->execute();
            } else {
                $sqlInsert = "INSERT INTO user_tokens (user_id, token, expiration, created_at, updated_at) VALUES (:user_id, :token, :expiration, :created_at, :updated_at)";
                $stmtInsert = $this->db->prepare($sqlInsert);
                $stmtInsert->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $stmtInsert->bindParam(':token', $token, PDO::PARAM_STR);
                $stmtInsert->bindParam(':expiration', $expiration, PDO::PARAM_STR);
                $stmtInsert->bindParam(':created_at', $createdAt, PDO::PARAM_STR);
                $stmtInsert->bindParam(':updated_at', $createdAt, PDO::PARAM_STR);
                $stmtInsert->execute();
            }

            $this->db->commit();
            return $token;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw new PDOException("生成 token 发生错误:" . $e->getMessage());
        }
    }

    /**
     * 验证给定的 token 是否有效
     * @param string $token 要验证的 token
     * @return bool token 是否有效
     */
    public function validateToken(string $token): bool
    {
        try {
            $sql = "SELECT * FROM user_tokens WHERE token = :token";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':token', $token, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            // 输出结果
            return $result ? true : false;
        } catch (PDOException $e) {
            throw new PDOException("验证 token 发生错误:" . $e->getMessage());
        }
    }

    /**
     * 根具token返回用户信息
     *
     * @param Token $token
     * @return array
     */
    public function getInfo($token): array
    {
        try {
            $sql = "SELECT * FROM user_tokens WHERE token = :token";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':token', $token, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            // 输出结果
            return $result ? $result : throw new ("获取 token 信息发生错误: 无法获取");
        } catch (PDOException $e) {
            throw new PDOException("获取 token 信息发生错误:" . $e->getMessage());
        }
    }

    /**
     * 删除指定用户 ID TOKEN
     *
     * @param int $userId
     * @return bool
     */
    public function delet(int $userId): bool
    {
        try {
            $sql = "DELETE FROM user_tokens WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new PDOException("删除 token 发生错误:" . $e->getMessage());
        }
    }
}
