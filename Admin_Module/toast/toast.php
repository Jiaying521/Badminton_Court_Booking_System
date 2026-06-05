<?php
require_once __DIR__ . '/toast_init.php';

if (!empty($_SESSION['toasts']) && is_array($_SESSION['toasts'])) {
    $toasts = array_merge($toasts, $_SESSION['toasts']);
    unset($_SESSION['toasts']);
}

if (!isset($base_path)) { $base_path = ''; }
?>

<link rel="stylesheet" href="<?php echo $base_path; ?>toast/toast.css">
<div class="toast-container" id="toastContainer">
    <?php foreach ($toasts as $t):
        $type = strtolower((string)($t['type'] ?? 'success'));
        if (!in_array($type, ['success', 'error', 'pending', 'loading'], true)) { $type = 'success'; }
        $icon  = $type === 'success' ? 'fa-check'
               : ($type === 'error' ? 'fa-xmark'
               : ($type === 'loading' ? 'fa-spinner' : 'fa-exclamation'));
        $label = $type === 'success' ? 'Success'
               : ($type === 'error' ? 'Error'
               : ($type === 'loading' ? 'Loading' : 'Notice'));
    ?>
        <div class="toast <?php echo htmlspecialchars($type); ?>" data-toast>
            <span class="toast-icon"><i class="fas <?php echo $icon; ?>"></i></span>
            <div class="toast-body">
                <span class="toast-label"><?php echo $label; ?></span>
                <span class="toast-text"><?php echo htmlspecialchars($t['text']); ?></span>
            </div>
            <button class="toast-close" type="button" aria-label="Dismiss">&times;</button>
        </div>
    <?php endforeach; ?>
</div>
<script src="<?php echo $base_path; ?>toast/toast.js"></script>
