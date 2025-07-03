// 定义常量
const MESSAGE_INDEX_KEY = 'chat_messages_index';  // 存储消息ID列表
const MESSAGE_DATA_PREFIX = 'msg_';              // 单个消息存储的前缀
const MAX_CACHED_MESSAGES = 200;                 // 最大缓存消息数
const MESSAGE_CACHE_KEY = 'chat_messages_cache';
const CACHE_EXPIRATION = 24 * 60 * 60 * 1000; // 24小时缓存有效期
// 文件预览状态管理
const filePreview = $('#select-file-preview');
const filePreviewModal = new bootstrap.Modal(document.getElementById('filePreviewModal'));
const filePreviewContent = $('#filePreviewContent');
let isFilePreviewMinimized = false;
let currentFilePreview = null;
let isDraggingFilePreview = false;
let filePreviewOffsetX = 0;
let filePreviewOffsetY = 0;

function updateUnreadBadge() {
    if (!window.noticeList) return;

    const unreadCount = window.noticeList.filter(notice => !notice.is_read).length;
    const badge = $('#unreadNoticeBadge');

    if (unreadCount > 0) {
        badge.text(unreadCount).show();
    } else {
        badge.hide();
    }
}

/**
 * 保存或更新消息到缓存
 * @param {Array|Object} messages - 要保存的消息数组或单个消息对象
 * @param {boolean} updateMode - 是否为更新模式（true时更新现有消息，false时追加新消息）
 */
function saveMessagesToCache(messages, updateMode = false) {
    // 获取当前索引 - 这里应该使用MESSAGE_INDEX_KEY
    let index = JSON.parse(localStorage.getItem(MESSAGE_INDEX_KEY)) || [];

    // 标准化输入
    const messagesToSave = Array.isArray(messages) ? messages : [messages];

    // 更新索引和存储
    messagesToSave.forEach(msg => {
        if (!msg.id) {
            console.error('消息缺少id字段，无法缓存:', msg);
            return;
        }

        const storageKey = MESSAGE_DATA_PREFIX + msg.id;

        if (updateMode) {
            // 更新现有消息
            const existingMsg = JSON.parse(localStorage.getItem(storageKey));
            if (existingMsg) {
                localStorage.setItem(storageKey, JSON.stringify({
                    ...existingMsg,
                    ...msg,
                    updated_at: new Date().toISOString()
                }));
            } else {
                // 新消息添加到索引
                index.push(msg.id);
                localStorage.setItem(storageKey, JSON.stringify(msg));
            }
        } else {
            // 只添加新消息
            if (!index.includes(msg.id)) {
                index.push(msg.id);
                localStorage.setItem(storageKey, JSON.stringify(msg));
            }
        }
    });

    // 维护索引大小
    if (index.length > MAX_CACHED_MESSAGES) {
        const toRemove = index.slice(0, index.length - MAX_CACHED_MESSAGES);
        toRemove.forEach(id => {
            localStorage.removeItem(MESSAGE_DATA_PREFIX + id);
        });
        index = index.slice(-MAX_CACHED_MESSAGES);
    }

    // 保存更新后的索引
    localStorage.setItem(MESSAGE_INDEX_KEY, JSON.stringify(index));
}
// 从缓存加载消息
function loadMessagesFromCache() {
    const cacheData = localStorage.getItem(MESSAGE_CACHE_KEY);
    if (!cacheData) return null;

    try {
        const parsedData = JSON.parse(cacheData);

        // 检查缓存是否过期
        if (Date.now() - parsedData.timestamp > CACHE_EXPIRATION) {
            localStorage.removeItem(MESSAGE_CACHE_KEY);
            return null;
        }

        return parsedData.messages;
    } catch (e) {
        console.error('Failed to parse cached messages', e);
        return null;
    }
}

/**
 * 初始化富文本编辑器
 * @param {Object} options - 配置选项
 * @param {string} options.editorSelector - 编辑器容器的选择器
 * @param {string} options.toolbarSelector - 工具栏容器的选择器
 * @param {string} options.textareaSelector - 同步内容的textarea选择器
 * @param {string} options.initialContent - 编辑器的初始内容
 * @param {string} options.uploadServer - 文件上传的服务器地址
 */
