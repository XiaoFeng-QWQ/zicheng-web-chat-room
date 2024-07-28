<?php

namespace ChatRoom\Core\Helpers;

use Exception;

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
        http_response_code($code);
        throw new Exception($msg);
    }
}
