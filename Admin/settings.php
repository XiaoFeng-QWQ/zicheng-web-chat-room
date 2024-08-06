<?php
require_once __DIR__ . "/module/head.php";
?>


<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-cog"></i> 系统设置
            </div>
            <div class="card-body">
                <div id="settings" class="list-group">
                    <a contentName="index.html" class="list-group-item list-group-item-action"><i class="fas fa-cog"></i> 基本设置</a>
                    <a contentName="user.html" class="list-group-item list-group-item-action"><i class="fas fa-user-cog"></i> 用户设置</a>
                    <a contentName="backup.html" class="list-group-item list-group-item-action"><i class="fas fa-save"></i> 备份数据</a>
                    <a contentName="update.html" class="list-group-item list-group-item-action"><i class="fas fa-sync"></i> 检测更新</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div id="settingsContainer" contentName="index.html" class="card mb-4">
            <div class="card-header">
                <i class="fas fa-cog"></i> 设置内容
            </div>
            <div class="card-body">
                <!-- 动态内容将被插入这里 -->
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/module/footer.php';
?>