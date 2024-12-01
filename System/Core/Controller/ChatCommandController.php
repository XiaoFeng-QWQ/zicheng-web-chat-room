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
        $output = '';
        foreach ($this->chatCommandList  as $command => $details) {
            $output .= "<strong>{$command}</strong><br>" . implode(", ", $details['notes']) . PHP_EOL;
        }
        return $output;
    }

    public function 重载()
    {
        return "<script>offset=0;$('#chat-box').html('');loadChatMessages();</script>";
    }

    public function 清屏()
    {
        return "<script>$('#chat-box').html('');</script>聊天窗口已清空。";
    }

    public function 随机图片($type = 'ycy')
    {
        $url = "https://t.alcy.cc/{$type}?json&rand=" . rand();
        $imgUrl = file_get_contents($url);
        if ($imgUrl === false) {
            return '无法获取图片数据';
        }
        return '[!file(path="' . $imgUrl . '", name="photo.jpg", type="image/webp")]';
    }

    public function 发起投票($topic, ...$options)
    {
        $vote = new ChatCommandVoteController;
        return $vote->发起投票($topic, ...$options);
    }
    public function 投票($option)
    {
        $vote = new ChatCommandVoteController;
        return $vote->投票($option);
    }
    public function 结束投票(){
        $vote = new ChatCommandVoteController;
        return $vote->结束投票();
    }
    public function 显示投票结果()
    {
        $vote = new ChatCommandVoteController;
        return $vote->显示投票结果();
    }

    /**
     * 管理员指令
     * 
     */

    public function 全员禁言($reason = 'no reason specified')
    {
        // 实现禁止所有人发言的逻辑
        return "所有用户已被禁言，原因: $reason";
    }
    public function 调试()
    {
        return '<div id="debug-info" class="debug-info"><div class="debug-info-header"><h5>调试信息</h5></div><div style="padding: 15px; text-align: justify;" id="debug-content"><strong>服务器时间:</strong><br><code id="server-time">' . date('Y-m-d H:i:s') . '</code><br><strong>会话信息:</strong><br><pre id="session-info">' . var_export($_SESSION, true) . '</pre><strong>服务器信息:</strong><br><pre id="server-info">' . var_export($_SERVER, true) . '</pre></div></div>';
    }
}
