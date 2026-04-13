<?php include 'db_connect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notification Center</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="container" style="max-width: 800px;">
        <h2>Admin Notification Center</h2>
        <p style="text-align: center; color: #666;">Patients with Failed or Pending payments who need reminders.</p>
        
        <?php
        $sql = "SELECT * FROM payments WHERE payment_status = 'Failed' OR payment_status = 'Pending' ORDER BY payment_date DESC";
        $result = $conn->query($sql);

        print "<table>";
        print "<tr><th>Receipt ID</th><th>Amount Due</th><th>Status</th><th>Action</th></tr>";

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                print "<tr>";
                print "<td>REC-00" . $row['payment_id'] . "</td>";
                print "<td>RM " . $row['final_amount'] . "</td>";
                print "<td style='color: red; font-weight: bold;'>" . $row['payment_status'] . "</td>";
                
                print "<td>
                        <form action='send_reminder.php' method='POST' style='margin: 0;'>
                            <input type='hidden' name='payment_id' value='" . $row['payment_id'] . "'>
                            <button type='submit' style='background-color: #ffaa00; padding: 8px; margin: 0;'>Send Reminder</button>
                        </form>
                      </td>";
                print "</tr>";
            }
        } else {
            print "<tr><td colspan='4' style='text-align: center; color: green; font-weight: bold;'>All patients are caught up! No reminders needed.</td></tr>";
        }
        
        print "</table>";
        print "<a href='admin_summary.php'>Back to Admin Dashboard</a>";
        
        $conn->close();
        ?>
    </div>

</body>
</html>