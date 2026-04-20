<?php
date_default_timezone_set('Asia/Kuala_Lumpur');
session_start();

// database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');               
define('DB_PASS', '');                   
define('DB_NAME', 'care_connect');       

// SMTP configuration (Gmail)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'adminclinic2026@gmail.com');
define('SMTP_PASS', 'brge wbgl zjnn egbp');  // get this from your Gmail account's App Passwords (not your regular password)
define('SMTP_FROM', 'adminclinic2026@gmail.com');
define('SMTP_FROM_NAME', 'CareConnect Clinic');

require_once __DIR__ . '/Admin_Module/Email_System/phpmailer/src/Exception.php';
require_once __DIR__ . '/Admin_Module/Email_System/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/Admin_Module/Email_System/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>