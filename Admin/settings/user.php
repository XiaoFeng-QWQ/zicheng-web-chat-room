<?php
require_once __DIR__ . "/../helper/common.php";

use ChatRoom\Core\Helpers\SystemSetting;

$systemSetting = new SystemSetting($db);
?>
<form action="/Admin/settings/update_settings.php" method="post">
    <h2>用户设置</h2>
    <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="enable_user_registration" id="enable_user_registration" name="enable_user_registration"
            <?= $systemSetting->getSetting('enable_user_registration') ? "checked" : "" ?>>
        <label class="form-check-label" for="enable_user_registration">允许新用户注册</label>
    </div>
    <button type="submit" class="btn btn-primary">保存设置</button>
</form>