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
<script src="/StaticResources/js/user.auth.js?v=<?php echo FRAMEWORK_VERSION ?>"></script>
<?php require_once FRAMEWORK_APP_PATH . '/Views/module/common.php' ?>
</body>

</html>