<?php
// ============================================================
// edit_profile.php - Customer Profile Management Page
// Allows users to view and edit their profile information, change password, and upload avatar
// ============================================================

require_once __DIR__ . '/../config.php';

// Check if user is logged in, redirect to homepage if not
if (!isLoggedIn()) redirect('homepage.php');

$user_id = $_SESSION['user_id'];

// ============================================================
// CHECK AND ADD PROFILE_PICTURE COLUMN IF NOT EXISTS
// ============================================================
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `profile_picture` VARCHAR(255) NULL AFTER `wallet_balance`");
    }
} catch (PDOException $e) {
    // Column may already exist, ignore error
}

// ============================================================
// FETCH USER INFORMATION
// ============================================================
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    redirect('homepage.php');
}

// Get wallet balance
$real_balance = $user['wallet_balance'] ?? 0.00;

// ============================================================
// VERIFY USER IDENTITY (Password check before editing)
// ============================================================
$verified = isset($_SESSION['profile_verified']) && $_SESSION['profile_verified'] === true;
$verify_error = '';

// Handle password verification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'verify') {
    $verify_password = $_POST['verify_password'] ?? '';
    
    if (empty($verify_password)) {
        $verify_error = 'Please enter your password';
    } elseif (password_verify($verify_password, $user['password'])) {
        $_SESSION['profile_verified'] = true;
        $verified = true;
    } else {
        $verify_error = 'Incorrect password. Please try again.';
    }
}

// ============================================================
// GET USER AVATAR PATH (Using unified function from functions.php)
// ============================================================

// Get user avatar using unified function
$avatarPath = getUserAvatar($user_id);

// ============================================================
// HANDLE AVATAR UPLOAD
// ============================================================
$avatar_message = '';
$avatar_error = '';

if ($verified && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'upload_avatar') {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['profile_picture']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $avatar_error = 'Only JPG, PNG, GIF, and WEBP files are allowed';
        } elseif ($_FILES['profile_picture']['size'] > 2 * 1024 * 1024) {
            $avatar_error = 'File size must be less than 2MB';
        } else {
            $upload_dir = __DIR__ . '/../Pictures/Admin_Module/users/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                // Delete old avatar if exists
                if (!empty($profile_picture) && strpos($profile_picture, 'default') === false) {
                    $old_path = __DIR__ . '/../' . $profile_picture;
                    if (file_exists($old_path)) {
                        unlink($old_path);
                    }
                }
                
                $db_path = 'Pictures/Admin_Module/users/' . $new_filename;
                $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                if ($stmt->execute([$db_path, $user_id])) {
                    $avatar_message = 'Profile picture updated successfully!';
                    $profile_picture = $db_path;
                    $avatarPath = '../' . $db_path . '?v=' . time();
                    $user['profile_picture'] = $db_path;
                    echo "<script>window.location.href = 'edit_profile.php';</script>";
                    exit;
                } else {
                    $avatar_error = 'Failed to update database';
                }
            } else {
                $avatar_error = 'Failed to upload file';
            }
        }
    } else {
        $avatar_error = 'Please select a file to upload';
    }
}

// ============================================================
// HANDLE AVATAR DELETION
// ============================================================
if ($verified && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete_avatar') {
    if (!empty($profile_picture) && strpos($profile_picture, 'default') === false) {
        $old_path = __DIR__ . '/../' . $profile_picture;
        if (file_exists($old_path)) {
            unlink($old_path);
        }
    }
    $stmt = $pdo->prepare("UPDATE users SET profile_picture = NULL WHERE id = ?");
    if ($stmt->execute([$user_id])) {
        $avatar_message = 'Profile picture removed';
        $profile_picture = '';
        $avatarPath = '../image/default_image.png?v=' . time();
        $user['profile_picture'] = null;
        echo "<script>window.location.href = 'edit_profile.php';</script>";
        exit;
    } else {
        $avatar_error = 'Failed to remove profile picture';
    }
}

// ============================================================
// HANDLE PROFILE UPDATE & PASSWORD CHANGE (if verified)
// ============================================================
$message = '';
$error = '';
$password_message = '';
$password_error = '';

