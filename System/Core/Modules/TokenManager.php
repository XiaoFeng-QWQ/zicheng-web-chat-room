<?php

namespace ChatRoom\Core\Modules;

use ChatRoom\Core\Database\SqlLite;
use Exception;
use PDO;

class TokenManager
{
    private $db;

    public function __construct()
    {
        $this->db = SqlLite::getInstance()->getConnection();
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

            $token = bin2hex(random_bytes(32));
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
                // 如果不存在，执行插入
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
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new ("生成 token 发生错误:" . $e);
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
        } catch (Exception $e) {
            throw new ("验证 token 发生错误:" . $e);
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
        } catch (Exception $e) {
            throw new ("获取 token 信息发生错误:" . $e);
        }
    }
}
