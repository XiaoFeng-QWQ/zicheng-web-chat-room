<?php

namespace ChatRoom\Core;

/**
 * 路由类
 * 
 * @copyright 2024 XiaoFeng-QWQ
 * @version 2.0
 * @author XiaoFeng-QWQ <1432777209@qq.com>
 */

use ChatRoom\Core\Config\App;
use ChatRoom\Core\Helpers\Error;
use Exception;

class Route
{
    private array $route_rules;
    private App $app_config;
    private string $currentUri;
    private Error $error;

    public function __construct()
    {
        $this->app_config = new App();
        $this->currentUri = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
        $this->route_rules = $this->app_config->route_rules;
        $this->error = new Error();
    }

    public function processRoutes(): void
    {
        if (empty($this->currentUri)) {
            $this->error->http('400', "400 Bad Request URI：$this->currentUri");
            return;
        }

        $handler = $this->findHandler($this->currentUri);
        if ($handler) {
            $filePath = realpath(FRAMEWORK_APP_PATH . '/Views' . $handler['file'][0]);

            if ($filePath && is_file($filePath)) {
                include $filePath;
            } else {
                $this->error->http('404', "路由规则配置错误，视图文件{$filePath}不存在！", "路由规则配置错误");
            }
        } else {
            $this->error->http('404', '404 页面不存在，请刷新重试', "您访问的页面：$this->currentUri 不存在");
        }
    }

    private function findHandler(string $uri): ?array
    {
        $uriWithoutQuery = strtok($uri, '?');
        foreach ($this->route_rules as $pattern => $handler) {
            $pattern = preg_quote($pattern, '/');
            if (preg_match("/^$pattern$/", $uriWithoutQuery)) {
                return $handler;
            }
        }
        return null;
    }
}
