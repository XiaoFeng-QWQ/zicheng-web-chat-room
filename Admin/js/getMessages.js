let currentPage = 1;
let totalPages = 1;

/**
 * 显示加载动画
 */
function showLoading() {
    $('#loading').show();
}

/**
 * 隐藏加载动画
 */
function hideLoading() {
    $('#loading').hide();
}

/**
 * 加载消息
 * @param {number} page 
 */
function loadMessages(page) {
    showLoading();
    $.get('message/get_messages.php', { page }, function (response) {
        hideLoading();
        const { messages, totalPages: newTotalPages, currentPage: newCurrentPage } = response;
        currentPage = newCurrentPage;
        totalPages = newTotalPages;

        const tbody = $('#message-table tbody');
        tbody.empty();

        messages.forEach(({ id, user_name, user_ip, content, created_at, type }) => {
            const row = `
                <tr data-id="${id}">
                    <td><input type="checkbox" class="select-checkbox form-check-input"></td>
                    <td>${id}</td>
                    <td>${user_name}</td>
                    <td>${user_ip}</td>
                    <td>${content}</td>
                    <td>${type}</td>
                    <td>${created_at}</td>
                </tr>`;
            tbody.append(row);
        });

        updatePagination(totalPages);
    }).fail(function () {
        hideLoading();
        alert('加载消息失败，请稍后重试');
    });
}

/**
 * 更新分页
 * @param {number} totalPages 
 */
function updatePagination(totalPages) {
    const pagination = $('.messagesPagination');
    pagination.empty();

    const prevDisabled = currentPage === 1 ? 'disabled' : '';
    const nextDisabled = currentPage === totalPages ? 'disabled' : '';

    pagination.append(`
    <li class="page-item ${prevDisabled}" id="prev-page"><a class="page-link" href="#" tabindex="-1" aria-disabled="${prevDisabled}">上一页</a></li>
    <li class="page-item"><a class="page-link">${currentPage} / ${totalPages}</a></li>
    <li class="page-item ${nextDisabled}" id="next-page"><a class="page-link" href="#" aria-disabled="${nextDisabled}">下一页</a></li>
    `);
  
}

/**
 * 事件绑定
 */
function msgBindEvents() {
    $('.messagesPagination').on('click', 'a', function (e) {
        e.preventDefault();
        const page = parseInt($(this).text());
        if (!isNaN(page)) {
            loadMessages(page);
        } else if ($(this).parent().attr('id') === 'prev-page' && currentPage > 1) {
            loadMessages(currentPage - 1);
        } else if ($(this).parent().attr('id') === 'next-page' && currentPage < totalPages) {
            loadMessages(currentPage + 1);
        }
    });

    $('#msg-select-all').click(function () {
        $('.select-checkbox').prop('checked', this.checked);
    });

    $('#msg-delete-selected').click(function () {
        const selectedMessages = $('.select-checkbox:checked').not('#select-all');
        if (selectedMessages.length === 0) {
            alert('请选择要删除的消息');
            return;
        }
        $('#deleteModal').modal('show');
    });

    $('#confirm-delete').click(function () {
        $('#deleteModal').modal('hide');
        const selectedMessages = $('.select-checkbox:checked').not('#select-all');
        selectedMessages.each(function () {
            const id = $(this).closest('tr').data('id');
            $.post('message/delete_message.php', { id }, function (response) {
                if (response.success) {
                    $(this).closest('tr').remove();
                    loadMessages(1);
                } else {
                    alert('删除失败');
                }
            }.bind(this));
        });
    });
}
msgBindEvents()
loadMessages(currentPage);