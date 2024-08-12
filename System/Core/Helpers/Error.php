<?php

namespace ChatRoom\Core\Helpers;

/**
 * 错误处理辅助类
 * 
 * @copyright 2024 XiaoFeng-QWQ
 */
class Error
{
    /**
     * 生成并输出HTTP错误页面
     *
     * @param int $code HTTP状态码
     * @param string $msg 错误消息
     * @param string|null $title 错误标题（可选）
     * @return void
     */
    public function http(int $code, string $msg, ?string $title = null): void
    {
        // 设置HTTP响应状态码
        http_response_code($code);

        // 生成并输出错误页面
        exit("
            <html>
                <head>
                    <title>$title</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        h2 { color: #D9534F; }
                        blockquote { background: #f9f9f9; border-left: 10px solid #ccc; margin: 1.5em 10px; padding: 0.5em 10px; }
                    </style>
                </head>
                <body>
                    <h2>$title</h2>
                    <blockquote>
                        $msg
                        <hr> 
                        <small>Powered By XiaoFeng-QWQ</small>
                    </blockquote>
                </body>
            </html>
        ");
    }
}