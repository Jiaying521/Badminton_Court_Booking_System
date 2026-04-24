<?php
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';

if (empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter your email.']);
    exit;
}

$host     = "localhost";
$db_user  = "root";
$db_pass  = "";
$db_name  = "badminton_hub";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit;
}

// 1. Check if email exists
$stmt = $conn->prepare("SELECT id FROM admins WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Email not found.']);
} else {
    // 2. Generate a secure random token
    $token = bin2hex(random_bytes(32));

    // 3. Set expiry time (10 minutes from now)
    $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    // 4. Save the token and expiry into the database
    $updateStmt = $conn->prepare("UPDATE admins SET reset_token = ?, token_expiry = ? WHERE email = ?");
    $updateStmt->bind_param("sss", $token, $expiry, $email);

    if ($updateStmt->execute()) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'smasharenabadminton@gmail.com';
            $mail->Password   = 'hgrk ocze fowx rbrd';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('smasharenabadminton@gmail.com', 'Badminton Hub');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'Reset Your Password - Badminton Hub';

            // 5. Reset link with token
            $resetUrl = "http://localhost/fyp/Admin_Module/PasswordReset.php?token=" . $token;

            $mail->Body = "
            <div style='font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 30px;'>
                <div style='max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);'>

                    <!-- Header -->
                    <div style='background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding: 32px; text-align: center;'>
                        <p style='margin:0; font-size:32px;'>&#127992;</p>
                        <h1 style='margin: 10px 0 4px; color: #f59e0b; font-size: 22px; letter-spacing: -0.5px;'>Badminton Hub</h1>
                        <p style='margin:0; color: rgba(255,255,255,0.6); font-size: 13px;'>Admin Portal</p>
                    </div>

                    <!-- Body -->
                    <div style='padding: 36px 32px;'>
                        <h2 style='margin: 0 0 8px; color: #0f172a; font-size: 20px;'>Password Reset Request</h2>
                        <p style='color: #64748b; font-size: 15px; line-height: 1.6; margin: 0 0 28px;'>
                            We received a request to reset the password for your Badminton Hub admin account.
                            Click the button below to set a new password. This link is valid for <strong>10 minutes only</strong>.
                        </p>

                        <a href='$resetUrl'
                           style='display: block; text-align: center; padding: 16px; background: linear-gradient(135deg, #f59e0b, #d97706); color: #0f172a; text-decoration: none; border-radius: 50px; font-weight: 800; font-size: 15px; margin-bottom: 28px;'>
                            Reset My Password &rarr;
                        </a>

                        <div style='background: #f8fafc; border-radius: 10px; padding: 16px 20px;'>
                            <p style='margin: 0; font-size: 12px; color: #94a3b8; line-height: 1.6;'>
                                If the button doesn't work, copy and paste this link into your browser:<br>
                                <span style='color: #64748b; word-break: break-all;'>$resetUrl</span>
                            </p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div style='background: #f8fafc; padding: 20px 32px; text-align: center; border-top: 1px solid #e2e8f0;'>
                        <p style='margin: 0; font-size: 12px; color: #94a3b8;'>
                            If you didn't request a password reset, you can safely ignore this email.<br>
                            This link will expire in 10 minutes.
                        </p>
                    </div>

                </div>
            </div>";

            $mail->send();
            echo json_encode(['status' => 'success', 'message' => 'Reset link has been sent to your email!']);

        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => "Mail error: {$mail->ErrorInfo}"]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save reset token.']);
    }
    $updateStmt->close();
}

$stmt->close();
$conn->close();
?>
