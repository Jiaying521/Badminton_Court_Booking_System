<?php
// process_refund.php

// 1. Connect to the database
include 'db_connect.php';

// 2. Catch the specific payment ID sent by the refund button
$payment_id = $_POST['payment_id'];

// 3. Command the database to UPDATE the status to 'Refunded'
$sql = "UPDATE payments SET payment_status = 'Refunded' WHERE payment_id = '$payment_id'";

// 4. Execute the command and show a confirmation message
if ($conn->query($sql) === TRUE) {
    print "<br><br>";
    print "<div style='border: 2px solid #009933; padding: 20px; width: 300px; font-family: sans-serif; background-color: #e6ffed;'>";
    print "<h2 style='color: #009933;'>Refund Processed</h2>";
    print "<p>The payment for Receipt <strong>REC-00" . $payment_id . "</strong> has been successfully refunded to the customer.</p>";
    print "<a href='history.php'>Go Back to History</a>";
    print "</div>";
} else {
    print "Error processing refund: " . $conn->error;
}

// Close the connection
$conn->close();
?>