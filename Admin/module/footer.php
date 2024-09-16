</main>

<footer class="footer mt-auto py-3 bg-dark">
    <div class="container text-center">
        <span class="text-light">
            <i class="fas fa-copyright"></i> 2024 子辰聊天室. All rights reserved.
        </span>
    </div>
</footer>

<!-- Bootstrap JS and dependencies -->
<script src="https://cdn.bootcdn.net/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.bootcdn.net/ajax/libs/nprogress/0.2.0/nprogress.min.js"></script>
<script src="https://cdn.bootcdn.net/ajax/libs/jquery.pjax/2.0.1/jquery.pjax.min.js"></script>
<script src="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.1.0/js/bootstrap.bundle.min.js"></script>
<script src="js/getMessages.js?<?php echo FRAMEWORK_VERSION ?>"></script>
<script src="js/script.js?<?php echo FRAMEWORK_VERSION ?>"></script>
</body>

</html>

<?php
if (defined('FRAMEWORK_DEBUG') && FRAMEWORK_DEBUG) {
    PHP_EOL;
    // 检查请求头是否包含 text/html
    if (stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'text/html') !== false) {
        ob_start(); // 启用输出缓冲区
?>
        <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
        <style>
            .debug-info {
                position: fixed;
                top: 10%;
                left: 10%;
                width: 900px;
                max-height: 500px;
                overflow-y: auto;
                background: rgba(255, 255, 255, 0.8);
                border: 1px solid #ccc;
                z-index: 1000;
            }

            .debug-info pre {
                font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
                margin: 0 0 1rem;
                overflow: auto;
                font-size: .875em;
            }

            .debug-info-header {
                cursor: move;
                /* 手型光标 */
                position: sticky;
                top: 0;
                background: #ffffff;
                padding: 15px 15px 0px;
            }
        </style>
        <div id="debug-info" class="debug-info">
            <div class="debug-info-header">
                <h5>调试信息</h5>
                <hr>
            </div>
            <div style="padding: 15px;" id="debug-content">
                <!-- 这里是动态更新内容的容器 -->
                <strong>服务器时间:</strong><br>
                <code id="server-time"><?php echo date('Y-m-d H:i:s'); ?></code><br>
                <strong>会话信息:</strong><br>
                <pre id="session-info"><?php var_dump($_SESSION); ?></pre>
                <strong>服务器信息:</strong><br>
                <pre id="server-info"><?php var_dump($_SERVER); ?></pre>
            </div>
        </div>
        <script>
            $(function() {
                $("#debug-info").draggable({
                    containment: "document",
                    handle: ".debug-info-header"
                });
            });

            // 定时请求调试信息
            setInterval(function() {
                $.ajax({
                    url: '/api/debug',
                    method: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        $("#server-time").text(data.data.server_time);
                        $("#session-info").text(JSON.stringify(data.data.session_data, null, 4));
                        $("#server-info").text(JSON.stringify(data.data.server_data, null, 4));
                    }
                });
            }, 1000);
        </script>
<?php
        echo ob_get_clean(); // 获取输出的内容并输出
    }
}
