const checkNotificationPermission = () => {
    if (!("Notification" in window)) return;

    // 如果权限未被设置，显示提示
    if (Notification.permission === "default") {
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
    // 如果权限未被拒绝，但不主动请求，只显示内部提示
    else if (Notification.permission !== "denied") {
        console.log("Notification permission not granted yet");
        // 可以在这里添加一个非侵入式的UI提示，告知用户可以启用通知
        showNotificationPermissionHint();
    }
    // 如果权限被拒绝，不做任何操作
};

const createNotification = (message) => {
    const notification = new Notification(`${message.user_name} 提到了你`, {
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
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `);
        $('body').append(hint);

        // 添加点击事件处理
        $('#enable-notifications').click(requestNotificationPermission);
    }
};

const requestNotificationPermission = () => {
    Notification.requestPermission().then(permission => {
        if (permission === "granted") {
            $('#notification-permission-hint').alert('close');
            // 可以在这里显示一个成功的提示
            showToast('通知权限已启用', 'success');
        } else {
            showToast('通知权限被拒绝', 'warning');
        }
    });
};

const showToast = (message, type) => {
    // 简单的toast通知实现
    const toast = $(`
        <div class="toast align-items-center text-white bg-${type} border-0 position-fixed bottom-0 end-0 m-3" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `);
    $('body').append(toast);
    const toastInstance = new bootstrap.Toast(toast[0]);
    toastInstance.show();

    // 自动消失
    setTimeout(() => {
        toastInstance.hide();
    }, 3000);
};