<?php
// ============================================================
// functions.php - Common Functions Library
// ============================================================

// Import PHPMailer classes at the top
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer class files
require_once __DIR__ . '/../Admin_Module/Email_System/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../Admin_Module/Email_System/phpmailer/src/SMTP.php';
require_once __DIR__ . '/../Admin_Module/Email_System/phpmailer/src/Exception.php';

// ============================================================
// SESSION & REDIRECT FUNCTIONS
// ============================================================

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit;
    }
}

// ============================================================
// SETTINGS FUNCTIONS
// ============================================================

if (!function_exists('getSetting')) {
    function getSetting($key, $default = '') {
        global $pdo;
        try {
            if (!$pdo) {
                return $default;
            }
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetchColumn();
            return $result !== false ? $result : $default;
        } catch(PDOException $e) {
            return $default;
        }
    }
}

if (!function_exists('getUserBalance')) {
    function getUserBalance($user_id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result ? $result['wallet_balance'] : 0;
    }
}

if (!function_exists('updateUserBalance')) {
    function updateUserBalance($user_id, $amount) {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
        return $stmt->execute([$amount, $user_id]);
    }
}

if (!function_exists('logActivity')) {
    function logActivity($user_id, $username, $role, $action, $module, $description, $ip_address = null) {
        global $pdo;
        if (!$ip_address) {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, username, role, action, module, description, ip_address, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([$user_id, $username, $role, $action, $module, $description, $ip_address]);
    }
}

// ============================================================
// EMAIL SENDING FUNCTION (Direct send - for queue processor)
// ============================================================

if (!function_exists('sendEmail')) {
    function sendEmail($to, $subject, $body, $isHTML = true) {
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        try {
            $mail = new PHPMailer(true);
            
            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'smasharenabadminton@gmail.com';
            $mail->Password   = 'mrxd evvy oyyl boak';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            
            // Sender
            $mail->setFrom('smasharenabadminton@gmail.com', 'Smash Arena');
            $mail->addAddress($to);
            
            // Content
            $mail->isHTML($isHTML);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            
            if (!$isHTML) {
                $mail->AltBody = strip_tags($body);
            }
            
            return $mail->send();
            
        } catch (Exception $e) {
            error_log("Email failed to {$to}: " . $mail->ErrorInfo);
            return false;
        }
    }
}

// ============================================================
// EMAIL QUEUE FUNCTION (Store emails for background sending)
// ============================================================

if (!function_exists('queueEmail')) {
    function queueEmail($to, $subject, $body, $isHTML = false) {
        global $pdo;
        try {
            // Check if email_queue table exists
            $checkTable = $pdo->query("SHOW TABLES LIKE 'email_queue'");
            if ($checkTable->rowCount() == 0) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `email_queue` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `to_email` varchar(255) NOT NULL,
                      `subject` varchar(255) NOT NULL,
                      `body` text NOT NULL,
                      `is_html` tinyint(1) DEFAULT 0,
                      `status` enum('pending','sent','failed') DEFAULT 'pending',
                      `retry_count` int(11) DEFAULT 0,
                      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                      `sent_at` timestamp NULL DEFAULT NULL,
                      PRIMARY KEY (`id`),
                      KEY `idx_status` (`status`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
                ");
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO email_queue (to_email, subject, body, is_html, status, created_at) 
                VALUES (?, ?, ?, ?, 'pending', NOW())
            ");
            return $stmt->execute([$to, $subject, $body, $isHTML ? 1 : 0]);
        } catch (Exception $e) {
            error_log("Queue email error: " . $e->getMessage());
            return false;
        }
    }
}

// ============================================================
// AUTO PROCESS EMAIL QUEUE - Called on page load via AJAX
// ============================================================

if (!function_exists('autoProcessEmailQueue')) {
    function autoProcessEmailQueue() {
        global $pdo;
        
        // Only process if there are pending emails
        $check = $pdo->prepare("SELECT COUNT(*) FROM email_queue WHERE status = 'pending'");
        $check->execute();
        $pending = $check->fetchColumn();
        
        if ($pending > 0) {
            // Return the URL for AJAX to call
            return '../process_email_queue.php';
        }
        return null;
    }
}

// ============================================================
// DATABASE NOTIFICATION FUNCTION
// ============================================================

if (!function_exists('createNotification')) {
    function createNotification($recipient_role, $recipient_id, $type, $title, $message, $reference_id, $reference_type) {
        global $pdo;
        try {
            // Check if notifications table exists
            $checkTable = $pdo->query("SHOW TABLES LIKE 'notifications'");
            if ($checkTable->rowCount() == 0) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `notifications` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `recipient_role` enum('Superadmin','Admin','Coach','All') NOT NULL,
                      `recipient_id` int(11) DEFAULT NULL,
                      `type` varchar(50) NOT NULL,
                      `title` varchar(100) NOT NULL,
                      `message` text NOT NULL,
                      `reference_id` int(11) DEFAULT NULL,
                      `reference_type` varchar(30) DEFAULT NULL,
                      `is_read` tinyint(1) DEFAULT 0,
                      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
                ");
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO notifications (recipient_role, recipient_id, type, title, message, reference_id, reference_type, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$recipient_role, $recipient_id, $type, $title, $message, $reference_id, $reference_type]);
            return true;
        } catch(PDOException $e) {
            error_log("Notification error: " . $e->getMessage());
            return false;
        }
    }
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================

if (!function_exists('getBaseUrl')) {
    function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $script = dirname($_SERVER['SCRIPT_NAME']);
        return $protocol . '://' . $host . rtrim($script, '/') . '/../';
    }
}

if (!function_exists('generateRandomString')) {
    function generateRandomString($length = 10) {
        return substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
    }
}

if (!function_exists('calculateBookingPrice')) {
    function calculateBookingPrice($court, $start_time, $total_hours, $coach_price_total = 0) {
        $total = 0;
        $currentHour = (int)date('H', strtotime($start_time));
        
        for ($i = 0; $i < $total_hours; $i++) {
            $hour = ($currentHour + $i) % 24;
            if ($hour >= 8 && $hour < 14) {
                $total += $court['price_off_peak'];
            } else {
                $total += $court['price_peak'];
            }
        }
        
        return $total + $coach_price_total;
    }
}

// ============================================================
// GET USER AVATAR PATH (Unified function for all pages)
// ============================================================
if (!function_exists('getUserAvatar')) {
    /**
     * Get user avatar path with cache-busting timestamp
     * 
     * @param int $user_id The user ID
     * @return string The avatar URL path
     */
    function getUserAvatar($user_id) {
        global $pdo;
        
        // Default avatar path
        $defaultAvatar = '../image/default_image.png';
        
        try {
            // Get user's profile picture from database
            $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!$user || empty($user['profile_picture'])) {
                return $defaultAvatar;
            }
            
            $profile_picture = $user['profile_picture'];
            
            // Build full file path to check if file exists
            $fullPath = __DIR__ . '/../' . $profile_picture;
            
            if (file_exists($fullPath)) {
                $fileTime = filemtime($fullPath);
                return '../' . $profile_picture . '?v=' . $fileTime;
            }
            
            // If file doesn't exist, return default avatar
            return $defaultAvatar;
            
        } catch (Exception $e) {
            return $defaultAvatar;
        }
    }
}

