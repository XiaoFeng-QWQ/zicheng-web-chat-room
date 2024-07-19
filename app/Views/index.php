<?php
if (!isset($_SESSION['userinfo'])) {
    if (!is_array($_SESSION['userinfo'])) {
        header('Location: /user/login');
        exit();
    }
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
        const sessionUsername = "<?php echo $_SESSION['userinfo']['username']; ?>"; // 获取用户名
    </script>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="/StaticResources/image/logo.png" alt="logo" class="logo img-fluid me-2">
                子辰在线聊天室V<?php echo FRAMEWORK_VERSION ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
                <div class="navbar-nav mr-auto">
                    <a class="nav-link" href="https://image.dfggmc.top/imgs/2024/07/b4fa5d91c72ca548.jpg" target="_blank" rel="noopener noreferrer">联系站长</a>
                    <a class="nav-link" href="https://gitee.com/XiaoFengQWQ/zichen-web-chat-room" target="_blank" rel="noopener noreferrer">Gitee开源地址</a>
                </div>
                <div class="ms-auto">
                    <button id="logout" class="btn btn-danger">离开聊天室</button>
                </div>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div id="chat-box-container" class="card">
                    <div id="chat-box" class="card-body talk" style="overflow-y: auto; max-height: 75vh; min-height: 75vh;">
                        <div id="loading" class="text-center">
                            <img src="/StaticResources/image/logo.loading.svg" alt="">
                        </div>
                    </div>
                    <form id="chat-form" class="card-footer d-flex">
                        <input type="text" class="form-control me-2" id="message" maxlength="240" placeholder="聊点什么吧..." required>
                        <button type="submit" class="btn btn-primary send">发送</button>
                    </form>
                </div>
                <!-- 向下箭头按钮 -->
                <button id="scroll-down-button" class="btn btn-primary" style="position: fixed; bottom: 140px; right: 16px; display: none;">
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