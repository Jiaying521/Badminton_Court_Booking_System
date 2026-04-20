<?php

// Catch the ID sent by the button
$payment_id = $_POST['payment_id'] ?? 'Unknown';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reminder Sent</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="container" style="text-align: center;">
        <h2 style="color: #009933; border-bottom: none; font-size: 32px; margin-bottom: 0;">✉️ Reminder Sent!</h2>
        
        <div style="background-color: #e6ffed; padding: 20px; border-radius: 8px; border: 1px dashed #009933; margin: 20px 0;">
            <p style="font-size: 16px; margin-top: 0;">An automated Email and SMS reminder has been successfully simulated and sent to the patient for Receipt <strong>REC-00<?php print $payment_id; ?></strong>.</p>
            <p style="color: #555; margin-bottom: 0;">They have been provided with a secure link to complete their payment.</p>
        </div>

        <a href="notification.php" style="background-color: #0073e6; color: white; padding: 12px 20px; border-radius: 6px; display: inline-block; text-decoration: none;">Go Back to Notification Center</a>
    </div>

</body>
</html>