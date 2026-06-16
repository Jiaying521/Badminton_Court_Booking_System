<?php
session_start();
$host = 'localhost';
$dbname = 'badminton_hub';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: $url");
    exit;
}

/* Returns the web path to a user's avatar, falling back to the default image */
function getUserAvatar($profile_picture) {
    $defaultAvatar = '../image/default_image.png';

    if (!empty($profile_picture)) {
        $fullPath = __DIR__ . '/' . $profile_picture;
        if (file_exists($fullPath)) {
            return '../' . $profile_picture . '?v=' . filemtime($fullPath);
        }
    }

    return $defaultAvatar;
}

/* 自动向所有 HTML 页面注入 responsive.css（只对含 </head> 的 HTML 响应生效） */
ob_start(function ($html) {
    if (strpos($html, '</head>') !== false) {
        return str_replace(
            '</head>',
            '    <link rel="stylesheet" href="responsive.css">' . "\n" . '</head>',
            $html
        );
    }
    return $html;
});
?>