$(function () {
    // 消息右键菜单
    $.contextMenu({
        selector: '.chat-message',
        callback: function (key, options) {
            const targetElement = options.$trigger;
            const messageId = $(targetElement).attr('data-msg-id');
            const messageContentText = $(targetElement).find('.message-content-text').text().trim();

            switch (key) {
                case 'user.reply':
                    // 触发引用消息事件
                    $(document).trigger('message:reply', [messageId]);
                    break;

                case 'user.delete':
                    // 发送撤回请求
                    $.ajax({
                        url: `/api/v1/chat/delete?id=${messageId}`,
                        type: 'GET',
                        success: function (response) {
                            if (response.code !== 200) {
                                alert(response.message || '撤回失败，请稍后重试');
                            }
                        },
                        error: function () {
                            alert('请求失败，请检查网络或稍后再试');
                        }
                    });
                    break;

                case 'user.copy':
                    // 复制消息内容到剪贴板
                    navigator.clipboard.writeText(messageContentText).then(() => {
                        console.log('内容已复制到剪贴板');
                    }).catch(err => {
                        console.error('无法复制内容: ', err);
                        // 回退方案
                        const textarea = document.createElement('textarea');
                        textarea.value = messageContentText;
                        document.body.appendChild(textarea);
                        textarea.select();
                        document.execCommand('copy');
                        document.body.removeChild(textarea);
                    });
                    break;
            }
        },
        items: {
            "user.reply": {
                name: "引用",
                icon: "fa-reply"
            },
            "user.copy": {
                name: "复制",
                icon: "fa-copy"
            },
            "sep1": "---------",
            "user.delete": {
                name: "撤回",
                icon: "fa-trash"
            }
        }
    });

    // 空白区域右键菜单
    $(document).on('contextmenu', function (e) {
        if (!$(e.target).closest('.chat-message').length) {
            $.contextMenu({
                selector: '#chat-box',
                callback: function (key) {
                    switch (key) {
                        case 'clear':
                            $('#chat-box').html('');
                            break;

                        case 'export':
                            // 导出聊天记录
                            exportChatHistory();
                            break;
                    }
                },
                items: {
                    "export": {
                        name: "导出聊天记录",
                        icon: "fa-download"
                    },
                    "clear": {
                        name: "清屏",
                        icon: "fa-eraser"
                    }
                }
            });
        }
    });

    // 导出聊天记录函数
    function exportChatHistory() {
        try {
            const messages = [];
            $('.chat-message').each(function () {
                messages.push({
                    time: $(this).find('.message-time').text(),
                    sender: $(this).find('.message-sender').text(),
                    content: $(this).find('.message-content').text().trim()
                });
            });

            if (messages.length === 0) {
                alert('没有聊天记录可导出');
                return;
            }

            // 创建导出内容
            let exportContent = "聊天记录导出\n\n";
            messages.forEach(msg => {
                exportContent += `[${msg.time}] ${msg.sender}: ${msg.content}\n`;
            });

            // 创建Blob对象并下载
            const blob = new Blob([exportContent], { type: 'text/plain;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `chat_history_${new Date().toISOString().slice(0, 10)}.txt`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

        } catch (error) {
            console.error('导出聊天记录失败:', error);
            alert('导出聊天记录失败，请重试');
        }
    }
});