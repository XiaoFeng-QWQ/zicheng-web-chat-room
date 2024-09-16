<?php

namespace ChatRoom\Core\Controller;

use PDO;
use Exception;
use PDOException;
use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Helpers\Helpers;
use ChatRoom\Core\Database\SqlLite;

class ChatController
{
    private $user_helpers;
    public $Helpers;

    // 状态消息常量
    const STATUS_SUCCESS = 'success';
    const STATUS_WARNING = 'warning';
    const STATUS_ERROR = 'error';
    const MESSAGE_EMPTY = '消息内容不能为空';
    const MESSAGE_SUCCESS = '发送成功';
    const MESSAGE_NOT_LOGGED_IN = '用户未登录';
    const MESSAGE_SEND_FAILED = '发送消息失败';
    const MESSAGE_FETCH_FAILED = '获取消息失败';
    const MESSAGE_INVALID_REQUEST = '无效请求';

    public function __construct()
    {
        $this->user_helpers = new User;
        $this->Helpers = new Helpers;
    }

    /**
     * 发送消息
     *
     * @param array $user 用户信息数组
     * @param string $message 发送的消息内容
     * @return bool
     */
    public function sendMessage($user, $message)
    {
        try {
            $db = SqlLite::getInstance()->getConnection();
            $stmt = $db->prepare('INSERT INTO messages (user_name, content, type, created_at, user_ip) VALUES (?, ?, ?, ?, ?)');
            return $stmt->execute([$user['username'], $message, 'user', date('Y-m-d H:i:s'), $this->user_helpers->getIp()]);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 获取消息
     *
     * @param int $offset 偏移量
     * @param int $limit 限制条数
     * @return array|bool
     */
    public function getMessages($offset = 0, $limit = 10)
    {
        try {
            $db = SqlLite::getInstance()->getConnection();

            // 查询总消息数
            $countQuery = 'SELECT COUNT(*) as total FROM messages';
            $totalStmt = $db->query($countQuery);
            $totalMessages = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];

            $query = '
            SELECT 
                messages.id,
                messages.type,
                messages.content,
                messages.user_name,
                users.group_id AS user_group_id,
                messages.created_at AS created_at,
                users.avatar_url,
                groups.group_name
            FROM messages
            LEFT JOIN users ON messages.user_name = users.username
            LEFT JOIN groups ON users.group_id = groups.group_id
            ORDER BY messages.created_at ASC LIMIT :limit OFFSET :offset';

            $stmt = $db->prepare($query);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

            $stmt->execute();
            return [
                'total' => $totalMessages, // 返回总数
                'messages' => $stmt->fetchAll(PDO::FETCH_ASSOC) // 返回消息数组
            ];
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getAllMessages()
    {
        try {
            $db = SqlLite::getInstance()->getConnection();

            // 查询总消息数
            $countQuery = 'SELECT COUNT(*) as total FROM messages';
            $totalStmt = $db->query($countQuery);
            $totalMessages = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // 查询消息
            $query = '
            SELECT 
                messages.id,
                messages.type,
                messages.content,
                messages.user_name,
                users.group_id AS user_group_id,
                messages.created_at AS created_at,
                users.avatar_url,
                groups.group_name
            FROM messages
            LEFT JOIN users ON messages.user_name = users.username
            LEFT JOIN groups ON users.group_id = groups.group_id';

            $stmt = $db->query($query);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'total' => $totalMessages, // 返回总数
                'messages' => $messages // 返回消息数组
            ];
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
}