// ============================================================
// EMAIL TEMPLATE FUNCTIONS (Outlook Compatible - No Emojis)
// ============================================================

// 1. Booking Confirmation Email Template (User) - Outlook Compatible
function getBookingConfirmedEmailTemplate($data) {
    $court_name = $data['court_name'] ?? 'Court';
    $date = $data['date'] ?? '';
    $start_time = $data['start_time'] ?? '';
    $end_time = $data['end_time'] ?? '';
    $coach_name = $data['coach_name'] ?? '';
    $booking_id = $data['booking_id'] ?? '';
    $customer_name = $data['customer_name'] ?? 'Customer';
    $total_price = $data['total_price'] ?? '0.00';
    $session_type = $data['session_type'] ?? 'Casual Play';
    
    $coach_html = '';
    if (!empty($coach_name)) {
        $coach_html = "
            <tr>
                <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                    <span style='color: #666; font-size: 13px;'>Coach:</span>
                </td>
                <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                    <span style='color: #333; font-weight: 500;'>{$coach_name}</span>
                </td>
            </tr>
        ";
    }
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
        <title>Session Booking Confirmed</title>
    </head>
    <body style='margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f0f2f0;'>
        <div style='max-width: 550px; margin: 0 auto; padding: 20px;'>
            <div style='background: white; border-radius: 8px; overflow: hidden; border: 1px solid #ddd; box-shadow: 0 2px 5px rgba(0,0,0,0.05);'>
                
                <div style='background: #2b7e3a; padding: 20px 20px; text-align: center;'>
                    <h1 style='color: white; font-size: 22px; font-weight: bold; margin: 0;'>Smash Arena</h1>
                    <p style='color: #c8e6d0; font-size: 13px; margin: 5px 0 0 0;'>Session Booking Confirmed</p>
                </div>
                
                <div style='padding: 25px;'>
                    <p style='color: #333; font-size: 14px; margin: 0 0 15px 0;'>
                        Dear <strong style='color: #2b7e3a;'>{$customer_name}</strong>,
                    </p>
                    <p style='color: #666; font-size: 13px; margin: 0 0 20px 0; line-height: 1.5;'>
                        Your booking has been confirmed. Here are the details:
                    </p>
                    
                    <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
                        <tr>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                                <span style='color: #666; font-size: 13px;'>Date:</span>
                            </td>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                                <span style='color: #333; font-weight: 500;'>{$date}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                                <span style='color: #666; font-size: 13px;'>Time:</span>
                            </td>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                                <span style='color: #333; font-weight: 500;'>{$start_time} - {$end_time}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                                <span style='color: #666; font-size: 13px;'>Court:</span>
                            </td>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                                <span style='color: #333; font-weight: 500;'>{$court_name}</span>
                            </td>
                        </tr>
                        {$coach_html}
                        <tr>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                                <span style='color: #666; font-size: 13px;'>Session Type:</span>
                            </td>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                                <span style='color: #333; font-weight: 500;'>{$session_type}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                                <span style='color: #666; font-size: 13px;'>Total Amount:</span>
                            </td>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                                <span style='color: #2b7e3a; font-weight: bold; font-size: 16px;'>RM {$total_price}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 10px 0;'>
                                <span style='color: #666; font-size: 13px;'>Booking ID:</span>
                            </td>
                            <td style='padding: 10px 0; text-align: right;'>
                                <span style='color: #999; font-size: 12px;'>#{$booking_id}</span>
                            </td>
                        </tr>
                    </table>
                    
                    <hr style='border: none; border-top: 1px solid #eef3ea; margin: 25px 0 15px 0;'>
                    
                    <div style='text-align: center;'>
                        <p style='color: #999; font-size: 11px; margin: 0 0 5px 0;'>Smash Arena - Your Game, Our Court</p>
                        <p style='color: #bbb; font-size: 10px; margin: 0;'>Need help? Contact us at support@smasharena.com</p>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
}

