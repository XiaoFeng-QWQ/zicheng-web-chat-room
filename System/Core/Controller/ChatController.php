<?php

namespace ChatRoom\Core\Controller;

use PDO;
use Exception;
use Throwable;
use PDOException;
use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Database\Base;
use ChatRoom\Core\Helpers\Helpers;
use ChatRoom\Core\Controller\Events;
use ChatRoom\Core\Modules\TokenManager;

class ChatController
{
    public $Helpers;
    private $db;

    // 状态消息常量
    const MESSAGE_SUCCESS = true;
    const MESSAGE_SEND_FAILED = '发送消息失败';
    const MESSAGE_FETCH_FAILED = '获取消息失败';
    const MESSAGE_INVALID_REQUEST = '无效请求';
    const ONLINE_USERS_FILE = FRAMEWORK_DIR . '/Writable/chat.user.online.list.json';

    public function __construct()
    {
        $this->Helpers = new Helpers;
        $this->db = Base::getInstance()->getConnection();
    }

    /**
     * 发送消息（增加回复功能）
     *
     * @param array $user 用户信息数组
     * @param string $message 发送的消息内容
     * @param bool $isMarkdown 是否为MD语法
     * @param int|null $replyTo 回复的消息ID
     * @return bool
     */
    public function sendMessage($user, $message, $isMarkdown = false, $replyTo = null, $isNotice = false): bool
    {
        try {
            $type = 'user';
            $userIP = new User;
            $db = Base::getInstance()->getConnection();
            if ($isMarkdown === 'true') {
                $type = 'user.markdown';
            }
            if ($isNotice) {
                $type = 'admin.notice';
            }

            $stmt = $db->prepare('INSERT INTO messages 
                (user_name, content, type, created_at, user_ip, reply_to) 
                VALUES (?, ?, ?, ?, ?, ?)');

            return $stmt->execute([
                $user['username'],
                $message,
                $type,
                date('Y-m-d H:i:s'),
                $userIP->getIp(),
                $replyTo
            ]);
        } catch (PDOException $e) {
            throw new PDOException('发送消息发生错误:' . $e->getMessage());
        }
    }

    /**
     * 根据不同条件获取消息
     *
     * @param array $conditions 查询条件，包含消息ID、类型、用户、状态等
     * @return array|null 返回查询到的消息或 null
     */
    public function getMessagesByConditions(array $conditions): ?array
    {
        try {

            $query = 'SELECT 
                    messages.id,
                    messages.type,
                    messages.content,
                    messages.user_name,
                    messages.user_ip,
                    messages.created_at,
                    messages.status
                FROM messages';

            // 条件数组，用于存储查询的WHERE部分
            $whereClauses = [];
            $params = [];

            // 使用 switch 语句来动态构建 WHERE 子句
            foreach ($conditions as $key => $value) {
                switch ($key) {
                    case 'messageId':
                        $whereClauses[] = 'messages.id = :messageId';
                        $params[':messageId'] = $value;
                        break;
                    case 'type':
                        $whereClauses[] = 'messages.type = :type';
                        $params[':type'] = $value;
                        break;
                    case 'userName':
                        $whereClauses[] = 'messages.user_name = :userName';
                        $params[':userName'] = $value;
                        break;
                    case 'status':
                        $whereClauses[] = 'messages.status = :status';
                        $params[':status'] = $value;
                        break;
                    case 'createdAtStart':
                        $whereClauses[] = 'messages.created_at >= :createdAtStart';
                        $params[':createdAtStart'] = $value;
                        break;
                    case 'createdAtEnd':
                        $whereClauses[] = 'messages.created_at <= :createdAtEnd';
                        $params[':createdAtEnd'] = $value;
                        break;
                    default:
                        break;
                }
            }

            // 如果有条件，加入 WHERE 子句
            if (count($whereClauses) > 0) {
                $query .= ' WHERE ' . implode(' AND ', $whereClauses);
            }

            $stmt = $this->db->prepare($query);
            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value);
            }
            $stmt->execute();
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($messages) {
                return $messages;
            } else {
                return null; // 没有找到任何符合条件的消息
            }
        } catch (PDOException $e) {
            throw new PDOException('根据条件查询消息发生错误: ' . $e->getMessage());
        }
    }

    /**
     * 获取消息
     *
     * @param int $offset 偏移量
     * @param int $limit 限制条数
     * @param int $eventOffset 事件偏移量
     * @param int $eventLimit 事件限制条数
     * @return array
     */
    public function getMessages($offset = 0, $limit = 10, $eventOffset = 0, $eventLimit = 10): array
    {
        try {
            $event = new Events;
            $query =
                'SELECT 
                    m.id,
                    m.type,
                    m.content,
                    m.user_name,
                    u.group_id AS user_group_id,
                    m.created_at AS created_at,
                    u.avatar_url,
                    g.group_name,
                    m.status,
                    m.reply_to,
                    rm.user_name AS reply_user_name,
                    rm.content AS reply_content
                FROM messages m
                LEFT JOIN users u ON m.user_name = u.username
                LEFT JOIN groups g ON u.group_id = g.group_id
                LEFT JOIN messages rm ON m.reply_to = rm.id
                ORDER BY m.id ASC
                LIMIT :limit OFFSET :offset';

            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();

            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->updataOnlineUsers();

            foreach ($messages as &$message) {
                if ($message['type'] === 'event.delete') {
                    $eventConditions = [
                        'target_id' => $message['id'],
                    ];
                    $events = $event->getEventsByConditions($eventConditions);
                    if ($events) {
                        $message['content'] = $events[0]['additional_data'];
                    }
                }

                // 添加回复消息数据
                if ($message['reply_to']) {
                    $message['reply'] = [
                        'id' => $message['reply_to'],
                        'user_name' => $message['reply_user_name'],
                        'content' => $message['reply_content']
                    ];
                }
                unset($message['reply_user_name'], $message['reply_content']);
            }

            return [
                'onlineUsers' => $this->getOnlineUsers(),
                'messages' => $messages,
                'events' => $event->getEvents($eventOffset, $eventLimit),
            ];
        } catch (PDOException $e) {
            throw new PDOException('获取消息发送错误:' . $e->getMessage());
        }
    }

    /**
     * 获取消息总数
     *
     * @return array
     */
    public function getMessageCount(): array
    {
        try {

            $countQuery = 'SELECT COUNT(*) as total FROM messages';
            $totalStmt = $this->db->query($countQuery);
            $totalMessages = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
            return ['total' => $totalMessages];
        } catch (PDOException $e) {
            throw new PDOException('获取消息总数发生错误:' . $e->getMessage());
        }
    }

    /**
     * 插入系统消息到聊天记录
     *
     * @param string $user_name
     * @param string $message
     * @param string $type
     * @return bool
     */
    public function insertSystemMessage($user_name, $message, $type): bool
    {
        try {
            $userIP = new User;

            $stmt = $this->db->prepare('INSERT INTO messages (user_name, content, type, created_at, user_ip) VALUES (?, ?, ?, ?, ?)');
            return $stmt->execute([$user_name, $message, $type, date('Y-m-d H:i:s'), $userIP->getIp()]);
        } catch (PDOException $e) {
            throw new PDOException('插入系统消息发生错误:' .  $e->getMessage());
        }
    }

    /**
     * 回收消息(删除、撤回)
     *
     * @param int $messageId
     * @return bool 返回操作是否成功
     */
    public function recycleMessage($messageId): bool
    {
        try {
            $sqlUpdate = "UPDATE messages SET status = :status, type = :type WHERE id = :id";
            $stmtUpdate = $this->db->prepare($sqlUpdate);
            $stmtUpdate->bindValue(':status', 'delete', PDO::PARAM_STR);
            $stmtUpdate->bindValue(':type', 'event.delete', PDO::PARAM_STR);
            $stmtUpdate->bindParam(':id', $messageId, PDO::PARAM_INT);
            $result = $stmtUpdate->execute();

            return $result;
        } catch (PDOException $e) {
            throw new PDOException('回收消息失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取当前所有在线用户
     *
     * @return array 在线用户数据
     */
    private function getOnlineUsers(): array
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
     * @return int|false
     */
    private function updataOnlineUsers(): int|false
    {
        try {
            $userHelpers = new User;
            $tokenManager = new TokenManager;
            $userCookieInfo = json_decode($_COOKIE['user_login_info'] ?? '', true);
            $token = !empty($userCookieInfo['token']) ? $userCookieInfo['token'] : ($_POST['token'] ?? null);
            $tokenInfo = $token ? $tokenManager->getInfo($token) : null;
            $userInfo = $userHelpers->getUserInfo(null, $tokenInfo['user_id']);
            // 获取在线用户列表并更新
            $onlineUsers = $this->getOnlineUsers();
            $onlineUsers[$userInfo['user_id']] = [
                'user_name' => $userInfo['username'],
                'avatar_url' => $userInfo['avatar_url'],
                'last_time' => time()
            ];
            return file_put_contents(self::ONLINE_USERS_FILE, json_encode($onlineUsers, JSON_UNESCAPED_UNICODE));
        } catch (Throwable $e) {
            throw new Exception('更新在线用户列表失败:' . $e->getMessage());
        }
    }
}
