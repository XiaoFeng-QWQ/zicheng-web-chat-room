$(function () {
    $.contextMenu({
        selector: '.chat-message',
        callback: function (key, options) {
            const targetElement = options.$trigger;
            // 发送撤回请求
            if (key === 'user.delete') {
                $.ajax({
                    url: `/api/v1/chat/delete?id=${$(targetElement).attr('data-msg-id')}`,
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
            } else if (key === 'user.reply') {
                // 触发引用消息事件
                $(document).trigger('message:reply', [targetElement.attr('data-msg-id')]);
            }
        },
        items: {
            "user.reply": {
                name: "引用",
                icon: "fa-reply"
            },
            "sep1": "---------",
            "user.delete": {
                name: "撤回",
                icon: "delete"
            },
        }
    });

    // 为其他区域（即非 .chat-message 元素）
    $(document).on('contextmenu', function (e) {
        if (!$(e.target).closest('.chat-message').length) {
            $.contextMenu({
                selector: '#chat-box',
                callback: function (key) {
                    if (key === 'clear') {
                        $('#chat-box').html('');
                    }
                },
                items: {
                    "clear": {
                        name: "清屏",
                        icon: "fa-eraser"
                    }
                }
            });
        }
    });
});
