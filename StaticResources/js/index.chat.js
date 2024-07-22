let isUserScrolling = false;
let offset = 0;
const limit = 20;
let lastFetched = null; // 用于存储上次获取消息的时间戳
let lastScrollTop = 0; // 初始化上一次滚动位置
let loadingMessages = false; // 是否正在加载消息

/**
 * 隐藏加载指示器
 */
function hideLoading() {
    $('#loading').hide();
}

/**
 * 生成文字头像
 * @param {string} name 用户名
 * @param {number} size 尺寸
 * @param {string} customColor 自定义颜色
 * @returns {string} Base64 格式的图片数据
 */
function letterAvatar(name, size = 60, customColor) {
    const colors = [
        "#1abc9c", "#2ecc71", "#3498db", "#9b59b6", "#34495e",
        "#16a085", "#27ae60", "#2980b9", "#8e44ad", "#2c3e50",
        "#f1c40f", "#e67e22", "#e74c3c", "#00bcd4", "#95a5a6",
        "#f39c12", "#d35400", "#c0392b", "#bdc3c7", "#7f8c8d"
    ];

    const text = (name || "?").split(" ");
    const initials = text.length === 1 ? text[0].charAt(0) : text[0].charAt(0) + text[1].charAt(0);

    const pixelRatio = window.devicePixelRatio || 1;
    const canvasSize = size * pixelRatio;
    const canvas = document.createElement("canvas");
    canvas.width = canvasSize;
    canvas.height = canvasSize;

    const context = canvas.getContext("2d");
    const textColorIndex = (initials === "?" ? 72 : initials.charCodeAt(0) - 64) % colors.length;
    context.fillStyle = customColor || colors[textColorIndex];
    context.fillRect(0, 0, canvas.width, canvas.height);

    context.font = `${Math.round(canvas.width / 2)}px 'Microsoft Yahei'`;
    context.textAlign = "center";
    context.fillStyle = "#FFF";
    context.fillText(initials, canvas.width / 2, canvas.height / 1.5);

    return canvas.toDataURL();
}

/**
 * 构造消息 HTML
 * @param {object} message 消息对象
 * @param {boolean} isSelf 是否为自己发送的消息
 * @returns {string} 消息 HTML 字符串
 */
function displayMessage(message, isSelf) {
    let alignmentClass;
    let messageClass;
    let contentHtml;

    switch (message.type) {
        case 'system':
            alignmentClass = 'text-center';
            messageClass = 'alert alert-info system-msg';
            contentHtml = `
                <p>${message.content}</p>
                <span class="timestamp">${message.created_at}</span>
            `;
            break;
        case 'warning':
            alignmentClass = 'text-center';
            messageClass = 'alert alert-warning system-msg';
            contentHtml = `
                <p>${message.content}</p>
                <span class="timestamp">${message.created_at}</span>
            `;
            break;
        case 'error':
            alignmentClass = 'text-center';
            messageClass = 'alert alert-danger system-msg';
            contentHtml = `
                <p>${message.content}</p>
                <span class="timestamp">${message.created_at}</span>
            `;
            break;
        case 'info':
            alignmentClass = 'text-center';
            messageClass = 'alert alert-primary system-msg';
            contentHtml = `
                <p>${message.content}</p>
                <span class="timestamp">${message.created_at}</span>
            `;
            break;
        default:
            alignmentClass = isSelf ? 'right' : 'left';
            messageClass = 'chat-message';
            const userGroup = message.group_name ? `<span class="user-group">(${message.group_name})</span>` : '';

            const avatar = message.avatar_url
                ? `<img src="${message.avatar_url}" alt="avatar" class="avatar">`
                : `<img src="${letterAvatar(message.user_name)}" alt="avatar" class="avatar">`;

            const username = `<span class="username">
                ${isSelf ? '' : avatar} ${message.user_name} ${isSelf ? avatar : ''} ${userGroup}
            </span>`;

            contentHtml = `
                <div class="message-text">
                    ${username}
                    <p>${message.content}</p>
                    <span class="timestamp">${message.created_at}</span>
                </div>
            `;
            break;
    }

    return `
    <div class="${messageClass} ${alignmentClass}">
        <div class="message-content">
            ${contentHtml}
        </div>
    </div>
    `;
}

/**
 * 滚动至底部
 */
function scrollToBottom() {
    const chatBox = $('#chat-box');
    chatBox.scrollTop(chatBox[0].scrollHeight);
}

/**
 * 加载聊天消息
 */
