<?php
require_once __DIR__ . "/../helper/common.php";

use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Helpers\SystemSetting;

$systemSetting = new SystemSetting($db);
$UserHelpers = new User();
?>
<style>
    #editor-wrapper {
        border: 1px solid #ccc;
        z-index: 99999999999999999999;
    }

    #toolbar-container {
        border-bottom: 1px solid #ccc;
    }

    #editor-container {
        height: 900px;
    }
</style>
<form action="/Admin/settings/update_settings.php" method="post">
    <h2>用户设置</h2>
    <hr />
    <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="enable_user_registration" name="enable_user_registration"
            <?= $systemSetting->getSetting('enable_user_registration') ? 'checked' : '' ?>>
        <label class="form-check-label" for="enable_user_registration">允许新用户注册</label>
    </div>

    <div class="mb-3">
        <label class="form-check-label" for="user_agreement">自定义用户协议(支持MD格式)</label>
        <div id="editor-wrapper">
            <div id="toolbar-container"></div>
            <div id="editor-container"></div>
            <input type="hidden" id="editor" name="user_agreement" />
        </div>
    </div>
    <button type="submit" class="btn btn-primary">保存设置</button>
</form>