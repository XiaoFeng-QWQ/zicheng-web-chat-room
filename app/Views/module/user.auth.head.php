<?php

use ChatRoom\Core\Database\SqlLite;
use ChatRoom\Core\Helpers\SystemSetting;

$setting = new SystemSetting(SqlLite::getInstance()->getConnection());
?>
<!DOCTYPE html>
<html lang="zh-cn">

<head>
    <meta charset="UTF-8">
    <title><?= $setting->getSetting('site_name') ?> | 用户验证</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/nprogress/0.2.0/nprogress.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/StaticResources/css/user.auth.css?v=<?php echo FRAMEWORK_VERSION ?>">
    <link rel="stylesheet" href="/StaticResources/css/module.rest.css">
    <meta name="description" content="<?= $setting->getSetting('site_description') ?>">
</head>

<body>
    <div class="container" id="pjaxContainer">