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
<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><meta http-equiv="X-UA-Compatible"content="IE=edge"><meta name="viewport"content="width=device-width, initial-scale=1.0"><title><?php echo htmlspecialchars($title);?></title><style>body{margin:0;padding:0;font-family:"微软雅黑";font-size:14px;color:#333;background-color:#2068b4 !important}a{text-decoration:none;color:#fff}a:hover{text-decoration:underline}img{border:0}text{color:#fff}p{margin-block-start:0em;margin-block-end:0em;margin-inline-start:0px;margin-inline-end:0px}ul{list-style:none;margin-block-start:0em;margin-block-end:0em;margin-inline-start:0px;margin-inline-end:0px;padding-inline-start:0px}ol{list-style:none;padding-inline-start:0px}.main{position:absolute;display:flex;flex-direction:column;justify-content:center;align-items:left;height:100%;margin-left:10%;margin-right:10%;color:#fff;top:-100px}.p1{font-size:150px;margin-top:50px;margin-bottom:20px}.p2{font-size:30px;margin-bottom:15px}.p3{font-size:25px;margin-bottom:15px}.p4{display:flex;flex-direction:row;justify-content:left}.p5{font-size:25px}.p6{margin-top:5px;font-size:25px}.x1{margin-right:20px}</style></head><body><div class="main"><p class="p1">:(</p><p class="p2"><?php echo htmlspecialchars($title);?></p><p class="p3">正在收集错误信息<span class="ds">0%</span></p><div class="p4"><img style="width: 137px; height: 137px; margin-right: 20px;"src="data:image/png;base64,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            
		"alt="QRCODE"><div><p class="p5">有关此问题的详细信息是：</p><ul class="p6"><li><?php echo nl2br(htmlspecialchars($msg));?></li></ul></div></div></div><script>var ds=document.querySelector(".ds");var timer=setInterval(function(){let num=parseInt(ds.innerText);num++;ds.innerText=num+"%";if(num>=100){clearInterval(timer)}},50);</script></body></html>
<?php
        return ob_get_clean();
    }
}
