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
    echo json_encode(['message' => 'Please enter your email.']);
    exit;
}

$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "care_connect"; 

$conn = new mysqli($host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    echo json_encode(['message' => 'Database connection failed.']);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM admins WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['message' => 'Email not found.']);
} else {
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
        
        $resetUrl = "http://localhost/fyp/Admin_Module/PasswordReset.php?email=" . urlencode($email);

        $mail->Body = "
        <script type='application/ld+json'>
        {
          '@context': 'http://schema.org',
          '@type': 'EmailMessage',
          'potentialAction': {
            '@type': 'HttpActionHandler',
            'name': 'Reset Password',
            'target': '$resetUrl'
          },
          'description': 'Reset your Admin password for Care Connect'
        }
        </script>

        <div style='font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 30px; text-align: center;'>
            <div style='max-width: 500px; margin: 0 auto; background-color: #ffffff; padding: 40px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); border: 1px solid #ddd;'>
                <h2 style='color: #2c3e50;'>Care Connect Clinic</h2>
                <p style='color: #555; font-size: 16px; line-height: 1.5;'>
                    You requested a password reset for your account. 
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
        echo json_encode(['message' => 'Reset link has been sent to your email!']);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => "Mail error: {$mail->ErrorInfo}"]);
    }
}

$stmt->close();
$conn->close();
?>