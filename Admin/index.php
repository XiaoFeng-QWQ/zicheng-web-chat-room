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
                        <li class="list-group-item">2024-10-4:[1.0.0.0]【正式版本！】发送消息支持Ctrl+Enter、优化登录逻辑，微调数据库结构，微调部分代码</li>
                        <li class="list-group-item">2024-09-17:[1.10.0.0]添加指令系统、新消息通知音。微调部分代码</li>
                        <li class="list-group-item">2024-08-27: [1.9.0.0]支持发送图片消息。</li>
                        <li class="list-group-item">2024-08-26: [1.8.0.0]完善后台管理。</li>
                        <li class="list-group-item">2024-08-11: [1.7.0.0]完善站点设置功能。</li>
                        <li class="list-group-item">2024-08-09: [1.6.7.0]优化部分逻辑。</li>
                        <li class="list-group-item">2024-08-09: [1.6.6.1]修复通过内置路由验证码无法正常输出问题。</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <!-- 概览 -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-chart-line"></i> <?= $SystemSetting->getSetting('site_name') ?>概览
            </div>
            <div class="card-body">
                <h5 class="card-title">欢迎来到聊天室管理仪表板</h5>
                <p class="card-text">
                    您可以在此处管理用户、消息列表和设置。当前版本：<span class="badge bg-primary"><?php echo FRAMEWORK_VERSION ?></span>
                    <?php
                    if (defined('FRAMEWORK_DEBUG') && FRAMEWORK_DEBUG):
                    ?>
                        | <span class="badge bg-danger">调试模式已启用</span>
                    <?php
                    endif;
                    ?>
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
            type: 'bar', // 修改图表类型为柱状图
            data: {
                labels: trendLabels,
                datasets: [{
                        label: '消息数',
                        data: messageTrendCounts,
                        backgroundColor: 'rgba(255, 99, 132, 0.2)', // 填充颜色
                        borderColor: 'rgba(255, 99, 132, 1)', // 边框颜色
                        borderWidth: 1
                    },
                    {
                        label: '用户数',
                        data: userTrendCounts,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)', // 填充颜色
                        borderColor: 'rgba(75, 192, 192, 1)', // 边框颜色
                        borderWidth: 1
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