// 2. Admin Notification Email Template - Outlook Compatible
function getAdminNotificationEmailTemplate($data) {
    $booking_id = $data['booking_id'] ?? '';
    $customer_name = $data['customer_name'] ?? 'Customer';
    $customer_email = $data['customer_email'] ?? '';
    $court_name = $data['court_name'] ?? 'Court';
    $date = $data['date'] ?? '';
    $start_time = $data['start_time'] ?? '';
    $end_time = $data['end_time'] ?? '';
    $duration = $data['duration'] ?? '';
    $coach_name = $data['coach_name'] ?? '';
    $addons_total = $data['addons_total'] ?? 0;
    $total_paid = $data['total_paid'] ?? '0.00';
    $payment_method = $data['payment_method'] ?? '';
    
    $coach_html = '';
    if (!empty($coach_name)) {
        $coach_html = "
            <tr>
                <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                    <span style='color: #666; font-size: 13px;'>Coach:</span>
                </td>
                <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                    <span style='color: #333; font-weight: 500;'>{$coach_name}</span>
                </td>
            </tr>
        ";
    }
    
    $addons_html = '';
    if ($addons_total > 0) {
        $addons_html = "
            <tr>
                <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                    <span style='color: #666; font-size: 13px;'>Add-ons Total:</span>
                </td>
                <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                    <span style='color: #e67e22; font-weight: 600;'>RM " . number_format($addons_total, 2) . "</span>
                </td>
            </tr>
        ";
    }
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
        <title>Booking Paid & Confirmed</title>
    </head>
    <body style='margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f0f2f0;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background: white; border-radius: 8px; overflow: hidden; border: 1px solid #ddd; box-shadow: 0 2px 5px rgba(0,0,0,0.05);'>
                
                <div style='background: #2b7e3a; padding: 20px 20px; text-align: center;'>
                    <h1 style='color: white; font-size: 22px; font-weight: bold; margin: 0;'>Smash Arena</h1>
                    <p style='color: #c8e6d0; font-size: 13px; margin: 5px 0 0 0;'>Payment Confirmed</p>
                </div>
                
                <div style='padding: 25px;'>
                    <p style='color: #333; font-size: 14px; margin: 0 0 10px 0;'>
                        Booking <strong>#{$booking_id}</strong> has been <span style='color: #2b7e3a; font-weight: bold;'>PAID and CONFIRMED</span>.
                    </p>
                    
                    <hr style='border: none; border-top: 1px solid #eef3ea; margin: 20px 0;'>
                    
                    <h3 style='color: #333; font-size: 15px; margin: 0 0 15px 0;'>Customer Information</h3>
                    <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
                        <tr>
                            <td style='padding: 8px 0; border-bottom: 1px solid #eef3ea;'>
                                <span style='color: #666; font-size: 13px;'>Name:</span>
                            </td>
                            <td style='padding: 8px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                                <span style='color: #333; font-weight: 500;'>{$customer_name}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; border-bottom: 1px solid #eef3ea;'>
                                <span style='color: #666; font-size: 13px;'>Email:</span>
                            </td>
                            <td style='padding: 8px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                                <span style='color: #333;'>{$customer_email}</span>
                            </td>
                        </tr>
                    </table>
                    
                    <h3 style='color: #333; font-size: 15px; margin: 20px 0 15px 0;'>Booking Details</h3>
                    <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
                        <tr>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                                <span style='color: #666; font-size: 13px;'>Court:</span>
                            </td>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                                <span style='color: #333; font-weight: 500;'>{$court_name}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                                <span style='color: #666; font-size: 13px;'>Date:</span>
                            </td>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                                <span style='color: #333; font-weight: 500;'>{$date}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                                <span style='color: #666; font-size: 13px;'>Time:</span>
                            </td>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                                <span style='color: #333; font-weight: 500;'>{$start_time} - {$end_time}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                                <span style='color: #666; font-size: 13px;'>Duration:</span>
                            </td>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                                <span style='color: #333; font-weight: 500;'>{$duration}</span>
                            </td>
                        </tr>
                        {$coach_html}
                    </table>
                    
                    <h3 style='color: #333; font-size: 15px; margin: 20px 0 15px 0;'>Payment Information</h3>
                    <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
                        {$addons_html}
                        <tr>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                                <span style='color: #666; font-size: 13px;'>Payment Method:</span>
                            </td>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                                <span style='color: #333; font-weight: 500;'>{$payment_method}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 15px 0 0 0;'>
                                <span style='color: #333; font-size: 16px; font-weight: bold;'>Total Paid:</span>
                            </td>
                            <td style='padding: 15px 0 0 0; text-align: right;'>
                                <span style='color: #2b7e3a; font-weight: bold; font-size: 20px;'>RM {$total_paid}</span>
                            </td>
                        </tr>
                    </table>
                    
                    <hr style='border: none; border-top: 1px solid #eef3ea; margin: 25px 0 15px 0;'>
                    
                    <div style='text-align: center; margin-top: 20px;'>
                        <p style='color: #999; font-size: 11px; margin: 0;'>Smash Arena - Your Game, Our Court</p>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
}

