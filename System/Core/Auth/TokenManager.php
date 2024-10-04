<?php

namespace ChatRoom\Core\Auth;

use ChatRoom\Core\Database\SqlLite;
use Exception;
use PDO;

class TokenManager
{
    private $db;
    private $secretKey;

    public function __construct()
    {
        $this->db = SqlLite::getInstance()->getConnection();
        $this->secretKey = 'NX@HVPl%u$fXaT8V*Ur~1vzwBvZa2tB(8Q~M!nB9pX09Q#wOo&H2GliIppK7h84)!Vz3c~)^sAwm6&$u3XTxUDm^EeVwi3+gGfumY%u5Y2)qG_d1nlw19K)P3bG+N5ns@tNxT^O57wOkct$LcTbow@v_ngN*Tx_o2XYza@KCX*m+lCPIlG%Z~wr0M*e7LoxM4H~FZBRs8olPAKwIBac#0Cp6kp$U$&d5$WAAIoC(7awTD^TrKZt$2gP2MyE_jD$v';
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
            // 开始事务
            $this->db->beginTransaction();

            $token = bin2hex(random_bytes(32)); // 生成明文 token
            $hashedToken = hash_hmac('sha256', $token, $this->secretKey); // 对 token 进行哈希处理
            $expiration = date('Y-m-d H:i:s', strtotime($expirationInterval));
            $createdAt = date('Y-m-d H:i:s');

            // 定义 SQL 语句，插入或更新 token
            $sql = "INSERT INTO user_tokens (user_id, token, expiration, created_at, updated_at)
                VALUES (:user_id, :token, :expiration, :created_at, :updated_at)
                ON CONFLICT(user_id)
                DO UPDATE SET
                    token = :token,
                    expiration = :expiration,
                    updated_at = :updated_at";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':token', $hashedToken, PDO::PARAM_STR);
            $stmt->bindParam(':expiration', $expiration, PDO::PARAM_STR);
            $stmt->bindParam(':created_at', $createdAt, PDO::PARAM_STR);
            $stmt->bindParam(':updated_at', $createdAt, PDO::PARAM_STR);

            // 执行 SQL 语句
            if ($stmt->execute()) {
                // 提交事务
                $this->db->commit();
                return $token; // 返回未加密的明文 token
            } else {
                // 如果执行失败，回滚事务
                $this->db->rollBack();
                throw new Exception("Token 生成失败");
            }
        } catch (Exception $e) {
            // 捕获异常并回滚事务
            $this->db->rollBack();
            throw new Exception("生成 token 时发生错误: " . $e->getMessage());
        }
    }

    /**
     * 验证给定的 token 是否有效
     * @param string $token 要验证的 token
     * @param int $userId 用户 ID
     * @return bool token 是否有效
     */
    public function validateToken(string $token, int $userId): bool
    {
        // 对传递的 token 进行哈希处理
        $hashedToken = hash_hmac('sha256', $token, $this->secretKey);

        // 执行查询
        $sql = "SELECT * FROM user_tokens WHERE token = :token AND user_id = :user_id AND expiration > :current_time";
        $stmt = $this->db->prepare($sql);
        $currentTime = date('Y-m-d H:i:s');
        $stmt->bindParam(':token', $hashedToken, PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':current_time', $currentTime, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // 输出结果
        return $result ? true : false;
    }

    /**
     * 手动使 token 失效
     * @param int $userId 用户ID
     * @throws Exception
     */
    public function invalidateToken(int $userId)
    {
        $sql = "DELETE FROM user_tokens WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

        if (!$stmt->execute()) {
            throw new Exception("Token 失效失败");
        }
        return true;
    }
}
