<?php

use ChatRoom\Core\Helpers\SystemSetting;
use ChatRoom\Core\Database\SqlLite;
use ChatRoom\Core\Helpers\User;
// 检查 $_SESSION['user_login_info'] 是否存在且为数组
if (!isset($_SESSION['user_login_info']) || !is_array($_SESSION['user_login_info'])) {
    header('Location: /user/login'); // 重定向到登录页面
    exit(); // 终止脚本执行
}
$db = SqlLite::getInstance()->getConnection();

$SystemSetting = new SystemSetting($db);

?>
<!--
 ______     __     ______     __  __     ______     __   __     ______     ______     __  __     ______     ______   ______     ______     ______     __    __    
/\___  \   /\ \   /\  ___\   /\ \_\ \   /\  ___\   /\ "-.\ \   /\  ___\   /\  ___\   /\ \_\ \   /\  __ \   /\__  _\ /\  == \   /\  __ \   /\  __ \   /\ "-./  \   
\/_/  /__  \ \ \  \ \ \____  \ \  __ \  \ \  __\   \ \ \-.  \  \ \ \__ \  \ \ \____  \ \  __ \  \ \  __ \  \/_/\ \/ \ \  __<   \ \ \/\ \  \ \ \/\ \  \ \ \-./\ \  
  /\_____\  \ \_\  \ \_____\  \ \_\ \_\  \ \_____\  \ \_\\"\_\  \ \_____\  \ \_____\  \ \_\ \_\  \ \_\ \_\    \ \_\  \ \_\ \_\  \ \_____\  \ \_____\  \ \_\ \ \_\ 
  \/_____/   \/_/   \/_____/   \/_/\/_/   \/_____/   \/_/ \/_/   \/_____/   \/_____/   \/_/\/_/   \/_/\/_/     \/_/   \/_/ /_/   \/_____/   \/_____/   \/_/  \/_/ 
-->
<!DOCTYPE html>
<html lang="zh-cn">

<head>
    <meta charset="UTF-8">
    <title><?= $SystemSetting->getSetting('site_name') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $SystemSetting->getSetting('site_description') ?>">
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://gcore.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/StaticResources/css/index.chat.css?v=<?php echo FRAMEWORK_VERSION ?>">
    <script>
        const sessionUsername = "<?= $_SESSION['user_login_info']['username']; ?>"; // 获取用户名
    </script>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center">
                <?= $SystemSetting->getSetting('site_name') ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <span class="nav-link">
                            <?php
                            $user = new User;
                            echo '您的IP是:' . $user->getIp() . ' 请注意言行举止!';
                            ?>
                        </span>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" id="navbarScrollingDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            其他链接
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarScrollingDropdown">
                            <?php
                            // 获取导航链接的设置
                            $navLinks = $SystemSetting->getSetting('nav_link');

                            // 遍历导航链接并生成列表项
                            if ($navLinks) {
                                foreach ($navLinks as $item) {
                                    // 使用双引号插入变量
                                    echo "
                                    <li>
                                        <a class=\"dropdown-item\" href=\"{$item['link']}\" target=\"_blank\" rel=\"noopener noreferrer\">
                                            {$item['name']}
                                        </a>
                                    </li>
                                    ";
                                }
                            }
                            ?>
                        </ul>
                    </li>
                    <?php if ($_SESSION['user_login_info']['group_id'] === 1) : ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/Admin/index.php" target="_blank" rel="noopener noreferrer">后台管理</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <button id="logout" class="btn btn-danger nav-link">退出登录</button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div id="chat-box-container" class="card">
                    <div id="chat-box" class="card-body talk">
                        <div id="loading">
                            <div class="spinner-border" role="status" aria-hidden="true"></div>
                            <p>加载中…</p>
                        </div>
                    </div>
                    <form id="chat-form" class="card-footer d-flex">
                        <input type="text" class="form-control me-2" id="message" maxlength="256" placeholder="聊点什么吧...(最大256)" required>
                        <button type="submit" id="send-button" class="btn btn-primary send">发送</button>
                    </form>
                </div>
                <!-- 向下箭头按钮 -->
                <button id="scroll-down-button" class="btn btn-primary">
                    <i class="bi bi-arrow-down-circle"></i> 返回底部
                </button>
            </div>
        </div>
    </div>
    <!-- Bootstrap 模态窗结构 -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalLabel">确认退出</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
                </div>
                <div class="modal-body">
                    <img src="https://image.dfggmc.top/imgs/2024/07/b4fa5d91c72ca548.jpg" alt="">
                    确定要离开“<?= $SystemSetting->getSetting('site_name') ?>”吗？
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-danger" id="confirmLogout">确认</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.bootcdn.net/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
    <script src="/StaticResources/js/index.chat.js?v=<?php echo FRAMEWORK_VERSION ?>"></script>
</body>

</html>