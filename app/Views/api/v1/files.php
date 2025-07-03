<?php

use ChatRoom\Core\Config\Chat;
use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Modules\FileUploader;

class FileAPI
{
    private $chatConfig;
    private $userHelpers;
    private $fileUploader;
    private $helpers;

    public function __construct($helpers)
    {
        $this->chatConfig = new Chat();
        $this->userHelpers = new User();
        $this->fileUploader = new FileUploader(
            $this->chatConfig->uploadFile["allowTypes"],
            $this->chatConfig->uploadFile["maxSize"]
        );
        $this->helpers = $helpers;
    }

    public function handleRequest()
    {
        $uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
        $uriParts = explode("/", trim($uri, "/"));
        $uuid = $uriParts[3] ?? "";

        if (!$this->validateUuid($uuid)) {
            $this->helpers->jsonResponse(400, "Invalid API method");
            return;
        }

        $userInfo = $this->userHelpers->getUserInfoByEnv();
        if (in_array($uuid, ['all', 'upload', 'delete']) && !$userInfo) {
            $this->helpers->jsonResponse(401, "未登录或登录已过期");
            return;
        }

        switch ($uuid) {
            case 'all':
                $this->handleFileList();
                break;
            case 'upload':
                $this->handleFileUpload($userInfo);
                break;
            case 'delete':
                $this->handleFileDelete($userInfo, $uriParts);
                break;
            default:
                $this->handleFileDownload($uuid);
        }
    }

    private function validateUuid($uuid)
    {
        return preg_match('/^[a-zA-Z0-9]{1,256}$/', $uuid);
    }

    private function handleFileList()
    {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 10;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'created_at';

        $result = $this->fileUploader->manageFile("search", $page, $perPage, $search, $sort);

        // 移除敏感信息
        array_walk($result["files"], function (&$file) {
            unset($file["file_path"]);
        });

        $this->helpers->jsonResponse(200, true, $result);
    }

    private function handleFileUpload($userInfo)
    {
        if (!isset($_FILES["file"]) || $_FILES["file"]["error"] !== UPLOAD_ERR_OK) {
            $this->helpers->jsonResponse(406, "上传失败 - 未传递文件");
            return;
        }

        // 先判断文件是否存在
        $file = $this->fileUploader->upload($_FILES["file"], $userInfo["user_id"]);
        if ($file === false) {
            $this->helpers->jsonResponse(406, "文件上传失败或此文件类型不允许");
            return;
        }

        unset($file["path"]);
        $this->helpers->jsonResponse(200, true, $file);
    }

    private function handleFileDelete($userInfo, $uriParts)
    {
        $fileId = (int)($uriParts[4] ?? 0);

        if ($fileId <= 0) {
            $this->helpers->jsonResponse(400, "无效的文件ID");
            return;
        }

        $fileInfo = $this->fileUploader->manageFile("search", "id", $fileId);

        // 验证权限：管理员或文件所有者
        if (!$this->hasDeletePermission($userInfo, $fileInfo)) {
            $this->helpers->jsonResponse(403, "没有权限删除");
            return;
        }

        $result = $this->fileUploader->deleteFile($fileId, 'id');
        if ($result) {
            $this->helpers->jsonResponse(200, true, ['message' => '文件删除成功']);
        } else {
            $this->helpers->jsonResponse(500, "文件删除失败");
        }
    }

    private function hasDeletePermission($userInfo, $fileInfo)
    {
        return $userInfo['group_id'] === 1 ||
            $userInfo['user_id'] === ($fileInfo[0]['user_id'] ?? null);
    }

    private function handleFileDownload($uuid)
    {
        $fileInfo = $this->fileUploader->manageFile("search", "file_md5", $uuid);

        if (empty($fileInfo) || !$this->isFileAccessible($fileInfo["file_path"])) {
            http_response_code(404);
            exit;
        }

        $this->sendFileHeaders($fileInfo);
        $this->outputFile($fileInfo["file_path"]);
    }

    private function isFileAccessible($filePath)
    {
        return is_file($filePath) && is_readable($filePath);
    }

    private function sendFileHeaders($fileInfo)
    {
        $headers = [
            "Content-Description: File Transfer",
            "Content-Type: " . $fileInfo["file_type"],
            "Content-Length: " . filesize($fileInfo["file_path"]),
            "Content-Disposition: attachment; filename=\"" . basename($fileInfo["file_name"]) . "\"",
            "Cache-Control: public, max-age=86400",
            "Expires: 0",
            "Pragma: public"
        ];

        foreach ($headers as $header) {
            header($header);
        }
    }

    private function outputFile($filePath, $chunkSize = 8192)
    {
        if (ob_get_level()) {
            ob_end_clean();
        }

        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            http_response_code(500);
            exit;
        }

        while (!feof($handle)) {
            echo fread($handle, $chunkSize);
            flush();
        }

        fclose($handle);
        exit;
    }
}

// 实例化并处理请求
(new FileAPI($helpers))->handleRequest();