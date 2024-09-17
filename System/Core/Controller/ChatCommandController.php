<?php

namespace ChatRoom\Core\Controller;

use ChatRoom\Core\Config\Chat;

/**
 * 聊天指令控制器
 */
class ChatCommandController
{
    private $chatConfig;
    private $chatCommandList;

    public function __construct()
    {
        $this->chatConfig = new Chat;
        $this->chatCommandList  = $this->chatConfig->chatCommandList;
    }
    //////////////////////////////////////////////////////////////////////////
    //|                                                                    |//
    //|                                                                    |//
    //////////////////////////////////////////////////////////////////////////
    public function 帮助()
    {
        $output = '<hr>';
        foreach ($this->chatCommandList  as $command => $details) {
            $output .= "<strong>{$command}</strong> " . implode(", ", $details['notes']) . '<br>';
        }
        return $output;
    }

    public function 全员禁言($reason = 'no reason specified')
    {
        // 实现禁止所有人发言的逻辑
        return "所有用户已被禁言，原因: $reason";
    }

    public function 重载()
    {
        // 实现重新加载所有数据的逻辑
        return "
        <script>
            offset = 0
            $('#chat-box').html('');
            loadChatMessages();
        </script>";
    }

    public function 随机图片($type = 'ycy')
    {
        $url = "https://t.alcy.cc/{$type}?json&rand=" . rand();
        $rawData = file_get_contents($url);
        // 检查是否成功获取到数据
        if ($rawData === false) {
            return '无法获取图片数据';
        }
        // 解码 JSON 数据
        $data = json_decode($rawData, true);
        // 检查解码是否成功
        if (json_last_error() !== JSON_ERROR_NONE) {
            return '无法解码图片数据';
        }
        // 检查是否包含 'url' 键
        if (!isset($data['url'])) {
            return '图片 URL 不存在';
        }
        $imgUrl = $data['url'];
        return "
        <hr>
        <a href='$imgUrl' data-fancybox>
            <img src='$imgUrl'>
        </a>";
    }

    public function ai对话($msg) {}

    public function 调试()
    {
        return '
        <hr>
        <div id="debug-info" class="debug-info">
            <div class="debug-info-header">
                <h5>调试信息</h5>
            </div>
            <div style="padding: 15px; text-align: justify;" id="debug-content">
                <!-- 这里是动态更新内容的容器 -->
                <strong>服务器时间:</strong><br>
                <code id="server-time">' . date('Y-m-d H:i:s') . '</code><br>
                <strong>会话信息:</strong><br>
                <pre id="session-info">' . var_export($_SESSION, true) . '</pre>
                <strong>服务器信息:</strong><br>
                <pre id="server-info">' . var_export($_SERVER, true) . '</pre>
            </div>
        </div>';
    }
}
