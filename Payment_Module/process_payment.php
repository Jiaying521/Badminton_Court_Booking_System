<?php
// process_payment.php

include 'db_connect.php';

// Catch the data from the form
$amount = $_POST['amount'];
$method = $_POST['payment_method'];
$promo = $_POST['promo_code']; 

// --- NEW: Improved Discount Validation Logic ---
$discount_applied = 0.00;

// Check if the user actually typed something in the promo box
if (!empty($promo)) {
    // If they typed the correct code, give the discount
    if ($promo === "HEALTH20") {
        $discount_applied = 20.00;
    } 
    // If they typed something wrong, show an error and STOP
    else {
        print "<div style='border: 2px solid orange; padding: 20px; width: 300px; background-color: #fff3cd; font-family: sans-serif;'>";
        print "<h2 style='color: #cc7a00;'>Invalid Promo Code</h2>";
        print "<p>The code '<strong>" . $promo . "</strong>' is not valid or has expired.</p>";
        print "<p>Your payment was not processed.</p>";
        print "<a href='checkout.php'>Go Back and Try Again</a>";
        print "</div>";
        
        // This stops the rest of the file from running so no money is charged
        die(); 
    }
}

$final_amount = $amount - $discount_applied;

// --- The Simulation Logic ---
$simulation_result = rand(1, 100);
$status = "";

if ($simulation_result <= 80) {
    $status = "Success";
} else {
    $status = "Failed";
}

// Update the SQL to save the data
$sql = "INSERT INTO payments (amount, discount_applied, final_amount, payment_method, payment_status) 
        VALUES ('$amount', '$discount_applied', '$final_amount', '$method', '$status')";

// Execute the database command
if ($conn->query($sql) === TRUE) {
    
    $payment_id = $conn->insert_id; 
    
    print "<br><br>";

    // Success Receipt
    if ($status === "Success") {
        print "<div style='border: 2px dashed #000; padding: 20px; width: 300px; font-family: monospace;'>";
        print "<h2 style='text-align: center;'>CLINIC RECEIPT</h2>";
        print "<hr>";
        print "<p><strong>Receipt No:</strong> REC-00" . $payment_id . "</p>";
        print "<p><strong>Date:</strong> " . date("Y-m-d H:i:s") . "</p>";
        print "<p><strong>Method:</strong> " . $method . "</p>";
        print "<hr>";
        print "<p>Subtotal: RM " . $amount . "</p>";
        print "<p>Discount: -RM " . $discount_applied . "</p>";
        print "<hr>";
        print "<h3>TOTAL PAID: RM " . $final_amount . "</h3>";
        print "<p style='text-align: center; color: green;'>Payment Successful!</p>";
        print "</div>";
    } 
    // Failure Box
    else {
        print "<div style='border: 2px solid red; padding: 20px; width: 300px; background-color: #ffe6e6; font-family: sans-serif;'>";
        print "<h2 style='color: red;'>Payment Failed</h2>";
        print "<p>We could not process your " . $method . " at this time.</p>";
        print "<p>Please try again or use a different payment method.</p>";
        print "<a href='checkout.php'>Go Back to Checkout</a>";
        print "</div>";
    }

} else {
    print "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>