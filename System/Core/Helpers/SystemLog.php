<?php

namespace ChatRoom\Core\Helpers;

use PDO;
use PDOException;
use Exception;

/**
 * 系统日志辅助类
 */
class SystemLog
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * 插入系统日志记录
     *
     * @param string $logType 日志类型
     * @param string $message 日志信息
     * @return void
     * @throws Exception 如果插入日志记录失败
     */
    public function insertLog(string $logType, string $message): void
    {
        try {
            $stmt = $this->db->prepare('INSERT INTO system_logs (log_type, message, created_at) VALUES (:log_type, :message, :created_at)');
            $stmt->bindParam(':log_type', $logType, PDO::PARAM_STR);
            $stmt->bindParam(':message', $message, PDO::PARAM_STR);
            $stmt->bindValue(':created_at', date('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();
        } catch (PDOException $e) {
            throw new ('插入日志记录失败:' . $e);
        }
    }

    /**
     * 读取系统日志记录
     *
     * @param int $limit 返回的日志条数
     * @return array 返回日志记录的数组
     * @throws Exception 如果读取日志记录失败
     */
    public function getLogs(int $limit = 10): array
    {
        try {
            $stmt = $this->db->prepare('SELECT * FROM system_logs ORDER BY created_at DESC LIMIT :limit');
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            throw new ('读取日志记录失败:' . $e);
        }
    }

    /**
     * 更新系统日志记录
     *
     * @param int $logId 日志记录的ID
     * @param string $logType 日志类型
     * @param string $message 日志信息
     * @return void
     * @throws Exception 如果更新日志记录失败
     */
    public function updateLog(int $logId, string $logType, string $message): void
    {
        try {
            $stmt = $this->db->prepare('UPDATE system_logs SET log_type = :log_type, message = :message, created_at = :created_at WHERE log_id = :log_id');
            $stmt->bindParam(':log_id', $logId, PDO::PARAM_INT);
            $stmt->bindParam(':log_type', $logType, PDO::PARAM_STR);
            $stmt->bindParam(':message', $message, PDO::PARAM_STR);
            $stmt->bindValue(':created_at', date('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();
        } catch (PDOException $e) {
            throw new ('更新日志记录失败:' . $e);
        }
    }
}