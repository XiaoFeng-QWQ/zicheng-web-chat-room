<?php
require_once __DIR__ . '/../../config.global.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use ChatRoom\Core\Database\SqlLite;

if (defined('FRAMEWORK_DATABASE_PATH')) {

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $keyInput = $_POST['updateKey'];
        $updateName = $_POST['updateName'];
        $dataFilePath = __DIR__ . '/' . $updateName;

        if (!is_file($dataFilePath)) {
            http_response_code(404);
            exit;
        }

        try {
            $expectedKey = file_get_contents($dataFilePath);

            // 验证key
            if ($keyInput !== $expectedKey) {
                http_response_code(400);
                echo "无效的更新密钥。";
                exit();
            }

            // 变量初始化

            $db = SqlLite::getInstance()->getConnection();
            $db->beginTransaction();
            $columnsToRemove = ['login_token', 'user_login_token', 'admin_login_token'];
            $indexes = [
                'groups_index' => ['table' => 'groups', 'columns' => 'group_id, group_name'],
                'messages_index' => ['table' => 'messages', 'columns' => 'id, content'],
                'system_logs_index' => ['table' => 'system_logs', 'columns' => 'log_id, message'],
                'system_sets_index' => ['table' => 'system_sets', 'columns' => 'name, id'],
                'user_sets_index' => ['table' => 'user_sets', 'columns' => 'id, set_name'],
                'user_tokens_index' => ['table' => 'user_tokens', 'columns' => 'id, user_id'],
                'users_index' => ['table' => 'users', 'columns' => 'user_id, username'],
            ];

            // 更新主操作
            /**
             * 批量处理
             */
            // 移除不需要的列
            foreach ($columnsToRemove as $column) {
                $db->exec("ALTER TABLE users DROP COLUMN $column;");
            }
            // 更新索引
            foreach ($indexes as $indexName => $info) {
                $db->exec("DROP INDEX IF EXISTS $indexName;");
                $db->exec("CREATE UNIQUE INDEX $indexName ON {$info['table']} ({$info['columns']});");
            }

            // 备份旧表并创建新表
            $db->exec("ALTER TABLE users RENAME TO users_old;");
            $db->exec("
            CREATE TABLE users (
                user_id INTEGER NOT NULL UNIQUE,
                username TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                email TEXT,
                register_ip REAL,
                group_id INTEGER NOT NULL DEFAULT 2,
                avatar_url TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY(user_id AUTOINCREMENT)
            );");

            // 将旧数据插入新表
            $db->exec("
            INSERT INTO users (user_id, username, password, email, register_ip, group_id, avatar_url, created_at)
            SELECT user_id, username, password, email, register_ip, group_id, avatar_url, created_at FROM users_old;");

            // 删除旧表
            $db->exec("DROP TABLE users_old;");

            // 创建新的 user_tokens 表
            $db->exec("
            CREATE TABLE user_tokens (
                id INTEGER NOT NULL UNIQUE,
                user_id INTEGER NOT NULL,
                token VARCHAR(256) NOT NULL,
                expiration DATETIME,
                created_at DATETIME,
                updated_at DATETIME,
                PRIMARY KEY(id AUTOINCREMENT)
            );");

            $db->commit();
            echo "数据库更新成功！但是您仍需要手动把messages表里面的user_name外键重置为没有！";
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("数据库更新失败: " . $e->getMessage());
            echo "数据库更新失败: " . $e->getMessage() . "<br>";
            echo "有可能您的数据库不完整或已完成更新，请手动更新！";
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
            <h1 class="text-center">系统数据库更新 - 1.10.0.1(测试) 到 1.20.0.0</h1>
            <p>请在当前目录下创建以下填写的数据</p>
            <form id="updateForm" method="POST">
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
        <script src="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.1.0/js/bootstrap.bundle.min.js"></script>
        <script>
            document.getElementById("updateForm").addEventListener("submit", function(event) {
                var confirmation = confirm("请自行备份数据库文件，因为不能保证百分百成功和完整性。");
                if (!confirmation) {
                    event.preventDefault(); // 如果用户选择“取消”，阻止表单提交
                }
            });
        </script>
    </body>

    </html>
<?php
} else {
    header('Location: /Admin/install');
}
?>