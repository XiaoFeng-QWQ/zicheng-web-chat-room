<?php
require_once __DIR__ . "/head.php";
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
                                <th>邮箱</th>
                                <th>注册时间</th>
                                <th>状态</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 这里可以用PHP循环输出实际的用户数据 -->
                            <tr>
                                <td>1</td>
                                <td>user001</td>
                                <td>user001@example.com</td>
                                <td>2024-07-01</td>
                                <td><span class="badge bg-success">正常</span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-danger"><i class="fas fa-user-slash"></i></button>
                                </td>
                            </tr>
                            <!-- 更多用户行 -->
                        </tbody>
                    </table>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item disabled">
                                <a class="page-link" href="#" tabindex="-1" aria-disabled="true">上一页</a>
                            </li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item">
                                <a class="page-link" href="#">下一页</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/footer.php';
?>