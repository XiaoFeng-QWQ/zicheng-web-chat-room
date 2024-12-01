<?php
require_once __DIR__ . "/module/head.php";
require_once __DIR__ . "/helper/display_toast.php";
?>


<div class="row">
    <div class="col-md-4">
        <?php
        if (isset($_GET['success'])) {
            $success = $_GET['success'] === 'true';
            $message = $success ? ($_GET['msg'] ?? '操作成功！') : ($_GET['msg'] ?? '操作失败！');
            displayToast($message, $success);
        }
        ?>
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-cog"></i> 设置列表
            </div>
            <div class="card-body">
                <div id="settings" class="list-group">
                    <a contentName="index.php" class="list-group-item list-group-item-action"><i class="fas fa-cog"></i> 基本设置</a>
                    <a contentName="user.php" class="list-group-item list-group-item-action"><i class="fas fa-user-cog"></i> 用户设置</a>
                    <a contentName="diy.php" class="list-group-item list-group-item-action"><i class="fas fa-sliders-h"></i> 自定义设置</a>
                    <a contentName="backup.php" class="list-group-item list-group-item-action"><i class="fas fa-save"></i> 备份数据</a>
                    <a contentName="update.php" class="list-group-item list-group-item-action"><i class="fas fa-sync"></i> 检测更新</a>
                    <a contentName="info.php" class="list-group-item list-group-item-action"><i class="fa-solid fa-info"></i> 关于</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div id="settingsContainer" contentName="index.php" class="card mb-4">
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