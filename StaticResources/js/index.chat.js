let isUserScrolling = false;
let offset = 0;
const limit = 20;
// 用于存储上次获取消息的时间戳
let lastFetched = null;
// 初始化上一次滚动位置
let lastScrollTop = 0;
// 是否正在加载消息
let loadingMessages = false;

/**
 * 生成文字头像
 * @param {*} name 
 * @param {*} size 
 * @param {*} customColor 
 * @returns 
 */
function letterAvatar(name, size = 60, customColor) {
    // 颜色数组
    const colors = [
        "#1abc9c", "#2ecc71", "#3498db", "#9b59b6", "#34495e",
        "#16a085", "#27ae60", "#2980b9", "#8e44ad", "#2c3e50",
        "#f1c40f", "#e67e22", "#e74c3c", "#00bcd4", "#95a5a6",
        "#f39c12", "#d35400", "#c0392b", "#bdc3c7", "#7f8c8d"
    ];

    // 获取字母
    const text = (name || "?").split(" ");
    const initials = text.length === 1 ? text[0].charAt(0) : text[0].charAt(0) + text[1].charAt(0);

    // Canvas 尺寸响应设备像素比率
    const pixelRatio = window.devicePixelRatio || 1;
    const canvasSize = size * pixelRatio;

    // 创建 Canvas
    const canvas = document.createElement("canvas");
    canvas.width = canvasSize;
    canvas.height = canvasSize;

    const context = canvas.getContext("2d");

    // 背景颜色, 使用 customColor 优先，其次从 colors 选择
    const textColorIndex = (initials === "?" ? 72 : initials.charCodeAt(0) - 64) % colors.length;
    context.fillStyle = customColor || colors[textColorIndex];
    context.fillRect(0, 0, canvas.width, canvas.height);

    // 设置字体等样式
    context.font = `${Math.round(canvas.width / 2)}px 'Microsoft Yahei'`;
    context.textAlign = "center";
    context.fillStyle = "#FFF";
    context.fillText(initials, canvas.width / 2, canvas.height / 1.5);

    // 返回 Base64 格式的图片数据
    return canvas.toDataURL();
}

/**
 * 构造消息HTML
 * @param {*} message 
 * @param {*} isSelf 
 * @returns 
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
            const userGroup = message.group_name
                ? `<span class="user-group">(${message.group_name})</span>`
                : '';

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

function scrollToBottom() {
    const chatBox = $('#chat-box');
    chatBox.scrollTop(chatBox[0].scrollHeight);
}

function loadChatMessages() {
    if (loadingMessages) return; // 阻止函数在执行未完成时再被执行
    loadingMessages = true;

    $.ajax({
        url: '/api/chat',
        type: 'GET',
        data: {
            offset: offset,
        },
        dataType: 'json',
        success: function (response) {
            $('#loading').hide();
            const chatBox = $('#chat-box');
            const previousHeight = chatBox[0].scrollHeight;
            const wasAtBottom = chatBox.scrollTop() + chatBox.outerHeight() >= previousHeight;

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
                scrollToBottom()
            }

            if (!isUserScrolling) {
                scrollToBottom();
            } else if (wasAtBottom) {
                $('#scroll-down-button').show();
            }

            loadingMessages = false; // 解除锁定
        },
        error: function (xhr) {
            console.error(xhr)
            $('#loading').hide();
            const chatBox = $('#chat-box');
            chatBox.append(displayMessage({
                type: 'error',
                content: `加载聊天记录失败 <br> ${xhr.responseText} <br> ${xhr.statusText}`,
                created_at: new Date()
            }, 'error', false));
            scrollToBottom();

            loadingMessages = false; // 解除锁定
        }
    });
}

function sendMessage(message) {
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
                    created_at: new Date().toLocaleTimeString()
                }, false)); // isSelf set to false for warnings
                scrollToBottom()
            }
        },
        error: function () {
            const chatBox = $('#chat-box');
            chatBox.append(displayMessage({
                type: 'error',
                user_name: '系统',
                content: '发送消息失败，请稍后再试。',
                created_at: new Date().toLocaleTimeString()
            }, false)); // isSelf set to false for errors
            scrollToBottom()
        }
    });
}

function bindEventListeners() {
    $('#chat-box').on('scroll', function () {
        const chatBox = $(this);
        const scrollTop = chatBox.scrollTop();

        if (scrollTop < lastScrollTop) { // 检查滚动是否为向上滚动
            isUserScrolling = true;
            $('#scroll-down-button').show();
        } else if (scrollTop + chatBox.outerHeight() >= chatBox[0].scrollHeight) {
            isUserScrolling = false;
            $('#scroll-down-button').hide();
        }

        lastScrollTop = scrollTop; // 更新上一次滚动位置
    });

    $('#chat-form').on('submit', function (event) {
        event.preventDefault();
        const messageInput = $('#message');
        const message = messageInput.val();
        if (message.trim() === '') return; // 如果消息为空，直接返回
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

// 只调用一次
bindEventListeners();

loadChatMessages(); // 初始加载聊天记录
// 开始轮询
setInterval(loadChatMessages, 3000);