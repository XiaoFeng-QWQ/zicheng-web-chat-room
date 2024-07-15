<?php

namespace ChatRoom\Core\Helpers;

class Helpers
{
    /**
     * 生成JSON响应
     *
     * @param string $message
     * @param integer $statusCode
     * @return string
     */
    public function jsonResponse($message, $statusCode = 200)
    {
        header('Content-Type: application/json');
        return json_encode([
            'code' => $statusCode,
            'message' => $message
        ]);
    }
}
