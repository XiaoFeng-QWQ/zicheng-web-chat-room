<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config.global.php';
require_once __DIR__ . '/../../vendor/autoload.php';

session_start();

// 检查是否已安装
if (defined('FRAMEWORK_INSTALL_LOCK') && FRAMEWORK_INSTALL_LOCK === true) {
    header('Location: /');
    exit;
}

/**
 * 安装配置类
 */
class InstallConfig
{
    public const ERROR_INVALID_METHOD = '无效的方法。';
    public const ERROR_METHOD_NOT_PROVIDED = '方法未提供。';
    public const ERROR_INVALID_REQUEST_METHOD = '无效的请求方法。';
    public const ERROR_README_NOT_FOUND = 'README 文件不存在。';
    public const ERROR_PASSWORD_MISMATCH = '密码不匹配，请重新输入。';
    public const ERROR_DB_PATH_NOT_WRITABLE = '数据库路径不存在或不可写，请检查。';
    public const ERROR_DB_CREATION_FAILURE = '无法创建数据库文件，请检查权限。';
    public const ERROR_FILE_ALREADY_EXISTS = '文件已存在！禁止覆盖。';
    public const ERROR_DB_WRITE_FAILURE = '数据库文件不可写，请检查权限。';
    public const ERROR_SQL_IMPORT_FAILURE = 'SQL 导入失败。';
    public const ERROR_ADMIN_CREATION_FAILURE = '管理员用户创建失败。';
    public const ERROR_SITE_CONFIG_FAILURE = '站点配置创建失败。';
    public const ERROR_CONFIG_WRITE_FAILURE = '配置文件写入失败。';
    public const SUCCESS_SESSION_DESTROY = '销毁临时会话成功。';
    public const SUCCESS = '安装成功！<a href="/">点我访问前台</a> 或者 <a href="/Admin/index.php">点我访问后台</a>。';

    public const PROGRESS_STEPS = [
        '' => 0,
        '0' => 10,
        '1' => 50,
        '2' => 100,
    ];
}

/**
 * 安装处理器
 */
class InstallHandler
{
    private static $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 处理异常
     */
    public static function handleException(Throwable $e): void
    {
        $errorMessage = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        $errorTrace = htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8');

        echo sprintf(
            '<div class="alert alert-danger" role="alert">出错了! <pre><code class="language-php">%s</code></pre><details><summary>Stack trace</summary><pre>%s</pre></details></div>',
            $errorMessage,
            $errorTrace
        );
    }

    /**
     * 动态返回上一步链接
     */
    public static function getBackLink(?string $step): string
    {
        $step = $step ?? '1';
        return '<a href="?step=' . ((int)$step - 1) . '" class="btn btn-secondary">返回上一步</a>';
    }

    /**
     * 读取Markdown文件
     */
    public static function readMarkdownFile(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException(InstallConfig::ERROR_README_NOT_FOUND);
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new RuntimeException("无法读取文件: {$filePath}");
        }

