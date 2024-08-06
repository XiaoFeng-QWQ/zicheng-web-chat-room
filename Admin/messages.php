<?php
require_once __DIR__ . "/module/head.php";

// 查询消息数据
$statement = $db->query('SELECT id, user_name, content, created_at FROM messages');
$messages = $statement->fetchAll(PDO::FETCH_ASSOC);
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
                <div class="select-all-container d-flex align-items-center">
                    <input type="checkbox" id="msg-select-all" class="form-check-input">
                    <label for="select-all">全选</label>
                    <button id="msg-delete-selected" class="btn btn-danger btn-sm ms-2">
                        <i class="fas fa-trash-alt"></i> 删除选中
                    </button>
                    <button id="refresh-list" class="btn btn-secondary btn-sm ms-auto" onclick="loadMessages()">
                        <i class="fas fa-sync-alt"></i> 刷新列表
                    </button>
                </div>
                <table id="message-table" class="table">
                    <thead>
                        <tr>
                            <th>选中</th>
                            <th>ID</th>
                            <th>用户名</th>
                            <th>发布IP</th>
                            <th>内容</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <div id="loading" class="text-center my-3" style="display: none;">
                            <div class="spinner-border" role="status" aria-hidden="true"></div>
                            <p>加载中…</p>
                        </div>
                    </tbody>
                </table>
                <ul class="pagination"></ul>
            </div>
        </div>
    </div>
</div>
<!-- 删除确认弹窗 -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">确认删除</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                确定要删除选中的消息吗？
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-danger" id="confirm-delete">删除</button>
            </div>
        </div>
    </div>
</div>
<?php
require_once __DIR__ . '/module/footer.php';
?>