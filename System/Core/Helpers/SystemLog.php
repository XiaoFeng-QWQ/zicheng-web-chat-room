<?php

namespace ChatRoom\Core\Helpers;

use PDOException;
use Exception;
use PDO;

/**
 * 系统日志
 */
class SystemLog
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * 插入系统日志记录
     * @param mixed $logType
     * @param mixed $message
     * @throws \Exception
     * @return void
     */
    public function insertLog($logType, $message)
    {
        try {
            $stmt = $this->db->prepare('INSERT INTO system_logs (log_type, message, created_at) VALUES (:log_type, :message, :created_at)');
            $stmt->bindParam(':log_type', $logType);
            $stmt->bindParam(':message', $message);
            $stmt->bindValue(':created_at', date('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception('插入日志记录失败：' . $e->getMessage());
        }
    }

    /**
     * 读取系统日志记录
     * @param mixed $limit
     * @throws \Exception
     * @return mixed
     */
    public function getLogs($limit = 10)
    {
        try {
            $stmt = $this->db->prepare('SELECT * FROM system_logs ORDER BY created_at DESC LIMIT :limit');
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception('读取日志记录失败：' . $e->getMessage());
        }
    }

    /**
     * 更新系统日志记录
     * @param mixed $logId
     * @param mixed $logType
     * @param mixed $message
     * @throws \Exception
     * @return void
     */
    public function updateLog($logId, $logType, $message)
    {
        try {
            $stmt = $this->db->prepare('UPDATE system_logs SET log_type = :log_type, message = :message, created_at = :created_at WHERE log_id = :log_id');
            $stmt->bindParam(':log_id', $logId, PDO::PARAM_INT);
            $stmt->bindParam(':log_type', $logType);
            $stmt->bindParam(':message', $message);
            $stmt->bindValue(':created_at', date('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception('更新日志记录失败：' . $e->getMessage());
        }
    }
}
