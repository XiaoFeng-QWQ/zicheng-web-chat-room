<?php

namespace ChatRoom\Core\Config;

/**
 * 应用配置
 */
class App
{
    /**
     * 路由规则
     * @var array
     */
    public array $routeRules = [
        // 基本路由
        '/' => [
            'file' => ['/index.php'],
            'cache' => [null]
        ],
        '/index' => [
            'file' => ['/index.php'],
            'cache' => [null]
        ],
        '/user/login' => [
            'file' => ['/user/auth/login.php'],
            'cache' => [null]
        ],
        '/user/register' => [
            'file' => ['/user/auth/register.php'],
            'cache' => [null]
        ],
        '/user/logout' => [
            'file' => ['/user/logout.php'],
            'cache' => [null]
        ],
        '/api/v1/[\s\S]*' => [
            'file' => ['/api/v1/API_BASE.php']
        ],
        '/doc/api/v1.html' => [
            'file' => ['/v1apidoc.html']
        ]
    ];
}
