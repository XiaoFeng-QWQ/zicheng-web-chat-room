<?php

/**
 *     _____   _ ________                     ________          __  ____  ____  ____  __  ___
 *    /__  /  (_) ____/ /_  ___  ____  ____ _/ ____/ /_  ____ _/ /_/ __ \/ __ \/ __ \/  |/  /
 *      / /  / / /   / __ \/ _ \/ __ \/ __ `/ /   / __ \/ __ `/ __/ /_/ / / / / / / / /|_/ / 
 *     / /__/ / /___/ / / /  __/ / / / /_/ / /___/ / / / /_/ / /_/ _, _/ /_/ / /_/ / /  / /  
 *    /____/_/\____/_/ /_/\___/_/ /_/\__, /\____/_/ /_/\__,_/\__/_/ |_|\____/\____/_/  /_/   
 *                                  /____/                                                   
 *                                                          Powered By:XiaoFeng_QWQ V:1.7.0.1
 * -----------------------------------
 * 注意事项：
 * - 文件路径必须遵守以下规则：
 *   - 路径末尾不得有斜杠 ("/")。
 *   - 路径必须以斜杠 ("/") 开头。
 *   - URI 也需符合以上规则。
 * - 安装检测通过检查常量 `FRAMEWORK_DATABASE_PATH` 是否定义来完成。（别问我为什么💦）
 * -----------------------------------
 * 命名规范：
 * - `System` 目录下的所有文件使用大驼峰命名法。
 * - `App` 和 `StaticResources` 目录下的文件使用点号分隔的命名方式，目录使用大驼峰命名法。
 * - 项目根目录使用大驼峰命名法。
 * - 函数名使用小驼峰，函数内部变量也是
 * -----------------------------------
 */
// 必须在调用 session_start() 之前设置 session.cookie_lifetime
// 设置 PHPSESSID cookie 存活时长
ini_set('session.cookie_lifetime', 86400 * 365); // 设置为1年（86400秒 * 365天）
ini_set('session.gc_maxlifetime', 86400 * 365); // 垃圾回收最大生存时间（与cookie保持同步）
session_start();

// 强制设置时区为国内
date_default_timezone_set("Asia/Shanghai");

// PHP版本判断
if (version_compare(phpversion(), '8.2', '<')) {
    header('Content-type:text/html;charset=utf-8');
    http_response_code(500);
    echo sprintf(
        '<h3>程序运行失败：</h3><blockquote>您的 PHP 版本低于最低要求 8.2，当前版本为 %s</blockquote>',
        phpversion()
    );
    exit;
}

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.global.php';

// 检查请求头，避免在非图片请求时注册自定义异常处理器
if (strpos($_SERVER['HTTP_ACCEPT'], 'text') !== false) {
    require __DIR__ . '/System/Core/Helpers/handleException.php';
    // 注册自定义异常处理器
    set_exception_handler('HandleException');
}

use ChatRoom\Core\Main;

$App = new Main;
$App->run();

if (defined('FRAMEWORK_DEBUG') && FRAMEWORK_DEBUG) {
    PHP_EOL;
    // 检查请求头是否包含 text/html
    if (stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'text/html') !== false) {
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
    }
}
