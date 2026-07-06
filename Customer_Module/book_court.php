<?php
// ============================================================
// book_court.php - Court Booking Page
// Allows users to select date, time, duration, and coach for a court booking
// ============================================================

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/functions.php';

// Check if user is logged in, redirect to homepage if not
if(!isLoggedIn()) redirect('homepage.php');

$court_id = $_GET['court_id'] ?? 0;
if(!$court_id) redirect('dashboard.php');

// ============================================================
// FETCH COURT INFORMATION
// ============================================================
$stmt = $pdo->prepare("SELECT * FROM courts WHERE id = ?");
$stmt->execute([$court_id]);
$court = $stmt->fetch();
if(!$court) redirect('dashboard.php');

// ============================================================
// GET PREFILLED PARAMETERS (from view_coach.php or other pages)
// ============================================================
$preferred_coach_id = isset($_GET['preferred_coach_id']) ? (int)$_GET['preferred_coach_id'] : 0;
$preferred_duration = isset($_GET['duration']) ? (int)$_GET['duration'] : 0;
$preferred_date = isset($_GET['booking_date']) ? $_GET['booking_date'] : '';

// ============================================================
// HELPER FUNCTION: GET COURT IMAGE
// ============================================================
function getCourtImage($court) {
    $possibleFields = ['court_image', 'photo', 'image', 'photo_path'];
    $imageField = null;
    
    foreach ($possibleFields as $field) {
        if (isset($court[$field]) && !empty($court[$field])) {
            $imageField = $court[$field];
            break;
        }
    }
    
    if (!empty($imageField)) {
        $imagePath = $imageField;
        if (strpos($imagePath, 'http') === 0) return $imagePath;
        if (strpos($imagePath, '/') === 0) return $imagePath;
        if (strpos($imagePath, '../') === 0) return $imagePath;
        
        $possiblePaths = [
            '../Pictures/Admin_Module/courts/' . $imagePath,
            '../Pictures/Customer_Module/court/' . $imagePath,
            '../uploads/courts/' . $imagePath,
        ];
        
        foreach ($possiblePaths as $path) {
            if (file_exists(__DIR__ . '/' . $path)) return $path;
        }
        return '../Pictures/Admin_Module/courts/' . $imagePath;
    }
    
    $courtName = $court['court_name'];
    $possibleImageNames = [
        strtolower(str_replace(' ', '_', $courtName)) . '.png',
        strtolower(str_replace(' ', '_', $courtName)) . '.jpg',
        'court_' . strtolower(str_replace(' ', '_', $courtName)) . '.png',
    ];
    
    $imagePaths = [
        '../Pictures/Admin_Module/courts/',
        '../Pictures/Customer_Module/court/',
    ];
    
    foreach ($imagePaths as $basePath) {
        foreach ($possibleImageNames as $imgName) {
            if (file_exists(__DIR__ . '/' . $basePath . $imgName)) return $basePath . $imgName;
        }
    }
    return null;
}

// ============================================================
// HELPER FUNCTION: GET ALL COURT IMAGES FOR GALLERY
// ============================================================
function getAllCourtImages($court) {
    $images = [];
    $mainImage = getCourtImage($court);
    if ($mainImage) $images[] = $mainImage;
    
    $courtName = $court['court_name'];
    $baseName = strtolower(str_replace(' ', '_', $courtName));
    $imagePaths = ['../Pictures/Admin_Module/courts/', '../Pictures/Customer_Module/court/'];
    
    for ($i = 1; $i <= 5; $i++) {
        $variations = [$baseName . '_' . $i . '.jpg', $baseName . '_' . $i . '.png', $baseName . $i . '.jpg', $baseName . $i . '.png'];
        foreach ($imagePaths as $basePath) {
            foreach ($variations as $imgName) {
                if (file_exists(__DIR__ . '/' . $basePath . $imgName) && !in_array($basePath . $imgName, $images)) {
                    $images[] = $basePath . $imgName;
                    break 2;
                }
            }
        }
    }
    return $images;
}

// ============================================================
// FETCH COACHES FOR TRAINING COURTS
// ============================================================
$coaches = [];
if($court['court_type'] == 'Training') {
    $coachStmt = $pdo->query("SELECT * FROM coaches WHERE is_active = 1 ORDER BY price_per_hour");
    $coaches = $coachStmt->fetchAll();
}

// ============================================================
// HELPER FUNCTION: GET COACH IMAGE
// ============================================================
function getCoachImage($coach) {
    $basePath = '../Pictures/Admin_Module/coaches/';
    if (!empty($coach['profile_img'])) {
        $photoPath = $coach['profile_img'];
        if (strpos($photoPath, 'http') === 0) return $photoPath;
        if (strpos($photoPath, '/') === 0) return $photoPath;
        if (strpos($photoPath, '../') === 0) return $photoPath;
        if (strpos($photoPath, 'Admin_Module/') === 0) return '../' . $photoPath;
        if (file_exists($basePath . $photoPath)) return $basePath . $photoPath;
    }
    return '../Pictures/Admin_Module/coaches/default.png';
}

