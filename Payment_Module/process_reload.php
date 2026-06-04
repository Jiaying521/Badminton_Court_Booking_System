<?php
// Bring in our master configuration settings
require_once __DIR__ . '/../config.php';
// Include our MySQLi connection file to read/write to tables
include 'db_connect.php'; 

// Check if a session hasn't started yet; if not, kick-start it
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Make sure the user is submitting data via a secure POST form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Catch all the incoming payload data from our wallet gateway interface form
    $user_id = $_SESSION['user_id'] ?? 0;   // The unique ID of the player topping up
    $amt = $_POST['reload_amount'] ?? 0;    // How much money they want to add to their wallet
    $return_to = $_POST['return_to'] ?? 'dashboard'; // Tells us where they came from (checkout or wallet page)
    $b_id = $_POST['booking_id'] ?? 0;      // The current booking ID if they topped up mid-checkout
    $b_amt = $_POST['amount'] ?? 0;          // The original court booking price if applicable

    // Basic validation to protect our code from empty data or zero/negative money reload attempts
    if ($user_id == 0 || $amt <= 0) {
        die("Invalid transaction request."); // Crash the script immediately if data is bad
    }

    // DATABASE UPDATE
    // Prepare our SQL statement to add the top-up cash amount straight into their current wallet balance
    $sql = "UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    // Securely bind the money amount (double/decimal) and user ID (integer) parameters to block SQL injection attacks
    $stmt->bind_param("di", $amt, $user_id);
    
    // Run the balance top-up update statement against our tables
    if ($stmt->execute()) {
        
        // SMART REDIRECT
        // If they were originally trying to check out a court and ran out of cash
        if ($return_to === 'checkout' && $b_id != 0) {
            // Send them right back to checkout.php with their booking details so they can finish renting their court instantly
            header("Location: checkout.php?booking_id=$b_id&amount=$b_amt&status=success");
        } else {
            // Otherwise, if they just topped up from the menu link, send them back to the main dashboard screen
            header("Location: ../Customer_Module/dashboard.php?status=success");
        }
        exit(); // Stop running the script execution after redirection triggers successfully
        
    } else {
        // Print a generic error message out if the database engine fails to write row inputs updates
        echo "Database Error: " . $conn->error;
    }
} else {
    // If someone tries to manually open this background processing file directly via the browser address link, kick them back out to the dashboard immediately
    header("Location: ../Customer_Module/dashboard.php");
    exit(); // Exit out safely
}
?>