// Pjax 初始化以及相关配置
$(document).pjax('a:not(a[target="_blank"],a[no-pjax])', {
    container: '#pjaxContainer',
    fragment: '#pjaxContainer',
    timeout: 20000
});

// Pjax 请求发送时显示进度条
$(document).on('pjax:send', function () {
    NProgress.start();
});

// Pjax 请求结束时隐藏进度条并重新绑定表单事件
$(document).on('pjax:end', function () {
    NProgress.done();
    bindFormEvents();
});

// 绑定初始的表单事件
bindFormEvents();

/**
 * 绑定登录和注册表单的提交事件
 */
function bindFormEvents() {
    // 解除之前的绑定以防止重复绑定
    $('#loginForm').off('submit');
    $('#registerForm').off('submit');

    // 登录表单提交事件
    $('#loginForm').on('submit', function (event) {
        handleFormSubmit(event, loginUser);
    });

    // 注册表单提交事件
    $('#registerForm').on('submit', function (event) {
        handleFormSubmit(event, registerUser);
    });
}

/**
 * 通用表单提交事件处理
 * 
 * @param {Event} event - 事件对象
 * @param {Function} submitFunction - 表单提交处理函数
 */
function handleFormSubmit(event, submitFunction) {
    event.preventDefault(); // 防止默认表单提交行为
    const form = $(event.target);
    const formId = form.attr('id');
    const captcha = $('#captcha').val();
    const username = $('#username').val();
    const password = $('#password').val();
    const confirmPassword = formId === 'registerForm' ? $('#confirm_password').val() : null;
    const submitButton = form.find('button[type="submit"]');

    submitFunction(username, password, confirmPassword, captcha, submitButton);
}

/**
 * 封装的登录 AJAX 请求函数
 * 
 * @param {string} username - 用户名
 * @param {string} password - 密码
 * @param {string} captcha - 验证码
 * @param {jQuery} button - 触发请求的按钮
 * 
 * 
 */
function loginUser(username, password, 我TM是占位符, captcha, button) {
    ajaxRequest('/api/user?method=login', { username, password, captcha }, button, '首页', '/');
}

/**
 * 封装的注册 AJAX 请求函数
 * 
 * @param {string} username - 用户名
 * @param {string} password - 密码
 * @param {string} confirmPassword - 确认密码
 * @param {string} captcha - 验证码
 * @param {jQuery} button - 触发请求的按钮
 */
function registerUser(username, password, confirmPassword, captcha, button) {
    ajaxRequest('/api/user?method=register', { username, password, confirm_password: confirmPassword, captcha }, button, '首页', '/');
}

/**
 * 通用的 AJAX 请求函数
 * 
 * @param {string} url - 请求的 URL
 * @param {Object} data - 请求的数据
 * @param {jQuery} button - 触发请求的按钮
 * @param {string} action - 动作名称
 * @param {string} successRedirect - 成功后的重定向 URL
 */
function ajaxRequest(url, data, button, action, successRedirect) {
    $.ajax({
        url: url,
        type: 'POST',
        data: JSON.stringify(data),
        contentType: 'application/json',
        dataType: 'JSON',
        beforeSend: function () {
            disableButtonWithLoading(button);
        },
        success: function (response) {
            handleResponse(response, action, successRedirect);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            handleError(errorThrown);
            enableButtonWithLoading(button); // 仅在请求失败时启用按钮
        }
    });
}

/**
 * 处理 AJAX 请求成功的响应
 * 
 * @param {Object} response - 服务器返回的响应
 * @param {string} action - 动作名称
 * @param {string} redirectUrl - 成功后的重定向 URL
 */
function handleResponse(response, action, redirectUrl) {
    const messageBox = $('#messageBox');
    const submitButton = $('button[type="submit"]');

    if (response.code === 200) {
        messageBox.text(`${action}成功，三秒后跳转到${action}`);
        messageBox.removeClass().addClass('alert alert-success');
        setTimeout(() => {
            window.location.href = redirectUrl;
        }, 3000);
    } else {
        const message = response.message || `${action}失败`;
        messageBox.text(message).removeClass().addClass('alert alert-danger');
        enableButtonWithLoading(submitButton);
    }
}

/**
 * 处理 AJAX 请求错误的响应
 * 
 * @param {string} errorThrown - 错误信息
 */
function handleError(errorThrown) {
    console.error('Error:', errorThrown);
    const messageBox = $('#messageBox');
    messageBox.text('发生错误，请稍后再试。');
    messageBox.removeClass().addClass('alert alert-danger');
}

/**
 * 禁用按钮并显示加载动画
 * 
 * @param {jQuery} button - 触发请求的按钮
 */
function disableButtonWithLoading(button) {
    button.prop('disabled', true);
    button.data('original-text', button.html());
    button.html(`
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        请求中...
    `);
}

/**
 * 启用按钮并恢复原始文本
 * 
 * @param {jQuery} button - 触发请求的按钮
 */
function enableButtonWithLoading(button) {
    button.prop('disabled', false);
    button.html(button.data('original-text'));
}