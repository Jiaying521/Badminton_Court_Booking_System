<?php
// process_payment.php - The Processing Engine (No HTML Output)

// Use PDO connection (consistent with functions.php)
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Customer_Module/functions.php';

// ============================================================
// 1. Catch ALL the data sent from the gateway.php form submit
// ============================================================
$booking_id = $_POST['booking_id'] ?? 0;
$amount = $_POST['amount'] ?? 0;
$method = $_POST['payment_method'] ?? 'Not Specified';
$promo = $_POST['promo_code'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;
$user_voucher_id = $_POST['user_voucher_id'] ?? '';
$sub_method = $_POST['sub_method'] ?? '';

// Start discount counter
$discount_applied = 0.00;

// ============================================================
// 2. Promo Code Check
// ============================================================
if (!empty($promo)) {
    if (strtoupper($promo) === "SMASH20") {
        $discount_applied = 20.00;
    } else {
        header("Location: checkout.php?booking_id=$booking_id&amount=$amount&error=invalid_promo");
        exit();
    }
}

// ============================================================
// 3. Voucher Deduction Validation
// ============================================================
if (!empty($user_voucher_id) && $user_id > 0) {
    $v_check = $pdo->prepare("
        SELECT v.discount_amount 
        FROM user_vouchers uv
        JOIN voucher v ON uv.voucher_id = v.id
        WHERE uv.id = ? AND uv.user_id = ? AND uv.is_used = 0
          AND (v.valid_until IS NULL OR v.valid_until >= NOW())
    ");
    $v_check->execute([$user_voucher_id, $user_id]);
    $v_result = $v_check->fetch();
    
    if ($v_result) {
        $discount_applied += $v_result['discount_amount'];
    }
}

// ============================================================
// 4. Final Math Calculations
// ============================================================
$final_amount = $amount - $discount_applied;
if ($final_amount < 0) {
    $final_amount = 0;
}

// ============================================================
// 5. Payment Simulation Check
// ============================================================
$status = "Success";

if ($method === 'App Wallet') {
    $stmt_bal = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $stmt_bal->execute([$user_id]);
    $user_data = $stmt_bal->fetch();
    $current_balance = $user_data['wallet_balance'] ?? 0.00;

    if ($current_balance < $final_amount) {
        $status = "Failed";
    }
} else {
    $simulation_result = rand(1, 100);
    $status = ($simulation_result <= 90) ? "Success" : "Failed";
}

// ============================================================
// 6. Save to Payments Database Log
// ============================================================
$sql = "INSERT INTO payments (booking_id, amount, discount_applied, final_amount, payment_method, payment_status) 
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt_pay = $pdo->prepare($sql);
$stmt_pay->execute([$booking_id, $amount, $discount_applied, $final_amount, $method, $status]);

$encoded_method = urlencode($method);
$encoded_sub = urlencode($sub_method);

// ============================================================
// 7. Process Successful Payment
// ============================================================
if ($status === "Success") {
    
    // Wallet deduction (if paid via wallet)
    if ($method === 'App Wallet' && $user_id > 0) {
        $deduct_sql = "UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?";
        $stmt_deduct = $pdo->prepare($deduct_sql);
        $stmt_deduct->execute([$final_amount, $user_id]);
    }

    // Confirm the booking status
    if ($booking_id) {
        $update_sql = "UPDATE bookings SET status = 'Confirmed' WHERE id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([$booking_id]);
    }

    // Loyalty points: RM 1 = 1 point
    if ($user_id > 0) {
        $points_earned = (int) floor($final_amount);
        if ($points_earned > 0) {
            $pts_sql = "UPDATE users SET loyalty_points = loyalty_points + ? WHERE id = ?";
            $pts_stmt = $pdo->prepare($pts_sql);
            $pts_stmt->execute([$points_earned, $user_id]);
        }
    }

    // Consume the voucher (mark as used)
    if (!empty($user_voucher_id) && $user_id > 0) {
        $burn_sql = "UPDATE user_vouchers SET is_used = 1 WHERE id = ?";
        $burn_stmt = $pdo->prepare($burn_sql);
        $burn_stmt->execute([$user_voucher_id]);
    }

    // ============================================================
    // 8. QUEUE EMAIL NOTIFICATIONS (包含 Add-ons 的完整确认邮件)
    // ============================================================
    try {
        // Get booking details with court and user info
        $booking_query = "
            SELECT b.*, c.court_name, c.court_type, u.name as user_name, u.email as user_email
            FROM bookings b
            JOIN courts c ON b.court_id = c.id
            JOIN users u ON b.user_id = u.id
            WHERE b.id = ?
        ";
        $stmt_booking = $pdo->prepare($booking_query);
        $stmt_booking->execute([$booking_id]);
        $booking = $stmt_booking->fetch();
        
        if ($booking) {
            // ============================================================
            // 8a. Get Add-ons details from database
            // ============================================================
            $stmt_addons = $pdo->prepare("
                SELECT a.*, p.name 
                FROM booking_addons a 
                JOIN products p ON a.product_id = p.id 
                WHERE a.booking_id = ?
            ");
            $stmt_addons->execute([$booking_id]);
            $addons_items = $stmt_addons->fetchAll();
            
            $addons_total = 0;
            $addons_html = '';
            
            if (!empty($addons_items)) {
                foreach ($addons_items as $item) {
                    $addons_total += $item['price'] * $item['quantity'];
                }
                
                // Build add-ons HTML table
                $addons_html = '
                <div style="background: #f8faf5; border-radius: 16px; padding: 15px; margin: 15px 0; border: 1px solid #eef3ea;">
                    <p style="font-weight: 700; margin-bottom: 12px; color: #2b7e3a;">
                        <i class="fas fa-shopping-bag"></i> Add-ons Purchased
                    </p>
                    <table style="width: 100%; border-collapse: collapse;">';
                
                foreach ($addons_items as $item) {
                    $item_total = $item['price'] * $item['quantity'];
                    $addons_html .= '
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #eef3ea;">
                            • ' . htmlspecialchars($item['name']) . ' x' . $item['quantity'] . '
                        </td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #eef3ea; text-align: right;">
                            RM ' . number_format($item_total, 2) . '
                        </td>
                    </tr>';
                }
                
                $addons_html .= '
                    <tr>
                        <td style="padding: 10px 0 0 0; font-weight: 600;">Add-ons Subtotal</td>
                        <td style="padding: 10px 0 0 0; text-align: right; font-weight: 600; color: #e67e22;">
                            RM ' . number_format($addons_total, 2) . '
                        </td>
                    </tr>
                    </table>
                </div>';
            }
            
            // ============================================================
            // 8b. Get coach information
            // ============================================================
            $date_formatted = date('M j, Y', strtotime($booking['booking_date']));
            $start_time_formatted = date('h:i A', strtotime($booking['start_time']));
            $end_time_formatted = date('h:i A', strtotime($booking['end_time']));
            $duration_text = $booking['total_hours'] . ' hour' . ($booking['total_hours'] > 1 ? 's' : '');
            
            $coach_name = '';
            $coach_email = null;
            if ($booking['coach_id'] > 0 && $booking['coach_hours'] > 0) {
                $coach_query = "
                    SELECT a.email, a.username as coach_name 
                    FROM coaches c 
                    JOIN admins a ON c.admin_id = a.id 
                    WHERE c.id = ?
                ";
                $stmt_coach = $pdo->prepare($coach_query);
                $stmt_coach->execute([$booking['coach_id']]);
                $coach_data = $stmt_coach->fetch();
                if ($coach_data) {
                    $coach_name = $coach_data['coach_name'];
                    $coach_email = $coach_data['email'];
                }
            }
            
            // ============================================================
            // 8c. Send HTML email to user (with add-ons)
            // ============================================================
            if (!empty($booking['user_email'])) {
                $emailData = [
                    'court_name' => $booking['court_name'],
                    'date' => $date_formatted,
                    'start_time' => $start_time_formatted,
                    'end_time' => $end_time_formatted,
                    'duration' => $booking['total_hours'],
                    'coach_name' => $coach_name,
                    'booking_id' => $booking_id,
                    'customer_name' => $booking['user_name'],
                    'total_price' => number_format($booking['total_price'], 2),
                    'session_type' => $booking['court_type'] == 'Training' ? 'Training' : 'Casual Play'
                ];
                
                // Get the base email template
                $userBody = getBookingConfirmedEmailTemplate($emailData);
                
                // Insert add-ons HTML before the price section
                if (!empty($addons_html)) {
                    // Find position to insert add-ons (before the price section)
                    $userBody = str_replace(
                        '<div class="price-info">',
                        $addons_html . '<div class="price-info" style="margin-top: 0;">',
                        $userBody
                    );
                }
                
                queueEmail($booking['user_email'], "Booking Confirmed - #{$booking_id}", $userBody, true);
            }
            
            // ============================================================
            // 8d. Send email to Admin and Superadmin
            // ============================================================
            // 8d. Send email to Admin and Superadmin (HTML格式)
            $admin_query = "SELECT email FROM admins WHERE role IN ('Superadmin', 'Admin') AND status = 'Active'";
            $admins = $pdo->query($admin_query)->fetchAll();
            
            $adminSubject = "Booking Paid & Confirmed - #{$booking_id}";
            
            // prepare data for admin email template
            $adminEmailData = [
                'booking_id' => $booking_id,
                'customer_name' => $booking['user_name'],
                'customer_email' => $booking['user_email'],
                'court_name' => $booking['court_name'],
                'date' => $date_formatted,
                'start_time' => $start_time_formatted,
                'end_time' => $end_time_formatted,
                'duration' => $duration_text,
                'coach_name' => $coach_name,
                'addons_total' => $addons_total,
                'total_paid' => number_format($booking['total_price'], 2),
                'payment_method' => $method
            ];
            
            $adminBody = getAdminNotificationEmailTemplate($adminEmailData);
            
            foreach ($admins as $admin) {
                if (!empty($admin['email']) && $admin['email'] != $booking['user_email']) {
                    queueEmail($admin['email'], $adminSubject, $adminBody, true);  // true = HTML格式
                }
            }
            
            // ============================================================
            // 8e. Send email to coach if assigned
            // ============================================================
            // 8e. Send email to coach if assigned
            if ($coach_email) {
                $coachSubject = "Session Confirmed - #{$booking_id}";
                $coachEmailData = [
                    'court_name' => $booking['court_name'],
                    'date' => $date_formatted,
                    'start_time' => $start_time_formatted,
                    'end_time' => $end_time_formatted,
                    'customer_name' => $booking['user_name'],
                    'booking_id' => $booking_id,
                    'coach_name' => $coach_name,
                    'session_type' => $booking['court_type'] == 'Training' ? 'Training' : 'Casual Play'
                ];
    
                $coachBody = getCoachNotificationEmailTemplate($coachEmailData);
    
                queueEmail($coach_email, $coachSubject, $coachBody, true);  // true = HTML格式
            }
            
            // ============================================================
            // 8f. Create database notifications
            // ============================================================
            createNotification('Admin', NULL, 'confirmed', 'Booking Paid & Confirmed', 
                "Booking #{$booking_id} from {$booking['user_name']} has been paid and confirmed." . ($addons_total > 0 ? " Add-ons: RM " . number_format($addons_total, 2) : ""), 
                $booking_id, 'booking');
            
            createNotification('Superadmin', NULL, 'confirmed', 'Booking Paid & Confirmed', 
                "Booking #{$booking_id} from {$booking['user_name']} has been paid and confirmed.", 
                $booking_id, 'booking');
            
            if ($booking['coach_id'] > 0) {
                createNotification('Coach', $booking['coach_id'], 'confirmed', 'Session Confirmed', 
                    "Your coaching session on {$date_formatted} at {$booking['court_name']} has been confirmed.", 
                    $booking_id, 'booking');
            }
        }
    } catch (Exception $e) {
        // Log error but don't interrupt the payment flow
        error_log("Email queue error: " . $e->getMessage());
    }

    // Redirect to success page
    header("Location: gateway.php?step=3&status=success&booking_id=$booking_id&amount=$final_amount&payment_method=$encoded_method&sub_method=$encoded_sub");
    exit();
    
} else {
    // Payment failed - redirect to fail page
    header("Location: gateway.php?step=3&status=fail&booking_id=$booking_id&amount=$amount&payment_method=$encoded_method&sub_method=$encoded_sub");
    exit();
}
?>