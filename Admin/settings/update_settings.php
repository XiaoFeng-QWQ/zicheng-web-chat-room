<?php

require_once __DIR__ . "/../helper/common.php";

use ChatRoom\Core\Helpers\SystemSetting;

try {
    $systemSetting = new SystemSetting($db);

    $updatedSettings = [];

    // 更新站点名称
    if (!empty($_POST['site_name'])) {
        $systemSetting->setSetting('site_name', $_POST['site_name']);
        $updatedSettings[] = '站点名称';
    }

    // 更新站点描述
    if (!empty($_POST['site_description'])) {
        $systemSetting->setSetting('site_description', $_POST['site_description']);
        $updatedSettings[] = '站点描述';
    }

    // 更新自定义导航栏其他链接
    if (!empty($_POST['nav_link'])) {
        $navLinks = $_POST['nav_link'] ?? [];
        $navLinksJson = json_encode($navLinks, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $systemSetting->setSetting('nav_link', $navLinksJson);
        $updatedSettings[] = '自定义导航栏其他链接';
    }

    // 更新用户注册设置
    $enableUserRegistration = isset($_POST['enable_user_registration']) ? 'true' : 'false';
    $systemSetting->setSetting('enable_user_registration', $enableUserRegistration);

    // 更新用户协议
    if (!empty($_POST['user_agreement'])) {
        $filePath = FRAMEWORK_DIR . '/StaticResources/MarkDown/UserAgreement.md';

        // 备份旧的用户协议文件
        backupUserAgreement($filePath);

        file_put_contents($filePath, $_POST['user_agreement']);
        $updatedSettings[] = '用户协议';
    }

    // 备份数据
    if (!empty($_POST['backup_database'])) {
        $backupFilePath = dirname(FRAMEWORK_DATABASE_PATH) . '/database_backup_' . date('Ymd_His') . '.db';
        if (!copy(FRAMEWORK_DATABASE_PATH, $backupFilePath)) {
            throw new Exception('数据库文件备份失败。');
        }
        $updatedSettings[] = "备份数据库到：$backupFilePath";
    }


    // 设置更新成功后重定向回设置页面
    $settingsSummary = implode('，', $updatedSettings);
    header("Location: /Admin/settings.php?success=true&msg=更新设置成功");
    exit;
} catch (Exception $e) {
    // 如果发生错误，重定向回设置页面并显示错误消息
    header("Location: /Admin/settings.php?success=false&msg=" . urlencode($e->getMessage()));
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
        $backupFilePath = dirname($filePath) . '/UserAgreement_backup_' . date('Ymd_His') . '.md';
        if (!copy($filePath, $backupFilePath)) {
            throw new Exception('用户协议备份失败。');
        }
    }
}
