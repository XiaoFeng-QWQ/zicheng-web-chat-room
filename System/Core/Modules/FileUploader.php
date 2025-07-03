<?php

namespace ChatRoom\Core\Modules;

use PDO;
use Exception;
use Throwable;
use PDOException;
use ChatRoom\Core\Database\Base;

class FileUploader
{
    private $allowedTypes;
    private $maxSize;
    private $uploadDir;
    private $db;

    public function __construct($allowedTypes, $maxSize)
    {
        $this->db = Base::getInstance()->getConnection();
        $this->allowedTypes = $allowedTypes;
        $this->maxSize = $maxSize;
        $this->uploadDir = FRAMEWORK_DIR . "/Writable/uploads";
    }

    /**
     * 上传文件并返回相关信息
     *
     * @param array $file $_FILES
     * @param int $userId 用户ID
     * @return array|false 文件上传成功时返回文件信息，失败时返回 false
     * @throws Throwable 如果数据库操作失败，将抛出异常
     */
    public function upload($file, $userId): array|bool
    {
        try {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                return false;
            }
            $fileType = mime_content_type($file['tmp_name']);
            if (!in_array($fileType, $this->allowedTypes)) {
                return false;
            }
            if ($file['size'] > $this->maxSize) {
                return false;
            }
            // 构建上传目录
            $uploadPath = $this->uploadDir . "/u_$userId";
            if (!is_dir($uploadPath) && !mkdir($uploadPath, 0775, true)) {
                return false;
            }
            $md5 = md5_file($file['tmp_name']);
            // 检查文件是否已存在
            $fileCheck = $this->manageFile('search', 'file_md5', $md5);
            if ($fileCheck) {
                return [
                    'name' => $fileCheck['file_name'],
                    'type' => $fileCheck['file_type'],
                    'size' => round($fileCheck['file_size'] / 1024, 2) . 'KB',
                    'path' => $fileCheck['file_path'], //实际存储路径(完整路径)
                    'md5' => $md5,
                    'created_at' => $fileCheck['created_at'],
                    'updated_at' => $fileCheck['updated_at'],
                    'user_id' => $fileCheck['user_id'],
                    'status' => $fileCheck['status']
                ];
            }
            $filePath = $uploadPath . '/' . $md5 . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                return false;
            }
            $fileInfo = [
                'name' => $file['name'],
                'type' => $file['type'],
                'size' => round($file['size'] / 1024, 2),
                'path' => $filePath, //实际存储路径(完整路径)
                'url' => "/api/v1/files/$md5",
                'md5' => $md5,
            ];
            if (!$this->saveFileInfo($userId, $fileInfo)) {
                return false;
            }
            return $fileInfo;
        } catch (Throwable $e) {
            throw new Exception('文件保存失败:' . $e->getMessage());
        }
    }

    /**
     * 将文件信息插入数据库
     *
     * @param int $userId 用户ID
     * @param array $fileData 文件信息
     * @return bool 返回是否成功
     * @throws PDOException 如果数据库操作失败，将抛出异常
     */
    private function saveFileInfo($userId, $fileData): bool
    {
        try {
            $query = "INSERT INTO files (file_name, file_type, file_size, file_path, file_md5, created_at, user_id) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $fileData['name'],
                $fileData['type'],
                $fileData['size'],
                $fileData['path'],
                $fileData['md5'],
                date('Y-m-d H:i:s'),
                $userId,
            ];

            $stmt = $this->db->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            throw new PDOException('文件信息插入数据库出错:' . $e->getMessage());
        }
    }

    /**
     * 文件管理
     * 
     * @param string $method 操作类型，支持 "search" 和 "delete"。
     *                       - "search" 用于搜索文件，可以提供条件进行筛选；
     *                       - "delete" 用于删除指定文件。
     * @param mixed ...$options 可选的参数，取决于操作类型：
     *                        - 如果 $method 是 "search"，第一个选项应为搜索条件（如文件名、文件类型等）。
     *                        - 如果 $method 是 "delete"，第一个选项应为文件的 UUID（唯一标识符）。
     * 
     * @return array|bool 如果操作是 "search"，返回符合条件的文件列表（数组形式）； 
     *                     如果操作是 "delete"，返回删除是否成功（布尔值）。
     *                     如果 $method 不支持或没有提供正确的参数，返回 false。
     */
    public function manageFile($method, ...$options): array|bool
    {
        switch ($method) {
            case "search":
                if (count($options) === 4) {
                    // 分页搜索
                    return $this->getPaginatedFiles($options[0], $options[1], $options[2], $options[3]);
                } elseif (count($options) === 2) {
                    // 条件搜索
                    return $this->searchFiles($options);
                } else {
                    // 获取所有文件
                    return $this->getAllFiles();
                }
                break;
            case "delete":
                if (isset($options[0])) {
                    // 默认使用MD5删除
                    $type = isset($options[1]) && $options[1] === 'id' ? 'id' : 'md5';
                    return $this->deleteFile($options[0], $type);
                }
                break;
        }
        return false;
    }

    /**
     * 获取分页文件列表
     * 
     * @param int $page 当前页码
     * @param int $perPage 每页数量
     * @param string $search 搜索关键词
     * @param string $sort 排序字段
     * @return array 文件列表和总数
     */
    public function getPaginatedFiles($page = 1, $perPage = 10, $search = '', $sort = 'created_at'): array
    {
        try {
            // 验证排序字段
            $allowedSortFields = ['file_name', 'file_type', 'file_size', 'created_at', 'username'];
            $sortField = in_array($sort, $allowedSortFields) ? $sort : 'created_at';
            $orderBy = "ORDER BY $sortField DESC";

            // 计算偏移量
            $offset = ($page - 1) * $perPage;

            // 基础查询
            $query = "SELECT f.*, u.username, u.avatar_url 
                  FROM files f 
                  LEFT JOIN users u ON f.user_id = u.user_id 
                  WHERE f.status = 'active'";

            // 添加搜索条件
            $params = [];
            if (!empty($search)) {
                $query .= " AND (f.file_name LIKE :search OR u.username LIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }

            // 添加排序和分页
            $query .= " $orderBy LIMIT :limit OFFSET :offset";

            // 准备并执行查询
            $stmt = $this->db->prepare($query);

            // 绑定参数
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            $stmt->execute();
            $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 获取总数
            $countQuery = "SELECT COUNT(*) as total 
                       FROM files f 
                       LEFT JOIN users u ON f.user_id = u.user_id 
                       WHERE f.status = 'active'";

            if (!empty($search)) {
                $countQuery .= " AND (f.file_name LIKE :search OR u.username LIKE :search)";
            }

            $countStmt = $this->db->prepare($countQuery);
            foreach ($params as $key => $value) {
                if ($key !== ':limit' && $key !== ':offset') {
                    $countStmt->bindValue($key, $value);
                }
            }
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            return [
                'total' => $total,
                'files' => $files
            ];
        } catch (PDOException $e) {
            throw new PDOException("获取分页文件列表失败:" . $e->getMessage());
        }
    }

    /**
     * 获取所有文件并返回所有文件信息
     * 
     * @return array
     * @throws PDOException 如果数据库操作失败，将抛出异常
     */
    private function getAllFiles(): array
    {
        try {
            // 查询文件及对应用户的用户名和头像
            $query = "SELECT f.*, u.username, u.avatar_url 
                      FROM files f 
                      LEFT JOIN users u ON f.user_id = u.user_id 
                      WHERE f.status = 'active'";
            $stmt = $this->db->query($query);
            $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $countQuery = "SELECT COUNT(*) as total FROM files WHERE status = 'active'";
            $totalStmt = $this->db->query($countQuery);
            $total = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];

            return [
                'total' => $total, // 返回总数
                'files' => $files // 返回消息数组
            ];
        } catch (PDOException $e) {
            throw new PDOException("获取文件列表失败:" . $e->getMessage());
        }
    }

    /**
     * 根据条件搜索文件
     *
     * @param array $condition 搜索条件，可以是文件名、文件类型等
     * @return array 返回符合条件的文件列表
     * @throws PDOException 如果数据库操作失败，将抛出异常
     */
    private function searchFiles($condition): array
    {
        try {
            // 执行查询
            $sql = "SELECT * FROM files WHERE $condition[0] = :value AND status = 'active'";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':value', $condition[1]);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // 如果只查找一个文件（如通过md5），返回第一个，否则返回全部
            if ($condition[0] === 'file_md5') {
                return $result[0] ?? [];
            }
            return $result;
        } catch (PDOException $e) {
            throw new PDOException("搜索文件失败: " . $e->getMessage());
        }
    }

    /**
     * 删除文件（支持通过文件ID或MD5删除）
     *
     * @param mixed $identifier 文件ID(int)或文件MD5(string)
     * @param string $type 标识类型 ('id' 或 'md5')
     * @return bool 返回删除是否成功
     * @throws PDOException 如果数据库操作失败，将抛出异常
     */
    public function deleteFile($identifier, string $type = 'md5'): bool
    {
        try {
            // 根据类型确定查询条件
            $column = $type === 'id' ? 'id' : 'file_md5';

            // 获取文件路径
            $query = "SELECT file_path FROM files WHERE $column = ? AND status = 'active'";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$identifier]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$file) {
                return false;
            }

            // 更新文件状态为已删除（软删除）
            $updateQuery = "UPDATE files SET status = 'deleted' WHERE $column = ?";
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->execute([$identifier]);

            return true;
        } catch (PDOException $e) {
            throw new PDOException("删除文件失败:" . $e->getMessage());
        }
    }
}
