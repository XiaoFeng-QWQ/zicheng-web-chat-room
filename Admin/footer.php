</main>

<footer class="footer mt-auto py-3 bg-dark">
    <div class="container text-center">
        <span class="text-light">
            &copy; 2024 子辰聊天室. All rights reserved. | 后端尚未完善，当前仅用于演示。
        </span>
    </div>
</footer>

<!-- Bootstrap JS and dependencies -->
<script src="https://cdn.bootcdn.net/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.bootcdn.net/ajax/libs/nprogress/0.2.0/nprogress.min.js"></script>
<script src="https://cdn.bootcdn.net/ajax/libs/jquery.pjax/2.0.1/jquery.pjax.min.js"></script>
<script src="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.1.0/js/bootstrap.bundle.min.js"></script>
<script>
    // Pjax 初始化以及相关配置
    $(document).pjax('a:not(a[target="_blank"],a[no-pjax])', {
        container: 'main',
        fragment: 'main',
        timeout: 20000
    });

    // Pjax 请求发送时显示进度条
    $(document).on('pjax:send', function() {
        NProgress.start();
    });

    // Pjax 请求结束时隐藏进度条并重新绑定表单事件
    $(document).on('pjax:end', function() {
        NProgress.done();
    });

    $(document).ready(function() {
        // 当导航链接被点击时执行
        $('.navbar-nav .nav-link').click(function() {
            // 移除所有导航链接上的 active 类
            $('.navbar-nav .nav-link').removeClass('active');

            // 为被点击的链接添加 active 类
            $(this).addClass('active');
        });
    });
</script>
</body>

</html>