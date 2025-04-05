<?php

namespace ChatRoom\Core\Database;

use PDO;
use PDOException;

class SQLite
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        try {
            // 确保数据库文件存在
            if (empty(FRAMEWORK_DATABASE['host']) || !is_file(FRAMEWORK_DATABASE['host'])) {
                throw new PDOException('数据库文件不存在，请检查配置文件！');
            }
            $this->connection = new PDO('sqlite:' . FRAMEWORK_DATABASE['host']);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // 设置 SQLite 超时时间和启用 WAL 模式
            $this->connection->exec('PRAGMA busy_timeout = 5000;');
            $this->connection->exec('PRAGMA journal_mode=WAL;');
        } catch (PDOException $e) {
            throw new PDOException('SQLite数据库错误：' . $e->getMessage());
        }
    }

    /**
     * 获取 SQLite 实例
     *
     * @return SQLite
     */
    public static function getInstance(): SQLite
    {
        if (!self::$instance) {
            self::$instance = new SQLite();
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
