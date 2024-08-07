<?php
require_once __DIR__ . '/install.php';
?>
<!DOCTYPE html>
<html lang="zh-cn">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>子辰聊天室安装程序</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        #readme,
        #usageTerms {
            overflow: auto;
            max-height: 65vh;
        }

        img {
            max-width: 50%;
            max-height: 50%;
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
            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?php echo $progress; ?>%;">
                <?php echo $progress; ?>%
            </div>
        </div>
        <div class="step <?php echo ($step == '') ? 'active' : ''; ?>">
            <h1 class="text-center">欢迎使用子辰聊天室 <i>V<?php echo FRAMEWORK_VERSION ?></i> 安装程序</h1>
            <h2>描述：</h2>
            <div id="readme" class="mt-4 p-3 border border-primary rounded">
                <?php echo readme(); ?>
            </div>
            <div class="text-center mt-3">
                <a href="?step=0" class="btn btn-primary">下一步</a>
            </div>
        </div>
        <div class="step <?php echo ($step == '0') ? 'active' : ''; ?>">
            <h2 class="text-center">使用条款</h2>
            <div id="usageTerms" class="mt-4 p-3 border border-primary rounded">
                <?php echo usageTerms(); ?>
            </div>
            <div class="text-center mt-3">
                <a href="?step=1" class="btn btn-primary">我同意</a>
            </div>
        </div>
        <div class="step <?php echo ($step == '1') ? 'active' : ''; ?>">
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
        </div>
        <div class="step <?php echo ($step == '2') ? 'active' : ''; ?>">
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'])) {
                if (!isset($_SESSION['install_step']) || $_SESSION['install_step'] < 2) {
                    $admin_name = $_POST['admin_name'];
                    $admin_pass = $_POST['admin_pass'];
                    $confirm_pass = $_POST['confirm_pass'];

                    if ($admin_pass !== $confirm_pass) {
                        exit('<div class="alert alert-danger">' . MSG_PASSWORD_MISMATCH . ' <a href="?step=1">返回上一步</a></div>');
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
        </div>
        <div class="step <?php echo ($step == '3') ? 'active' : ''; ?>">
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
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="mb-3">
                    <label for="database_path" class="form-label">数据库路径</label>
                    <input id="database_path" name="database_path" type="text" class="form-control" required>
                    <div class="form-text">必须是完整路径例如：System/Data/data.db (确保路径可读写)</div>
                </div>
                <button type="submit" class="btn btn-primary">下一步</button>
            </form>
        </div>
        <div class="step <?php echo ($step == '4') ? 'active' : ''; ?>">
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'])) {
                if (!isset($_SESSION['install_step']) || $_SESSION['install_step'] < 4) {
                    $database_path = $_POST['database_path'];
                    $fullPath = FRAMEWORK_DIR . '/' . $database_path;

                    $directory = dirname($fullPath);
                    if (!is_writable($directory)) {
                        echo '<div class="alert alert-danger">' . MSG_DB_PATH_NOT_WRITABLE . '</div>';
                    } else {
                        if (file_exists($fullPath)) {
                            echo '<div class="alert alert-danger">' . MSG_FILE_ALREADY_EXISTS . '</div>';
                            exit;
                        }

                        if (!file_exists($fullPath) && !touch($fullPath)) {
                            echo '<div class="alert alert-danger">' . MSG_DB_CREATION_FAILURE . '</div>';
                            exit;
                        }

                        if (is_writable($fullPath)) {
                            $_SESSION['database_path'] = $fullPath;

                            try {
                                $pdo = new PDO('sqlite:' . $fullPath);
                                $install_sql = file_get_contents(__DIR__ . '/install.sql');
                                if ($pdo->exec($install_sql) === false) {
                                    throw new Exception(MSG_SQL_IMPORT_FAILURE);
                                }
                                echo '<div class="alert alert-success">数据库创建完成 <br></div>';

                                $stmt = $pdo->prepare("INSERT INTO users (username, password, group_id) VALUES (?, ?, 1)");
                                if (!$stmt->execute([$_SESSION['admin_name'], $_SESSION['admin_pass']])) {
                                    throw new Exception(MSG_ADMIN_CREATION_FAILURE);
                                }

                                echo '<div class="alert alert-success">管理员用户插入完成 <br></div>';

                                $stmt = $pdo->prepare("INSERT INTO system_sets (name, value) VALUES (?, ?)");
                                if (!$stmt->execute(['site_name', $_SESSION['site_name']]) || !$stmt->execute(['site_description', $_SESSION['site_description']])) {
                                    throw new Exception(MSG_SITE_CONFIG_FAILURE);
                                }

                                echo '<div class="alert alert-success">站点配置插入完成 <br></div>';

                                session_unset();
                                session_destroy();
                                echo '<div class="alert alert-success">' . MSG_SESSION_DESTROY_SUCCESS . '</div>';
                                echo sprintf('<div class="alert alert-success">' . MSG_INSTALL_SUCCESS . '</div>', addslashes($fullPath));
                            } catch (PDOException $e) {
                                HandleException($e);
                            } catch (Exception $e) {
                                HandleException($e);
                            }
                        } else {
                            echo '<div class="alert alert-danger">' . MSG_DB_WRITE_FAILURE . '</div>';
                        }
                    }
                    $_SESSION['install_step'] = 4;
                }
            } else {
                echo '<div class="alert alert-danger">非法访问！</div>';
                exit;
            }
            ?>
        </div>
    </div>
    <script src="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>

</html>