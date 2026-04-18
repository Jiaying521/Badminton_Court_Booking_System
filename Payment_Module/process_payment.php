<?php
include 'db_connect.php';

$amount = $_POST['amount'] ?? 0;
$method = $_POST['payment_method'] ?? 'Not Specified';
$promo  = $_POST['promo_code'] ?? ''; 

$discount_applied = 0.00;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Status</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
<?php

if (!empty($promo)) {
    if ($promo === "HEALTH20") {
        $discount_applied = 20.00;
    } else {
        print "<h2 style='color: orange; text-align: center;'>Invalid Promo Code</h2>";
        print "<p style='text-align: center;'>The code '<strong>" . htmlspecialchars($promo) . "</strong>' is incorrect.</p>";
        print "<a href='checkout.php'>Go Back and Try Again</a>";
        print "</div></body></html>";
        die(); 
    }
}

$final_amount = $amount - $discount_applied;

if ($final_amount < 0) {
    $final_amount = 0;
}

$simulation_result = rand(1, 100);
$status = ($simulation_result <= 80) ? "Success" : "Failed";

$sql = "INSERT INTO payments (amount, discount_applied, final_amount, payment_method, payment_status) 
        VALUES ('$amount', '$discount_applied', '$final_amount', '$method', '$status')";

if ($conn->query($sql) === TRUE) {
    
    $payment_id = $conn->insert_id; 

    if ($status === "Success") {
        print "<h2 style='color: #28a745; text-align: center;'>✅ Payment Successful!</h2>";
        print "<div style='background-color: #f9f9f9; padding: 15px; border: 1px dashed #ccc; border-radius: 8px; margin-bottom: 20px;'>";
        print "<p><strong>Receipt No:</strong> CC-" . $payment_id . "</p>";
        print "<p><strong>Date:</strong> " . date("Y-m-d H:i:s") . "</p>";
        print "<p><strong>Method:</strong> " . htmlspecialchars($method) . "</p>";
        print "<hr style='border: 0; border-top: 1px solid #ddd;'>";
        print "<p>Subtotal: RM " . number_format($amount, 2) . "</p>";
        
        if ($discount_applied > 0) {
            print "<p style='color: green;'>Discount: -RM " . number_format($discount_applied, 2) . "</p>";
        }
        
        print "<h3 style='color: #005580;'>TOTAL PAID: RM " . number_format($final_amount, 2) . "</h3>";
        print "</div>";
        print "<a href='checkout.php'>Return to Home</a>";
        
    } else {
        print "<h2 style='color: #dc3545; text-align: center;'>❌ Payment Failed</h2>";
        print "<p style='text-align: center;'>We were unable to process your " . htmlspecialchars($method) . " transaction.</p>";
        print "<a href='checkout.php'>Return to Checkout</a>";
    }

} else {
    print "<p style='color: red; text-align: center;'>Database Error: " . $conn->error . "</p>";
}

$conn->close();
?>
    </div>
</body>
</html>