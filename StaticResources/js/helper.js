// 定义常量
const MESSAGE_INDEX_KEY = 'chat_messages_index';  // 存储消息ID列表
const MESSAGE_DATA_PREFIX = 'msg_';              // 单个消息存储的前缀
const MAX_CACHED_MESSAGES = 200;                 // 最大缓存消息数
const MESSAGE_CACHE_KEY = 'chat_messages_cache';
const CACHE_EXPIRATION = 24 * 60 * 60 * 1000; // 24小时缓存有效期
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
 * 获取指定cookie
 * @param {*} key 
 * @returns 
 */
function getCookie(key) {
    const converter = {
        read: function (value) {
            if (value[0] === '"') {
                value = value.slice(1, -1);
            }
            return value.replace(/(%[\dA-F]{2})+/gi, decodeURIComponent)
        },
        write: function (value) {
            return encodeURIComponent(value).replace(
                /%(2[346BF]|3[AC-F]|40|5[BDE]|60|7[BCD])/g,
                decodeURIComponent
            )
        }
    }
    const cookies = document.cookie ? document.cookie.split('; ') : [];
    const jar = {};
    for (let i = 0; i < cookies.length; i++) {
        const parts = cookies[i].split('=');
        const value = parts.slice(1).join('=');

        try {
            const foundKey = decodeURIComponent(parts[0]);
            jar[foundKey] = converter.read(value, foundKey);

            if (key === foundKey) {
                break
            }
        } catch (e) { }
    }
    return key ? jar[key] : jar
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
        case 'admin.announcement.publish': {

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