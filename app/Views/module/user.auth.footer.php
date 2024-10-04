</div>

<script src="/StaticResources/js/jquery.min.js"></script>
<script src="/StaticResources/js/nprogress.min.js"></script>
<script src="/StaticResources/js/jquery.pjax.min.js"></script>
<script src="/StaticResources/js/bootstrap.bundle.min.js"></script>

<script>
    NProgress.configure({
        parent: '.user-auth-container'
    })
</script>
<span style="
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 0.9rem;
    color: #6c757d;
    padding: 10px;
    width: 100%;
    text-align: center;
    position: fixed;
    bottom: 0;
    left: 0;
">
    &copy; 2024 Powered By:
    <a href="http://blog.zicheng.icu" target="_blank" rel="noopener noreferrer" style="margin-left: 5px; color: #007bff; text-decoration: none;">
        小枫_QWQ |
    </a>&nbsp系统版本[<?= FRAMEWORK_VERSION ?>]
</span>
<script src="/StaticResources/js/user.auth.js?v=<?php echo FRAMEWORK_VERSION ?>"></script>

</body>

</html>