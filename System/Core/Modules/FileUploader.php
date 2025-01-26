<?php

namespace ChatRoom\Core\Modules;

use PDO;
use Exception;
use Throwable;
use PDOException;
use ChatRoom\Core\Database\SqlLite;

class FileUploader
{
    private $allowedTypes;
    private $maxSize;
    private $uploadDir;

    public function __construct($allowedTypes, $maxSize)
    {
        $this->allowedTypes = $allowedTypes;
        $this->maxSize = $maxSize;
        $this->uploadDir = FRAMEWORK_DIR . "/StaticResources/uploads/";
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
            $uploadPath = $this->uploadDir . date('Y/m/d') . "/u_$userId/";
            if (!is_dir($uploadPath) && !mkdir($uploadPath, 0775, true)) {
                return false;
            }
            $filePath = $uploadPath . $file['name'];
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                return false;
            }
            $uuid = time() . $userId . uniqid();
            $fileInfo = [
                'name' => $file['name'],
                'type' => $file['type'],
                'size' => round($file['size'] / 1024, 2) . 'KB',
                'path' => $filePath, //存储实际路径
                'uuid' => $uuid,
            ];
            if (!$this->saveFileInfo($userId, $fileInfo)) {
                return false;
            }
            $fileInfo['url'] = "/api/v1/files/$uuid"; // 返回前端相对路径
            return $fileInfo;
        } catch (Throwable $e) {
            throw new Exception('文件保存失败:' . $e);
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
            $query = "INSERT INTO files (file_name, file_type, file_size, file_path, file_uuid, created_at, user_id) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $fileData['name'],
                $fileData['type'],
                $fileData['size'],
                $fileData['path'],
                $fileData['uuid'],
                date('Y-m-d H:i:s'),
                $userId,
            ];

            $db = SqlLite::getInstance()->getConnection();
            $stmt = $db->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            throw new PDOException('文件信息插入数据库出错:' . $e);
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
                if (empty($options)) {
                    return $this->getAllFiles();
                }
                if (count($options) > 0) {
                    $condition = $options[0]; //第一个选项是条件（例如按文件名、文件类型等）
                    return $this->searchFiles($condition);
                }
                break;
            case "delete":
                if (isset($options[0])) {
                    $fileUuid = $options[0]; // 第一个选项是文件UUID
                    return $this->deleteFile($fileUuid);
                }
                break;
            default:
                return false;
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
            $query = "SELECT * FROM files WHERE status = 'active'";
            $db = SqlLite::getInstance()->getConnection();
            $stmt = $db->query($query);
            $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $countQuery = "SELECT COUNT(*) as total FROM files WHERE status = 'active'";
            $totalStmt = $db->query($countQuery);
            $total = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];

            return [
                'total' => $total, // 返回总数
                'files' => $files // 返回消息数组
            ];
        } catch (PDOException $e) {
            throw new PDOException("获取文件列表失败:" . $e);
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
            if ($condition[1] === '1') {
                return [];
            }
            if ($condition[1] === '0') {
                return [];
            }

            // 执行查询
            $db = SqlLite::getInstance()->getConnection();
            $sql = "SELECT * FROM files WHERE $condition[0] = :value AND status = 'active'";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':value', $condition[1]);
            $stmt->execute();
            $results = $stmt->fetchAll();
            return $results;
        } catch (PDOException $e) {
            throw new PDOException("搜索文件失败: " . $e);
        }
    }

    /**
     * 删除文件
     *
     * @param string $fileUuid 文件的 UUID
     * @return bool 返回删除是否成功
     * @throws PDOException 如果数据库操作失败，将抛出异常
     */
    private function deleteFile($fileUuid): bool
    {
        try {
            // 获取文件路径
            $query = "SELECT file_path FROM files WHERE file_uuid = ? AND status = 'active'";
            $db = SqlLite::getInstance()->getConnection();
            $stmt = $db->prepare($query);
            $stmt->execute([$fileUuid]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$file) {
                return false;
            }

            // 更新文件状态
            $updateQuery = "UPDATE files SET status = 'deleted' WHERE file_uuid = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([$fileUuid]);

            return true;
        } catch (PDOException $e) {
            throw new PDOException("删除文件失败:" . $e);
        }
    }
}
