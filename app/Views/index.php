<?php

use ChatRoom\Core\Helpers\SystemSetting;
use ChatRoom\Core\Database\SqlLite;
use ChatRoom\Core\Helpers\User;

$db = SqlLite::getInstance()->getConnection();
$SystemSetting = new SystemSetting($db);
$user = new User;
if (!$user->checkUserLoginStatus()) {
    header("Location: /user/login?callBack={$_SERVER['REQUEST_URI']}");
    exit();
}


?>
<!--
 ______     __     ______     __  __     ______     __   __     ______     ______     __  __     ______     ______   ______     ______     ______     __    __    
/\___  \   /\ \   /\  ___\   /\ \_\ \   /\  ___\   /\ "-.\ \   /\  ___\   /\  ___\   /\ \_\ \   /\  __ \   /\__  _\ /\  == \   /\  __ \   /\  __ \   /\ "-./  \   
\/_/  /__  \ \ \  \ \ \____  \ \  __ \  \ \  __\   \ \ \-.  \  \ \ \__ \  \ \ \____  \ \  __ \  \ \  __ \  \/_/\ \/ \ \  __<   \ \ \/\ \  \ \ \/\ \  \ \ \-./\ \  
  /\_____\  \ \_\  \ \_____\  \ \_\ \_\  \ \_____\  \ \_\'\_\  \ \_____\  \ \_____\  \ \_\ \_\  \ \_\ \_\    \ \_\  \ \_\ \_\  \ \_____\  \ \_____\  \ \_\ \ \_\ 
  \/_____/   \/_/   \/_____/   \/_/\/_/   \/_____/   \/_/ \/_/   \/_____/   \/_____/   \/_/\/_/   \/_/\/_/     \/_/   \/_/ /_/   \/_____/   \/_____/   \/_/  \/_/ 
-->
<!DOCTYPE html>
<html lang="zh-cn">

<head>
    <meta charset="UTF-8">
    <title><?= $SystemSetting->getSetting('site_name') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $SystemSetting->getSetting('site_description') ?>">
    <link rel="stylesheet" href="https://cdn.plyr.io/3.6.12/plyr.css" />
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/StaticResources/css/index.chat.css?v=<?php echo FRAMEWORK_VERSION ?>">
    <script>
        const sessionUsername = "<?= $_SESSION['user_login_info']['username']; ?>"; // 获取用户名
    </script>
</head>

<body>
    <nav class="navbar navbar-fixed-top navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center">
                <?= $SystemSetting->getSetting('site_name') ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" id="navbarScrollingDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            其他链接
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarScrollingDropdown">
                            <?php
                            // 获取导航链接的设置
                            $navLinks = $SystemSetting->getSetting('nav_link');
                            if ($navLinks) {
                                foreach ($navLinks as $item) {
                                    echo "
                                    <li>
                                        <a class='dropdown-item' href='{$item['link']}' target='_blank' rel='noopener noreferrer'>
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
                <div id="chat-box-container" class="card shadow-sm">
                    <div id="chat-box" class="card-body talk" style="overflow-y: auto; max-height: 500px;">
                        <div id="loading" class="text-center my-3">
                            <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                            <p class="mt-2 text-muted">加载中…</p>
                        </div>
                        <button id="scroll-down-button" class="btn btn-primary">
                            <i class="bi bi-arrow-down-circle"></i>
                        </button>
                    </div>
                    <div id="select-file-preview" class="p-3 position-absolute"></div>
                    <form id="chat-form" class="card-footer d-flex align-items-center gap-3 p-3">
                        <textarea id="message" class="form-control flex-grow-1" rows="2"
                            placeholder="聊点什么吧，Ctrl+Enter发送消息" style="resize: none;"></textarea>
                        <div class="position-relative d-flex align-items-center">
                            <input type="file" name="file" id="file" class="d-none" multiple />
                            <button type="button" id="select-file" class="btn btn-secondary" title="上传文件">
                                <i class="bi bi-paperclip"></i>
                            </button>
                        </div>
                        <button type="submit" id="send-button" class="primary">
                            <i class="bi bi-send me-1"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!--
        模态窗
    -->
    <div class="modal fade" id="logoutModal" aria-labelledby="logoutModalLabel" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalLabel">确认退出</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
                </div>
                <div class="modal-body">
                    确定要离开“<?= $SystemSetting->getSetting('site_name') ?>”吗？
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-danger" id="confirmLogout">确认</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="filePreviewModal" aria-labelledby="filePreviewModalLabel" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filePreviewModalLabel">文件预览 <span id="filePreviewFileInfo"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="filePreviewContent" style="overflow: auto; max-height: 100vh;"></div>
            </div>
        </div>
    </div>

    <script src="/StaticResources/js/jquery.min.js"></script>
    <script src="/StaticResources/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.plyr.io/3.6.12/plyr.js"></script>
    <script src="/StaticResources/js/helper.js?v=<?php echo FRAMEWORK_VERSION ?>"></script>
    <script src="/StaticResources/js/index.chat.js?v=<?php echo FRAMEWORK_VERSION ?>"></script>
</body>

</html>