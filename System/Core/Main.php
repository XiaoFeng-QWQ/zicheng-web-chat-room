<?php

namespace ChatRoom\Core;

use ChatRoom\Core\Modules\WebSecurity;

/**
 *     _____   _ ________                     ________          __  ____  ____  ____  __  ___
 *    /__  /  (_) ____/ /_  ___  ____  ____ _/ ____/ /_  ____ _/ /_/ __ \/ __ \/ __ \/  |/  /
 *      / /  / / /   / __ \/ _ \/ __ \/ __ `/ /   / __ \/ __ `/ __/ /_/ / / / / / / / /|_/ / 
 *     / /__/ / /___/ / / /  __/ / / / /_/ / /___/ / / / /_/ / /_/ _, _/ /_/ / /_/ / /  / /  
 *    /____/_/\____/_/ /_/\___/_/ /_/\__, /\____/_/ /_/\__,_/\__/_/ |_|\____/\____/_/  /_/   
 *                                  /____/                                                   
 * ------------------------------------------------------------------ Powered By:XiaoFeng_QWQ
 * 注意事项：
 * - 文件路径必须遵守以下规则：
 *   - 路径末尾不得有斜杠 ("/")。
 *   - 路径必须以斜杠 ("/") 开头。
 *   - URI 也需符合以上规则。
 * - 安装检测通过检查常量 `FRAMEWORK_DATABASE_PATH` 是否定义来完成。
 * -----------------------------------
 * 命名规范：
 * - `System` 目录下的所有文件使用大驼峰命名法。
 * - `App` 和 `StaticResources` 目录下的文件使用点号分隔的命名方式，目录使用大驼峰命名法。
 * - 项目根目录使用大驼峰命名法。
 * - 函数名使用小驼峰，函数内部变量也是
 * -----------------------------------
 * 
 * @copyright 2024 - 2025 XiaoFeng-QWQ
 * @version FRAMEWORK_VERSION
 * @author XiaoFeng-QWQ <1432777209@qq.com>
 * @license Apache
 * @link https://github.com/XiaoFeng-QWQ/zicheng-web-chat-room
 */
class Main
{
    public Route $route;

    public function __construct()
    {
        $this->route = new Route(); // 初始化 $route 属性
    }

    /**
     * 初始化
     *
     */
    private function initialize(): void
    {
        require_once FRAMEWORK_DIR . '/System/Core/Modules/HandleException.php';
        require_once FRAMEWORK_DIR . '/System/Core/Modules/Waf.php';

        /**
         * 注册错误处理函数
         */
        set_exception_handler(function ($e) {
            HandleException($e);
        });
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            if (!(error_reporting() & $errno)) {
                return false;
            }
            $exception = new \ErrorException($errstr, 0, $errno, $errfile, $errline);
            HandleException($exception);
            return true;
        });
        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                $exception = new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
                HandleException($exception, false, true);
            }
        });

        session_set_cookie_params([
            'lifetime' => 2592000,
            'path' => '/',
            'domain' => '',
            'secure' => false,    // 设置是否仅通过 HTTPS 传输 cookie
            'httponly' => true    // 仅通过 HTTP 协议访问，防止通过 JavaScript 获取 cookie
        ]);
        session_start();
        date_default_timezone_set("Asia/Shanghai");
        // 系统防护
        $security = new WebSecurity();
        $security->checkRequest();
    }

    /**
     * 启动程序
     *
     * @return void
     */
    public function run(): void
    {
        $this->initialize();

        // 调试模式直接跳过安装
        if (defined('FRAMEWORK_DEBUG') && FRAMEWORK_DEBUG) {
            exit($this->route->processRoutes());
        }
        if (defined('FRAMEWORK_INSTALL_LOCK') && FRAMEWORK_INSTALL_LOCK === true) {
            // 启动路由处理
            $this->route->processRoutes();
        } else {
            header('Location: /Admin/install/index.php');
            exit;
        }
    }
}
