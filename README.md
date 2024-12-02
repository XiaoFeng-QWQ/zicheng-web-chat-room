<center>
    <h1>子辰网页聊天室</h1>
    <img src=https://image.lolimi.cn/2024/12/01/674c7b46aa3c1.png alt='logo' />
</center>

发布页: [https://bri6.cn/archives/405.html](https://bri6.cn/archives/405.html)  
开源主仓库: [GitHub](https://github.com/XiaoFeng-QWQ/zicheng-web-chat-room)  
开源镜像: [Gitee](https://gitee.com/XiaoFengQWQ/zichen-web-chat-room) (可能不保证实时更新)

## 使用注意

- 确保 PHP 版本为 8.2 或以上

Nginx需要配置以下伪静态
```
if (!-d $request_filename){
    set $rule_0 1$rule_0;
}
if ($rule_0 = "1"){
 rewrite ^/(.+)/$ /$1 permanent;
}
#ignored: "-" thing used or unknown variable in regex/rew
if (!-f $request_filename){
    set $rule_2 1$rule_2;
}
if (!-d $request_filename){
    set $rule_2 2$rule_2;
}
if ($rule_2 = "21"){
 rewrite ^/(.*)$ /index.php?/$1 last;
}
```

## 项目介绍

- 基于 AJAX 技术实现的网页聊天室
- 兼容虚拟主机环境
- 使用 Bootstrap 进行界面设计

## 已实现的功能
- [x] 完整的后台管理
- [x] 上传文件
- [x] 上传的文件预览

## 参与贡献

1. Fork 本仓库
2. 创建新分支
3. 提交代码更改
4. 提交 Pull Request
