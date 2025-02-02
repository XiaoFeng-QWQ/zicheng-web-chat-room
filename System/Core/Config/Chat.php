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
        '/ai' => [
            'action' => ['ai'], // 对应ChatCommandController类函数名
            'notes' => ['ai聊天(暂不支持上下文)'], // 指令说明
            'iSelf' =>  false, // 发送消息后是否仅自己能看见
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
        '/清屏' => [
            'action' => ['清屏'],
            'notes' => ['清空聊天窗口'],
            'iSelf' => true,
            'isAdmin' => false,
        ],

        '/发起投票' => [
            'action' => ['发起投票'],
            'notes' => ['只能同时进行一个投票 [主题] [选项]'],
            'iSelf' => false,
            'isAdmin' => false,
        ],
        '/投票' => [
            'action' => ['投票'],
            'notes' => ['参与投票 [编号]'],
            'iSelf' => true,
            'isAdmin' => false,
        ],
        '/结束投票' => [
            'action' => ['结束投票'],
            'notes' => ['结束投票'],
            'iSelf' => false,
            'isAdmin' => true,
        ],
        '/显示投票结果' => [
            'action' => ['显示投票结果'],
            'notes' => ['显示当前进行中的投票状态'],
            'iSelf' => true,
            'isAdmin' => true,
        ],

        '/全员禁言' => [
            'action' => ['全员禁言'],
            'iSelf' => false,
            'isAdmin' => true,
            'notes' => ['说明(可选) 全员禁言'],
        ],
        '/调试' => [
            'action' => ['调试'],
            'notes' => ['输出调试信息'],
            'iSelf' => true,
            'isAdmin' => true
        ]
    ];

    /**
     * 上传文件
     *
     * @var array
     */
    public array $uploadFile = [
        // 运行上传文件类型
        'allowTypes' => [
            //** image **//
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/bmp',
            'image/svg+xml',
            'image/x-icon',
            //** application **//
            'application/pdf',
            'application/msword',
            'application/vnd.ms-excel',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/rtf',
            'application/json',
            //** text **//
            'text/plain',
            'text/txt',
            'text/json',
            'text/xml',
            'text/log',
            //** audio **//
            'audio/mpeg',
            'audio/wav',
            'audio/ogg',
            'audio/aac',
            'audio/mp3',
            'audio/x-msvideo',
            'audio/ogg',
            'audio/webm',
            //** video **/
            'video/mp4',
            'video/mkv',
        ],
        'maxSize' => 4097152 // 最大文件大小 4MB
    ];
}
