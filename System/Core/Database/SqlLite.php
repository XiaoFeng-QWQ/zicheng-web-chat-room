<?php

namespace ChatRoom\Core\Database;

use PDO;
use Exception;
use PDOException;
use Psr\Log\LoggerInterface;

class SqlLite
{
    // 单例实例
    private static $instance = null;
    // 数据库连接实例
    private $connection;
    /**
     * 私有构造函数以实现单例模式
     *
     */
    private function __construct()
    {
        try {
            // 建立 SQLite 数据库连接
            $this->connection = new PDO('sqlite:' . FRAMEWORK_DATABASE_PATH);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception('数据库错误：' . $e->getMessage());
        }
    }
    /**
     * 获取 SqlLite 单例实例
     *
     * @param LoggerInterface|null $logger 可选的日志记录器实例
     * @return SqlLite 单例实例
     */
    public static function getInstance()
    {
        // 如果单例实例尚未创建，则创建实例
        if (!self::$instance) {
            self::$instance = new SqlLite();
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
