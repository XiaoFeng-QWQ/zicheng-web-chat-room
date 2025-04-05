let isUserScrolling = false;
let offset = 0;
let eventOffset = 0;
let eventCount = 0;
let lastFetched = null;
let lastScrollTop = 0;
let loadingMessages = false;
let isAtTop = false;
let quotedMessage = null;
let onlineUsersList = []; // 存储在线用户列表
const messageInput = $('#message');
const messagesPerPage = 40;
const chatBox = $('#chat-box');
const fileInput = $('#file');
const filePreview = $('#select-file-preview');
const filePreviewModal = new bootstrap.Modal(document.getElementById('filePreviewModal'));
const filePreviewContent = $('#filePreviewContent');
const scrollToBottom = () => chatBox.scrollTop(chatBox[0].scrollHeight);

$.ajax({
    type: "GET",
    url: "/api/v1/event/count",
    dataType: "json",
    success: function (response) {
        if (response.code === 200) {
            eventCount = response.data.count;
        } else {
            displayErrorMessage('获取事件数量失败。');
        }
    },
    error: function (xhr) {
        console.error(xhr);
        displayErrorMessage('获取事件数量失败。');
    }
});

const loadMessages = () => {
    if (loadingMessages) return;
    // 先尝试从缓存加载
    if (offset === 0) {
        const cachedMessages = loadMessagesFromCache();
        if (cachedMessages && cachedMessages.length > 0) {
            cachedMessages.forEach(msg => chatBox.append(displayMessage(msg, msg.user_name === sessionUsername)));
            offset = cachedMessages.length;
        }
    }
    loadingMessages = true;
    $.ajax({
        url: `/api/v1/chat/get?offset=${offset}&limit=${messagesPerPage}&eventOffset=${eventOffset}&eventLimit=${eventCount}`,
        type: 'GET',
        dataType: 'json',
        success: (response) => {
            loadingMessages = false;
            if (response.code === 200) {
                updateNetworkStatus(true);
                const data = response.data;
                const isAtBottom = chatBox[0].scrollHeight - chatBox[0].clientHeight <= chatBox[0].scrollTop + 1;
                const isAtTop = chatBox.scrollTop() === 0;

                if (Array.isArray(data.messages)) {
                    // 如果是第一页请求，保存到缓存
                    if (offset === 0) {
                        saveMessagesToCache(data.messages);
                    }

                    data.messages.forEach(msg => chatBox.append(displayMessage(msg, msg.user_name === sessionUsername)));
                    lastFetched = data.messages[data.messages.length - 1]?.created_at;
                    offset += data.messages.length;
                }

                if (Array.isArray(data.events)) {
                    data.events.forEach(event => parsingEvent(event));
                    eventOffset += data.events.length;
                }

                if (data.onlineUsers) {
                    updateOnlineUsers(data.onlineUsers);
                }

                if (isAtBottom) {
                    scrollToBottom();
                    $('#loading').hide();
                    chatBox.css('scroll-behavior', 'smooth');
                } else if (isAtTop) {
                    $('#scroll-down-button').show();
                }
            } else {
                displayErrorMessage('加载聊天记录失败。');
                scrollToBottom();
            }
        },
        error: (xhr) => {
            console.error(xhr);
            $('#loading').hide();
            updateNetworkStatus(false);
            loadingMessages = false;
        }
    });
};
const updateOnlineUsers = (onlineUsers) => {
    const onlineUsersListElement = $('#online-users-list');
    onlineUsersListElement.empty();
    const currentTime = Math.floor(Date.now() / 1000);
    onlineUsersList = []; // 重置列表

    let count = 0;
    for (let userId in onlineUsers) {
        count++;
        if (count >= 6) break;
        const user = onlineUsers[userId];
        if (currentTime - user.last_time < 10) {
            onlineUsersList.push(user.user_name); // 添加到列表
            const userItem = $('<li>').text(`${user.user_name}|`);
            $('#online-users-list-count').text(count);
            onlineUsersListElement.append(userItem);
        } else {
            count--;
        }
    }
};
const displayErrorMessage = (message) => {
    $('#loading').hide();
    chatBox.append(displayMessage({
        type: 'error',
        content: message,
        created_at: new Date()
    }, false));
};
const handleSelectFilePreview = (file) => {
    const fileURL = URL.createObjectURL(file);
    const fileName = file.name;
    const fileType = file.type;
    const showPreview = (content) => {
        filePreviewContent.html(content);
        $('#filePreviewFileInfo').text(fileName);
        filePreviewModal.show();
    };
    const fileExtension = fileName.split('.').pop().toLowerCase();
    const isMatch = (patterns) => patterns.some(ext => fileExtension === ext || fileType.startsWith(ext));
    switch (true) {
        case isMatch(['text']) || ['txt', 'json', 'xml', 'log'].includes(fileExtension): {
            const reader = new FileReader();
            reader.onload = e => {
                const fileContent = e.target.result;
                showPreview(`<pre><code class="language-${fileExtension}">${fileContent}</code></pre>`);
                hljs.highlightAll();
            };
            reader.readAsText(file);
            break;
        }
        case isMatch(['image']): {
            showPreview(`<img src="${fileURL}" alt="${fileName}" style="max-width: 100%; max-height: 100%; object-fit: contain;">`);
            break;
        }
        case isMatch(['officedocument']) || ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv'].includes(fileExtension): {
            showPreview(`<iframe src="https://view.officeapps.live.com/op/view.aspx?src=${encodeURIComponent(fileURL)}"></iframe>`);
            break;
        }
        case fileType === 'application/pdf' || fileExtension === 'pdf': {
            showPreview(`<iframe src="${fileURL}"></iframe>`);
            break;
        }
        case isMatch(['audio']) || ['mp3', 'wav', 'ogg', 'aac'].includes(fileExtension): {
            showPreview(`<audio playsinline controls style="max-height: 100%;"><source src="${fileURL}" type="${fileType}"></audio>`);
            new Plyr('audio');
            break;
        }
        case isMatch(['video']) || ['mp4', 'mkv'].includes(fileExtension): {
            showPreview(`<video playsinline controls style="max-height: 100%;"><source src="${fileURL}" type="${fileType}"></video>`);
            new Plyr('video');
            break;
        }
        default: {
            showPreview(`<p class="text-center"><br>暂不支持该文件类型的预览。</p>`);
        }
    }
};
// 对于上传的文件生成预览
const handleFilePreview = (path, name, type, callback) => {
    if (typeof callback !== 'function') {
        console.error('The callback is not a function');
        return;
    }
    const fileURL = path;
    const fileName = name;
    const fileType = type;
    let html = '';
    // 根据文件类型生成预览内容
    const fileExtension = fileName.split('.').pop().toLowerCase();
    const isMatch = (patterns) => patterns.some(ext => fileExtension === ext || fileType.startsWith(ext));
    // 生成下载按钮
    $('#filePreviewFileInfo').html(`
        <a href="${fileURL}" download="${fileName}">
            <button class="btn btn-sm btn-primary edit-button">下载</button>
        </a>`);

    switch (true) {
        case isMatch(['text']) || ['txt', 'json', 'xml', 'log'].includes(fileExtension): {
            // 异步加载文本文件
            $.ajax({
                type: "GET",
                url: fileURL,
                success: function (response) {
                    html = `<pre>${response}</pre>`;
                    callback(html); // 确保通过回调函数更新页面
                },
                error: function () {
                    callback('<p class="text-center">文件加载失败。</p>'); // 错误处理
                }
            });
            return; // 直接返回，防止后续代码执行
        }
        case isMatch(['image']): {
            html = `<img src="${fileURL}" alt="${fileName}" style="max-width: 100%; max-height: 100%; object-fit: contain;">`;
            callback(html);
            return;
        }
        case isMatch(['officedocument']) || ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv'].includes(fileExtension): {
            html = `<iframe src="https://view.officeapps.live.com/op/view.aspx?src=${encodeURIComponent(fileURL)}"></iframe>`;
            callback(html);
            return;
        }
        case fileType === 'application/pdf' || fileExtension === 'pdf': {
            html = `<iframe src="${fileURL}"></iframe>`;
            callback(html);
            return;
        }
        case isMatch(['audio']) || ['mp3', 'wav', 'ogg', 'aac'].includes(fileExtension): {
            html = `
                <audio playsinline controls style="max-height: 100%;">
                    <source src="${fileURL}" type="${fileType}">
                </audio>
            `;
            callback(html);
            new Plyr('audio');
            return;
        }
        case isMatch(['video']) || ['mp4', 'mkv'].includes(fileExtension): {
            html = `
                <video playsinline controls style="max-height: 100%;">
                    <source src="${fileURL}" type="${fileType}">
                </video>
            `;
            callback(html);
            new Plyr('video');
            return;
        }
        default: {
            html = `<p class="text-center"><br>暂不支持该文件类型的在线预览。</p>`;
            callback(html);
        }
    }
};

