<?php
require_once __DIR__ . '/../config.php';
if (!isLoggedIn()) redirect('homepage.php');

$booking_id = $_GET['booking_id'] ?? 0;
if (!$booking_id) redirect('dashboard.php');

// 获取预订详情
$stmt = $pdo->prepare("
    SELECT b.*, c.court_name, c.court_type 
    FROM bookings b 
    JOIN courts c ON b.court_id = c.id 
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) redirect('dashboard.php');

// ========== 获取已存在的 add-ons（用于回显） ==========
$existing_addons = [];
$stmt_existing = $pdo->prepare("SELECT product_id, quantity, price FROM booking_addons WHERE booking_id = ?");
$stmt_existing->execute([$booking_id]);
$existing_addons_raw = $stmt_existing->fetchAll();

foreach ($existing_addons_raw as $item) {
    $existing_addons[$item['product_id']] = $item['quantity'];
}

// ========== 获取所有产品 ==========
$products = [
    'racket' => [],
    'shuttlecock' => [],
    'grip' => [],
    'string' => [],
    'snack' => [],
    'drink' => []
];

// 获取球拍
$stmt = $pdo->prepare("SELECT * FROM products WHERE category = 'racket' AND is_active = 1 ORDER BY price");
$stmt->execute();
$products['racket'] = $stmt->fetchAll();

// 获取羽毛球
$stmt = $pdo->prepare("SELECT * FROM products WHERE category = 'shuttlecock' AND is_active = 1 ORDER BY price");
$stmt->execute();
$products['shuttlecock'] = $stmt->fetchAll();

// 获取手胶
$stmt = $pdo->prepare("SELECT * FROM products WHERE category = 'grip' AND is_active = 1 ORDER BY price");
$stmt->execute();
$products['grip'] = $stmt->fetchAll();

// 获取球线
$stmt = $pdo->prepare("SELECT * FROM products WHERE category = 'string' AND is_active = 1 ORDER BY price");
$stmt->execute();
$products['string'] = $stmt->fetchAll();

// 获取零食
$stmt = $pdo->prepare("SELECT * FROM products WHERE category = 'snack' AND is_active = 1 ORDER BY price");
$stmt->execute();
$products['snack'] = $stmt->fetchAll();

// 获取饮料
$stmt = $pdo->prepare("SELECT * FROM products WHERE category = 'drink' AND is_active = 1 ORDER BY price");
$stmt->execute();
$products['drink'] = $stmt->fetchAll();

// 获取产品图片
function getProductImage($product) {
    if (!empty($product['image_url'])) {
        $imagePath = $product['image_url'];
        $fullPath = '../Pictures/Admin_Module/products/' . $imagePath;
        if (file_exists(__DIR__ . '/' . $fullPath)) {
            return $fullPath;
        }
        return $fullPath;
    }
    
    $defaultImages = [
        'racket' => 'https://placehold.co/120x120/2b7e3a/white?text=🏸',
        'shuttlecock' => 'https://placehold.co/120x120/2b7e3a/white?text=🏸',
        'grip' => 'https://placehold.co/120x120/2b7e3a/white?text=🎾',
        'string' => 'https://placehold.co/120x120/2b7e3a/white?text=🧵',
        'snack' => 'https://placehold.co/120x120/f39c12/white?text=🍪',
        'drink' => 'https://placehold.co/120x120/3498db/white?text=🥤'
    ];
    
    $category = $product['category'];
    return $defaultImages[$category] ?? 'https://placehold.co/120x120/2b7e3a/white?text=🏸';
}

