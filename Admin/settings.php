<?php
require_once __DIR__ . "/module/head.php";
require_once __DIR__ . "/helper/display_toast.php";
?>
<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-cog"></i> 设置列表
            </div>
            <div class="card-body p-0">
                <div id="settings" class="list-group">
                    <a contentName="index.php" class="list-group-item list-group-item-action"><i class="fas fa-cog"></i> 基本设置</a>
                    <a contentName="api.php" class="list-group-item list-group-item-action"><i class="fas fa-sliders-h"></i> API设置</a>
                    <a contentName="diy.php" class="list-group-item list-group-item-action"><i class="fas fa-sliders-h"></i> 自定义设置</a>
                    <a contentName="user.php" class="list-group-item list-group-item-action"><i class="fas fa-user-cog"></i> 用户设置</a>
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
                <?php
                if (isset($_GET['success'])) {
                    $success = $_GET['success'] === 'true';
                    $message = $success ? ($_GET['msg'] ?? '操作成功！') : ($_GET['msg'] ?? '操作失败！');
                    displayToast($message, $success);
                }
                ?>
            </div>
            <div class="card-body">
                <!-- 动态内容将被插入这里 -->
            </div>
        </div>
    </div>
</div>
<link href="https://cdn.bootcdn.net/ajax/libs/wangeditor5/5.1.23/css/style.min.css" rel="stylesheet" />
<script src="/StaticResources/js/wangeditor5.min.js"></script>
<script>
    function initEditorIfNeeded(contentName) {
        if (contentName === 'user.php') {
            initializeEditor({
                editorSelector: '#editor-container',
                toolbarSelector: '#toolbar-container',
                textareaSelector: '#editor',
                initialContent: `<?= $UserHelpers->readUserAgreement(true) ?>`
            });
        }
    }

    function loadSettingsContent(contentName) {
        $('#settingsContainer')
            .attr('contentName', contentName)
            .find('.card-body')
            .load('settings/' + contentName, function() {
                // 内容加载完成后初始化编辑器
                initEditorIfNeeded(contentName);
            });

        window.location.hash = contentName;
    }

    $('#settings .list-group-item').on('click', function(e) {
        e.preventDefault();
        loadSettingsContent($(this).attr('contentName'));
    });

    const defaultContentName = window.location.hash ?
        window.location.hash.substring(1) :
        $('#settingsContainer').attr('contentName') || 'index.php';

    loadSettingsContent(defaultContentName);
</script>
<?php
require_once __DIR__ . '/module/footer.php';
?>