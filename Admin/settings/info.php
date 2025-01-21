<?php
require_once __DIR__ . "/../helper/common.php";
?>

<div class="container mt-5">
    <!-- 标题和版本信息 -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <h2 class="card-title text-primary">关于</h2>
            <hr>
            <p class="mb-2">
                当前版本：
                <span class="badge bg-primary fs-6"><?= FRAMEWORK_VERSION; ?></span>
                <?php if (defined('FRAMEWORK_DEBUG') && FRAMEWORK_DEBUG): ?>
                    <span class="badge bg-danger fs-6 ms-2">调试模式已启用</span>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- 更新日志 -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-light text-dark">
            <h4 class="mb-0">更新日志</h4>
        </div>
        <div class="card-body">
            <p class="text-muted">以下是最近的更新记录：</p>
            <ul class="list-group list-group-flush">
                <?php
                $changelog = json_decode(file_get_contents(FRAMEWORK_DIR . '/StaticResources/json/CHANGELOG.json'), true);
                foreach ($changelog as $log) {
                    echo "<li class='list-group-item'>{$log['version']}-{$log['date']} {$log['description']}</li>";
                }
                ?>
            </ul>
        </div>
        <div class="card-footer bg-light">
            <p class="text-muted mb-0">详细技术细节访问： <a href="https://blog.zicheng.icu/tag/zichen-caht-room/" target="_blank" class="text-decoration-underline">https://blog.zicheng.icu/tag/zichen-caht-room/</a></p>
        </div>
    </div>
</div>