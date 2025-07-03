<form action="/Admin/settings/update_settings.php" method="post" onsubmit="return confirmBackup(this);">
    <h2>数据备份</h2>
    <hr>
    <input type="hidden" value="true" name="backup_database" id="backup_database">
    <button type="submit" class="btn btn-primary" id="backupBtn">备份数据</button>
    <span id="backupStatus" style="margin-left:10px;color:green;display:none;">正在备份，请稍候...</span>
</form>
<script>
    function confirmBackup(form) {
        if (confirm('确定要备份数据吗？')) {
            document.getElementById('backupBtn').disabled = true;
            document.getElementById('backupStatus').style.display = 'inline';
            return true;
        }
        return false;
    }
</script>