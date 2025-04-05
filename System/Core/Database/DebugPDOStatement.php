<?php

namespace ChatRoom\Core\Database;

use PDO;
use PDOStatement;

/**
 * 调试用的PDOStatement类
 */
class DebugPDOStatement extends PDOStatement
{
    private PDO $connection;
    private float $startTime;
    private array $boundParams = [];

    /**
     * 查询日志文件路径
     */
    private const QUERY_LOG_FILE = FRAMEWORK_DIR . '/Writable/logs/db_queries.log';

    protected function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    /**
     * 记录查询日志到文件
     *
     * @param string $sql SQL语句
     * @param array $params 参数
     * @param float $time 执行时间(秒)
     * @param string $caller 调用位置（文件:行号）
     */
    private function logQuery(string $sql, array $params = [], float $time = 0, string $caller = ''): void
    {
        if (!defined('FRAMEWORK_DEBUG') || !FRAMEWORK_DEBUG) {
            return;
        }

        // 确保日志目录存在
        $logDir = dirname(self::QUERY_LOG_FILE);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // 创建日志数据数组
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'caller' => $caller,
            'sql' => $sql,
            'params' => $params,
            'time' => $time
        ];

        // 序列化数据并写入日志文件
        $serializedData = serialize($logData) . PHP_EOL;
        file_put_contents(self::QUERY_LOG_FILE, $serializedData, FILE_APPEND);
    }

    public function execute($params = null): bool
    {
        $this->startTime = microtime(true);

        // 合并绑定的参数和execute参数
        $mergedParams = $this->boundParams;
        if (is_array($params)) {
            $mergedParams = array_merge($mergedParams, $params);
        }

        $result = parent::execute($params);
        $time = microtime(true) - $this->startTime;

        // 获取调用位置（文件:行号）
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $caller = $this->findSqlCaller($backtrace);

        // 记录查询日志
        $this->logQuery($this->queryString, $mergedParams, $time, $caller);

        // 清空绑定参数
        $this->boundParams = [];

        return $result;
    }

    /**
     * 从调用堆栈中找出执行SQL的位置
     */
    private function findSqlCaller(array $backtrace): string
    {
        // 跳过PDO内部调用
        $skipClasses = ['PDO', 'PDOStatement', 'DebugPDO', 'DebugPDOStatement'];

        foreach ($backtrace as $trace) {
            $class = $trace['class'] ?? '';
            if (!in_array($class, $skipClasses)) {
                $file = $trace['file'] ?? 'unknown';
                $line = $trace['line'] ?? 0;
                return basename($file) . ':' . $line;
            }
        }

        return 'unknown:0';
    }

    public function bindParam(
        $param,
        &$var,
        $type = PDO::PARAM_STR,
        $maxLength = 0,  // 将 null 改为 0
        $driverOptions = null
    ): bool {
        $this->boundParams[$param] = $var;
        return parent::bindParam($param, $var, $type, $maxLength, $driverOptions);
    }

    public function bindValue($param, $value, $type = PDO::PARAM_STR): bool
    {
        $this->boundParams[$param] = $value;
        return parent::bindValue($param, $value, $type);
    }
}
