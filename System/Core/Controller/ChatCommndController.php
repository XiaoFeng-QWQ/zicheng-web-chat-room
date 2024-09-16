<?php

namespace ChatRoom\Core\Controller;

/**
 * 聊天指令控制器
 */
class ChatCommndController
{
    function help()
    {
        return "可用指令:
        <br>
        /help - 输出所有可用指令
        <br>
        /refresh - 重新加载聊天列表
        <br>
        /stop - 全员禁言 | 暂未实现
        <br>
        /debug - 输出调试信息
        <br>
        ";
    }

    function stop()
    {
        // 实现禁止所有人发言的逻辑
        return "所有用户已被禁言";
    }

    function refresh()
    {
        // 实现重新加载所有数据的逻辑
        return "
        <script>
            offset = 0
            $('#chat-box').html('');
            loadChatMessages();
        </script>";
    }

    function debug()
    {
        return '
        <div id="debug-info" class="debug-info">
            <div class="debug-info-header">
                <h5>调试信息</h5>
                <hr>
            </div>
            <div style="padding: 15px;" id="debug-content">
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