function initializeEditor(options = {}) {
    // 如果已经初始化过编辑器，重新初始化
    if (window.wangEditor.editor) {
        window.wangEditor.editor.destroy();
        window.wangEditor.toolbar.destroy();
    }

    // 默认配置
    const defaultOptions = {
        editorSelector: '#editor-container',
        toolbarSelector: '#toolbar-container',
        textareaSelector: '#editor',
        initialContent: '',
        uploadServer: '/api/v1/files/upload'
    };

    // 合并配置
    const config = { ...defaultOptions, ...options };
    const { createEditor, createToolbar } = window.wangEditor;

    // 编辑器配置
    const editorConfig = {
        placeholder: '请输入内容...',
        onChange(editor) {
            const html = editor.getHtml();
            $(config.textareaSelector).val(html); // 同步到隐藏的textarea
        },
        MENU_CONF: {
            uploadImage: {
                server: config.uploadServer,
                fieldName: 'file',
                maxFileSize: 10 * 1024 * 1024, // 10M
                allowedFileTypes: ['image/*'],
                customInsert(res, insertFn) {
                    if (res.code === 200) {
                        console.table(res)
                        insertFn(res.data.url, res.data.name, res.data.url);
                    }
                }
            },
            uploadVideo: {
                server: config.uploadServer,
                fieldName: 'file',
                maxFileSize: 50 * 1024 * 1024, // 50M
                allowedFileTypes: ['video/*'],
                customInsert(res, insertFn) {
                    if (res.code === 200) {
                        insertFn(res.data.url, res.data.name, res.data.url);
                    }
                }
            },
            uploadFile: {
                server: config.uploadServer,
                fieldName: 'file',
                maxFileSize: 50 * 1024 * 1024, // 50M
                allowedFileTypes: ['*/*'],
                customInsert(res, insertFn) {
                    if (res.code === 200) {
                        insertFn(res.data.url, res.data.name, res.data.url);
                    }
                }
            }
        }
    };

    // 创建编辑器
    const editor = createEditor({
        selector: config.editorSelector,
        html: config.initialContent,
        config: editorConfig,
        mode: 'default'
    });

    // 创建工具栏
    createToolbar({
        editor,
        selector: config.toolbarSelector,
        config: {},
        mode: 'default'
    });
}

/// Cookie工具函数
function setCookie(key, value, days) {
    const date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    document.cookie = `${key}=${value};expires=${date.toUTCString()};path=/`;
}

/**
 * 获取指定cookie
 * @param {*} key 
 * @returns 
 */
