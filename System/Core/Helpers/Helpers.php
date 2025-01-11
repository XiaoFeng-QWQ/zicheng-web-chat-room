<?php

namespace ChatRoom\Core\Helpers;

/**
 * 未分类辅助
 */
class Helpers
{
    /**
     * 生成JSON响应(内置exit)
     *
     * @param int $statusCode HTTP状态码
     * @param string $message 响应消息
     * @param array $data 返回数据数组
     * @return string JSON格式的响应
     */
    public function jsonResponse(int $statusCode, string $message, array $data = []): string
    {
        header('Content-Type: application/json');

        // 构建JSON响应数据
        $response = [
            'APIVersion' => '1.1.0.0',
            'code' => $statusCode,
            'message' => $message,
            'data' => $data,
        ];
        if (defined('FRAMEWORK_DEBUG') && FRAMEWORK_DEBUG) {
            $response['data']['api_debug'] = [
                'AppVersion' => FRAMEWORK_VERSION,
                'backtrace' => debug_backtrace(),
            ];
        }

        exit(json_encode($response, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 获取指定GET参数
     *
     * @param string $param 要获取的参数名称
     * @return string 指定参数的格式化字符串，例如 "?param=value"，如果参数不存在则返回空字符串
     */
    public function getGetParams($param, $return = true)
    {
        // 获取查询字符串
        $queryString = $_SERVER['QUERY_STRING'] ?? '';

        // 将查询字符串解析为数组
        parse_str($queryString, $queryArray);

        // 检查并输出 GET 参数
        if (isset($queryArray[$param])) {
            if ($return) {
                return "?$param={$queryArray[$param]}";
            } else {
                return $queryArray[$param];
            }
        } else {
            return '';
        }
    }

    /**
     * 获取当前请求的完整 URL。
     * 
     * @return string 返回当前请求的完整 URL（协议 + 主机名）
     */
    public function getCurrentUrl()
    {
        $protocol = 'http';
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $protocol = 'https';
        }

        $host = $_SERVER['HTTP_HOST'];  // 当前主机名（包括端口号）

        // 返回完整的 URL
        return $protocol . '://' . $host;
    }
}