// 3. Coach Notification Email Template - Outlook Compatible
function getCoachNotificationEmailTemplate($data) {
    $court_name = $data['court_name'] ?? 'Court';
    $date = $data['date'] ?? '';
    $start_time = $data['start_time'] ?? '';
    $end_time = $data['end_time'] ?? '';
    $customer_name = $data['customer_name'] ?? 'Customer';
    $booking_id = $data['booking_id'] ?? '';
    $coach_name = $data['coach_name'] ?? 'Coach';
    $session_type = $data['session_type'] ?? 'Training';
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
        <title>New Session Confirmed</title>
    </head>
    <body style='margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f0f2f0;'>
        <div style='max-width: 550px; margin: 0 auto; padding: 20px;'>
            <div style='background: white; border-radius: 8px; overflow: hidden; border: 1px solid #ddd; box-shadow: 0 2px 5px rgba(0,0,0,0.05);'>
                
                <div style='background: #2b7e3a; padding: 20px 20px; text-align: center;'>
                    <h1 style='color: white; font-size: 22px; font-weight: bold; margin: 0;'>Smash Arena</h1>
                    <p style='color: #c8e6d0; font-size: 13px; margin: 5px 0 0 0;'>New Session Confirmed</p>
                </div>
                
                <div style='padding: 25px;'>
                    <p style='color: #333; font-size: 14px; margin: 0 0 10px 0;'>
                        Dear <strong style='color: #2b7e3a;'>{$coach_name}</strong>,
                    </p>
                    <p style='color: #666; font-size: 13px; margin: 0 0 20px 0;'>
                        A coaching session has been confirmed for you. Here are the details:
                    </p>
                    
                    <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
                        <tr>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                                <span style='color: #666; font-size: 13px;'>Date:</span>
                            </td>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                                <span style='color: #333; font-weight: 500;'>{$date}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                                <span style='color: #666; font-size: 13px;'>Time:</span>
                            </td>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                                <span style='color: #333; font-weight: 500;'>{$start_time} - {$end_time}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                                <span style='color: #666; font-size: 13px;'>Court:</span>
                            </td>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                                <span style='color: #333; font-weight: 500;'>{$court_name}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                                <span style='color: #666; font-size: 13px;'>Customer:</span>
                            </td>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                                <span style='color: #333; font-weight: 500;'>{$customer_name}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                                <span style='color: #666; font-size: 13px;'>Session Type:</span>
                            </td>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                                <span style='color: #333; font-weight: 500;'>{$session_type}</span>
                            <td>
                        </tr>
                        <tr>
                            <td style='padding: 10px 0;'>
                                <span style='color: #666; font-size: 13px;'>Booking ID:</span>
                            </td>
                            <td style='padding: 10px 0; text-align: right;'>
                                <span style='color: #999; font-size: 12px;'>#{$booking_id}</span>
                            </td>
                        </tr>
                    </table>
                    
                    <hr style='border: none; border-top: 1px solid #eef3ea; margin: 25px 0 15px 0;'>
                    
                    <div style='text-align: center;'>
                        <a href='" . getBaseUrl() . "/Admin_Module/manage_bookings.php' style='display: inline-block; background: #2b7e3a; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; font-size: 13px; font-weight: 500;'>
                            View Dashboard
                        </a>
                    </div>
                    
                    <div style='text-align: center; margin-top: 20px;'>
                        <p style='color: #999; font-size: 11px; margin: 0;'>Smash Arena - Your Game, Our Court</p>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
}