function getCookie(key) {
    const cookies = document.cookie.split(';');
    for (let cookie of cookies) {
        const [k, v] = cookie.trim().split('=');
        if (k === key) return v;
    }
    return null;
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
 * 解码文件模版
 * @param {*} template 
 * @returns 
 */
function parseFileTemplate(template) {
    const pattern = /\[!file\((.*?)\)\]/;
    const match = template.match(pattern);
    if (match) {
        const paramsString = match[1];
        const params = paramsString.split(',').reduce((acc, param) => {
            param = param.trim();
            // 使用正则检查并拆分参数：支持'_'作为分隔符
            const [key, value] = param.split(/_/).map(item => item.trim());
            if (key && value !== undefined) {
                acc[key] = value.replace(/^"|"$/g, '');
            }
            return acc;
        }, {});
        return params;
    }
    return null;
}

// 处理事件
const parsingEvent = (event) => {
    switch (event.event_type) {
        case 'message.revoke': {
            $(`#${event.target_id}`).removeClass('chat-message left right').addClass('event').html(`
                    <div>${event.additional_data}</div>
                    <span class="timestamp">${event.created_at}</span>`);
            const newMessage = {
                id: event.target_id,
                content: event.additional_data,
                created_at: event.created_at,
            };
            saveMessagesToCache(newMessage, true);
            break;
        }
        case 'admin.push.notice': {
            const noticeData = event.additional_data;

            // 解码base64格式的内容
            if (/^[A-Za-z0-9+/=]+$/.test(noticeData.content) && noticeData.content.length % 4 === 0) {
                noticeData.content = decodeURIComponent(escape(atob(noticeData.content)));
            }

            // 初始化公告队列和存储
            window.forceNoticeQueue = window.forceNoticeQueue || [];
            window.noticeList = window.noticeList || [];

            // 将新公告添加到列表
            noticeData.event_id = event.event_id;
            noticeData.is_read = localStorage.getItem(`notice_read_${event.event_id}`) === 'true';
            window.noticeList.unshift(noticeData); // 最新公告放在前面

            // 公告颜色配置
            const noticeColors = {
                force: {
                    header: 'bg-danger text-white',
                    btn: 'btn-danger',
                    badge: 'badge bg-danger'
                },
                normal: {
                    header: 'bg-info text-white',
                    btn: 'btn-info',
                    badge: 'badge bg-info'
                }
            };

            // 显示公告模态框
            const showNoticeModal = (noticeData, eventId) => {
                // 如果已经有模态框显示，将新公告加入队列
                if ($('#noticeModal').length > 0) {
                    window.forceNoticeQueue.push({ noticeData, eventId });
                    return;
                }

                const color = noticeData.force_read === "true" ? noticeColors.force : noticeColors.normal;
                const isForceRead = noticeData.force_read === "true";

                const modalHTML = `
                <div class="modal fade" id="noticeModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="${isForceRead ? 'static' : 'true'}" data-bs-keyboard="${!isForceRead}">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header ${color.header}">
                                <h5 class="modal-title">
                                    ${isForceRead ? '【强制阅读】' : '【公告】'}${noticeData.title} <br>
                                    <small class="text-light">${noticeData.publisher || '系统公告'} ${new Date(noticeData.created_at).toLocaleString()}</small>
                                </h5>
                                ${!isForceRead ? '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' : ''}
                            </div>
                            <div class="modal-body">
                                ${noticeData.content}
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn ${color.btn} confirm-read" data-notice-id="${eventId}">
                                    ${isForceRead ? '确认已阅读' : '我已阅读'}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>`;

                $('body').append(modalHTML);
                const modal = new bootstrap.Modal($('#noticeModal'));
                modal.show();

                // 确认阅读处理
                $('.confirm-read').click(function () {
                    const noticeId = $(this).data('notice-id');
                    localStorage.setItem(`notice_read_${noticeId}`, 'true');

                    // 更新公告列表中的阅读状态
                    const noticeIndex = window.noticeList.findIndex(n => n.event_id === noticeId);
                    if (noticeIndex !== -1) {
                        window.noticeList[noticeIndex].is_read = true;
                    }

                    modal.hide();
                    $('#noticeModal').on('hidden.bs.modal', function () {
                        $(this).remove();

                        // 检查队列中是否有下一个公告
                        if (window.forceNoticeQueue.length > 0) {
                            const nextNotice = window.forceNoticeQueue.shift();
                            showNoticeModal(nextNotice.noticeData, nextNotice.eventId);
                        }
                    });
                    updateUnreadBadge();
                });
            };

            // 更新公告列表UI
            const updateNoticeListUI = () => {
                const noticeListBody = $('#noticeListBody');
                noticeListBody.empty();

                window.noticeList.forEach(notice => {
                    const isRead = notice.is_read;
                    const isForce = notice.force_read === "true";
                    const color = isForce ? noticeColors.force : noticeColors.normal;

                    const row = `
                    <tr data-notice-id="${notice.event_id}" class="${isRead ? 'text-muted' : 'fw-bold'}">
                        <td><span class="${color.badge}">${isForce ? '强制' : '普通'}</span></td>
                        <td>
                            <a href="#" class="view-notice text-decoration-none ${isRead ? 'text-muted' : (isForce ? 'text-danger' : 'text-primary')}">
                                ${notice.title}
                            </a>
                        </td>
                        <td>${notice.publisher || '系统'}</td>
                        <td>${new Date(notice.created_at).toLocaleString()}</td>
                        <td>${isRead ? '已读' : '未读'}</td>
                        <td>
                            <button class="btn btn-sm ${isForce ? 'btn-outline-danger' : 'btn-outline-primary'} view-notice">
                                查看
                            </button>
                        </td>
                    </tr>`;

                    noticeListBody.append(row);
                });

                // 绑定查看详情事件
                $('.view-notice').click(function (e) {
                    e.preventDefault();
                    const noticeId = $(this).closest('tr').data('notice-id');
                    const notice = window.noticeList.find(n => n.event_id === noticeId);

                    if (notice) {
                        showNoticeDetail(notice);
                    }
                });
            };

            // 显示公告详情
            const showNoticeDetail = (noticeData) => {
                const color = noticeData.force_read === "true" ? noticeColors.force : noticeColors.normal;

                $('#noticeDetailModalLabel').text(noticeData.title);
                $('#noticeDetailHeader').removeClass().addClass(`modal-header ${color.header}`);
                $('#noticeDetailContent').html(noticeData.content);

                // 更新底部按钮
                const footer = $('#noticeDetailFooter');
                footer.find('.confirm-read').remove();

                if (!noticeData.is_read) {
                    footer.prepend(`
                        <button type="button" class="btn ${color.btn} confirm-read" data-notice-id="${noticeData.event_id}">
                            标记为已读
                        </button>
                    `);

                    // 绑定标记已读事件
                    footer.find('.confirm-read').click(function () {
                        const noticeId = $(this).data('notice-id');
                        localStorage.setItem(`notice_read_${noticeId}`, 'true');

                        // 更新状态
                        const noticeIndex = window.noticeList.findIndex(n => n.event_id === noticeId);
                        if (noticeIndex !== -1) {
                            window.noticeList[noticeIndex].is_read = true;
                            updateNoticeListUI();
                        }

                        // 关闭模态框
                        bootstrap.Modal.getInstance($('#noticeDetailModal')).hide();
                    });
                }

                // 显示详情模态框
                const modal = new bootstrap.Modal($('#noticeDetailModal'));
                modal.show();
            };

            // 强制阅读且用户未确认
            if (noticeData.force_read === "true" && !noticeData.is_read) {
                showNoticeModal(noticeData, event.event_id);
            } else if (noticeData.force_read !== "true" && !noticeData.is_read) {
                // 显示新公告提示（非强制）
                showToast(`新公告：${noticeData.title}`, 'info')
            }

            // 初始化公告列表UI
            updateNoticeListUI();
            updateUnreadBadge();
            break;
        }
        case 'admin.message.highlight': {

            break;
        }
        default: {
            console.error('未知事件ID: ' + event.event_type);
        }
    }
}

const showNoticeForm = () => {
    const formHTML = `
        <style>
            .notice-editor-wrapper {
                border: 1px solid #ccc;
                z-index: 99999999999999999999;
            }

            #noticeEditorToolbar {
                border-bottom: 1px solid #ccc;
            }

            #noticeEditor {
                height: 900px;
            }
        </style>
        <div class="modal fade" id="noticeFormModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">发布系统公告</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="noticeForm">
                            <div class="mb-3">
                                <label for="noticeTitle" class="form-label">公告标题</label>
                                <input type="text" class="form-control" id="noticeTitle" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">公告内容</label>
                                <div class="notice-editor-wrapper">
                                    <div id="noticeEditorToolbar"></div>
                                    <div id="noticeEditor" style="min-height: 300px;"></div>
                                    <input type="hidden" id="noticeContent">
                                </div>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="isSticky">
                                <label class="form-check-label" for="isSticky">置顶公告</label>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="forceRead">
                                <label class="form-check-label" for="forceRead">强制成员阅读</label>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="button" class="btn btn-primary" id="submitNotice">发布公告</button>
                    </div>
                </div>
            </div>
        </div>`;

    $('body').append(formHTML);
    const modal = new bootstrap.Modal($('#noticeFormModal'));
    modal.show();

    initializeEditor({
        editorSelector: '#noticeEditor',
        toolbarSelector: '#noticeEditorToolbar',
        textareaSelector: '#noticeContent',
        initialContent: '',
    });

    // base64编码函数
    function encodeBase64(str) {
        return window.btoa(unescape(encodeURIComponent(str)));
    }

    // 表单提交处理
    $('#submitNotice').click(() => {
        const noticeData = {
            title: $('#noticeTitle').val().trim(),
            content: $('#noticeContent').val().trim(),
            is_sticky: $('#isSticky').is(':checked'),
            force_read: $('#forceRead').is(':checked'),
            publisher: sessionUsername
        };

        // 验证输入
        if (!noticeData.title) {
            showToast('请输入公告标题', 'danger');
            return;
        }

        if (!noticeData.content) {
            showToast('请输入公告内容', 'danger');
            return;
        }

        // 公告内容base64编码
        const base64Content = encodeBase64(noticeData.content);

        // 自动组合消息内容，内容部分用base64
        const message = `/notice ${noticeData.title} ${base64Content} ${noticeData.is_sticky} ${noticeData.force_read}`;

        // 使用现有的sendMessage函数发送
        sendMessage(message);

        // 关闭模态框
        modal.hide();
        $('#noticeFormModal').remove();
    });

    // 模态框关闭时清理
    $('#noticeFormModal').on('hidden.bs.modal', () => {
        $('#noticeFormModal').remove();
    });
};

/**
 * 更新网络状态
 * @param {bool} isOnline 
 */
function updateNetworkStatus(isOnline) {
    const statusElement = $('.network-status');
    if (isOnline) {
        // 网络恢复
        if (!networkStatus) {
            statusElement.removeClass('alert-warning').addClass('alert-success').html('<strong>成功！</strong>网络已恢复正常。').fadeIn();
            setTimeout(() => {
                statusElement.fadeOut();
            }, 2000);
            networkStatus = true;
        }
    } else {
        if (networkStatus) {
            statusElement.addClass('alert-warning').fadeIn().html('<strong>错误！</strong>您的网络连接有问题。');
            networkStatus = false;
        }
    }
};

// 更新引用预览的函数
function updateReplyPreview() {
    if (!quotedMessage) return;
    const preview = $('#reply-preview');
    preview.html(`
        <div class="reply-preview-content">
            <span>回复 ${quotedMessage.username}:</span>
            <div class="reply-message">${quotedMessage.content.substring(0, 50)}${quotedMessage.content.length > 50 ? '...' : ''}</div>
            <button type="button" id="cancel-reply" class="btn btn-sm btn-link">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `).show();
    $('#message').val(`@${quotedMessage.username}`);
    $('#cancel-reply').click(function (e) {
        e.preventDefault();
        quotedMessage = null;
        $('#reply-preview').hide().empty();
    });
}

const setupAtMentionAutocomplete = () => {
    const messageInput = $('#message');

    messageInput.on('input', function () {
        const cursorPos = this.selectionStart;
        const textBeforeCursor = this.value.substring(0, cursorPos);
        const atPos = textBeforeCursor.lastIndexOf('@');

        if (atPos >= 0 && (atPos === 0 || textBeforeCursor[atPos - 1] === ' ')) {
            const searchTerm = textBeforeCursor.substring(atPos + 1).toLowerCase();
            const matchedUsers = onlineUsersList.filter(user =>
                user.toLowerCase().includes(searchTerm)
            );

            if (matchedUsers.length > 0) {
                showAtMentionSuggestions(matchedUsers, atPos);
            } else {
                hideAtMentionSuggestions();
            }
        } else {
            hideAtMentionSuggestions();
        }
    });

    // 点击外部隐藏建议框
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#at-mention-suggestions').length &&
            !$(e.target).is(messageInput)) {
            hideAtMentionSuggestions();
        }
    });
};

