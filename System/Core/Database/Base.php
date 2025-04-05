<?php

namespace ChatRoom\Core\Database;

use Exception;
use PDO;

/**
 * 数据库基类
 */
class Base
{
    /**
     * 获取数据库实例
     *
     * @return MySQL|SQLite
     * @throws Exception
     */
    public static function getInstance(): MySQL|SQLite
    {
        $config = FRAMEWORK_DATABASE;
        switch ($config['driver']) {
            case 'mysql':
                return MySQL::getInstance();
            case 'sqlite':
                return SQLite::getInstance();
            default:
                throw new Exception('不支持的数据库驱动: ' . $config['driver']);
        }
    }

    /**
     * 获取数据库连接
     *
     * @return PDO
     * @throws Exception
     */
    public static function getConnection(): PDO
    {
        $connection = self::getInstance()->getConnection();
        return $connection;
    }
}
