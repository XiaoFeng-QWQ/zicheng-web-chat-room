<?php

use ChatRoom\Core\Helpers\User;
// 检查 $_SESSION['user_login_info'] 是否存在且为数组
if (!isset($_SESSION['user_login_info']) || !is_array($_SESSION['user_login_info'])) {
    header('Location: /user/login'); // 重定向到登录页面
    exit(); // 终止脚本执行
}

?>
<!DOCTYPE html>
<html lang="zh-cn">

<head>
    <meta charset="UTF-8">
    <title>子辰在线聊天室</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://gcore.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/StaticResources/css/index.chat.css?v=<?php echo FRAMEWORK_VERSION ?>">
    <script>
        const sessionUsername = "<?php echo $_SESSION['user_login_info']['username']; ?>"; // 获取用户名
    </script>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="#">
                子辰在线聊天室 V<?php echo FRAMEWORK_VERSION ?>
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
                            <li>
                                <a class="dropdown-item" href="https://image.dfggmc.top/imgs/2024/07/b4fa5d91c72ca548.jpg" target="_blank" rel="noopener noreferrer">
                                    联系站长
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="https://gitee.com/XiaoFengQWQ/zichen-web-chat-room" target="_blank" rel="noopener noreferrer">Gitee开源地址</a>
                            </li>
                        </ul>
                    </li>
                    <?php if ($_SESSION['user_login_info']['group_id'] === 1) : ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/Admin/index.php" target="_blank" rel="noopener noreferrer">后台管理</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <button id="logout" class="btn btn-danger nav-link">离开聊天室</button>
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
                    你确定要离开聊天室吗？
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