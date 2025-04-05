<?php

namespace ChatRoom\Core\Controller;

use PDO;
use PDOException;
use ChatRoom\Core\Database\Base;

class Events
{
    private $db;

    public function __construct()
    {
        $this->db = Base::getInstance()->getConnection();
    }

    /**
     * 创建一个新的事件
     * 
     * @param string $eventType 事件类型，如 message.revoke | admin.announcement.publish | admin.message.highlight
     * @param int $user_id 触发事件的用户ID
     * @param int $targetId 目标ID（如消息ID、公告ID等）
     * @param mixed $additionalData 附加数据（JSON可编码格式）
     * @return bool 是否插入成功
     * @throws PDOException
     */
    public function createEvent(string $eventType, int $user_id, int $targetId, mixed $additionalData = null): bool
    {
        try {
            $sql = "INSERT INTO events 
                    (event_type, user_id, target_id, created_at, additional_data) 
                    VALUES (?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $eventType,
                $user_id,
                $targetId,
                date('Y-m-d H:i:s'),
                $additionalData
            ]);
        } catch (PDOException $e) {
            throw new PDOException('创建事件失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取分页的事件列表
     * 
     * @param int $offset 偏移量
     * @param int $limit 每页数量（默认10）
     * @return array 事件列表
     * @throws PDOException
     */
    public function getEvents(int $offset, int $limit = 10): array
    {
        try {
            $sql = "SELECT 
                        event_id,
                        event_type,
                        user_id,
                        target_id,
                        created_at,
                        additional_data 
                    FROM events 
                    ORDER BY event_id ASC
                    LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $events;
        } catch (PDOException $e) {
            throw new PDOException('获取事件列表失败: ' . $e->getMessage());
        }
    }

    /**
     * 根据条件查询指定类型的事件
     * 
     * @param array $conditions 查询条件 [
     *     'event_type' => string,      // 事件类型
     *     'user_id' => int,            // 用户ID
     *     'target_id' => int,          // 目标ID
     *     'start_date' => string,      // 开始日期 (Y-m-d H:i:s)
     *     'end_date' => string,        // 结束日期 (Y-m-d H:i:s)
     *     'limit' => int,              // 结果数量限制
     *     'order_by' => string,        // 排序字段
     *     'order_dir' => string        // 排序方向 (ASC/DESC)
     * ]
     * @return array 匹配的事件列表
     * @throws PDOException
     */
    public function getEventsByConditions(array $conditions = []): array
    {
        try {
            // 基础查询
            $sql = "SELECT 
                        event_id,
                        event_type,
                        user_id,
                        target_id,
                        created_at,
                        additional_data 
                    FROM events 
                    WHERE 1=1";

            // 参数绑定
            $params = [];

            // 添加事件类型条件
            if (!empty($conditions['event_type'])) {
                $sql .= " AND event_type = :event_type";
                $params[':event_type'] = $conditions['event_type'];
            }

            // 添加用户ID条件
            if (!empty($conditions['user_id'])) {
                $sql .= " AND user_id = :user_id";
                $params[':user_id'] = $conditions['user_id'];
            }

            // 添加目标ID条件
            if (!empty($conditions['target_id'])) {
                $sql .= " AND target_id = :target_id";
                $params[':target_id'] = $conditions['target_id'];
            }

            // 添加日期范围条件
            if (!empty($conditions['start_date'])) {
                $sql .= " AND created_at >= :start_date";
                $params[':start_date'] = $conditions['start_date'];
            }

            if (!empty($conditions['end_date'])) {
                $sql .= " AND created_at <= :end_date";
                $params[':end_date'] = $conditions['end_date'];
            }

            // 添加排序
            $orderBy = $conditions['order_by'] ?? 'event_id';
            $orderDir = strtoupper($conditions['order_dir'] ?? 'DESC');
            $orderDir = in_array($orderDir, ['ASC', 'DESC']) ? $orderDir : 'DESC';
            $sql .= " ORDER BY {$orderBy} {$orderDir}";

            // 添加数量限制
            if (!empty($conditions['limit'])) {
                $sql .= " LIMIT :limit";
                $params[':limit'] = (int)$conditions['limit'];
            }

            $stmt = $this->db->prepare($sql);

            // 绑定参数
            foreach ($params as $key => $value) {
                $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($key, $value, $paramType);
            }

            $stmt->execute();

            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $events;
        } catch (PDOException $e) {
            throw new PDOException('查询事件失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取特定类型的事件数量统计
     * 
     * @param string|null $eventType 事件类型（可选）
     * @return int 事件数量
     * @throws PDOException
     */
    public function getEventsCount(?string $eventType = null): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM events";

            if ($eventType !== null) {
                $sql .= " WHERE event_type = :event_type";
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':event_type', $eventType, PDO::PARAM_STR);
            } else {
                $stmt = $this->db->prepare($sql);
            }

            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int)($result['count'] ?? 0);
        } catch (PDOException $e) {
            throw new PDOException('获取事件数量失败: ' . $e->getMessage());
        }
    }
}
