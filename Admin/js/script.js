/**
 * 绑定事件
 */
function bindEvents() {
    $('#search-input, #filter-sender, #filter-date').on('input change', filterMessages);
    $('#search-user-input, #filter-status, #filter-reg-date').on('input change', filterUsers);
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
        const id = $(this).find('td').eq(1).text().toLowerCase();
        const sender = $(this).find('td').eq(2).text().toLowerCase();
        const content = $(this).find('td').eq(3).text().toLowerCase();
        const time = $(this).find('td').eq(4).text().toLowerCase();

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