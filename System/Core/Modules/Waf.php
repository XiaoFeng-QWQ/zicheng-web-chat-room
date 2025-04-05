<?php

namespace ChatRoom\Core\Modules;

use Exception;

/**
 * 云体检通用漏洞防护补丁v1.1  
 * 更新时间：2013-05-25  
 * 功能说明：防护XSS,SQL,代码执行，文件包含等多种高危漏洞  
 * 博客：http://blog.bri6.cn  
 * ----------------------------------------------
 * 小枫_QWQ 2025年1月24日 二改
 * 
 * 该类主要用于防护Web应用中的常见安全漏洞，如XSS（跨站脚本攻击）、SQL注入、文件包含等。
 */
class WebSecurity
{
    /**
     * 存储用于检测URL中可能存在漏洞的正则表达式模式。
     *
     * @var array
     */
    private $urlPatterns;

    /**
     * 存储用于检测请求数据（如GET, POST, COOKIE等）中可能存在漏洞的正则表达式模式。
     *
     * @var array
     */
    private $argsPatterns;

    /**
     * 构造函数：初始化URL和请求参数的默认漏洞检测规则。
     */
    public function __construct()
    {
        // 初始化URL模式，检测XSS等漏洞
        $this->urlPatterns = [
            'xss' => "\\=\\+\\/v(?:8|9|\\+|\\/)|\\%0acontent\\-(?:id|location|type|transfer\\-encoding)",
        ];

        // 初始化请求参数模式，检测XSS、SQL注入、文件包含等漏洞
        $this->argsPatterns = [
            'xss' => "[\\'\\\"\\;\\*\\<\\>].*\\bon[a-zA-Z]{3,15}[\\s\\r\\n\\v\\f]*\\=|\\b(?:expression)\\(|\\<script[\\s\\\\\\/]|\\<\\!\\[cdata\\[|\\b(?:eval|alert|prompt|msgbox)\\s*\\(|url\\((?:\\#|data|javascript)",
            'sql' => "[^\\{\\s]{1}(\\s|\\b)+(?:select\\b|update\\b|insert(?:(\\/\\*.*?\\*\\/)|(\\s)|(\\+))+into\\b).+?(?:from\\b|set\\b)|[^\\{\\s]{1}(\\s|\\b)+(?:create|delete|drop|truncate|rename|desc)(?:(\\/\\*.*?\\*\\/)|(\\s)|(\\+))+(?:table\\b|from\\b|database\\b)|into(?:(\\/\\*.*?\\*\\/)|\\s|\\+)+(?:dump|out)file\\b|\\bsleep\\([\\s]*[\\d]+[\\s]*\\)|benchmark\\(([^\\,]*)\\,([^\\,]*)\\)|(?:declare|set|select)\\b.*@|union\\b.*(?:select|all)\\b|(?:select|update|insert|create|delete|drop|grant|truncate|rename|exec|desc|from|table|database|set|where)\\b.*(charset|ascii|bin|char|uncompress|concat|concat_ws|conv|export_set|hex|instr|left|load_file|locate|mid|sub|substring|oct|reverse|right|unhex)\\(|(?:master\\.\\.sysdatabases|msysaccessobjects|msysqueries|sysmodules|mysql\\.db|sys\\.database_name|information_schema\\.|sysobjects|sp_makewebtask|xp_cmdshell|sp_oamethod|sp_addextendedproc|sp_oacreate|xp_regread|sys\\.dbms_export_extension)",
            'other' => "\\.\\.[\\\\\\/].*\\%00([^0-9a-fA-F]|$)|%00[\\'\\\"\\.]"
        ];
    }

    /**
     * 执行请求数据的安全检查。
     * 
     * 该方法会遍历$_GET, $_POST, $_COOKIE, HTTP_REFERER 和 QUERY_STRING，
     * 对这些数据进行逐项检查，检测是否存在潜在的安全漏洞。
     */
    public function checkRequest()
    {
        // 检查各类请求数据
        $this->checkData($_GET);
        $this->checkData($_POST);
        $this->checkData($_COOKIE);
        $this->checkData(empty($_SERVER['HTTP_REFERER']) ? array() : array($_SERVER['HTTP_REFERER']));
        $this->checkData(empty($_SERVER["QUERY_STRING"]) ? array() : array($_SERVER["QUERY_STRING"]));
    }

    /**
     * 遍历并逐项检查传入的数据。
     *
     * @param array $arr 需要检查的数据数组，通常为$_GET, $_POST等。
     */
    private function checkData($arr)
    {
        foreach ($arr as $key => $value) {
            // 递归检查键（key）
            if (!is_array($key)) {
                $this->check($key);
            } else {
                $this->checkData($key);
            }

            // 递归检查值（value）
            if (!is_array($value)) {
                $this->check($value);
            } else {
                $this->checkData($value);
            }
        }
    }

    /**
     * 检查单个字符串是否存在安全漏洞。
     *
     * @param string $str 需要检查的字符串。
     */
    private function check($str)
    {
        // 检查请求数据是否匹配任何已知的漏洞模式（XSS, SQL注入等）
        foreach ($this->argsPatterns as $pattern) {
            if (preg_match("/" . $pattern . "/is", $str) === 1 || preg_match("/" . $pattern . "/is", urlencode($str)) === 1) {
                $this->handleException($str);
            }
        }

        // 检查URL是否匹配已知的漏洞模式
        foreach ($this->urlPatterns as $pattern) {
            if (preg_match("/" . $pattern . "/is", $str) === 1 || preg_match("/" . $pattern . "/is", urlencode($str)) === 1) {
                $this->handleException($str);
            }
        }
    }

    /**
     * 处理检测到的安全漏洞。
     * 
     * 当检测到潜在漏洞时，会记录相关信息并返回403禁止访问的HTTP响应。
     *
     * @param string $str 触发漏洞的提交数据。
     */
    private function handleException($str)
    {
        $exception = new Exception("WAF防护: \nIP: " . $_SERVER["REMOTE_ADDR"] . "\n时间: " . date("Y-m-d H:i:s") . "\n页面:" . $_SERVER["PHP_SELF"] . "\n提交方式: " . $_SERVER["REQUEST_METHOD"] . "\n提交数据: " . $str);
        handleException($exception, true);
        exit(http_response_code(403));
    }
}
