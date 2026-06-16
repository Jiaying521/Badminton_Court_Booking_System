<?php
// ============================================================
// view_coach.php - Customer Coach Profile Page
// Displays detailed coach information, ratings, and booking options
// ============================================================

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/functions.php';

// Check if user is logged in, redirect to homepage if not
if (!isLoggedIn()) redirect('homepage.php');

$coach_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($coach_id <= 0) {
    redirect('coaches.php');
}

// ============================================================
// GET CURRENT USER INFORMATION
// ============================================================
$user_id = $_SESSION['user_id'];
$userStmt = $pdo->prepare("SELECT name, profile_picture, wallet_balance, loyalty_points FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch();

// ============================================================
// GET USER AVATAR PATH (Using unified function from functions.php)
// ============================================================

// Get user avatar using unified function
$avatarPath = getUserAvatar($user_id);

$real_balance = $user['wallet_balance'] ?? 0.00;
$currentPointsBalance = (int)($user['loyalty_points'] ?? 0);

// ============================================================
// FETCH COACH DETAILS
// ============================================================
$stmt = $pdo->prepare("
    SELECT c.*, a.email
    FROM coaches c
    JOIN admins a ON c.admin_id = a.id
    WHERE c.id = ? AND c.is_active = 1
    LIMIT 1
");
$stmt->execute([$coach_id]);
$coach = $stmt->fetch();

if (!$coach) {
    redirect('coaches.php');
}

// ============================================================
// GET COACH IMAGE
// ============================================================
$profile_img = !empty($coach['profile_img'])
    ? '../Pictures/Admin_Module/coaches/' . htmlspecialchars($coach['profile_img'])
    : '../Pictures/Admin_Module/coaches/default.png';

// ============================================================
// AVAILABILITY STATUS MAPPING
// ============================================================
$avail = $coach['availability_status'] ?? 'Available';
$avail_map = [
    'Available' => ['color' => '#16a34a', 'bg' => '#dcfce7', 'icon' => '●', 'text' => 'Available for booking'],
    'On Leave'  => ['color' => '#d97706', 'bg' => '#fef3c7', 'icon' => '●', 'text' => 'Currently on leave'],
    'Sick'      => ['color' => '#dc2626', 'bg' => '#fee2e2', 'icon' => '●', 'text' => 'Not available (sick)'],
    'Off Day'   => ['color' => '#64748b', 'bg' => '#f1f5f9', 'icon' => '●', 'text' => 'Off day'],
];
$ac = $avail_map[$avail] ?? $avail_map['Available'];

// ============================================================
// GET COACH STATISTICS
// ============================================================

// Get coach's completed sessions count
$stmt_bookings = $pdo->prepare("
    SELECT COUNT(*) as total_bookings 
    FROM bookings 
    WHERE coach_id = ? AND status IN ('Confirmed', 'Completed')
");
$stmt_bookings->execute([$coach_id]);
$total_sessions = $stmt_bookings->fetchColumn() ?? 0;

// ============================================================
// GET COACH RATINGS
// ============================================================
$rating = 4.9;
$total_reviews = 12;

try {
    $checkTable = $pdo->query("SHOW TABLES LIKE 'coach_ratings'");
    if ($checkTable && $checkTable->rowCount() > 0) {
        $stmt_rating = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM coach_ratings WHERE coach_id = ?");
        $stmt_rating->execute([$coach_id]);
        $rating_data = $stmt_rating->fetch();
        if ($rating_data && $rating_data['avg_rating']) {
            $rating = round($rating_data['avg_rating'], 1);
            $total_reviews = $rating_data['total'];
        }
    }
} catch(PDOException $e) {
    // Table doesn't exist, use default values
}

// ============================================================
// GET SYSTEM SETTINGS FOR FOOTER DISPLAY
// ============================================================
$open_time = getSetting('open_time', '08:00');
$close_time = getSetting('close_time', '01:00');
$peak_start = getSetting('peak_start', '15:00');
$off_peak_price = getSetting('off_peak_price', '10');
$peak_price = getSetting('peak_price', '15');
$open_time_display = date('h:i A', strtotime($open_time));
$close_time_display = date('h:i A', strtotime($close_time));
$peak_start_display = date('h:i A', strtotime($peak_start));

// Calculate years of experience
$experience_years = date('Y') - 2018;

// Get min date for booking (tomorrow)
$min_date = date('Y-m-d', strtotime('+1 day'));

// ============================================================
// COACH FIELDS WITH FALLBACK VALUES
// ============================================================
$coach_description = isset($coach['description']) && !empty($coach['description']) 
    ? $coach['description'] 
    : 'Professional badminton coach dedicated to helping players of all levels improve their game. Specializing in technique, footwork, and match strategy.';

$coach_specialty = isset($coach['specialty']) && !empty($coach['specialty']) 
    ? $coach['specialty'] 
    : 'All-round Coaching';

$coach_phone = isset($coach['phone']) && !empty($coach['phone']) 
    ? $coach['phone'] 
    : 'Contact via support';

$coach_email = isset($coach['email']) && !empty($coach['email']) 
    ? $coach['email'] 
    : 'Not available';

$coach_gender = isset($coach['gender']) ? $coach['gender'] : '';
$coach_age = isset($coach['age']) ? (int)$coach['age'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($coach['name']); ?> – Coach Profile | Smash Arena</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Reset and base styles */
        * { margin:0; padding:0; box-sizing:border-box; }
        
        body { 
            font-family: 'Inter', 'Poppins', 'Montserrat', sans-serif; 
            background: radial-gradient(circle at 10% 20%, rgba(240,245,236,1) 0%, rgba(226,236,217,1) 100%);
            color: #1e2a2e; 
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
            padding: 2rem;
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
            display: inline-block;
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
        .nav-links a.user-profile,
        .user-profile {
            display: flex !important;
            align-items: center;
            flex-wrap: nowrap;
            gap: 0.6rem;
            cursor: pointer;
            background: rgba(234,245,230,0.6);
            padding: 0.2rem 0.8rem 0.2rem 0.3rem;
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
            width: 32px;
            height: 32px;
            min-width: 32px;
            border-radius: 50%;
            overflow: hidden;
            background: #2b7e3a;
            flex-shrink: 0;
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
            font-weight: 600;
            font-size: 0.75rem;
            color: #1e3a2a;
            white-space: nowrap;
        }
        .user-balance {
            font-family: 'DM Sans', sans-serif;
            font-size: 0.65rem;
            color: #2b7e3a;
            font-weight: 500;
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
           MAIN CONTENT - 2 Column Layout
        ============================================================ */
        .main-content {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 2rem;
        }
        
        /* ============================================================
           PROFILE CARD - Left Column
        ============================================================ */
        .profile-card {
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(10px);
            border-radius: 32px;
            overflow: hidden;
            box-shadow: 0 12px 28px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.3);
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .coach-hero {
            background: linear-gradient(135deg, #1a3a2a, #0f2a1a);
            padding: 2rem;
            text-align: center;
            position: relative;
        }
        
        .coach-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #f59e0b;
            box-shadow: 0 0 0 4px rgba(245,158,11,0.2);
            margin-bottom: 1rem;
        }
        
        .coach-name {
            font-family: 'Montserrat', 'Poppins', sans-serif;
            font-size: 1.8rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 0.25rem;
        }
        
        .coach-title {
            color: rgba(255,255,255,0.8);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .availability-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            background: <?php echo $ac['bg']; ?>;
            color: <?php echo $ac['color']; ?>;
        }
        
        .coach-body {
            padding: 1.5rem;
        }
        
        .section-title {
            font-family: 'Montserrat', 'Poppins', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e3a2a;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid rgba(234,245,230,0.8);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .section-title i {
            color: #2b7e3a;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .info-item {
            background: rgba(248,250,245,0.8);
            border-radius: 20px;
            padding: 1rem;
            border: 1px solid rgba(224,232,220,0.8);
        }
        
        .info-item .label {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
            margin-bottom: 0.3rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .info-item .label i {
            color: #2b7e3a;
            font-size: 0.7rem;
        }
        
        .info-item .value {
            font-size: 1rem;
            font-weight: 700;
            color: #1e2a2e;
        }
        
        .info-item.highlight .value {
            color: #2b7e3a;
            font-size: 1.3rem;
        }
        
        .bio-text {
            background: rgba(248,250,245,0.6);
            border-radius: 20px;
            padding: 1rem;
            margin-top: 1rem;
            line-height: 1.6;
            color: #5a6e5c;
            font-size: 0.85rem;
        }
        
        .stats-row {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .stat-badge {
            flex: 1;
            background: rgba(234,245,230,0.8);
            border-radius: 16px;
            padding: 0.8rem;
            text-align: center;
        }
        
        .stat-badge .number {
            font-size: 1.2rem;
            font-weight: 800;
            color: #2b7e3a;
        }
        
        .stat-badge .label {
            font-size: 0.65rem;
            color: #5a6e5c;
        }
        
        /* ============================================================
           BOOKING SIDEBAR - Right Column
        ============================================================ */
        .booking-sidebar {
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(10px);
            border-radius: 32px;
            padding: 1.5rem;
            border: 1px solid rgba(255,255,255,0.3);
            position: sticky;
            top: 2rem;
            animation: fadeInUp 0.6s ease-out 0.1s both;
        }
        
        .sidebar-title {
            font-family: 'Montserrat', 'Poppins', sans-serif;
            font-size: 1.2rem;
            font-weight: 700;
            color: #1e3a2a;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .price-display {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .price-amount {
            font-size: 2rem;
            font-weight: 800;
            color: #2b7e3a;
        }
        
        .price-unit {
            font-size: 0.85rem;
            color: #5a6e5c;
        }
        
        .booking-form {
            margin-top: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 0.8rem;
            color: #1e3a2a;
            margin-bottom: 0.3rem;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 2px solid rgba(224,232,220,0.8);
            border-radius: 16px;
            background: rgba(254,253,248,0.9);
            font-family: 'Inter', sans-serif;
            font-size: 0.85rem;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #2b7e3a;
            box-shadow: 0 0 0 3px rgba(43,126,58,0.1);
        }
        
        .btn-book-now {
            background: linear-gradient(135deg, #2b7e3a, #1f5a2a);
            color: white;
            border: none;
            padding: 0.9rem;
            border-radius: 60px;
            width: 100%;
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-book-now:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(43,126,58,0.3);
        }
        
        .btn-book-now.disabled {
            background: #e2e8f0;
            color: #94a3b8;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        .note-text {
            font-size: 0.7rem;
            color: #94a3b8;
            text-align: center;
            margin-top: 1rem;
        }
        
        .wallet-info-sidebar {
            background: rgba(234,245,230,0.6);
            border-radius: 16px;
            padding: 0.8rem;
            margin-top: 1rem;
            text-align: center;
        }
        
        .info-banner {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border-radius: 16px;
            padding: 0.8rem;
            margin-bottom: 1rem;
            text-align: center;
            font-size: 0.75rem;
            color: #92400e;
        }
        
        .info-banner i {
            margin-right: 0.3rem;
        }
        
        /* ============================================================
           FOOTER
        ============================================================ */
        .footer { 
            background: #0f1f12; 
            color: #cbd5c0; 
            padding: 2rem 5% 1rem; 
            margin-top: 3rem;
            border-radius: 32px 32px 0 0;
        }
        .footer-container { 
            max-width: 1400px; 
            margin: 0 auto; 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 1.5rem; 
            margin-bottom: 1.5rem; 
        }
        .footer-col h3, .footer-col h4 { 
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            color: #2b7e3a; 
            margin-bottom: 0.8rem;
            font-size: 1rem;
        }
        .footer-col p { 
            margin-bottom: 0.4rem; 
            display: flex; 
            align-items: center; 
            gap: 0.5rem; 
            font-size: 0.8rem; 
        }
        .footer-col a { 
            color: #cbd5c0; 
            text-decoration: none; 
            display: block; 
            margin-bottom: 0.5rem; 
            transition: 0.2s; 
            font-size: 0.8rem; 
        }
        .footer-col a:hover { 
            color: #2b7e3a; 
            padding-left: 5px; 
            transform: translateX(3px);
        }
        .social-icons { 
            display: flex; 
            gap: 0.8rem; 
            margin-top: 0.8rem; 
        }
        .social-icons a { 
            background: #2c4a2e; 
            width: 32px; 
            height: 32px; 
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
            transform: translateY(-4px) rotate(360deg);
            border-radius: 50%;
        }
        .footer-bottom { 
            text-align: center; 
            border-top: 1px solid #2c4a2e; 
            padding-top: 1rem; 
            font-size: 0.7rem; 
        }
        
        /* ============================================================
           RESPONSIVE DESIGN
        ============================================================ */
        @media (max-width: 968px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            .booking-sidebar {
                position: static;
            }
        }
        
        @media (max-width: 768px) {
            .container { padding: 1rem; }
            .navbar { flex-direction: column; border-radius: 28px; }
            .info-grid { grid-template-columns: 1fr; }
            .coach-name { font-size: 1.4rem; }
            .coach-avatar { width: 90px; height: 90px; }
            .footer-container { text-align: center; }
            .footer-col p { justify-content: center; }
            .social-icons { justify-content: center; }
            .user-profile { padding: 0.2rem 0.8rem 0.2rem 0.3rem; }
            .user-avatar { width: 28px; height: 28px; }
            .user-name { font-size: 0.7rem; }
            .user-balance { font-size: 0.6rem; }
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
         MAIN CONTENT
    ============================================================ -->
    <div class="main-content">
        <!-- ============================================================
             LEFT COLUMN - COACH PROFILE
        ============================================================ -->
        <div class="profile-card">
            <div class="coach-hero">
                <img src="<?php echo $profile_img; ?>" alt="<?php echo htmlspecialchars($coach['name']); ?>" class="coach-avatar"
                     onerror="this.src='../Pictures/Admin_Module/coaches/default.png'">
                <div class="coach-name"><?php echo htmlspecialchars($coach['name']); ?></div>
                <div class="coach-title">
                    <i class="fas fa-whistle"></i> Professional Badminton Coach
                </div>
                <div class="availability-badge">
                    <?php echo $ac['icon']; ?> <?php echo htmlspecialchars($avail); ?> – <?php echo $ac['text']; ?>
                </div>
            </div>
            
            <div class="coach-body">
                <!-- About Section -->
                <div class="section-title">
                    <i class="fas fa-user-circle"></i> About the Coach
                </div>
                
                <!-- Coach Information Grid -->
                <div class="info-grid">
                    <div class="info-item highlight">
                        <div class="label"><i class="fas fa-tag"></i> Rate</div>
                        <div class="value">RM <?php echo number_format($coach['price_per_hour'], 2); ?> <span style="font-size:0.8rem;">/ hour</span></div>
                    </div>
                    <div class="info-item">
                        <div class="label"><i class="fas fa-star"></i> Specialty</div>
                        <div class="value"><?php echo htmlspecialchars($coach_specialty); ?></div>
                    </div>
                    <?php if ($coach_gender): ?>
                    <div class="info-item">
                        <div class="label"><i class="fas fa-venus-mars"></i> Gender</div>
                        <div class="value"><?php echo htmlspecialchars($coach_gender); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($coach_age > 0): ?>
                    <div class="info-item">
                        <div class="label"><i class="fas fa-cake-candles"></i> Age</div>
                        <div class="value"><?php echo $coach_age; ?> years</div>
                    </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <div class="label"><i class="fas fa-envelope"></i> Email</div>
                        <div class="value"><?php echo htmlspecialchars($coach_email); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="label"><i class="fas fa-phone"></i> Contact</div>
                        <div class="value"><?php echo htmlspecialchars($coach_phone); ?></div>
                    </div>
                </div>
                
                <!-- Statistics Badges -->
                <div class="stats-row">
                    <div class="stat-badge">
                        <div class="number"><?php echo $total_sessions; ?>+</div>
                        <div class="label">Sessions Completed</div>
                    </div>
                    <div class="stat-badge">
                        <div class="number"><?php echo $rating; ?> ★</div>
                        <div class="label">Rating (<?php echo $total_reviews; ?> reviews)</div>
                    </div>
                    <div class="stat-badge">
                        <div class="number"><?php echo $experience_years; ?></div>
                        <div class="label">Years Experience</div>
                    </div>
                </div>
                
                <!-- Coach Description -->
                <div class="bio-text">
                    <i class="fas fa-quote-left" style="color:#2b7e3a; margin-right:0.5rem;"></i>
                    <?php echo htmlspecialchars($coach_description); ?>
                </div>
            </div>
        </div>
        
        <!-- ============================================================
             RIGHT COLUMN - BOOKING SIDEBAR
        ============================================================ -->
        <div class="booking-sidebar">
            <div class="sidebar-title">
                <i class="fas fa-calendar-alt" style="color:#2b7e3a;"></i> Book a Session
            </div>
            
            <!-- Price Display -->
            <div class="price-display">
                <span class="price-amount">RM <?php echo number_format($coach['price_per_hour'], 2); ?></span>
                <span class="price-unit">/ hour</span>
            </div>
            
            <!-- Info Banner -->
            <div class="info-banner">
                <i class="fas fa-info-circle"></i> Coach sessions require a Training Court booking
            </div>
            
            <?php if ($avail === 'Available'): ?>
                <!-- Booking Form -->
                <form action="dashboard.php" method="GET" class="booking-form" id="bookingForm">
                    <input type="hidden" name="preferred_coach_id" value="<?php echo $coach_id; ?>">
                    <input type="hidden" name="court_type" value="Training">
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Select Date</label>
                        <input type="date" name="booking_date" id="booking_date" min="<?php echo $min_date; ?>" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Duration</label>
                        <select name="duration" id="duration" required>
                            <option value="">Select duration</option>
                            <option value="1">1 hour</option>
                            <option value="2">2 hours</option>
                            <option value="3">3 hours</option>
                            <option value="4">4 hours</option>
                        </select>
                    </div>
                    <!-- Wallet Balance Display -->
                    <div class="wallet-info-sidebar">
                        <i class="fas fa-wallet"></i> Your Balance: <strong>RM <?php echo number_format($real_balance, 2); ?></strong>
                    </div>
                    <!-- Submit Button -->
                    <button type="submit" class="btn-book-now" id="bookNowBtn">
                        <i class="fas fa-calendar-plus"></i> Choose a Training Court
                    </button>
                    <!-- Note -->
                    <div class="note-text">
                        <i class="fas fa-info-circle"></i> You will be shown only Training Courts to book with this coach
                    </div>
                </form>
            <?php else: ?>
                <!-- Unavailable State -->
                <button class="btn-book-now disabled" disabled>
                    <i class="fas fa-calendar-xmark"></i> Not Available
                </button>
                <div class="note-text">
                    This coach is currently <?php echo strtolower($avail); ?>. Please check back later or choose another coach.
                </div>
            <?php endif; ?>
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
            <p><i class="fas fa-map-marker-alt"></i> 123 Jalan Badminton, KL</p>
            <p><i class="fas fa-phone-alt"></i> +603-1234 5678</p>
            <p><i class="fas fa-envelope"></i> support@smasharena.com</p>
            <div class="social-icons">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
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
            <p><i class="fas fa-clock"></i> Mon - Sun: <?php echo $open_time_display; ?> - <?php echo $close_time_display; ?></p>
            <p><i class="fas fa-tag"></i> <?php echo $open_time_display; ?> - <?php echo $peak_start_display; ?>: RM <?php echo $off_peak_price; ?>/hour</p>
            <p><i class="fas fa-tag"></i> <?php echo $peak_start_display; ?> - <?php echo $close_time_display; ?>: RM <?php echo $peak_price; ?>/hour</p>
            <p><i class="fas fa-calendar-alt"></i> Open daily including holidays</p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; 2025 Smash Arena – Your Game, Our Court.</p>
    </div>
</footer>

<!-- ============================================================
     JAVASCRIPT FUNCTIONS
============================================================ -->
<script>
    // ============================================================
    // BOOKING FORM VALIDATION
    // ============================================================
    document.getElementById('bookingForm')?.addEventListener('submit', function(e) {
        // Get form values
        const date = document.getElementById('booking_date')?.value;
        const duration = document.getElementById('duration')?.value;
        
        // Validate date is selected
        if (!date) {
            e.preventDefault();
            alert('Please select a date');
            return false;
        }
        
        // Validate duration is selected
        if (!duration) {
            e.preventDefault();
            alert('Please select duration');
            return false;
        }
        
        // Validate date must be at least tomorrow (not today)
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