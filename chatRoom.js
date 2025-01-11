(function ($) {
    $.fn.ZichenChatRoom = function (options) {
        const settings = $.extend({
            apiUrl: 'http://127.0.0.1', // API URL
        }, options);

        const chatbox = $('<div id="ZichenChatRoomHtmlPluginChatbox">').html(`
        <style>
            #ZichenChatRoomHtmlPluginChatbox {
                max-width: 330px;
                height: 420px;
                border: 2px solid #ccc;
                background-color: #f9f9f9;
                position: fixed;
                top: 0;
                left: 0;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
                z-index: 1000;
                margin: 10px;
                transition: all 0.3s linear;
            }
            #ZichenChatRoomHtmlPluginChatbox .chatbox-header {
                box-shadow: 0 2px 3px rgba(0, 0, 0, 0.1);
                background: rgba(255, 255, 255, 0.2);
                backdrop-filter: blur(2.5px);
                padding: 10px;
                user-select: none;
            }
            #ZichenChatRoomHtmlPluginChatbox .chatbox-body {
                max-height: 88%;
                overflow-y: scroll;
                overflow-x: hidden;
                height: calc(100% - 70px);
            }
            #ZichenChatRoomHtmlPluginChatbox .chatbox-body .message-content {
                padding: 0.75rem 1rem;
                margin: 10px;
                border-radius: 0.75rem;
                word-wrap: break-word;
                background-color: #f1f1f1;
            }
            #ZichenChatRoomHtmlPluginChatbox img {
                max-height: 100%;
                max-width: 100%;
            }
            #ZichenChatRoomHtmlPluginChatbox .chatbox-footer {
                display: flex;
                position: absolute;
                width: 100%;
                flex-direction: column;
            }
            #ZichenChatRoomHtmlPluginChatbox.collapsed .chatbox-body,
            #ZichenChatRoomHtmlPluginChatbox.collapsed .chatbox-footer {
                display: none;
            }
            #ZichenChatRoomHtmlPluginChatbox.collapsed {
                height: 40px;
            }
            #ZichenChatRoomHtmlPluginChatbox.collapsed .scrollToBottom,
            #ZichenChatRoomHtmlPluginChatbox.collapsed a {
                display: none;
            }
            #ZichenChatRoomHtmlPluginChatbox .chatbox-body,
            #ZichenChatRoomHtmlPluginChatbox .chatbox-footer {
                opacity: 1;
            }
            #ZichenChatRoomHtmlPluginChatbox .toggleChatbox,
            #ZichenChatRoomHtmlPluginChatbox .scrollToBottom {
                background-color: transparent;
                border: none;
                cursor: pointer;
                font-size: 16px;
                transition: transform 0.2s;
            }
            #ZichenChatRoomHtmlPluginChatbox .message-input-container {
                display: flex;
            }
            #ZichenChatRoomHtmlPluginChatbox .message-input-container input {
                width: 80%;
                padding: 5px;
                border: 0px solid #ccc;
                height: 18px;
            }
            #ZichenChatRoomHtmlPluginChatbox .message-input-container button {
                border: 1px solid #ccc;
                background-color: #4CAF50;
                color: white;
                cursor: pointer;
            }
        </style>
        <div class="chatbox-header">
            <span>聊天室</span>
            <button class="toggleChatbox">折叠</button>
            <a href="https://chat.zicheng.icu/" target="_blank" rel="noopener noreferrer">完整体验地址</a>
            <button class="scrollToBottom">返回底部</button>
        </div>
        <div class="chatbox-body"><span class="loading">加载中……</span></div>
        <div class="chatbox-footer">
            <div class="message-input-container">
                <input type="text" class="message-input" placeholder="请输入消息..." />
                <button class="sendMessage">发送</button>
            </div>
        </div>`);

        $('body').append(chatbox);

        const tokenManage = function (method) {
            if (method === 'set') {
                Cookies.set('ZichenChatRoomHtmlPlugin_Token', chatbox.find('.message-input').val(), { expires: 365 });
            } else {
                return Cookies.get('ZichenChatRoomHtmlPlugin_Token');
            }
        };

        const appendMessage = function (content, alignment) {
            chatboxBody.append(`<div class="message-content" style="text-align:${alignment};">${content}</div>`);
            scrollToBottom();
        };

        const scrollToBottom = function () {
            chatboxBody.scrollTop(chatboxBody[0].scrollHeight);
        };

        const loadMessages = function () {
            $.ajax({
                type: "POST",
                url: `${settings.apiUrl}/api/v1/chat/get?offset=${offset}`,
                data: {
                    token: token
                },
                dataType: "JSON",
                success: function (response) {
                    if (response.code === 200) {
                        tokenIsTrue = true;
                        populateMessages(response.data.messages);
                    } else if (response.code === 401) {
                        appendMessage('请在下方文本框输入token并点发送后刷新网页！', 'center');
                    } else {
                        appendMessage(`无法加载聊天记录${response.message}`, 'center');
                    }
                },
                error: function (xhr) {
                    appendMessage(`无法加载聊天记录 ${xhr.status}`, 'center');
                }, 
                complete: function (xhr) {
                    chatbox.find('.loading').fadeOut();
                }
            });
        };

        const sendMessage = function () {
            $.ajax({
                type: "POST",
                url: `${settings.apiUrl}/api/v1/chat/send`,
                data: {
                    token: token,
                    message: chatbox.find('.message-input').val()
                },
                dataType: "JSON",
                success: function (response) {
                    if (response.code === 200) {
                        loadMessages();
                    } else {
                        appendMessage(`无法发送消息 ${response.message}`, 'center');
                    }
                },
                error: function (xhr) {
                    appendMessage(`无法发送消息 ${xhr.status}`, 'center');
                }
            });
        };

        const populateMessages = function (messages) {
            if (messages.length > 0) {
                offset += messages.length;
                messages.forEach(({ type, content, user_name }) => {
                    let alignment = type === "user" ? "left" : "right";
                    let userDisplayName = type === "user" ? user_name : "系统";
                    appendMessage(`${userDisplayName}:${content}`, alignment);
                });
            }
        };

        const chatboxBody = chatbox.find('.chatbox-body');
        let token = tokenManage();
        let tokenIsTrue = false;
        let offset = 0;

        chatbox.find('.toggleChatbox').click(function () {
            chatbox.toggleClass('collapsed');
        });

        chatbox.find('.scrollToBottom').click(scrollToBottom);

        chatbox.find('.sendMessage').click(function () {
            if (tokenIsTrue) {
                sendMessage();
            } else {
                tokenManage('set');
            }
        });

        loadMessages();
        setInterval(loadMessages, 3000);
    };
})(jQuery);