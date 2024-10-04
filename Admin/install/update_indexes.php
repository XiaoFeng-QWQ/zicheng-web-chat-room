<?php
require_once __DIR__ . '/../../config.global.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use ChatRoom\Core\Database\SqlLite;

try {
    $db = SqlLite::getInstance()->getConnection();

    $db->exec("DROP INDEX IF EXISTS groups_index;");
    $db->exec("CREATE UNIQUE INDEX groups_index ON groups (group_id, group_name);");
    $db->exec("DROP INDEX IF EXISTS messages_index;");
    $db->exec("CREATE UNIQUE INDEX messages_index ON messages (id, content);");
    $db->exec("DROP INDEX IF EXISTS system_logs_index;");
    $db->exec("CREATE UNIQUE INDEX system_logs_index ON system_logs (log_id, message);");
    $db->exec("DROP INDEX IF EXISTS system_sets_index;");
    $db->exec("CREATE UNIQUE INDEX system_sets_index ON system_sets (name, id);");
    $db->exec("DROP INDEX IF EXISTS user_sets_index;");
    $db->exec("CREATE UNIQUE INDEX user_sets_index ON user_sets (id, set_name);");
    $db->exec("DROP INDEX IF EXISTS user_tokens_index;");
    $db->exec("CREATE UNIQUE INDEX user_tokens_index ON user_tokens (id, user_id);");
    $db->exec("DROP INDEX IF EXISTS users_index;");
    $db->exec("CREATE UNIQUE INDEX users_index ON users (user_id, username);");

    echo json_encode(['message' => '索引更新成功！但是您仍需要手动把messages表里面的user_name外键重置为没有']);
} catch (Exception $e) {
    error_log("索引更新失败: " . $e->getMessage());
    echo json_encode(['message' => '索引更新失败: ' . $e->getMessage()]);
}
