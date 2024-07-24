<?php
require_once __DIR__ . '/../../System/Core/Define.php';

// 开始会话
session_start();

if (defined('FRAMEWORK_DATABASE_PATH')) {
    // 你都安装了还访问这个页面
    header('Location: /');
    exit;
}
require_once __DIR__ .'/../../vendor/autoload.php';

// 常量配置
define('MSG_SUCCESS', 'Success!');
define('MSG_INVALID_METHOD', '无效的方法。');
define('MSG_METHOD_NOT_PROVIDED', '方法未提供。');
define('MSG_INVALID_REQUEST_METHOD', '无效的请求方法。');
define('MSG_README_NOT_FOUND', 'README 文件不存在。');
define('MSG_PASSWORD_MISMATCH', '密码不匹配，请重新输入。');
define('MSG_DB_PATH_NOT_WRITABLE', '数据库路径不可写，请检查权限。');
define('MSG_DB_CREATION_FAILURE', '无法创建数据库文件，请检查权限。');
define('MSG_FILE_ALREADY_EXISTS', '文件已存在！禁止覆盖。');
define('MSG_DB_WRITE_FAILURE', '数据库文件不可写，请检查权限。');
define('MSG_SQL_IMPORT_FAILURE', 'SQL 导入失败。');
define('MSG_ADMIN_CREATION_FAILURE', '管理员用户创建失败。');
define('MSG_SITE_CONFIG_FAILURE', '站点配置创建失败。');
define('MSG_SESSION_DESTROY_SUCCESS', '销毁临时会话成功。');
define('MSG_INSTALL_SUCCESS', '安装成功！请在 System/Core/Define.php 文件中设置 define(\'FRAMEWORK_DATABASE_PATH\', \'%s\');');

// 处理异常
function handleException($e)
{
    echo sprintf('<div class="alert alert-danger" role="alert">哎呀！安装程序出错了：%s</div>', htmlspecialchars($e->getMessage()));
}
set_exception_handler('handleException');

function readme()
{
    $Parsedown = new Parsedown();
    $readme_file = FRAMEWORK_DIR . '/README.md';
    if (file_exists($readme_file)) {
        return $Parsedown->text(file_get_contents($readme_file));
    }
    return MSG_README_NOT_FOUND;
}

// 获取步骤
$step = $_GET['step'] ?? '';
// 生成或验证 CSRF 令牌
function generateCSRFToken() {
    return bin2hex(random_bytes(16));
}

