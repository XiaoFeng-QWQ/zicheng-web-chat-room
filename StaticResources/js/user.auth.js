$(document).ready(function () {
    /**
     * 封装的登录 AJAX 请求函数
     * 
     * @param {string} username - 用户名
     * @param {string} password - 密码
     * @param {string} captcha - 验证码
     * @param {jQuery} button - 触发请求的按钮
     */
    function loginUser(username, password, captcha, button) {
        $.ajax({
            url: '/api/user?method=login',
            type: 'POST',
            data: JSON.stringify({
                captcha: captcha,
                username: username,
                password: password
            }),
            contentType: 'application/json',
            dataType: 'JSON',
            beforeSend: function () {
                disableButtonWithLoading(button);
            },
            success: function (response) {
                handleResponse(response, '主页', '/');
            },
            error: function (jqXHR, textStatus, errorThrown) {
                handleError(errorThrown);
                enableButtonWithLoading(button);  // 仅在请求失败时启用按钮
            }
        });
    }
    /**
     * 封装的注册 AJAX 请求函数
     * 
     * @param {string} username - 用户名
     * @param {string} password - 密码
     * @param {string} captcha - 验证码
     * @param {string} confirmPassword - 确认密码
     * @param {jQuery} button - 触发请求的按钮
     */
    function registerUser(username, password, confirmPassword, captcha, button) {
        $.ajax({
            url: '/api/user?method=register',
            type: 'POST',
            data: JSON.stringify({
                captcha: captcha,
                username: username,
                password: password,
                confirm_password: confirmPassword
            }),
            contentType: 'application/json',
            dataType: 'JSON',
            beforeSend: function () {
                disableButtonWithLoading(button);
            },
            success: function (response) {
                handleResponse(response, '首页', '/');
            },
            error: function (jqXHR, textStatus, errorThrown) {
                handleError(errorThrown);
                enableButtonWithLoading(button);  // 仅在请求失败时启用按钮
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
        if (response.code === 200) {
            messageBox.text(`${action}成功，三秒后跳转到${action}页面`);
            messageBox.removeClass().addClass('alert alert-success');
            setTimeout(() => {
                window.location.href = redirectUrl;
            }, 3000);
        } else {
            messageBox.text(response.message || `${action}失败`);
            messageBox.removeClass().addClass('alert alert-danger');
            const submitButton = (action === '登录') ? $('#loginForm').find('button[type="submit"]') : $('#registerForm').find('button[type="submit"]');
            enableButtonWithLoading(submitButton); // 在请求失败时启用按钮
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
    // 将登录功能绑定到表单提交事件
    $('#loginForm').on('submit', function (event) {
        event.preventDefault(); // 防止表单默认提交行为
        const captcha = $('#captcha').val();
        const username = $('#username').val();
        const password = $('#password').val();
        const submitButton = $(this).find('button[type="submit"]');
        loginUser(username, password, captcha, submitButton); // 调用封装的登录函数
    });
    // 将注册功能绑定到表单提交事件
    $('#registerForm').on('submit', function (event) {
        event.preventDefault(); // 防止表单默认提交行为
        const captcha = $('#captcha').val();
        const username = $('#username').val();
        const password = $('#password').val();
        const confirmPassword = $('#confirm_password').val();
        const submitButton = $(this).find('button[type="submit"]');
        registerUser(username, password, confirmPassword, captcha, submitButton); // 调用封装的注册函数
    });
});