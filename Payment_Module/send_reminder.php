<?php
// send_reminder.php

// Catch the ID sent by the button
$payment_id = $_POST['payment_id'];

print "<br><br>";
print "<div style='border: 2px solid #009933; padding: 20px; width: 400px; font-family: sans-serif; background-color: #e6ffed; border-radius: 8px;'>";
print "<h2 style='color: #009933;'>✉️ Reminder Sent!</h2>";
print "<p>An automated Email and SMS reminder has been successfully simulated and sent to the patient for Receipt <strong>REC-00" . $payment_id . "</strong>.</p>";
print "<p>They have been provided with a secure link to complete their payment.</p>";
print "<br>";
print "<a href='notifications.php' style='text-decoration: none; background-color: #0066cc; color: white; padding: 10px 15px; border-radius: 4px;'>Go Back to Notification Center</a>";
print "</div>";
?>