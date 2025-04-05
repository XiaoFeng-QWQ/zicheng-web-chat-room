<?php
require_once __DIR__ . '/../../config.global.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../System/Core/Modules/HandleException.php';
set_exception_handler('HandleException');

use ChatRoom\Core\Database\Base;

// 检查是否安装
if (FRAMEWORK_INSTALL_LOCK === false) {
    header('Location: /Admin/install/index.php');
    exit;
}
// 数据库连接
$db = Base::getInstance()->getConnection();