const checkNotificationPermission = () => {
    if (!("Notification" in window)) return;

    // 如果权限未被设置且用户没有选择"永不启用"，显示提示
    if (Notification.permission === "default" && localStorage.getItem('never_enable_notifications') !== 'true') {
        showNotificationPermissionHint();
    }
};

const showMentionNotification = (message) => {
    // 检查浏览器通知权限
    if (!("Notification" in window)) {
        console.log("This browser does not support desktop notification");
        return;
    }

    // 检查是否已经显示过通知
    if (localStorage.getItem(`notified_${message.id}`)) {
        return;
    }

    // 如果权限已经授予，创建通知并记录
    if (Notification.permission === "granted") {
        createNotification(message);
        localStorage.setItem(`notified_${message.id}`, 'true');
    }
    // 如果权限未被拒绝且用户没有选择"永不启用"，显示内部提示
    else if (Notification.permission !== "denied" && localStorage.getItem('never_enable_notifications') !== 'true') {
        console.log("Notification permission not granted yet");
        showNotificationPermissionHint();
    }
    // 如果权限被拒绝或用户选择了"永不启用"，不做任何操作
};

const createNotification = (message) => {
    const notification = new Notification(`${message.user_name} @了你`, {
        body: message.content.length > 50 ?
            message.content.substring(0, 50) + '...' :
            message.content,
        icon: message.avatar_url || letterAvatar(message.user_name)
    });

    notification.onclick = () => {
        window.focus();
        const messageElement = $(`#${message.id}`);
        if (messageElement.length) {
            chatBox.stop().animate({
                scrollTop: messageElement.offset().top - chatBox.offset().top + chatBox.scrollTop() - 20
            }, 500);
        }
    };
};

const showNotificationPermissionHint = () => {
    const hintElement = $('#notification-permission-hint');
    if (hintElement.length === 0) {
        const hint = $(`
            <div id="notification-permission-hint" class="alert alert-info alert-dismissible fade show" role="alert">
                启用通知可以及时收到@提及提醒
                <button type="button" class="btn btn-sm btn-primary ml-2" id="enable-notifications">
                    启用通知
                </button>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close" id="never-enable-notifications">
                    <span aria-hidden="true">&times;</span>
                    <span class="sr-only">永不启用</span>
                </button>
            </div>
        `);
        $('body').append(hint);

        // 启用通知按钮
        $('#enable-notifications').click(requestNotificationPermission);

        // 永不启用按钮
        $('#never-enable-notifications').click(() => {
            localStorage.setItem('never_enable_notifications', 'true');
            $('#notification-permission-hint').alert('close');
        });
    }
};

const requestNotificationPermission = () => {
    Notification.requestPermission().then(permission => {
        if (permission === "granted") {
            $('#notification-permission-hint').alert('close');
            showToast('通知权限已启用', 'success');
        } else {
            showToast('通知权限被拒绝', 'warning');
        }
    });
};