<?php
// send_email.php - Email helper function using PHPMailer

// require_once __DIR__ . '/../config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// require_once __DIR__ . '/../Admin_Module/Email_System/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../Admin_Module/Email_System/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../Admin_Module/Email_System/phpmailer/src/SMTP.php';
require_once __DIR__ . '/../Admin_Module/Email_System/phpmailer/src/Exception.php';

// sendEmail function to send emails using PHPMailer
function sendEmail($to, $subject, $body, $isHTML = true) {
    if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    try {
        $mail = new PHPMailer(true);
        
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'smasharenabadminton@gmail.com';
        $mail->Password   = 'zrem mwsu ihct qvay';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        $mail->setFrom('smasharenabadminton@gmail.com', 'Smash Arena');
        $mail->addAddress($to);
        
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

?>