<?php
// ============================================================
// process_email_queue.php - Process pending emails in background
// Supports both manual run and AJAX auto-trigger
// ============================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Customer_Module/functions.php';

// Check if this is an AJAX call
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Set maximum execution time to avoid timeout
set_time_limit(120);

// Process pending emails (max 5 per request to avoid slow down)
$stmt = $pdo->prepare("
    SELECT * FROM email_queue 
    WHERE status = 'pending' AND retry_count < 3 
    ORDER BY created_at ASC 
    LIMIT 5
");
$stmt->execute();
$emails = $stmt->fetchAll();

$sent_count = 0;
$failed_count = 0;
$errors = [];

foreach ($emails as $email) {
    // Send the email
    $success = sendEmail(
        $email['to_email'], 
        $email['subject'], 
        $email['body'], 
        $email['is_html'] == 1
    );
    
    if ($success) {
        // Mark as sent
        $update = $pdo->prepare("UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = ?");
        $update->execute([$email['id']]);
        $sent_count++;
    } else {
        // Increment retry count
        $new_retry = $email['retry_count'] + 1;
        if ($new_retry >= 3) {
            // Mark as failed after 3 attempts
            $update = $pdo->prepare("UPDATE email_queue SET status = 'failed', retry_count = ? WHERE id = ?");
            $update->execute([$new_retry, $email['id']]);
            $failed_count++;
        } else {
            // Keep as pending for next retry
            $update = $pdo->prepare("UPDATE email_queue SET retry_count = ? WHERE id = ?");
            $update->execute([$new_retry, $email['id']]);
        }
        $errors[] = "Failed to send to {$email['to_email']}";
    }
    
    // Small delay to avoid rate limiting
    usleep(200000); // 0.2 seconds
}

// ============================================================
// Output based on request type
// ============================================================
if ($is_ajax) {
    // AJAX call - return JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'sent' => $sent_count,
        'failed' => $failed_count,
        'remaining' => count($emails) - $sent_count - $failed_count,
        'errors' => $errors
    ]);
} else {
    // Manual browser access - show detailed output
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Email Queue Processor</title>
        <style>
            body { font-family: monospace; padding: 20px; background: #f5f5f5; }
            .success { color: green; }
            .error { color: red; }
            .info { color: blue; }
            .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
            h1 { color: #2b7e3a; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>📧 Email Queue Processor</h1>
            <hr>
            <p><strong>Processed:</strong> " . count($emails) . " emails</p>
            <p class='success'><strong>✓ Sent:</strong> $sent_count</p>
            <p class='error'><strong>✗ Failed:</strong> $failed_count</p>
            <p class='info'><strong>⏳ Remaining in this batch:</strong> " . (count($emails) - $sent_count - $failed_count) . "</p>
            <hr>";
    
    if (!empty($errors)) {
        echo "<h3>Errors:</h3><ul>";
        foreach ($errors as $err) {
            echo "<li class='error'>$err</li>";
        }
        echo "</ul><hr>";
    }
    
    // Show pending count
    $pending_stmt = $pdo->prepare("SELECT COUNT(*) FROM email_queue WHERE status = 'pending'");
    $pending_stmt->execute();
    $pending_count = $pending_stmt->fetchColumn();
    
    echo "<p><strong>📬 Total pending in queue:</strong> " . $pending_count . "</p>";
    echo "<hr>
            <a href='process_email_queue.php' style='background:#2b7e3a; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>⟳ Run Again</a>
            <a href='Customer_Module/dashboard.php' style='background:#555; color:white; padding:10px 20px; text-decoration:none; border-radius:5px; margin-left:10px;'>← Back to Dashboard</a>
        </div>
    </body>
    </html>";
}
?>