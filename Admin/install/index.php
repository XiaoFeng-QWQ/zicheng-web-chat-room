<?php
require_once __DIR__ . '/install.php';
?>
<!DOCTYPE html>
<html lang="zh-cn">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>子辰聊天室安装程序 - <?= $progress ?>%</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/nprogress/0.2.0/nprogress.min.css" rel="stylesheet">
    <script src="/StaticResources/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.bootcdn.net/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <?php require_once FRAMEWORK_APP_PATH . '/Views/module/module.highlight.php' ?>
    <style>
        #readme,
        #usageTerms {
            overflow: auto;
            max-height: 70vh;
        }

        img {
            max-width: 100%;
        }

        .step {
            display: none;
            animation: fadeIn 1s;
        }

        .step.active {
            display: block;
        }

        .progress {
            height: 20px;
        }

        .progress-bar {
            line-height: 20px;
            transition: width 0.6s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <div class="progress mb-4">
            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?= $progress; ?>%;">
                <?= $progress; ?>%
            </div>
        </div>
        <div class="step <?= ($step == '') ? 'active' : ''; ?>">
            <h1 class="text-center">欢迎使用子辰聊天室 <i>V<?= FRAMEWORK_VERSION ?></i></h1>
            <h2>描述：</h2>
            <div id="readme" class="mt-4 p-3 border border-primary rounded">
                <?= readme(); ?>
            </div>
            <div class="text-center mt-3">
                <a href="?step=0" class="btn btn-primary">下一步</a>
            </div>
        </div>
        <div class="step <?= ($step == '0') ? 'active' : ''; ?>">
            <h2 class="text-center">使用条款</h2>
            <div id="usageTerms" class="mt-4 p-3 border border-primary rounded">
                <?= usageTerms(); ?>
            </div>
            <div class="text-center mt-3">
                <a href="?step=1" class="btn btn-primary">我同意</a>
            </div>
        </div>
        <div class="step <?= ($step == '1') ? 'active' : ''; ?>">
            <h2 class="text-center">设置管理员</h2>
            <form action="?step=2" method="post" class="mt-4">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
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
        </div>
        <div class="step <?= ($step == '2') ? 'active' : ''; ?>">
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'])) {
                if (!isset($_SESSION['install_step']) || $_SESSION['install_step'] < 2) {
                    $admin_name = $_POST['admin_name'];
                    $admin_pass = $_POST['admin_pass'];
                    $confirm_pass = $_POST['confirm_pass'];

                    if ($admin_pass !== $confirm_pass) {
                        exit('<div class="alert alert-danger">' . INSTALL_CONFIG['ERROR_PASSWORD_MISMATCH']);
                    } else {
                        $_SESSION['admin_name'] = $admin_name;
                        $_SESSION['admin_pass'] = password_hash($admin_pass, PASSWORD_DEFAULT);
                        $_SESSION['csrf_token'] = generateCSRFToken();
                        $_SESSION['install_step'] = 2;
                    }
                }
            }
            ?>
            <h2 class="text-center">站点信息</h2>
            <form action="?step=3" method="post" class="mt-4">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
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
        </div>
        <div class="step <?= ($step == '3') ? 'active' : ''; ?>">
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'])) {
                if (!isset($_SESSION['install_step']) || $_SESSION['install_step'] < 3) {
                    $_SESSION['site_name'] = $_POST['site_name'];
                    $_SESSION['site_description'] = $_POST['site_description'];
                    $_SESSION['csrf_token'] = generateCSRFToken();
                    $_SESSION['install_step'] = 3;
                }
            }
            ?>
            <h2 class="text-center">数据库配置</h2>
            <form action="?step=4" method="post" class="mt-4">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                <div class="mb-3">
                    <label for="database_path" class="form-label">自定义数据库路径</label>
                    <input id="database_path" name="database_path" type="text" class="form-control" required>
                    <div class="form-text">必须是完整路径例如：System/Data/data.db (确保路径可读写)</div>
                </div>
                <button type="submit" class="btn btn-primary">下一步</button>
            </form>
        </div>
        <div class="step <?= ($step == '4') ? 'active' : ''; ?>">
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'])) {
                if (!isset($_SESSION['install_step']) || $_SESSION['install_step'] <= 4) {
                    $database_path = $_POST['database_path'];
                    $fullPath = FRAMEWORK_DIR . '/' . $database_path;

                    $directory = dirname($fullPath);
                    if (!is_writable($directory)) {
                        echo '<div class="alert alert-danger">' . INSTALL_CONFIG['ERROR_DB_PATH_NOT_WRITABLE'] . '</div>';
                    } else {
                        if (file_exists($fullPath)) {
                            echo '<div class="alert alert-danger">' . INSTALL_CONFIG['ERROR_FILE_ALREADY_EXISTS'] . '</div>';
                            exit;
                        }
                        if (!file_exists($fullPath) && !touch($fullPath)) {
                            echo '<div class="alert alert-danger">' . INSTALL_CONFIG['ERROR_DB_CREATION_FAILURE'] . '</div>';
                            exit;
                        }
                        if (is_writable($fullPath)) {
                            $_SESSION['database_path'] = $fullPath;

                            try {
                                $pdo = new PDO("sqlite:$fullPath");
                                $install_sql = file_get_contents(__DIR__ . '/install.sql');
                                if ($pdo->exec($install_sql) === false) {
                                    throw new Exception(INSTALL_CONFIG['ERROR_SQL_IMPORT_FAILURE']);
                                }
                                // 开启事务
                                $pdo->beginTransaction();

                                // 插入管理员用户
                                $stmt = $pdo->prepare("INSERT INTO users (username, password, group_id) VALUES (?, ?, 1)");
                                $stmt->execute([$_SESSION['admin_name'], $_SESSION['admin_pass']]);

                                // 创建站点配置
                                $siteConfig = [
                                    ['site_name', $_SESSION['site_name']],
                                    ['site_description', $_SESSION['site_description']],
                                    ['enable_user_registration', 'true'],
                                    ['nav_link', json_encode([
                                        ['name' => '联系站长', 'link' => 'https://blog.zicheng.icu'],
                                        ['name' => 'Gitee开源地址', 'link' => 'https://gitee.com/XiaoFengQWQ/zichen-web-chat-room']
                                    ], JSON_UNESCAPED_UNICODE)]
                                ];
                                $stmt = $pdo->prepare("INSERT INTO system_sets (name, value) VALUES (?, ?)");
                                foreach ($siteConfig as $config) {
                                    $stmt->execute($config);
                                }

                                // 创建用户组
                                $groups = [
                                    ['管理员'],
                                    ['普通用户']
                                ];
                                $stmt = $pdo->prepare("INSERT INTO groups (group_name) VALUES (?)");
                                foreach ($groups as $config) {
                                    $stmt->execute($config);
                                }

                                // 提交事务
                                $pdo->commit();
                                session_unset();
                                session_destroy();
                                echo sprintf('<div class="alert alert-success">' . INSTALL_CONFIG['SUCCESS'] . '</div>', addslashes($fullPath));
                                exit();
                            } catch (PDOException $e) {
                                // 滚回！
                                $pdo->rollBack();
                                HandleException($e);
                            }
                        } else {
                            echo '<div class="alert alert-danger">' . INSTALL_CONFIG['ERROR_DB_WRITE_FAILURE'] . '</div>';
                        }
                    }
                    $_SESSION['install_step'] = 4;
                }
            } else {
                echo '<div class="alert alert-danger">非法访问！</div>';
            }
            ?>
        </div>
    </div>
    <script src="/StaticResources/js/jquery.min.js"></script>
    <script src="/StaticResources/js/nprogress.min.js"></script>
    <script src="/StaticResources/js/jquery.pjax.min.js"></script>
    <script>
        // Pjax!
        $(document).pjax('a:not(a[target="_blank"],a[no-pjax])', {
            container: '.container',
            fragment: '.container',
            timeout: 20000
        });
        $(document).on('pjax:send', function() {
            NProgress.start();
        });
        $(document).on('pjax:end', function() {
            NProgress.done();
        });
    </script>
</body>

</html>