const showAtMentionSuggestions = (users, atPos) => {
    let suggestionsBox = $('#at-mention-suggestions');
    if (suggestionsBox.length === 0) {
        suggestionsBox = $('<div id="at-mention-suggestions"></div>');
        $('body').append(suggestionsBox);
    }

    suggestionsBox.empty();
    users.forEach(user => {
        const userElement = $('<div class="at-mention-suggestion"></div>')
            .text(user)
            .click(function () {
                const messageInput = $('#message');
                const currentValue = messageInput.val();
                const cursorPos = messageInput[0].selectionStart;

                // 替换 @username 部分
                const newValue = currentValue.substring(0, atPos) + '@' + user + ' ' +
                    currentValue.substring(cursorPos);

                messageInput.val(newValue);
                messageInput.focus();
                messageInput[0].setSelectionRange(atPos + user.length + 2, atPos + user.length + 2);
                hideAtMentionSuggestions();
            });
        suggestionsBox.append(userElement);
    });

    // 定位建议框
    const messageInput = $('#message');
    const inputOffset = messageInput.offset();
    const inputHeight = messageInput.outerHeight();

    suggestionsBox.css({
        position: 'absolute',
        left: inputOffset.left + atPos * 8, // 粗略估计字符宽度
        top: inputOffset.top - inputHeight - 50,
        display: 'block'
    });
};

