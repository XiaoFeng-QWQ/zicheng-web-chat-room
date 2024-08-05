<?php

namespace ChatRoom\Core;


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
        $this->route = new Route(); // 初始化 $route 属性
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
