<?php
// 引入基本常量
require_once __DIR__ . '/../System/Core/Define.php';

if (!defined('FRAMEWORK_DATABASE_PATH')) {
    // 滚去给我安装😡！
    header('Location: /Admin/install/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>子辰聊天室 - 管理仪表板</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.1.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/nprogress/0.2.0/nprogress.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/StaticResources/css/module.rest.css">
    <link rel="stylesheet" href="/Admin/css/style.css">
</head>

<body class="d-flex flex-column min-vh-100">
    <header>
        <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
            <div class="container-fluid">
                <a class="navbar-brand"><i class="fas fa-cogs mr-2"></i> 后台管理</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarCollapse">
                    <ul class="navbar-nav me-auto mb-2 mb-md-0">
                        <li class="nav-item">
                            <a class="nav-link" aria-current="page" href="index.php"><i class="fas fa-tachometer-alt"></i> 仪表板</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="messages.php"><i class="fas fa-comments"></i> 消息管理</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php"><i class="fas fa-users"></i> 用户管理</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="settings.php"><i class="fas fa-cog"></i> 系统设置</a>
                        </li>
                    </ul>
                    <div class="d-flex">
                        <a href="#" class="btn btn-outline-light"><i class="fas fa-sign-out-alt"></i> 退出</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    <div id="NProgress"></div>

    <main class="container mt-5 flex-shrink-0" style="padding-top: 60px;">