<?php
require_once __DIR__ . '/../../config.global.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use ChatRoom\Core\Database\SqlLite;

if (defined('FRAMEWORK_DATABASE_PATH')) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // 数据清理函数
        function sanitizeInput($input)
        {
            return htmlspecialchars(trim($input)); // 修剪输入并使用更安全的清理
        }

        // 执行数据库更新操作
        function updateDatabase($db)
        {
            $db->exec("
            CREATE TABLE IF NOT EXISTS events (
                event_id INTEGER PRIMARY KEY AUTOINCREMENT,
                event_type VARCHAR(100) NOT NULL,
                user_id INT NOT NULL,
                target_id INT NOT NULL,
                created_at DATETIME NOT NULL,
                additional_data TEXT)");
            $db->exec("CREATE INDEX IF NOT EXISTS events_index ON events (event_id, event_type)");
            $db->exec("ALTER TABLE messages ADD COLUMN status TEXT DEFAULT 'active'");
        }
        $db = SqlLite::getInstance()->getConnection();

        // 获取并验证输入的密钥与文件名
        $keyInput = sanitizeInput($_POST['updateKey']);
        $updateName = sanitizeInput($_POST['updateName']);
        $dataFilePath = __DIR__ . '/' . $updateName;

        try {
            // 检查文件是否存在
            if (!is_file($dataFilePath)) {
                $error = "文件不存在";
                throw new Exception($error);
            }

            $expectedKey = file_get_contents($dataFilePath);

            // 验证密钥
            if ($keyInput !== $expectedKey) {
                $error = '无效的文件信息';
                throw new Exception($error);
            }

            // 执行数据库更新操作
            updateDatabase($db);
            echo '数据库更新成功！请删除第一步创建的文件';
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">';
            echo "数据库更新失败: " . $e . "<br>";
            echo "可能是数据库不完整或更新已完成，请手动检查。";
            echo '</div>';
        }
    }
?>

    <!DOCTYPE html>
    <html lang="zh-CN">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>系统更新</title>
        <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.1.0/css/bootstrap.min.css" rel="stylesheet">
    </head>

    <body>
        <div class="container mt-5">
            <h1 class="text-center">子辰聊天室数据库更新 - 从2.1.0.0 到 2.2.0.0</h1>
            <p>请在当前目录下创建以下文件并填写正确的内容以进行权限验证。</p>
            <p><?= isset($error) ? $error : '' ?></p>
            <form id="updateForm" method="POST" action="">
                <div class="mb-3">
                    <label for="updateName" class="form-label">文件名:</label>
                    <input type="text" class="form-control" id="updateName" name="updateName" required>
                </div>
                <div class="mb-3">
                    <label for="updateKey" class="form-label">文件内容:</label>
                    <input type="text" class="form-control" id="updateKey" name="updateKey" required>
                </div>
                <button type="submit" class="btn btn-primary" id="nextStep">下一步</button>
            </form>
        </div>
        <script>
            document.getElementById("updateForm").addEventListener("submit", function(event) {
                var confirmation = confirm("⚠请备份数据库文件，因为不能保证百分百成功和完整性。点击确定开始点击取消取消更新⚠");
                if (!confirmation) {
                    event.preventDefault();
                } else {
                    var confirmationn = confirm("🔔 真的要更新？嗯……我相信你:)，如果没有备份数据库的情况下出现问题与作者无关。祝你顺利！🔔");
                    if (!confirmationn) {
                        event.preventDefault();
                    }
                }
            });
        </script>
    </body>

    </html>
<?php
} else {
    // 如果框架数据库路径未定义，则重定向到安装页面
    header('Location: /Admin/install');
}
?>