<?php
// ============================================================
// coaches.php - Customer Coaches Listing Page
// Displays all active coaches with their details and booking options
// ============================================================

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/functions.php';

// Check if user is logged in, redirect to homepage if not
if (!isLoggedIn()) redirect('homepage.php');

$user_id = $_SESSION['user_id'];

// ============================================================
// FETCH USER INFORMATION
// ============================================================
$stmt = $pdo->prepare("SELECT name, profile_picture, wallet_balance FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    redirect('homepage.php');
}

// ============================================================
// GET USER AVATAR PATH (Using unified function from functions.php)
// ============================================================

// Get user avatar using unified function
$avatarPath = getUserAvatar($user_id);

$real_balance = $user['wallet_balance'] ?? 0.00;

// ============================================================
// GET SYSTEM SETTINGS FOR FOOTER DISPLAY
// ============================================================
$open_time = getSetting('open_time', '08:00');
$close_time = getSetting('close_time', '01:00');
$peak_start = getSetting('peak_start', '15:00');
$off_peak_price = getSetting('off_peak_price', '10');
$peak_price = getSetting('peak_price', '15');

// Format times for display
$open_time_display = date('h:i A', strtotime($open_time));
$close_time_display = date('h:i A', strtotime($close_time));
$peak_start_display = date('h:i A', strtotime($peak_start));

