$(function () {
    $.contextMenu({
        selector: '.chat-message',
        callback: function (key, options) {
            const targetElement = options.$trigger;
            console.log('Menu:');
            console.log(targetElement);
            console.log($(targetElement).attr('data-msg-id'));
            console.log(key);
            console.log('===========END============');
            // 发送撤回请求
            if (key === 'user.delete') {
                $.ajax({
                    url: `/api/v1/chat/delete?id=${$(targetElement).attr('data-msg-id')}`,
                    type: 'GET',
                    success: function (response) {
                        if (response.code === 200) {
                            $(`[data-msg-id="${$(targetElement).
                                attr('data-msg-id')}"]`).
                                removeClass('chat-message right active-message').
                                addClass('user-delete-msg').
                                html(`你撤回了一条消息`);
                        } else {
                            alert(response.message || '撤回失败，请稍后重试');
                        }
                    },
                    error: function () {
                        alert('请求失败，请检查网络或稍后再试');
                    }
                });
            } else {
                // 设为精华
            }
        },
        items: {
            "user.delete": {
                name: "撤回",
                icon: "delete"
            },
            "admin.essential": {
                name: "设为精华(未实现)",
                icon: "edit"
            },
            "sep1": "---------",
            "quit": {
                name: "退出", icon: function () {
                    return 'context-menu-icon context-menu-icon-quit';
                }
            }
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
                    },
                    "sep1": "---------",
                    "quit": {
                        name: "退出", icon: function () {
                            return 'context-menu-icon context-menu-icon-quit';
                        }
                    }
                }
            });
        }
    });
});