function verifyCSRFToken($token) {
    return hash_equals($_SESSION['csrf_token'], $token);
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = generateCSRFToken();
}
?>
<!DOCTYPE html>
<html lang="zh-cn">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>子辰聊天室安装程序</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <?php
        switch ($step) {
            case '1':
        ?>
                <h2 class="text-center">设置管理员</h2>
                <form action="?step=2" method="post" class="mt-4">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="mb-3">
                        <label for="admin_name" class="form-label">设置管理员用户名</label>
                        <input id="admin_name" name="admin_name" type="text" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="admin_pass" class="form-label">设置管理员密码</label>
                        <input id="admin_pass" name="admin_pass" type="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_pass" class="form-label">确认密码</label>
                        <input id="confirm_pass" type="password" name="confirm_pass" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">下一步</button>
                </form>
            <?php
                break;
            case '2':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'])) {
                    $admin_name = $_POST['admin_name'];
                    $admin_pass = $_POST['admin_pass'];
                    $confirm_pass = $_POST['confirm_pass'];

                    if ($admin_pass !== $confirm_pass) {
                        exit('<div class="alert alert-danger">' . MSG_PASSWORD_MISMATCH . ' <a href="?step=1">返回上一步</a></div>');
                    } else {
                        $_SESSION['admin_name'] = $admin_name;
                        $_SESSION['admin_pass'] = password_hash($admin_pass, PASSWORD_DEFAULT);
                        $_SESSION['csrf_token'] = generateCSRFToken();
                    }
                }
            ?>
                <h2 class="text-center">站点信息</h2>
                <form action="?step=3" method="post" class="mt-4">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="mb-3">
                        <label for="site_name" class="form-label">站点名称</label>
                        <input id="site_name" name="site_name" type="text" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="site_description" class="form-label">站点介绍</label>
                        <input id="site_description" type="text" name="site_description" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary">下一步</button>
                </form>
            <?php
                break;
            case '3':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'])) {
                    $_SESSION['site_name'] = $_POST['site_name'];
                    $_SESSION['site_description'] = $_POST['site_description'];
                    $_SESSION['csrf_token'] = generateCSRFToken();
                }
            ?>
                <h2 class="text-center">数据库配置</h2>
                <form action="?step=4" method="post" class="mt-4">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="mb-3">
                        <label for="database_path" class="form-label">数据库路径</label>
                        <input id="database_path" name="database_path" type="text" class="form-control" required>
                        <div class="form-text">必须是完整路径例如：System/Data/data.db (确保路径可读写)</div>
                    </div>
                    <button type="submit" class="btn btn-primary">下一步</button>
                </form>
            <?php
                break;
            case '4':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'])) {
                    $database_path = $_POST['database_path'];
                    $fullPath = FRAMEWORK_DIR . '/' . $database_path;

                    // 检查父目录是否可写
                    $directory = dirname($fullPath);
                    if (!is_writable($directory)) {
                        echo '<div class="alert alert-danger">' . MSG_DB_PATH_NOT_WRITABLE . '</div>';
                    } else {
                        // 检查文件是否已存在
                        if (file_exists($fullPath)) {
                            echo '<div class="alert alert-danger">' . MSG_FILE_ALREADY_EXISTS . '</div>';
                            exit;
                        }

                        // 尝试创建文件，并再次检查可写权限
                        if (!file_exists($fullPath) && !touch($fullPath)) {
                            echo '<div class="alert alert-danger">' . MSG_DB_CREATION_FAILURE . '</div>';
                            exit;
                        }

                        if (is_writable($fullPath)) {
                            $_SESSION['database_path'] = $fullPath;

                            try {
                                // 连接数据库
                                $pdo = new PDO('sqlite:' . $fullPath);
                                $install_sql = file_get_contents(__DIR__ . '/install.sql');
                                if ($pdo->exec($install_sql) === false) {
                                    throw new Exception(MSG_SQL_IMPORT_FAILURE);
                                }
                                echo '<div class="alert alert-success">数据库创建完成 <br></div>';

                                // 插入管理员用户
                                $stmt = $pdo->prepare("INSERT INTO users (username, password, group_id) VALUES (?, ?, 1)");
                                if (!$stmt->execute([$_SESSION['admin_name'], $_SESSION['admin_pass']])) {
                                    throw new Exception(MSG_ADMIN_CREATION_FAILURE);
                                }

                                echo '<div class="alert alert-success">管理员用户插入完成 <br></div>';

                                // 插入站点配置
                                $stmt = $pdo->prepare("INSERT INTO system_sets (name, value) VALUES (?, ?)");
                                if (!$stmt->execute(['site_name', $_SESSION['site_name']]) || !$stmt->execute(['site_description', $_SESSION['site_description']])) {
                                    throw new Exception(MSG_SITE_CONFIG_FAILURE);
                                }

                                echo '<div class="alert alert-success">站点配置插入完成 <br></div>';

                                // 安装完成
                                session_unset();
                                session_destroy();
                                echo '<div class="alert alert-success">' . MSG_SESSION_DESTROY_SUCCESS . '</div>';
                                echo sprintf('<div class="alert alert-success">' . MSG_INSTALL_SUCCESS . '</div>', addslashes($fullPath));
                            } catch (PDOException $e) {
                                handleException($e);
                            } catch (Exception $e) {
                                handleException($e);
                            }
                        } else {
                            echo '<div class="alert alert-danger">' . MSG_DB_WRITE_FAILURE . '</div>';
                        }
                    }
                }
                break;
            default:
            ?>
                <h1 class="text-center">欢迎使用子辰聊天室 <i>V<?php echo FRAMEWORK_VERSION ?></i> 安装程序</h1>
                <div class="mt-4 p-3 border border-primary rounded">
                    <?php echo readme(); ?>
                </div>
                <div class="text-center mt-3">
                    <a href="?step=1" class="btn btn-primary">那我们开始吧！</a>
                </div>
        <?php
                break;
        }
        ?>
    </div>
    <script src="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>

</html>