<?php
// config.php
date_default_timezone_set('Asia/Kuala_Lumpur');
session_start();

// 数据库配置
define('DB_HOST', 'localhost');
define('DB_USER', 'root');               // 你的数据库用户名
define('DB_PASS', '');                   // 你的数据库密码
define('DB_NAME', 'care_connect');       // 数据库名

// SMTP 配置（Gmail）
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'adminclinic2026@gmail.com');
define('SMTP_PASS', 'brge wbgl zjnn egbp');  // 从 Google 账号生成的应用专用密码
define('SMTP_FROM', 'adminclinic2026@gmail.com');
define('SMTP_FROM_NAME', 'CareConnect Clinic');

// 引入 PHPMailer（根据你的实际路径调整）
// 由于你的项目在 Email_System/phpmailer/src，需要引入 autoload 或手动引入
// 假设你在根目录创建 config.php，那么 PHPMailer 路径是 Email_System/phpmailer/src/
require_once __DIR__ . '/Admin_Module/Email_System/phpmailer/src/Exception.php';
require_once __DIR__ . '/Admin_Module/Email_System/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/Admin_Module/Email_System/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 初始化数据库连接
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>