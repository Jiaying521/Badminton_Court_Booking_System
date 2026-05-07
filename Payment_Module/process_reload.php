<?php
require_once __DIR__ . '/../config.php';
include 'db_connect.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'GET' || $_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $amt = $_REQUEST['reload_amount'];
    $return_to = $_REQUEST['return_to'] ?? 'dashboard';
    $b_id = $_REQUEST['booking_id'] ?? 0;
    $b_amt = $_REQUEST['amount'] ?? 0;

    $sql = "UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("di", $amt, $user_id);
    
    if ($stmt->execute()) {
        // Redirect back to original page
        if ($return_to === 'checkout') {
            header("Location: checkout.php?booking_id=$b_id&amount=$b_amt&status=reloaded");
        } else {
            header("Location: ../Customer_Module/dashboard.php?status=reloaded");
        }
        exit();
    }
}
?>