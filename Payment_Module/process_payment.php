<?php
// process_payment.php

include 'db_connect.php';

// Retrieve data from the POST request
// Using null coalescing operator to avoid "undefined index" warnings
$amount = $_POST['amount'] ?? 0;
$method = $_POST['payment_method'] ?? 'Not Specified';
$promo  = $_POST['promo_code'] ?? ''; 

// --- Discount Validation Logic ---
$discount_applied = 0.00;

// Check if a promo code was entered
if (!empty($promo)) {
    // Check for valid code
    if ($promo === "HEALTH20") {
        $discount_applied = 20.00;
    } 
    // If the code is invalid, stop the process
    else {
        print "<div style='border: 2px solid orange; padding: 20px; width: 350px; background-color: #fff3cd; font-family: Arial, sans-serif;'>";
        print "<h2 style='color: #cc7a00;'>Invalid Promo Code</h2>";
        print "<p>The code '<strong>" . htmlspecialchars($promo) . "</strong>' is incorrect or has expired.</p>";
        print "<p>Payment was not processed.</p>";
        print "<a href='checkout.php' style='color: #000; font-weight: bold;'>Go Back and Try Again</a>";
        print "</div>";
        
        die(); 
    }
}

// Calculate the final total
$final_amount = $amount - $discount_applied;

// Prevent negative totals
if ($final_amount < 0) {
    $final_amount = 0;
}

// --- Payment Simulation Logic ---
// 80% Success Rate, 20% Failure Rate
$simulation_result = rand(1, 100);
$status = ($simulation_result <= 80) ? "Success" : "Failed";

// Prepare the SQL query to save the transaction
$sql = "INSERT INTO payments (amount, discount_applied, final_amount, payment_method, payment_status) 
        VALUES ('$amount', '$discount_applied', '$final_amount', '$method', '$status')";

// Execute the query
if ($conn->query($sql) === TRUE) {
    
    $payment_id = $conn->insert_id; 
    print "<br>";

    // Display Receipt on Success
    if ($status === "Success") {
        print "<div style='border: 2px dashed #333; padding: 20px; width: 320px; font-family: \"Courier New\", Courier, monospace; background-color: #f9f9f9;'>";
        print "<h2 style='text-align: center; margin-bottom: 5px;'>CARE CONNECT</h2>";
        print "<p style='text-align: center; margin-top: 0;'>Official Receipt</p>";
        print "<hr>";
        print "<p><strong>Receipt No:</strong> CC-" . $payment_id . "</p>";
        print "<p><strong>Date:</strong> " . date("Y-m-d H:i:s") . "</p>";
        print "<p><strong>Method:</strong> " . htmlspecialchars($method) . "</p>";
        print "<hr>";
        print "<p>Subtotal: <span style='float:right;'>RM " . number_format($amount, 2) . "</span></p>";
        print "<p>Discount: <span style='float:right;'>-RM " . number_format($discount_applied, 2) . "</span></p>";
        print "<hr>";
        print "<h3 style='margin: 10px 0;'>TOTAL PAID: <span style='float:right;'>RM " . number_format($final_amount, 2) . "</span></h3>";
        print "<hr>";
        print "<p style='text-align: center; color: green; font-weight: bold;'>PAYMENT SUCCESSFUL</p>";
        print "<p style='text-align: center; font-size: 12px;'>Thank you for choosing Care Connect.</p>";
        print "</div>";
    } 
    // Display Error Message on Failure
    else {
        print "<div style='border: 2px solid #ff4d4d; padding: 20px; width: 350px; background-color: #ffe6e6; font-family: Arial, sans-serif;'>";
        print "<h2 style='color: #cc0000;'>Payment Failed</h2>";
        print "<p>We were unable to process your " . htmlspecialchars($method) . " transaction.</p>";
        print "<p>Please ensure your balance is sufficient and try again.</p>";
        print "<br>";
        print "<a href='checkout.php' style='display: inline-block; padding: 10px 15px; background: #cc0000; color: white; text-decoration: none; border-radius: 5px;'>Return to Checkout</a>";
        print "</div>";
    }

} else {
    // Show database errors if the query fails
    print "<div style='color: red;'>Database Error: " . $conn->error . "</div>";
}

// Close the connection
$conn->close();
?>