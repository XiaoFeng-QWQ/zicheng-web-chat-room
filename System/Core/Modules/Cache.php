<?php

namespace ChatRoom\Core\Modules;

use Exception;

class Cache
{
    private array $config;
    private bool $enabled;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->enabled = $this->config['enabled'] ?? false;
        // 确保缓存目录存在
        if ($this->enabled && !is_dir($this->config['path'])) {
            if (!mkdir($this->config['path'], 0755, true)) {
                throw new Exception("无法创建缓存目录: " . $this->config['path']);
            }
        }
    }

    /**
     * 检查缓存是否启用
     * 
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * 获取缓存项
     * 
     * @param string $key 缓存键
     * @return mixed 缓存值
     */
    public function get(string $key): mixed
    {
        if (!$this->enabled) {
            return null;
        }
        $file = $this->getCacheFilePath($key);
        if (!file_exists($file)) {
            return null;
        }
        $data = unserialize(file_get_contents($file));
        // 检查是否过期
        if (time() > $data['expire']) {
            $this->delete($key);
            return null;
        }
        return $data['content'];
    }

    /**
     * 设置缓存项
     * 
     * @param string $key 缓存键
     * @param mixed $value 缓存值
     * @param int|null $ttl 缓存过期时间（秒）
     * @return bool 是否成功设置缓存
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (!$this->enabled) {
            return false;
        }
        $file = $this->getCacheFilePath($key);
        $ttl = $ttl ?? $this->config['ttl'];
        $data = [
            'content' => $value,
            'expire' => time() + $ttl
        ];
        return file_put_contents($file, serialize($data), LOCK_EX) !== false;
    }

    /**
     * 删除缓存项
     * 
     * @param string $key 缓存键
     * @return bool 是否成功删除缓存
     */
    public function delete(string $key): bool
    {
        if (!$this->enabled) {
            return false;
        }
        $file = $this->getCacheFilePath($key);
        if (file_exists($file)) {
            return unlink($file);
        }
        return false;
    }

    /**
     * 清空所有缓存
     * 
     * @return bool 是否成功清空缓存
     */
    public function clear(): bool
    {
        if (!$this->enabled) {
            return false;
        }
        $files = glob($this->config['path'] . '/' . $this->config['prefix'] . '*');
        $success = true;
        foreach ($files as $file) {
            if (is_file($file)) {
                $success = $success && unlink($file);
            }
        }
        return $success;
    }

    /**
     * 获取缓存文件路径
     * 
     * @param string $key 缓存键
     * @return string 缓存文件路径
     */
    private function getCacheFilePath(string $key): string
    {
        $safeKey = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);
        return $this->config['path'] . '/' . $this->config['prefix'] . md5($safeKey) . '.cache';
    }
}
