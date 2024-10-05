<center>
    <h1>简易聊天室插件</h1>
    <img src="logo.svg"></img>
</center>

这是一个可以嵌入网页的小型聊天室插件，使用HTML、CSS和JavaScript（依赖jQuery）开发。该插件允许用户在小窗口内接收消息。

## 功能

- **实时聊天**：即可通过API接口实时接收消息。
- **可折叠聊天窗**：支持展开和折叠功能，方便用户使用。
- **自适应设计**：在移动设备上默认设置最大宽度与高度，并根据屏幕尺寸进行调整。

## 使用说明

### 先决条件

- 下载安装PHP程序：https://github.com/XiaoFeng-QWQ/zicheng-web-chat-room/
- 将代码中的`const apiUrl`值替换为你的域名。

### 引入jQuery

请确保在HTML文件中引入最新版本的jQuery：

```html
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
```

### 插件集成

在HTML页面中插入以下JavaScript代码：

```html
<script>
function ChatHtml() {
    var container = document.createDocumentFragment();
    var e_0 = document.createElement("div");
    var e_1 = document.createElement("style");
    e_1.appendChild(document.createTextNode("/* 插件样式代码 */"));
    e_0.appendChild(e_1);
    var e_2 = document.createElement("div");
    e_2.setAttribute("class", "collapsed");
    e_2.setAttribute("id", "ZichenChatRoomHtmlPluginChatbox");
    var e_3 = document.createElement("div");
    e_3.setAttribute("class", "chatbox-header");
    e_3.appendChild(document.createTextNode("\n简易聊天室\n"));
    var e_4 = document.createElement("a");
    e_4.setAttribute("href", "https://chat.zicheng.icu/");
    e_4.setAttribute("target", "_blank");
    e_4.setAttribute("rel", "noopener noreferrer");
    e_4.appendChild(document.createTextNode("完整体验地址"));
    e_3.appendChild(e_4);
    var e_5 = document.createElement("button");
    e_5.setAttribute("class", "scrollToBottom");
    e_5.appendChild(document.createTextNode("返回底部"));
    e_3.appendChild(e_5);
    var e_6 = document.createElement("button");
    e_6.setAttribute("class", "toggleChatbox");
    e_6.appendChild(document.createTextNode("折叠"));
    e_3.appendChild(e_6);
    e_2.appendChild(e_3);
    var e_7 = document.createElement("div");
    e_7.setAttribute("class", "chatbox-body");
    e_2.appendChild(e_7);
    e_0.appendChild(e_2);
    container.appendChild(e_0);
    return container;
}
document.body.appendChild(ChatHtml());

const apiUrl = 'https://chat.zicheng.icu';
const chatbox = $("#ZichenChatRoomHtmlPluginChatbox");
const chatboxBody = chatbox.find(".chatbox-body");
let offset = 0, isCollapsed = true, firstLoad = true;

chatbox.find(".toggleChatbox").click(() => chatbox.toggleClass("collapsed"));
chatbox.find(".scrollToBottom").click(scrollToBottom);

function sendMessage(message) {
    if (!(message = message.trim())) return;
    $.post(`${apiUrl}/api/chat`, { message })
        .done(response => appendMessage(response.status === "success" ? message : "消息发送失败", "end"))
        .fail(() => appendMessage("消息发送失败", "end"));
}

function appendMessage(content, alignment) {
    chatboxBody.append(`<div class="message-content" style="text-align:${alignment};">${content}</div>`);
    scrollToBottom();
}

function scrollToBottom() {
    chatboxBody.scrollTop(chatboxBody[0].scrollHeight);
}

function loadMessages() {
    $.get(`${apiUrl}/api/chat`, { offset })
        .done(data => populateMessages(data.messages))
        .fail(() => appendMessage("无法加载聊天记录", "center"));
}

function populateMessages(messages) {
    if (messages.length > 0) {
        offset += messages.length;
        messages.forEach(({ type, content, user_name }) => {
            let alignment = type === "user" ? "left" : "right";
            let userDisplayName = type === "user" ? user_name : "系统";
            appendMessage(`${userDisplayName}:${content}`, alignment);
        });
        if (firstLoad) {
            scrollToBottom();
            firstLoad = false;
        }
    }
}

loadMessages();
setInterval(loadMessages, 3000);
</script>
```

或者下载仓库中的`chatRoom.min.js`，上传到您的服务器，修改`const apiUrl`值后引入！

如有自定义需求，请相应调整JavaScript代码中的样式或功能。希望这个简易聊天室插件可以给您带来便利。如有任何问题或建议，请随时反馈！