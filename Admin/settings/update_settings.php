<?php

require_once __DIR__ . "/../helper/common.php";

use ChatRoom\Core\Helpers\SystemSetting;

try {
    $systemSetting = new SystemSetting($db);

    $updatedSettings = [];

    if (!empty($_POST['site_name'])) {
        $systemSetting->setSetting('site_name', $_POST['site_name']);
        $updatedSettings[] = '更新站点名称';
    }

    if (!empty($_POST['site_description'])) {
        $systemSetting->setSetting('site_description', $_POST['site_description']);
        $updatedSettings[] = '更新站点描述';
    }

    if (!empty($_POST['api'])) {
        $enableCrossDomain = !empty($_POST['api_enable_cross_domain']) ? 'true' : 'false';
        $systemSetting->setSetting('api_enable_cross_domain', $enableCrossDomain);
        $systemSetting->setSetting('api_cross_domain_allowlist', $_POST['api_cross_domain_allowlist']);
        $updatedSettings[] = '更新API设置';
    }

    if (!empty($_POST['nav_link'])) {
        $navLinks = $_POST['nav_link'] ?? [];
        $navLinksJson = json_encode($navLinks, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $systemSetting->setSetting('nav_link', $navLinksJson);
        $updatedSettings[] = '更新自定义导航栏其他链接';
    }

    if (!empty($_POST['user_agreement'])) {
        $filePath = FRAMEWORK_DIR . '/StaticResources/MarkDown/user.agreement.md';

        // 备份旧的用户协议文件
        backupUserAgreement($filePath);

        file_put_contents($filePath, $_POST['user_agreement']);

        // 更新用户注册设置
        $enableUserRegistration = !empty($_POST['enable_user_registration']) ? 'true' : 'false';
        $systemSetting->setSetting('enable_user_registration', $enableUserRegistration);

        $updatedSettings[] = '更新用户';
    }

    if (!empty($_POST['backup_database'])) {
        $backupFilePath = dirname(FRAMEWORK_DATABASE_PATH) . '/backup.database_' . date('Ymd_His') . '.db';
        if (!copy(FRAMEWORK_DATABASE_PATH, $backupFilePath)) {
            throw new Exception('数据库文件备份失败。');
        }
        $updatedSettings[] = "备份数据库到：$backupFilePath";
    }

    // 设置更新成功后
    $settingsSummary = implode('，', $updatedSettings);
    header("Location: /Admin/settings.php?success=true&msg= $settingsSummary 成功");
    exit;
} catch (Exception $e) {
    header("Location: /Admin/settings.php?success=false&msg=" . urlencode($e));
    exit;
}

/**
 * 备份用户协议文件
 *
 * @param string $filePath 用户协议文件路径
 * @throws Exception 如果备份失败抛出异常
 */
function backupUserAgreement(string $filePath)
{
    if (file_exists($filePath)) {
        $backupFilePath = dirname($filePath) . '/backup.user.agreement_' . date('Ymd_His') . '.md';
        if (!copy($filePath, $backupFilePath)) {
            throw new Exception('用户协议备份失败。');
        }
    }
}
