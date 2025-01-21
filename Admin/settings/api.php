<?php
require_once __DIR__ . "/../helper/common.php";

use ChatRoom\Core\Helpers\SystemSetting;

$systemSetting = new SystemSetting($db);
?>
<form action="/Admin/settings/update_settings.php" method="post">
    <h2>API设置</h2>
    <h3><i class="fa-solid fa-triangle-exclamation"></i>如果您使用了 <a href="https://github.com/XiaoFeng-QWQ/zicheng-web-chat-room/tree/HtmlPlugin" target="_blank" rel="noopener noreferrer">简易聊天室</a> 此组件，建议设置此项，否则请保持默认设置，除非您确定知道自己在做什么！</h3>
    <hr>
    <input name="api" value="api" hidden>
    <div class="mb-3 form-check">
        <input type="checkbox" for="api_enable_cross_domain" class="form-check-input" id="api_enable_cross_domain" name="api_enable_cross_domain"
            <?= $systemSetting->getSetting('api_enable_cross_domain') ? 'checked' : '' ?>>
        <label class="form-check-label" for="api_enable_cross_domain">允许API跨域请求</label>
    </div>
    <div class="mb-3">
        <label for="api_cross_domain_allowlist">允许跨域域名列表(请以半角逗号 "," 分割多个域名 填 "*" 为允许所有)</label>
        <textarea type="text" class="form-control" id="api_cross_domain_allowlist" name="api_cross_domain_allowlist"><?= $systemSetting->getSetting('api_cross_domain_allowlist') ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary">保存设置</button>
</form>