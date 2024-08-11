<?php
/**
 * Summary of displayToast
 * @param mixed $message
 * @param mixed $success
 * @return void
 */
function displayToast($message, $success = true)
{
    $bgClass = $success ? 'bg-primary' : 'bg-danger';
?>
    <div class="toast align-items-center text-white <?= $bgClass ?> border-0 fade show" role="alert" aria-live="assertive" aria-atomic="true"
        style="margin-bottom: 10px;">
        <div class="d-flex">
            <div class="toast-body">
                <?= htmlspecialchars($message) ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
<?php
}