// 将 PHP 数组转换为 JavaScript 对象，用于回显已有数量
$existing_addons_json = json_encode($existing_addons);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add-ons | Smash Arena</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
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
        
        /* Progress Bar */
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
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .row-2cols { 
            display: grid; 
            grid-template-columns: 2fr 1fr; 
            gap: 2rem; 
        }
        
        /* Category Tabs */
        .category-tabs {
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(10px);
            border-radius: 60px;
            padding: 0.6rem;
            margin-bottom: 2rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid rgba(255,255,255,0.3);
            animation: fadeInScale 0.5s ease-out 0.15s both;
        }
        
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .category-btn {
            background: transparent;
            border: none;
            padding: 0.7rem 1.3rem;
            border-radius: 50px;
            cursor: pointer;
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            color: #4a5b4e;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .category-btn i { font-size: 1rem; transition: 0.2s; }
        .category-btn:hover { background: rgba(234,245,230,0.8); color: #2b7e3a; transform: translateY(-2px); }
        .category-btn.active { background: linear-gradient(135deg, #2b7e3a, #1f5a2a); color: white; box-shadow: 0 4px 15px rgba(43,126,58,0.3); }
        .category-btn.active i { color: white; }
        
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
        .product-section:nth-child(1) { animation-delay: 0.05s; }
        .product-section:nth-child(2) { animation-delay: 0.1s; }
        .product-section:nth-child(3) { animation-delay: 0.15s; }
        .product-section:nth-child(4) { animation-delay: 0.2s; }
        .product-section:nth-child(5) { animation-delay: 0.25s; }
        .product-section:nth-child(6) { animation-delay: 0.3s; }
        
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
        
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.2rem; }
        
        .product-card { 
            background: rgba(255,255,255,0.8);
            border: 1px solid rgba(238,243,234,0.8);
            border-radius: 20px; 
            padding: 1rem; 
            display: flex; 
            gap: 1rem; 
            transition: all 0.4s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            cursor: pointer;
        }
        .product-card:hover { 
            border-color: #2b7e3a; 
            box-shadow: 0 12px 25px rgba(43,126,58,0.15);
            transform: translateY(-5px);
            background: white;
        }
        .product-image { 
            width: 80px; 
            height: 80px; 
            border-radius: 16px; 
            overflow: hidden; 
            background: linear-gradient(145deg, #f5f9f0, #e8efe2);
            flex-shrink: 0; 
            display: flex; 
            align-items: center; 
            justify-content: center;
        }
        .product-image img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s; }
        .product-card:hover .product-image img { transform: scale(1.05); }
        .product-info { flex: 1; }
        .product-name { 
            font-family: 'Montserrat', 'Poppins', sans-serif;
            font-weight: 700; 
            color: #1e3a2a; 
            margin-bottom: 0.3rem; 
            font-size: 0.95rem;
        }
        .product-price { 
            font-family: 'DM Sans', sans-serif;
            color: #e67e22; 
            font-weight: 700; 
            font-size: 1rem;
        }
        .product-price small { font-size: 0.7rem; font-weight: 400; color: #888; }
        .product-qty { display: flex; align-items: center; gap: 0.6rem; margin-top: 0.6rem; }
        .qty-btn { 
            width: 32px; height: 32px; border: 1px solid #ddd; background: rgba(249,249,249,0.8);
            border-radius: 10px; cursor: pointer; font-weight: 700; font-size: 1rem;
            transition: all 0.2s; color: #2b7e3a;
        }
        .qty-btn:hover { background: #2b7e3a; color: white; border-color: #2b7e3a; transform: scale(1.05); }
        .qty-input { 
            width: 55px; text-align: center; padding: 0.4rem; border: 1px solid #ddd;
            border-radius: 12px; font-weight: 600; background: rgba(254,253,248,0.9);
        }
        
        /* Cart Summary */
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
        .cart-item { 
            display: flex; 
            justify-content: space-between; 
            padding: 0.7rem 0; 
            border-bottom: 1px solid rgba(240,240,240,0.8);
            font-size: 0.9rem;
            color: #4a5b4e;
        }
        .cart-item span:first-child { font-weight: 500; }
        .cart-item span:last-child { font-weight: 600; color: #2b7e3a; }
        .cart-total { 
            display: flex; 
            justify-content: space-between; 
            padding: 1rem 0 0.5rem;
            margin-top: 0.5rem; 
            border-top: 2px solid #2b7e3a;
            font-weight: 800; 
            font-size: 1.2rem; 
            color: #2b7e3a;
        }
        .btn-continue { 
            background: linear-gradient(135deg, #2b7e3a, #1f5a2a);
            color: white; border: none; padding: 1rem; border-radius: 60px;
            width: 100%; font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 700; font-size: 1rem; cursor: pointer; margin-top: 1.2rem;
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
        
        .btn-skip { 
            background: rgba(245,245,245,0.8);
            color: #666; border: 1px solid rgba(224,224,224,0.8);
            padding: 0.8rem; border-radius: 60px; width: 100%; margin-top: 0.8rem;
            cursor: pointer; font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 600; transition: all 0.3s ease;
        }
        .btn-skip:hover { background: #e8e8e8; transform: translateY(-2px); }
        
        .btn-back-link {
            display: block; text-align: center; color: #888; text-decoration: none;
            font-size: 0.85rem; font-weight: 600; margin-top: 1.2rem; padding-top: 0.8rem;
            border-top: 1px solid rgba(238,238,238,0.8); transition: 0.3s;
        }
        .btn-back-link:hover { color: #2b7e3a; transform: translateX(-3px); }
        
        .empty-cart { text-align: center; padding: 2rem; color: #aaa; }
        .empty-cart i { font-size: 3rem; margin-bottom: 0.5rem; opacity: 0.5; }
        
        .product-category-section { display: block; }
        
        @media (max-width: 768px) {
            body { padding: 1rem; }
            .row-2cols { grid-template-columns: 1fr; }
            .category-tabs { border-radius: 30px; padding: 0.5rem; flex-wrap: wrap; }
            .category-btn { padding: 0.5rem 1rem; font-size: 0.75rem; }
            .progress-bar { flex-direction: column; gap: 0.5rem; background: transparent; padding: 0; border: none; }
            .progress-step { display: flex; align-items: center; gap: 1rem; background: rgba(255,255,255,0.7); padding: 0.7rem 1rem; border-radius: 60px; margin-bottom: 0.5rem; border: 1px solid rgba(255,255,255,0.3); }
            .progress-step:not(:last-child)::after { display: none; }
            .progress-step .step-number { margin-bottom: 0; }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Progress Bar -->
    <div class="progress-bar">
        <div class="progress-step completed"><div class="step-number"><i class="fas fa-check"></i></div><div class="step-label">Court</div></div>
        <div class="progress-step completed"><div class="step-number"><i class="fas fa-check"></i></div><div class="step-label">Time</div></div>
        <div class="progress-step active"><div class="step-number">3</div><div class="step-label">Add-ons</div></div>
        <div class="progress-step"><div class="step-number">4</div><div class="step-label">Payment</div></div>
    </div>
    
    <div class="booking-summary">
        <div><i class="fas fa-calendar-alt"></i> <strong><?php echo htmlspecialchars($booking['court_name']); ?></strong><br><?php echo date('M j, Y', strtotime($booking['booking_date'])); ?> • <?php echo date('h:i A', strtotime($booking['start_time'])); ?> - <?php echo date('h:i A', strtotime($booking['end_time'])); ?></div>
        <div>Court Fee: <strong>RM <?php echo number_format($booking['total_price'], 2); ?></strong></div>
    </div>
    
    <div class="row-2cols">
        <div>
            <div class="category-tabs">
                <button class="category-btn active" data-category="all"><i class="fas fa-th-large"></i> All</button>
                <button class="category-btn" data-category="racket"><i class="fas fa-table-tennis"></i> Rackets</button>
                <button class="category-btn" data-category="shuttlecock"><i class="fas fa-shuttlecock"></i> Shuttlecocks</button>
                <button class="category-btn" data-category="string"><i class="fas fa-thread"></i> Strings</button>
                <button class="category-btn" data-category="grip"><i class="fas fa-hand-peace"></i> Grips</button>
                <button class="category-btn" data-category="snack"><i class="fas fa-cookie-bite"></i> Snacks</button>
                <button class="category-btn" data-category="drink"><i class="fas fa-tint"></i> Drinks</button>
            </div>
            
            <!-- Rackets Section -->
            <div id="category-racket" class="product-category-section">
                <div class="product-section">
                    <div class="section-title"><i class="fas fa-table-tennis"></i> 🏸 Badminton Rackets</div>
                    <div class="products-grid">
                        <?php if(count($products['racket']) > 0): ?>
                            <?php foreach($products['racket'] as $item): 
                                $existing_qty = $existing_addons[$item['id']] ?? 0;
                            ?>
                            <div class="product-card">
                                <div class="product-image"><img src="<?php echo getProductImage($item); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" onerror="this.src='https://placehold.co/120x120/2b7e3a/white?text=🏸'"></div>
                                <div class="product-info">
                                    <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="product-price">RM <?php echo number_format($item['price'], 2); ?></div>
                                    <div class="product-qty">
                                        <button class="qty-btn" onclick="changeQty(<?php echo $item['id']; ?>, -1)">-</button>
                                        <input type="number" class="qty-input" id="qty_<?php echo $item['id']; ?>" value="<?php echo $existing_qty; ?>" min="0" max="3" data-id="<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>">
                                        <button class="qty-btn" onclick="changeQty(<?php echo $item['id']; ?>, 1)">+</button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="product-card" style="text-align:center; color:#888;">No rackets available</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Shuttlecocks Section -->
            <div id="category-shuttlecock" class="product-category-section">
                <div class="product-section">
                    <div class="section-title"><i class="fas fa-shuttlecock"></i> 🏸 Shuttlecocks</div>
                    <div class="products-grid">
                        <?php if(count($products['shuttlecock']) > 0): ?>
                            <?php foreach($products['shuttlecock'] as $item): 
                                $existing_qty = $existing_addons[$item['id']] ?? 0;
                            ?>
                            <div class="product-card">
                                <div class="product-image"><img src="<?php echo getProductImage($item); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" onerror="this.src='https://placehold.co/120x120/2b7e3a/white?text=🏸'"></div>
                                <div class="product-info">
                                    <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="product-price">RM <?php echo number_format($item['price'], 2); ?> <small>/ tube</small></div>
                                    <div class="product-qty">
                                        <button class="qty-btn" onclick="changeQty(<?php echo $item['id']; ?>, -1)">-</button>
                                        <input type="number" class="qty-input" id="qty_<?php echo $item['id']; ?>" value="<?php echo $existing_qty; ?>" min="0" max="10" data-id="<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>">
                                        <button class="qty-btn" onclick="changeQty(<?php echo $item['id']; ?>, 1)">+</button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="product-card" style="text-align:center; color:#888;">No shuttlecocks available</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Strings Section -->
            <div id="category-string" class="product-category-section">
                <div class="product-section">
                    <div class="section-title"><i class="fas fa-thread"></i> 🧵 Badminton Strings</div>
                    <div class="products-grid">
                        <?php if(count($products['string']) > 0): ?>
                            <?php foreach($products['string'] as $item): 
                                $existing_qty = $existing_addons[$item['id']] ?? 0;
                            ?>
                            <div class="product-card">
                                <div class="product-image"><img src="<?php echo getProductImage($item); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" onerror="this.src='https://placehold.co/120x120/2b7e3a/white?text=🧵'"></div>
                                <div class="product-info">
                                    <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="product-price">RM <?php echo number_format($item['price'], 2); ?></div>
                                    <div class="product-qty">
                                        <button class="qty-btn" onclick="changeQty(<?php echo $item['id']; ?>, -1)">-</button>
                                        <input type="number" class="qty-input" id="qty_<?php echo $item['id']; ?>" value="<?php echo $existing_qty; ?>" min="0" max="10" data-id="<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>">
                                        <button class="qty-btn" onclick="changeQty(<?php echo $item['id']; ?>, 1)">+</button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="product-card" style="text-align:center; color:#888;">No strings available</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Grips Section -->
            <div id="category-grip" class="product-category-section">
                <div class="product-section">
                    <div class="section-title"><i class="fas fa-hand-peace"></i> 🎾 Grips / Overgrips</div>
                    <div class="products-grid">
                        <?php if(count($products['grip']) > 0): ?>
                            <?php foreach($products['grip'] as $item): 
                                $existing_qty = $existing_addons[$item['id']] ?? 0;
                            ?>
                            <div class="product-card">
                                <div class="product-image"><img src="<?php echo getProductImage($item); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" onerror="this.src='https://placehold.co/120x120/2b7e3a/white?text=🎾'"></div>
                                <div class="product-info">
                                    <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="product-price">RM <?php echo number_format($item['price'], 2); ?></div>
                                    <div class="product-qty">
                                        <button class="qty-btn" onclick="changeQty(<?php echo $item['id']; ?>, -1)">-</button>
                                        <input type="number" class="qty-input" id="qty_<?php echo $item['id']; ?>" value="<?php echo $existing_qty; ?>" min="0" max="20" data-id="<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>">
                                        <button class="qty-btn" onclick="changeQty(<?php echo $item['id']; ?>, 1)">+</button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="product-card" style="text-align:center; color:#888;">No grips available</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Snacks Section -->
            <div id="category-snack" class="product-category-section">
                <div class="product-section">
                    <div class="section-title"><i class="fas fa-cookie-bite"></i> 🍪 Snacks</div>
                    <div class="products-grid">
                        <?php if(count($products['snack']) > 0): ?>
                            <?php foreach($products['snack'] as $item): 
                                $existing_qty = $existing_addons[$item['id']] ?? 0;
                            ?>
                            <div class="product-card">
                                <div class="product-image"><img src="<?php echo getProductImage($item); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" onerror="this.src='https://placehold.co/120x120/f39c12/white?text=🍪'"></div>
                                <div class="product-info">
                                    <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="product-price">RM <?php echo number_format($item['price'], 2); ?></div>
                                    <div class="product-qty">
                                        <button class="qty-btn" onclick="changeQty(<?php echo $item['id']; ?>, -1)">-</button>
                                        <input type="number" class="qty-input" id="qty_<?php echo $item['id']; ?>" value="<?php echo $existing_qty; ?>" min="0" max="20" data-id="<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>">
                                        <button class="qty-btn" onclick="changeQty(<?php echo $item['id']; ?>, 1)">+</button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="product-card" style="text-align:center; color:#888;">No snacks available</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Drinks Section -->
            <div id="category-drink" class="product-category-section">
                <div class="product-section">
                    <div class="section-title"><i class="fas fa-tint"></i> 🥤 Drinks</div>
                    <div class="products-grid">
                        <?php if(count($products['drink']) > 0): ?>
                            <?php foreach($products['drink'] as $item): 
                                $existing_qty = $existing_addons[$item['id']] ?? 0;
                            ?>
                            <div class="product-card">
                                <div class="product-image"><img src="<?php echo getProductImage($item); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" onerror="this.src='https://placehold.co/120x120/3498db/white?text=🥤'"></div>
                                <div class="product-info">
                                    <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="product-price">RM <?php echo number_format($item['price'], 2); ?></div>
                                    <div class="product-qty">
                                        <button class="qty-btn" onclick="changeQty(<?php echo $item['id']; ?>, -1)">-</button>
                                        <input type="number" class="qty-input" id="qty_<?php echo $item['id']; ?>" value="<?php echo $existing_qty; ?>" min="0" max="20" data-id="<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>">
                                        <button class="qty-btn" onclick="changeQty(<?php echo $item['id']; ?>, 1)">+</button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="product-card" style="text-align:center; color:#888;">No drinks available</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div>
            <div class="cart-summary">
                <h3><i class="fas fa-shopping-cart"></i> Your Cart</h3>
                <div id="cartItems"></div>
                <div class="cart-total" id="cartTotal"><span>Add-ons Total:</span><span>RM 0.00</span></div>
                
                <form action="process_addons.php" method="POST" id="addonsForm">
                    <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                    <input type="hidden" name="cart_data" id="cartData" value="">
                    <button type="submit" class="btn-continue"><i class="fas fa-arrow-right"></i> Continue to Payment</button>
                </form>
                
                <form action="../Payment_Module/checkout.php" method="GET" id="skipForm">
                    <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                    <input type="hidden" name="amount" value="<?php echo $booking['total_price']; ?>">
                    <button type="submit" class="btn-skip"><i class="fas fa-forward"></i> Skip Add-ons</button>
                </form>

                <a href="book_court.php?court_id=<?php echo $booking['court_id']; ?>" class="btn-back-link">
                    <i class="fas fa-chevron-left"></i> Change Court / Time Selection
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    let cart = [];
    
    // 页面加载时从数据库加载已有 add-ons
    const existingAddons = <?php echo $existing_addons_json; ?>;
    
    function changeQty(productId, delta) {
        const qtyInput = document.getElementById('qty_' + productId);
        if (!qtyInput) return;
        let newVal = parseInt(qtyInput.value) + delta;
        if (newVal < 0) newVal = 0;
        const max = parseInt(qtyInput.getAttribute('max')) || 10;
        if (newVal > max) newVal = max;
        qtyInput.value = newVal;
        updateCart();
    }
    
    function updateCart() {
        cart = [];
        document.querySelectorAll('.qty-input').forEach(input => {
            const qty = parseInt(input.value);
            if (qty > 0) {
                const id = input.getAttribute('data-id');
                const name = input.getAttribute('data-name');
                const price = parseFloat(input.getAttribute('data-price'));
                cart.push({ id, name, qty, price });
            }
        });
        displayCart();
    }
    
    function displayCart() {
        const cartDiv = document.getElementById('cartItems');
        let total = 0;
        let html = '';
        
        if (cart.length === 0) {
            cartDiv.innerHTML = '<div class="empty-cart"><i class="fas fa-shopping-basket"></i><p>No items selected</p></div>';
        } else {
            cart.forEach(item => {
                const itemTotal = item.qty * item.price;
                total += itemTotal;
                html += `<div class="cart-item"><span>${item.name} x${item.qty}</span><span>RM ${itemTotal.toFixed(2)}</span></div>`;
            });
            cartDiv.innerHTML = html;
        }
        
        document.getElementById('cartTotal').innerHTML = `<span>Add-ons Total:</span><span>RM ${total.toFixed(2)}</span>`;
    }
    
    document.getElementById('addonsForm').addEventListener('submit', function(e) {
        document.getElementById('cartData').value = JSON.stringify(cart);
    });
    
    // 监听所有数量输入框的变化
    document.querySelectorAll('.qty-input').forEach(input => {
        input.addEventListener('change', function() { updateCart(); });
    });
    
    // 初始化时加载已有数量
    function loadExistingAddons() {
        for (const [productId, quantity] of Object.entries(existingAddons)) {
            const qtyInput = document.getElementById('qty_' + productId);
            if (qtyInput && quantity > 0) {
                qtyInput.value = quantity;
            }
        }
        updateCart();
    }
    
    loadExistingAddons();
    
    // Category Filter
    const categoryBtns = document.querySelectorAll('.category-btn');
    const categorySections = {
        'racket': document.getElementById('category-racket'),
        'shuttlecock': document.getElementById('category-shuttlecock'),
        'string': document.getElementById('category-string'),
        'grip': document.getElementById('category-grip'),
        'snack': document.getElementById('category-snack'),
        'drink': document.getElementById('category-drink')
    };
    
    function showCategory(category) {
        if (category === 'all') {
            for (let key in categorySections) {
                if (categorySections[key]) categorySections[key].style.display = 'block';
            }
        } else {
            for (let key in categorySections) {
                if (categorySections[key]) categorySections[key].style.display = 'none';
            }
            if (categorySections[category]) categorySections[category].style.display = 'block';
        }
        categoryBtns.forEach(btn => {
            btn.classList.remove('active');
            if (btn.getAttribute('data-category') === category) btn.classList.add('active');
        });
    }
    
    categoryBtns.forEach(btn => {
        btn.addEventListener('click', function() { showCategory(this.getAttribute('data-category')); });
    });
    
    showCategory('all');
</script>
</body>
</html>