// ============================================================
// GET COURT PHOTOS FOR GALLERY
// ============================================================
$court_photos = getAllCourtImages($court);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Book <?=htmlspecialchars($court['court_name'])?> | Smash Arena</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        
        body { 
            font-family: 'Inter', 'Poppins', 'Montserrat', sans-serif; 
            background: radial-gradient(circle at 10% 20%, rgba(240,245,236,1) 0%, rgba(226,236,217,1) 100%);
            color: #1e2a2e; 
            padding: 2rem;
            min-height: 100vh;
            position: relative;
        }
        
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
        
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #e0e8dc; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #2b7e3a; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #1f5a2a; }
        
        .progress-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(10px);
            padding: 0.8rem 2rem;
            border-radius: 80px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            border: 1px solid rgba(255,255,255,0.3);
            animation: fadeInDown 0.6s ease-out;
        }
        
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .progress-step {
            text-align: center;
            flex: 1;
            position: relative;
        }
        .progress-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 15px;
            right: -50%;
            width: 100%;
            height: 2px;
            background: #e0e8dc;
            z-index: 0;
        }
        .progress-step.completed:not(:last-child)::after {
            background: #2b7e3a;
        }
        .progress-step .step-number {
            width: 36px;
            height: 36px;
            background: #e0e8dc;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.4rem;
            font-weight: 700;
            font-size: 0.9rem;
            position: relative;
            z-index: 1;
            transition: 0.3s;
            color: #5a6e5c;
        }
        .progress-step.active .step-number {
            background: #2b7e3a;
            color: white;
            box-shadow: 0 0 0 4px rgba(43,126,58,0.2);
            animation: pulseStep 2s ease-in-out infinite;
        }
        @keyframes pulseStep {
            0%, 100% { box-shadow: 0 0 0 0px rgba(43,126,58,0.4); }
            50% { box-shadow: 0 0 0 6px rgba(43,126,58,0.1); }
        }
        .progress-step.completed .step-number {
            background: #2b7e3a;
            color: white;
        }
        .progress-step .step-label {
            font-size: 0.75rem;
            color: #888;
            font-weight: 500;
        }
        .progress-step.active .step-label {
            color: #2b7e3a;
            font-weight: 700;
        }
        .progress-step.completed .step-label {
            color: #2b7e3a;
        }
        
        .booking-summary { 
            background: linear-gradient(135deg, rgba(43,126,58,0.9), rgba(27,94,42,0.9));
            backdrop-filter: blur(5px);
            color: white; 
            padding: 1rem 1.8rem; 
            border-radius: 28px; 
            margin-bottom: 2rem; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap; 
            gap: 1rem;
            animation: fadeInUp 0.6s ease-out 0.1s both;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .row-2cols { 
            display: grid; 
            grid-template-columns: 2fr 1fr; 
            gap: 2rem; 
        }
        
        .product-section { 
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(10px);
            border-radius: 28px; 
            padding: 1.8rem; 
            margin-bottom: 2rem;
            border: 1px solid rgba(255,255,255,0.3);
            transition: all 0.3s;
            animation: fadeInScale 0.5s ease-out both;
        }
        .product-section:hover { background: rgba(255,255,255,0.8); }
        
        .section-title { 
            font-family: 'Montserrat', 'Poppins', sans-serif;
            font-size: 1.3rem; 
            font-weight: 700; 
            color: #2b7e3a; 
            margin-bottom: 1.2rem; 
            padding-bottom: 0.7rem; 
            border-bottom: 2px solid rgba(234,245,230,0.8);
            display: flex; 
            align-items: center; 
            gap: 0.6rem;
        }
        
        .info-grid {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }
        .info-row {
            display: flex;
            gap: 1rem;
            font-size: 0.9rem;
        }
        .info-row .label {
            width: 90px;
            font-weight: 600;
            color: #5a6e5c;
        }
        .info-row .value {
            flex: 1;
            color: #1e2a2e;
        }
        .price-tags {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
        }
        .price-tag {
            font-size: 0.8rem;
            padding: 0.3rem 1rem;
            border-radius: 40px;
            font-weight: 500;
        }
        .price-offpeak {
            background: rgba(43,126,58,0.15);
            color: #2b7e3a;
        }
        .price-peak {
            background: rgba(230,126,34,0.1);
            color: #e67e22;
        }
        
        .photos-scroll {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
        }
        .photos-scroll::-webkit-scrollbar {
            height: 4px;
        }
        .photo-card {
            flex-shrink: 0;
            width: 140px;
            height: 140px;
            border-radius: 20px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            background: #eef3ea;
        }
        .photo-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .photo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .photo-card.active {
            border-color: #2b7e3a;
            box-shadow: 0 0 0 3px rgba(43,126,58,0.3);
        }
        .no-photos {
            text-align: center;
            padding: 2rem;
            color: #aaa;
        }
        
        .lightbox {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .lightbox.active {
            display: flex;
            opacity: 1;
        }
        
        .lightbox-content {
            max-width: 90%;
            max-height: 85vh;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            transform: scale(0.95);
            transition: transform 0.3s ease;
            object-fit: contain;
        }
        
        .lightbox.active .lightbox-content {
            transform: scale(1);
        }
        
        .lightbox-close {
            position: fixed;
            top: 25px;
            right: 35px;
            color: white;
            font-size: 40px;
            font-weight: 300;
            cursor: pointer;
            z-index: 10000;
            background: none;
            border: none;
            transition: transform 0.3s ease;
        }
        
        .lightbox-close:hover {
            transform: rotate(90deg);
        }
        
        .lightbox-caption {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
            font-weight: 500;
            background: rgba(0, 0, 0, 0.5);
            padding: 8px 20px;
            border-radius: 30px;
            backdrop-filter: blur(5px);
            z-index: 10000;
            text-align: center;
        }
        
        .lightbox-nav {
            position: fixed;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-size: 45px;
            cursor: pointer;
            z-index: 10000;
            background: rgba(0, 0, 0, 0.3);
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }
        
        .lightbox-nav:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-50%) scale(1.1);
        }
        
        .lightbox-nav.prev {
            left: 25px;
        }
        
        .lightbox-nav.next {
            right: 25px;
        }
        
        .lightbox-counter {
            position: fixed;
            top: 25px;
            left: 35px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 16px;
            font-weight: 500;
            z-index: 10000;
            background: rgba(0, 0, 0, 0.3);
            padding: 6px 16px;
            border-radius: 20px;
            backdrop-filter: blur(5px);
        }
        
        @media (max-width: 768px) {
            .lightbox-nav {
                width: 40px;
                height: 40px;
                font-size: 20px;
            }
            .lightbox-nav.prev { left: 10px; }
            .lightbox-nav.next { right: 10px; }
            .lightbox-close { top: 15px; right: 20px; font-size: 30px; }
            .lightbox-counter { top: 15px; left: 20px; font-size: 12px; }
            .lightbox-caption { font-size: 12px; padding: 6px 16px; bottom: 20px; }
        }
        
        #datepicker {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 2px solid rgba(224,232,220,0.8);
            border-radius: 20px;
            background: rgba(254,253,248,0.9);
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            transition: 0.2s;
            cursor: pointer;
        }
        #datepicker:focus {
            outline: none;
            border-color: #2b7e3a;
            box-shadow: 0 0 0 3px rgba(43,126,58,0.1);
        }
        
        .time-hours-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        .slot-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.7rem;
            margin-top: 0.5rem;
            max-height: 260px;
            overflow-y: auto;
            padding: 0.8rem;
            border: 2px solid rgba(238,243,234,0.8);
            border-radius: 24px;
            background: rgba(254,253,248,0.5);
        }
        
        .slot-btn {
            background: rgba(234,245,230,0.8);
            border: 1px solid rgba(194,213,187,0.8);
            padding: 0.6rem 1rem;
            border-radius: 50px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 80px;
            font-weight: 500;
        }
        .slot-btn:hover:not(.disabled) {
            background: #c2d5bb;
            transform: translateY(-3px);
        }
        .slot-btn.selected {
            background: #2b7e3a;
            color: white;
            border-color: #2b7e3a;
            box-shadow: 0 4px 15px rgba(43,126,58,0.3);
        }
        .slot-btn.disabled {
            opacity: 0.4;
            cursor: not-allowed;
            background: #e0e0e0;
            text-decoration: line-through;
            pointer-events: none;
        }
        .slot-time {
            font-weight: 700;
            font-size: 0.9rem;
        }
        .slot-price {
            font-size: 0.65rem;
            opacity: 0.8;
        }
        
        .hours-selector {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            margin-top: 0.5rem;
        }
        .hour-btn {
            background: rgba(234,245,230,0.8);
            border: 1px solid rgba(194,213,187,0.8);
            padding: 0.6rem 1.2rem;
            border-radius: 50px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            min-width: 70px;
            text-align: center;
            font-weight: 600;
        }
        .hour-btn:hover {
            background: #c2d5bb;
            transform: translateY(-3px);
        }
        .hour-btn.selected {
            background: #2b7e3a;
            color: white;
            border-color: #2b7e3a;
        }
        
        .help-text {
            font-size: 0.7rem;
            color: #888;
            margin-top: 0.5rem;
        }
        .info-text {
            font-size: 0.75rem;
            color: #2b7e3a;
            margin-top: 0.6rem;
            padding: 0.4rem;
            background: rgba(224,240,220,0.8);
            border-radius: 16px;
            text-align: center;
        }
        .error-msg {
            color: #e67e22;
            padding: 0.6rem;
            text-align: center;
            background: rgba(255,240,224,0.8);
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.8rem;
        }
        
        .cart-summary { 
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(10px);
            border-radius: 28px; 
            padding: 1.8rem; 
            position: sticky; 
            top: 2rem;
            border: 1px solid rgba(255,255,255,0.3);
            animation: fadeInScale 0.5s ease-out 0.2s both;
        }
        .cart-summary h3 { 
            font-family: 'Montserrat', 'Poppins', sans-serif;
            font-size: 1.3rem; 
            color: #1e3a2a; 
            margin-bottom: 1.2rem; 
            padding-bottom: 0.7rem; 
            border-bottom: 2px solid rgba(234,245,230,0.8);
        }
        
        .breakdown-item {
            display: flex;
            justify-content: space-between;
            padding: 0.6rem 0;
            border-bottom: 1px solid rgba(224,232,220,0.8);
            font-size: 0.85rem;
            color: #4a5b4e;
        }
        .breakdown-total {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0 0;
            margin-top: 0.5rem;
            font-weight: 800;
            font-size: 1.1rem;
            border-top: 2px solid #2b7e3a;
            color: #2b7e3a;
        }
        
        .coach-divider {
            margin: 1.2rem 0 1rem 0;
            border-top: 2px solid rgba(234,245,230,0.8);
        }
        .coach-section-title {
            font-family: 'Montserrat', 'Poppins', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            color: #2b7e3a;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .coach-container {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            max-height: 280px;
            overflow-y: auto;
        }
        .coach-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem;
            border: 2px solid rgba(238,243,234,0.8);
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.5);
        }
        .coach-item:hover:not(.disabled) {
            border-color: #2b7e3a;
            background: rgba(234,245,230,0.8);
            transform: translateX(5px);
        }
        .coach-item.selected {
            border-color: #2b7e3a;
            background: rgba(224,240,220,0.8);
            box-shadow: 0 4px 15px rgba(43,126,58,0.1);
        }
        
        .coach-item.disabled {
            opacity: 0.5;
            cursor: not-allowed !important;
            pointer-events: none;
            border-color: #e0e0e0 !important;
            background: #f5f5f5 !important;
        }
        .coach-item.disabled .coach-name {
            color: #999 !important;
        }
        .coach-item .coach-status {
            display: block;
            margin-top: 2px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .coach-item.has-booking {
            border-color: #f39c12 !important;
            background: rgba(243, 156, 18, 0.05) !important;
        }
        .coach-item.has-booking:hover:not(.disabled) {
            border-color: #f39c12 !important;
            background: rgba(243, 156, 18, 0.1) !important;
        }
        
        .coach-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            background: linear-gradient(145deg, #e8efe2, #d4e0ca);
            flex-shrink: 0;
        }
        .coach-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .coach-info {
            flex: 1;
        }
        .coach-name {
            font-family: 'Montserrat', 'Poppins', sans-serif;
            font-weight: 800;
            font-size: 0.9rem;
            color: #1e2a2e;
        }
        .coach-specialty {
            font-size: 0.7rem;
            color: #888;
            margin-top: 0.2rem;
        }
        .coach-price {
            text-align: right;
            min-width: 70px;
        }
        .coach-price .price {
            font-weight: 800;
            color: #e67e22;
            font-size: 1rem;
        }
        .coach-price .unit {
            font-size: 0.6rem;
            color: #888;
        }
        
        .coach-hours-selector {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.8rem;
        }
        .coach-hour-btn {
            background: rgba(255,240,224,0.8);
            border: 1px solid #e67e22;
            padding: 0.4rem 1rem;
            border-radius: 40px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        .coach-hour-btn:hover {
            background: #e67e22;
            color: white;
        }
        .coach-hour-btn.selected {
            background: #e67e22;
            color: white;
        }
        
        .btn-continue { 
            background: linear-gradient(135deg, #2b7e3a, #1f5a2a);
            color: white; border: none; padding: 1rem; border-radius: 60px;
            width: 100%; font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 700; font-size: 1rem; cursor: pointer; margin-top: 1.5rem;
            transition: all 0.4s ease; box-shadow: 0 4px 15px rgba(43,126,58,0.3);
            position: relative; overflow: hidden;
        }
        .btn-continue::before {
            content: ''; position: absolute; top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }
        .btn-continue:hover::before { left: 100%; }
        .btn-continue:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(43,126,58,0.4); }
        .btn-continue:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        
        .btn-back-link {
            display: block; text-align: center; color: #888; text-decoration: none;
            font-size: 0.85rem; font-weight: 600; margin-top: 1.2rem; padding-top: 0.8rem;
            border-top: 1px solid rgba(238,238,238,0.8); transition: 0.3s;
        }
        .btn-back-link:hover { color: #2b7e3a; transform: translateX(-3px); }
        
        .training-badge {
            background: linear-gradient(135deg, #2b7e3a, #1f5a2a);
            color: white;
            padding: 0.3rem 1rem;
            border-radius: 40px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            body { padding: 1rem; }
            .row-2cols { grid-template-columns: 1fr; }
            .time-hours-grid { grid-template-columns: 1fr; }
            .cart-summary { position: static; }
            .progress-bar { flex-direction: column; gap: 0.5rem; background: transparent; padding: 0; border: none; }
            .progress-step { display: flex; align-items: center; gap: 1rem; background: rgba(255,255,255,0.7); padding: 0.7rem 1rem; border-radius: 60px; margin-bottom: 0.5rem; border: 1px solid rgba(255,255,255,0.3); }
            .progress-step:not(:last-child)::after { display: none; }
            .progress-step .step-number { margin-bottom: 0; }
            .photo-card { width: 110px; height: 110px; }
        }
    </style>
</head>
<body>
    <?php if(isset($_SESSION['error'])): ?>
    <div class="error-msg" style="margin:15px;">
        <?php
            echo $_SESSION['error'];
            unset($_SESSION['error']);
        ?>
    </div>
<?php endif; ?>

<div class="container">
    
    <div class="progress-bar">
        <div class="progress-step active"><div class="step-number">1</div><div class="step-label">Book Court</div></div>
        <div class="progress-step"><div class="step-number">2</div><div class="step-label">Add-ons</div></div>
        <div class="progress-step"><div class="step-number">3</div><div class="step-label">Checkout</div></div>
        <div class="progress-step"><div class="step-number">4</div><div class="step-label">Payment</div></div>
    </div>
    
    <div class="booking-summary">
        <div>
            <div style="font-size:1.2rem; font-weight:800;">🏸 <?=htmlspecialchars($court['court_name'])?></div>
            <div style="font-size:0.8rem; opacity:0.85; margin-top:0.2rem;">
                📍 <?=htmlspecialchars($court['location'] ?? 'Main Hall')?> &nbsp;|&nbsp;
                Off-Peak: RM <?=number_format($court['price_off_peak'], 2)?>/hr &nbsp;|&nbsp;
                Peak: RM <?=number_format($court['price_peak'], 2)?>/hr
            </div>
        </div>
        <?php if($court['court_type'] == 'Training'): ?>
            <span class="training-badge">🎯 Training Court</span>
        <?php endif; ?>
    </div>
    
    <div class="row-2cols">
        <div>
            <div class="product-section">
                <div class="section-title"><i class="fas fa-info-circle"></i> Court Information</div>
                <div class="info-grid">
                    <div class="info-row"><span class="label">Location</span><span class="value"><?=htmlspecialchars($court['location'] ?? 'Main Hall')?></span></div>
                    <div class="info-row"><span class="label">Facilities</span><span class="value"><?=htmlspecialchars($court['facilities'] ?? 'Shower, Locker, Rest Area')?></span></div>
                    <div class="info-row"><span class="label">Description</span><span class="value"><?=htmlspecialchars($court['description'] ?? 'Professional badminton court with premium flooring')?></span></div>
                    <div class="price-tags">
                        <span class="price-tag price-offpeak"><i class="fas fa-sun"></i> Off-Peak: RM <?=number_format($court['price_off_peak'], 2)?>/hr</span>
                        <span class="price-tag price-peak"><i class="fas fa-moon"></i> Peak: RM <?=number_format($court['price_peak'], 2)?>/hr</span>
                    </div>
                </div>
            </div>
            
            <div class="product-section">
                <div class="section-title"><i class="fas fa-images"></i> Court Photos</div>
                <?php if(empty($court_photos)): ?>
                    <div class="no-photos"><i class="fas fa-camera"></i> No photos available</div>
                <?php else: ?>
                    <div class="photos-scroll">
                        <?php foreach($court_photos as $index => $photo): ?>
                            <div class="photo-card <?= $index === 0 ? 'active' : '' ?>" data-index="<?=$index?>">
                                <img src="<?=$photo?>" alt="Court photo <?=$index+1?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="product-section">
                <div class="section-title"><i class="fas fa-calendar-alt"></i> Select Date & Time</div>
                <input type="text" id="datepicker" placeholder="Click to select a date" required readonly>
                
                <div id="timeSection" style="margin-top: 1.5rem; display: none;">
                    <div class="time-hours-grid">
                        <div>
                            <div style="font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Start Time</div>
                            <div id="slotList" class="slot-container"></div>
                            <input type="hidden" id="selected_time">
                        </div>
                        <div>
                            <div style="font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Court Hours</div>
                            <div id="hoursList" class="hours-selector"></div>
                            <div id="maxHoursInfo" class="help-text"></div>
                        </div>
                    </div>
                </div>
                <div id="noSlotsMessage" class="error-msg" style="display: none; margin-top: 1rem;">No available slots for this date</div>
                <div id="dateHint" class="help-text" style="margin-top: 0.8rem;">Select a date above first</div>
            </div>
        </div>
        
        <div>
            <div class="cart-summary">
                <h3><i class="fas fa-receipt"></i> Booking Summary</h3>
                <div id="breakdownContainer">
                    <div style="text-align: center; padding: 1.5rem 0; color: #aaa;">
                        <i class="fas fa-calendar-alt" style="font-size: 2rem; display: block; margin-bottom: 0.5rem; opacity: 0.3;"></i>
                        Select a date and time
                    </div>
                </div>
                
                <?php if($court['court_type'] == 'Training' && !empty($coaches)): ?>
                <div id="coachSection" style="display: <?php echo ($preferred_coach_id > 0 || $court['court_type'] == 'Training') ? 'block' : 'none'; ?>;">
                    <div class="coach-divider"></div>
                    <div class="coach-section-title"><i class="fas fa-user-tie"></i> Select Coach (Optional)</div>
                    <div id="coachList" class="coach-container">
                        <div class="coach-item <?php echo ($preferred_coach_id == 0) ? 'selected' : ''; ?>" 
                             data-coach-id="0" data-coach-price="0" data-coach-name="No coach">
                            <div class="coach-avatar"><img src="../Pictures/Admin_Module/coaches/default.png" alt="No coach"></div>
                            <div class="coach-info"><div class="coach-name">📝 No coach</div><div class="coach-specialty">Practice on your own</div></div>
                            <div class="coach-price"><div class="price">FREE</div></div>
                        </div>
                        <?php foreach($coaches as $coach): ?>
                        <div class="coach-item <?php echo ($preferred_coach_id == $coach['id']) ? 'selected' : ''; ?>" 
                             data-coach-id="<?=$coach['id']?>" 
                             data-coach-price="<?=$coach['price_per_hour']?>" 
                             data-coach-name="<?=htmlspecialchars($coach['name'])?>">
                            <div class="coach-avatar"><img src="<?=htmlspecialchars(getCoachImage($coach))?>" alt="<?=htmlspecialchars($coach['name'])?>" onerror="this.src='../Pictures/Admin_Module/coaches/default.png'"></div>
                            <div class="coach-info">
                                <div class="coach-name"><?=htmlspecialchars($coach['name'])?></div>
                                <div class="coach-specialty"><?=htmlspecialchars($coach['specialty'])?></div>
                                <div class="coach-status" id="coach-status-<?=$coach['id']?>">⏳ Checking availability...</div>
                            </div>
                            <div class="coach-price">
                                <div class="price">RM <?=number_format($coach['price_per_hour'], 2)?></div>
                                <div class="unit">/ hr</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div id="coachHoursSection" style="display: none;">
                        <div style="font-weight: 600; font-size: 0.9rem; margin: 0.8rem 0 0.5rem 0;">Coach Hours</div>
                        <div id="coachHoursList" class="coach-hours-selector"></div>
                        <div class="help-text">Max = court hours</div>
                    </div>
                </div>
                <?php endif; ?>
                
                <button id="submitBtn" class="btn-continue" disabled>Proceed to Add-ons →</button>
                <a href="dashboard.php" class="btn-back-link">← Back to Courts</a>
            </div>
        </div>
    </div>
    
    <form id="bookingForm" action="process_booking.php" method="POST" style="display: none;">
        <input type="hidden" name="court_id" value="<?=$court_id?>">
        <input type="hidden" id="booking_date" name="booking_date">
        <input type="hidden" id="start_time" name="start_time">
        <input type="hidden" id="total_hours" name="total_hours">
        <input type="hidden" id="total_price" name="price">
        <input type="hidden" id="coach_id" name="coach_id" value="0">
        <input type="hidden" id="coach_hours" name="coach_hours" value="0">
        <input type="hidden" id="coach_price_total" name="coach_price_total" value="0">
        <input type="hidden" name="notes" value="">
    </form>
</div>

<div class="lightbox" id="lightbox">
    <button class="lightbox-close" onclick="closeLightbox()">&times;</button>
    <span class="lightbox-counter" id="lightboxCounter">1 / 1</span>
    <button class="lightbox-nav prev" onclick="navigateLightbox(-1)">&#10094;</button>
    <button class="lightbox-nav next" onclick="navigateLightbox(1)">&#10095;</button>
    <img class="lightbox-content" id="lightboxImage" src="" alt="Court photo">
    <div class="lightbox-caption" id="lightboxCaption">Court Photo</div>
</div>

<script>
    // ============================================================
    // CONFIGURATION
    // ============================================================
    const courtId = <?=$court_id?>;
    const courtType = '<?=$court['court_type']?>';
    const offPeakPrice = <?=$court['price_off_peak']?>;
    const peakPrice = <?=$court['price_peak']?>;
    
    const preferredCoachId = <?php echo $preferred_coach_id; ?>;
    const preferredDuration = <?php echo $preferred_duration; ?>;
    const preferredDate = '<?php echo $preferred_date; ?>';
    
    console.log('🔵 Page loaded with:', { preferredCoachId, preferredDuration, preferredDate, courtType });
    
    // ============================================================
    // STATE VARIABLES
    // ============================================================
    let availableSlots = [];
    let selectedDate = null;
    let selectedStartHour = null;
    let selectedStartTime = null;
    let selectedCourtHours = null;
    let maxAvailableHours = 0;
    let selectedCoachId = 0;
    let selectedCoachPrice = 0;
    let selectedCoachName = '';
    let selectedCoachHours = 0;
    
    // ============================================================
    // DOM REFERENCES
    // ============================================================
    const dateInput = document.getElementById('datepicker');
    const timeSection = document.getElementById('timeSection');
    const slotList = document.getElementById('slotList');
    const hoursList = document.getElementById('hoursList');
    const maxHoursInfo = document.getElementById('maxHoursInfo');
    const noSlotsMessage = document.getElementById('noSlotsMessage');
    const dateHint = document.getElementById('dateHint');
    const breakdownContainer = document.getElementById('breakdownContainer');
    const submitBtn = document.getElementById('submitBtn');
    const coachSection = document.getElementById('coachSection');
    const coachHoursSection = document.getElementById('coachHoursSection');
    const coachHoursList = document.getElementById('coachHoursList');
    
    // ============================================================
    // LIGHTBOX - Photo Viewer
    // ============================================================
    let lightboxImages = [];
    let currentImageIndex = 0;

    document.querySelectorAll('.photo-card').forEach((card, index) => {
        card.addEventListener('click', function(e) {
            if (e.target.tagName === 'IMG' || e.target.closest('img')) {
                openLightbox(index);
            }
        });
    });

    function openLightbox(index) {
        const photoCards = document.querySelectorAll('.photo-card img');
        lightboxImages = [];
        photoCards.forEach(img => {
            lightboxImages.push(img.src);
        });
        
        if (lightboxImages.length === 0) return;
        
        currentImageIndex = index;
        const lightbox = document.getElementById('lightbox');
        const lightboxImage = document.getElementById('lightboxImage');
        const lightboxCounter = document.getElementById('lightboxCounter');
        const lightboxCaption = document.getElementById('lightboxCaption');
        
        lightboxImage.src = lightboxImages[index];
        lightboxCounter.textContent = (index + 1) + ' / ' + lightboxImages.length;
        lightboxCaption.textContent = 'Court Photo ' + (index + 1);
        
        const showNav = lightboxImages.length > 1;
        document.querySelectorAll('.lightbox-nav').forEach(el => {
            el.style.display = showNav ? 'flex' : 'none';
        });
        
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        const lightbox = document.getElementById('lightbox');
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
    }

    function navigateLightbox(direction) {
        const newIndex = currentImageIndex + direction;
        if (newIndex < 0 || newIndex >= lightboxImages.length) return;
        
        currentImageIndex = newIndex;
        const lightboxImage = document.getElementById('lightboxImage');
        const lightboxCounter = document.getElementById('lightboxCounter');
        const lightboxCaption = document.getElementById('lightboxCaption');
        
        lightboxImage.src = lightboxImages[newIndex];
        lightboxCounter.textContent = (newIndex + 1) + ' / ' + lightboxImages.length;
        lightboxCaption.textContent = 'Court Photo ' + (newIndex + 1);
    }

    document.getElementById('lightbox').addEventListener('click', function(e) {
        if (e.target === this) {
            closeLightbox();
        }
    });

    document.addEventListener('keydown', function(e) {
        const lightbox = document.getElementById('lightbox');
        if (!lightbox.classList.contains('active')) return;
        
        if (e.key === 'Escape') {
            closeLightbox();
        } else if (e.key === 'ArrowLeft') {
            navigateLightbox(-1);
        } else if (e.key === 'ArrowRight') {
            navigateLightbox(1);
        }
    });
    
    // ============================================================
    // ★★★ CHECK COACH AVAILABILITY ★★★
    // ============================================================
    async function checkCoachAvailability(coachId, date, startTime, duration) {
        try {
            const res = await fetch(`ajax_check_coach_availability.php?coach_id=${coachId}&date=${date}&start_time=${startTime}&duration=${duration}`);
            const data = await res.json();
            return data;
        } catch(e) {
            console.error('Error checking coach availability:', e);
            return { available: false, count: 0 };
        }
    }

    async function updateAllCoachAvailability() {
        console.log('🔍 updateAllCoachAvailability called');
        console.log('📊 Current state:', {
            selectedDate: selectedDate,
            selectedStartTime: selectedStartTime,
            selectedCourtHours: selectedCourtHours
        });
        
        // 如果缺少数据，尝试从 DOM 获取
        if (!selectedDate) {
            selectedDate = document.getElementById('booking_date')?.value || null;
        }
        if (!selectedStartTime) {
            const selectedSlot = document.querySelector('.slot-btn.selected');
            if (selectedSlot) {
                selectedStartTime = selectedSlot.dataset.time;
                selectedStartHour = parseInt(selectedSlot.dataset.hour);
                console.log('🔄 Retrieved start time from DOM:', selectedStartTime);
            }
        }
        if (!selectedCourtHours) {
            const selectedHour = document.querySelector('.hour-btn.selected');
            if (selectedHour) {
                selectedCourtHours = parseInt(selectedHour.dataset.hours);
                console.log('🔄 Retrieved court hours from DOM:', selectedCourtHours);
            }
        }
        
        if (!selectedDate || !selectedStartTime || !selectedCourtHours) {
            console.log('⚠️ Missing selection, skipping update');
            return;
        }

        console.log('🔄 Updating coach availability for:', {
            date: selectedDate,
            start: selectedStartTime,
            duration: selectedCourtHours
        });

        const coachItems = document.querySelectorAll('.coach-item[data-coach-id]');
        console.log('📋 Found ' + coachItems.length + ' coach items');
        
        for (const item of coachItems) {
            const coachId = parseInt(item.dataset.coachId);
            if (coachId === 0) continue;
            
            const statusEl = document.getElementById('coach-status-' + coachId);
            if (!statusEl) {
                console.warn('⚠️ Status element not found for coach:', coachId);
                continue;
            }
            
            statusEl.textContent = '⏳ Checking...';
            statusEl.style.color = '#888';
            
            try {
                const url = `ajax_check_coach_availability.php?coach_id=${coachId}&date=${selectedDate}&start_time=${selectedStartTime}&duration=${selectedCourtHours}`;
                console.log('📡 Fetching: ' + url);
                
                const res = await fetch(url);
                const text = await res.text();
                console.log('📥 Raw response for coach ' + coachId + ':', text);
                
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('❌ Failed to parse JSON for coach ' + coachId + ':', text);
                    statusEl.textContent = '⚠️ Error parsing';
                    statusEl.style.color = '#f39c12';
                    continue;
                }
                
                console.log('✅ Coach ' + coachId + ' data:', data);
                
                if (data.count === 0) {
                    item.classList.remove('disabled', 'has-booking');
                    item.style.opacity = '1';
                    item.style.pointerEvents = 'auto';
                    statusEl.textContent = '✅ Available';
                    statusEl.style.color = '#16a34a';
                    console.log('✅ Coach ' + coachId + ' is AVAILABLE');
                } else {
                    item.classList.add('disabled', 'has-booking');
                    statusEl.textContent = '❌ Booked (has ' + data.count + ' session' + (data.count > 1 ? 's' : '') + ')';
                    statusEl.style.color = '#dc2626';
                    console.log('❌ Coach ' + coachId + ' is BOOKED (' + data.count + ' sessions)');
                    
                    if (selectedCoachId === coachId) {
                        const noCoachItem = document.querySelector('.coach-item[data-coach-id="0"]');
                        if (noCoachItem) {
                            noCoachItem.click();
                        }
                        showToast('This coach is not available at the selected time. Please choose another coach or time.', 'warning');
                    }
                }
            } catch(e) {
                console.error('❌ Error checking coach ' + coachId + ':', e);
                if (statusEl) {
                    statusEl.textContent = '⚠️ Error: ' + e.message;
                    statusEl.style.color = '#f39c12';
                }
            }
        }
        
        console.log('✅ Coach availability update complete');
    }

    function showToast(message, type) {
        let toast = document.getElementById('customToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'customToast';
            toast.style.cssText = `
                position: fixed;
                bottom: 30px;
                left: 50%;
                transform: translateX(-50%);
                background: ${type === 'warning' ? '#f39c12' : '#2b7e3a'};
                color: white;
                padding: 15px 30px;
                border-radius: 12px;
                font-weight: 600;
                box-shadow: 0 4px 20px rgba(0,0,0,0.2);
                z-index: 99999;
                max-width: 90%;
                text-align: center;
                animation: fadeInUp 0.3s ease;
                font-family: 'Inter', sans-serif;
            `;
            document.body.appendChild(toast);
        }
        toast.textContent = message;
        toast.style.display = 'block';
        
        clearTimeout(toast._timeout);
        toast._timeout = setTimeout(() => {
            toast.style.display = 'none';
        }, 5000);
    }
    
    // ============================================================
    // COACH SELECTION
    // ============================================================
    if(document.getElementById('coachList')) {
        document.querySelectorAll('.coach-item').forEach(item => {
            item.addEventListener('click', function() {
                if (this.classList.contains('disabled')) {
                    showToast('This coach is not available at the selected time.', 'warning');
                    return;
                }
                
                document.querySelectorAll('.coach-item').forEach(i => i.classList.remove('selected'));
                this.classList.add('selected');
                selectedCoachId = parseInt(this.dataset.coachId);
                selectedCoachPrice = parseInt(this.dataset.coachPrice);
                selectedCoachName = this.dataset.coachName;
                document.getElementById('coach_id').value = selectedCoachId;
                if(selectedCoachId > 0 && selectedCourtHours) {
                    coachHoursSection.style.display = 'block';
                    generateCoachHourButtons();
                } else {
                    coachHoursSection.style.display = 'none';
                    selectedCoachHours = 0;
                    document.getElementById('coach_hours').value = 0;
                    document.getElementById('coach_price_total').value = 0;
                    if(selectedCourtHours) calculatePrice();
                }
                if(selectedCourtHours) calculatePrice();
            });
        });
    }
    
    let flatpickrInstance = null;
    
    // ============================================================
    // DATE PICKER - Flatpickr with prefill support
    // ============================================================
    const flatpickrConfig = {
        dateFormat: "Y-m-d",
        minDate: "today",
        maxDate: new Date().fp_incr(30),
        onChange: (dates, dateStr) => {
            if(dateStr) {
                console.log('📅 Flatpickr onChange triggered:', dateStr);
                selectedDate = dateStr;
                document.getElementById('booking_date').value = dateStr;
                loadSlots(dateStr);
                resetSelection();
            }
        }
    };

    if(preferredDate) {
        console.log('📅 Setting preferred date:', preferredDate);
        flatpickrInstance = flatpickr(dateInput, flatpickrConfig);
        // ★★★ 关键修复：setDate 第二个参数 true 触发 onChange ★★★
        flatpickrInstance.setDate(preferredDate, true);
        
        // 手动设置变量并加载 slots（作为备选，防止 onChange 不触发）
        setTimeout(() => {
            if(preferredDate) {
                console.log('📅 Manual fallback - loading slots for:', preferredDate);
                if (!selectedDate) {
                    selectedDate = preferredDate;
                    document.getElementById('booking_date').value = preferredDate;
                    loadSlots(preferredDate);
                }
            }
        }, 400);
    } else {
        flatpickr(dateInput, flatpickrConfig);
    }
    
    // ============================================================
    // LOAD AVAILABLE TIME SLOTS
    // ============================================================
    async function loadSlots(date) {
        console.log('🔄 loadSlots called for date:', date);
        timeSection.style.display = 'block';
        slotList.innerHTML = '<div class="help-text">Loading...</div>';
        noSlotsMessage.style.display = 'none';
        hoursList.innerHTML = '';
        maxHoursInfo.innerHTML = '';
        dateHint.style.display = 'none';
        resetSelection();
        
        try {
            const res = await fetch(`ajax_get_available_slots.php?court_id=${courtId}&date=${date}`);
            const slots = await res.json();
            availableSlots = slots;
            
            if(!slots || slots.length === 0) {
                slotList.innerHTML = '';
                noSlotsMessage.style.display = 'block';
                return;
            }
            
            const now = new Date();
            const today = new Date().toISOString().slice(0,10);
            const currentHour = now.getHours();
            
            let html = '';
            slots.forEach(slot => {
                let hour = parseInt(slot.time.split(':')[0]);
                let price = (hour >= 8 && hour < 14) ? offPeakPrice : peakPrice;
                let isPast = false;
                if(date === today && hour < currentHour) isPast = true;
                html += `<button class="slot-btn ${isPast ? 'disabled' : ''}" data-time="${slot.time}" data-hour="${hour}" data-price="${price}" ${isPast ? 'disabled' : ''}><span class="slot-time">${slot.display}</span><span class="slot-price">RM ${price}</span></button>`;
            });
            slotList.innerHTML = html;
            
            document.querySelectorAll('.slot-btn:not(.disabled)').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedStartTime = this.dataset.time;
                    selectedStartHour = parseInt(this.dataset.hour);
                    document.getElementById('start_time').value = selectedStartTime;
                    calculateMaxHours(selectedStartHour);
                    generateHourButtons();
                    if(courtType === 'Training' && coachSection) {
                        coachSection.style.display = 'block';
                        console.log('✅ Coach section displayed');
                    }
                    selectedCourtHours = null;
                    selectedCoachHours = 0;
                    document.getElementById('coach_hours').value = 0;
                    document.getElementById('coach_price_total').value = 0;
                    if(coachHoursSection) coachHoursSection.style.display = 'none';
                    updateBreakdownPlaceholder();
                    submitBtn.disabled = true;
                    
                    if (courtType === 'Training') {
                        setTimeout(() => {
                            updateAllCoachAvailability();
                        }, 500);
                    }
                });
            });
            
            // ★★★ 从 Coach 页面跳转过来的自动处理 ★★★
            console.log('🔄 Checking for preferred coach:', preferredCoachId, 'preferredDate:', preferredDate);
            
            // 如果有预填日期，但没有选择时间，自动选择第一个可用时间
            if (preferredDate && !selectedStartTime) {
                setTimeout(function() {
                    const firstSlot = document.querySelector('.slot-btn:not(.disabled)');
                    if (firstSlot) {
                        console.log('⏳ Auto-selecting first available slot');
                        firstSlot.click();
                    }
                }, 400);
            }
            
            // 自动选择预填的时长
            if (preferredDuration > 0) {
                setTimeout(function() {
                    const hourBtns = document.querySelectorAll('.hour-btn');
                    hourBtns.forEach(btn => {
                        if(parseInt(btn.dataset.hours) === preferredDuration) {
                            btn.click();
                            console.log('⏳ Auto-selected duration:', preferredDuration);
                        }
                    });
                }, 600);
            }
            
            // ★★★ 如果有预填教练，自动选择并检查可用性 ★★★
            if (preferredCoachId > 0 && courtType === 'Training') {
                console.log('🔄 Auto-selecting preferred coach:', preferredCoachId);
                
                setTimeout(function() {
                    if (coachSection) {
                        coachSection.style.display = 'block';
                        console.log('✅ Coach section forced to display');
                    }
                    
                    const preferredCoach = document.querySelector('.coach-item[data-coach-id="' + preferredCoachId + '"]');
                    if (preferredCoach) {
                        if (!preferredCoach.classList.contains('disabled')) {
                            preferredCoach.click();
                            console.log('✅ Auto-selected coach:', preferredCoachId);
                        } else {
                            console.log('⚠️ Preferred coach is disabled');
                            showToast('This coach is not available at the selected time. Please choose another coach.', 'warning');
                        }
                    } else {
                        console.log('⚠️ Coach item not found, retrying...');
                        setTimeout(function() {
                            const coachRetry = document.querySelector('.coach-item[data-coach-id="' + preferredCoachId + '"]');
                            if (coachRetry) {
                                coachRetry.click();
                                console.log('✅ Auto-selected coach (retry):', preferredCoachId);
                            }
                        }, 500);
                    }
                    
                    setTimeout(function() {
                        console.log('🔄 Running updateAllCoachAvailability...');
                        updateAllCoachAvailability();
                    }, 700);
                }, 1000);
            }
            
            // ★★★ 强制触发教练可用性检查（作为备选） ★★★
            if (courtType === 'Training') {
                setTimeout(function() {
                    if (!selectedStartTime) {
                        const selectedSlot = document.querySelector('.slot-btn.selected');
                        if (selectedSlot) {
                            selectedStartTime = selectedSlot.dataset.time;
                            selectedStartHour = parseInt(selectedSlot.dataset.hour);
                            console.log('🔄 Retrieved start time from DOM (fallback):', selectedStartTime);
                        }
                    }
                    if (!selectedCourtHours) {
                        const selectedHour = document.querySelector('.hour-btn.selected');
                        if (selectedHour) {
                            selectedCourtHours = parseInt(selectedHour.dataset.hours);
                            console.log('🔄 Retrieved court hours from DOM (fallback):', selectedCourtHours);
                        }
                    }
                    // 如果现在有数据，执行更新
                    if (selectedDate && selectedStartTime && selectedCourtHours) {
                        console.log('🔄 Running updateAllCoachAvailability (fallback)...');
                        updateAllCoachAvailability();
                    }
                }, 2000);
            }
            
        } catch(e) {
            console.error(e);
            slotList.innerHTML = '<div class="error-msg">Error loading slots</div>';
        }
    }
    
    // ============================================================
    // CALCULATE MAX AVAILABLE HOURS
    // ============================================================
    function calculateMaxHours(startHour) {
        let maxHours = 0;
        let checkHour = startHour;
        while(true) {
            let timeStr = (checkHour % 24).toString().padStart(2,'0') + ':00:00';
            let isAvail = availableSlots.some(slot => slot.time === timeStr);
            if(!isAvail) break;
            maxHours++;
            checkHour++;
            if(checkHour >= 25) break;
        }
        maxAvailableHours = maxHours;
        maxHoursInfo.innerHTML = `📢 Maximum available: ${maxAvailableHours} hour${maxAvailableHours > 1 ? 's' : ''}`;
    }
    
    // ============================================================
    // GENERATE HOUR BUTTONS
    // ============================================================
    function generateHourButtons() {
        if(maxAvailableHours === 0) {
            hoursList.innerHTML = '<div class="error-msg">No more hours available</div>';
            return;
        }
        let html = '';
        for(let i = 1; i <= maxAvailableHours; i++) {
            html += `<button class="hour-btn" data-hours="${i}">${i} hour${i > 1 ? 's' : ''}</button>`;
        }
        hoursList.innerHTML = html;
        document.querySelectorAll('.hour-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.hour-btn').forEach(b => b.classList.remove('selected'));
                this.classList.add('selected');
                selectedCourtHours = parseInt(this.dataset.hours);
                document.getElementById('total_hours').value = selectedCourtHours;
                if(selectedCoachId > 0) {
                    generateCoachHourButtons();
                    coachHoursSection.style.display = 'block';
                }
                calculatePrice();
                
                if (courtType === 'Training') {
                    setTimeout(() => {
                        updateAllCoachAvailability();
                    }, 500);
                }
            });
        });
    }
    
    // ============================================================
    // GENERATE COACH HOUR BUTTONS
    // ============================================================
    function generateCoachHourButtons() {
        let maxCoachHours = selectedCourtHours || maxAvailableHours;
        if(maxCoachHours === 0) maxCoachHours = 1;
        let html = '';
        for(let i = 1; i <= maxCoachHours; i++) {
            let isSelected = (selectedCoachHours === i) ? 'selected' : '';
            html += `<button class="coach-hour-btn ${isSelected}" data-hours="${i}">${i} hour${i > 1 ? 's' : ''}</button>`;
        }
        coachHoursList.innerHTML = html;
        if(selectedCoachHours === 0 && maxCoachHours > 0) {
            selectedCoachHours = maxCoachHours;
            document.getElementById('coach_hours').value = selectedCoachHours;
            const defaultBtn = document.querySelector('.coach-hour-btn[data-hours="' + selectedCoachHours + '"]');
            if(defaultBtn) defaultBtn.classList.add('selected');
        }
        document.querySelectorAll('.coach-hour-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.coach-hour-btn').forEach(b => b.classList.remove('selected'));
                this.classList.add('selected');
                selectedCoachHours = parseInt(this.dataset.hours);
                document.getElementById('coach_hours').value = selectedCoachHours;
                calculatePrice();
            });
        });
    }
    
    // ============================================================
    // CALCULATE TOTAL PRICE
    // ============================================================
    function calculatePrice() {
        if(!selectedStartHour || !selectedCourtHours) return;
        let breakdownHtml = '';
        let totalCourtPrice = 0;
        let currentHour = selectedStartHour;
        for(let i = 0; i < selectedCourtHours; i++) {
            let displayHour = currentHour % 24;
            let price = (displayHour >= 8 && displayHour < 14) ? offPeakPrice : peakPrice;
            let priceType = (displayHour >= 8 && displayHour < 14) ? 'Off-Peak' : 'Peak';
            let start12 = formatHour(displayHour);
            let end12 = formatHour(displayHour + 1);
            breakdownHtml += `<div class="breakdown-item"><span>🏸 ${start12} - ${end12} <span style="font-size:0.7rem; color:#aaa;">(${priceType})</span></span><span>RM ${price}</span></div>`;
            totalCourtPrice += price;
            currentHour++;
        }
        let totalCoachPrice = 0;
        if(selectedCoachId > 0 && selectedCoachHours > 0) {
            totalCoachPrice = selectedCoachPrice * selectedCoachHours;
            breakdownHtml += `<div class="breakdown-item" style="background:#fff8f0; border-radius:12px; margin-top:0.3rem;"><span>🎓 Coach: ${selectedCoachName} (${selectedCoachHours} hour${selectedCoachHours > 1 ? 's' : ''})</span><span>RM ${totalCoachPrice}</span></div>`;
        }
        let totalPrice = totalCourtPrice + totalCoachPrice;
        breakdownHtml += `<div class="breakdown-total"><span>Total</span><span>RM ${totalPrice}</span></div>`;
        breakdownContainer.innerHTML = breakdownHtml;
        document.getElementById('total_price').value = totalPrice;
        document.getElementById('coach_price_total').value = totalCoachPrice;
        submitBtn.disabled = false;
    }
    
    // ============================================================
    // UPDATE BREAKDOWN PLACEHOLDER
    // ============================================================
    function updateBreakdownPlaceholder() {
        breakdownContainer.innerHTML = '<div style="text-align: center; padding: 1.5rem 0; color: #aaa;"><i class="fas fa-calendar-alt" style="font-size: 2rem; display: block; margin-bottom: 0.5rem; opacity: 0.3;"></i>Select a date and time</div>';
    }
    
    // ============================================================
    // FORMAT HOUR (12-hour format)
    // ============================================================
    function formatHour(hour) {
        let h = hour % 24;
        if(h >= 12) {
            let hr = h === 12 ? 12 : h - 12;
            return hr + ':00 PM';
        } else {
            let hr = h === 0 ? 12 : h;
            return hr + ':00 AM';
        }
    }
    
    // ============================================================
    // RESET SELECTION
    // ============================================================
    function resetSelection() {
        selectedStartTime = null;
        selectedStartHour = null;
        selectedCourtHours = null;
        selectedCoachHours = 0;
        document.getElementById('start_time').value = '';
        document.getElementById('total_hours').value = '';
        document.getElementById('coach_hours').value = '0';
        document.getElementById('coach_price_total').value = '0';
        hoursList.innerHTML = '';
        maxHoursInfo.innerHTML = '';
        if(coachHoursSection) coachHoursSection.style.display = 'none';
        updateBreakdownPlaceholder();
        submitBtn.disabled = true;
        
        document.querySelectorAll('.coach-status').forEach(el => {
            if (el) {
                el.textContent = '⏳ Checking availability...';
                el.style.color = '#888';
            }
        });
        document.querySelectorAll('.coach-item').forEach(el => {
            el.classList.remove('disabled', 'has-booking');
            el.style.opacity = '1';
            el.style.pointerEvents = 'auto';
        });
    }
    
    // ============================================================
    // FORM SUBMISSION
    // ============================================================
    document.getElementById('submitBtn').addEventListener('click', () => {
        if (selectedCoachId > 0) {
            const selectedCoachItem = document.querySelector('.coach-item[data-coach-id="' + selectedCoachId + '"]');
            if (selectedCoachItem && selectedCoachItem.classList.contains('disabled')) {
                showToast('Selected coach is not available at this time. Please choose another coach.', 'warning');
                return;
            }
        }
        document.getElementById('bookingForm').submit();
    });
    
    // ============================================================
    // ★★★ 页面加载完成后额外触发（备选方案） ★★★
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        console.log('📄 DOMContentLoaded - preferredCoachId:', preferredCoachId);
        
        if (preferredCoachId > 0 && preferredDate && courtType === 'Training') {
            console.log('🔄 Will auto-select coach after page load');
            
            const coachSectionEl = document.getElementById('coachSection');
            if (coachSectionEl) {
                coachSectionEl.style.display = 'block';
                console.log('✅ Coach section forced to display');
            }
            
            setTimeout(function() {
                if (selectedDate && selectedStartTime && selectedCourtHours) {
                    console.log('✅ All data ready, updating coach availability (DOMContentLoaded)');
                    updateAllCoachAvailability();
                } else {
                    console.log('⏳ Waiting for data...');
                    setTimeout(function() {
                        if (selectedDate && selectedStartTime && selectedCourtHours) {
                            console.log('✅ All data ready (2nd attempt), updating coach availability');
                            updateAllCoachAvailability();
                        } else {
                            console.log('⚠️ Still waiting, forcing update...');
                            if (!selectedStartTime) {
                                const slot = document.querySelector('.slot-btn.selected');
                                if (slot) {
                                    selectedStartTime = slot.dataset.time;
                                    selectedStartHour = parseInt(slot.dataset.hour);
                                }
                            }
                            if (!selectedCourtHours) {
                                const hour = document.querySelector('.hour-btn.selected');
                                if (hour) {
                                    selectedCourtHours = parseInt(hour.dataset.hours);
                                }
                            }
                            if (selectedDate && selectedStartTime && selectedCourtHours) {
                                updateAllCoachAvailability();
                            }
                        }
                    }, 2000);
                }
            }, 1500);
        }
    });
</script>
<?php include 'footer.php'; ?>
</body>
</html>