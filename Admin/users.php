<?php
require_once __DIR__ . "/module/head.php";

use ChatRoom\Core\Helpers\User;

$userHelper = new User();

// 分页参数
$limit = 10; // 每页显示10条记录
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

try {
    // 获取用户总数和分页后的数据
    $totalUsers = $userHelper->getUserCount();
    $usersData = $userHelper->getUsersWithPagination($limit, $offset);
    // 计算总页数
    $totalPages = ceil($totalUsers / $limit);
} catch (Exception $e) {
    echo "无法获取用户列表: " . $e->getMessage();
    $usersData = [];
    $totalPages = 1;
}
?>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-users"></i> 用户管理
            </div>
            <div class="card-body">
                <h5 class="card-title">用户列表</h5>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" id="search-user-input" class="form-control" placeholder="搜索用户...">
                    </div>
                    <div class="col-md-4">
                        <select id="filter-status" class="form-control">
                            <option value="">筛选状态</option>
                            <option value="正常">正常</option>
                            <option value="禁用">禁用</option>
                            <!-- 更多状态选项 -->
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="date" id="filter-reg-date" class="form-control">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped" id="user-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>用户名</th>
                                <th>注册时间</th>
                                <th>注册ip</th>
                                <th>是否为管理员</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($usersData)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">暂无用户数据</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($usersData as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                        <td><?php echo htmlspecialchars($user['register_ip'] ?? '无法获取'); ?></td>
                                        <td>
                                            <?php echo $user['group_id'] == 1 ? '是' : '否'; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick="editUser(<?php echo $user['user_id']; ?>)"><i class="fas fa-edit"></i></button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['user_id']; ?>)"><i class="fas fa-user-slash"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">上一页</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link"><?php echo $page ?> / <?php echo $totalPages ?></a>
                            </li>
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">下一页</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    function editUser(userId) {
        // 使用 jQuery 的 AJAX 请求获取用户信息
        $.ajax({
            url: `/Admin/user/get_user_info.php?user_id=${userId}`,
            type: 'GET',
            dataType: 'json',
            success: function(userInfo) {
                // 动态生成模态框 HTML 结构
                const modalHtml = `
                <!-- 编辑用户信息弹窗 -->
                <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editModalLabel">编辑用户ID为${userInfo.data.user_id}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="POST" id="editUserForm">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">用户名</label>
                                        <input type="text" class="form-control" id="username" name="username" value="${userInfo.data.username}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="group_id" class="form-label">用户组ID (1为管理员, 2为普通用户)</label>
                                        <input type="number" class="form-control" id="group_id" name="group_id" value="${userInfo.data.group_id}" required>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                                <button type="submit" class="btn btn-primary" form="editUserForm">保存更改</button>
                            </div>
                        </div>
                    </div>
                </div>
                `;

                // 将生成的 HTML 插入到页面中
                $('body').append(modalHtml);

                // 显示模态框
                const editModal = new bootstrap.Modal($('#editModal')[0]);
                editModal.show();

                // 表单提交事件处理
                $('#editUserForm').on('submit', function(event) {
                    event.preventDefault();
                    // 使用 jQuery 的 AJAX 请求提交表单数据
                    $.ajax({
                        url: `/Admin/user/edit_user.php?user_id=${userInfo.data.user_id}`,
                        type: 'POST',
                        data: $(this).serialize(),
                        dataType: 'json',
                        success: function(data) {
                            alert(data.success ? '用户信息更新成功' : `用户信息更新失败: ${data.message} ${data.error}`);
                            if (data.success) location.reload();
                        },
                        error: function(xhr, status, error) {
                            console.error('Error updating user:', error);
                            alert(`无法更新用户信息: ${error}`);
                        }
                    });
                });

                // 模态框关闭后，清理模态框 HTML 以防止重复添加
                $('#editModal').on('hidden.bs.modal', function() {
                    $(this).remove();
                });
            },
            error: function(xhr, status, error) {
                console.error('Error fetching user info:', error);
                alert(`无法获取用户信息: ${error}`);
            }
        });
    }

    function deleteUser(userId) {
        if (confirm("确定要删除此用户吗？")) {
            $.ajax({
                url: `/Admin/user/delete_user.php?user_id=${userId}`,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    alert(data.success ? '用户删除成功' : `删除用户失败: ${data.message}`);
                    if (data.success) location.reload();
                },
                error: function(xhr, status, error) {
                    console.error('Error deleting user:', error);
                    alert(`无法删除用户: ${error}`);
                }
            });
        }
    }
</script>
<?php
require_once __DIR__ . '/module/footer.php';
?>