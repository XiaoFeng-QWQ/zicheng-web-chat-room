<?php

namespace ChatRoom\Core\Controller;

use Exception;
use ChatRoom\Core\Config\Chat;

/**
 * 聊天指令控制器
 */
class ChatCommandController extends ChatController
{
    private $chatConfig;
    private $chatCommandList;

    public function __construct()
    {
        $this->chatConfig = new Chat;
        $this->chatCommandList  = $this->chatConfig->chatCommandList;
    }

    /**
     * 执行命令
     *
     * @param string $action 动作方法名
     * @param array $params 参数列表
     * @return string 返回的响应
     */
    private function executeCommand($action, $params)
    {
        if (method_exists($this, $action)) {
            return call_user_func_array([$this, $action], $params);
        } else {
            return '命令配置错误: 函数不存在';
        }
    }
    /**
     * 基础指令处理
     *
     * @param string $message 用户消息
     * @param array $userInfo 用户信息
     * @return string|false 返回指令响应或 false
     */
    public function command($message, $userInfo)
    {
        $chatConfig = new Chat();
        $parts = explode(' ', $message);
        $command = $parts[0]; // 获取指令名
        $params = array_slice($parts, 1); // 获取参数列表

        // 检查指令是否有效
        if (!isset($chatConfig->chatCommandList[$command])) {
            return false;
        }

        $commandConfig = $chatConfig->chatCommandList[$command];
        $action = $commandConfig['action'][0];
        $isAdminRequired = $commandConfig['isAdmin'];

        // 权限检查：只有管理员才能执行需要管理员权限的指令
        if ($isAdminRequired && $userInfo['group_id'] != 1) {
            return false;
        }

        // 执行命令并捕获异常
        try {
            $response = $this->executeCommand($action, $params);

            // 如果是管理员并且指令配置为不可见，则直接返回响应
            if ($isAdminRequired || $commandConfig['iSelf']) {
                return $response;
            }

            // 否则发送消息
            $this->sendMessage($userInfo, $message);
            $this->insertSystemMessage('system', $response, 'system');
        } catch (Exception $e) {
            throw new ('执行聊天指令发生错误:' . $e);
        }

        return true;
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
    public function 结束投票()
    {
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
