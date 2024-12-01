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
    public function http(int $code, string $msg, ?string $title = '错误'): void
    {
        // 设置HTTP响应状态码
        http_response_code($code);

        // 生成并输出错误页面
        echo $this->generateErrorPage($code, $msg, $title);
    }

    /**
     * 生成错误页面的HTML
     *
     * @param int $code HTTP状态码
     * @param string $msg 错误消息
     * @param string|null $title 错误标题
     * @return string
     */
    private function generateErrorPage(int $code, string $msg, ?string $title): string
    {
        // 定义错误页面基本结构
        ob_start();
?>
<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><meta http-equiv="X-UA-Compatible"content="IE=edge"><meta name="viewport"content="width=device-width, initial-scale=1.0"><title><?php echo htmlspecialchars($title);?></title><style>body{margin:0;padding:0;font-family:"微软雅黑";font-size:14px;color:#333;background-color:#2068b4 !important}a{text-decoration:none;color:#fff}a:hover{text-decoration:underline}img{border:0}text{color:#fff}p{margin-block-start:0em;margin-block-end:0em;margin-inline-start:0px;margin-inline-end:0px}ul{list-style:none;margin-block-start:0em;margin-block-end:0em;margin-inline-start:0px;margin-inline-end:0px;padding-inline-start:0px}ol{list-style:none;padding-inline-start:0px}.main{position:absolute;display:flex;flex-direction:column;justify-content:center;align-items:left;height:100%;margin-left:10%;margin-right:10%;color:#fff;top:-100px}.p1{font-size:150px;margin-top:50px;margin-bottom:20px}.p2{font-size:30px;margin-bottom:15px}.p3{font-size:25px;margin-bottom:15px}.p4{display:flex;flex-direction:row;justify-content:left}.p5{font-size:25px}.p6{margin-top:5px;font-size:25px}.x1{margin-right:20px}</style></head><body><div class="main"><p class="p1">:(</p><p class="p2"><?php echo htmlspecialchars($title);?></p><p class="p3">正在收集错误信息<span class="ds">0%</span></p><div class="p4"><img style="width: 137px; height: 137px; margin-right: 20px;"src="/StaticResources/image/httpsbri6.cnarchives405.html.png
		"alt="QRCODE"><div><p class="p5">有关此问题的详细信息是：</p><ul class="p6"><li><?php echo nl2br(htmlspecialchars($msg));?></li></ul></div></div></div><script>var ds=document.querySelector(".ds");var timer=setInterval(function(){let num=parseInt(ds.innerText);num++;ds.innerText=num+"%";if(num>=100){clearInterval(timer)}},50);</script></body></html>
<?php
        return ob_get_clean();
    }
}