const hideAtMentionSuggestions = () => {
    $('#at-mention-suggestions').hide();
};

// 显示Toast通知
const showToast = (message, type = 'info') => {
    const toast = $(`
        <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true" style="margin-bottom: 10px;">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `);

    $('#toast-container').append(toast);
    const bsToast = new bootstrap.Toast(toast[0]);
    bsToast.show();

    toast.on('hidden.bs.toast', () => {
        toast.remove();
    });
};

/**
 * 通用文件预览处理函数
 * @param {File|Object} file - 可以是File对象或包含{path, name, type}的对象
 * @param {Function} [callback] - 可选回调函数，用于异步处理预览内容
 * @param {boolean} [isMinimized] - 是否最小化预览
 */
const handleFilePreview = (file, callback, isMinimized = false) => {
    // 判断是本地文件还是已上传文件
    const isLocalFile = file instanceof File;
    const fileURL = isLocalFile ? URL.createObjectURL(file) : file.path;
    const fileName = isLocalFile ? file.name : file.name;
    const fileType = isLocalFile ? file.type : file.type;
    const fileExtension = fileName.split('.').pop().toLowerCase();

    // 保存所有媒体状态
    const mediaStates = new Map();

    // 清理之前的预览
    const cleanupPreviousPreview = () => {
        // 保存所有媒体元素状态
        const mediaElements = ['audio-preview', 'video-preview', 'floating-audio', 'floating-video']
            .map(id => document.getElementById(id))
            .filter(el => el);

        mediaElements.forEach(media => {
            mediaStates.set(media.id, {
                isPlaying: !media.paused,
                currentTime: media.currentTime,
                volume: media.volume
            });

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
    };

    // 初始化媒体元素并恢复状态
    const initMediaWithState = async (mediaElement) => {
        try {
            const mediaId = mediaElement.id;
            const savedState = mediaStates.get(mediaId);

            if (!savedState) return;

            // 等待媒体元数据加载完成
            await new Promise((resolve) => {
                if (mediaElement.readyState > 0) {
                    resolve();
                } else {
                    mediaElement.addEventListener('loadedmetadata', resolve, { once: true });
                }
            });

            // 恢复状态
            mediaElement.currentTime = savedState.currentTime;
            mediaElement.volume = savedState.volume;

            // 恢复播放状态（需要用户交互）
            if (savedState.isPlaying) {
                try {
                    await mediaElement.play();
                } catch (e) {
                    console.log("自动播放被阻止，需要用户交互:", e);
                }
            }

            // 清理保存的状态
            mediaStates.delete(mediaId);
        } catch (e) {
            console.error("媒体初始化错误:", e);
        }
    };

    // 保存当前文件预览信息
    cleanupPreviousPreview();
    currentFilePreview = {
        fileURL,
        fileName,
        fileType,
        fileExtension,
        isLocalFile
    };

    const showPreview = (content, isAudio = false) => {
        $('#filePreviewFileInfo').text(fileName);

        if (typeof callback === 'function') {
            callback(content);
        } else {
            filePreviewContent.html(content);
            filePreviewModal.show();
        }

        // 添加最小化按钮（只有在模态框显示后才能添加）
        if (!isAudio && !callback) {
            setTimeout(() => {
                const minimizeBtn = $('<button id="minimize-file" class="btn" style="position: fixed; top: 12px; right: 50px;">')
                    .html('<i class="fa fa-window-minimize"></i>');
                $('#filePreviewModal .modal-content').prepend(minimizeBtn);

                minimizeBtn.on('click', () => {
                    handleFilePreview(file, null, true);
                });
            }, 100);
        }
    };

    // 如果是最小化模式，直接显示浮动预览窗口
    if (isMinimized) {
        try {
            // 隐藏模态框
            filePreviewModal.hide();

            // 更新状态
            isFilePreviewMinimized = true;

            // 移除已存在的文件预览
            $('#floating-file-preview').remove();

            // 根据文件类型创建不同的预览组件
            let previewContent = '';
            const isMatch = (patterns) => patterns.some(ext => fileExtension === ext || fileType.startsWith(ext));

            if (isMatch(['audio'])) {
                previewContent = `
                    <div class="file-preview-header">
                        <span>${fileName}</span>
                        <div class="file-preview-actions">
                            <button class="btn btn-sm btn-outline-secondary maximize-file">
                                <i class="fa fa-window-maximize"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger close-file">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <audio id="floating-audio" playsinline controls style="width: 100%;">
                        <source src="${fileURL}" type="${fileType}">
                        您的浏览器不支持音频播放。
                    </audio>
                    <canvas id="floating-audio-visualizer" style="width: 100%; height: 66%; background: #f0f0f0;"></canvas>`;
            } else if (isMatch(['video'])) {
                previewContent = `
                    <div class="file-preview-header">
                        <span>${fileName}</span>
                        <div class="file-preview-actions">
                            <button class="btn btn-sm btn-outline-secondary maximize-file">
                                <i class="fa fa-window-maximize"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger close-file">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <video id="floating-video" playsinline controls style="width: 100%; height: 85%;">
                        <source src="${fileURL}" type="${fileType}">
                        您的浏览器不支持视频播放。
                    </video>`;
            } else if (isMatch(['image'])) {
                previewContent = `
                    <div class="file-preview-header">
                        <span>${fileName}</span>
                        <div class="file-preview-actions">
                            <button class="btn btn-sm btn-outline-secondary maximize-file">
                                <i class="fa fa-window-maximize"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger close-file">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <img src="${fileURL}" alt="${fileName}" style="max-width: 100%; max-height: 65%; object-fit: contain;">`;
            } else {
                previewContent = `
                    <div class="file-preview-header">
                        <span>${fileName}</span>
                        <div class="file-preview-actions">
                            <button class="btn btn-sm btn-outline-secondary maximize-file">
                                <i class="fa fa-window-maximize"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger close-file">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="text-center py-3">
                        <i class="fa fa-file-o fa-3x mb-2"></i>
                        <a href="${fileURL}" download="${fileName}" class="btn btn-sm btn-primary">
                            <i class="fa fa-download"></i> 下载文件
                        </a>
                    </div>`;
            }

            // 创建浮动文件预览窗口
            const floatingFilePreview = `
                <div id="floating-file-preview" class="floating-file-preview">
                    ${previewContent}
                </div>`;

            // 添加到页面
            $('body').append(floatingFilePreview);

            // 设置初始位置（右下角）
            const preview = $('#floating-file-preview');
            preview.css({
                right: '20px',
                bottom: '20px'
            });

            // 添加拖动功能
            preview.on('mousedown', function (e) {
                if ($(e.target).is('input, button, select, textarea, a, audio, video, img')) {
                    return;
                }

                isDraggingFilePreview = true;
                filePreviewOffsetX = e.clientX - preview.offset().left;
                filePreviewOffsetY = e.clientY - preview.offset().top;

                // 提升z-index确保在最前
                $('.floating-file-preview').css('z-index', '1000');
                $(this).css('z-index', '1001');

                e.preventDefault();
            });

            $(document).on('mousemove.filePreviewEvents', function (e) {
                if (!isDraggingFilePreview) return;

                const x = e.clientX - filePreviewOffsetX;
                const y = e.clientY - filePreviewOffsetY;

                preview.css({
                    left: x + 'px',
                    top: y + 'px',
                    right: 'auto',
                    bottom: 'auto'
                });
            });

            $(document).on('mouseup.filePreviewEvents', function () {
                isDraggingFilePreview = false;
            });

            // 为关闭按钮添加事件
            $('#floating-file-preview .close-file').on('click', () => {
                closeFilePreview();
            });

            // 为最大化按钮添加事件
            $('#floating-file-preview .maximize-file').on('click', () => {
                cleanupPreviousPreview();
                handleFilePreview({
                    path: fileURL,
                    name: fileName,
                    type: fileType
                });
            });

            // 如果是音频文件，初始化可视化
            if (isMatch(['audio'])) {
                const observer = new MutationObserver(async (mutations, obs) => {
                    const floatingAudio = document.getElementById('floating-audio');
                    const audioVisualizer = document.getElementById('floating-audio-visualizer');

                    if (floatingAudio && audioVisualizer) {
                        try {
                            // 初始化可视化
                            initAudioVisualizer(floatingAudio, audioVisualizer, fileURL);

                            // 恢复媒体状态
                            await initMediaWithState(floatingAudio);

                            obs.disconnect();
                        } catch (e) {
                            console.error("音频初始化错误:", e);
                            obs.disconnect();
                        }
                    }
                });

                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }

            // 如果是视频文件，恢复状态
            if (isMatch(['video'])) {
                const observer = new MutationObserver(async (mutations, obs) => {
                    const floatingVideo = document.getElementById('floating-video');

                    if (floatingVideo) {
                        try {
                            // 恢复媒体状态
                            await initMediaWithState(floatingVideo);
                            obs.disconnect();
                        } catch (e) {
                            console.error("视频初始化错误:", e);
                            obs.disconnect();
                        }
                    }
                });

                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }

            return; // 直接返回，不再执行后续的预览逻辑
        } catch (error) {
            console.error("最小化文件预览时出错:", error);
        }
    }

    // 正常预览逻辑
    const isMatch = (patterns) => patterns.some(ext => fileExtension === ext || fileType.startsWith(ext));

    switch (true) {
        case isMatch(['text']) || ['txt', 'json', 'xml', 'log'].includes(fileExtension): {
            if (isLocalFile) {
                const reader = new FileReader();
                reader.onload = e => {
                    const fileContent = e.target.result;
                    showPreview(`<pre><code class="language-${fileExtension}">${fileContent}</code></pre>`);
                    hljs.highlightAll();
                };
                reader.readAsText(file);
            } else {
                // 异步加载文本文件
                $.ajax({
                    type: "GET",
                    url: fileURL,
                    success: function (response) {
                        showPreview(`<pre><code class="language-${fileExtension}">${response}</code></pre>`);
                        hljs.highlightAll();
                    },
                    error: function () {
                        showPreview('<p class="text-center">文件加载失败。</p>');
                    }
                });
            }
            break;
        }
        case isMatch(['image']): {
            showPreview(`<img src="${fileURL}" alt="${fileName}" style="max-width: 100%; max-height: 100%; object-fit: contain;">`);
            break;
        }
        case isMatch(['officedocument']) || ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv'].includes(fileExtension): {
            showPreview(`<iframe src="https://view.officeapps.live.com/op/view.aspx?src=${location.origin}${encodeURIComponent(fileURL)}"></iframe>`);
            break;
        }
        case fileType === 'application/pdf' || fileExtension === 'pdf': {
            showPreview(`<iframe src="${fileURL}"></iframe>`);
            break;
        }
        case isMatch(['audio']) || ['mp3', 'wav', 'ogg', 'aac'].includes(fileExtension): {
            const audioContent = `
                <button id="minimize-audio" class="btn" style="position: fixed; top: 12px; right: 50px;">
                    <i class="fa fa-window-minimize"></i>
                </button>
                <audio id="audio-preview" playsinline controls style="width: 100%; max-height: calc(100vh - 105px)">
                    <source src="${fileURL}" type="${fileType}">
                    您的浏览器不支持音频播放。
                </audio>
                <canvas id="audio-visualizer" style="width: 100%; height: 90%; background: #f0f0f0;"></canvas>`;

            showPreview(audioContent, true);

            // 使用MutationObserver等待元素加载完成
            const observer = new MutationObserver(async (mutations, obs) => {
                const audio = document.getElementById('audio-preview');
                const canvas = document.getElementById('audio-visualizer');
                const minimizeBtn = document.getElementById('minimize-audio');

                if (audio && canvas && minimizeBtn) {
                    try {
                        // 元素已加载，可以初始化可视化
                        initAudioVisualizer(audio, canvas, fileURL);

                        // 恢复媒体状态
                        await initMediaWithState(audio);

                        // 添加最小化按钮事件
                        minimizeBtn.addEventListener('click', () => {
                            cleanupPreviousPreview();
                            handleFilePreview(file, null, true);
                        });

                        obs.disconnect();
                    } catch (e) {
                        console.error("音频初始化错误:", e);
                        obs.disconnect();
                    }
                }
            });

            // 开始观察DOM变化
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
            break;
        }
        case isMatch(['video']) || ['mp4', 'mkv', 'webm', 'mov'].includes(fileExtension): {
            const videoContent = `
                <video id="video-preview" playsinline controls style="width: 100%; max-height: calc(100vh - 105px); background: #000;">
                    <source src="${fileURL}" type="${fileType}">
                    您的浏览器不支持视频播放。
                </video>
            `;

            showPreview(videoContent);

            // 视频预览的最小化按钮
            setTimeout(() => {
                const minimizeBtn = $('<button id="minimize-video" class="btn" style="position: fixed; top: 12px; right: 50px;">')
                    .html('<i class="fa fa-window-minimize"></i>');
                $('#filePreviewModal .modal-content').prepend(minimizeBtn);

                minimizeBtn.on('click', () => {
                    cleanupPreviousPreview();
                    handleFilePreview(file, null, true);
                });

                // 初始化视频状态
                const video = document.getElementById('video-preview');
                if (video) {
                    initMediaWithState(video);
                }
            }, 100);
            break;
        }
        default: {
            showPreview(`<p class="text-center"><br>暂不支持该文件类型的预览。</p>`);
        }
    }

    $('#filePreviewModal .btn-close').click(function (e) {
        // 如果不是最小化导致的关闭，则完全清理
        if (!isFilePreviewMinimized) {
            closeFilePreview();
        }
    });
};