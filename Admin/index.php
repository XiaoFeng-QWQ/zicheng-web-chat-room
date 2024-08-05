<?php
require_once __DIR__ . "/head.php";

// 用户统计数据
$userCountQuery = $db->query('SELECT COUNT(*) as count FROM users');
$userCount = $userCountQuery->fetch(PDO::FETCH_ASSOC)['count'];

// 消息总数
$messageCountQuery = $db->query('SELECT COUNT(*) as count FROM messages');
$messageCount = $messageCountQuery->fetch(PDO::FETCH_ASSOC)['count'];

// 今日消息统计
$todayMessageCountQuery = $db->query("SELECT COUNT(*) as count FROM messages WHERE date(created_at) = date('now')");
$todayMessageCount = $todayMessageCountQuery->fetch(PDO::FETCH_ASSOC)['count'];

// 今日新注册用户数
$todayNewUserCountQuery = $db->query("SELECT COUNT(*) as count FROM users WHERE date(created_at) = date('now')");
$todayNewUserCount = $todayNewUserCountQuery->fetch(PDO::FETCH_ASSOC)['count'];
?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-chart-line"></i> 子辰聊天室概览
            </div>
            <div class="card-body">
                <h5 class="card-title">欢迎来到聊天室管理仪表板</h5>
                <p class="card-text">
                    您可以在此处管理用户、消息列表和设置。当前版本：<span class="badge bg-primary"><?php echo FRAMEWORK_VERSION ?></span>
                </p>
                <div class="row mt-4">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-comments"></i> 今日消息</h5>
                                <p class="card-text display-4"><?php echo htmlspecialchars($todayMessageCount); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-comments"></i> 消息总数</h5>
                                <p class="card-text display-4"><?php echo htmlspecialchars($messageCount); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card bg-warning text-dark">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-user-plus"></i> 新注册</h5>
                                <p class="card-text display-4"><?php echo htmlspecialchars($todayNewUserCount); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-users"></i> 总用户数</h5>
                                <p class="card-text display-4"><?php echo htmlspecialchars($userCount); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <h6>快速操作</h6>
                    <div class="btn-group" role="group" aria-label="快速操作">
                        <button type="button" class="btn btn-outline-primary"><i class="fas fa-user-plus"></i> 添加用户</button>
                        <button type="button" class="btn btn-outline-secondary"><i class="fas fa-envelope"></i> 发送公告</button>
                        <button type="button" class="btn btn-outline-success"><i class="fas fa-cog"></i> 系统设置</button>
                        <button type="button" class="btn btn-outline-info"><i class="fas fa-database"></i> 备份数据</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-bullhorn"></i> 管理员留言
            </div>
            <div class="card-body">
                <div class="list-group">
                    <p class="list-group-item list-group-item-action">用户XXX登录系统</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/footer.php';
?>