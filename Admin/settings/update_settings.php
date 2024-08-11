<?php

require_once __DIR__ . "/../helper/common.php";

use ChatRoom\Core\Helpers\SystemSetting;

try {
    $systemSetting = new SystemSetting($db);

    // 更新站点名称
    if (isset($_POST['site_name'])) {
        $systemSetting->setSetting('site_name', $_POST['site_name']);
    }

    // 更新站点描述
    if (isset($_POST['site_description'])) {
        $systemSetting->setSetting('site_description', $_POST['site_description']);
    }

    // 更新自定义导航栏其他链接
    if (isset($_POST['nav_link'])) {
        // 处理提交的数据
        $navLinks = $_POST['nav_link'] ?? [];

        // 将数据编码为 JSON 字符串
        $navLinksJson = json_encode($navLinks, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        // 保存设置
        $systemSetting->setSetting('nav_link', $navLinksJson);
    }

    // 更新用户注册设置
    $enableUserRegistration = isset($_POST['enable_user_registration']) ? 'true' : 'false';
    $systemSetting->setSetting('enable_user_registration', $enableUserRegistration);

    // 设置更新成功后重定向回设置页面
    header("Location: /Admin/settings.php?success=true&msg=更新成功");
    exit;
} catch (Exception $e) {
    // 如果发生错误，重定向回设置页面并显示错误消息
    header("Location: /Admin/settings.php?success=false&msg=" . urlencode($e->getMessage()));
    exit;
}
