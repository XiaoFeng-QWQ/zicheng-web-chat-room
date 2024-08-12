<?php

namespace ChatRoom\Core\Helpers;

use PDO;
use PDOException;
use Exception;

/**
 * 系统设置辅助类
 */
class SystemSetting
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * 根据设置名称获取系统设置的值
     * 
     * @param string $name 设置名称
     * @return mixed 返回设置的值，如果是JSON字符串则解析为数组，否则返回原始值，如果不存在则返回 null
     * @throws Exception 如果数据库操作出错，抛出异常
     */
    public function getSetting(string $name)
    {
        try {
            $stmt = $this->db->prepare("SELECT value FROM system_sets WHERE name = :name");
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                $value = $result['value'];

                // 尝试将值解析为JSON，如果解析成功则返回解析后的数组，否则返回原始值
                $decodedValue = json_decode($value, true);
                return json_last_error() === JSON_ERROR_NONE ? $decodedValue : $value;
            }

            return null;
        } catch (PDOException $e) {
            throw new Exception("获取设置时出错: " . $e->getMessage());
        }
    }

    /**
     * 设置系统配置的值
     *
     * @param string $name 设置名称
     * @param mixed $value 设置值
     * @return void
     * @throws Exception 如果数据库操作出错，抛出异常
     */
    public function setSetting(string $name, $value): void
    {
        try {
            // 检查设置是否存在
            $stmt = $this->db->prepare("SELECT id FROM system_sets WHERE name = :name");
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // 根据结果执行更新或插入操作
            $stmt = $result ? $this->db->prepare("UPDATE system_sets SET value = :value WHERE name = :name") : $this->db->prepare("INSERT INTO system_sets (name, value) VALUES (:name, :value)");

            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':value', $value, PDO::PARAM_STR);
            $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("设置值时出错: " . $e->getMessage());
        }
    }

    /**
     * 获取所有系统设置
     *
     * @return array 所有设置的键值对数组
     * @throws Exception 如果数据库操作出错，抛出异常
     */
    public function getAllSettings(): array
    {
        try {
            $stmt = $this->db->query("SELECT name, value FROM system_sets");
            $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $result = [];
            foreach ($settings as $setting) {
                $decodedValue = json_decode($setting['value'], true);
                $result[$setting['name']] = json_last_error() === JSON_ERROR_NONE ? $decodedValue : $setting['value'];
            }

            return $result;
        } catch (PDOException $e) {
            throw new Exception("获取所有设置时出错: " . $e->getMessage());
        }
    }
}