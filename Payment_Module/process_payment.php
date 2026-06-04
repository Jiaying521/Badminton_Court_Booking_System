<?php
// process_payment.php - The Processing Engine (No HTML Output)
// Start session tracking so we know who is currently logged in
session_start();
// Pull in the database connection settings
include 'db_connect.php';

// 1. Catch ALL the data sent from the gateway.php form submit
$booking_id = $_POST['booking_id'] ?? 0;       // The ID of the court being booked
$amount = $_POST['amount'] ?? 0;               // The original base price of the booking
$method = $_POST['payment_method'] ?? 'Not Specified'; // Wallet or Online Payment
$promo = $_POST['promo_code'] ?? '';           // Any manual text coupon code typed
$user_id = $_SESSION['user_id'] ?? 0;          // Unique ID of the current player
$user_voucher_id = $_POST['user_voucher_id'] ?? ''; // The database ID of the chosen rewards voucher

// Start our running discount counter at 0
$discount_applied = 0.00;

// 2. Promo Code Check
if (!empty($promo)) {
    // If they typed the master promo code "SMASH20"
    if (strtoupper($promo) === "SMASH20") {
        $discount_applied = 20.00; // Knock RM 20 off the bill
    } else {
        // If the typed promo code is wrong, kick them back to checkout with an error flag
        header("Location: checkout.php?booking_id=$booking_id&amount=$amount&error=invalid_promo");
        exit(); // Stop running the script right here
    }
}

// 🎫 VOUCHER DEDUCTION LOOKUP VALIDATION ENGINE
// If a user selected a claimed voucher from their dropdown inventory
if (!empty($user_voucher_id) && $user_id > 0) {
    // Check if the voucher actually belongs to this user and hasn't been used yet
    $v_check = $conn->prepare("
        SELECT v.discount_amount 
        FROM user_vouchers uv
        JOIN voucher v ON uv.voucher_id = v.id
        WHERE uv.id = ? AND uv.user_id = ? AND uv.is_used = 0
    ");
    $v_check->bind_param("ii", $user_voucher_id, $user_id);
    $v_check->execute();
    $v_result = $v_check->get_result()->fetch_assoc();
    
    // If the voucher is valid and verified in the database
    if ($v_result) {
        // Add the voucher's worth onto our total discount pool amount
        $discount_applied += $v_result['discount_amount'];
    }
}

// 3. Final Math
// Subtract whatever discount they got from the original court price tag
$final_amount = $amount - $discount_applied;
// If the discount is huge and makes the price negative, lock it to RM 0 so it's free
if ($final_amount < 0) {
    $final_amount = 0;
}

// 4. Payment Simulation Check
// Set default payment state to Success
$status = "Success"; 

// If the user chose to pay out of their app wallet balance
if ($method === 'App Wallet') {
    // Pull their real-time wallet account funds from the users table
    $stmt_bal = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $stmt_bal->bind_param("i", $user_id);
    $stmt_bal->execute();
    $user_data = $stmt_bal->get_result()->fetch_assoc();
    $current_balance = $user_data['wallet_balance'] ?? 0.00;

    // If they have less money than the final discounted price tag, fail the transaction
    if ($current_balance < $final_amount) {
        $status = "Failed";
    }
} else {
    // If they chose Online Banking/Card/TNG, roll a random number between 1 and 100
    $simulation_result = rand(1, 100);
    // Simulate a 90% pass rate. If the roll is 91-100, the bank mock-declines it!
    $status = ($simulation_result <= 90) ? "Success" : "Failed";
}

// 5. Save to Payments Database Log History Row
// Always insert a record of this attempt into our master payments audit table
$sql = "INSERT INTO payments (booking_id, amount, discount_applied, final_amount, payment_method, payment_status) 
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt_pay = $conn->prepare($sql);
$stmt_pay->bind_param("idddss", $booking_id, $amount, $discount_applied, $final_amount, $method, $status);

// If the log was written successfully into our database
if ($stmt_pay->execute()) {
    
    // If the payment actually passed validation or was approved by the bank simulation
    if ($status === "Success") {
        
        // WALLET DEDUCTION ENGINE: If they paid via wallet, deduct the final amount from their profile row
        if ($method === 'App Wallet' && $user_id > 0) {
            $deduct_sql = "UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?";
            $stmt_deduct = $conn->prepare($deduct_sql);
            $stmt_deduct->bind_param("di", $final_amount, $user_id);
            $stmt_deduct->execute();
        }

        // CONFIRM THE BOOKING STATUS
        // Flip the booking row state from 'Pending' to 'Confirmed' so the court is officially locked down
        if ($booking_id) {
            $update_sql = "UPDATE bookings SET status = 'Confirmed' WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $booking_id);
            $update_stmt->execute();
        }

        // LOYALTY POINTS: RM 1 = 1 point, persist into users.loyalty_points
        if ($user_id > 0) {
            $points_earned = (int) floor($final_amount);
            if ($points_earned > 0) {
                $pts_sql = "UPDATE users SET loyalty_points = loyalty_points + ? WHERE id = ?";
                $pts_stmt = $conn->prepare($pts_sql);
                $pts_stmt->bind_param("ii", $points_earned, $user_id);
                $pts_stmt->execute();
            }
        }

        // CONSUME THE VOUCHER LOGIC: Mark it as used so they can't reuse it!
        // Switch is_used to 1 for this specific voucher row so they can't exploit it again
        if (!empty($user_voucher_id) && $user_id > 0) {
            $burn_sql = "UPDATE user_vouchers SET is_used = 1 WHERE id = ?";
            $burn_stmt = $conn->prepare($burn_sql);
            $burn_stmt->bind_param("i", $user_voucher_id);
            $burn_stmt->execute();
        }

        // Redirect over to gateway.php Step 3 and show the green "Success" receipt panel
        header("Location: gateway.php?step=3&status=success&booking_id=$booking_id&amount=$final_amount");
        exit();
        
    } else {
        // If the wallet was short on cash or bank declined, redirect to Step 3 with the red "Fail" card
        header("Location: gateway.php?step=3&status=fail&booking_id=$booking_id&amount=$amount");
        exit();
    }

} else {
    // If the SQL database statement failed to insert into the tables, trigger a critical alert crash
    die("Critical Database Error: " . $conn->error);
}

// Close our connection pool link manually
$conn->close();
?>