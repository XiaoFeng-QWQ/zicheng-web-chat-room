<?php
require_once __DIR__ . "/head.php";
?>


<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-cog"></i> 系统设置
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="#" class="list-group-item list-group-item-action"><i class="fas fa-cog"></i> 基本设置</a>
                    <a href="#" class="list-group-item list-group-item-action"><i class="fas fa-user-cog"></i> 用户设置</a>
                    <a href="#" class="list-group-item list-group-item-action"><i class="fas fa-save"></i> 备份数据</a>
                    <a href="#" class="list-group-item list-group-item-action"><i class="fas fa-sync"></i> 检测更新</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-cog"></i> 基本设置
            </div>
            <div class="card-body">
                <form>
                    <div class="mb-3">
                        <label for="siteName" class="form-label">网站名称</label>
                        <input type="text" class="form-control" id="siteName" value="子辰聊天室">
                    </div>
                    <div class="mb-3">
                        <label for="siteDescription" class="form-label">网站描述</label>
                        <textarea class="form-control" id="siteDescription" rows="3">一个友好的在线聊天社区</textarea>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="enableRegistration" checked>
                        <label class="form-check-label" for="enableRegistration">允许新用户注册</label>
                    </div>
                    <button type="submit" class="btn btn-primary">保存设置</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/footer.php';
?>