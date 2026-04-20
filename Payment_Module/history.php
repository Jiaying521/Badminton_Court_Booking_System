<?php include 'db_connect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment History</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="container" style="max-width: 850px;"> <h2>My Payment History</h2>
        
        <?php
        $sql = "SELECT * FROM payments ORDER BY payment_date DESC";
        $result = $conn->query($sql);

        print "<table>";
        // 1. ADDED 'Date & Time' to the table headers
        print "<tr><th>Receipt ID</th><th>Date & Time</th><th>Amount Paid</th><th>Method</th><th>Status</th><th>Action</th></tr>";

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                print "<tr>";
                print "<td>REC-00" . $row['payment_id'] . "</td>";
                
                // 2. ADDED the formatted date column
                $formatted_date = date("d M Y, h:i A", strtotime($row['payment_date']));
                print "<td>" . $formatted_date . "</td>";
                
                print "<td>RM " . $row['final_amount'] . "</td>";
                print "<td>" . $row['payment_method'] . "</td>";
                
                // Color-coding the status
                if ($row['payment_status'] === 'Success') {
                    print "<td style='color: green; font-weight: bold;'>" . $row['payment_status'] . "</td>";
                    // Only show the refund button if successful
                    print "<td>
                            <form action='process_refund.php' method='POST' style='margin: 0;'>
                                <input type='hidden' name='payment_id' value='" . $row['payment_id'] . "'>
                                <button type='submit' style='background-color: #ff4d4d; padding: 8px; margin: 0;'>Refund</button>
                            </form>
                          </td>";
                } else if ($row['payment_status'] === 'Refunded') {
                    print "<td style='color: #ff4d4d; font-weight: bold;'>" . $row['payment_status'] . "</td>";
                    print "<td style='text-align: center;'>-</td>";
                } else {
                    print "<td style='color: red;'>" . $row['payment_status'] . "</td>";
                    print "<td style='text-align: center;'>-</td>";
                }
                
                print "</tr>";
            }
        } else {
            // 3. Changed colspan to 6 because our table is wider now
            print "<tr><td colspan='6' style='text-align:center;'>No payments found.</td></tr>";
        }

        print "</table>";
        print "<a href='checkout.php'>Make a New Payment</a>";

        $conn->close();
        ?>
    </div>

</body>
</html>