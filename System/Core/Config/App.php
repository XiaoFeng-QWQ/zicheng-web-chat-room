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
        '/api/user' => [
            'file' => ['/api/user.php'],
            'cache' => [null]
        ],
        '/api/chat' => [
            'file' => ['/api/chat.php'],
            'cache' => [null]
        ],
        '/api/captcha' => [
            'file' => ['/api/captcha.php'],
            'cache' => []
        ],
        '/api/debug' => [
            'file' => ['/api/debug.php']
        ],
        '/api/v1/[\s\S]*' => [
            'file' => ['/api/v1/api.php']
        ]
    ];
}
