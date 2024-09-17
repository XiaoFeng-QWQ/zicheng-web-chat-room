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
    public array $chatCommandList = [
        '/' => [ // 指令名
            'action' => ['帮助'], // 对应ChatCommandController类函数名
            'notes' => ['获取指令列表'], // 指令说明
            'iSelf' =>  true, // 发送消息后是否仅自己能看见
            'isAdmin' => false // 是否需要管理员权限
        ],
        '/随机图片' => [
            'action' => ['随机图片'],
            'notes' => ['后面参数见:https://t.alcy.cc/ | 随机二次元图片'],
            'iSelf' =>  false,
            'isAdmin' => false
        ],
        '/重载' => [
            'action' => ['重载'],
            'notes' => ['重载聊天列表'],
            'iSelf' => true,
            'isAdmin' => false
        ],
        '/全员禁言' => [
            'action' => ['全员禁言'],
            'notes' => ['说明(可选) 全员禁言'],
            'iSelf' => true,
            'isAdmin' => true
        ],
        '/调试' => [
            'action' => ['调试'],
            'notes' => ['输出调试信息'],
            'iSelf' => true,
            'isAdmin' => true
        ]
    ];
}
