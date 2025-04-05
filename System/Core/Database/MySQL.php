<?php

namespace ChatRoom\Core\Database;

use PDO;
use PDOException;
use ChatRoom\Core\Database\DebugPDOStatement;

class MySQL
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        try {
            $dsn = 'mysql:host=' . FRAMEWORK_DATABASE['host'] . ';dbname=' . FRAMEWORK_DATABASE['dbname'];
            $username = FRAMEWORK_DATABASE['username'];
            $password = FRAMEWORK_DATABASE['password'];
            $options = [];

            $this->connection = new PDO($dsn, $username, $password, $options);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new PDOException('MySQL数据库错误：' . $e->getMessage());
        }
    }

    /**
     * 获取 MySQL 实例
     *
     * @return MySQL
     */
    public static function getInstance(): MySQL
    {
        if (!self::$instance) {
            self::$instance = new MySQL();
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
        // 调试模式下记录查询
        if (defined('FRAMEWORK_DEBUG') && FRAMEWORK_DEBUG) {
            $this->connection->setAttribute(PDO::ATTR_STATEMENT_CLASS, [DebugPDOStatement::class, [$this->connection]]);
        }
        return $this->connection;
    }
}