<?php

namespace ChatRoom\Core;

/**
 *     _____   _ ________                     ________          __  ____  ____  ____  __  ___
 *    /__  /  (_) ____/ /_  ___  ____  ____ _/ ____/ /_  ____ _/ /_/ __ \/ __ \/ __ \/  |/  /
 *      / /  / / /   / __ \/ _ \/ __ \/ __ `/ /   / __ \/ __ `/ __/ /_/ / / / / / / / /|_/ / 
 *     / /__/ / /___/ / / /  __/ / / / /_/ / /___/ / / / /_/ / /_/ _, _/ /_/ / /_/ / /  / /  
 *    /____/_/\____/_/ /_/\___/_/ /_/\__, /\____/_/ /_/\__,_/\__/_/ |_|\____/\____/_/  /_/   
 *                                  /____/                                                   
 *                                                          Powered By:XiaoFeng_QWQ
 * -----------------------------------
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
 * @copyright 2025 XiaoFeng-QWQ
 * @version FRAMEWORK_VERSION
 * @author XiaoFeng-QWQ <1432777209@qq.com>
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
    private function init() {
        require_once __DIR__ . '/../../config.global.php';
        require_once __DIR__ . '/../../System/Core/Helpers/HandleException.php';
        set_exception_handler('HandleException');
        session_start();
        date_default_timezone_set("Asia/Shanghai");
    }

    /**
     * 启动程序
     *
     * @return void
     */
    public function run(): void
    {
        $this->init();
        if (defined('FRAMEWORK_DEBUG') && FRAMEWORK_DEBUG) {
            exit($this->route->processRoutes());
        }
        if (!defined('FRAMEWORK_DATABASE_PATH')) {
            // 滚去给我安装😡！
            header('Location: /Admin/install/index.php');
            exit;
        } else {
            // 启动路由
            $this->route->processRoutes();
        }
    }
}
