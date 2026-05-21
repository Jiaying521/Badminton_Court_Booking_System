<?php
// process_payment.php - The Processing Engine (No HTML Output)
session_start();
include 'db_connect.php';

// 1. Catch ALL the data from gateway.php
$booking_id = $_POST['booking_id'] ?? 0;
$amount = $_POST['amount'] ?? 0; // Base court price amount
$method = $_POST['payment_method'] ?? 'Not Specified';
$promo = $_POST['promo_code'] ?? ''; 
$user_id = $_SESSION['user_id'] ?? 0;
$user_voucher_id = $_POST['user_voucher_id'] ?? ''; // 🟢 CATCH THE APPLIED VOUCHER DATA ROW ID

$discount_applied = 0.00;

// 2. Promo Code Check
if (!empty($promo)) {
    if (strtoupper($promo) === "SMASH20") {
        $discount_applied = 20.00;
    } else {
        // Redirect back with an invalid promo status flag if needed
        header("Location: checkout.php?booking_id=$booking_id&amount=$amount&error=invalid_promo");
        exit();
    }
}

// 🎫 🟢 VOUCHER DEDUCTION LOOKUP VALIDATION ENGINE
if (!empty($user_voucher_id) && $user_id > 0) {
    $v_check = $conn->prepare("
        SELECT v.discount_amount 
        FROM user_vouchers uv
        JOIN voucher v ON uv.voucher_id = v.id
        WHERE uv.id = ? AND uv.user_id = ? AND uv.is_used = 0
    ");
    $v_check->bind_param("ii", $user_voucher_id, $user_id);
    $v_check->execute();
    $v_result = $v_check->get_result()->fetch_assoc();
    
    if ($v_result) {
        // Add the voucher discount amount value onto your global running discount variable!
        $discount_applied += $v_result['discount_amount'];
    }
}

// 3. Final Math
$final_amount = $amount - $discount_applied;
if ($final_amount < 0) {
    $final_amount = 0;
}

// 4. Payment Simulation Check
$status = "Success"; 

if ($method === 'App Wallet') {
    // Check real user account balance
    $stmt_bal = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $stmt_bal->bind_param("i", $user_id);
    $stmt_bal->execute();
    $user_data = $stmt_bal->get_result()->fetch_assoc();
    $current_balance = $user_data['wallet_balance'] ?? 0.00;

    if ($current_balance < $final_amount) {
        $status = "Failed";
    }
} else {
    // 90% Success simulation for external mock gateways (Bank/Card/TNG)
    $simulation_result = rand(1, 100);
    $status = ($simulation_result <= 90) ? "Success" : "Failed";
}

// 5. Save to Payments Database Log History Row
$sql = "INSERT INTO payments (booking_id, amount, discount_applied, final_amount, payment_method, payment_status) 
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt_pay = $conn->prepare($sql);
$stmt_pay->bind_param("idddss", $booking_id, $amount, $discount_applied, $final_amount, $method, $status);

if ($stmt_pay->execute()) {
    
    if ($status === "Success") {
        // 🟢 WALLET DEDUCTION ENGINE: Subtract money if paid via app wallet
        if ($method === 'App Wallet' && $user_id > 0) {
            $deduct_sql = "UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?";
            $stmt_deduct = $conn->prepare($deduct_sql);
            $stmt_deduct->bind_param("di", $final_amount, $user_id);
            $stmt_deduct->execute();
        }

        // 🟢 CONFIRM THE BOOKING STATUS
        if ($booking_id) {
            $update_sql = "UPDATE bookings SET status = 'Confirmed' WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $booking_id);
            $update_stmt->execute();
        }

        // 🎫 🟢 CONSUME THE VOUCHER LOGIC: Mark it as used so they can't reuse it!
        if (!empty($user_voucher_id) && $user_id > 0) {
            $burn_sql = "UPDATE user_vouchers SET is_used = 1 WHERE id = ?";
            $burn_stmt = $conn->prepare($burn_sql);
            $burn_stmt->bind_param("i", $user_voucher_id);
            $burn_stmt->execute();
        }

        // 🟢 REDIRECT TO GATEWAY STEP 3 SUCCESS PAGE
        header("Location: gateway.php?step=3&status=success&booking_id=$booking_id&amount=$final_amount");
        exit();
        
    } else {
        // 🔴 REDIRECT TO GATEWAY STEP 3 FAILURE PAGE
        header("Location: gateway.php?step=3&status=fail&booking_id=$booking_id&amount=$amount");
        exit();
    }

} else {
    die("Critical Database Error: " . $conn->error);
}

$conn->close();
?>