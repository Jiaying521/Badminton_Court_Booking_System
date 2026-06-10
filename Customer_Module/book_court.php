<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/functions.php';
if(!isLoggedIn()) redirect('homepage.php');
$court_id = $_GET['court_id'] ?? 0;
if(!$court_id) redirect('dashboard.php');

$stmt = $pdo->prepare("SELECT * FROM courts WHERE id = ?");
$stmt->execute([$court_id]);
$court = $stmt->fetch();
if(!$court) redirect('dashboard.php');

// 如果是 Training Court，获取教练列表
$coaches = [];
if($court['court_type'] == 'Training') {
    $coachStmt = $pdo->query("SELECT * FROM coaches WHERE is_active = 1 ORDER BY price_per_hour");
    $coaches = $coachStmt->fetchAll();
}

// 获取教练头像函数
function getCoachImage($coach) {
    $basePath = '../Pictures/Admin_Module/coaches/';
    
    if (!empty($coach['profile_img'])) {
        $photoPath = $coach['profile_img'];
        
        if (strpos($photoPath, 'http') === 0) {
            return $photoPath;
        } elseif (strpos($photoPath, '/') === 0) {
            return $photoPath;
        } elseif (strpos($photoPath, '../') === 0) {
            return $photoPath;
        } elseif (strpos($photoPath, 'Admin_Module/') === 0) {
            return '../' . $photoPath;
        } else {
            $fullPath = $basePath . $photoPath;
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }
    }
    
    return '../Pictures/Admin_Module/coaches/default.png';
}
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
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-image: radial-gradient(rgba(43,126,58,0.08) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
            z-index: 0;
        }
        
        /* ── Exact copy of addons.php CSS ── */
        .container { max-width: 1400px; margin: 0 auto; position: relative; z-index: 1; }

        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #e0e8dc; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #2b7e3a; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #1f5a2a; }

        .progress-bar {
            display: flex; justify-content: space-between; margin-bottom: 2rem;
            background: rgba(255,255,255,0.7); backdrop-filter: blur(10px);
            padding: 0.8rem 2rem; border-radius: 80px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            border: 1px solid rgba(255,255,255,0.3);
            animation: fadeInDown 0.6s ease-out;
        }
        @keyframes fadeInDown { from { opacity:0; transform:translateY(-30px); } to { opacity:1; transform:translateY(0); } }
        @keyframes fadeInUp   { from { opacity:0; transform:translateY(30px);  } to { opacity:1; transform:translateY(0); } }
        @keyframes fadeInScale{ from { opacity:0; transform:scale(0.95); }       to { opacity:1; transform:scale(1); } }

        .progress-step { text-align:center; flex:1; position:relative; }
        .progress-step:not(:last-child)::after { content:''; position:absolute; top:15px; right:-50%; width:100%; height:2px; background:#e0e8dc; z-index:0; }
        .progress-step.completed:not(:last-child)::after { background:#2b7e3a; }
        .progress-step .step-number { width:36px; height:36px; background:#e0e8dc; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; margin-bottom:0.4rem; font-weight:700; font-size:0.9rem; position:relative; z-index:1; transition:0.3s; color:#5a6e5c; }
        .progress-step.active .step-number { background:#2b7e3a; color:white; box-shadow:0 0 0 4px rgba(43,126,58,0.2); animation:pulseStep 2s ease-in-out infinite; }
        @keyframes pulseStep { 0%,100%{box-shadow:0 0 0 0px rgba(43,126,58,0.4);} 50%{box-shadow:0 0 0 6px rgba(43,126,58,0.1);} }
        .progress-step.completed .step-number { background:#2b7e3a; color:white; }
        .progress-step .step-label { font-size:0.75rem; color:#888; font-weight:500; }
        .progress-step.active .step-label { color:#2b7e3a; font-weight:700; }
        .progress-step.completed .step-label { color:#2b7e3a; }

        .booking-summary {
            background: linear-gradient(135deg, rgba(43,126,58,0.9), rgba(27,94,42,0.9));
            backdrop-filter: blur(5px); color:white;
            padding:1rem 1.8rem; border-radius:28px; margin-bottom:2rem;
            display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;
            animation: fadeInUp 0.6s ease-out 0.1s both;
            border:1px solid rgba(255,255,255,0.2);
        }

        .row-2cols { display:grid; grid-template-columns:2fr 1fr; gap:2rem; }

        .product-section {
            background: rgba(255,255,255,0.7); backdrop-filter:blur(10px);
            border-radius:28px; padding:1.8rem; margin-bottom:2rem;
            border:1px solid rgba(255,255,255,0.3);
            transition:all 0.3s; animation:fadeInScale 0.5s ease-out both;
        }
        .product-section:hover { background:rgba(255,255,255,0.8); }
        .product-section:nth-child(1){animation-delay:0.05s;}
        .product-section:nth-child(2){animation-delay:0.1s;}
        .product-section:nth-child(3){animation-delay:0.15s;}

        .section-title {
            font-family:'Montserrat','Poppins',sans-serif; font-size:1.3rem; font-weight:700;
            color:#2b7e3a; margin-bottom:1.2rem; padding-bottom:0.7rem;
            border-bottom:2px solid rgba(234,245,230,0.8);
            display:flex; align-items:center; gap:0.6rem;
        }

        .cart-summary {
            background:rgba(255,255,255,0.7); backdrop-filter:blur(10px);
            border-radius:28px; padding:1.8rem; position:sticky; top:2rem;
            border:1px solid rgba(255,255,255,0.3);
            animation:fadeInScale 0.5s ease-out 0.2s both;
        }
        .cart-summary h3 {
            font-family:'Montserrat','Poppins',sans-serif; font-size:1.3rem; color:#1e3a2a;
            margin-bottom:1.2rem; padding-bottom:0.7rem;
            border-bottom:2px solid rgba(234,245,230,0.8);
        }
        .cart-item { display:flex; justify-content:space-between; padding:0.7rem 0; border-bottom:1px solid rgba(240,240,240,0.8); font-size:0.9rem; color:#4a5b4e; }
        .cart-item span:first-child { font-weight:500; }
        .cart-item span:last-child { font-weight:600; color:#2b7e3a; }
        .cart-total { display:flex; justify-content:space-between; padding:1rem 0 0.5rem; margin-top:0.5rem; border-top:2px solid #2b7e3a; font-weight:800; font-size:1.2rem; color:#2b7e3a; }

        .btn-continue {
            background:linear-gradient(135deg,#2b7e3a,#1f5a2a); color:white; border:none;
            padding:1rem; border-radius:60px; width:100%;
            font-family:'Montserrat','Inter',sans-serif; font-weight:700; font-size:1rem;
            cursor:pointer; margin-top:1.2rem; transition:all 0.4s ease;
            box-shadow:0 4px 15px rgba(43,126,58,0.3); position:relative; overflow:hidden;
            display:block; text-align:center; text-decoration:none;
        }
        .btn-continue::before { content:''; position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg,transparent,rgba(255,255,255,0.3),transparent); transition:left 0.5s ease; }
        .btn-continue:hover::before { left:100%; }
        .btn-continue:hover { transform:translateY(-3px); box-shadow:0 12px 30px rgba(43,126,58,0.4); }
        .btn-continue:disabled { opacity:0.6; cursor:not-allowed; transform:none; box-shadow:none; }

        .btn-skip {
            background:rgba(245,245,245,0.8); color:#666; border:1px solid rgba(224,224,224,0.8);
            padding:0.8rem; border-radius:60px; width:100%; margin-top:0.8rem;
            cursor:pointer; font-family:'Montserrat','Inter',sans-serif; font-weight:600; transition:all 0.3s ease;
            display:block; text-align:center; text-decoration:none; font-size:0.9rem;
        }
        .btn-skip:hover { background:#e8e8e8; transform:translateY(-2px); }

        .btn-back-link {
            display:block; text-align:center; color:#888; text-decoration:none;
            font-size:0.85rem; font-weight:600; margin-top:1.2rem; padding-top:0.8rem;
            border-top:1px solid rgba(238,238,238,0.8); transition:0.3s;
        }
        .btn-back-link:hover { color:#2b7e3a; transform:translateX(-3px); }

        /* ── Book court specific ── */
        .training-badge { background:linear-gradient(135deg,#2b7e3a,#1f5a2a); color:white; padding:0.2rem 0.8rem; border-radius:20px; font-size:0.7rem; display:inline-block; }

        .court-info { background:linear-gradient(135deg,rgba(234,245,230,0.8),rgba(212,232,205,0.8)); backdrop-filter:blur(5px); padding:1.2rem 1.5rem; border-radius:20px; margin-top:1rem; border:1px solid rgba(255,255,255,0.5); }
        .court-info div { margin-bottom:0.3rem; }
        .court-info strong { color:#1b5e2a; }
        .price-info { display:flex; gap:1rem; margin-top:0.8rem; flex-wrap:wrap; }
        .price-offpeak { color:#2b7e3a; background:rgba(43,126,58,0.15); padding:0.3rem 0.8rem; border-radius:40px; font-size:0.85rem; font-weight:500; }
        .price-peak { color:#e67e22; background:rgba(230,126,34,0.1); padding:0.3rem 0.8rem; border-radius:40px; font-size:0.85rem; font-weight:500; }

        label { font-family:'Montserrat','Inter',sans-serif; font-weight:700; display:block; margin-top:1.2rem; color:#1e3a2a; margin-bottom:0.5rem; font-size:0.9rem; }

        #datepicker { width:100%; padding:0.9rem 1rem; border:2px solid rgba(224,232,220,0.8); border-radius:20px; background:rgba(254,253,248,0.9); font-family:'Inter',sans-serif; font-size:1rem; transition:0.2s; cursor:pointer; }
        #datepicker:focus { outline:none; border-color:#2b7e3a; box-shadow:0 0 0 3px rgba(43,126,58,0.1); }

        .time-hours-grid { display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; }

        .slot-container { display:flex; flex-wrap:wrap; gap:0.7rem; margin-top:0.5rem; max-height:280px; overflow-y:auto; padding:1rem; border:2px solid rgba(238,243,234,0.8); border-radius:24px; background:rgba(254,253,248,0.5); }
        .slot-btn { background:rgba(234,245,230,0.8); border:1px solid rgba(194,213,187,0.8); padding:0.7rem 1rem; border-radius:50px; cursor:pointer; font-size:0.9rem; transition:all 0.3s ease; display:flex; flex-direction:column; align-items:center; min-width:90px; font-weight:500; }
        .slot-btn:hover:not(.disabled) { background:#c2d5bb; transform:translateY(-3px); }
        .slot-btn.selected { background:#2b7e3a; color:white; border-color:#2b7e3a; box-shadow:0 4px 15px rgba(43,126,58,0.3); transform:scale(1.02); }
        .slot-btn.disabled { opacity:0.4; cursor:not-allowed; background:#e0e0e0; text-decoration:line-through; pointer-events:none; }
        .slot-time { font-weight:700; font-size:1rem; }
        .slot-price { font-size:0.7rem; opacity:0.8; }

        .hours-selector { display:flex; flex-wrap:wrap; gap:0.6rem; margin-top:0.5rem; }
        .hour-btn { background:rgba(234,245,230,0.8); border:1px solid rgba(194,213,187,0.8); padding:0.7rem 1.2rem; border-radius:50px; cursor:pointer; font-size:0.9rem; transition:all 0.3s ease; min-width:80px; text-align:center; font-weight:600; }
        .hour-btn:hover { background:#c2d5bb; transform:translateY(-3px); }
        .hour-btn.selected { background:#2b7e3a; color:white; border-color:#2b7e3a; box-shadow:0 4px 15px rgba(43,126,58,0.3); }

        .breakdown-item { display:flex; justify-content:space-between; padding:0.6rem 0; border-bottom:1px solid rgba(224,232,220,0.8); font-size:0.9rem; color:#4a5b4e; }
        .breakdown-total { display:flex; justify-content:space-between; padding:0.8rem 0 0; margin-top:0.5rem; font-weight:800; font-size:1.1rem; border-top:2px solid #2b7e3a; color:#2b7e3a; }

        .payment-summary { background:linear-gradient(135deg,#2b7e3a,#1f5a2a); color:white; padding:1rem 1.5rem; border-radius:24px; margin-top:1rem; display:flex; justify-content:space-between; align-items:center; }
        .payment-summary .amount { font-size:1.5rem; font-weight:800; }

        .help-text { font-size:0.7rem; color:#888; margin-top:0.3rem; }
        .loading { text-align:center; padding:1.5rem; color:#5a6e5c; }
        .error-msg { color:#e67e22; padding:0.8rem; text-align:center; background:rgba(255,240,224,0.8); border-radius:20px; font-weight:500; }
        .info-text { font-size:0.8rem; color:#2b7e3a; margin-top:0.8rem; padding:0.5rem; background:rgba(224,240,220,0.8); border-radius:16px; text-align:center; }
        .warning-text { font-size:0.8rem; color:#e67e22; margin-top:0.5rem; padding:0.5rem; background:rgba(255,240,224,0.8); border-radius:16px; text-align:center; }

        .coach-container { display:flex; flex-direction:column; gap:0.8rem; margin-top:0.5rem; max-height:400px; overflow-y:auto; padding:0.3rem; }
        .coach-option { display:flex; align-items:center; gap:1rem; padding:1rem; border:2px solid rgba(238,243,234,0.8); border-radius:20px; cursor:pointer; transition:all 0.4s ease; background:rgba(255,255,255,0.5); }
        .coach-option:hover { border-color:#2b7e3a; background:rgba(234,245,230,0.8); transform:translateX(5px); }
        .coach-option.selected { border-color:#2b7e3a; background:rgba(224,240,220,0.8); box-shadow:0 4px 15px rgba(43,126,58,0.1); }
        .coach-avatar { width:50px; height:50px; border-radius:50%; overflow:hidden; background:linear-gradient(145deg,#e8efe2,#d4e0ca); flex-shrink:0; display:flex; align-items:center; justify-content:center; }
        .coach-avatar img { width:100%; height:100%; object-fit:cover; }
        .coach-info { flex:1; }
        .coach-name { font-family:'Montserrat','Poppins',sans-serif; font-weight:800; font-size:0.9rem; color:#1e2a2e; }
        .coach-specialty { font-size:0.72rem; color:#666; margin-top:0.2rem; }
        .coach-price { text-align:right; min-width:70px; }
        .coach-price .price { font-weight:800; color:#e67e22; font-size:1rem; }
        .coach-price .unit { font-size:0.65rem; color:#888; }
        .coach-badge { display:inline-block; background:#2b7e3a; color:white; padding:0.2rem 0.5rem; border-radius:20px; font-size:0.6rem; margin-top:0.3rem; font-weight:600; }

        .coach-hours-selector { display:flex; flex-wrap:wrap; gap:0.6rem; margin-top:0.5rem; }
        .coach-hour-btn { background:rgba(255,240,224,0.8); border:1px solid #e67e22; padding:0.5rem 1rem; border-radius:40px; cursor:pointer; font-size:0.85rem; transition:all 0.3s ease; min-width:60px; text-align:center; font-weight:600; }
        .coach-hour-btn:hover { background:#e67e22; color:white; transform:translateY(-3px); }
        .coach-hour-btn.selected { background:#e67e22; color:white; border-color:#e67e22; box-shadow:0 4px 15px rgba(230,126,34,0.3); }

        @media (max-width:768px) {
            body { padding:1rem; }
            .row-2cols { grid-template-columns:1fr; }
            .time-hours-grid { grid-template-columns:1fr; }
            .cart-summary { position:static; }
            .progress-bar { flex-direction:column; gap:0.5rem; background:transparent; padding:0; border:none; }
            .progress-step { display:flex; align-items:center; gap:1rem; background:rgba(255,255,255,0.7); padding:0.7rem 1rem; border-radius:60px; margin-bottom:0.5rem; border:1px solid rgba(255,255,255,0.3); }
            .progress-step:not(:last-child)::after { display:none; }
            .progress-step .step-number { margin-bottom:0; }
            .coach-option { flex-wrap:wrap; }
            .coach-price { text-align:left; margin-top:0.5rem; }
        }
    </style>
</head>
<body>
<div class="container">

    <!-- Progress Bar -->
    <div class="progress-bar">
        <div class="progress-step active"><div class="step-number">1</div><div class="step-label">Book Court</div></div>
        <div class="progress-step"><div class="step-number">2</div><div class="step-label">Add-ons</div></div>
        <div class="progress-step"><div class="step-number">3</div><div class="step-label">Checkout</div></div>
        <div class="progress-step"><div class="step-number">4</div><div class="step-label">Payment</div></div>
    </div>

    <!-- Booking Summary Banner -->
    <div class="booking-summary">
        <div>
            <div style="font-size:1.3rem; font-weight:800; font-family:'Montserrat',sans-serif;">🏸 <?=htmlspecialchars($court['court_name'])?></div>
            <div style="font-size:0.85rem; opacity:0.85; margin-top:0.3rem;">
                📍 <?=htmlspecialchars($court['location'] ?? 'Main Hall')?> &nbsp;|&nbsp;
                🕗 Off-Peak: RM <?=getSetting('off_peak_price','10')?>/hr &nbsp;|&nbsp;
                🕒 Peak: RM <?=getSetting('peak_price','15')?>/hr
            </div>
        </div>
        <?php if($court['court_type'] == 'Training'): ?>
            <span class="training-badge">🎯 Training Court</span>
        <?php endif; ?>
    </div>

    <form id="bookingForm" action="process_booking.php" method="POST">
        <input type="hidden" name="court_id" value="<?=$court_id?>">
        <input type="hidden" id="selected_price" name="price" value="">
        <input type="hidden" id="total_hours" name="total_hours" value="">
        <input type="hidden" id="coach_price_total" name="coach_price_total" value="0">
        <input type="hidden" id="coach_id" name="coach_id" value="0">
        <input type="hidden" id="coach_hours" name="coach_hours" value="0">
        <input type="hidden" name="notes" value="">

        <div class="row-2cols">

            <!-- LEFT COLUMN -->
            <div>

                <!-- Date Card -->
                <div class="product-section">
                    <div class="section-title"><i class="fas fa-calendar-alt"></i> Select Date</div>
                    <input type="text" id="datepicker" name="booking_date" placeholder="Click to select a date" required readonly>
                </div>

                <!-- Time & Hours Card -->
                <div class="product-section">
                    <div class="time-hours-grid">
                        <div>
                            <div class="section-title"><i class="fas fa-clock"></i> Start Time</div>
                            <div id="timeSlotContainer" style="display:none;">
                                <div id="slotList" class="slot-container"></div>
                                <input type="hidden" id="selected_time" name="start_time" required>
                            </div>
                            <div id="noSlotsMessage" style="display:none;" class="error-msg">No available slots for this date</div>
                            <p style="color:#bbb; font-size:0.82rem; margin-top:0.5rem;" id="dateHint">Select a date above first</p>
                        </div>
                        <div>
                            <div class="section-title"><i class="fas fa-hourglass-half"></i> Court Hours</div>
                            <div id="hoursContainer" style="display:none;">
                                <div id="hoursList" class="hours-selector"></div>
                                <div class="help-text">How many hours to book</div>
                            </div>
                            <div id="maxHoursInfo" class="info-text" style="display:none;"></div>
                        </div>
                    </div>
                </div>

            </div><!-- end left column -->

            <!-- RIGHT COLUMN — summary + coach -->
            <div>

                <!-- Cart Summary -->
                <div class="cart-summary">
                    <h3><i class="fas fa-receipt"></i> Booking Summary</h3>

                    <div id="priceBreakdown" style="display:none;">
                        <div id="breakdownList"></div>
                        <div id="breakdownTotal" class="cart-total"></div>
                    </div>

                    <p id="summaryPlaceholder" style="color:#aaa; font-size:0.85rem; text-align:center; padding:1.5rem 0;">
                        <i class="fas fa-calendar-alt" style="font-size:2rem; display:block; margin-bottom:0.5rem; opacity:0.3;"></i>
                        Select a date and time to see your booking summary
                    </p>

                    <button type="submit" id="submitBtn" class="btn-continue" disabled>Proceed to Add-ons →</button>
                    <a href="dashboard.php" class="btn-back-link">← Back to Courts</a>
                </div>

                <!-- Coach Card (Training only, in right column) -->
                <?php if($court['court_type'] == 'Training' && !empty($coaches)): ?>
                <div id="coachSection" style="display:none; margin-top:1.5rem;" class="cart-summary">
                    <h3><i class="fas fa-user-tie"></i> Select Coach (Optional)</h3>
                    <div id="coachContainer" class="coach-container">
                        <div class="coach-option" data-coach-id="0" data-coach-price="0" data-coach-name="No coach">
                            <div class="coach-avatar"><img src="../Pictures/Admin_Module/coaches/default.png" alt="No coach"></div>
                            <div class="coach-info"><div class="coach-name">📝 No coach (self-training)</div><div class="coach-specialty">Practice on your own</div></div>
                            <div class="coach-price"><div class="price">FREE</div></div>
                        </div>
                        <?php foreach($coaches as $coach): ?>
                        <div class="coach-option" data-coach-id="<?=$coach['id']?>" data-coach-price="<?=$coach['price_per_hour']?>" data-coach-name="<?=htmlspecialchars($coach['name'])?>">
                            <div class="coach-avatar"><img src="<?=htmlspecialchars(getCoachImage($coach))?>" alt="<?=htmlspecialchars($coach['name'])?>" onerror="this.src='../Pictures/Admin_Module/coaches/default.png'"></div>
                            <div class="coach-info"><div class="coach-name"><?=htmlspecialchars($coach['name'])?></div><div class="coach-specialty"><?=htmlspecialchars($coach['specialty'])?></div><?php if($coach['id'] == 1): ?><span class="coach-badge">🏆 Popular</span><?php elseif($coach['id'] == 2): ?><span class="coach-badge">🎯 Best for Beginners</span><?php elseif($coach['id'] == 3): ?><span class="coach-badge">⭐ Advanced</span><?php endif; ?></div>
                            <div class="coach-price"><div class="price">RM <?=$coach['price_per_hour']?></div><div class="unit">/ hour</div></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div id="coachHoursSection" style="display:none; margin-top:1rem;">
                        <div class="section-title"><i class="fas fa-stopwatch"></i> Coach Hours</div>
                        <div id="coachHoursList" class="coach-hours-selector"></div>
                        <div class="help-text">How many hours you want the coach (max = court hours)</div>
                    </div>
                </div>
                <?php endif; ?>

            </div><!-- end right column -->

        </div><!-- end row-2cols -->
    </form>

</div>

<script>
    const courtId = <?=$court_id?>;
    const courtType = '<?=$court['court_type']?>';
    const offPeakPrice = <?=$court['price_off_peak']?>;
    const peakPrice = <?=$court['price_peak']?>;
    
    const dateInput = document.getElementById('datepicker');
    const timeSlotContainer = document.getElementById('timeSlotContainer');
    const slotList = document.getElementById('slotList');
    const noSlotsMessage = document.getElementById('noSlotsMessage');
    const selectedTimeInput = document.getElementById('selected_time');
    const hoursContainer = document.getElementById('hoursContainer');
    const hoursList = document.getElementById('hoursList');
    const maxHoursInfo = document.getElementById('maxHoursInfo');
    const selectedPriceInput = document.getElementById('selected_price');
    const totalHoursInput = document.getElementById('total_hours');
    const submitBtn = document.getElementById('submitBtn');
    const priceBreakdown = document.getElementById('priceBreakdown');
    const breakdownList = document.getElementById('breakdownList');
    const breakdownTotal = document.getElementById('breakdownTotal');
    const coachPriceTotalInput = document.getElementById('coach_price_total');
    const coachIdInput = document.getElementById('coach_id');
    const coachHoursInput = document.getElementById('coach_hours');
    const coachSection = document.getElementById('coachSection');
    const coachHoursSection = document.getElementById('coachHoursSection');
    const coachHoursList = document.getElementById('coachHoursList');
    const dateHint = document.getElementById('dateHint');

    let availableSlots = [];
    let selectedStartTime = null;
    let selectedStartHour = null;
    let selectedCourtHours = null;
    let maxAvailableHours = 0;
    let selectedCoachPrice = 0;
    let selectedCoachId = 0;
    let selectedCoachName = '';
    let selectedCoachHours = 0;

    function safeSetInnerHTML(element, html) { if (element) element.innerHTML = html; }

    if(document.getElementById('coachContainer')) {
        document.querySelectorAll('.coach-option').forEach(opt => {
            opt.addEventListener('click', function() {
                document.querySelectorAll('.coach-option').forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                selectedCoachPrice = parseInt(this.getAttribute('data-coach-price'));
                selectedCoachId = parseInt(this.getAttribute('data-coach-id'));
                selectedCoachName = this.getAttribute('data-coach-name');
                if(coachIdInput) coachIdInput.value = selectedCoachId;
                if(selectedCoachId > 0 && selectedCourtHours) { if(coachHoursSection) coachHoursSection.style.display = 'block'; generateCoachHourButtons(); }
                else if(selectedCoachId === 0) { if(coachHoursSection) coachHoursSection.style.display = 'none'; selectedCoachHours = 0; if(coachHoursInput) coachHoursInput.value = 0; if(selectedCourtHours) calculatePrice(); }
                if(selectedCourtHours) calculatePrice();
            });
        });
        const defaultCoach = document.querySelector('.coach-option[data-coach-id="0"]');
        if(defaultCoach) { defaultCoach.classList.add('selected'); selectedCoachPrice = 0; selectedCoachId = 0; if(coachIdInput) coachIdInput.value = 0; }
    }

    flatpickr(dateInput, { dateFormat: "Y-m-d", minDate: "today", maxDate: new Date().fp_incr(30), onChange: function(selectedDates, dateStr) { if(dateStr && dateStr !== '') { loadSlots(dateStr); resetSelection(); } } });

    async function loadSlots(date) {
        if(timeSlotContainer) timeSlotContainer.style.display = 'block';
        if(slotList) slotList.innerHTML = '<div class="loading">⏳ Loading available slots...</div>';
        if(noSlotsMessage) noSlotsMessage.style.display = 'none';
        if(hoursContainer) hoursContainer.style.display = 'none';
        if(maxHoursInfo) maxHoursInfo.style.display = 'none';
        if(coachSection) coachSection.style.display = 'none';
        if(dateHint) dateHint.style.display = 'none';
        resetSelection();
        try {
            const res = await fetch(`ajax_get_available_slots.php?court_id=${courtId}&date=${date}`);
            const slots = await res.json();
            availableSlots = slots;
            if(!slots || slots.length === 0) { if(slotList) slotList.innerHTML = ''; if(noSlotsMessage) noSlotsMessage.style.display = 'block'; return; }
            const now = new Date();
            const today = new Date().toISOString().slice(0,10);
            const currentHour = now.getHours();
            const currentMinute = now.getMinutes();
            let html = '';
            let pastCount = 0;
            slots.forEach(slot => {
                let hour = parseInt(slot.time.split(':')[0]);
                let price = (hour >= 8 && hour < 14) ? offPeakPrice : peakPrice;
                let isPastTime = false;
                if (date === today) { if (hour < currentHour) { isPastTime = true; pastCount++; } else if (hour === currentHour && currentMinute > 0) { isPastTime = true; pastCount++; } }
                let disabledClass = isPastTime ? 'disabled' : '';
                let disabledAttr = isPastTime ? 'disabled' : '';
                html += `<button type="button" class="slot-btn ${disabledClass}" data-time="${slot.time}" data-hour="${hour}" data-price="${price}" ${disabledAttr}><span class="slot-time">${slot.display}</span><span class="slot-price">RM ${price}</span></button>`;
            });
            if(slotList) slotList.innerHTML = html;
            if (pastCount > 0 && slotList && slotList.parentNode) { const pastInfo = document.createElement('div'); pastInfo.className = 'warning-text'; pastInfo.innerHTML = `⏰ ${pastCount} past time slot${pastCount > 1 ? 's have' : ' has'} been disabled`; slotList.parentNode.appendChild(pastInfo); setTimeout(() => pastInfo.remove(), 5000); }
            document.querySelectorAll('.slot-btn:not(.disabled)').forEach(btn => { btn.addEventListener('click', function() { document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected')); this.classList.add('selected'); selectedStartTime = this.getAttribute('data-time'); selectedStartHour = parseInt(this.getAttribute('data-hour')); if(selectedTimeInput) selectedTimeInput.value = selectedStartTime; calculateMaxHours(selectedStartHour); generateHourButtons(); if(hoursContainer) hoursContainer.style.display = 'block'; if(maxHoursInfo) maxHoursInfo.style.display = 'block'; if(courtType === 'Training' && coachSection) coachSection.style.display = 'block'; selectedCourtHours = null; selectedCoachHours = 0; if(coachHoursInput) coachHoursInput.value = 0; if(coachHoursSection) coachHoursSection.style.display = 'none'; if(priceBreakdown) priceBreakdown.style.display = 'none'; if(submitBtn) submitBtn.disabled = true; }); });
            if (document.querySelectorAll('.slot-btn:not(.disabled)').length === 0 && slotList) { slotList.innerHTML = '<div class="error-msg">⏰ No available slots for today. Please choose another date.</div>'; }
        } catch(e) { console.error("Error loading slots:", e); if(slotList) slotList.innerHTML = '<div class="error-msg">⚠️ Error loading slots. Please refresh.</div>'; }
    }

    function calculateMaxHours(startHour) { let maxHours = 0; let checkHour = startHour; while(true) { let timeStr = (checkHour % 24).toString().padStart(2,'0') + ':00:00'; let isAvailable = availableSlots.some(slot => slot.time === timeStr); if(!isAvailable) break; maxHours++; checkHour++; if(checkHour >= 25) break; } maxAvailableHours = maxHours; if(maxHoursInfo) { maxHoursInfo.innerHTML = `📢 Maximum available: ${maxAvailableHours} hour${maxAvailableHours > 1 ? 's' : ''}`; if (maxAvailableHours === 0) { maxHoursInfo.style.background = '#fff0e0'; maxHoursInfo.style.color = '#e67e22'; } else { maxHoursInfo.style.background = '#e0f0dc'; maxHoursInfo.style.color = '#2b7e3a'; } } }
    function generateHourButtons() { if(!hoursList) return; if(maxAvailableHours === 0) { hoursList.innerHTML = '<div class="error-msg">No more hours available after this time</div>'; return; } let html = ''; for(let i = 1; i <= maxAvailableHours; i++) { html += `<button type="button" class="hour-btn" data-hours="${i}">${i} hour${i > 1 ? 's' : ''}</button>`; } hoursList.innerHTML = html; document.querySelectorAll('.hour-btn').forEach(btn => { btn.addEventListener('click', function() { document.querySelectorAll('.hour-btn').forEach(b => b.classList.remove('selected')); this.classList.add('selected'); selectedCourtHours = parseInt(this.getAttribute('data-hours')); if(totalHoursInput) totalHoursInput.value = selectedCourtHours; if(selectedCoachId > 0) { generateCoachHourButtons(); if(coachHoursSection) coachHoursSection.style.display = 'block'; } calculatePrice(); }); }); }
    function generateCoachHourButtons() { if(!coachHoursList) return; let maxCoachHours = selectedCourtHours || maxAvailableHours; if(maxCoachHours === 0) maxCoachHours = 1; let html = ''; for(let i = 1; i <= maxCoachHours; i++) { let isSelected = (selectedCoachHours === i) ? 'selected' : ''; html += `<button type="button" class="coach-hour-btn ${isSelected}" data-hours="${i}">${i} hour${i > 1 ? 's' : ''}</button>`; } coachHoursList.innerHTML = html; if(selectedCoachHours === 0 && maxCoachHours > 0) { selectedCoachHours = maxCoachHours; if(coachHoursInput) coachHoursInput.value = selectedCoachHours; const defaultBtn = document.querySelector('.coach-hour-btn[data-hours="' + selectedCoachHours + '"]'); if(defaultBtn) defaultBtn.classList.add('selected'); } document.querySelectorAll('.coach-hour-btn').forEach(btn => { btn.addEventListener('click', function() { document.querySelectorAll('.coach-hour-btn').forEach(b => b.classList.remove('selected')); this.classList.add('selected'); selectedCoachHours = parseInt(this.getAttribute('data-hours')); if(coachHoursInput) coachHoursInput.value = selectedCoachHours; calculatePrice(); }); }); }
    function calculatePrice() { if(!selectedStartHour || !selectedCourtHours) return; let breakdownHtml = ''; let totalCourtPrice = 0; let currentHour = selectedStartHour; for(let i = 0; i < selectedCourtHours; i++) { let slotPrice = 0; let priceType = ''; let displayHour = currentHour % 24; if(displayHour >= 8 && displayHour < 14) { slotPrice = offPeakPrice; priceType = 'Off-Peak (8am-2pm)'; } else { slotPrice = peakPrice; priceType = 'Peak (3pm-1am)'; } let startTime12 = formatHour(displayHour); let endTime12 = formatHour(displayHour + 1); let timeRange = `${startTime12} - ${endTime12}`; breakdownHtml += `<div class="breakdown-item"><span>🏸 ${timeRange} <span style="font-size:0.75rem; color:#888;">(${priceType})</span></span><span>RM ${slotPrice}</span></div>`; totalCourtPrice += slotPrice; currentHour++; } let totalCoachPrice = 0; if(selectedCoachId > 0 && selectedCoachHours > 0) { totalCoachPrice = selectedCoachPrice * selectedCoachHours; breakdownHtml += `<div class="breakdown-item" style="background:#fff0e0; border-radius:12px; margin-top:0.3rem;"><span>🎓 Coach: ${selectedCoachName} (${selectedCoachHours} hour${selectedCoachHours > 1 ? 's' : ''})</span><span>RM ${totalCoachPrice}</span></div>`; } let totalPrice = totalCourtPrice + totalCoachPrice; safeSetInnerHTML(breakdownList, breakdownHtml); safeSetInnerHTML(breakdownTotal, `<span>Total (Court: ${selectedCourtHours}h${selectedCoachHours > 0 ? ' + Coach: ' + selectedCoachHours + 'h' : ''})</span><span>RM ${totalPrice}</span>`); if(priceBreakdown) priceBreakdown.style.display = 'block'; if(selectedPriceInput) selectedPriceInput.value = totalPrice; if(coachPriceTotalInput) coachPriceTotalInput.value = totalCoachPrice; if(submitBtn) submitBtn.disabled = false; const ph = document.getElementById('summaryPlaceholder'); if(ph) ph.style.display = 'none'; }
    function formatHour(hour) { let displayHour = hour % 24; if(displayHour >= 12) { let h = displayHour === 12 ? 12 : displayHour - 12; return h + ':00 PM'; } else { let h = displayHour === 0 ? 12 : displayHour; return h + ':00 AM'; } }
    function resetSelection() { selectedStartTime = null; selectedStartHour = null; selectedCourtHours = null; selectedCoachHours = 0; if(selectedTimeInput) selectedTimeInput.value = ""; if(selectedPriceInput) selectedPriceInput.value = ""; if(totalHoursInput) totalHoursInput.value = ""; if(coachHoursInput) coachHoursInput.value = "0"; if(hoursContainer) hoursContainer.style.display = 'none'; if(maxHoursInfo) maxHoursInfo.style.display = 'none'; if(coachSection) coachSection.style.display = 'none'; if(coachHoursSection) coachHoursSection.style.display = 'none'; if(priceBreakdown) priceBreakdown.style.display = 'none'; if(submitBtn) submitBtn.disabled = true; }
</script>
</body>
</html>