// 4. Reschedule Email Template (User) - Outlook Compatible
function getRescheduledEmailTemplate($data) {
    $court_name = $data['court_name'] ?? 'Court';
    $old_date = $data['old_date'] ?? '';
    $old_time = $data['old_time'] ?? '';
    $new_date = $data['new_date'] ?? '';
    $new_time = $data['new_time'] ?? '';
    $duration = $data['duration'] ?? '';
    $booking_id = $data['booking_id'] ?? '';
    $customer_name = $data['customer_name'] ?? 'Customer';
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
        <title>Booking Rescheduled</title>
    </head>
    <body style='margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f0f2f0;'>
        <div style='max-width: 550px; margin: 0 auto; padding: 20px;'>
            <div style='background: white; border-radius: 8px; overflow: hidden; border: 1px solid #ddd; box-shadow: 0 2px 5px rgba(0,0,0,0.05);'>
                
                <div style='background: #e67e22; padding: 20px 20px; text-align: center;'>
                    <h1 style='color: white; font-size: 22px; font-weight: bold; margin: 0;'>Smash Arena</h1>
                    <p style='color: #fdebd0; font-size: 13px; margin: 5px 0 0 0;'>Booking Rescheduled</p>
                </div>
                
                <div style='padding: 25px;'>
                    <p style='color: #333; font-size: 14px; margin: 0 0 10px 0;'>
                        Dear <strong style='color: #e67e22;'>{$customer_name}</strong>,
                    </p>
                    <p style='color: #666; font-size: 13px; margin: 0 0 20px 0;'>
                        Your booking has been successfully rescheduled. Here are the updated details:
                    </p>
                    
                    <div style='background: #fef3e0; border-radius: 8px; padding: 15px; margin-bottom: 20px; border: 1px solid #fdebd0;'>
                        <p style='color: #b85c00; font-size: 12px; margin: 0 0 10px 0; font-weight: bold;'>[CANCELLED] Old Schedule</p>
                        <p style='color: #b85c00; font-size: 13px; margin: 0;'>{$old_date} | {$old_time}</p>
                    </div>
                    
                    <div style='background: #e8f5e8; border-radius: 8px; padding: 15px; margin-bottom: 20px; border: 1px solid #c8e6c9;'>
                        <p style='color: #2e7d32; font-size: 12px; margin: 0 0 10px 0; font-weight: bold;'>[NEW] Updated Schedule</p>
                        <p style='color: #2e7d32; font-size: 13px; margin: 0;'>{$new_date} | {$new_time}</p>
                    </div>
                    
                    <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
                        <tr>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                                <span style='color: #666; font-size: 13px;'>Court:</span>
                            </td>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                                <span style='color: #333; font-weight: 500;'>{$court_name}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                                <span style='color: #666; font-size: 13px;'>Duration:</span>
                            </td>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                                <span style='color: #333; font-weight: 500;'>{$duration} hour(s)</span>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 10px 0;'>
                                <span style='color: #666; font-size: 13px;'>Booking ID:</span>
                            </td>
                            <td style='padding: 10px 0; text-align: right;'>
                                <span style='color: #999; font-size: 12px;'>#{$booking_id}</span>
                            </td>
                        </tr>
                    </table>
                    
                    <div style='text-align: center; margin: 20px 0 0 0;'>
                        <a href='" . getBaseUrl() . "/Customer_Module/my_bookings.php' style='display: inline-block; background: #2b7e3a; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; font-size: 13px; font-weight: 500;'>
                            View My Bookings
                        </a>
                    </div>
                    
                    <hr style='border: none; border-top: 1px solid #eef3ea; margin: 25px 0 15px 0;'>
                    
                    <div style='text-align: center;'>
                        <p style='color: #999; font-size: 11px; margin: 0;'>Smash Arena - Your Game, Our Court</p>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
}

