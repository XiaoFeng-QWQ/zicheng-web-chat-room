<?php

namespace ChatRoom\Core\Config;

/**
 * 聊天配置
 *
 * @var array
 */
class Chat
{
    /**
     * 聊天指令列表
     *
     * @var array
     */
    public array $ChatCommndList = [
        '/help' => [
            'action' => ['help'],
            'isAdmin' => false
        ],
        '/refresh' => [
            'action' => ['refresh'],
            'isAdmin' => false
        ],
        '/stop' => [
            'action' => ['stop'],
            'isAdmin' => true
        ],
        '/debug' => [
            'action' => ['debug'],
            'isAdmin' => true
        ]
    ];
}
