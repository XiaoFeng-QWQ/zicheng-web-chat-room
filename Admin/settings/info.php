<?php
require_once __DIR__ . "/../helper/common.php";
?>

<div class="container mt-5">
    <!-- 标题和版本信息 -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <h2 class="card-title text-primary">关于</h2>
            <hr>
            <p class="mb-2">
                当前版本：
                <span class="badge bg-primary fs-6"><?= FRAMEWORK_VERSION; ?></span>
                <?php if (defined('FRAMEWORK_DEBUG') && FRAMEWORK_DEBUG): ?>
                    <span class="badge bg-danger fs-6 ms-2">调试模式已启用</span>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- 更新日志 -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-light text-dark">
            <h4 class="mb-0">更新日志</h4>
        </div>
        <div class="card-body">
            <p class="text-muted">以下是最近的更新记录：</p>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>2024-10-4: [1.2.0.0]1.聊天支持上传文件，指令列表新增投票 2.部分CSS、布局优化 3.优化部分代码</span>
                    <span class="badge bg-success">新功能</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>2024-10-4: [1.0.0.0]【正式版本！】发送消息支持Ctrl+Enter、优化登录逻辑，微调数据库结构，微调部分代码</span>
                    <span class="badge bg-success">新功能</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>2024-09-17: [1.10.0.0] 添加指令系统、新消息通知音。微调部分代码</span>
                    <span class="badge bg-info">改进</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>2024-08-27: [1.9.0.0] 支持发送图片消息。</span>
                    <span class="badge bg-primary">新增</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>2024-08-26: [1.8.0.0] 完善后台管理。</span>
                    <span class="badge bg-secondary">改进</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>2024-08-11: [1.7.0.0] 完善站点设置功能。</span>
                    <span class="badge bg-warning text-dark">优化</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>2024-08-09: [1.6.7.0] 优化部分逻辑。</span>
                    <span class="badge bg-light text-dark">修复</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>2024-08-09: [1.6.6.1] 修复通过内置路由验证码无法正常输出问题。</span>
                    <span class="badge bg-danger">修复</span>
                </li>
            </ul>
        </div>
        <div class="card-footer bg-light">
            <p class="text-muted mb-0">详细技术细节访问： <a href="https://blog.zicheng.icu/tag/zichen-caht-room/" target="_blank" class="text-decoration-underline">https://blog.zicheng.icu/tag/zichen-caht-room/</a></p>
        </div>
    </div>
</div>