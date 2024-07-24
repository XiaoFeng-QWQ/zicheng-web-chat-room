<?php

namespace ChatRoom\Core;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * 入口类
 * 
 * @copyright 2024 XiaoFeng-QWQ
 * @version 2.0
 * @author XiaoFeng-QWQ <1432777209@qq.com>
 */

class Main
{
    public Route $route;

    public function __construct()
    {
        // 创建 Logger 实例
        $logger = new Logger('App.Route');
        $logger->pushHandler(new StreamHandler(FRAMEWORK_DIR . '/Writable/logs/App.Route.log'));

        // 实例化 Route 并传递 Logger 实例
        $this->route = new Route($logger);
    }

    /**
     * 启动程序
     *
     * @return void
     */
    public function run(): void
    {
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
