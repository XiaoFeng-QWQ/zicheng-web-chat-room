<?php
require_once __DIR__ . "/module/head.php";

use ChatRoom\Core\Helpers\SystemLog;

// 获取用户统计数据
$userCountQuery = $db->query('SELECT COUNT(*) as count FROM users');
$userCount = $userCountQuery->fetch(PDO::FETCH_ASSOC)['count'];

// 获取消息总数
$messageCountQuery = $db->query('SELECT COUNT(*) as count FROM messages');
$messageCount = $messageCountQuery->fetch(PDO::FETCH_ASSOC)['count'];

// 获取今日消息统计
$todayMessageCountQuery = $db->query("SELECT COUNT(*) as count FROM messages WHERE date(created_at) = date('now')");
$todayMessageCount = $todayMessageCountQuery->fetch(PDO::FETCH_ASSOC)['count'];

// 获取今日新注册用户数
$todayNewUserCountQuery = $db->query("SELECT COUNT(*) as count FROM users WHERE date(created_at) = date('now')");
$todayNewUserCount = $todayNewUserCountQuery->fetch(PDO::FETCH_ASSOC)['count'];

// 获取消息增长趋势数据
$messageTrendQuery = $db->query("SELECT date(created_at) as date, COUNT(*) as count FROM messages GROUP BY date(created_at) ORDER BY date(created_at) DESC LIMIT 30");
$messageTrendData = $messageTrendQuery->fetchAll(PDO::FETCH_ASSOC);

// 获取用户增长趋势数据
$userTrendQuery = $db->query("SELECT date(created_at) as date, COUNT(*) as count FROM users GROUP BY date(created_at) ORDER BY date(created_at) DESC LIMIT 30");
$userTrendData = $userTrendQuery->fetchAll(PDO::FETCH_ASSOC);

$log = new SystemLog($db);
$logs = $log->getLogs(5);
?>

<div class="row">
    <div class="col-md-2">
        <!-- 系统通知和公告 -->
        <div class="mb-3">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-bell"></i> 更新日志
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">2024-08-06: [1.6.0]完善后台主页、优化后台文件目录。</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <!-- 子辰聊天室概览 -->
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
                        <div class="card bg-success text-white h-100">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-comments"></i> 今日消息</h5>
                                <p class="card-text display-4"><?php echo htmlspecialchars($todayMessageCount); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card bg-info text-white h-100">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-comments"></i> 消息总数</h5>
                                <p class="card-text display-4"><?php echo htmlspecialchars($messageCount); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card bg-warning text-dark h-100">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-user-plus"></i> 新注册</h5>
                                <p class="card-text display-4"><?php echo htmlspecialchars($todayNewUserCount); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card bg-primary text-white h-100">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-users"></i> 总用户数</h5>
                                <p class="card-text display-4"><?php echo htmlspecialchars($userCount); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-chart-bar"></i> 用户和消息增长趋势
                            </div>
                            <div class="card-body">
                                <!-- Chart.js -->
                                <canvas id="trendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <!-- 最近活动日志 -->
        <div class="mb-3">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-history"></i> 最近活动日志
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php
                        foreach ($logs as $log) {
                            echo "<li class='list-group-item'>[{$log['log_type']}] {$log['message']} - {$log['created_at']}</li>";
                        }
                        ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.bootcdn.net/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
    (function() {
        // 将PHP数组转换为JavaScript数组
        const messageTrendData = <?php echo json_encode($messageTrendData); ?>;
        const userTrendData = <?php echo json_encode($userTrendData); ?>;

        // 准备消息和用户增长趋势的数据
        const trendLabels = messageTrendData.map(item => item.date);
        const messageTrendCounts = messageTrendData.map(item => item.count);
        const userTrendCounts = userTrendData.map(item => item.count);

        // Chart.js 配置对象
        const chartConfig = {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [{
                        label: '消息数',
                        data: messageTrendCounts,
                        borderColor: 'rgba(255, 99, 132, 1)', // 修改颜色为红色
                        backgroundColor: 'rgba(255, 99, 132, 0.2)', // 填充颜色
                        borderWidth: 1,
                        fill: true // 启用填充
                    },
                    {
                        label: '用户数',
                        data: userTrendCounts,
                        borderColor: 'rgba(75, 192, 192, 1)', // 修改颜色为绿色
                        backgroundColor: 'rgba(75, 192, 192, 0.2)', // 填充颜色
                        borderWidth: 1,
                        fill: true // 启用填充
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: '日期'
                        }
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: '数量'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        enabled: true
                    }
                }
            }
        };

        // 创建图表
        const ctx = document.getElementById('trendChart').getContext('2d');
        const trendChart = new Chart(ctx, chartConfig);
    })();
</script>
<?php
require_once __DIR__ . '/module/footer.php';
?>