if ($verified) {
    // Handle profile information update
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        if (empty($name) || empty($email) || empty($phone)) {
            $error = 'All fields are required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format';
        } elseif (strlen($phone) < 5) {
            $error = 'Please enter a valid phone number';
        } else {
            // Check if email is already used by another account
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $error = 'Email already used by another account';
            } else {
                // Check if name is already taken by another user
                $stmt = $pdo->prepare("SELECT id FROM users WHERE name = ? AND id != ?");
                $stmt->execute([$name, $user_id]);
                if ($stmt->fetch()) {
                    $error = 'Name already taken by another user';
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
                    if ($stmt->execute([$name, $email, $phone, $user_id])) {
                        $message = 'Profile updated successfully!';
                        $user['name'] = $name;
                        $user['email'] = $email;
                        $user['phone'] = $phone;
                    } else {
                        $error = 'Failed to update profile';
                    }
                }
            }
        }
    }
    
    // Handle password change
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $password_error = 'All password fields are required';
        } elseif ($new_password !== $confirm_password) {
            $password_error = 'New password and confirm password do not match';
        } elseif (strlen($new_password) < 6) {
            $password_error = 'Password must be at least 6 characters';
        } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_password)) {
            $password_error = 'Password must contain at least one symbol (!@#$%^&* etc.)';
        } else {
            if (!password_verify($current_password, $user['password'])) {
                $password_error = 'Current password is incorrect';
            } elseif (password_verify($new_password, $user['password'])) {
                $password_error = 'New password cannot be the same as your current password';
            } else {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($stmt->execute([$hashed, $user_id])) {
                    $password_message = 'Password changed successfully!';
                } else {
                    $password_error = 'Failed to change password';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Profile | Smash Arena</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Modern font imports -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Reset and base styles */
        * { margin:0; padding:0; box-sizing:border-box; }
        
        body { 
            font-family: 'Inter', 'Poppins', 'Montserrat', sans-serif; 
            background: radial-gradient(circle at 10% 20%, rgba(240,245,236,1) 0%, rgba(226,236,217,1) 100%);
            color: #1e2a2e; 
            padding: 2rem;
            min-height: 100vh;
            position: relative;
        }
        
        /* Dynamic background pattern */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(rgba(43,126,58,0.08) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
            z-index: 0;
        }
        
        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
            position: relative;
            z-index: 1;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #e0e8dc; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #2b7e3a; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #1f5a2a; }
        
        /* ============================================================
           GLASSMORPHISM NAVBAR
        ============================================================ */
        .navbar { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 2rem; 
            flex-wrap: wrap; 
            gap: 1rem; 
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(15px);
            padding: 0.8rem 1.8rem;
            border-radius: 80px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.05);
            border: 1px solid rgba(255,255,255,0.3);
            animation: fadeInDown 0.6s ease-out;
        }
        
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .logo-area { 
            display: flex; 
            align-items: center; 
            gap: 0.8rem; 
            text-decoration: none; 
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .logo-area::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #2b7e3a, #e67e22);
            transition: width 0.4s ease;
        }
        
        .logo-area:hover::after { width: 100%; }
        .logo-area:hover .logo-text { transform: scale(1.02); }
        .logo-area img { 
            height: 45px; 
            width: auto; 
            transition: transform 0.3s ease;
        }
        .logo-area:hover img { transform: scale(1.02) rotate(5deg); }
        .logo-text { 
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-size: 1.4rem; 
            font-weight: 800; 
            background: linear-gradient(135deg, #2b7e3a 0%, #e67e22 80%);
            -webkit-background-clip: text; 
            background-clip: text; 
            color: transparent;
            letter-spacing: -1px;
            transition: transform 0.3s ease;
            text-transform: uppercase;
        }
        .logo-text span { 
            background: linear-gradient(135deg, #e67e22 0%, #f39c12 100%); 
            -webkit-background-clip: text; 
            background-clip: text; 
            color: transparent;
        }
        
        .nav-links { 
            display: flex; 
            align-items: center; 
            gap: 0.8rem; 
            flex-wrap: wrap; 
        }
        .nav-links a { 
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 600;
            letter-spacing: 0.2px;
            color: #2c4a2e; 
            text-decoration: none; 
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            position: relative;
            overflow: hidden;
        }
        
        .nav-links a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }
        
        .nav-links a:hover::before { left: 100%; }
        .nav-links a:hover, .nav-links a.active { 
            background: #2b7e3a;
            color: white; 
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(43,126,58,0.3);
            border-radius: 50px;
        }
        
        /* User profile area - clickable to edit profile */
        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            cursor: pointer;
            background: rgba(234,245,230,0.6);
            padding: 0.3rem 1rem 0.3rem 0.5rem;
            border-radius: 50px;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .user-profile:hover {
            background: rgba(43,126,58,0.15);
            transform: translateY(-2px);
            border-radius: 50px;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            background: #2b7e3a;
        }
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .user-info {
            text-align: left;
        }
        .user-name {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 0.85rem;
            color: #1e3a2a;
        }
        .user-balance {
            font-family: 'DM Sans', sans-serif;
            font-size: 0.7rem;
            color: #2b7e3a;
            font-weight: 600;
        }
        
        .btn-logout { 
            background: #fee2e2; 
            color: #e67e22; 
            padding: 0.5rem 1.2rem; 
            border-radius: 50px; 
            text-decoration: none; 
            font-size: 0.85rem; 
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-logout:hover { 
            background: #e67e22; 
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(230,126,34,0.3);
            border-radius: 50px;
        }
        
        /* ============================================================
           PROFILE CARD - Glassmorphism
        ============================================================ */
        .profile-card { 
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(10px);
            border-radius: 32px; 
            overflow: hidden; 
            box-shadow: 0 12px 28px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
            border: 1px solid rgba(255,255,255,0.3);
            animation: fadeInScale 0.5s ease-out 0.1s both;
        }
        
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        
        /* Verify Card - Glassmorphism */
        .verify-card { 
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(10px);
            border-radius: 32px; 
            overflow: hidden; 
            box-shadow: 0 12px 28px rgba(0,0,0,0.08);
            max-width: 450px; 
            margin: 0 auto;
            border: 1px solid rgba(255,255,255,0.3);
            animation: fadeInScale 0.5s ease-out;
        }
        
        .verify-header { 
            background: linear-gradient(135deg, #2b7e3a, #1b5e2a);
            color: white; 
            padding: 2rem; 
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .verify-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1), transparent);
            animation: pulse 4s ease-in-out infinite;
            pointer-events: none;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        .verify-header .icon { font-size: 3rem; margin-bottom: 0.5rem; }
        .verify-header h2 { 
            font-family: 'Montserrat', 'Poppins', sans-serif;
            font-size: 1.5rem; 
            margin-bottom: 0.3rem; 
        }
        .verify-header p { opacity: 0.9; font-size: 0.9rem; }
        .verify-body { padding: 2rem; }
        .verify-body .info-group { margin-bottom: 1.5rem; }
        .verify-body label { 
            font-family: 'Montserrat', 'Inter', sans-serif;
            display: block; 
            font-weight: 600; 
            color: #1e3a2a; 
            margin-bottom: 0.5rem; 
        }
        .verify-body input { 
            width: 100%; 
            padding: 0.8rem 1rem; 
            border: 2px solid rgba(224,232,220,0.8);
            border-radius: 16px; 
            background: rgba(254,253,248,0.9);
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            transition: 0.2s;
        }
        .verify-body input:focus { outline: none; border-color: #2b7e3a; box-shadow: 0 0 0 3px rgba(43,126,58,0.1); }
        .btn-verify { 
            background: linear-gradient(135deg, #2b7e3a, #1f5a2a);
            color: white; 
            border: none; 
            padding: 0.9rem; 
            border-radius: 50px; 
            width: 100%; 
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 700; 
            cursor: pointer; 
            margin-top: 0.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .btn-verify::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }
        .btn-verify:hover::before { left: 100%; }
        .btn-verify:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(43,126,58,0.3); }
        .error-msg { background: #fee2dd; color: #b45f1b; padding: 0.8rem; border-radius: 16px; margin-bottom: 1rem; border-left: 4px solid #e67e22; }
        
        .profile-header { 
            background: linear-gradient(135deg, #2b7e3a, #1b5e2a);
            color: white; 
            padding: 2rem; 
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1), transparent);
            animation: pulse 4s ease-in-out infinite;
            pointer-events: none;
        }
        .profile-header .avatar { 
            width: 120px; 
            height: 120px; 
            background: rgba(255,255,255,0.2); 
            border-radius: 50%; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            margin-bottom: 1rem; 
            overflow: hidden; 
            position: relative; 
            cursor: pointer;
            transition: transform 0.3s;
        }
        .profile-header .avatar:hover { transform: scale(1.02); }
        .profile-header .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .profile-header .avatar .edit-overlay { 
            position: absolute; 
            bottom: 0; 
            left: 0; 
            right: 0; 
            background: rgba(0,0,0,0.6); 
            color: white; 
            text-align: center; 
            padding: 5px; 
            font-size: 12px; 
            opacity: 0; 
            transition: 0.3s;
        }
        .profile-header .avatar:hover .edit-overlay { opacity: 1; }
        .profile-header h2 { 
            font-family: 'Montserrat', 'Poppins', sans-serif;
            font-size: 1.5rem; 
            margin-bottom: 0.3rem; 
        }
        .profile-header p { opacity: 0.9; font-size: 0.9rem; }
        .profile-body { padding: 2rem; }
        
        /* ============================================================
           MODAL - Glassmorphism
        ============================================================ */
        .modal { 
            display: none; 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,0.5); 
            backdrop-filter: blur(8px);
            z-index: 1000; 
            align-items: center; 
            justify-content: center;
        }
        .modal-content { 
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 32px; 
            max-width: 450px; 
            width: 90%; 
            padding: 2rem; 
            text-align: center;
            border: 1px solid rgba(255,255,255,0.3);
            animation: fadeInUp 0.3s ease;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .modal-content h3 { 
            font-family: 'Montserrat', 'Poppins', sans-serif;
            color: #2b7e3a; 
            margin-bottom: 1rem; 
        }
        .modal-content .preview { 
            width: 150px; 
            height: 150px; 
            border-radius: 50%; 
            margin: 1rem auto; 
            overflow: hidden; 
            background: #e8efe2; 
            display: flex; 
            align-items: center; 
            justify-content: center;
        }
        .modal-content .preview img { width: 100%; height: 100%; object-fit: cover; }
        .modal-content input[type="file"] { 
            margin: 1rem 0; 
            display: block; 
            width: 100%;
            padding: 0.5rem;
            border: 2px solid rgba(224,232,220,0.8);
            border-radius: 16px;
            background: rgba(254,253,248,0.9);
        }
        .modal-buttons { display: flex; gap: 1rem; margin-top: 1rem; flex-wrap: wrap; }
        .modal-buttons button { 
            flex: 1; 
            padding: 0.8rem; 
            border-radius: 50px; 
            border: none; 
            cursor: pointer; 
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 600; 
            min-width: 100px;
            transition: all 0.3s ease;
        }
        .btn-upload { background: linear-gradient(135deg, #2b7e3a, #1f5a2a); color: white; position: relative; overflow: hidden; }
        .btn-upload::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent); transition: left 0.5s ease; }
        .btn-upload:hover::before { left: 100%; }
        .btn-upload:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(43,126,58,0.3); }
        .btn-delete { background: #e67e22; color: white; }
        .btn-delete:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(230,126,34,0.3); }
        .btn-cancel-modal { background: #e0e0e0; color: #333; }
        .btn-cancel-modal:hover { transform: translateY(-2px); }
        
        /* ============================================================
           FORM SECTIONS
        ============================================================ */
        .form-section { 
            margin-bottom: 2rem; 
            padding-bottom: 2rem; 
            border-bottom: 1px solid rgba(224,224,224,0.5);
        }
        .form-section:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .form-section h3 { 
            font-family: 'Montserrat', 'Poppins', sans-serif;
            color: #2b7e3a; 
            margin-bottom: 1rem; 
            font-size: 1.2rem; 
            display: flex; 
            align-items: center; 
            gap: 0.5rem; 
        }
        
        .info-group { margin-bottom: 1.2rem; }
        .info-group label { 
            font-family: 'Montserrat', 'Inter', sans-serif;
            display: block; 
            font-weight: 600; 
            color: #1e3a2a; 
            margin-bottom: 0.5rem; 
            font-size: 0.85rem; 
        }
        .info-group input { 
            width: 100%; 
            padding: 0.8rem 1rem; 
            border: 2px solid rgba(224,232,220,0.8);
            border-radius: 16px; 
            background: rgba(254,253,248,0.9);
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            transition: 0.2s;
        }
        .info-group input:focus { outline: none; border-color: #2b7e3a; box-shadow: 0 0 0 3px rgba(43,126,58,0.1); }
        .info-group .readonly { background: rgba(232,240,229,0.5); color: #5a6e5c; cursor: not-allowed; }
        
        .btn-save { 
            background: linear-gradient(135deg, #2b7e3a, #1f5a2a);
            color: white; 
            border: none; 
            padding: 0.9rem; 
            border-radius: 50px; 
            width: 100%; 
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 700; 
            cursor: pointer; 
            margin-top: 0.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .btn-save::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }
        .btn-save:hover::before { left: 100%; }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(43,126,58,0.3); }
        
        .btn-cancel { 
            background: rgba(224,224,224,0.8);
            color: #333; 
            border: none; 
            padding: 0.8rem; 
            border-radius: 50px; 
            width: 100%; 
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 600; 
            cursor: pointer; 
            margin-top: 0.5rem; 
            text-decoration: none; 
            display: inline-block; 
            text-align: center;
            transition: all 0.3s ease;
        }
        .btn-cancel:hover { background: #ccc; transform: translateY(-2px); }
        
        .message { background: #d4edda; color: #155724; padding: 0.8rem; border-radius: 16px; margin-bottom: 1rem; border-left: 4px solid #2b7e3a; }
        .error { background: #fee2dd; color: #b45f1b; padding: 0.8rem; border-radius: 16px; margin-bottom: 1rem; border-left: 4px solid #e67e22; }
        
        .wallet-info { 
            background: rgba(234,245,230,0.8);
            backdrop-filter: blur(5px);
            padding: 0.8rem 1rem; 
            border-radius: 16px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 1.5rem; 
            flex-wrap: wrap; 
            gap: 0.5rem;
            border: 1px solid rgba(255,255,255,0.3);
        }
        .wallet-info span:first-child { font-family: 'Montserrat', sans-serif; font-weight: 600; }
        .wallet-info span:last-child { font-family: 'DM Sans', sans-serif; font-size: 1.2rem; font-weight: 700; color: #2b7e3a; }
        .wallet-info a { color: #e67e22; text-decoration: underline; font-size: 0.8rem; transition: color 0.3s; }
        .wallet-info a:hover { color: #2b7e3a; }
        
        .strength-meter { margin-top: 0.3rem; margin-bottom: 0.5rem; height: 5px; background: #e0e0e0; border-radius: 3px; overflow: hidden; }
        .strength-meter-fill { height: 100%; width: 0%; transition: width 0.2s; border-radius: 3px; }
        .strength-text { font-size: 0.7rem; text-align: right; color: #888; margin-top: 0.2rem; }
        
        /* ============================================================
           RESPONSIVE DESIGN
        ============================================================ */
        @media (max-width: 768px) { 
            body { padding: 1rem; } 
            .navbar { flex-direction: column; border-radius: 28px; }
            .nav-links { gap: 0.8rem; }
            .profile-header .avatar { width: 90px; height: 90px; }
            .user-profile { padding: 0.2rem 0.8rem 0.2rem 0.3rem; }
            .user-avatar { width: 32px; height: 32px; }
            .user-name { font-size: 0.75rem; }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- ============================================================
         NAVIGATION BAR
    ============================================================ -->
    <div class="navbar">
        <a href="dashboard.php" class="logo-area">
            <img src="../Pictures/Admin_Module/logo.png" alt="Smash Arena" onerror="this.style.display='none'">
            <div class="logo-text">Smash <span>Arena</span></div>
        </a>
        <div class="nav-links">
            <a href="dashboard.php"><i class="fas fa-home"></i> Courts</a>
            <a href="my_bookings.php"><i class="fas fa-bookmark"></i> My Bookings</a>
            <a href="../Payment_Module/wallet.php"><i class="fas fa-wallet"></i> Wallet</a>
            <a href="coaches.php"><i class="fas fa-user-tie"></i> Coaches</a>
            <!-- User profile area - click to edit profile -->
            <a href="edit_profile.php" class="user-profile">
                <div class="user-avatar">
                    <img src="<?php echo htmlspecialchars($avatarPath); ?>" alt="Avatar">
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($user['name'] ?? 'Player'); ?></div>
                    <div class="user-balance">💰 RM <?php echo number_format($real_balance, 2); ?></div>
                </div>
            </a>
            <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    
    <?php if (!$verified): ?>
        <!-- ============================================================
             VERIFY IDENTITY CARD (Show when not verified)
        ============================================================ -->
        <div class="verify-card">
            <div class="verify-header">
                <div class="icon"><i class="fas fa-shield-alt"></i></div>
                <h2>Verify Identity</h2>
                <p>Please enter your password to access your profile</p>
            </div>
            <div class="verify-body">
                <?php if ($verify_error): ?>
                    <div class="error-msg"><?php echo htmlspecialchars($verify_error); ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="verify">
                    <div class="info-group">
                        <label><i class="fas fa-lock"></i> Your Password</label>
                        <input type="password" name="verify_password" placeholder="Enter your password" required autofocus>
                    </div>
                    <button type="submit" class="btn-verify"><i class="fas fa-unlock-alt"></i> Verify & Continue</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- ============================================================
             PROFILE CARD (Show when verified)
        ============================================================ -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="avatar" onclick="openAvatarModal()">
                    <img id="profileAvatar" src="<?php echo htmlspecialchars($avatarPath); ?>" alt="">
                    <div class="edit-overlay">
                        <i class="fas fa-camera"></i> Change
                    </div>
                </div>
                <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                <p>Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
            </div>
            <div class="profile-body">
                <!-- Wallet Balance Display -->
                <div class="wallet-info">
                    <span><i class="fas fa-wallet"></i> Wallet Balance</span>
                    <span>RM <?php echo number_format($real_balance, 2); ?></span>
                    <a href="../Payment_Module/wallet.php">Top Up</a>
                </div>
                
                <!-- ============================================================
                     PERSONAL INFORMATION SECTION
                ============================================================ -->
                <div class="form-section">
                    <h3><i class="fas fa-user-edit"></i> Personal Information</h3>
                    
                    <?php if ($message): ?>
                        <div class="message"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="info-group">
                            <label><i class="fas fa-user"></i> Full Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        
                        <div class="info-group">
                            <label><i class="fas fa-envelope"></i> Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="info-group">
                            <label><i class="fas fa-phone"></i> Phone Number</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                        </div>
                        
                        <div class="info-group">
                            <label><i class="fas fa-calendar-alt"></i> Member Since</label>
                            <input type="text" value="<?php echo date('F j, Y', strtotime($user['created_at'])); ?>" class="readonly" readonly disabled>
                        </div>
                        
                        <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Changes</button>
                    </form>
                </div>
                
                <!-- ============================================================
                     CHANGE PASSWORD SECTION
                ============================================================ -->
                <div class="form-section">
                    <h3><i class="fas fa-key"></i> Change Password (Optional)</h3>
                    
                    <?php if ($password_message): ?>
                        <div class="message"><?php echo htmlspecialchars($password_message); ?></div>
                    <?php endif; ?>
                    <?php if ($password_error): ?>
                        <div class="error"><?php echo htmlspecialchars($password_error); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" onsubmit="return validatePasswordForm()">
                        <input type="hidden" name="action" value="change_password">
                        <div class="info-group">
                            <label><i class="fas fa-lock"></i> Current Password</label>
                            <input type="password" name="current_password" id="current_password" required>
                        </div>
                        
                        <div class="info-group">
                            <label><i class="fas fa-lock"></i> New Password</label>
                            <input type="password" name="new_password" id="new_password" required oninput="checkPasswordStrength()">
                            <div class="strength-meter">
                                <div class="strength-meter-fill" id="strengthFill"></div>
                            </div>
                            <div id="strengthText" class="strength-text"></div>
                        </div>
                        
                        <div class="info-group">
                            <label><i class="fas fa-lock"></i> Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" required oninput="checkPasswordMatch()">
                            <div id="matchText" class="strength-text"></div>
                        </div>
                        
                        <button type="submit" class="btn-save"><i class="fas fa-key"></i> Change Password</button>
                    </form>
                </div>
                
                <a href="dashboard.php" class="btn-cancel"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================================
     AVATAR MODAL
============================================================ -->
<div id="avatarModal" class="modal">
    <div class="modal-content">
        <h3><i class="fas fa-camera"></i> Change Profile Picture</h3>
        <div class="preview">
            <img id="previewImage" src="<?php echo htmlspecialchars($avatarPath); ?>" alt="">
        </div>

        <?php if ($avatar_message): ?>
            <div class="message"><?php echo htmlspecialchars($avatar_message); ?></div>
        <?php endif; ?>
        <?php if ($avatar_error): ?>
            <div class="error"><?php echo htmlspecialchars($avatar_error); ?></div>
        <?php endif; ?>

        <form id="avatarForm" method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_avatar">
            <input type="file" name="profile_picture" id="avatarInput" accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewImage(this)">
            <div class="modal-buttons">
                <button type="submit" class="btn-upload"><i class="fas fa-upload"></i> Upload</button>
                <?php if (!empty($profile_picture)): ?>
                    <button type="button" class="btn-delete" onclick="deleteAvatar()"><i class="fas fa-trash"></i> Remove</button>
                <?php endif; ?>
                <button type="button" class="btn-cancel-modal" onclick="closeAvatarModal()"><i class="fas fa-times"></i> Cancel</button>
            </div>
        </form>

        <form id="deleteAvatarForm" method="POST" action="" style="display:none;">
            <input type="hidden" name="action" value="delete_avatar">
        </form>
    </div>
</div>

<!-- ============================================================
     JAVASCRIPT FUNCTIONS
============================================================ -->
<script>
    // Open avatar modal
    function openAvatarModal() {
        document.getElementById('avatarModal').style.display = 'flex';
    }
    
    // Close avatar modal
    function closeAvatarModal() {
        document.getElementById('avatarModal').style.display = 'none';
        document.getElementById('avatarInput').value = '';
        var previewImage = document.getElementById('previewImage');
        var currentAvatar = document.getElementById('profileAvatar').src;
        previewImage.src = currentAvatar;
    }
    
    // Preview image before upload
    function previewImage(input) {
        var previewImage = document.getElementById('previewImage');
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Delete avatar
    function deleteAvatar() {
        if (confirm('Are you sure you want to remove your profile picture?')) {
            document.getElementById('deleteAvatarForm').submit();
        }
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        var modal = document.getElementById('avatarModal');
        if (event.target == modal) {
            closeAvatarModal();
        }
    }
    
    // Check password strength
    function checkPasswordStrength() {
        var password = document.getElementById('new_password').value;
        var strengthFill = document.getElementById('strengthFill');
        var strengthText = document.getElementById('strengthText');
        
        var score = 0;
        if (password.length >= 6) score++;
        if (password.length >= 8) score++;
        if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) score++;
        if (/[A-Z]/.test(password) && /[a-z]/.test(password)) score++;
        if (/\d/.test(password)) score++;
        
        var percent = 0;
        var text = '';
        var color = '#e67e22';
        
        if (password.length === 0) {
            percent = 0;
            text = '';
        } else if (score <= 2) {
            percent = 25;
            text = 'Weak';
            color = '#e67e22';
        } else if (score === 3) {
            percent = 50;
            text = 'Fair';
            color = '#f1c40f';
        } else if (score === 4) {
            percent = 75;
            text = 'Good';
            color = '#2b7e3a';
        } else {
            percent = 100;
            text = 'Strong';
            color = '#2b7e3a';
        }
        
        if (password.length < 6) {
            text = 'Too short (min 6)';
            percent = 25;
            color = '#e67e22';
        } else if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
            text = 'Need at least 1 symbol';
            percent = 50;
            color = '#f1c40f';
        }
        
        strengthFill.style.width = percent + '%';
        strengthFill.style.background = color;
        strengthText.innerHTML = text;
        strengthText.style.color = color;
    }
    
    // Check password match
    function checkPasswordMatch() {
        var newPassword = document.getElementById('new_password').value;
        var confirmPassword = document.getElementById('confirm_password').value;
        var matchText = document.getElementById('matchText');
        
        if (confirmPassword.length === 0) {
            matchText.innerHTML = '';
        } else if (newPassword === confirmPassword) {
            matchText.innerHTML = '✓ Passwords match';
            matchText.style.color = '#2b7e3a';
        } else {
            matchText.innerHTML = '✗ Passwords do not match';
            matchText.style.color = '#e67e22';
        }
    }
    
    // Validate password form before submission
    function validatePasswordForm() {
        var newPassword = document.getElementById('new_password').value;
        var confirmPassword = document.getElementById('confirm_password').value;
        
        if (newPassword !== confirmPassword) {
            alert('New password and confirm password do not match');
            return false;
        }
        if (newPassword.length < 6) {
            alert('Password must be at least 6 characters');
            return false;
        }
        if (!/[!@#$%^&*(),.?":{}|<>]/.test(newPassword)) {
            alert('Password must contain at least one symbol (!@#$%^&* etc.)');
            return false;
        }
        return true;
    }
</script>
</body>
</html>