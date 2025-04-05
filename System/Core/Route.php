<?php

namespace ChatRoom\Core;

/**
 * 路由类
 *
 */

use Exception;
use ChatRoom\Core\Config\App;
use ChatRoom\Core\Helpers\Error;

class Route
{
    private array $routeRules;
    private App $appConfig;
    private string $currentUri;

    public function __construct()
    {
        $this->appConfig = new App();
        $this->currentUri = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
        $this->routeRules = $this->appConfig->routeRules;
    }

    public function processRoutes(): void
    {
        if (empty($this->currentUri)) {
            throw new Exception("400 Bad Request URI：$this->currentUri");
        }

        $handler = $this->findHandler($this->currentUri);
        $error = new Error();
        if ($handler) {
            $filePath = realpath(FRAMEWORK_APP_PATH . '/Views' . $handler['file'][0]);

            if ($filePath && is_file($filePath)) {
                include $filePath;
            } else {
                $error->http(404, '404 路由规则配置错误，视图文件不存在！', '404 页面不存在');
            }
        } else {
            $error->http(404, '404 路由规则配置错误，未找到匹配的路由！', '404 页面不存在');
        }
    }

    private function findHandler(string $uri): ?array
    {
        $uriWithoutQuery = strtok($uri, '?');
        foreach ($this->routeRules as $pattern => $handler) {
            if (strpos($pattern, '/') === 0) {
                if (preg_match("#^$pattern$#", $uriWithoutQuery)) {
                    return $handler;
                }
            } else {
                if ($uriWithoutQuery === $pattern) {
                    return $handler;
                }
            }
        }
        return null;
    }
}
