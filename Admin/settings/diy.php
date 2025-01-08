<?php
require_once __DIR__ . "/../helper/common.php";

use ChatRoom\Core\Helpers\SystemSetting;

$systemSetting = new SystemSetting($db);

// 获取设置
$navLinkSetting = $systemSetting->getSetting('nav_link');

// 如果是 JSON 字符串，解码为数组
if (is_string($navLinkSetting)) {
    $navLinkSetting = json_decode($navLinkSetting, true);
}

// 如果为空则初始化为空数组
if (!is_array($navLinkSetting)) {
    $navLinkSetting = [];
}
?>

<form action="/Admin/settings/update_settings.php" method="post">
    <h2>自定义设置</h2>
    <hr>
    <div id="nav-links-container">
        <?php foreach ($navLinkSetting as $index => $link): ?>
            <div class="nav-link-item mb-3" data-index="<?= $index ?>">
                <label class="form-label">链接名称</label>
                <input type="text" name="nav_link[<?= $index ?>][name]" class="form-control" value="<?= htmlspecialchars($link['name'], ENT_QUOTES) ?>" required>
                <label class="form-label">链接地址</label>
                <input type="url" name="nav_link[<?= $index ?>][link]" class="form-control" value="<?= htmlspecialchars($link['link'], ENT_QUOTES) ?>" required>
                <button type="button" class="btn btn-danger mt-2 remove-link">删除</button>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="btn-group">
        <button type="button" class="btn btn-secondary" id="add-link">添加链接</button>
        <button type="submit" class="btn btn-primary">保存设置</button>
    </div>
</form>

<script>
    let removedLinks = [];

    // 动态添加链接表单
    $('#add-link').on('click', function() {
        const container = $('#nav-links-container');
        let index = container.children().length;
        var newLink = `
        <div class="nav-link-item mb-3" data-index="${index}">
            <label class="form-label">链接名称</label>
            <input type="text" name="nav_link[${index}][name]" class="form-control" required>
            <label class="form-label">链接地址</label>
            <input type="url" name="nav_link[${index}][link]" class="form-control" required>
            <button type="button" class="btn btn-danger mt-2 remove-link">删除</button>
        </div>
        `;
        container.append(newLink);
    });

    // 事件委托处理删除链接表单
    $('#nav-links-container').on('click', '.remove-link', function() {
        const linkItem = $(this).closest('.nav-link-item');
        var index = linkItem.data('index');

        linkItem.html('');
    });
</script>