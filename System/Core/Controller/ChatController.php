<?php

namespace ChatRoom\Core\Controller;

use PDO;
use Exception;
use Throwable;
use PDOException;
use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Helpers\Helpers;
use ChatRoom\Core\Database\SqlLite;
use ChatRoom\Core\Modules\TokenManager;

class ChatController
{
    public $Helpers;

    // 状态消息常量
    const MESSAGE_SUCCESS = true;
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
     * @param bool $isMarkdown 是否为MD语法
     * @return bool
     */
    public function sendMessage($user, $message, $isMarkdown = false): bool
    {
        try {
            $userIP = new User;
            $db = SqlLite::getInstance()->getConnection();
            if ($isMarkdown === 'true') {
                $type = 'user.markdown';
            } else {
                $type = 'user';
            }
            $stmt = $db->prepare('INSERT INTO messages (user_name, content, type, created_at, user_ip) VALUES (?, ?, ?, ?, ?)');
            return $stmt->execute([$user['username'], $message, $type, date('Y-m-d H:i:s'), $userIP->getIp()]);
        } catch (PDOException $e) {
            throw new PDOException('发送消息发生错误:' . $e);
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
            $db = SqlLite::getInstance()->getConnection();
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

            $stmt = $db->prepare($query);
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
     * @param int $offset 偏移量 (当前页数 - 1) * 每页条数
     * @param int $limit 限制条数
     * @return array
     */
    public function getMessages($offset = 0, $limit = 10): array
    {
        try {
            $db = SqlLite::getInstance()->getConnection();
            $query =
                'SELECT 
                    messages.id,
                    messages.type,
                    messages.content,
                    messages.user_name,
                    users.group_id AS user_group_id,
                    messages.created_at AS created_at,
                    users.avatar_url,
                    groups.group_name,
                    messages.status
                FROM messages
                LEFT JOIN users ON messages.user_name = users.username
                LEFT JOIN groups ON users.group_id = groups.group_id
                WHERE messages.status = "active"
                ORDER BY messages.id ASC
                LIMIT :limit OFFSET :offset';

            $stmt = $db->prepare($query);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

            $stmt->execute();

            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->updataOnlineUsers();

            return [
                'onlineUsers' => $this->getOnlineUsers(),
                'messages' => $messages
            ];
        } catch (PDOException $e) {
            throw new PDOException('获取消息发送错误:' . $e);
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
            $db = SqlLite::getInstance()->getConnection();
            $countQuery = 'SELECT COUNT(*) as total FROM messages';
            $totalStmt = $db->query($countQuery);
            $totalMessages = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
            return ['total' => $totalMessages];
        } catch (PDOException $e) {
            throw new PDOException('获取消息总数发生错误:' . $e);
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
            $db = SqlLite::getInstance()->getConnection();
            $stmt = $db->prepare('INSERT INTO messages (user_name, content, type, created_at, user_ip) VALUES (?, ?, ?, ?, ?)');
            return $stmt->execute([$user_name, $message, $type, date('Y-m-d H:i:s'), $userIP->getIp()]);
        } catch (PDOException $e) {
            throw new PDOException('插入系统消息发生错误:' .  $e);
        }
    }

    /**
     * 回收消息(删除、撤回)
     *
     * @param int $messageId
     * @return bool 返回操作是否成功
     */
    public function recycleMessage($messageId, $tips): bool
    {
        try {
            $db = SqlLite::getInstance()->getConnection();
            // 更新消息为已删除状态
            $sqlUpdate = "UPDATE messages SET status = :delete WHERE id = :id";
            $stmtUpdate = $db->prepare($sqlUpdate);
            $stmtUpdate->bindValue(':delete', 'delete', PDO::PARAM_STR);
            $stmtUpdate->bindParam(':id', $messageId, PDO::PARAM_INT);
            $result = $stmtUpdate->execute();

            // 插入系统消息
            if ($result) {
                $this->insertSystemMessage('system', $tips, 'event.delete');
            }

            return $result;
        } catch (PDOException $e) {
            throw new PDOException('回收消息失败: ' . $e);
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
            throw new Exception('更新在线用户列表失败:' . $e);
        }
    }
}
