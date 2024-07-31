// 初始化 Pjax
$(document).pjax('a:not(a[target="_blank"],a[no-pjax])', {
    container: 'main',
    fragment: 'main',
    timeout: 20000
});

// Pjax 请求发送时显示进度条
$(document).on('pjax:send', NProgress.start);

// Pjax 请求结束时隐藏进度条并重新绑定表单事件
$(document).on('pjax:end', function () {
    NProgress.done();
    bindEvents();
});

/**
 * 绑定事件
 */
function bindEvents() {
    $('#search-input, #filter-sender, #filter-date').on('input change', filterMessages);
    $('#search-user-input, #filter-status, #filter-reg-date').on('input change', filterUsers);
    $('#settings .list-group-item').on('click', function (e) {
        e.preventDefault(); // 阻止默认的链接跳转行为
        let settingsContentName = $(this).attr('contentName');
        $('#settingsContainer').attr('contentName', settingsContentName);
        $('#settingsContainer .card-body').load('settings/' + settingsContentName);
        // 更改链接，添加 #名称
        window.location.hash = settingsContentName;
    });

    // 加载默认内容（是这样的：首先获取链接后面的，如果没有就获取contentName）
    let defaultSettingsContentName = window.location.hash ? window.location.hash.substring(1) : $('#settingsContainer').attr('contentName');
    $('#settingsContainer .card-body').load('settings/' + defaultSettingsContentName);
}

/**
 * 消息过滤
 */
function filterMessages() {
    const searchInput = $('#search-input').val().toLowerCase();
    const filterSender = $('#filter-sender').val().toLowerCase();
    const filterDate = $('#filter-date').val();
    const rows = $('#message-table tbody tr');

    rows.each(function () {
        const id = $(this).find('td').eq(0).text().toLowerCase();
        const sender = $(this).find('td').eq(1).text().toLowerCase();
        const content = $(this).find('td').eq(2).text().toLowerCase();
        const time = $(this).find('td').eq(3).text().toLowerCase();

        const matchesSearch = content.includes(searchInput);
        const matchesSender = filterSender === "" || sender.includes(filterSender);
        const matchesDate = filterDate === "" || time.startsWith(filterDate);

        $(this).toggle(matchesSearch && matchesSender && matchesDate);
    });
}
/**
 * 用户过滤
 */
function filterUsers() {
    const searchInput = $('#search-user-input').val().toLowerCase();
    const filterStatus = $('#filter-status').val().toLowerCase();
    const filterRegDate = $('#filter-reg-date').val();
    const rows = $('#user-table tbody tr');

    rows.each(function () {
        const id = $(this).find('td').eq(0).text().toLowerCase();
        const username = $(this).find('td').eq(1).text().toLowerCase();
        const email = $(this).find('td').eq(2).text().toLowerCase();
        const regTime = $(this).find('td').eq(3).text().toLowerCase();
        const status = $(this).find('td').eq(4).text().toLowerCase();

        const matchesSearch = username.includes(searchInput) || email.includes(searchInput);
        const matchesStatus = filterStatus === "" || status.includes(filterStatus);
        const matchesRegDate = filterRegDate === "" || regTime.startsWith(filterRegDate);

        $(this).toggle(matchesSearch && matchesStatus && matchesRegDate);
    });
}

// 绑定初始事件
bindEvents();