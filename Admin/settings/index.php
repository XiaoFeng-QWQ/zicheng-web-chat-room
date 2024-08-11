<?php
require_once __DIR__ . "/../helper/common.php";

use ChatRoom\Core\Helpers\SystemSetting;

$systemSetting = new SystemSetting($db);
?>
<form action="/Admin/settings/update_settings.php" method="post">
    <h2>基本设置</h2>
    <div class="mb-3">
        <label for="site_name" class="form-label">网站名称</label>
        <input type="text" class="form-control" id="site_name" name="site_name" value="<?= htmlspecialchars($systemSetting->getSetting('site_name')) ?>">
    </div>
    <div class="mb-3">
        <label for="site_description" class="form-label">网站描述</label>
        <textarea class="form-control" id="site_description" name="site_description" rows="3"><?= htmlspecialchars($systemSetting->getSetting('site_description')) ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary">保存设置</button>
</form>