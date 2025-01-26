<?php

namespace ChatRoom\Core\Database;

use PDO;
use PDOException;

class SqlLite
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        try {
            // 确保数据库文件存在
            if (empty(FRAMEWORK_DATABASE_PATH) || !is_file(FRAMEWORK_DATABASE_PATH)) {
                throw new PDOException('数据库文件不存在，请检查配置文件！');
            }
            $this->connection = new PDO('sqlite:' . FRAMEWORK_DATABASE_PATH);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // 设置 SQLite 超时时间和启用 WAL 模式
            $this->connection->exec('PRAGMA busy_timeout = 5000;');
            $this->connection->exec('PRAGMA journal_mode=WAL;');
        } catch (PDOException $e) {
            throw new PDOException('数据库错误：' . $e);
        }
    }

    /**
     * 获取 SqlLite 实例
     *
     * @return SqlLite
     */
    public static function getInstance(): SqlLite
    {
        if (!self::$instance) {
            self::$instance = new SqlLite();
        }
        return self::$instance;
    }

    /**
     * 获取数据库连接
     *
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }
}
