<?php

namespace ChatRoom\Core\Helpers;

/**
 * 错误处理
 * 
 * @copyright 2024 XiaoFeng-QWQ
 * @author XiaoFeng-QWQ <1432777209@qq.com>
 */
class Error
{
    private const ERROR_HTML = '
    <h2>%s</h2>
    <blockquote>
        <br>
        %s 
        <hr> 
        Powered By XiaoFeng-QWQ
    </blockquote>
    ';

    /**
     * http
     *
     * @param [type] $code - http code
     * @param [type] $msg - 内容
     * @param [type] $title - 标题（可为空）
     * @return void
     */
    public function http($code, $msg, $title = null)
    {
        echo sprintf(self::ERROR_HTML, $title ?? '页面错误：', $msg);
        \http_response_code($code);
    }

    /**
     * 获取错误堆栈
     *
     * @return array
     */
    function getStackTrace(): array
    {
        $stack = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        $stackTrace = [];

        foreach ($stack as $frame) {
            if (isset($frame['file']) && isset($frame['line'])) {
                $stackTrace[] = [
                    'file' => $frame['file'],
                    'line' => $frame['line']
                ];
            }
        }
        return $stackTrace;
    }
}
