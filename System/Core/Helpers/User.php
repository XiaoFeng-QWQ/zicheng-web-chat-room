<?php

namespace ChatRoom\Core\Helpers;

use PDO;
use Exception;
use ChatRoom\Core\Database\SqlLite;

/**
 * 用户辅助类
 */
class User
{
    /**
     * 验证用户名
     *
     * @param string $username
     * @return bool
     */
    public function validateUsername($username)
    {
        // 检查用户名是否为空和长度是否在3到20字符之间
        if (empty($username) || strlen($username) < 3 || strlen($username) > 20) {
            return false;
        }

        // 检查用户名是否只包含字母数字和下划线
        return preg_match('/^[a-zA-Z0-9_]+$/', $username);
    }

    /**
     * 获取用户信息，返回用户信息数组
     *
     * @param [type] $username 默认通过用户名查询
     * @param [type] $user_id 如果传入，使用用户id查询
     * @return array
     */
    public function getUserInfo($username, $user_id = null)
    {
        $db = SqlLite::getInstance()->getConnection();
        try {
            if ($user_id !== null) {
                // 通过用户ID查询
                $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
                $stmt->execute([$user_id]);
            } else {
                // 通过用户名查询
                $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->execute([$username]);
            }
            $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($userInfo) {
                // 如果找到了用户信息，则返回它
                return $userInfo;
            } else {
                // 如果没有找到用户信息
                return [];
            }
        } catch (Exception $e) {
            // 我抛出了一个错误
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 检查用户名是否已被使用
     * @param string $username
     * @return bool
     */
    public function isUsernameTaken($username)
    {
        // 获取数据库连接
        $db = SqlLite::getInstance()->getConnection();
        // 预处理语句，防止SQL注入
        $stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
        $stmt->execute([$username]);
        // 获取查询结果的行数
        $count = $stmt->fetchColumn();
        // 返回是否有相同用户名存在
        return $count > 0;
    }
}
