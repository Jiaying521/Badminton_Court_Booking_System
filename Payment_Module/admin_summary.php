<?php include 'db_connect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="container" style="max-width: 800px;">
        <h2>Clinic Admin Dashboard</h2>
        
        <?php
        // 1. Calculate Total Revenue
        $sql_total = "SELECT SUM(final_amount) AS total_revenue FROM payments WHERE payment_status = 'Success'";
        $result_total = $conn->query($sql_total);
        $row_total = $result_total->fetch_assoc();
        
        $total = $row_total['total_revenue'] ? $row_total['total_revenue'] : 0.00;

        print "<div style='background-color: #e6f2ff; padding: 20px; border-radius: 8px; border: 1px solid #b3d9ff; text-align: center; margin-bottom: 20px;'>";
        print "<h3 style='margin: 0; color: #004d99;'>Total Clinic Revenue</h3>";
        print "<h1 style='margin: 10px 0 0 0;'>RM " . number_format($total, 2) . "</h1>";
        print "</div>";

        // 2. Breakdown by Payment Method
        print "<h3 style='text-align: center; color: #005580;'>Revenue Breakdown by Method</h3>";
        
        $sql_methods = "SELECT payment_method, SUM(final_amount) AS method_total, COUNT(*) AS txn_count 
                        FROM payments 
                        WHERE payment_status = 'Success' 
                        GROUP BY payment_method";
                        
        $result_methods = $conn->query($sql_methods);

        print "<table>";
        print "<tr><th>Payment Method</th><th>Total Processed</th><th>No. of Transactions</th></tr>";

        if ($result_methods->num_rows > 0) {
            while($row = $result_methods->fetch_assoc()) {
                print "<tr>";
                print "<td>" . $row['payment_method'] . "</td>";
                print "<td>RM " . number_format($row['method_total'], 2) . "</td>";
                print "<td>" . $row['txn_count'] . "</td>";
                print "</tr>";
            }
        } else {
            print "<tr><td colspan='3' style='text-align: center;'>No successful payments recorded yet.</td></tr>";
        }
        
        print "</table>";
        print "<a href='notifications.php'>Go to Notification Center</a>";
        
        $conn->close();
        ?>
    </div>

</body>
</html>