let isUserScrolling = false;
let offset = 0;
let eventOffset = 0;
let eventCount = 40;
let lastFetched = null;
let lastScrollTop = 0;
let loadingMessages = false;
let isAtTop = false;
let quotedMessage = null;
let onlineUsersList = []; // 存储在线用户列表
let networkStatus = true;
let sessionUsername = '';

// 文件管理相关变量
let currentPage = 1;
const filesPerPage = 10;
let currentSort = 'created_at';
let currentSearch = '';

const messageInput = $('#message');
const messagesPerPage = 40;
const chatBox = $('#chat-box');
const fileInput = $('#file');
const scrollToBottom = () => chatBox.scrollTop(chatBox[0].scrollHeight);

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
                    // 如果是第一次请求，保存到缓存
                    if (loadMessagesFromCache() === null) {
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

// 单独提取点击处理函数（避免内联JS）
function handleFilePreviewClick(button) {
    try {
        const data = JSON.parse(button.getAttribute('fileData'));
        handleFilePreview({
            path: data.path,
            name: data.name || '未命名文件',
            type: data.type || 'application/octet-stream'
        }, (html) => {
            filePreviewContent.html(html);
            filePreviewModal.show();
        });
    } catch (e) {
        console.error('文件预览失败:', e);
        alert('无法预览该文件');
    }
}

// 关闭文件预览
const closeFilePreview = () => {
    // 停止所有媒体播放
    const mediaElements = ['audio-preview', 'video-preview', 'floating-audio', 'floating-video']
        .map(id => document.getElementById(id))
        .filter(el => el);

    mediaElements.forEach(media => {
        media.pause();
        media.removeAttribute('src');
        media.load();
    });

    // 清理可视化
    if (window.audioContext) {
        window.audioContext.close();
        window.audioContext = null;
    }

    // 清理ObjectURL
    if (currentFilePreview && currentFilePreview.isLocalFile) {
        URL.revokeObjectURL(currentFilePreview.fileURL);
    }

    // 移除所有相关事件监听器
    $(document).off('.filePreviewEvents');
    $('#floating-file-preview').off();
    $('#minimize-file, #minimize-audio, #minimize-video').off();
    $('.maximize-file, .close-file').off();

    // 移除浮动预览窗口
    $('#floating-file-preview').remove();

    // 重置状态
    isFilePreviewMinimized = false;
    currentFilePreview = null;
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

        // 安全解构 + 默认值
        const { path, name = '未命名文件', type = 'application/octet-stream', size = '' } = parsedData;

        // 安全提取扩展名
        const fileExtension = typeof name === 'string' && name.includes('.')
            ? name.split('.').pop().toLowerCase()
            : '';

        const isMatch = (patterns) => patterns.some(ext => fileExtension === ext || type.startsWith(ext));

        if (isMatch(['image'])) {
            return `<img alt="${name}" src="${path}">`; // 修复：移除冗余的 </img>
        } else {
            return `
            <div class="file-info">
                <button type="button" 
                    class="preview-upload-file btn btn-sm primary" 
                    fileData='${JSON.stringify(parsedData)}' 
                    onclick="handleFilePreviewClick(this)">
                    <i class="fa-solid fa-expand"></i> 预览文件
                </button>
                <p>${name} - ${size}KB</p>
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
            loadMessages(); // 修正：调用函数
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

// ==================== 文件管理功能 ====================

/**
 * 显示加载动画
 */
const showFileLoading = () => {
    $('#fileListBody').html(`
        <tr>
            <td colspan="5" class="text-center">
                <div class="d-flex justify-content-center align-items-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">加载中...</span>
                    </div>
                    <span class="ms-2">正在加载文件列表...</span>
                </div>
            </td>
        </tr>
    `);

    // 禁用分页和搜索按钮防止重复请求
    $('.page-link, #fileSearchBtn').addClass('disabled');
};

/**
 * 隐藏加载动画
 */
const hideFileLoading = () => {
    $('.page-link, #fileSearchBtn').removeClass('disabled');
};

/**
 * 计算并更新文件统计信息
 * @param {Array} files - 文件数组
 */
const calculateAndUpdateFileStats = (files) => {
    if (!files || files.length === 0) {
        updateFileStats(0, 0);
        return;
    }

    // 计算总大小(KB)
    const totalSizeKB = files.reduce((sum, file) => {
        return sum + (parseFloat(file.file_size) || 0);
    }, 0);

    updateFileStats(files.length, totalSizeKB);
};

/**
 * 更新文件统计信息显示
 * @param {number} totalCount - 文件总数
 * @param {number} totalSizeKB - 总大小(KB)
 */
const updateFileStats = (totalCount, totalSizeKB) => {
    $('#totalFilesCount').text(totalCount);
    $('#totalFilesSize').text(formatFileSize(totalSizeKB));
};

/**
 * 加载文件列表
 * @param {number} page - 当前页码
 * @param {string} search - 搜索关键词
 * @param {string} sort - 排序字段
 */
const loadFiles = (page = 1, search = '', sort = 'created_at') => {
    showFileLoading();

    $.ajax({
        url: `/api/v1/files/all?page=${page}&per_page=${filesPerPage}&search=${encodeURIComponent(search)}&sort=${sort}`,
        type: 'GET',
        dataType: 'json',
        success: (response) => {
            if (response.code === 200) {
                renderFileList(response.data.files);
                renderPagination(response.data.total, page);

                // 使用前端计算文件统计信息
                calculateAndUpdateFileStats(response.data.files);

                currentPage = page;
                currentSort = sort;
                currentSearch = search;
            } else {
                console.error('加载文件列表失败:', response.message);
                $('#fileListBody').html(`
                    <tr>
                        <td colspan="5" class="text-center text-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            加载文件列表失败: ${response.message}
                        </td>
                    </tr>
                `);
                updateFileStats(0, 0);
            }
        },
        error: (xhr) => {
            console.error('加载文件列表错误:', xhr);
            $('#fileListBody').html(`
                <tr>
                    <td colspan="5" class="text-center text-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        加载文件列表时出错，请稍后重试
                    </td>
                </tr>
            `);
            updateFileStats(0, 0);
        },
        complete: () => {
            hideFileLoading();
        }
    });
};

/**
 * 渲染文件列表
 * @param {Array} files - 文件数组
 */
const renderFileList = (files) => {
    const fileListBody = $('#fileListBody');
    fileListBody.empty();

    if (!files || files.length === 0) {
        fileListBody.html(`
            <tr>
                <td colspan="5" class="text-center text-muted">
                    <i class="bi bi-folder-x me-2"></i>
                    没有找到文件
                </td>
            </tr>
        `);
        return;
    }

    // 添加淡入动画效果
    fileListBody.css('opacity', 0);

    files.forEach(file => {
        const fileTypeIcon = getFileTypeIcon(file.file_type);
        const fileSize = formatFileSize(file.file_size);
        const createdAt = new Date(file.created_at).toLocaleString();
        const filePath = `/api/v1/files/${file.file_md5}`;

        const row = $(`
            <tr class="file-item">
                <td>
                    <i class="${fileTypeIcon} me-2"></i>
                    来自: ${file.user_id === parseInt($('#user-id').val()) ? '我' : file.username} 的 ${file.file_name}
                </td>
                <td>${file.file_type}</td>
                <td>${fileSize}</td>
                <td>${createdAt}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary preview-file-btn" 
                            data-file-name="${file.file_name}" 
                            data-file-type="${file.file_type}"
                            data-file-path="${filePath}">
                        <i class="bi bi-eye"></i> 预览
                    </button>
                    <a href="/api/v1/files/${file.file_md5}" download="${file.file_name}" class="btn btn-sm btn-outline-primary">
                            <i class="fa fa-download"></i> 下载文件
                    </a>
                    ${file.user_id === parseInt($('#user-id').val()) || $('#user-group').val() === '1' ?
                `<button class="btn btn-sm btn-outline-danger delete-file-btn" 
                                data-file-id="${file.id}">
                            <i class="bi bi-trash"></i> 删除
                        </button>` :
                ''}
                </td>
            </tr>
        `);

        fileListBody.append(row);
    });

    // 淡入动画
    fileListBody.animate({ opacity: 1 }, 300);
};

/**
 * 渲染分页控件
 * @param {number} totalFiles - 总文件数
 * @param {number} currentPage - 当前页码
 */
const renderPagination = (totalFiles, currentPage) => {
    const totalPages = Math.ceil(totalFiles / filesPerPage);
    const pagination = $('#filePagination');
    const paginationInfo = $('#filePaginationInfo');

    pagination.empty();
    paginationInfo.text(`共 ${totalFiles} 个文件，第 ${currentPage}/${totalPages} 页`);

    if (totalPages <= 1) return;

    // 上一页按钮
    const prevBtn = $(`
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" aria-label="Previous" data-page="${currentPage - 1}">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>
    `);
    pagination.append(prevBtn);

    // 页码按钮
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);

    for (let i = startPage; i <= endPage; i++) {
        const pageBtn = $(`
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>
        `);
        pagination.append(pageBtn);
    }

    // 下一页按钮
    const nextBtn = $(`
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" aria-label="Next" data-page="${currentPage + 1}">
                <span aria-hidden="true">&raquo;</span>
            </a>
        </li>
    `);
    pagination.append(nextBtn);

    // 添加分页按钮点击动画
    $('.page-link').on('click', function () {
        $(this).addClass('active');
        setTimeout(() => $(this).removeClass('active'), 150);
    });
};

/**
 * 根据文件类型获取对应的图标类
 * @param {string} fileType - 文件MIME类型
 * @returns {string} 图标类名
 */
const getFileTypeIcon = (fileType) => {
    if (fileType.startsWith('image/')) return 'bi bi-file-image';
    if (fileType.startsWith('audio/')) return 'bi bi-file-music';
    if (fileType.startsWith('video/')) return 'bi bi-file-play';
    if (fileType === 'application/pdf') return 'bi bi-file-pdf';
    if (fileType.includes('word') || fileType.includes('document')) return 'bi bi-file-word';
    if (fileType.includes('excel') || fileType.includes('spreadsheet')) return 'bi bi-file-excel';
    if (fileType.includes('powerpoint') || fileType.includes('presentation')) return 'bi bi-file-ppt';
    return 'bi bi-file-earmark';
};

/**
 * 格式化文件大小
 * @param {number} sizeInKB - 文件大小(KB)
 * @returns {string} 格式化后的文件大小
 */
const formatFileSize = (sizeInKB) => {
    if (sizeInKB < 1024) return `${Math.round(sizeInKB)} KB`;
    const sizeInMB = sizeInKB / 1024;
    if (sizeInMB < 1024) return `${sizeInMB.toFixed(1)} MB`;
    const sizeInGB = sizeInMB / 1024;
    return `${sizeInGB.toFixed(1)} GB`;
};

/**
 * 删除文件
 * @param {number} fileId - 文件ID
 */
const deleteFile = (fileId) => {
    if (!confirm('确定要删除这个文件吗？此操作不可恢复。')) return;

    $.ajax({
        url: `/api/v1/files/delete/${fileId}`,
        type: 'DELETE',
        dataType: 'json',
        success: (response) => {
            if (response.code === 200) {
                showToast('文件删除成功', 'success');
                loadFiles(currentPage, currentSearch, currentSort);
            } else {
                showToast(`文件删除失败: ${response.message}`, 'danger');
            }
        },
        error: (xhr) => {
            console.error('删除文件错误:', xhr);
            showToast('删除文件时出错', 'danger');
        }
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
            $('#preview-file').click(() => handleFilePreview(file)); // 直接传入File对象
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

    // ==================== 文件管理相关事件 ====================
    // 文件管理相关事件
    $('#fileManagerModal').on('show.bs.modal', () => {
        loadFiles();
    });

    // 分页点击事件
    $(document).on('click', '.page-link', function (e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page) {
            loadFiles(page, currentSearch, currentSort);
        }
    });

    // 排序选项点击事件
    $(document).on('click', '.sort-option', function (e) {
        e.preventDefault();
        const sort = $(this).data('sort');
        loadFiles(1, currentSearch, sort);
    });

    // 搜索按钮点击事件
    $('#fileSearchBtn').click(() => {
        const searchTerm = $('#fileSearchInput').val().trim();
        loadFiles(1, searchTerm, currentSort);
    });

    // 搜索框回车事件
    $('#fileSearchInput').keypress((e) => {
        if (e.which === 13) {
            const searchTerm = $('#fileSearchInput').val().trim();
            loadFiles(1, searchTerm, currentSort);
        }
    });

    // 预览按钮点击事件
    $(document).on('click', '.preview-file-btn', function () {
        const fileName = $(this).data('file-name');
        const fileType = $(this).data('file-type');
        const filePath = $(this).data('file-path');

        handleFilePreview({
            path: filePath,
            name: fileName,
            type: fileType
        });
    });

    // 删除按钮点击事件
    $(document).on('click', '.delete-file-btn', function () {
        const fileId = $(this).data('file-id');
        deleteFile(fileId);
    });
    // ==================== 文件管理相关事件 END ====================

    $(document).ready(function () {
        updateUnreadBadge();
    });
    $('body').on('click', '.confirm-read', function () {
        updateUnreadBadge();
    });
};

// 初始化用户信息
$.ajax({
    type: "GET",
    url: "/api/v1/home/user",
    dataType: "JSON",
    success: function (response) {
        if (response.code === 200) {
            $('#chatroom-user-count').text(response.data.registerUserCount);
            sessionUsername = response.data.userdata.data.username;
            // 添加用户ID和组ID到隐藏字段，用于权限判断
            $('body').append(`<input type="hidden" id="user-id" value="${response.data.userdata.data.user_id}">`);
            $('body').append(`<input type="hidden" id="user-group" value="${response.data.userdata.data.group_id}">`);
        } else {
            $('#chatroom-user-count').text('undefined');
        }
    },
    error: function () {
        $('#chatroom-user-count').text('undefined');
    }
});

// 初始化
bindEventListeners();
setInterval(() => {
    loadMessages();
}, 1000);