<?php

namespace ChatRoom\Core\Config;

/**
 * 应用配置
 */
class App
{
    /**
     * 路由规则
     * 注意！URI规则前面必须加'/'结尾不得加'/'
     * @var array
     */
    public array $route_rules = [
        '/' => [
            'file' => [
                '/index.php'
            ],
            'cache' => [
                null
            ]
        ],
        '/index' => [
            'file' => [
                '/index.php'
            ],
            'cache' => [
                null
            ]
        ],
        '/install' => [
            'file' => [
                '/install/index.php'
            ],
            'cache' => [
                null
            ]
        ],
        // 用户路由
        '/user/login' => [
            'file' => [
                '/user/login.php'
            ],
            'cache' => [
                null
            ]
        ],
        '/user/register' => [
            'file' => [
                '/user/register.php'
            ],
            'cache' => [
                null
            ]
        ],
        '/user/logout' => [
            'file' => [
                '/user/logout.php'
            ],
            'cache' => [
                null
            ]
        ],
        // API路由
        '/api/user' => [
            // 用户
            'file' => [
                '/api/user.php'
            ],
            'cache' => [
                null
            ]
        ],
        '/api/chat' => [
            // 聊天主要后端逻辑
            'file' => [
                '/api/chat.php'
            ],
            'cache' => [
                null
            ]
        ],
        '/api/captcha' => [
            'file' => [
                '/api/captcha.php'
            ],
        ]
    ];
}
