<?php

namespace ChatRoom\Core\Helpers;

class Helpers
{
    /**
     * 生成JSON响应
     *
     * @param string $message 响应消息
     * @param int $statusCode HTTP状态码
     * @return string JSON格式的响应
     */
    public function jsonResponse(string $message, int $statusCode = 200): string
    {
        // 设置响应的内容类型为JSON
        header('Content-Type: application/json');

        // 构建JSON响应数据
        $response = [
            'code' => $statusCode,
            'message' => $message
        ];

        // 返回JSON编码后的响应
        return json_encode($response, JSON_UNESCAPED_UNICODE);
    }
}