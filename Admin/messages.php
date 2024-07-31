<?php
require_once __DIR__ . "/head.php";
?>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-comments"></i> 消息管理
            </div>
            <div class="card-body">
                <h5 class="card-title">消息列表</h5>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" id="search-input" class="form-control" placeholder="搜索消息...">
                    </div>
                    <div class="col-md-4">
                        <input type="text" id="filter-sender" class="form-control" placeholder="输入发送者...">
                    </div>
                    <div class="col-md-4">
                        <input type="date" id="filter-date" class="form-control">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped" id="message-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>发送者</th>
                                <th>内容</th>
                                <th>时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 这里可以用PHP循环输出实际的消息数据 -->
                            <tr>
                                <td>1</td>
                                <td>用户A</td>
                                <td>这是一条测试消息</td>
                                <td>2024-07-20 10:00:00</td>
                                <td>
                                    <button class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <!-- 更多消息行 -->
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