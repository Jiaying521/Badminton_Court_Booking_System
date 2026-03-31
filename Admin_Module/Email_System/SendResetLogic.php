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

$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "care_connect"; 

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
    
    // 3. Set expiry time (e.g., 10 minutes from now)
    $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes')); 

    // 4. Save the token and expiry time into the database
    $updateStmt = $conn->prepare("UPDATE admins SET reset_token = ?, token_expiry = ? WHERE email = ?");
    $updateStmt->bind_param("sss", $token, $expiry, $email);
    
    if ($updateStmt->execute()) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();                                    
            $mail->Host       = 'smtp.gmail.com';                
            $mail->SMTPAuth   = true;                             
            $mail->Username   = 'adminclinic2026@gmail.com';          
            $mail->Password   = 'wugc qoue fcta diqx';          
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;   
            $mail->Port       = 587;                              

            $mail->setFrom('adminclinic2026@gmail.com', 'Care Connect Clinic');
            $mail->addAddress($email);                           

            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'Reset Your Password - Care Connect';
            
            // 5. IMPORTANT: Link now uses ?token= instead of ?email=
            $resetUrl = "http://localhost/fyp/Admin_Module/PasswordReset.php?token=" . $token;

            $mail->Body = "
            <div style='font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 30px; text-align: center;'>
                <div style='max-width: 500px; margin: 0 auto; background-color: #ffffff; padding: 40px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); border: 1px solid #ddd;'>
                    <h2 style='color: #2c3e50;'>Care Connect Clinic</h2>
                    <p style='color: #555; font-size: 16px; line-height: 1.5;'>
                        You requested a password reset. This link is valid for 10 minutes only.
                        Click the button below to set a new password:
                    </p>
                    <a href='$resetUrl' 
                       style='display: inline-block; padding: 15px 30px; margin: 25px 0; background-color: #3498db; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px;'>
                       Reset My Password
                    </a>
                    <p style='font-size: 12px; color: #999;'>
                        If you didn't request this, please ignore this email.
                    </p>
                </div>
            </div>
            ";

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