<?php

use ChatRoom\Core\Helpers\SystemSetting;
use ChatRoom\Core\Database\Base;
use ChatRoom\Core\Helpers\User;

$db = Base::getInstance()->getConnection();
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
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-contextmenu/2.7.1/jquery.contextMenu.min.css">
    <link href="https://cdn.bootcdn.net/ajax/libs/wangeditor5/5.1.23/css/style.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="/StaticResources/css/highlight/vs2015.min.css">
    <link rel="stylesheet" href="/StaticResources/css/index.chat.css?v=<?= FRAMEWORK_VERSION ?>">
</head>

<body>
    <nav class="navbar navbar-fixed-top navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center">
                <?= $SystemSetting->getSetting('site_name') ?>(<i class="bi bi-people-fill"></i><span id="chatroom-user-count"></span>)
            </a>
            <a class="navbar-brand d-flex align-items-center">
                <span>在线用户(<span id="online-users-list-count"></span>)</span>
                <a id="online-users-list"></a>
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
                            <?=
                            // 获取导航链接的设置
                            $navLinks = $SystemSetting->getSetting('nav_link');
                            if ($navLinks) {
                                $navLinks = unserialize($navLinks);
                            } else {
                                $navLinks = null;
                            }
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
                        <button class="btn btn-outline-primary nav-link" data-bs-toggle="modal" data-bs-target="#fileManagerModal">
                            <i class="bi bi-files"></i> 所有文件
                        </button>
                    </li>
                    <!-- 通知按钮 -->
                    <li class="nav-item dropdown">
                        <button type="button" class="btn btn-link nav-link position-relative" data-bs-toggle="modal" data-bs-target="#noticeListModal">
                            <i class="bi bi-bell"></i>
                            <span id="unreadNoticeBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display: none;">
                                0
                            </span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="btn btn-outline-secondary nav-link" data-bs-toggle="modal" data-bs-target="#cssCustomizeModal">
                            <i class="bi bi-palette"></i> 自定义界面
                        </button>
                    </li>
                    <?php if ($cookieData['group_id'] === 1) : ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/Admin/index.php" target="_blank" rel="noopener noreferrer"><i class="bi bi-gear"></i> 后台管理</a>
                        </li>
                        <li class="nav-item">
                            <button class="btn btn-outline-info nav-link" onclick="showNoticeForm()">
                                <i class="bi bi-bell-fill"></i> 发布公告
                            </button>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <button id="logout" class="btn btn-danger nav-link"><i class="bi bi-box-arrow-right"></i> 退出登录</button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div id="chat-box-container" class="card shadow-sm" style="z-index: 1;">
        <div class="network-status alert"></div>
        <div id="chat-box" class="card-body talk">
            <div id="loading" class="text-center my-3">
                <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                <p class="mt-2 text-muted">加载中…</p>
            </div>
        </div>
        <div id="select-file-preview" class="p-3 position-absolute"></div>
        <form id="chat-form" class="card-footer d-flex flex-column align-items-stretch gap-3 p-3">
            <div id="reply-preview" style="display: none;"></div>
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
    <?= require_once FRAMEWORK_APP_PATH . '/Views/module/modal.php' ?>
    <div id="toast-container" class="position-fixed bottom-0 end-0 p-3" style="z-index: 11;left: 0;"></div>
    <script src="/StaticResources/js/jquery.min.js"></script>
    <script src="/StaticResources/js/marked.min.js"></script>
    <script src="/StaticResources/js/highlight.min.js"></script>
    <script src="/StaticResources/js/highlight.prolog.min.js"></script>
    <script src="/StaticResources/js/bootstrap.bundle.min.js"></script>
    <script src="/StaticResources/js/jquery.contextMenu.min.js"></script>
    <script src="/StaticResources/js/wangeditor5.min.js"></script>
    <script src="/StaticResources/js/notification.js?v=<? FRAMEWORK_VERSION ?>"></script>
    <script src="/StaticResources/js/helper.js?v=<?= FRAMEWORK_VERSION ?>"></script>
    <script src="/StaticResources/js/index.chat.js?v=<?= FRAMEWORK_VERSION ?>"></script>
    <script src="/StaticResources/js/chat.meun.js"></script>
    <script src="/StaticResources/js/fun.audioVisualizer.js"></script>
    <script>
        // 等待DOM加载完成
        $(document).ready(function() {
            // 保存CSS设置
            function saveCssSettings() {
                const settings = {
                    customCss: $('#customCss').val()
                };

                localStorage.setItem('chatCssSettings', JSON.stringify(settings));
                applyCssSettings(settings);
            }

            // 加载CSS设置
            function loadCssSettings() {
                const savedSettings = localStorage.getItem('chatCssSettings');
                if (savedSettings) {
                    const settings = JSON.parse(savedSettings);
                    $('#customCss').val(settings.customCss);

                    // 应用设置
                    applyCssSettings(settings);
                }
            }

            // 应用CSS设置
            function applyCssSettings(settings) {
                const root = document.documentElement;

                // 应用自定义CSS
                let customStyle = $('#custom-css-style');
                if (customStyle.length === 0) {
                    $('head').append('<style id="custom-css-style"></style>');
                    customStyle = $('#custom-css-style');
                }
                customStyle.text(settings.customCss);
            }

            // 重置CSS设置
            function resetCssSettings() {
                localStorage.removeItem('chatCssSettings');
                $('#customCss').val('');
                // 移除自定义样式
                $('#custom-css-style').remove();
            }

            // 加载保存的设置
            loadCssSettings();

            // 保存设置按钮点击事件
            $('#saveCssSettings').on('click', function() {
                saveCssSettings();
                $('#cssCustomizeModal').modal('hide');
            });

            // 重置设置按钮点击事件
            $('#resetCssSettings').on('click', function() {
                if (confirm('确定要恢复默认设置吗？所有自定义设置将被重置。')) {
                    resetCssSettings();
                }
            });
        });
    </script>
    <?= require_once FRAMEWORK_APP_PATH . '/Views/module/common.php' ?>
</body>

</html>