// 5. Cancelled Email Template - Outlook Compatible
function getCancelledEmailTemplate($data) {
    $court_name = $data['court_name'] ?? 'Court';
    $date = $data['date'] ?? '';
    $time = $data['time'] ?? '';
    $duration = $data['duration'] ?? '';
    $refund_amount = $data['refund_amount'] ?? '0.00';
    $booking_id = $data['booking_id'] ?? '';
    $customer_name = $data['customer_name'] ?? 'Customer';
    $cancellation_fee = $data['cancellation_fee'] ?? '0.00';
    
    $refund_text = '';
    if ($refund_amount > 0) {
        $refund_text = "
            <tr>
                <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                    <span style='color: #666; font-size: 13px;'>Refund Amount:</span>
                </td>
                <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                    <span style='color: #2b7e3a; font-weight: bold;'>RM {$refund_amount}</span>
                </td>
            </tr>
        ";
    }
    
    $fee_text = '';
    if ($cancellation_fee > 0) {
        $fee_text = "
            <tr>
                <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                    <span style='color: #666; font-size: 13px;'>Cancellation Fee:</span>
                </td>
                <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                    <span style='color: #e67e22; font-weight: 500;'>RM {$cancellation_fee}</span>
                </td>
            </tr>
        ";
    }
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
        <title>Booking Cancelled</title>
    </head>
    <body style='margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f0f2f0;'>
        <div style='max-width: 550px; margin: 0 auto; padding: 20px;'>
            <div style='background: white; border-radius: 8px; overflow: hidden; border: 1px solid #ddd; box-shadow: 0 2px 5px rgba(0,0,0,0.05);'>
                
                <div style='background: #dc2626; padding: 20px 20px; text-align: center;'>
                    <h1 style='color: white; font-size: 22px; font-weight: bold; margin: 0;'>Smash Arena</h1>
                    <p style='color: #fccccc; font-size: 13px; margin: 5px 0 0 0;'>Booking Cancelled</p>
                </div>
                
                <div style='padding: 25px;'>
                    <p style='color: #333; font-size: 14px; margin: 0 0 10px 0;'>
                        Dear <strong style='color: #dc2626;'>{$customer_name}</strong>,
                    </p>
                    <p style='color: #666; font-size: 13px; margin: 0 0 20px 0;'>
                        Your booking has been successfully cancelled.
                    </p>
                    
                    <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
                        <tr>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                                <span style='color: #666; font-size: 13px;'>Court:</span>
                            </td>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                                <span style='color: #333; font-weight: 500;'>{$court_name}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                                <span style='color: #666; font-size: 13px;'>Date:</span>
                            </td>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                                <span style='color: #333; font-weight: 500;'>{$date}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                                <span style='color: #666; font-size: 13px;'>Time:</span>
                            </td>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                                <span style='color: #333; font-weight: 500;'>{$time}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                                <span style='color: #666; font-size: 13px;'>Duration:</span>
                            </td>
                            <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                                <span style='color: #333; font-weight: 500;'>{$duration} hour(s)</span>
                            </td>
                        </tr>
                        {$fee_text}
                        {$refund_text}
                        <tr>
                            <td style='padding: 10px 0;'>
                                <span style='color: #666; font-size: 13px;'>Booking ID:</span>
                            </td>
                            <td style='padding: 10px 0; text-align: right;'>
                                <span style='color: #999; font-size: 12px;'>#{$booking_id}</span>
                            </td>
                        </tr>
                    </table>
                    
                    <div style='text-align: center; margin: 20px 0 0 0;'>
                        <a href='" . getBaseUrl() . "/Customer_Module/dashboard.php' style='display: inline-block; background: #2b7e3a; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; font-size: 13px; font-weight: 500;'>
                            Book a New Court
                        </a>
                    </div>
                    
                    <hr style='border: none; border-top: 1px solid #eef3ea; margin: 25px 0 15px 0;'>
                    
                    <div style='text-align: center;'>
                        <p style='color: #999; font-size: 11px; margin: 0;'>Smash Arena - Your Game, Our Court</p>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
}

