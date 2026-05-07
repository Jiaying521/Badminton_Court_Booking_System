<?php
// process_payment.php - The Final Receipt & Database Save

include 'db_connect.php';

// 1. Catch ALL the data from the gateway (Added booking_id!)
$booking_id = $_POST['booking_id'] ?? 0;
$amount = $_POST['amount'] ?? 0;
$method = $_POST['payment_method'] ?? 'Not Specified';
$promo  = $_POST['promo_code'] ?? ''; 

$discount_applied = 0.00;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Receipt</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="max-width: 500px; text-align: center;">
<?php

// 2. Promo Code Check
if (!empty($promo)) {
    if (strtoupper($promo) === "SMASH20") {
        $discount_applied = 20.00;
    } else {
        print "<h2 style='color: #ffaa00; text-align: center;'>⚠️ Invalid Promo Code</h2>";
        print "<p style='text-align: center;'>The code '<strong>" . htmlspecialchars($promo) . "</strong>' is incorrect or expired.</p>";
        // Fixed the back link so it remembers the booking!
        print "<a href='checkout.php?booking_id=$booking_id&amount=$amount' style='background-color: #1a2930; color: white; padding: 10px; border-radius: 6px; text-decoration: none; display: inline-block; margin-top: 15px;'>Go Back and Try Again</a>";
        print "</div></body></html>";
        die(); 
    }
}

// 3. Final Math
$final_amount = $amount - $discount_applied;
if ($final_amount < 0) {
    $final_amount = 0;
}

// 4. Payment Simulation (90% Success for your presentation)
$simulation_result = rand(1, 100);
$status = ($simulation_result <= 90) ? "Success" : "Failed";

// 5. Save to Payments Database
$sql = "INSERT INTO payments (booking_id, amount, discount_applied, final_amount, payment_method, payment_status) 
        VALUES ('$booking_id', '$amount', '$discount_applied', '$final_amount', '$method', '$status')";
if ($conn->query($sql) === TRUE) {
    
    $payment_id = $conn->insert_id; 

    if ($status === "Success") {
        
        // 🟢 THE BRAIN: Tell the bookings table this is PAID! 🟢
        if($booking_id) {
            $update_sql = "UPDATE bookings SET status = 'Confirmed' WHERE id = '$booking_id'";
            $conn->query($update_sql);
        }

        // 🟢 SUCCESSFUL RECEIPT UI
        print "<h2 style='color: #00b33c; border-bottom: none; margin-bottom: 5px;'>✅ Booking Confirmed!</h2>";
        print "<p style='color: #666; margin-top: 0;'>Your court is secured.</p>";
        
        // The Receipt Ticket Design
        print "<div style='background-color: #f9f9f9; padding: 25px; border: 2px dashed #ccc; border-radius: 8px; margin-bottom: 20px; text-align: left;'>";
        
        print "<h3 style='text-align: center; margin-top: 0; color: #1a2930; font-size: 24px; letter-spacing: 2px;'>SMASH ARENA</h3>";
        print "<p style='text-align: center; color: #888; font-size: 12px; margin-top: -10px; margin-bottom: 20px;'>OFFICIAL E-RECEIPT</p>";
        
        print "<p><strong>Booking Ref:</strong> #" . htmlspecialchars($booking_id) . "</p>";
        print "<p><strong>Receipt No:</strong> RCPT-" . str_pad($payment_id, 4, '0', STR_PAD_LEFT) . "</p>";
        print "<p><strong>Date:</strong> " . date("Y-m-d h:i A") . "</p>";
        print "<p><strong>Payment Method:</strong> " . htmlspecialchars($method) . "</p>";
        print "<hr style='border: 0; border-top: 1px solid #ddd; margin: 15px 0;'>";
        
        print "<p>Court Fee: <span style='float: right;'>RM " . number_format($amount, 2) . "</span></p>";
        
        if ($discount_applied > 0) {
            print "<p style='color: #00b33c; font-weight: bold;'>Promo (SMASH20): <span style='float: right;'>-RM " . number_format($discount_applied, 2) . "</span></p>";
        }
        
        print "<hr style='border: 0; border-top: 2px solid #1a2930; margin: 15px 0;'>";
        print "<h2 style='color: #1a2930; text-align: left; border: none; padding: 0; margin: 0;'>TOTAL PAID: <span style='float: right; color: #00b33c;'>RM " . number_format($final_amount, 2) . "</span></h2>";
        print "</div>";
        
        // Return ticket back to your friend's system!
        print "<a href='../Customer_Module/dashboard.php' style='background-color: #00b33c; color: white; padding: 15px; border-radius: 6px; text-decoration: none; display: inline-block; width: 80%;'>Return to Dashboard</a>";
        
    } else {
        // 🔴 FAILED PAYMENT UI
        print "<h2 style='color: #dc3545; border-bottom: none;'>❌ Payment Failed</h2>";
        print "<p style='text-align: center;'>We were unable to process your transaction via " . htmlspecialchars($method) . ".</p>";
        print "<p style='text-align: center; color: #666;'>Please check your balance and try again.</p>";
        // Fixed the back link here too!
        print "<a href='checkout.php?booking_id=$booking_id&amount=$amount' style='background-color: #dc3545; color: white; padding: 15px; border-radius: 6px; text-decoration: none; display: inline-block; margin-top: 15px;'>Try Payment Again</a>";
    }

} else {
    print "<p style='color: red; text-align: center;'>Database Error: " . $conn->error . "</p>";
}

$conn->close();
?>
    </div>
</body>
</html>