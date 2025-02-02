<?php

namespace ChatRoom\Core\Controller;

use PDO;
use PDOException;
use ChatRoom\Core\Database\SqlLite;

class Events
{
    private $db;
    public function __construct()
    {
        $this->db = SqlLite::getInstance()->getConnection(); // 获取数据库连接
    }

    /**
     * 创建一个新的事件
     * 
     * @param string $eventType 事件类型，如 message.revoke | admin.announcement.publish | admin.message.highlight。
     * @param int $user_id 用户ID，表示哪个用户触发了此事件。
     * @param int $targetId 目标ID，通常是受影响的对象的ID（例如消息ID、公告ID等）。
     * @param mixed $additionalData 附加数据，视具体事件类型可能包含额外的信息，例如公告内容等。
     * 
     * @return bool 返回是否插入成功。
     */
    public function createEvent(string $eventType, int $user_id, int $targetId, mixed $additionalData = null): bool
    {
        try {
            $sql = "INSERT INTO events (event_type, user_id, target_id, created_at, additional_data) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$eventType, $user_id, $targetId, date('Y-m-d H:i:s'), $additionalData]);
        } catch (PDOException $e) {
            throw new PDOException('创建事件发生错误:' . $e->getMessage());
        }
    }

    /**
     * 获取分页的事件列表，支持手动传递偏移量
     * 
     * @param int $offset 手动传递的偏移量。可选，默认为 null。
     * @param int $limit 每页的事件数量。可选，默认为 10。
     * @return array 返回事件列表（包含事件的详细信息）。
     */
    public function getEvents(int $offset, int $limit = 10): array
    {
        try {
            $sql = "SELECT * FROM events ORDER BY event_id ASC LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $events ?? [];
        } catch (PDOException $e) {
            throw new PDOException('获取事件列表发生错误: ' . $e->getMessage());
        }
    }
}
