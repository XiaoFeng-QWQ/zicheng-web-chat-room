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
$cookieData = json_decode($_COOKIE['user_login_info'], true);
?>
<!--
 ______     __     ______     __  __     ______     __   __     ______     ______     __  __     ______     ______   ______     ______     ______     __    __    
/\___  \   /\ \   /\  ___\   /\ \_\ \   /\  ___\   /\ "-.\ \   /\  ___\   /\  ___\   /\ \_\ \   /\  __ \   /\__  _\ /\  == \   /\  __ \   /\  __ \   /\ "-./  \   
\/_/  /__  \ \ \  \ \ \____  \ \  __ \  \ \  __\   \ \ \-.  \  \ \ \__ \  \ \ \____  \ \  __ \  \ \  __ \  \/_/\ \/ \ \  __<   \ \ \/\ \  \ \ \/\ \  \ \ \-./\ \  
  /\_____\  \ \_\  \ \_____\  \ \_\ \_\  \ \_____\  \ \_\'\_\   \ \_____\  \ \_____\  \ \_\ \_\  \ \_\ \_\    \ \_\  \ \_\ \_\  \ \_____\  \ \_____\  \ \_\ \ \_\ 
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
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://lf26-cdn-tos.bytecdntp.com/cdn/expire-1-M/jquery-contextmenu/3.0-beta.1/jquery.contextMenu.min.css">
    <link rel="stylesheet" href="/StaticResources/css/highlight/vs2015.min.css">
    <link rel="stylesheet" href="/StaticResources/css/index.chat.css?v=<?= FRAMEWORK_VERSION ?>">
    <script>
        const sessionUsername = "<?= $cookieData['username']; ?>"; // 获取用户名
        let networkStatus = true;
    </script>
</head>

<body>
    <nav class="navbar navbar-fixed-top navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center">
                <?= $SystemSetting->getSetting('site_name') ?>(<i class="bi bi-people-fill"></i><span id="chatroom-user-count"></span>)
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
                    <li class="nav-item">
                        <span>在线用户(<span id="online-users-list-count"></span>)</span>
                        <a id="online-users-list"></a>
                    </li>
                    <?php if ($cookieData['group_id'] === 1) : ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/Admin/index.php" target="_blank" rel="noopener noreferrer"><i class="bi bi-gear"></i> 后台管理</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <button id="logout" class="btn btn-danger nav-link"><i class="bi bi-box-arrow-right"></i> 退出登录</button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div id="chat-box-container" class="card shadow-sm">
        <div class="network-status alert"></div>
        <div id="chat-box" class="card-body talk">
            <div id="loading" class="text-center my-3">
                <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                <p class="mt-2 text-muted">加载中…</p>
            </div>
        </div>
        <div id="select-file-preview" class="p-3 position-absolute"></div>
        <form id="chat-form" class="card-footer d-flex flex-column align-items-stretch gap-3 p-3">
            <div class="d-flex gap-3">
                <div class="position-relative">
                    <button type="button" id="insert-md" class="btn btn-secondary" title="插入Markdown语法">
                        <i class="bi bi-markdown"></i>
                    </button>
                </div>
                <div class="position-relative">
                    <input type="file" name="file" id="file" class="d-none" multiple />
                    <button type="button" id="select-file" class="btn btn-secondary" title="上传文件">
                        <i class="bi bi-file-earmark-arrow-up"></i>
                    </button>
                </div>
                <textarea data-markdown="false" id="message" class="form-control flex-grow-1" rows="1"
                    placeholder="聊点什么吧，Ctrl+Enter发送消息"></textarea>
                <div class="position-relative">
                    <button type="submit" id="send-button" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i>
                    </button>
                </div>
            </div>
            <!-- 滚动到底部 -->
            <button type="button" id="scroll-down-button" class="btn btn-primary">
                <i class="bi bi-arrow-down-circle"></i>
            </button>
        </form>
    </div>

    <!-- 模态窗 -->
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
    <script src="/StaticResources/js/plyr.js"></script>
    <script src="/StaticResources/js/jquery.min.js"></script>
    <script src="/StaticResources/js/marked.min.js"></script>
    <script src="/StaticResources/js/highlight.min.js"></script>
    <script src="/StaticResources/js/highlight.prolog.min.js"></script>
    <script src="/StaticResources/js/bootstrap.bundle.min.js"></script>
    <script src="/StaticResources/js/jquery.contextMenu.min.js"></script>
    <script src="/StaticResources/js/helper.js?v=<?= FRAMEWORK_VERSION ?>"></script>
    <script src="/StaticResources/js/index.chat.js?v=<?= FRAMEWORK_VERSION ?>"></script>
    <script src="/StaticResources/js/chat.meun.js"></script>
    <script>
        $.ajax({
            type: "GET",
            url: "/api/v1/home/user",
            dataType: "JSON",
            success: function(response) {
                if (response.code === 200) {
                    $('#chatroom-user-count').text(response.data.registerUserCount);
                } else {
                    $('#chatroom-user-count').text('undefined');
                }
            },
            error: function() {
                $('#chatroom-user-count').text('undefined');
            }
        });
    </script>
</body>

</html>