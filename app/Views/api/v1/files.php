<?php

use ChatRoom\Core\Config\Chat;
use ChatRoom\Core\Modules\FileUploader;

$chatConfig = new Chat;
$file = new FileUploader($chatConfig->uploadFile['allowTypes'], $chatConfig->uploadFile['maxSize']);

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uuid = isset(explode('/', trim($uri, '/'))[3]) ? explode('/', trim($uri, '/'))[3] : '';

// 验证 API 名称是否符合字母和数字的格式，且长度不超过 256
if (preg_match('/^[a-zA-Z0-9]{1,256}$/', $uuid)) {
    if ($uuid === 'all') {
        // 直接返回所有文件列表
        $result = $file->manageFile('search');
        // 避免前端知道文件绝对路径
        foreach ($result['files'] as &$file) {
            unset($file['file_path']);
        }
        $helpers->jsonResponse(200, 'true', $result);
    }
    // 搜索文件元数据
    $fileInfo = $file->manageFile('search', ['file_uuid', $uuid]);
    // 如果文件存在
    if (!empty($fileInfo)) {
        $fileData = $fileInfo[0]; // 假设查询返回的是文件元数据数组，取第一个文件
        // 获取文件路径和类型
        $filePath = $fileData['file_path'];
        $fileName = $fileData['file_name'];
        $fileType = $fileData['file_type'];
        // 验证文件是否存在
        if (file_exists($filePath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $fileType);
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: no-cache, no-store, must-revalidate'); // 防止缓存
            header('Pragma: no-cache');
            header('Expires: 0');

            // 清空输出缓冲区并发送文件内容
            ob_clean();
            flush();
            readfile($filePath);
            exit;
        } else {
            http_response_code(404);
            echo "File not found.";
            exit;
        }
    } else {
        http_response_code(404);
        echo "File not found.";
        exit;
    }
} else {
    // 如果 method 不符合字母数字格式，返回 400 错误
    $helpers->jsonResponse(400, "Invalid API method");
}
