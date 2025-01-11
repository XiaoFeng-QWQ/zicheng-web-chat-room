<center>
    <h1>简易聊天室插件</h1>
    <img height="400px" src="logo.png"></img>
</center>

这是一个可以嵌入网页的小型聊天室插件该插件允许用户在小窗口内接收、发送消息。

## 功能

- **实时聊天**：即可通过API接口实时接收消息。
- **可折叠聊天窗**：支持展开和折叠功能，方便用户使用。

## 使用说明

### 先决条件
下载安装PHP程序：https://github.com/XiaoFeng-QWQ/zicheng-web-chat-room/

### 使用
```html
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="js.cookie.min.js"></script>
<script>
    $(document).ready(function () {
        $().ZichenChatRoom({
            apiUrl: 'http://127.0.0.1'
        });
    });
</script>
```