// ============================================================
// FETCH ALL ACTIVE COACHES
// ============================================================
$coaches = $pdo->query("
    SELECT c.*, a.email
    FROM coaches c
    JOIN admins a ON c.admin_id = a.id
    WHERE c.is_active = 1
    ORDER BY c.name ASC
")->fetchAll();

// Availability status mapping with colors
$avail_map = [
    'Available' => ['color' => '#16a34a', 'bg' => '#dcfce7'],
    'On Leave'  => ['color' => '#d97706', 'bg' => '#fef3c7'],
    'Sick'      => ['color' => '#dc2626', 'bg' => '#fee2e2'],
    'Off Day'   => ['color' => '#64748b', 'bg' => '#f1f5f9'],
];

// Get minimum date for booking (tomorrow)
$min_date = date('Y-m-d', strtotime('+1 day'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Coaches – Smash Arena</title>
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
        
        /* Background pattern overlay */
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
        
        /* Custom scrollbar styling */
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
            font-size: 1.5rem; 
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
            padding: 0.5rem 1.2rem;
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
           PAGE HEADER
        ============================================================ */
        .page-header {
            text-align: center;
            margin-bottom: 2rem;
            animation: fadeInUp 0.6s ease-out 0.1s both;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .page-header h1 {
            font-family: 'Montserrat', 'Poppins', sans-serif;
            font-weight: 800;
            font-size: 2rem;
            color: #1e3a2a;
            margin-bottom: 0.5rem;
        }
        
        .page-header p {
            font-family: 'DM Sans', sans-serif;
            color: #64748b;
            font-size: 1rem;
        }
        
        /* ============================================================
           COACHES GRID
        ============================================================ */
        .coaches-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.8rem;
            margin-top: 1rem;
        }
        
        .coach-card {
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(10px);
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            transition: all 0.5s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            border-bottom: 4px solid #2b7e3a;
            border: 1px solid rgba(255,255,255,0.3);
            animation: fadeInScale 0.5s ease-out both;
        }
        
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .coach-card:nth-child(1) { animation-delay: 0.05s; }
        .coach-card:nth-child(2) { animation-delay: 0.1s; }
        .coach-card:nth-child(3) { animation-delay: 0.15s; }
        
        .coach-card:hover {
            transform: translateY(-12px) scale(1.01);
            box-shadow: 0 30px 50px rgba(43,126,58,0.2);
            background: white;
            border-radius: 28px;
        }
        
        .card-hero {
            background: linear-gradient(135deg, #1a3a2a, #0f2a1a);
            padding: 28px 24px 20px;
            text-align: center;
            position: relative;
            height: 200px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .card-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
            pointer-events: none;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        
        .coach-avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #f59e0b;
            box-shadow: 0 0 0 3px rgba(245,158,11,0.2);
            margin-bottom: 12px;
            transition: transform 0.3s;
        }
        
        .coach-card:hover .coach-avatar {
            transform: scale(1.05);
        }
        
        .coach-name {
            font-family: 'Montserrat', 'Poppins', sans-serif;
            font-weight: 800;
            font-size: 1.2rem;
            color: #fff;
            margin-bottom: 8px;
        }
        
        .avail-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 14px;
            border-radius: 50px;
            font-size: 0.7rem;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
        }
        
        .card-body {
            padding: 1.3rem;
        }
        
        .coach-meta {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .meta-row {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
            color: #475569;
            font-family: 'DM Sans', sans-serif;
        }
        
        .meta-row i { 
            color: #2b7e3a; 
            width: 18px; 
            text-align: center; 
            font-size: 0.85rem;
        }
        .meta-row strong { 
            color: #1e2a2e;
            font-weight: 700;
        }
        
        .card-actions {
            display: flex;
            gap: 12px;
        }
        
        .btn-view {
            flex: 1;
            padding: 10px;
            border-radius: 60px;
            border: 1.5px solid #2b7e3a;
            background: rgba(255,255,255,0.9);
            color: #2b7e3a;
            font-size: 0.85rem;
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-view::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(43,126,58,0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .btn-view:hover::before { left: 100%; }
        .btn-view:hover {
            background: #2b7e3a;
            color: white;
            transform: translateY(-2px);
            border-radius: 60px;
        }
        
        .btn-book {
            flex: 1;
            padding: 10px;
            border-radius: 60px;
            border: none;
            background: linear-gradient(135deg, #2b7e3a, #1f5a2a);
            color: #fff;
            font-size: 0.85rem;
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.4s ease;
            box-shadow: 0 4px 12px rgba(43,126,58,0.2);
            position: relative;
            overflow: hidden;
        }
        
        .btn-book::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }
        
        .btn-book:hover::before { left: 100%; }
        .btn-book:hover { 
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(43,126,58,0.4);
            border-radius: 60px;
        }
        
        .btn-book.disabled {
            background: #e2e8f0;
            color: #94a3b8;
            cursor: not-allowed;
            pointer-events: none;
            box-shadow: none;
        }
        
        /* ============================================================
           BOOKING MODAL
        ============================================================ */
        .book-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .book-modal-content {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 32px;
            padding: 2rem;
            max-width: 450px;
            width: 90%;
            animation: slideUp 0.3s ease;
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .book-modal-header {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.3rem;
            font-weight: 700;
            color: #1e3a2a;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #eef3ea;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .book-modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #888;
            transition: 0.2s;
        }
        
        .book-modal-close:hover {
            color: #e67e22;
            transform: rotate(90deg);
        }
        
        .book-modal-body {
            margin-top: 1rem;
        }
        
        .book-form-group {
            margin-bottom: 1rem;
        }
        
        .book-form-group label {
            display: block;
            font-weight: 600;
            font-size: 0.8rem;
            color: #1e3a2a;
            margin-bottom: 0.3rem;
        }
        
        .book-form-group input, .book-form-group select {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 2px solid rgba(224,232,220,0.8);
            border-radius: 16px;
            background: rgba(254,253,248,0.9);
            font-family: 'Inter', sans-serif;
            font-size: 0.85rem;
        }
        
        .book-form-group input:focus, .book-form-group select:focus {
            outline: none;
            border-color: #2b7e3a;
        }
        
        .btn-submit-booking {
            background: linear-gradient(135deg, #2b7e3a, #1f5a2a);
            color: white;
            border: none;
            padding: 0.9rem;
            border-radius: 60px;
            width: 100%;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
        }
        
        .btn-submit-booking:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(43,126,58,0.3);
        }
        
        /* ============================================================
           FOOTER
        ============================================================ */
        .footer { 
            background: #0f1f12; 
            color: #cbd5c0; 
            padding: 3rem 5% 1.5rem; 
            margin-top: 4rem;
            margin-left: -2rem;
            margin-right: -2rem;
            margin-bottom: -2rem;
            border-radius: 32px 32px 0 0;
        }
        .footer-container { 
            max-width: 1400px; 
            margin: 0 auto; 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
            gap: 2rem; 
            margin-bottom: 2rem; 
        }
        .footer-col h3, .footer-col h4 { 
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            color: #2b7e3a; 
            margin-bottom: 1rem;
        }
        .footer-col p { 
            margin-bottom: 0.5rem; 
            display: flex; 
            align-items: center; 
            gap: 0.6rem; 
            font-size: 0.9rem; 
        }
        .footer-col a { 
            color: #cbd5c0; 
            text-decoration: none; 
            display: block; 
            margin-bottom: 0.6rem; 
            transition: 0.2s; 
            font-size: 0.9rem; 
        }
        .footer-col a:hover { 
            color: #2b7e3a; 
            padding-left: 5px; 
            transform: translateX(3px);
        }
        .social-icons { 
            display: flex; 
            gap: 1rem; 
            margin-top: 1rem; 
        }
        .social-icons a { 
            background: #2c4a2e; 
            width: 36px; 
            height: 36px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            border-radius: 50%; 
            transition: all 0.3s ease; 
            color: #cbd5c0; 
            text-decoration: none;
        }
        .social-icons a:hover { 
            background: #2b7e3a; 
            transform: translateY(-5px) rotate(360deg);
            border-radius: 50%;
        }
        .footer-bottom { 
            text-align: center; 
            border-top: 1px solid #2c4a2e; 
            padding-top: 1.5rem; 
            font-size: 0.8rem; 
        }
        
        /* ============================================================
           RESPONSIVE DESIGN
        ============================================================ */
        @media (max-width: 768px) {
            body { padding: 1rem; }
            .coaches-grid { grid-template-columns: 1fr; }
            .page-header h1 { font-size: 1.5rem; }
            .navbar { flex-direction: column; border-radius: 28px; }
            .footer-container { text-align: center; }
            .footer-col p { justify-content: center; }
            .social-icons { justify-content: center; }
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
            <a href="coaches.php" class="active"><i class="fas fa-user-tie"></i> Coaches</a>
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
    
    <!-- ============================================================
         PAGE HEADER
    ============================================================ -->
    <div class="page-header">
        <h1><i class="fas fa-user-tie" style="color:#2b7e3a;"></i> Our Coaches</h1>
        <p>Browse our professional coaches and book a training session</p>
    </div>
    
    <!-- ============================================================
         COACHES GRID
    ============================================================ -->
    <?php if (empty($coaches)): ?>
        <div class="empty-state" style="text-align:center; padding:60px; background:rgba(255,255,255,0.7); backdrop-filter:blur(10px); border-radius:28px;">
            <i class="fas fa-user-slash" style="font-size:3rem; color:#cbd5c0; margin-bottom:1rem; display:block;"></i>
            No coaches available at the moment.
        </div>
    <?php else: ?>
        <div class="coaches-grid">
            <?php foreach ($coaches as $c):
                $avail  = $c['availability_status'] ?? 'Available';
                $ac     = $avail_map[$avail] ?? $avail_map['Available'];
                $img    = !empty($c['profile_img'])
                            ? '../Pictures/Admin_Module/coaches/' . htmlspecialchars($c['profile_img'])
                            : '../Pictures/Admin_Module/coaches/default.png';
            ?>
            <div class="coach-card">
                <div class="card-hero">
                    <img src="<?php echo $img; ?>"
                         alt="<?php echo htmlspecialchars($c['name']); ?>"
                         class="coach-avatar"
                         onerror="this.src='../Pictures/Admin_Module/coaches/default.png'">
                    <div class="coach-name"><?php echo htmlspecialchars($c['name']); ?></div>
                    <span class="avail-pill" style="background:<?php echo $ac['bg']; ?>;color:<?php echo $ac['color']; ?>;">
                        ● <?php echo htmlspecialchars($avail); ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="coach-meta">
                        <?php if ($c['specialty']): ?>
                        <div class="meta-row">
                            <i class="fas fa-star"></i>
                            <span><?php echo htmlspecialchars($c['specialty']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="meta-row">
                            <i class="fas fa-tag"></i>
                            <strong>RM <?php echo number_format($c['price_per_hour'], 2); ?></strong>
                            <span>/ hour</span>
                        </div>
                        <?php if ($c['gender']): ?>
                        <div class="meta-row">
                            <i class="fas fa-venus-mars"></i>
                            <span><?php echo htmlspecialchars($c['gender']); ?></span>
                            <?php if ($c['age']): ?>
                                <span style="color:#94a3b8;">· <?php echo (int)$c['age']; ?> yrs</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-actions">
                        <a href="view_coach.php?id=<?php echo $c['id']; ?>" class="btn-view">
                            <i class="fas fa-user"></i> View Profile
                        </a>
                        <?php if ($avail === 'Available'): ?>
                            <button class="btn-book" onclick="openBookingModal(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars($c['name']); ?>', <?php echo $c['price_per_hour']; ?>)">
                                <i class="fas fa-calendar-plus"></i> Book
                            </button>
                        <?php else: ?>
                            <span class="btn-book disabled">
                                <i class="fas fa-calendar-xmark"></i> Unavailable
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================================
     BOOKING MODAL
============================================================ -->
<div id="bookingModal" class="book-modal">
    <div class="book-modal-content">
        <div class="book-modal-header">
            <span>Book Training Session</span>
            <button class="book-modal-close" onclick="closeBookingModal()">&times;</button>
        </div>
        <div class="book-modal-body">
            <form action="dashboard.php" method="GET" id="bookingForm">
                <input type="hidden" name="preferred_coach_id" id="preferred_coach_id">
                <input type="hidden" name="court_type" value="Training">
                <div class="book-form-group">
                    <label><i class="fas fa-calendar"></i> Select Date</label>
                    <input type="date" name="booking_date" id="booking_date" min="<?php echo $min_date; ?>" required>
                </div>
                <div class="book-form-group">
                    <label><i class="fas fa-clock"></i> Duration</label>
                    <select name="duration" id="duration" required>
                        <option value="">Select duration</option>
                        <option value="1">1 hour</option>
                        <option value="2">2 hours</option>
                        <option value="3">3 hours</option>
                        <option value="4">4 hours</option>
                    </select>
                </div>
                <div class="wallet-info-sidebar" style="background: rgba(234,245,230,0.6); border-radius: 16px; padding: 0.8rem; margin: 1rem 0; text-align: center;">
                    <i class="fas fa-wallet"></i> Your Balance: <strong>RM <?php echo number_format($real_balance, 2); ?></strong>
                </div>
                <button type="submit" class="btn-submit-booking">
                    <i class="fas fa-calendar-plus"></i> Choose a Training Court
                </button>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================
     FOOTER
============================================================ -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-col">
            <h3>Smash Arena</h3>
            <p><i class="fas fa-map-marker-alt"></i> 123 Jalan Badminton, Kuala Lumpur</p>
            <p><i class="fas fa-phone-alt"></i> +603-1234 5678</p>
            <p><i class="fas fa-envelope"></i> smasharenabadminton@gmail.com</p>
            <div class="social-icons">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-whatsapp"></i></a>
            </div>
        </div>
        <div class="footer-col">
            <h4>Quick Links</h4>
            <a href="dashboard.php">Find a Court</a>
            <a href="my_bookings.php">My Bookings</a>
            <a href="../Payment_Module/wallet.php">Wallet</a>
        </div>
        <div class="footer-col">
            <h4>Support</h4>
            <a href="faq.php">FAQs</a>
            <a href="cancellation_policy.php">Cancellation Policy</a>
            <a href="privacy_policy.php">Privacy Policy</a>
            <a href="terms_of_use.php">Terms of Use</a>
            <a href="contact_us.php">Contact Us</a>
        </div>
        <div class="footer-col">
            <h4>Operating Hours</h4>
            <p><i class="fas fa-clock"></i> Monday - Sunday: <?php echo $open_time_display; ?> - <?php echo $close_time_display; ?></p>
            <p><i class="fas fa-tag"></i> <?php echo $open_time_display; ?> - <?php echo $peak_start_display; ?>: RM <?php echo $off_peak_price; ?>/hour</p>
            <p><i class="fas fa-tag"></i> <?php echo $peak_start_display; ?> - <?php echo $close_time_display; ?>: RM <?php echo $peak_price; ?>/hour</p>
            <p><i class="fas fa-calendar-alt"></i> Open daily including public holidays</p>
        </div>
    </div>
    <div class="footer-bottom"><p>&copy; 2025 Smash Arena – Your Game, Our Court. All rights reserved.</p></div>
</footer>

<!-- ============================================================
     JAVASCRIPT FUNCTIONS
============================================================ -->
<script>
    let currentCoachId = null;
    
    // Open booking modal
    function openBookingModal(coachId, coachName, coachPrice) {
        currentCoachId = coachId;
        document.getElementById('preferred_coach_id').value = coachId;
        document.getElementById('booking_date').value = '';
        document.getElementById('duration').value = '';
        document.getElementById('bookingModal').style.display = 'flex';
    }
    
    // Close booking modal
    function closeBookingModal() {
        document.getElementById('bookingModal').style.display = 'none';
    }
    
    // Close modal when clicking outside
    document.getElementById('bookingModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeBookingModal();
        }
    });
    
    // Validate booking form before submission
    document.getElementById('bookingForm').addEventListener('submit', function(e) {
        const date = document.getElementById('booking_date').value;
        const duration = document.getElementById('duration').value;
        
        if (!date) {
            e.preventDefault();
            alert('Please select a date');
            return false;
        }
        
        if (!duration) {
            e.preventDefault();
            alert('Please select duration');
            return false;
        }
        
        // Validate date must be at least tomorrow
        const selectedDate = new Date(date);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (selectedDate <= today) {
            e.preventDefault();
            alert('Please select a date from tomorrow onwards');
            return false;
        }
        
        return true;
    });
</script>
</body>
</html>