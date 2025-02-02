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

// 更新事件
const updateEvent = () => {
    $.ajax({
        url: `/api/v1/event?offset=${eventOffset}&limit=20`,
        type: 'POST',
        dataType: 'json',
        success: function (response) {
            if (response.code === 200) {
                const data = response.data;
                if (Array.isArray(data.event)) {
                    data.event.forEach(evetn => parsingEvent(evetn));
                    eventOffset += data.event.length;
                } else {
                    displayErrorMessage('事件API错误！');
                }
            } else {
                displayErrorMessage('事件API错误！');
                scrollToBottom();
            }
        }
    });
}

// 处理事件
const parsingEvent = (evetn) => {
    switch (evetn.event_type) {
        case 'message.revoke': {
            $(`#${evetn.target_id}`).removeClass('chat-message left right').addClass('event').html(`
                    <div>${evetn.additional_data}</div>
                    <span class="timestamp">${evetn.created_at}</span>`);
            break;
        }
        case 'admin.announcement.publish': {

            break;
        }
        case 'admin.message.highlight': {

            break;
        }
        default: {
            console.error('未知事件ID: ' + evetn.event_type);
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

function debug() {
    const time = new Date().toISOString();
    console.debug(`[${time}] Debugging Info:`);
    console.debug(`offset: ${offset}`);
    console.debug(`isAtTop: ${isAtTop}`);
    console.debug(`lastOffset: ${lastOffset}`);
    console.debug(`lastFetched: ${lastFetched}`);
    console.debug(`lastScrollTop: ${lastScrollTop}`);
    console.debug(`isUserScrolling: ${isUserScrolling}`);
    console.debug(`loadingMessages: ${loadingMessages}`);
    console.debug('===========END============');
}