        $Parsedown = new Parsedown();
        //$Parsedown->setSafeMode(true);
        return $Parsedown->text($content);
    }

    /**
     * 生成CSRF令牌
     */
    public static function generateCSRFToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * 验证CSRF令牌
     */
    public static function verifyCSRFToken(string $token): bool
    {
        if (!isset($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * 写入配置文件
     */
    public static function writeConfigFile(array $dbConfig): void
    {
        if ($dbConfig['driver'] === 'sqlite') {
            $dbConfig['host'] = FRAMEWORK_DIR . '/Writable/' . basename($dbConfig['dbname']);
        }
        $configContent = <<<PHP

/**
 * 数据库配置
 */
define('FRAMEWORK_DATABASE', [
    'driver' => '{$dbConfig['driver']}',
    'host' => '{$dbConfig['host']}',
    'port' => {$dbConfig['port']},
    'dbname' => '{$dbConfig['dbname']}',
    'username' => '{$dbConfig['dbusername']}',
    'password' => '{$dbConfig['dbpassword']}',
    'charset' => '{$dbConfig['charset']}',
]);

/**
 * 安装锁
 */
define('FRAMEWORK_INSTALL_LOCK', true);

PHP;

        $configFile = FRAMEWORK_DIR . '/config.global.php';
        $result = file_put_contents($configFile, $configContent, FILE_APPEND);

        if ($result === false) {
            throw new RuntimeException(InstallConfig::ERROR_CONFIG_WRITE_FAILURE);
        }

        // 设置文件权限
        chmod($configFile, 0644);
    }

    /**
     * 导入SQL
     */
    public static function importSQL(array $dbConfig): PDO
    {
        try {
            if ($dbConfig['driver'] === 'sqlite') {
                return self::setupSQLite($dbConfig);
            }

            return self::setupMySQL($dbConfig);
        } catch (PDOException $e) {
            throw new RuntimeException(InstallConfig::ERROR_SQL_IMPORT_FAILURE . ': ' . $e->getMessage());
        }
    }

    private static function setupSQLite(array $dbConfig): PDO
    {
        $writableDir = FRAMEWORK_DIR . '/Writable';
        if (!is_dir($writableDir)) {
            if (!mkdir($writableDir, 0775, true)) {
                throw new RuntimeException("无法创建Writable目录: {$writableDir}");
            }
        }

        $dbPath = $writableDir . '/' . basename($dbConfig['dbname']);
        if (file_exists($dbPath)) {
            throw new RuntimeException(InstallConfig::ERROR_FILE_ALREADY_EXISTS);
        }

        if (!touch($dbPath)) {
            throw new RuntimeException(InstallConfig::ERROR_DB_CREATION_FAILURE);
        }

        chmod($dbPath, 0664);

        $dsn = "sqlite:{$dbPath}";
        $pdo = new PDO($dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlFile = __DIR__ . '/SQLite.sql';
        self::executeSQLFile($pdo, $sqlFile);

        return $pdo;
    }

    private static function setupMySQL(array $dbConfig): PDO
    {
        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};charset={$dbConfig['charset']}";
        $pdo = new PDO($dsn, $dbConfig['dbusername'], $dbConfig['dbpassword']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 创建数据库
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbConfig['dbname']}` CHARACTER SET {$dbConfig['charset']}");
        $pdo->exec("USE `{$dbConfig['dbname']}`");

        $sqlFile = __DIR__ . '/MySQL.sql';
        self::executeSQLFile($pdo, $sqlFile);

        return $pdo;
    }

    private static function executeSQLFile(PDO $pdo, string $sqlFile): void
    {
        if (!file_exists($sqlFile)) {
            throw new RuntimeException("SQL文件不存在: {$sqlFile}");
        }

        $sql = file_get_contents($sqlFile);
        if ($sql === false) {
            throw new RuntimeException("无法读取SQL文件: {$sqlFile}");
        }

        $pdo->exec($sql);
    }

    /**
     * 设置管理员和站点
     */
    public static function setupAdminAndSite(PDO $pdo, array $adminData, array $siteData): void
    {
        try {
            $pdo->beginTransaction();

            // 插入管理员
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, group_id, created_at) VALUES (?, ?, ?, 1, ?)");
            $stmt->execute([
                $adminData['admin_name'],
                password_hash($adminData['admin_pass'], PASSWORD_DEFAULT),
                $adminData['admin_email'] ?? 'admin@example.com',
                date('Y-m-d H:i:s')
            ]);

            // 插入站点配置
            $siteConfig = [
                ['site_name', $siteData['site_name']],
                ['site_description', $siteData['site_description']],
                ['enable_user_registration', 'true'],
                ['site_timezone', 'Asia/Shanghai'],
                ['nav_link', serialize([
                    ['name' => '联系站长', 'link' => 'https://blog.zicheng.icu'],
                    ['name' => 'Gitee开源地址', 'link' => 'https://gitee.com/XiaoFengQWQ/zichen-web-chat-room']
                ])]
            ];

            foreach ($siteConfig as $config) {
                $stmt = $pdo->prepare("INSERT INTO system_sets (name, value) VALUES (?, ?)");
                $stmt->execute($config);
            }

            // 插入默认用户组
            $groups = [['管理员'], ['普通用户']];
            foreach ($groups as $group) {
                $stmt = $pdo->prepare("INSERT INTO groups (group_name) VALUES (?)");
                $stmt->execute($group);
            }

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw new RuntimeException(InstallConfig::ERROR_ADMIN_CREATION_FAILURE . ': ' . $e->getMessage());
        }
    }

    /**
     * 处理POST请求
     */
    public static function processPostRequest(?string $step): ?string
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return null;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!self::verifyCSRFToken($csrfToken)) {
            return '<div class="alert alert-danger">' . InstallConfig::ERROR_INVALID_REQUEST_METHOD . '</div>';
        }

        switch ($step) {
            case '1':
                return self::handleAdminSetup($_POST);
            case '2':
                return self::handleDatabaseSetup($_POST);
            default:
                return null;
        }
    }

    private static function handleAdminSetup(array $postData): string
    {
        // 验证输入
        if (empty($postData['admin_name'])) {
            return '<div class="alert alert-danger">管理员用户名不能为空</div>';
        }

        if (strlen($postData['admin_pass']) < 8) {
            return '<div class="alert alert-danger">密码长度至少8位</div>';
        }

        if ($postData['admin_pass'] !== $postData['confirm_pass']) {
            return '<div class="alert alert-danger">' . InstallConfig::ERROR_PASSWORD_MISMATCH . self::getBackLink('2') . '</div>';
        }

        // 存储到会话
        $_SESSION['admin_name'] = htmlspecialchars($postData['admin_name'], ENT_QUOTES, 'UTF-8');
        $_SESSION['admin_pass'] = $postData['admin_pass'];
        $_SESSION['admin_email'] = filter_var($postData['admin_email'] ?? '', FILTER_SANITIZE_EMAIL);
        $_SESSION['install_step'] = 2;

        // 重定向到下一步
        header('Location: ?step=2');
        exit;
    }

    private static function handleDatabaseSetup(array $postData): string
    {
        try {
            // 验证输入
            if (empty($postData['site_name'])) {
                return '<div class="alert alert-danger">站点名称不能为空</div>';
            }

            // 准备数据库配置
            $dbConfig = [
                'driver' => $postData['driver'] === 'sqlite' ? 'sqlite' : 'mysql',
                'host' => htmlspecialchars($postData['host'] ?? 'localhost', ENT_QUOTES, 'UTF-8'),
                'port' => (int)($postData['port'] ?? ($postData['driver'] === 'mysql' ? 3306 : 0)),
                'dbname' => htmlspecialchars(
                    $postData['driver'] === 'sqlite'
                        ? ($postData['sqlite_dbname'] ?? '')
                        : ($postData['dbname'] ?? ''),
                    ENT_QUOTES,
                    'UTF-8'
                ),
                'dbusername' => htmlspecialchars($postData['dbusername'] ?? '', ENT_QUOTES, 'UTF-8'),
                'dbpassword' => $postData['dbpassword'] ?? '',
                'charset' => htmlspecialchars($postData['charset'] ?? 'utf8mb4', ENT_QUOTES, 'UTF-8'),
            ];

            // 导入SQL
            $pdo = self::importSQL($dbConfig);

            // 设置管理员和站点预配置
            self::setupAdminAndSite($pdo, [
                'admin_name' => $_SESSION['admin_name'],
                'admin_pass' => $_SESSION['admin_pass'],
                'admin_email' => $_SESSION['admin_email']
            ], [
                'site_name' => htmlspecialchars($postData['site_name'], ENT_QUOTES, 'UTF-8'),
                'site_description' => htmlspecialchars($postData['site_description'] ?? '', ENT_QUOTES, 'UTF-8')
            ]);

            // 清除会话
            session_unset();
            session_destroy();

            // 写入配置文件
            self::writeConfigFile($dbConfig);
            return '<div class="alert alert-success">' . InstallConfig::SUCCESS . '</div>';
        } catch (Exception $e) {
            return '<div class="alert alert-danger">' . $e->getMessage() . self::getBackLink('3') . '</div>';
        }
    }
}

// 设置异常处理器
set_exception_handler([InstallHandler::class, 'handleException']);

// 初始化CSRF令牌
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = InstallHandler::generateCSRFToken();
}

// 获取当前步骤
$step = $_GET['step'] ?? '';
$progress = InstallConfig::PROGRESS_STEPS[$step] ?? 0;

// 处理POST请求
$stepFeedback = InstallHandler::processPostRequest($step);
$progress = htmlspecialchars((string)$progress);
