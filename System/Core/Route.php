<?php

namespace ChatRoom\Core;

/**
 * 路由类
 * 
 */

use ChatRoom\Core\Config\App;
use ChatRoom\Core\Helpers\Error;

class Route
{
    private array $routeRules;
    private App $appConfig;
    private string $currentUri;
    private Error $error;

    public function __construct()
    {
        $this->appConfig = new App();
        $this->currentUri = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
        $this->routeRules = $this->appConfig->routeRules;
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
        foreach ($this->routeRules as $pattern => $handler) {
            $pattern = preg_quote($pattern, '/');
            if (preg_match("/^$pattern$/", $uriWithoutQuery)) {
                return $handler;
            }
        }
        return null;
    }
}