// 6. Add-ons Added Email Template - Outlook Compatible
function getAddonsAddedEmailTemplate($data) {
    $court_name = $data['court_name'] ?? 'Court';
    $date = $data['date'] ?? '';
    $time = $data['time'] ?? '';
    $addons_items = $data['addons_items'] ?? [];
    $addons_total = $data['addons_total'] ?? '0.00';
    $previous_total = $data['previous_total'] ?? '0.00';
    $new_total = $data['new_total'] ?? '0.00';
    $booking_id = $data['booking_id'] ?? '';
    $customer_name = $data['customer_name'] ?? 'Customer';
    
    $items_html = '';
    foreach ($addons_items as $item) {
        $items_html .= "
        <tr>
            <td style='padding: 8px 0; border-bottom: 1px solid #eef3ea;'>
                <span style='color: #666; font-size: 13px;'>• {$item['name']} x {$item['quantity']}</span>
            </td>
            <td style='padding: 8px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                <span style='color: #333; font-weight: 500;'>RM " . number_format($item['total'], 2) . "</span>
            </td>
        </tr>
        ";
    }
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
        <title>Add-ons Added to Your Booking</title>
    </head>
    <body style='margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f0f2f0;'>
        <div style='max-width: 550px; margin: 0 auto; padding: 20px;'>
            <div style='background: white; border-radius: 8px; overflow: hidden; border: 1px solid #ddd; box-shadow: 0 2px 5px rgba(0,0,0,0.05);'>
                
                <div style='background: #2b7e3a; padding: 20px 20px; text-align: center;'>
                    <h1 style='color: white; font-size: 22px; font-weight: bold; margin: 0;'>Smash Arena</h1>
                    <p style='color: #c8e6d0; font-size: 13px; margin: 5px 0 0 0;'>Add-ons Added</p>
                </div>
                
                <div style='padding: 25px;'>
                    <p style='color: #333; font-size: 14px; margin: 0 0 10px 0;'>
                        Dear <strong style='color: #2b7e3a;'>{$customer_name}</strong>,
                    </p>
                    <p style='color: #666; font-size: 13px; margin: 0 0 20px 0;'>
                        You have successfully added items to your booking.
                    </p>
                    
                    <div style='background: #f0f5ed; border-radius: 8px; padding: 15px; margin-bottom: 20px; border: 1px solid #e0e8dc;'>
                        <p style='color: #333; font-size: 13px; margin: 0 0 10px 0; font-weight: bold;'>Booking Details</p>
                        <p style='color: #666; font-size: 12px; margin: 5px 0;'>Court: {$court_name}</p>
                        <p style='color: #666; font-size: 12px; margin: 5px 0;'>Date: {$date}</p>
                        <p style='color: #666; font-size: 12px; margin: 5px 0;'>Time: {$time}</p>
                    </div>
                    
                    <div style='background: white; border-radius: 8px; padding: 15px; margin-bottom: 20px; border: 1px solid #eef3ea;'>
                        <p style='color: #333; font-size: 13px; margin: 0 0 10px 0; font-weight: bold;'>Items Added</p>
                        <table style='width: 100%; border-collapse: collapse;'>
                            {$items_html}
                        </table>
                    </div>
                    
                    <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
                        <tr>
                            <td style='padding: 8px 0; border-bottom: 1px solid #eef3ea;'>
                                <span style='color: #666; font-size: 13px;'>Previous Total:</span>
                            </td>
                            <td style='padding: 8px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                                <span style='color: #333; font-weight: 500;'>RM {$previous_total}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; border-bottom: 1px solid #eef3ea;'>
                                <span style='color: #666; font-size: 13px;'>Add-ons Total:</span>
                            </td>
                            <td style='padding: 8px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                                <span style='color: #e67e22; font-weight: 600;'>+ RM {$addons_total}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 12px 0;'>
                                <span style='color: #333; font-size: 15px; font-weight: bold;'>New Total:</span>
                            </td>
                            <td style='padding: 12px 0; text-align: right;'>
                                <span style='color: #2b7e3a; font-weight: bold; font-size: 17px;'>RM {$new_total}</span>
                            </td>
                        </tr>
                    </table>
                    
                    <div style='text-align: center; margin: 20px 0 0 0;'>
                        <a href='" . getBaseUrl() . "/Payment_Module/checkout.php?booking_id={$booking_id}' style='display: inline-block; background: #2b7e3a; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; font-size: 13px; font-weight: 500;'>
                            Proceed to Payment
                        </a>
                    </div>
                    
                    <hr style='border: none; border-top: 1px solid #eef3ea; margin: 25px 0 15px 0;'>
                    
                    <div style='text-align: center;'>
                        <p style='color: #999; font-size: 11px; margin: 0;'>Smash Arena - Your Game, Our Court</p>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
}
?>