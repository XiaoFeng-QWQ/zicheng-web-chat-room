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
            $this->db->beginTransaction();

            $token = bin2hex(random_bytes(32));
            $hashedToken = hash_hmac('sha256', $token, $this->secretKey);
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
                $stmtUpdate->bindParam(':token', $hashedToken, PDO::PARAM_STR);
                $stmtUpdate->bindParam(':expiration', $expiration, PDO::PARAM_STR);
                $stmtUpdate->bindParam(':updated_at', $createdAt, PDO::PARAM_STR);
                $stmtUpdate->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $stmtUpdate->execute();
            } else {
                // 如果不存在，执行插入
                $sqlInsert = "INSERT INTO user_tokens (user_id, token, expiration, created_at, updated_at) VALUES (:user_id, :token, :expiration, :created_at, :updated_at)";
                $stmtInsert = $this->db->prepare($sqlInsert);
                $stmtInsert->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $stmtInsert->bindParam(':token', $hashedToken, PDO::PARAM_STR);
                $stmtInsert->bindParam(':expiration', $expiration, PDO::PARAM_STR);
                $stmtInsert->bindParam(':created_at', $createdAt, PDO::PARAM_STR);
                $stmtInsert->bindParam(':updated_at', $createdAt, PDO::PARAM_STR);
                $stmtInsert->execute();
            }

            $this->db->commit();
            return $token;
        } catch (Exception $e) {
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
