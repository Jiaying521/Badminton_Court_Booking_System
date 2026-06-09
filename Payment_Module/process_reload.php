<?php
// Bring in our master configuration settings
require_once __DIR__ . '/../config.php';
// Include our MySQLi connection file to read/write to tables
include 'db_connect.php'; 

// Check if a session hasn't started yet; if not, kick-start it
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// EASY HUMAN COMMENT: Force the system engine to use local Malaysia time for real-time sync
date_default_timezone_set('Asia/Kuala_Lumpur'); 

// Make sure the user is submitting data via a secure POST form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Catch all the incoming payload data from our wallet gateway interface form
    $user_id = $_SESSION['user_id'] ?? 0;   // The unique ID of the player topping up
    $amt = $_POST['reload_amount'] ?? 0;    // How much money they want to add to their wallet
    $return_to = $_POST['return_to'] ?? 'dashboard'; // Tells us where they came from (checkout or wallet page)
    $b_id = $_POST['booking_id'] ?? 0;      // The current booking ID if they topped up mid-checkout
    $b_amt = $_POST['amount'] ?? 0;          // The original court booking price if applicable
    $pay_method = $_POST['pay_method'] ?? 'Bank'; // Collect the banking method type chosen

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
        
        // EASY HUMAN COMMENT: Initialize the top-up memory array inside our PHP session structure if it does not exist
        if (!isset($_SESSION['recent_topups']) || !is_array($_SESSION['recent_topups'])) {
            $_SESSION['recent_topups'] = [];
        }

        // Change the short single-word payment option labels into clean readable phrase titles
        if ($pay_method === 'Bank') {
            $clean_method = 'Online Banking';
        } elseif ($pay_method === 'Card') {
            $clean_method = 'Credit / Debit Card';
        } else {
            $clean_method = "Touch 'n Go eWallet";
        }

        // Add this new top-up record directly into our active computer session memory stream row array
        $_SESSION['recent_topups'][] = [
            'amount' => $amt,
            'payment_method' => $clean_method,
            'payment_status' => 'success',
            'payment_date' => date('Y-m-d H:i:s')
        ];

        // EASY HUMAN COMMENT: Redirect the customer back to wallet.php first so they can see their new balance and transaction history updated!
        header("Location: wallet.php?return=" . urlencode($return_to) . "&booking_id=$b_id&amount=$b_amt");
        exit(); 
        
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