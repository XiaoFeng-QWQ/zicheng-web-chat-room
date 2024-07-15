<?php

namespace ChatRoom\Core\Database;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

class SqlLite
{
    // 单例实例
    private static $instance = null;
    // 数据库连接实例
    private $connection;
    // 日志记录器实例
    private $logger;
    /**
     * 私有构造函数以实现单例模式
     *
     * @param LoggerInterface|null $logger PSR-3 兼容的日志记录器实例
     */
    private function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        try {
            // 建立 SQLite 数据库连接
            $this->connection = new PDO('sqlite:' . FRAMEWORK_DIR . '/System/Data/database.db');
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // 生成错误信息和调用堆栈
            $errorMessage = "Database operation failed: " . $e->getMessage();
            $errorTrace = $e->getTraceAsString();
            // 如果提供了日志记录器，记录错误信息和调用堆栈
            if ($this->logger) {
                $this->logger->error($errorMessage);
                $this->logger->error($errorTrace);
            }
            echo "服务器发生数据库错误。请联系管理员。";
            // 同步输出错误信息和调用堆栈
            http_response_code(500);
            // 终止脚本执行
            exit();
        }
    }
    /**
     * 获取 SqlLite 单例实例
     *
     * @param LoggerInterface|null $logger 可选的日志记录器实例
     * @return SqlLite 单例实例
     */
    public static function getInstance(LoggerInterface $logger = null)
    {
        // 如果单例实例尚未创建，则创建实例
        if (!self::$instance) {
            self::$instance = new SqlLite($logger);
        }
        return self::$instance;
    }
    /**
     * 获取数据库连接
     *
     * @return PDO 数据库连接实例
     */
    public function getConnection()
    {
        return $this->connection;
    }
}
