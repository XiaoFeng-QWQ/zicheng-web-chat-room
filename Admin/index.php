<?php
require_once __DIR__ . "/module/head.php";

use ChatRoom\Core\Helpers\SystemLog;

// 获取统计数据
$statsQuery = $db->prepare("
    SELECT 
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM messages) as total_messages,
        (SELECT COUNT(*) FROM users WHERE DATE(created_at) = DATE('now')) as new_users_today,
        (SELECT COUNT(*) FROM messages WHERE DATE(created_at) = DATE('now')) as messages_today
");
$statsQuery->execute();
$stats = $statsQuery->fetch(PDO::FETCH_ASSOC);

// 获取趋势数据
$trendQuery = $db->prepare("
    SELECT 
        DATE(created_at) as date,
        COUNT(CASE WHEN type = 'message' THEN 1 END) as message_count,
        COUNT(CASE WHEN type = 'user' THEN 1 END) as user_count
    FROM (
        SELECT created_at, 'message' as type FROM messages 
        UNION ALL
        SELECT created_at, 'user' as type FROM users
    ) trend_data
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at) DESC 
    LIMIT 31
");
$trendQuery->execute();
$trendData = $trendQuery->fetchAll(PDO::FETCH_ASSOC);

// 获取完整趋势数据
$trendCSVQuery = $db->prepare("
    SELECT 
        DATE(created_at) as date,
        COUNT(CASE WHEN type = 'message' THEN 1 END) as message_count,
        COUNT(CASE WHEN type = 'user' THEN 1 END) as user_count
    FROM (
        SELECT created_at, 'message' as type FROM messages 
        UNION ALL
        SELECT created_at, 'user' as type FROM users
    ) trend_data
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at)
");
$trendCSVQuery->execute();
$trendCSVData = $trendCSVQuery->fetchAll(PDO::FETCH_ASSOC);

// 获取最近日志
$log = new SystemLog($db);
$logs = $log->getLogs(5);
?>

<div class="row">
    <!-- 概览 -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-chart-line"></i> <?= htmlspecialchars($SystemSetting->getSetting('site_name')) ?>概览
        </div>
        <div class="card-body">
            <h5 class="card-title">聊天室管理</h5>
            <p class="card-text">您可以在此处管理用户、消息列表和设置。</p>
            <hr>
            <div class="row mt-4">
                <?php
                $statsCards = [
                    ['title' => '今日消息', 'icon' => 'fa-comments', 'value' => $stats['messages_today'], 'class' => 'bg-success'],
                    ['title' => '消息总数', 'icon' => 'fa-comments', 'value' => $stats['total_messages'], 'class' => 'bg-info'],
                    ['title' => '新注册', 'icon' => 'fa-user-plus', 'value' => $stats['new_users_today'], 'class' => 'bg-warning text-dark'],
                    ['title' => '总用户数', 'icon' => 'fa-users', 'value' => $stats['total_users'], 'class' => 'bg-primary'],
                ];
                foreach ($statsCards as $card) {
                    echo "
                    <div class='col-md-3 col-sm-6 mb-3'>
                        <div class='card {$card['class']} text-white h-100'>
                            <div class='card-body'>
                                <h5 class='card-title'><i class='fas {$card['icon']}'></i> {$card['title']}</h5>
                                <p class='card-text display-4'>" . htmlspecialchars($card['value']) . "</p>
                            </div>
                        </div>
                    </div>";
                }
                ?>
            </div>
            <div class="mt-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-history"></i> 最近活动日志
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php
                            foreach ($logs as $log) {
                                echo "<li class='list-group-item'>[{$log['log_type']}] " . htmlspecialchars($log['message']) . " - " . htmlspecialchars($log['created_at']) . "</li>";
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-chart-bar"></i> 近31天用户和消息增长图表(如需查看完整信息，请导出为CSV)
                            </div>
                            <button class="btn btn-success me-2" id="exportButton" type="button">
                                <i class="fas fa-file-csv me-1"></i> 导出数据为CSV
                            </button>
                        </div>
                        <div class="card-body">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        // 从PHP传递完整的趋势数据
        const trendData = <?php echo json_encode($trendData); ?>;
        // 提取趋势数据的日期、消息数和用户数
        const trendLabels = trendData.map(item => item.date);
        const messageTrendCounts = trendData.map(item => item.message_count);
        const userTrendCounts = trendData.map(item => item.user_count);
        // 配置 Chart.js 图表
        const chartConfig = {
            type: 'bar',
            data: {
                labels: trendLabels,
                datasets: [{
                        label: '消息数',
                        data: messageTrendCounts,
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    },
                    {
                        label: '用户数',
                        data: userTrendCounts,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: '日期'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: '数量'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        enabled: true
                    }
                }
            }
        };
        // 创建趋势图表
        const ctx = document.getElementById('trendChart').getContext('2d');
        new Chart(ctx, chartConfig);
        // 导出完整数据为CSV
        document.getElementById('exportButton').addEventListener('click', () => {
            // 从PHP传递完整的趋势数据
            const trendData = <?php echo json_encode($trendCSVData); ?>;
            // 提取趋势数据的日期、消息数和用户数
            const trendLabels = trendData.map(item => item.date);
            const messageTrendCounts = trendData.map(item => item.message_count);
            const userTrendCounts = trendData.map(item => item.user_count);

            let csvContent = "data:text/csv;charset=utf-8,Date,Message Count,User Count\n";
            trendLabels.forEach((label, index) => {
                csvContent += `${label},${messageTrendCounts[index]},${userTrendCounts[index]}\n`;
            });

            // 创建并触发下载链接
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "complete_trend_data.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    })();
</script>

<?php require_once __DIR__ . '/module/footer.php'; ?>