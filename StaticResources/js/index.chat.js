let isUserScrolling = false;
let offset = 0;
let lastFetched = null; // 用于存储上次获取消息的时间戳
let lastScrollTop = 0; // 初始化上一次滚动位置
let loadingMessages = false; // 是否正在加载消息
// 创建通知音频对象
const notificationSound = new Audio('/StaticResources/media/Windows10 Notify System Generic.wav');
const errorNotificationSound = new Audio('/StaticResources/media/Windows10 Foreground.wav')

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
 * 将用户输入的文本转为适当的HTML，保留换行和空格
 * @param {string} text - 用户输入的文本
 * @returns {string} - 转换后的HTML
 */
function preserveTextFormat(text) {
    return text.replace(/\n/g, '<br>');
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
    const formattedMessageContent = preserveTextFormat(message.content);

    switch (message.type) {
        case 'system':
            messageClass = 'alert alert-info system-msg';
            contentHtml = `
                <p>${formattedMessageContent}</p>
                <span class="timestamp">${message.created_at}</span>
            `;
            break;
        case 'warning':
            messageClass = 'alert alert-warning system-msg';
            contentHtml = `
                <p>${formattedMessageContent}</p>
                <span class="timestamp">${message.created_at}</span>
            `;
            break;
        case 'error':
            messageClass = 'alert alert-danger system-msg';
            contentHtml = `
                <p>${formattedMessageContent}</p>
                <span class="timestamp">${message.created_at}</span>
            `;
            break;
        case 'info':
            messageClass = 'alert alert-primary system-msg';
            contentHtml = `
                <p>${formattedMessageContent}</p>
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
                    <p>${formattedMessageContent}</p>
                    <span class="timestamp">${message.created_at}</span>
                </div>
            `;
            break;
    }

    playNotificationSound(message, isSelf)

    return `
    <div class="${messageClass} ${alignmentClass ?? ''}">
        <div class="message-content">
            ${contentHtml}
        </div>
    </div>
    `;
}
/**
 * 播放通知音效
 * @param {object} message 消息对象
 * @param {boolean} isSelf 是否为自己发送的消息
 */
function playNotificationSound(message, isSelf) {
    if (!isSelf) {
        if (message.type === 'user') {
            notificationSound.currentTime = 0; // 从头播放
            notificationSound.play().catch(error => console.log("播放音频失败:", error));
        } else {
            errorNotificationSound.currentTime = 0; // 从头播放
            errorNotificationSound.play().catch(error => console.log("播放音频失败:", error));
        }
    }
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
            const isAtBottom = Math.ceil(scrollPosition) >= previousHeight - 5; // 增加一个容差

            if (Array.isArray(response.messages)) {
                response.messages.forEach(message => {
                    const isSelf = message.user_name === sessionUsername;
                    chatBox.append(displayMessage(message, isSelf));
                });

                if (response.messages.length > 0) {
                    offset += response.messages.length;
                    lastFetched = response.messages[response.messages.length - 1].created_at;
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
 * @param {File} imageFile 图片文件
 */
function sendMessage(message, imageFile) {
    // 创建 FormData 对象
    let formData = new FormData();
    formData.append('message', message);
    const chatBox = $('#chat-box');

    // 如果有图片文件，添加到 formData
    if (imageFile) {
        formData.append('image', imageFile);
    }

    // 禁用发送按钮
    $('#send-button').attr('disabled', true);

    $.ajax({
        url: '/api/chat',
        type: 'POST',
        contentType: false, // 让 jQuery 不设置 contentType
        processData: false, // 让 jQuery 不处理 data
        data: formData,
        success: function (response) {
            if (response.status === 'success') {

                if (response.isCommnd) {
                    chatBox.append(displayMessage({
                        type: 'system',
                        user_name: '系统',
                        content: `${response.message}`,
                        created_at: new Date()
                    }, false));
                    if (!isUserScrolling) {
                        scrollToBottom(); // 若用户未滚动，则自动滚动到底部
                    } else {
                        $('#scroll-down-button').show(); // 若用户正在滚动，显示按钮
                    }
                    return;
                }

                loadChatMessages(); // 发送成功后重新加载聊天记录
                $('#message').val(''); // 清空文本框
                $('#select-image-file').html(''); // 清空图片预览
                $('#image').val(''); // 清空文件输入框
                if (!isUserScrolling) {
                    scrollToBottom(); // 若用户未滚动，则自动滚动到底部
                } else {
                    $('#scroll-down-button').show(); // 若用户正在滚动，显示按钮
                }
            } else {
                chatBox.append(displayMessage({
                    type: 'warning',
                    user_name: '系统',
                    content: response.message,
                    created_at: new Date()
                }, false));
                scrollToBottom();
            }
            loadingMessages = false;
        },
        error: function () {
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
        const previousHeight = chatBox[0].scrollHeight;
        const scrollPosition = chatBox.scrollTop() + chatBox.outerHeight();
        const isAtBottom = Math.ceil(scrollPosition) >= previousHeight - 5; // 增加一个容差

        if (scrollTop < lastScrollTop) {
            isUserScrolling = true;
            $('#scroll-down-button').show();
        } else if (isAtBottom) {
            isUserScrolling = false;
            $('#scroll-down-button').hide();
        }

        lastScrollTop = scrollTop; // 更新上一次滚动位置
    });

    $('#select-image').click(function () {
        $('#image').click(); // 触发隐藏的文件选择器
        $(this).hide(); // 隐藏选择图片按钮
    });

    $('#image').change(function (e) {
        const file = e.target.files[0];
        if (file) {
            const imageTag = `
                <div class="image-preview-wrapper position-relative">
                    <a href="${URL.createObjectURL(file)}" data-fancybox>
                        <img id="previewImage" src="${URL.createObjectURL(file)}" alt="Selected Image" style="max-width: 50%; max-height: 50%; cursor: pointer;">
                    </a>
                    <button type="button" id="remove-image" class="btn btn-danger btn-sm position-absolute" style="margin-left: 5px;bottom: 0;">取消</button>
                </div>`;

            // 显示图片在 select-image-file 区域
            const imagePreviewContainer = $('#select-image-file');
            imagePreviewContainer.html(imageTag); // 替换之前的图片

            // 绑定取消按钮的事件
            $('#remove-image').click(function () {
                $('#image').val(''); // 清空文件输入框
                imagePreviewContainer.html(''); // 移除图片预览
                $('#select-image').show(); // 显示图片选择按钮
            });
        }
    });

    $('#chat-form').submit(function (event) {
        event.preventDefault(); // 阻止表单默认提交

        const message = $('#message').val();
        const imageFile = $('#image')[0].files[0];

        // 根据用户的输入和选择决定发送的内容
        if (message.trim() || imageFile) {
            sendMessage(message, imageFile);
        } else {
            alert("请输入消息或选择图片。");
        }
        $('#select-image').show(); // 显示图片选择按钮
    });
    // 添加对 Ctrl+Enter 快捷键的监听
    $('#message').keydown(function (event) {
        if (event.ctrlKey && event.key === 'Enter') {
            event.preventDefault(); // 阻止默认换行动作
            $('#chat-form').submit();
        }
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