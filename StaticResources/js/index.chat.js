let isUserScrolling = false;
let offset = 0;
let lastFetched = null;
let lastScrollTop = 0;
let loadingMessages = false;
const fileInput = $('#file');
const filePreview = $('#select-file-preview');
const filePreviewModal = new bootstrap.Modal(document.getElementById('filePreviewModal'));
const filePreviewContent = $('#filePreviewContent');
const scrollToBottom = () => $('#chat-box').scrollTop($('#chat-box')[0].scrollHeight);

/**
 * 对于选择的文件生成预览
 * @param {*} file 
 * @returns 
 */
const handleSelectFilePreview = file => {
    const fileURL = URL.createObjectURL(file);
    const fileName = file.name;
    const fileType = file.type;

    // 渲染并显示预览
    const showPreview = content => {
        filePreviewContent.html(content);
        $('#filePreviewFileInfo').text(fileName);
        filePreviewModal.show();
    };

    // 根据文件类型生成预览内容
    const fileExtension = fileName.split('.').pop().toLowerCase();
    const isMatch = (patterns) => patterns.some(ext => fileExtension === ext || fileType.startsWith(ext));

    switch (true) {
        case isMatch(['text']) || ['txt', 'json', 'xml', 'log'].includes(fileExtension): {
            const reader = new FileReader();
            reader.onload = e => {
                const fileContent = e.target.result;
                showPreview(`
                    <pre><code class="language-${fileExtension}">${fileContent}</code></pre>
                `);
                hljs.highlightAll()
            };
            reader.readAsText(file);
            break;
        }
        case isMatch(['image']): {
            html = `<img src="${fileURL}" alt="${fileName}" style="max-width: 100%; max-height: 100%; object-fit: contain;">`;
            showPreview(html);
            return;
        }
        case isMatch(['officedocument']) || ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv'].includes(fileExtension): {
            html = `<iframe src="https://view.officeapps.live.com/op/view.aspx?src=${encodeURIComponent(fileURL)}"></iframe>`;
            showPreview(html);
            return;
        }
        case fileType === 'application/pdf' || fileExtension === 'pdf': {
            html = `<iframe src="${fileURL}"></iframe>`;
            showPreview(html);
            return;
        }
        case isMatch(['audio']) || ['mp3', 'wav', 'ogg', 'aac'].includes(fileExtension): {
            html = `
                <audio playsinline controls style="max-height: 100%;">
                    <source src="${fileURL}" type="${fileType}">
                </audio>
            `;
            showPreview(html);
            new Plyr('audio');
            return;
        }
        case isMatch(['video']) || ['mp4', 'mkv'].includes(fileExtension): {
            html = `
                <video playsinline controls style="max-height: 100%;">
                    <source src="${fileURL}" type="${fileType}">
                </video>
            `;
            showPreview(html);
            new Plyr('video');
            return;
        }
        default: {
            html = `<p class="text-center"><br>暂不支持该文件类型的预览。</p>`;
            showPreview(html);
        }
    }
};
/**
 * 对于上传的文件生成预览
 * @param {*} path 
 * @param {*} name 
 * @param {*} type 
 * @param {*} callback 
 * @returns 
 */
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
            <button class="btn btn-sm btn-primary edit-button"><i class="bi bi-file-earmark-arrow-down"></i></button>
        </a>`);

    switch (true) {
        case isMatch(['text']) || ['txt', 'json', 'xml', 'log'].includes(fileExtension): {
            // 异步加载文本文件
            $.ajax({
                type: "GET",
                url: fileURL,
                success: function (response) {
                    if (fileExtension === 'txt') {
                        html = `<pre><code class="language-${fileExtension}">${response}</code></pre>`;
                    } else {
                        html = `<pre><code class="language-${fileExtension}">${JSON.stringify(response, null, 2)}</code></pre>`;
                    }
                    callback(html); // 确保通过回调函数更新页面
                    hljs.highlightAll()
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
    // 保留文本中的换行和空格
    const preserveTextFormat = text => text.replace(/\n/g, '<br>');
    // 解析并格式化消息内容
    const parseAndFormatMessageContent = content => {
        const fileTemplatePattern = /\[!file\((.*?)\)\]/;

        if (fileTemplatePattern.test(content)) {
            const fileData = parseFileTemplate(content);
            const fileHTML = renderFileTemplate(fileData);
            return content.replace(fileTemplatePattern, fileHTML);
        }
        return preserveTextFormat(content);
    };
    // 渲染文件模板为HTML
    const renderFileTemplate = parsedData => {
        if (!parsedData) return '';
        const { path, name, type, size } = parsedData;
        const fileExtension = name.split('.').pop().toLowerCase();
        const isMatch = (patterns) => patterns.some(ext => fileExtension === ext || type.startsWith(ext));
        if (isMatch(['image'])) {
            return `<img alt="${name}" src=${path}></img>`
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
        warning: 'alert alert-warning system-msg',
        error: 'alert alert-danger system-msg',
        info: 'alert alert-primary system-msg'
    }[message.type] || 'chat-message';

    const avatar = message.avatar_url
        ? `<img src="${message.avatar_url}" alt="avatar" class="avatar">`
        : `<img src="${letterAvatar(message.user_name)}" alt="avatar" class="avatar">`;

    const username = `
        <span class="username">
            ${isSelf ? '' : avatar} ${message.user_name} ${isSelf ? avatar : ''} 
            ${message.group_name ? `<div class="user-group">(${message.group_name})</div>` : ''}
        </span>`;

    return `
        <div class="${messageTypeClass} ${isSelf ? 'right' : 'left'}">
            <div class="message-content">
                ${message.type === 'user' ? username : ''}
                <div>${formattedContent}</div>
                ${timestamp}
            </div>
        </div>`;
};

const loadChatMessages = () => {
    if (loadingMessages) return;
    loadingMessages = true;
    $.ajax({
        url: `/api/v1/chat/get?offset=${offset}`,
        type: 'POST',
        dataType: 'json',
        success: (response) => {
            loadingMessages = false;
            if (response.code === 200) {
                response = response.data;
                const chatBox = $('#chat-box');
                const isAtBottom = chatBox.scrollTop() + chatBox.outerHeight() >= chatBox[0].scrollHeight - 5;
                if (response.onlineUsers) {
                    const onlineUsersList = $('#online-users-list');
                    onlineUsersList.empty();
                    const currentTime = Math.floor(Date.now() / 1000);
                    let count = 0;
                    for (let userId in response.onlineUsers) {
                        count++
                        // 最多显示5个
                        if (count >= 6) {
                            break;
                        }
                        const user = response.onlineUsers[userId];
                        if (currentTime - user.last_time < 10) {
                            const userItem = $('<li>').text(`${user.user_name}|`);
                            $('#online-users-list-count').text(count)
                            onlineUsersList.append(userItem);
                        }
                    }
                }
                if (Array.isArray(response.messages)) {
                    response.messages.forEach(msg => chatBox.append(displayMessage(msg, msg.user_name === sessionUsername)));
                    offset += response.messages.length;
                    lastFetched = response.messages[response.messages.length - 1]?.created_at;
                } else {
                    chatBox.append(displayMessage({
                        type: 'error',
                        content: '加载聊天记录失败',
                        created_at: new Date()
                    }, false));
                }
                if (isAtBottom) {
                    scrollToBottom();
                    $('#loading').hide()
                    chatBox.css('scroll-behavior', 'smooth');
                } else {
                    $('#scroll-down-button').show();
                }
            } else {
                $('#chat-box').append(displayMessage({
                    type: 'error',
                    content: '加载聊天记录失败',
                    created_at: new Date()
                }, false));
                scrollToBottom();
            }
        },
        error: (xhr) => {
            $('#chat-box').append(displayMessage({
                type: 'error',
                content: `加载聊天记录失败<br>${xhr}`,
                created_at: new Date()
            }, false));
            scrollToBottom();
            loadingMessages = false;
        }
    });
};
const sendMessage = (message, uploadFile) => {
    const formData = new FormData();
    formData.append('message', message);
    const chatBox = $('#chat-box');
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
            loadChatMessages(); // 发送成功后重新加载聊天记录
            if (response.code === 200) {
                // 不为true 指令消息
                if (response.message !== 'true') {
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
                $('#message').val(''); // 清空文本框
                $('#file').val(''); // 清空文件输入
                $('#select-file-preview').empty(); // 清空预览区域
                if (!isUserScrolling) {
                    scrollToBottom();
                } else {
                    $('#scroll-down-button').show(); // 若用户正在滚动，显示按钮
                }
            } else {
                $('#chat-box').append(displayMessage({
                    type: 'warning',
                    user_name: '系统',
                    content: response.message,
                    created_at: new Date()
                }, false));
                scrollToBottom();
            }
        },
        error: () => {
            $('#chat-box').append(displayMessage({
                type: 'error',
                user_name: '系统',
                content: '发送消息失败，请稍后再试。',
                created_at: new Date()
            }, false));
            scrollToBottom();
        },
        complete: () => $('#send-button').attr('disabled', false)
    });
};
// 绑定事件监听器
const bindEventListeners = () => {
    $('#chat-box').on('scroll', function () {
        const chatBox = $(this);
        const isAtBottom = chatBox.scrollTop() + chatBox.outerHeight() >= chatBox[0].scrollHeight - 5;
        isUserScrolling = chatBox.scrollTop() < lastScrollTop;
        $('#scroll-down-button').toggle(!isAtBottom && isUserScrolling);
        lastScrollTop = chatBox.scrollTop();
    });
    $('#select-file').click(() => $('#file').click());
    // 文件选择事件处理
    $('#file').change((e) => {
        const file = e.target.files[0];
        if (file) {
            const filePreviewWrapper = $('#select-file-preview');
            const fileExtension = file.name.split('.').pop().toLowerCase();
            const isMatch = (patterns) => patterns.some(ext => fileExtension === ext || file.type.startsWith(ext));
            // 预览区动态生成预览按钮
            if (isMatch(['image'])) {
                filePreviewWrapper.html(`
                <div class="file-preview-wrapper position-relative d-inline-block">
                    <img style="max-width: 30%;" src="${URL.createObjectURL(file)}"></img>
                    <button type="button" id="remove-file" class="btn btn-sm btn-danger position-absolute" style="top: 5px; left: 5px">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </div>`)
            } else {
                filePreviewWrapper.html(`
                <div class="file-preview-wrapper position-relative d-inline-block">
                    <button type="button" id="preview-file" class="btn">预览 ${file.name}</button>
                    <button type="button" id="remove-file" class="btn btn-sm btn-danger position-absolute" style="top: 5px">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </div>`)
            };
            // 绑定文件预览事件
            $('#preview-file').click(() => handleSelectFilePreview(file));
            // 绑定移除文件事件
            $('#remove-file').click(() => {
                $('#file').val(''); // 清空文件输入
                $('#filePreviewContent').html('')
                filePreviewWrapper.empty(); // 清空预览区域
            });
        }
    });
    // 表单提交事件
    $('#chat-form').submit((event) => {
        event.preventDefault();
        const message = $('#message').val().trim();
        const uploadFile = $('#file')[0].files[0];
        if (message || uploadFile) {
            sendMessage(message, uploadFile);
        } else {
            alert("请输入消息或选择文件。");
        }
    });
    // 添加对 Ctrl+Enter 快捷键的监听
    $('#message').keydown(function (event) {
        if (event.ctrlKey && event.key === 'Enter') {
            event.preventDefault(); // 阻止默认换行动作
            $('#chat-form').submit();
        }
    });
    $('#scroll-down-button').click(() => { scrollToBottom(); isUserScrolling = false; });
    $('#logout').click(() => new bootstrap.Modal($('#logoutModal'), {}).show());
    $('#confirmLogout').click(() => $.post('/api/v1/user/logout').done(() => window.location.href = '/user/login').fail(() => alert('离开聊天室失败，请稍后再试。')));
};
bindEventListeners();
loadChatMessages();
setInterval(loadChatMessages, 3000);