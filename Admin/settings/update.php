<form>
    <h2>检查更新</h2>
    <hr>
    <div id="update">

    </div>
    <?php
    $updateUrl = 'https://api.github.com/repos/XiaoFeng-QWQ/zicheng-web-chat-room/releases/latest';
    $ch = curl_init();
    // 配置 cURL 选项
    curl_setopt($ch, CURLOPT_URL, $updateUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: PHP-cURL-Request'
    ]);
    // 忽略 SSL 验证（测试用）
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    // 检查是否有错误
    if (curl_errno($ch)) {
        echo '<div id="update">
            <p>cURL 错误: ' . curl_error($ch) . '</p>
          </div>';
    } else {
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            echo '<div id="update">
                <p>最新版本: V' . htmlspecialchars($data['tag_name']) . '</p>
                <a href="' . htmlspecialchars($data['assets'][0]['browser_download_url']) . '" target="_blank" rel="noopener noreferrer">下载链接</a>
                <a href="' . htmlspecialchars($data['html_url']) . '" target="_blank" rel="noopener noreferrer">详情页</a>
                <p>更新日志:</p>
                <pre>' . htmlspecialchars($data['body']) . '</pre>
              </div>';
        } else {
            $errorMessage = json_decode($response, true)['message'] ?? '未知错误';
            echo '<div id="update">
                <p>检查更新失败: ' . htmlspecialchars($httpCode) . ' ' . htmlspecialchars($errorMessage) . '</p>
              </div>';
        }
    }
    curl_close($ch);
    ?>
</form>