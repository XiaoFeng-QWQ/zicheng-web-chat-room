<?php

namespace ChatRoom\Core\Helpers;

/**
 * 未分类辅助
 */
class Helpers
{
    /**
     * 生成JSON响应(内置exit)
     *
     * @param int $statusCode HTTP状态码
     * @param $message 响应消息
     * @param array $data 返回数据数组
     * @return string JSON格式的响应
     */
    public function jsonResponse(int $statusCode, $message, array $data = []): string
    {
        header('Content-Type: application/json;charset=utf-8');
        // 构建JSON响应数据
        $response = [
            'APIVersion' => '1.1.0.0',
            'code' => $statusCode,
            'message' => $message,
            'data' => $data
        ];
        if (defined('FRAMEWORK_DEBUG') && FRAMEWORK_DEBUG) {
            $response['data']['apiDebug'] = [
                'AppVersion' => FRAMEWORK_VERSION,
                'backtrace' => debug_backtrace(),
            ];
        }
        exit(json_encode($response, JSON_UNESCAPED_UNICODE));
    }

    public function debugBar()
    {
        $backtrace = debug_backtrace();
        $executionTime = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
        $memoryUsage = memory_get_usage();
        $memoryPeakUsage = memory_get_peak_usage();
        $includedFiles = get_included_files();

        // 获取数据库查询信息
        $dbQueries = [];
        $totalQueryTime = 0;
        $queryLogFile = FRAMEWORK_DIR . '/Writable/logs/db_queries.log';

        if (file_exists($queryLogFile)) {
            $logLines = file($queryLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $logLines = array_reverse($logLines); // 新日志在前
            foreach ($logLines as $line) {
                $queryData = unserialize($line);
                if ($queryData) {
                    $dbQueries[] = [
                        'sql' => $queryData['sql'],
                        'params' => $queryData['params'],
                        'time' => $queryData['time'],
                        'caller' => $queryData['caller']
                    ];
                    $totalQueryTime += $queryData['time'];
                }
            }
        }

        // 输出调试信息
        echo "<style>#debug-bar{position:fixed;left:0;width:100%;background:#1a1a1a;color:#e0e0e0;padding:5px 10px;z-index:9999;display:flex;justify-content:space-between;align-items:center;font-family:'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;font-size:13px;box-shadow:0 -2px 10px rgba(0,0,0,0.3);border-top:1px solid #333;transition:all 0.3s ease}#debug-bar.bottom{bottom:0}#debug-bar.top{top:0;border-top:none;border-bottom:1px solid #333}.debug-info{display:flex;gap:15px;overflow-x:auto;padding:3px 0;scrollbar-width:thin}.debug-info::-webkit-scrollbar{height:3px}.debug-info::-webkit-scrollbar-thumb{background:#555}.debug-info span{white-space:nowrap;display:flex;align-items:center;gap:4px}.debug-info strong{color:#a0a0a0;font-weight:500}.debug-actions{display:flex;gap:8px;margin-left:10px}.debug-actions button{background:#333;color:#e0e0e0;border:none;padding:4px 10px;border-radius:3px;cursor:pointer;font-size:12px;transition:all 0.2s;display:flex;align-items:center;gap:4px}.debug-actions button:hover{background:#444}.debug-actions button i{font-size:12px}#debug-tabs{display:none;position:absolute;left:0;width:100%;background:#252525;color:#e0e0e0;max-height:60vh;overflow:scroll;box-shadow:0 -2px 10px rgba(0,0,0,0.3)}#debug-bar.bottom #debug-tabs{bottom:100%}#debug-bar.top #debug-tabs{top:100%}.tab-header{display:flex;background:#1e1e1e;border-bottom:1px solid #333;overflow-x:auto;position:sticky;top:0;z-index:10}.tab-header::-webkit-scrollbar{height:3px}.tab-header::-webkit-scrollbar-thumb{background:#555}.tab-header button{background:transparent;border:none;color:#a0a0a0;padding:8px 15px;cursor:pointer;white-space:nowrap;font-size:12px;transition:all 0.2s;border-right:1px solid #333;display:flex;align-items:center;gap:5px}.tab-header button:last-child{border-right:none}.tab-header button:hover{color:#e0e0e0;background:#333}.tab-header button.active{color:#fff;background:#333;font-weight:500}.tab-content{overflow-y:auto;max-height:calc(60vh - 40px)}.tab-panel{display:none;padding:10px}.tab-panel.active{display:block}.backtrace-item{margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid #333}.backtrace-item:last-child{border-bottom:none}.query-info{margin-bottom:15px;padding-bottom:15px;border-bottom:1px solid #333}.query-info:last-child{border-bottom:none}.query-sql{color:#4CAF50;margin:5px 0}.query-time{color:#FF9800;font-size:12px}.query-params{color:#2196F3;margin:5px 0;font-size:12px}.query-caller{color:#9C27B0;font-size:11px;margin-top:5px;opacity:0.8}pre{margin:0;white-space:pre-wrap;word-break:break-word;font-family:'Consolas','Monaco','Courier New',monospace;font-size:12px;line-height:1.4}@media (max-width:768px){.debug-info{gap:8px}.debug-info span{flex-direction:column;align-items:flex-start;gap:0}.debug-actions{gap:5px}}</style>";
        echo "<div id='debug-bar' class='bottom'>";
        echo "<div class='debug-info'>";

        echo "<span><strong>Execution Time:</strong> " . number_format($executionTime, 4) . "s</span>";
        echo "<span><strong>Memory:</strong> " . number_format($memoryUsage / 1024, 2) . "KB</span>";
        echo "<span><strong>Peak Memory:</strong> " . number_format($memoryPeakUsage / 1024, 2) . "KB</span>";
        if (!empty($dbQueries)) {
            echo "<span><strong>DB Queries:</strong> " . count($dbQueries) . "</span>";
            echo "<span><strong>DB Time:</strong> " . number_format($totalQueryTime * 1000, 2) . "ms</span>";
        }
        echo "</div>";

        echo "<div class='debug-actions'>";
        echo "<button onclick='togglePosition()'>Toggle Position</button>";
        echo "<button onclick='toggleTabs()'>Show Debug</button>";
        echo "</div>";

        // 输出调试信息作为标签页
        echo "<div id='debug-tabs'>";
        echo "<div class='tab-header'>";
        echo "<button class='active' onclick='switchTab(event, \"backtrace\")'>Backtrace</button>";
        echo "<button onclick='switchTab(event, \"included-files\")'>Files (" . count($includedFiles) . ")</button>";
        echo "<button onclick='switchTab(event, \"db-queries\")'>Queries (" . count($dbQueries) . ")</button>";
        echo "</div>";

        // 调用堆栈信息标签页
        echo "<div id='backtrace' class='tab-panel active'>";
        echo "<pre>";
        foreach ($backtrace as $trace) {
            echo "<div class='backtrace-item'>";
            echo "File: " . (isset($trace['file']) ? $trace['file'] : '') . "\n";
            echo "Line: " . (isset($trace['line']) ? $trace['line'] : '') . "\n";
            echo "Function: " . (isset($trace['function']) ? $trace['function'] : '') . "\n";
            echo "Class: " . (isset($trace['class']) ? $trace['class'] : '') . "\n";
            echo "Type: " . (isset($trace['type']) ? $trace['type'] : '') . "\n";
            echo "</div>";
        }
        echo "</pre>";
        echo "</div>";

        // 引入文件列表标签页
        echo "<div id='included-files' class='tab-panel'>";
        echo "<pre>";
        foreach ($includedFiles as $file) {
            echo $file . "\n";
        }
        echo "</pre>";
        echo "</div>";

        // 数据库查询标签页
        if (!empty($dbQueries)) {
            echo "<div id='db-queries' class='tab-panel'>";
            echo "<div style='padding:10px'>";
            echo "<div><strong>Total Queries:</strong> " . count($dbQueries) . "</div>";
            echo "<div><strong>Total Query Time:</strong> " . number_format($totalQueryTime * 1000, 2) . "ms</div>";
            echo "</div>";
            foreach ($dbQueries as $index => $query) {
                echo "<div class='query-info'>";
                echo "<div><strong>Query #" . ($index + 1) . "</strong></div>";
                echo "<div class='query-time'>Time: " . number_format($query['time'] * 1000, 2) . "ms</div>";
                echo "<div class='query-sql'><pre>" . htmlspecialchars($query['sql']) . "</pre></div>";
                if (!empty($query['params'])) {
                    echo "<div class='query-params'>Params: <pre>" . htmlspecialchars(print_r($query['params'], true)) . "</pre></div>";
                }
                echo "<div class='query-caller'>Called from: " . htmlspecialchars($query['caller']) . "</div>";
                echo "</div>";
            }
            echo "</div>";
        }

        echo "</div>"; // 结束 debug-tabs
        echo "</div>"; // 结束 debug-bar

        // JavaScript 控制逻辑
        echo "
	    <script>
	        function togglePosition() {
	            const debugBar = document.getElementById('debug-bar');
	            if (debugBar.classList.contains('bottom')) {
	                debugBar.classList.remove('bottom');
	                debugBar.classList.add('top');
	            } else {
	                debugBar.classList.remove('top');
	                debugBar.classList.add('bottom');
	            }
	        }
	        function toggleTabs() {
	            const debugTabs = document.getElementById('debug-tabs');
	            if (debugTabs.style.display === 'none' || !debugTabs.style.display) {
	                debugTabs.style.display = 'block';
	            } else {
	                debugTabs.style.display = 'none';
	            }
	        }
	        function switchTab(event, tabId) {
	            // 更新标签按钮状态
	            const tabButtons = document.querySelectorAll('#debug-tabs .tab-header button');
	            tabButtons.forEach(button => {
	                button.classList.remove('active');
	            });
	            event.currentTarget.classList.add('active');
	            // 更新标签内容状态
	            const tabPanels = document.querySelectorAll('#debug-tabs .tab-panel');
	            tabPanels.forEach(panel => {
	                panel.classList.remove('active');
	            });
	            document.getElementById(tabId).classList.add('active');
	        }
	    </script>";
    }

    /**
     * 获取当前请求的完整 URL。
     *
     * @return string 返回当前请求的完整 URL（协议 + 主机名）
     */
    public function getCurrentUrl()
    {
        $protocol = 'http';
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $protocol = 'https';
        }

        $host = $_SERVER['HTTP_HOST']; // 当前主机名（包括端口号）

        // 返回完整的 URL
        return $protocol . '://' . $host;
    }

    /**
     * 获取指定GET参数
     *
     * @param string $param 要获取的参数名称
     * @return string 指定参数的格式化字符串，例如 "?param=value"，如果参数不存在则返回空字符串
     */
    public function getGetParams($param, $return = true)
    {
        // 获取查询字符串
        $queryString = $_SERVER['QUERY_STRING'] ?? '';

        // 将查询字符串解析为数组
        parse_str($queryString, $queryArray);

        // 检查并输出 GET 参数
        if (isset($queryArray[$param])) {
            if ($return) {
                return "?$param={$queryArray[$param]}";
            } else {
                return $queryArray[$param];
            }
        } else {
            return '';
        }
    }
}
