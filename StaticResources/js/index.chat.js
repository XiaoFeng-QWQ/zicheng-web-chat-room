let isUserScrolling = false;
let offset = 1;
let eventOffset = 0;
let lastFetched = null;
let lastScrollTop = 0;
let loadingMessages = false;
let isAtTop = false;
const messagesPerPage = 40;
const chatBox = $('#chat-box');
const fileInput = $('#file');
const filePreview = $('#select-file-preview');
const filePreviewModal = new bootstrap.Modal(document.getElementById('filePreviewModal'));
const filePreviewContent = $('#filePreviewContent');
const scrollToBottom = () => chatBox.scrollTop(chatBox[0].scrollHeight);
const loadMessages = () => {
    if (loadingMessages) return;  // 防止重复请求
    loadingMessages = true;
    // 发起加载请求
    $.ajax({
        url: `/api/v1/chat/get?offset=${offset}&limit=${messagesPerPage}`,
        type: 'POST',
        dataType: 'json',
        success: (response) => {
            loadingMessages = false;
            if (response.code === 200) {
                updateEvent();
                updateNetworkStatus(true);
                const data = response.data;
                // 判断滚动位置
                const isAtBottom = chatBox.scrollTop() + chatBox.outerHeight() >= chatBox[0].scrollHeight - 5;
                const isAtTop = chatBox.scrollTop() === 0;
                // 加载消息内容
                if (Array.isArray(data.messages)) {
                    data.messages.forEach(msg => chatBox.append(displayMessage(msg, msg.user_name === sessionUsername)));
                    lastFetched = data.messages[data.messages.length - 1]?.created_at;
                    // 更新偏移量
                    offset += data.messages.length;
                } else {
                    displayErrorMessage('加载聊天记录失败');
                }
                if (data.onlineUsers) {
                    const onlineUsersList = $('#online-users-list');
                    onlineUsersList.empty();
                    const currentTime = Math.floor(Date.now() / 1000);
                    let count = 0;
                    for (let userId in data.onlineUsers) {
                        count++
                        // 最多显示5个
                        if (count >= 6) {
                            break;
                        }
                        const user = data.onlineUsers[userId];
                        if (currentTime - user.last_time < 10) {
                            const userItem = $('<li>').text(`${user.user_name}|`);
                            $('#online-users-list-count').text(count)
                            onlineUsersList.append(userItem);
                        } else {
                            count--
                        }
                    }
                }
                // 滚动控制
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
    const onlineUsersList = $('#online-users-list');
    onlineUsersList.empty();
    const currentTime = Math.floor(Date.now() / 1000);
    let count = 0;
    for (let userId in onlineUsers) {
        count++;
        if (count >= 6) break;
        const user = onlineUsers[userId];
        if (currentTime - user.last_time < 10) {
            const userItem = $('<li>').text(`${user.user_name}|`);
            $('#online-users-list-count').text(count);
            onlineUsersList.append(userItem);
        } else {
            count--;
        }
    }
};
const displayErrorMessage = (message) => {
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
const displayMessage = (message, isSelf) => {
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
    const formattedContent = parseAndFormatMessageContent(message.content);
    const timestamp = `<span class="timestamp">${message.created_at}</span>`;
    const messageTypeClass = {
        system: 'alert alert-info system-msg',
        command: 'alert alert-info system-msg',
        warning: 'alert alert-warning system-msg',
        error: 'alert alert-danger system-msg',
        info: 'alert alert-primary system-msg',
        'event.delete': 'user-delete-msg',
    }[message.type] || 'chat-message';
    const avatar = message.avatar_url
        ? `<img src="${message.avatar_url}" alt="avatar" class="avatar">`
        : `<img src="${letterAvatar(message.user_name)}" alt="avatar" class="avatar">`;
    const username = `
        <span class="username">
            ${isSelf ? '' : avatar} ${message.user_name} ${isSelf ? avatar : ''} 
            ${message.group_name ? `<div class="user-group">(${message.group_name})</div>` : ''}
        </span>`;
    const messageHTML = `
        <div class="${messageTypeClass} ${isSelf ? 'right' : 'left'}" id="${message.id}" data-msg-id="${message.id}">
            <div class="message-content">
                ${username}
                <div>${formattedContent}</div>
                ${timestamp}
            </div>
        </div>`;
    return messageHTML;
};
const sendMessage = (message, uploadFile) => {
    const formData = new FormData();
    formData.append('message', message);
    formData.append('isMarkdown', $('#message').attr('data-markdown') === 'true');
    if (uploadFile) {
        formData.append('file', uploadFile);
    }
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
    chatBox.on('scroll', function () {
        const isAtBottom = chatBox.scrollTop() + chatBox.outerHeight() >= chatBox[0].scrollHeight - 5;
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
    $('#chat-form').submit((event) => {
        event.preventDefault();
        const message = $('#message').val().trim();
        const uploadFile = fileInput[0].files[0];
        if (message || uploadFile) {
            sendMessage(message, uploadFile);
        } else {
            alert("请输入消息或选择文件。");
        }
    });
    $('#message').keydown(function (event) {
        if (event.ctrlKey && event.key === 'Enter') {
            event.preventDefault();
            $('#chat-form').submit();
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