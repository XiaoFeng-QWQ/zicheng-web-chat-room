<?php

namespace ChatRoom\Core\Controller;

use PDO;
use Exception;
use PDOException;
use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Helpers\Helpers;
use ChatRoom\Core\Database\SqlLite;
use ChatRoom\Core\Modules\TokenManager;

class ChatController
{
    public $Helpers;

    // 状态消息常量
    const MESSAGE_SUCCESS = 'true';
    const MESSAGE_SEND_FAILED = '发送消息失败';
    const MESSAGE_FETCH_FAILED = '获取消息失败';
    const MESSAGE_INVALID_REQUEST = '无效请求';
    const ONLINE_USERS_FILE = FRAMEWORK_DIR . '/Writable/chat.user.online.list.json';

    public function __construct()
    {
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
            $userIP = new User;
            $db = SqlLite::getInstance()->getConnection();
            $stmt = $db->prepare('INSERT INTO messages (user_name, content, type, created_at, user_ip) VALUES (?, ?, ?, ?, ?)');
            return $stmt->execute([$user['username'], $message, 'user', date('Y-m-d H:i:s'), $userIP->getIp()]);
        } catch (Exception $e) {
            throw new ('发送消息发生错误:' . $e);
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

            $query = 'SELECT 
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

            $this->updataOnlineUsers();

            return [
                'total' => $totalMessages, // 返回总数
                'onlineUsers' => $this->getOnlineUsers(),
                'messages' => $stmt->fetchAll(PDO::FETCH_ASSOC) // 返回消息数组
            ];
        } catch (PDOException $e) {
            throw new ('获取消息发送错误:' . $e);
        }
    }

    /**
     * 获取全部消息
     *
     * @return array
     */
    public function getAllMessages()
    {
        try {
            $db = SqlLite::getInstance()->getConnection();

            // 查询总消息数
            $countQuery = 'SELECT COUNT(*) as total FROM messages';
            $totalStmt = $db->query($countQuery);
            $totalMessages = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // 查询消息
            $query = 'SELECT 
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

            $this->updataOnlineUsers();

            return [
                'total' => $totalMessages, // 返回总数
                'onlineUsers' => $this->getOnlineUsers(),
                'messages' => $messages // 返回消息数组
            ];
        } catch (PDOException $e) {
            throw new ('获取全部消息发送错误:' . $e);
        }
    }
    /**
     * 插入系统消息到聊天记录
     *
     * @param string $user_name
     * @param string $message
     * @param string $type
     * @return void
     */
    public function insertSystemMessage($user_name, $message, $type)
    {
        try {
            $db = SqlLite::getInstance()->getConnection();
            $stmt = $db->prepare('INSERT INTO messages (user_name, content, type, created_at) VALUES (?, ?, ?, ?)');
            $stmt->execute([$user_name, $message, $type, date('Y-m-d H:i:s')]);
        } catch (PDOException $e) {
            throw new ('插入系统消息发生错误:' .  $e);
        }
    }

    /**
     * 获取当前所有在线用户
     *
     * @return array 在线用户数据
     */
    private function getOnlineUsers()
    {
        // 读取 JSON 文件中的在线用户数据
        if (file_exists(self::ONLINE_USERS_FILE)) {
            $data = file_get_contents(self::ONLINE_USERS_FILE);
            $onlineUsers = json_decode($data, true);
            return $onlineUsers ?? [];
        }
        return [];
    }

    /**
     * 更新在线用户数据
     *
     * @return void
     */
    private function updataOnlineUsers(){
        $userHelpers = new User;
        $tokenManager = new TokenManager;
        $userCookieInfo = json_decode($_COOKIE['user_login_info'], true);
        $tokenInfo = $tokenManager->getInfo($userCookieInfo['token']);
        $userInfo = $userHelpers->getUserInfo(null, $tokenInfo['user_id']);
        $onlineUsers = $this->getOnlineUsers();

        $onlineUsers[$userInfo['user_id']] = [
            'user_name' => $userInfo['username'],
            'avatar_url' => $userInfo['avatar_url'],
            'last_time' => time()
        ];
        file_put_contents(self::ONLINE_USERS_FILE, json_encode($onlineUsers, JSON_UNESCAPED_UNICODE));
    }
}
