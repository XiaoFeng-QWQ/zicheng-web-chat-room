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
            // 建立 SQLite 数据库连接
            $this->connection = new PDO('sqlite:' . FRAMEWORK_DATABASE_PATH);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 设置 SQLite 超时时间和启用 WAL 模式
            $this->connection->exec('PRAGMA busy_timeout = 5000;');
            $this->connection->exec('PRAGMA journal_mode=WAL;');
        } catch (PDOException $e) {
            throw new ('数据库错误：' . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new SqlLite();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->connection;
    }
}