function loadChatMessages() {
    if (loadingMessages) return;
    loadingMessages = true;

    $.ajax({
        url: '/api/chat',
        type: 'GET',
        data: { offset: offset },
        dataType: 'json',
        success: function (response) {
            const chatBox = $('#chat-box');
            const previousHeight = chatBox[0].scrollHeight;
            const scrollPosition = chatBox.scrollTop() + chatBox.outerHeight();
            const isAtBottom = Math.ceil(scrollPosition) >= previousHeight - 10; // 增加一个容差

            if (Array.isArray(response)) {
                response.forEach(message => {
                    const isSelf = message.user_name === sessionUsername;
                    chatBox.append(displayMessage(message, isSelf));
                });

                if (response.length > 0) {
                    offset += response.length;
                    lastFetched = response[response.length - 1].created_at;
                }
            } else {
                chatBox.append(displayMessage({
                    type: 'error',
                    content: `加载聊天记录失败${JSON.stringify(response)}请联系管理员。`,
                    created_at: new Date()
                }, 'error', false));
                scrollToBottom();
            }

            if (isAtBottom) {
                chatBox.css('scroll-behavior', 'auto'); // 关闭平滑滚动，确保页面即时跳转到底部
                scrollToBottom();
                chatBox.css('scroll-behavior', 'smooth'); // 恢复平滑滚动
                hideLoading(); // 只有在滚动到底部时隐藏加载中的元素
            } else {
                $('#scroll-down-button').show();
            }

            loadingMessages = false;
        },

        error: function (xhr) {
            console.error(xhr);
            const chatBox = $('#chat-box');
            chatBox.append(displayMessage({
                type: 'error',
                content: `加载聊天记录失败 <br> ${xhr.responseText} <br> ${xhr.statusText}`,
                created_at: new Date()
            }, 'error', false));
            scrollToBottom();
            loadingMessages = false;
        }
    });
}

/**
 * 发送消息
 * @param {string} message 消息内容
 */
function sendMessage(message) {
    // 截取超过 256 个字符的部分
    if (message.length > 256) {
        message = message.substring(0, 256);
    }

    // 禁用发送按钮
    $('#send-button').attr('disabled', true);

    $.ajax({
        url: '/api/chat',
        type: 'POST',
        contentType: 'application/x-www-form-urlencoded',
        data: { message: message },
        success: function (response) {
            if (response.status === 'success') {
                loadChatMessages(); // 发送成功后重新加载聊天记录
                if (!isUserScrolling) {
                    scrollToBottom(); // 若用户未滚动，则自动滚动到底部
                } else {
                    $('#scroll-down-button').show(); // 若用户正在滚动，显示按钮
                }
            } else {
                const chatBox = $('#chat-box');
                chatBox.append(displayMessage({
                    type: 'warning',
                    content: response.message,
                    created_at: new Date()
                }, false));
                scrollToBottom();
            }
        },
        error: function () {
            const chatBox = $('#chat-box');
            chatBox.append(displayMessage({
                type: 'error',
                user_name: '系统',
                content: '发送消息失败，请稍后再试。',
                created_at: new Date()
            }, false));
            scrollToBottom();
        },
        complete: function () {
            // 启用发送按钮
            $('#send-button').attr('disabled', false);
        }
    });
}

/**
 * 绑定事件监听器
 */
function bindEventListeners() {
    $('#chat-box').on('scroll', function () {
        const chatBox = $(this);
        const scrollTop = chatBox.scrollTop();
        const isAtBottom = scrollTop + chatBox.outerHeight() >= chatBox[0].scrollHeight;

        if (scrollTop < lastScrollTop) {
            isUserScrolling = true;
            $('#scroll-down-button').show();
        } else if (isAtBottom) {
            isUserScrolling = false;
            $('#scroll-down-button').hide();
        }

        lastScrollTop = scrollTop; // 更新上一次滚动位置
    });

    $('#chat-form').on('submit', function (event) {
        event.preventDefault();
        const messageInput = $('#message');
        let message = messageInput.val();
        if (message.trim() === '') return;
        messageInput.val(""); // 清空输入框
        sendMessage(message);
    });

    $('#scroll-down-button').on('click', function () {
        scrollToBottom();
        isUserScrolling = false;
        $(this).hide();
    });

    $('#logout').on('click', function () {
        const logoutModal = new bootstrap.Modal($('#logoutModal'), {});
        logoutModal.show();
    });

    $('#confirmLogout').on('click', function () {
        $.ajax({
            url: '/user/logout',
            type: 'POST',
            success: function () {
                window.location.href = '/user/login';
            },
            error: function () {
                alert('离开聊天室失败，请稍后再试。');
            }
        });
    });
}

// 绑定事件监听器只调用一次
bindEventListeners();

loadChatMessages(); // 初始加载聊天记录
setInterval(loadChatMessages, 3000); // 开始轮询，每 3 秒钟一次