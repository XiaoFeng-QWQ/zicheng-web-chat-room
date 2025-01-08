<center> <h1>子辰网页聊天室</h1> </center>

![Logo](https://image.lolimi.cn/2024/12/01/674c7b46aa3c1.png)  
![GitHub Activity](https://repobeats.axiom.co/api/embed/56704d7e8efdf560da335270c21f4bc46db73ada.svg)

**发布页**: [https://bri6.cn/archives/405.html](https://bri6.cn/archives/405.html)  
**开源主仓库**: [GitHub](https://github.com/XiaoFeng-QWQ/zicheng-web-chat-room)  
**开源镜像**: [Gitee](https://gitee.com/XiaoFengQWQ/zichen-web-chat-room) (可能不保证实时更新)

---

## 项目简介

**子辰网页聊天室** 是一款基于 AJAX 技术实现的简易网页聊天室，旨在为用户提供一个轻量级、易于部署的实时聊天解决方案。该项目兼容虚拟主机环境，采用 Bootstrap 框架打造简洁易用的界面。

### 主要特点
- **AJAX 技术**：无刷新聊天，提升用户体验。
- **虚拟主机兼容**：适用于大多数共享主机环境。
- **简洁界面**：基于 Bootstrap，支持移动端和桌面端兼容。

---

## 环境要求

- **PHP 版本**: 8.2 或更高
- **Web 服务器**: 支持 PHP 的服务器环境（如 Apache, Nginx）

---

## 部署说明

### 1. PHP 配置要求
确保您的服务器安装了以下 PHP 扩展：

- curl
- gd
- intl
- mbstring
- sqlite3
- pdo_sqlite

### 2. Nginx 配置

如果使用 Nginx 作为 Web 服务器，请参考以下伪静态规则：

```nginx
# 处理目录请求，避免尾部斜杠
if (!-d $request_filename){
    set $rule_0 1$rule_0;
}
if ($rule_0 = "1"){
    rewrite ^/(.+)/$ /$1 permanent;
}

# 处理文件请求
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

---

## 已实现的功能

- ✅ 完整的后台管理功能
- ✅ 文件上传功能
- ✅ 上传文件预览功能

---

## 参与贡献

欢迎开发者参与贡献！以下是贡献流程：

1. **Fork 本仓库**：点击页面右上角的 Fork 按钮。
2. **创建新分支**：在本地创建一个新的分支。
3. **提交代码更改**：进行代码修改并提交到您的分支。
4. **提交 Pull Request**：将您的修改提请求合并到主仓库。

---

## 联系方式

如有问题或建议，请通过以下方式联系：

- 电子邮件: [q1432777209@126.com](mailto:q1432777209@126.com)
- GitHub Issues: [提交问题](https://github.com/XiaoFeng-QWQ/zicheng-web-chat-room/issues)

---

感谢您的使用与支持！🎉