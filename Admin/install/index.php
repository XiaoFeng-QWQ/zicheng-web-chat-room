<?php
// 获取步骤
$step = $_GET['step'] ?? '';

require_once __DIR__ . '/install.php';

// 处理POST请求
function processPostRequest($step)
{
    // 提取全局变量
    $csrfToken = $_SESSION['csrf_token'] ?? '';
    $postData = $_POST;

    // CSRF验证
    if (!verifyCSRFToken($csrfToken)) {
        return '<div class="alert alert-danger">' . INSTALL_CONFIG['ERROR_INVALID_REQUEST_METHOD'] . '</div>';
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST'){
        return;
    }

    switch ($step) {
        case '2':
            return handleAdminSetup($postData);
        case '3':
            return handleDatabaseSetup($postData);
        default:
            return;
    }
}

function handleAdminSetup($postData)
{
    if ($postData['admin_pass'] !== $postData['confirm_pass']) {
        return '<div class="alert alert-danger">密码不一致，请重新输入。</div>';
    }

    // 设置管理员信息
    $_SESSION['admin_name'] = $postData['admin_name'];
    $_SESSION['admin_pass'] = password_hash($postData['admin_pass'], PASSWORD_DEFAULT);
    $_SESSION['install_step'] = 2;

    return true;
}

function handleDatabaseSetup($postData)
{
    if (empty($postData)){
        return '数据为空！';
    }
    $_SESSION['site_name'] = $postData['site_name'];
    $_SESSION['site_description'] = $postData['site_description'];

    $fullPath = FRAMEWORK_DATABASE_PATH;
    $directory = dirname($fullPath);
    if (!is_writable($directory)) {
        return '<div class="alert alert-danger">数据库路径不可写，请检查权限。</div>';
    }
    if (!file_exists($fullPath) && !touch($fullPath)) {
        return '<div class="alert alert-danger">无法创建数据库文件，请检查路径权限。</div>';
    }

    try {
        $pdo = new PDO("sqlite:$fullPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 执行SQL导入
        $install_sql = file_get_contents(__DIR__ . '/install.sql');
        if ($pdo->exec($install_sql) === false) {
            throw new Exception('SQL导入失败');
        }

        // 开启事务
        $pdo->beginTransaction();

        // 插入管理员信息
        insertIntoDatabase($pdo, "INSERT INTO users (username, password, group_id) VALUES (?, ?, 1)", [
            $_SESSION['admin_name'],
            $_SESSION['admin_pass']
        ]);

        // 插入站点配置信息
        $siteConfig = [
            ['site_name', $_SESSION['site_name']],
            ['site_description', $_SESSION['site_description']],
            ['enable_user_registration', 'true'],
            ['nav_link', json_encode([
                ['name' => '联系站长', 'link' => 'https://blog.zicheng.icu'],
                ['name' => 'Gitee开源地址', 'link' => 'https://gitee.com/XiaoFengQWQ/zichen-web-chat-room']
            ], JSON_UNESCAPED_UNICODE)]
        ];
        foreach ($siteConfig as $config) {
            insertIntoDatabase($pdo, "INSERT INTO system_sets (name, value) VALUES (?, ?)", $config);
        }

        // 插入默认用户组
        $groups = [['管理员'], ['普通用户']];
        foreach ($groups as $group) {
            insertIntoDatabase($pdo, "INSERT INTO groups (group_name) VALUES (?)", $group);
        }

        // 提交事务
        $pdo->commit();
        session_unset();
        session_destroy();

        return '<div class="alert alert-success">' . INSTALL_CONFIG['SUCCESS'] . '</div>';
    } catch (PDOException $e) {
        // 事务回滚
        $pdo->rollBack();
        throw $e;
    }
}

// 插入数据库的辅助函数
function insertIntoDatabase($pdo, $sql, $params)
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}
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
        <?php
        // 执行POST请求的处理
        $stepFeedback = processPostRequest($step);
        if ($stepFeedback !== true) {
            echo $stepFeedback;
        }
        ?>
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
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? ''; ?>">
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
            <h2 class="text-center">站点信息</h2>
            <form action="?step=3" method="post" class="mt-4">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? ''; ?>">
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