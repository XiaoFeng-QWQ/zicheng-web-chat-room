<?php

use ChatRoom\Core\Database\Base;
use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Helpers\Helpers;
use ChatRoom\Core\Helpers\SystemSetting;

$setting = new SystemSetting(Base::getInstance()->getConnection());
$UserHelpers = new User();
$helpers = new Helpers;

if ($UserHelpers->checkUserLoginStatus()) {
    header("Location: /");
    exit();
}
?>
<!--
 ______     __     ______     __  __     ______     __   __     ______     ______     __  __     ______     ______   ______     ______     ______     __    __    
/\___  \   /\ \   /\  ___\   /\ \_\ \   /\  ___\   /\ "-.\ \   /\  ___\   /\  ___\   /\ \_\ \   /\  __ \   /\__  _\ /\  == \   /\  __ \   /\  __ \   /\ "-./  \   
\/_/  /__  \ \ \  \ \ \____  \ \  __ \  \ \  __\   \ \ \-.  \  \ \ \__ \  \ \ \____  \ \  __ \  \ \  __ \  \/_/\ \/ \ \  __<   \ \ \/\ \  \ \ \/\ \  \ \ \-./\ \  
  /\_____\  \ \_\  \ \_____\  \ \_\ \_\  \ \_____\  \ \_\\"\_\  \ \_____\  \ \_____\  \ \_\ \_\  \ \_\ \_\    \ \_\  \ \_\ \_\  \ \_____\  \ \_____\  \ \_\ \ \_\ 
  \/_____/   \/_/   \/_____/   \/_/\/_/   \/_____/   \/_/ \/_/   \/_____/   \/_____/   \/_/\/_/   \/_/\/_/     \/_/   \/_/ /_/   \/_____/   \/_____/   \/_/  \/_/ 
-->
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
    <style>
        @media screen and (min-width: 768px) {
            .container {
                flex-direction: row;
                /* 在平板及以上设备上改为行方向 */
            }

            .user-auth-container {
                width: 50%;
                /* 占据一半宽度 */
            }

            .user-login-auth-image {
                display: block;
                /* 平板及以上设备显示 */
                background: url('<?= $setting->getSetting('login_left_image') ?>') no-repeat center center;
                background-size: cover;
                width: 50%;
                /* 占据一半宽度 */
                background-position: right;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            }

            .user-register-auth-image {
                display: block;
                /* 平板及以上设备显示 */
                background: url('<?= $setting->getSetting('register_left_image') ?>') no-repeat center center;
                background-size: cover;
                width: 50%;
                /* 占据一半宽度 */
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            }
        }
    </style>
</head>

<body>
    <div class="container" id="pjaxContainer">