<?php
require_once __DIR__ . '/../../config.global.php';
session_start();

if (defined('FRAMEWORK_DATABASE_PATH')) {
    header('Location: /');
    exit;
}
require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * 动态返回上一步链接的函数
 *
 * @return html
 */
function INSTALL_STEP_BACK()
{
    // 检查是否存在 $_GET['step']，如果不存在则设为默认值 1
    $step = isset($_GET['step']) ? $_GET['step'] : 1;
    return '<a href="?step=' . ((int)$step - 1) . '">返回上一步</a>';
}

// 配置数组，包含错误信息和成功信息
define('INSTALL_CONFIG', [
    'ERROR_INVALID_METHOD' => '无效的方法。',
    'ERROR_METHOD_NOT_PROVIDED' => '方法未提供。',
    'ERROR_INVALID_REQUEST_METHOD' => '无效的请求方法。',
    'ERROR_README_NOT_FOUND' => 'README 文件不存在。',
    'ERROR_PASSWORD_MISMATCH' => '密码不匹配，请重新输入。' . INSTALL_STEP_BACK(),
    'ERROR_DB_PATH_NOT_WRITABLE' => '数据库路径不存在或不可写，请检查。' . INSTALL_STEP_BACK(),
    'ERROR_DB_CREATION_FAILURE' => '无法创建数据库文件，请检查权限。' . INSTALL_STEP_BACK(),
    'ERROR_FILE_ALREADY_EXISTS' => '文件已存在！禁止覆盖。' . INSTALL_STEP_BACK(),
    'ERROR_DB_WRITE_FAILURE' => '数据库文件不可写，请检查权限。' . INSTALL_STEP_BACK(),
    'ERROR_SQL_IMPORT_FAILURE' => 'SQL 导入失败。' . INSTALL_STEP_BACK(),
    'ERROR_ADMIN_CREATION_FAILURE' => '管理员用户创建失败。' . INSTALL_STEP_BACK(),
    'ERROR_SITE_CONFIG_FAILURE' => '站点配置创建失败。' . INSTALL_STEP_BACK(),
    'SUCCESS_SESSION_DESTROY' => '销毁临时会话成功。',
    'SUCCESS' => "安装成功！请在站点目录下的 config.global.php 文件中添加 <pre class='language-php'><code>define('FRAMEWORK_DATABASE_PATH', '%s');</code></pre>"
]);


/**
 * 处理异常
 *
 * @param [type] $e
 * @return void
 */
function HandleException($e)
{
    echo sprintf('<div class="alert alert-danger" role="alert">出错了! -> <pre><code class="language-php">%s</code</pre></div>', $e);
}
set_exception_handler('HandleException');

function readme()
{
    $Parsedown = new Parsedown();
    $readme_file = FRAMEWORK_DIR . '/README.md';
    if (file_exists($readme_file)) {
        return $Parsedown->text(file_get_contents($readme_file));
    }
    return INSTALL_CONFIG['ERROR_README_NOT_FOUND'];
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
