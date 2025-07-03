<?php

namespace ChatRoom\Core\Controller;

use Exception;
use ChatRoom\Core\Config\Chat;
use Throwable;

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
    private function executeCommand($action, $userInfo, $params)
    {
        // 将 $userInfo 和 $params 合并，确保 $userInfo 是第一个参数
        $arguments = array_merge([$userInfo], $params);

        if (method_exists($this, $action)) {
            return call_user_func_array([$this, $action], $arguments);
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
            return '指令无效';
        }

        $commandConfig = $chatConfig->chatCommandList[$command];
        $action = $commandConfig['action'][0];
        $isAdminRequired = $commandConfig['isAdmin'];

        // 权限检查：只有管理员才能执行需要管理员权限的指令
        if ($isAdminRequired && $userInfo['group_id'] != 1) {
            return '权限不足';
        }

        // 执行命令并捕获异常
        try {
            // 注意：这里传递的是 $userInfo 和剩余的 $params
            $response = $this->executeCommand($action, $userInfo, $params);

            // 如果是管理员并且指令配置为不可见，则直接返回响应
            if ($isAdminRequired || $commandConfig['iSelf']) {
                return $response;
            }

            // 否则发送消息
            $this->sendMessage($userInfo, $message);
            $this->insertSystemMessage('Command', $response, 'command');

            return true;
        } catch (Throwable $e) {
            throw new Exception('执行聊天指令发生错误:' . $e->getMessage());
        }
    }

    //////////////////////////////////////////////////////////////////////////
    //|                                                                    |//
    //|                                                                    |//
    //////////////////////////////////////////////////////////////////////////
    public function 帮助($userInfo)
    {
        $output = '';
        foreach ($this->chatCommandList  as $command => $details) {
            $output .= "<strong>{$command}</strong><br>" . implode(", ", $details['notes']) . PHP_EOL;
        }
        return $output;
    }

    public function ai($userInfo, $msg)
    {

        $url = "https://spark-api-open.xf-yun.com/v1/chat/completions";
        $data = [
            "model" => "lite", // 指定请求的模型
            "messages" => [
                [
                    "role" => "user",
                    "content" => $msg
                ]
            ]
        ];
        $headers = [
            "Authorization: Bearer 你的APIPassword"
        ];
        // 初始化cURL会话
        $ch = curl_init($url);
        // 滚草泥马，不验证SSL证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        // 执行cURL请求
        $response = curl_exec($ch);
        // 检查是否有错误
        if (curl_errno($ch)) {
            echo 'cURL error: ' . curl_error($ch);
        }
        // 关闭cURL会话
        curl_close($ch);
        return json_decode($response, true)['choices']['0']['message']['content'];
    }

    public function 重载($userInfo)
    {
        return "<script>offset=0;$('#chat-box').html('');loadChatMessages();</script>";
    }

    public function 清屏($userInfo)
    {
        return "<script>$('#chat-box').html('');</script>聊天窗口已清空。";
    }

    public function 随机图片($userInfo, $type = 'ycy')
    {
        $url = "https://t.alcy.cc/{$type}?json&rand=" . rand();
        $imgUrl = file_get_contents($url);
        if ($imgUrl === false) {
            return '无法获取图片数据';
        }
        return '[!file(path_"' . $imgUrl . '", name_"photo.jpg", type_"image/webp")]';
    }

    public function 发起投票($userInfo, $topic, ...$options)
    {
        $vote = new ChatCommandVoteController;
        return $vote->发起投票($topic, ...$options);
    }
    public function 投票($userInfo, $option)
    {
        $vote = new ChatCommandVoteController;
        return $vote->投票($userInfo, $option);
    }
    public function 结束投票($userInfo)
    {
        $vote = new ChatCommandVoteController;
        return $vote->结束投票();
    }
    public function 显示投票结果($userInfo)
    {
        $vote = new ChatCommandVoteController;
        return $vote->显示投票结果();
    }

    /**
     * 管理员指令
     * 
     */

    public function 全员禁言($userInfo, $reason = 'no reason specified')
    {
        // 实现禁止所有人发言的逻辑
        return "所有用户已被禁言，原因: $reason";
    }
    public function 调试($userInfo)
    {
        return '<div id="debug-info" class="debug-info"><div class="debug-info-header"><h5>调试信息</h5></div><div style="padding: 15px; text-align: justify;" id="debug-content"><strong>服务器时间:</strong><br><code id="server-time">' . date('Y-m-d H:i:s') . '</code><br><strong>会话信息:</strong><br><pre id="session-info">' . var_export($_SESSION, true) . '</pre><strong>服务器信息:</strong><br><pre id="server-info">' . var_export($_SERVER, true) . '</pre></div></div>';
    }
    public function handleNoticeCommand($userInfo, ...$options)
    {
        // 验证是否为管理员
        if ($userInfo['group_id'] !== 1) {
            return '权限不足，只有管理员可以发布公告';
        }

        // 验证必要字段
        if (empty($options[0]) || empty($options[1])) {
            return '公告标题和内容不能为空';
        }

        // 设置值
        $noticeData = [
            'title' => '',
            'content' => '',
            'is_sticky' => false,
            'force_read' => false,
            'publisher' => $userInfo['username'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        $noticeData['title'] = $options[0];
        $noticeData['content'] = htmlspecialchars_decode($options[1]); // 既然是管理员了，带html很合理吧（
        $noticeData['is_sticky'] = (isset($options[2]) && $userInfo['group_id'] === 1) ? $options[2] : false;
        $noticeData['force_read'] = (isset($options[3]) && $userInfo['group_id'] === 1) ? $options[3] : false;
        $noticeData['publisher'] = $userInfo['username'];
        $noticeData['created_at'] = date('Y-m-d H:i:s');

        // 创建公告事件
        $events = new Events();
        $result = $events->createEvent(
            'admin.push.notice',
            $userInfo['user_id'],
            0, // 使用0作为target_id，因为公告没有特定目标
            $noticeData
        );

        return $result ? '公告发布成功' : '公告发布失败';
    }
}
