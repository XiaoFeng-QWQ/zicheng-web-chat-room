<?php
require_once __DIR__ . '/install.php';
?>
<!DOCTYPE html>
<html lang="zh-cn">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>子辰聊天室安装程序 - <?= $progress ?>%</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/nprogress/0.2.0/nprogress.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        #readme,
        #usageTerms {
            overflow: auto;
            max-height: 70vh;
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-radius: 0.25rem;
            border: 1px solid #dee2e6;
        }

        img {
            max-width: 100%;
            height: auto;
        }

        .step {
            display: none;
            animation: fadeIn 0.5s ease;
        }

        .step.active {
            display: block;
        }

        .progress {
            height: 20px;
            margin-bottom: 1.5rem;
        }

        .progress-bar {
            line-height: 20px;
            transition: width 0.6s ease;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .btn-primary {
            padding: 0.5rem 1.5rem;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .database-config {
            display: none;
        }

        .database-config.active {
            display: block;
        }

        .password-toggle {
            position: relative;
        }

        .password-toggle .toggle-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }

        .requirements-list {
            list-style-type: none;
            padding-left: 0;
        }

        .requirements-list li {
            margin-bottom: 0.5rem;
        }

        .requirements-list li i {
            margin-right: 0.5rem;
        }

        .requirement-met {
            color: #28a745;
        }

        .requirement-not-met {
            color: #dc3545;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h1 class="h4 mb-0">子辰聊天室安装程序 <small class="text-white-50">V<?= htmlspecialchars(FRAMEWORK_VERSION) ?></small></h1>
            </div>

            <div class="card-body">
                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated"
                        role="progressbar"
                        style="width: <?= htmlspecialchars($progress) ?>%;"
                        aria-valuenow="<?= htmlspecialchars($progress) ?>"
                        aria-valuemin="0"
                        aria-valuemax="100">
                        <?= htmlspecialchars($progress) ?>%
                    </div>
                </div>

                <?php if ($stepFeedback !== null && $stepFeedback !== true): ?>
                    <div class="mb-4">
                        <?= $stepFeedback ?>
                    </div>
                <?php endif; ?>

                <div class="step <?= ($step === '') ? 'active' : '' ?>">
                    <h2 class="mb-4">欢迎使用子辰聊天室</h2>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> 在开始安装前，请仔细阅读以下信息。
                    </div>

                    <h3 class="h5 mt-4 mb-3">系统要求</h3>
                    <ul class="requirements-list">
                        <li>
                            <i class="fas <?= version_compare(PHP_VERSION, '8.0.0', '>=') ? 'fa-check-circle requirement-met' : 'fa-times-circle requirement-not-met' ?>"></i>
                            PHP 8.0 或更高版本 (当前: <?= htmlspecialchars(PHP_VERSION) ?>)
                        </li>
                        <li>
                            <i class="fas <?= extension_loaded('pdo') ? 'fa-check-circle requirement-met' : 'fa-times-circle requirement-not-met' ?>"></i>
                            PDO 扩展
                        </li>
                        <li>
                            <i class="fas <?= extension_loaded('pdo_mysql') ? 'fa-check-circle requirement-met' : 'fa-times-circle requirement-not-met' ?>"></i>
                            PDO MySQL 驱动 (MySQL需要)
                        </li>
                        <li>
                            <i class="fas <?= extension_loaded('pdo_sqlite') ? 'fa-check-circle requirement-met' : 'fa-times-circle requirement-not-met' ?>"></i>
                            PDO SQLite 驱动 (SQLite需要)
                        </li>
                        <li>
                            <i class="fas <?= extension_loaded('gd') ? 'fa-check-circle requirement-met' : 'fa-times-circle requirement-not-met' ?>"></i>
                            GD 扩展
                        </li>
                        <li>
                            <i class="fas <?= extension_loaded('intl') ? 'fa-check-circle requirement-met' : 'fa-times-circle requirement-not-met' ?>"></i>
                            Intl 扩展
                        </li>
                        <li>
                            <i class="fas <?= extension_loaded('curl') ? 'fa-check-circle requirement-met' : 'fa-times-circle requirement-not-met' ?>"></i>
                            cURL 扩展
                        </li>
                        <li>
                            <i class="fas <?= extension_loaded('sqlite3') ? 'fa-check-circle requirement-met' : 'fa-times-circle requirement-not-met' ?>"></i>
                            SQLite3 扩展
                        </li>
                        <li>
                            <i class="fas <?= extension_loaded('mbstring') ? 'fa-check-circle requirement-met' : 'fa-times-circle requirement-not-met' ?>"></i>
                            mbstring 扩展
                        </li>
                        <li>
                            <i class="fas <?= function_exists('mime_content_type') ? 'fa-check-circle requirement-met' : 'fa-times-circle requirement-not-met' ?>"></i>
                            mime_content_type 函数
                        </li>
                        <li>
                            <i class="fas <?= is_writable(FRAMEWORK_DIR) ? 'fa-check-circle requirement-met' : 'fa-times-circle requirement-not-met' ?>"></i>
                            项目目录可写
                        </li>
                    </ul>

                    <h3 class="h5 mt-4 mb-3">项目介绍</h3>
                    <div id="readme" class="mb-4">
                        <?php try {
                            echo InstallHandler::readMarkdownFile(FRAMEWORK_DIR . '/README.md');
                        } catch (Exception $e) {
                            echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>';
                        } ?>
                    </div>

                    <div class="d-flex justify-content-between">
                        <div></div>
                        <a href="?step=0" class="btn btn-primary">
                            下一步 <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>

                <div class="step <?= ($step === '0') ? 'active' : '' ?>">
                    <h2 class="mb-4">使用条款</h2>
                    <div id="usageTerms" class="mb-4">
                        <?php try {
                            echo InstallHandler::readMarkdownFile(FRAMEWORK_DIR . '/StaticResources/MarkDown/usage.terms.md');
                        } catch (Exception $e) {
                            echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>';
                        } ?>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="?" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i> 上一步
                        </a>
                        <a href="?step=1" class="btn btn-primary">
                            我同意 <i class="fas fa-check ms-2"></i>
                        </a>
                    </div>
                </div>

                <div class="step <?= ($step === '1') ? 'active' : '' ?>">
                    <h2 class="mb-4">设置管理员账户</h2>
                    <form action="?step=1" method="post" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                        <div class="form-group">
                            <label for="admin_name" class="form-label">管理员用户名</label>
                            <input type="text" class="form-control" id="admin_name" name="admin_name" required>
                            <div class="invalid-feedback">
                                请输入管理员用户名
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="admin_email" class="form-label">管理员邮箱</label>
                            <input type="email" class="form-control" id="admin_email" name="admin_email" required>
                            <div class="invalid-feedback">
                                请输入有效的邮箱地址
                            </div>
                        </div>

                        <div class="form-group password-toggle">
                            <label for="admin_pass" class="form-label">管理员密码</label>
                            <input type="password" class="form-control" id="admin_pass" name="admin_pass" minlength="8" required>
                            <i class="fas fa-eye toggle-icon" onclick="togglePassword('admin_pass')"></i>
                            <div class="invalid-feedback">
                                密码长度至少8位
                            </div>
                            <small class="form-text text-muted">
                                密码长度至少8位，建议包含大小写字母、数字和特殊字符
                            </small>
                        </div>

                        <div class="form-group password-toggle">
                            <label for="confirm_pass" class="form-label">确认密码</label>
                            <input type="password" class="form-control" id="confirm_pass" name="confirm_pass" required>
                            <i class="fas fa-eye toggle-icon" onclick="togglePassword('confirm_pass')"></i>
                            <div class="invalid-feedback">
                                两次输入的密码不一致
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="?step=0" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i> 上一步
                            </a>
                            <button type="submit" class="btn btn-primary">
                                下一步 <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="step <?= ($step === '2') ? 'active' : '' ?>">
                    <h2 class="mb-4">数据库配置</h2>
                    <form action="?step=2" method="post" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                        <div class="form-group">
                            <label for="driver" class="form-label">数据库类型</label>
                            <select class="form-select" id="driver" name="driver" required>
                                <option value="mysql">MySQL</option>
                                <option value="sqlite">SQLite(纯文件存储)</option>
                            </select>
                        </div>

                        <!-- MySQL 配置 -->
                        <div id="mysqlConfig" class="database-config active">
                            <div class="form-group">
                                <label for="host" class="form-label">数据库主机</label>
                                <input type="text" class="form-control" id="host" name="host" value="localhost" required>
                            </div>

                            <div class="form-group">
                                <label for="port" class="form-label">端口</label>
                                <input type="number" class="form-control" id="port" name="port" value="3306" required>
                            </div>

                            <div class="form-group">
                                <label for="dbname" class="form-label">数据库名称</label>
                                <input type="text" class="form-control" id="dbname" name="dbname" required>
                            </div>

                            <div class="form-group">
                                <label for="dbusername" class="form-label">用户名</label>
                                <input type="text" class="form-control" id="dbusername" name="dbusername" required>
                            </div>

                            <div class="form-group password-toggle">
                                <label for="dbpassword" class="form-label">密码</label>
                                <input type="password" class="form-control" id="dbpassword" name="dbpassword">
                                <i class="fas fa-eye toggle-icon" onclick="togglePassword('dbpassword')"></i>
                            </div>

                            <div class="form-group">
                                <label for="prefix" class="form-label">表前缀</label>
                                <input type="text" class="form-control" id="prefix" name="prefix" placeholder="可选">
                            </div>

                            <div class="form-group">
                                <label for="charset" class="form-label">字符集</label>
                                <input type="text" class="form-control" id="charset" name="charset" value="utf8mb4" required>
                            </div>
                        </div>

                        <div id="sqliteConfig" class="database-config">
                            <div class="form-group">
                                <label for="sqlite_dbname" class="form-label">数据库文件名</label>
                                <input type="text" class="form-control" id="sqlite_dbname" name="sqlite_dbname" value="<?= uniqid() ?>.db" required>
                                <small class="form-text text-muted">
                                    数据库文件将存储在 Writable 目录下
                                </small>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h3 class="h5 mb-3">站点信息</h3>

                        <div class="form-group">
                            <label for="site_name" class="form-label">站点名称</label>
                            <input type="text" class="form-control" id="site_name" name="site_name" required>
                        </div>

                        <div class="form-group">
                            <label for="site_description" class="form-label">站点描述</label>
                            <textarea class="form-control" id="site_description" name="site_description" rows="3"></textarea>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="?step=1" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i> 上一步
                            </a>
                            <button type="submit" class="btn btn-primary">
                                完成安装 <i class="fas fa-check ms-2"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card-footer text-muted text-center">
                子辰聊天室 &copy; <?= date('Y') ?> - 安装向导
            </div>
        </div>
    </div>

    <script src="/StaticResources/js/jquery.min.js"></script>
    <script src="/StaticResources/js/bootstrap.bundle.min.js"></script>
    <script src="/StaticResources/js/nprogress.min.js"></script>
    <script src="/StaticResources/js/jquery.pjax.min.js"></script>
    <script>
        // Pjax配置
        $(document).pjax('a:not(a[target="_blank"],a[no-pjax])', {
            container: '.card-body',
            fragment: '.card-body',
            timeout: 20000
        })

        $(document).on('pjax:send', function() {
            NProgress.start()
        })

        $(document).on('pjax:complete', function() {
            NProgress.done()
        })

        // 数据库类型切换
        $('#driver').change(function() {
            const driver = $(this).val()

            $('.database-config').removeClass('active')
            if (driver === 'mysql') {
                $('#mysqlConfig').addClass('active')
            } else {
                $('#sqliteConfig').addClass('active')
            }
        }).trigger('change')

        // 密码显示/隐藏切换
        function togglePassword(id) {
            const input = document.getElementById(id)
            const icon = input.nextElementSibling

            if (input.type === 'password') {
                input.type = 'text'
                icon.classList.remove('fa-eye')
                icon.classList.add('fa-eye-slash')
            } else {
                input.type = 'password'
                icon.classList.remove('fa-eye-slash')
                icon.classList.add('fa-eye')
            }
        }
    </script>
</body>

</html>