const displayMessage = (message, isSelf) => {
    let replyPreview = '';
    // 检测是否被@提及
    const mentioned = message.content.includes(`@${sessionUsername}`);
    const isNewMessage = !localStorage.getItem(`read_${message.id}`);
    const messageClass = mentioned && isNewMessage ? 'mentioned-message' : '';

    const preserveTextFormat = text => text.replace(/\n/g, '<br>');
    const parseAndFormatMessageContent = content => {
        if (message.type === 'user.markdown') {
            return marked.parse(content);
        }
        const fileTemplatePattern = /\[!file\((.*?)\)\]/;
        if (fileTemplatePattern.test(content)) {
            const fileData = parseFileTemplate(content);
            const fileHTML = renderFileTemplate(fileData);
            return content.replace(fileTemplatePattern, fileHTML);
        }
        return preserveTextFormat(content);
    };
    const renderFileTemplate = parsedData => {
        if (!parsedData) return '';
        const { path, name, type, size } = parsedData;
        const fileExtension = name.split('.').pop().toLowerCase();
        const isMatch = (patterns) => patterns.some(ext => fileExtension === ext || type.startsWith(ext));
        if (isMatch(['image'])) {
            return `<img alt="${name}" src=${path}></img>`;
        } else {
            return `
            <div class="file-info">
                <button type="button" class="preview-upload-file btn btn-sm primary" fileData='${message.content}' onclick="const data = parseFileTemplate($(this).attr('fileData'));handleFilePreview(data['path'], data['name'], data['type'], function (html) {filePreviewContent.html(html);filePreviewModal.show();});"><i class="fa-solid fa-expand"></i> 预览文件</button><p>${name} ${size}</p>
            </div>`;
        }
    };
    if (message.reply_to) {
        const repliedMessage = $(`#${message.reply_to}`);
        if (repliedMessage.length) {
            const repliedContent = repliedMessage.find('.message-content-text').text().trim();
            replyPreview = `
                <div class="reply-preview">
                    <span>${repliedMessage.find('.username').text().trim()}</span>
                    <div class="reply-message">${repliedContent.substring(0, 30)}${repliedContent.length > 30 ? '...' : ''}</div>
                    <button class="btn btn-sm btn-link locate-original" data-msg-id="${message.reply_to}">
                        <i class="fas fa-location-arrow"></i> 定位原文
                    </button>
                </div>
            `;
        }
    }
    const formattedContent = parseAndFormatMessageContent(message.content);
    const timestamp = `<span class="timestamp">${message.created_at}</span>`;
    const messageTypeClass = {
        system: 'alert alert-info system-msg',
        warning: 'alert alert-warning system-msg',
        error: 'alert alert-danger system-msg',
        info: 'alert alert-primary system-msg',
    }[message.type] || 'chat-message';
    const avatar = message.avatar_url
        ? `<img src="${message.avatar_url}" alt="avatar" class="avatar">`
        : `<img src="${letterAvatar(message.user_name)}" alt="avatar" class="avatar">`;
    const username = `
        <span class="username">
            ${isSelf ? '' : avatar} ${message.user_name} ${isSelf ? avatar : ''} 
        </span>`;
    const messageHTML = `
        <div class="${messageTypeClass} ${isSelf ? 'right' : 'left'} ${messageClass}" id="${message.id}" data-msg-id="${message.id}">
            <div class="message-content">
                ${username}
                ${message.group_name ? `<div class="user-group">(${message.group_name})</div>` : ''}
                ${replyPreview}
                <div class="message-content-text">${formattedContent}</div>
                ${timestamp}
            </div>
        </div>`;

    // 如果被@提及、不是自己发的消息且是未读消息，显示通知并设置定时移除高亮
    if (mentioned && !isSelf && isNewMessage) {
        showMentionNotification(message);
        localStorage.setItem(`read_${message.id}`, 'true');
    }

    // 3秒后移除高亮类
    setTimeout(() => {
        const messageElement = $(`#${message.id}`);
        if (messageElement.length) {
            messageElement.removeClass('mentioned-message');
        }
    }, 3000); // 3000毫秒=3秒

    return messageHTML;
};
const sendMessage = (message, uploadFile) => {
    const formData = new FormData();
    formData.append('message', message);
    formData.append('isMarkdown', $('#message').attr('data-markdown') === 'true');
    if (uploadFile) {
        formData.append('file', uploadFile);
    }
    if (quotedMessage) {
        formData.append('replyTo', quotedMessage.id);
        quotedMessage = null; // 发送后清除引用
        $('#reply-preview').hide().empty();
    }
    // 在发送消息前检查通知权限
    checkNotificationPermission();
    $('#send-button').attr('disabled', true);
    $.ajax({
        url: '/api/v1/chat/send',
        type: 'POST',
        contentType: false,
        processData: false,
        data: formData,
        success: (response) => {
            loadMessages;
            if (response.code === 200) {
                if (typeof response.message === 'string') {
                    chatBox.append(displayMessage({
                        type: 'system',
                        user_name: 'Command',
                        content: `${response.message}`,
                        created_at: new Date()
                    }, false));
                    if (!isUserScrolling) {
                        scrollToBottom();
                    } else {
                        $('#scroll-down-button').show();
                    }
                    return;
                }
                $('#message').val('');
                $('#file').val('');
                filePreview.empty();
                if (!isUserScrolling) {
                    scrollToBottom();
                } else {
                    $('#scroll-down-button').show();
                }
            } else {
                displayErrorMessage(`发送消息失败: ${response.message}`);
                scrollToBottom();
            }
        },
        error: () => {
            displayErrorMessage('发送消息失败，请检查您的网络状态！');
            scrollToBottom();
        },
        complete: () => $('#send-button').attr('disabled', false)
    });
};

