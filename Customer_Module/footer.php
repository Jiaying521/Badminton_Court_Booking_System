<?php
// ============================================================
// footer.php - Common footer with auto email queue processing
// ============================================================
?>
<!-- Auto email queue processor - runs in background on every page -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    fetch('../process_email_queue.php', {
        method: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        if (data && data.sent > 0) {
            console.log('📧 Email queue: ' + data.sent + ' sent, ' + data.failed + ' failed');
        }
    })
    .catch(err => console.log('Email queue check:', err));
});
</script>
</body>
</html>