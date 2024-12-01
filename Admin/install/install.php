<?php
require_once __DIR__ . '/../../config.global.php';

// 开始会话
session_start();

if (defined('FRAMEWORK_DATABASE_PATH')) {
    header('Location: /');
    exit;
}
require_once __DIR__ . '/../../vendor/autoload.php';

// 常量配置
define('MSG_INVALID_METHOD', '无效的方法。');
define('MSG_METHOD_NOT_PROVIDED', '方法未提供。');
define('MSG_INVALID_REQUEST_METHOD', '无效的请求方法。');
define('MSG_README_NOT_FOUND', 'README 文件不存在。');
define('MSG_PASSWORD_MISMATCH', '密码不匹配，请重新输入。');
define('MSG_DB_PATH_NOT_WRITABLE', '数据库路径不可写，请检查权限。<a href="?step=3">返回上一步</a>');
define('MSG_DB_CREATION_FAILURE', '无法创建数据库文件，请检查权限。');
define('MSG_FILE_ALREADY_EXISTS', '文件已存在！禁止覆盖。<a href="?step=3">返回上一步</a>');
define('MSG_DB_WRITE_FAILURE', '数据库文件不可写，请检查权限。<a href="?step=3">返回上一步</a>');
define('MSG_SQL_IMPORT_FAILURE', 'SQL 导入失败。');
define('MSG_ADMIN_CREATION_FAILURE', '管理员用户创建失败。');
define('MSG_SITE_CONFIG_FAILURE', '站点配置创建失败。');
define('MSG_SESSION_DESTROY_SUCCESS', '销毁临时会话成功。');
define('MSG_INSTALL_SUCCESS', '安装成功！请在 站点目录下的 config.global.php 文件中设置 define(\'FRAMEWORK_DATABASE_PATH\', \'%s\');');

// 处理异常
function HandleException($e)
{
    echo sprintf('<div class="alert alert-danger" role="alert">哎呀！安装程序出错了：%s</div>', htmlspecialchars($e->getMessage()));
}
set_exception_handler('HandleException');

function readme()
{
    $Parsedown = new Parsedown();
    $readme_file = FRAMEWORK_DIR . '/README.md';
    if (file_exists($readme_file)) {
        return $Parsedown->text(file_get_contents($readme_file));
    }
    return MSG_README_NOT_FOUND;
}

function usageTerms()
{
    $Parsedown = new Parsedown();
    $usageTerms_file = FRAMEWORK_DIR . '/StaticResources/MarkDown/usageTerms.md';
    if (file_exists($usageTerms_file)) {
        return $Parsedown->text(file_get_contents($usageTerms_file));
    }
    return '使用条款文件不存在。';
}

// 获取步骤
$step = $_GET['step'] ?? '';

// 生成 CSRF 令牌
function generateCSRFToken()
{
    return bin2hex(random_bytes(16));
}

function verifyCSRFToken($token)
{
    return hash_equals($_SESSION['csrf_token'], $token);
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = generateCSRFToken();
}

/**
 * 计算进度百分比
 * @var array
 */
$progressSteps = [
    '' => 0,
    '0' => 10,
    '1' => 25,
    '2' => 50,
    '3' => 75,
    '4' => 100
];
$progress = $progressSteps[$step] ?? 0;