const bindEventListeners = () => {
    // 监听引用消息事件
    $(document).on('message:reply', function (e, messageId) {
        const messageElement = $(`#${messageId}`);
        if (messageElement.length) {
            quotedMessage = {
                id: messageId,
                content: messageElement.find('.message-content-text').text().trim(),
                username: messageElement.find('.username').text().trim()
            };
            updateReplyPreview();
            messageInput.focus();
        }
    });

    // 初始化@自动补全
    setupAtMentionAutocomplete();

    // 定位原文事件监听
    chatBox.on('click', '.locate-original', function () {
        const messageId = $(this).data('msg-id');
        const messageElement = $(`#${messageId}`);
        if (messageElement.length) {
            // 平滑滚动到目标消息
            chatBox.stop().animate({
                scrollTop: messageElement.offset().top - chatBox.offset().top + chatBox.scrollTop() - 20
            }, 500);

            // 添加高亮效果
            messageElement.addClass('highlighted-message');
            setTimeout(() => {
                messageElement.removeClass('highlighted-message');
            }, 2000);
        }
    });

    chatBox.on('scroll', function () {
        const isAtBottom = chatBox.scrollTop() + chatBox.outerHeight() >= chatBox[0].scrollHeight;
        isUserScrolling = chatBox.scrollTop() < lastScrollTop;
        // 滚动到底部时隐藏下拉按钮
        $('#scroll-down-button').toggle(!isAtBottom && isUserScrolling);
        lastScrollTop = chatBox.scrollTop();
    });
    $('#insert-md').click(function () {
        const isMarkdown = $('#message').attr('data-markdown') === 'true';
        $('#message').attr('data-markdown', !isMarkdown)
            .attr('placeholder', isMarkdown ? '聊点什么吧，Ctrl+Enter发送消息' : 'MarkDown语法已开启');
        $(this).toggleClass('btn-primary btn-secondary');
    });
    $('#select-file').click(() => fileInput.click());
    fileInput.change((e) => {
        const file = e.target.files[0];
        if (file) {
            const filePreviewWrapper = filePreview;
            const fileExtension = file.name.split('.').pop().toLowerCase();
            const isMatch = (patterns) => patterns.some(ext => fileExtension === ext || file.type.startsWith(ext));
            if (isMatch(['image'])) {
                filePreviewWrapper.html(`
                <div class="file-preview-wrapper position-relative d-inline-block">
                    <img style="max-width: 30%;" src="${URL.createObjectURL(file)}"></img>
                    <button type="button" id="remove-file" class="btn btn-sm btn-danger position-absolute" style="top: 5px; left: 5px">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </div>`);
            } else {
                filePreviewWrapper.html(`
                <div class="file-preview-wrapper position-relative d-inline-block">
                    <button type="button" id="preview-file" class="btn">预览 ${file.name}</button>
                    <button type="button" id="remove-file" class="btn btn-sm btn-danger position-absolute" style="top: 5px">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </div>`);
            }
            $('#preview-file').click(() => handleSelectFilePreview(file));
            $('#remove-file').click(() => {
                fileInput.val('');
                filePreviewContent.html('');
                filePreviewWrapper.empty();
            });
        }
    });
    $('#send-button').click(function (e) {
        e.preventDefault();
        const message = $('#message').val().trim();
        const uploadFile = fileInput[0].files[0];
        if (message || uploadFile || quotedMessage) {
            sendMessage(message, uploadFile);
        } else {
            alert("请输入消息或选择文件。");
        }
    });
    $('#message').keydown(function (event) {
        if (event.ctrlKey && event.key === 'Enter') {
            event.preventDefault();
            $('#send-button').click()
        }
    });
    $('#scroll-down-button').click(() => { scrollToBottom(); isUserScrolling = false; });
    $('#logout').click(() => new bootstrap.Modal($('#logoutModal'), {}).show());
    $('#confirmLogout').click(() => $.post('/api/v1/user/logout').done(() => window.location.href = '/user/login').fail(() => alert('离开聊天室失败，请稍后再试。')));
};

bindEventListeners();
setInterval(() => {
    loadMessages();
}, 1000);