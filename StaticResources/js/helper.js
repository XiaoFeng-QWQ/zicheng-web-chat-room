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