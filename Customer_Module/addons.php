<?php
// ============================================================
// addons.php - Customer Add-ons Selection Page
// Allows users to add products (rackets, shuttlecocks, etc.) to their booking
// ============================================================

require_once __DIR__ . '/../config.php';

// Check if user is logged in, redirect to homepage if not
if (!isLoggedIn()) redirect('homepage.php');

$booking_id = $_GET['booking_id'] ?? 0;
if (!$booking_id) redirect('dashboard.php');

// ============================================================
// FETCH BOOKING DETAILS
// ============================================================
$stmt = $pdo->prepare("
    SELECT b.*, c.court_name, c.court_type 
    FROM bookings b 
    JOIN courts c ON b.court_id = c.id 
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) redirect('dashboard.php');

// ============================================================
// FETCH EXISTING ADD-ONS (for pre-filling)
// ============================================================
$existing_addons = [];
$stmt_existing = $pdo->prepare("SELECT product_id, quantity, price FROM booking_addons WHERE booking_id = ?");
$stmt_existing->execute([$booking_id]);
$existing_addons_raw = $stmt_existing->fetchAll();

foreach ($existing_addons_raw as $item) {
    $existing_addons[$item['product_id']] = $item['quantity'];
}

// ============================================================
// FETCH ALL PRODUCTS FROM DATABASE GROUPED BY CATEGORY
// ============================================================
$products = [];

// Get all distinct categories from products table
$stmt = $pdo->prepare("SELECT DISTINCT category FROM products WHERE is_active = 1 ORDER BY category");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch products for each category
foreach ($categories as $category) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE category = ? AND is_active = 1 ORDER BY price");
    $stmt->execute([$category]);
    $products[$category] = $stmt->fetchAll();
}

// ============================================================
// CATEGORY DISPLAY CONFIGURATION
// ============================================================
$categoryConfig = [
    'racket' => [
        'icon' => 'fa-table-tennis',
        'label' => '🏸 Badminton Rackets',
        'max_qty' => 3,
        'default_img' => 'https://placehold.co/120x120/2b7e3a/white?text=🏸'
    ],
    'shuttlecock' => [
        'icon' => 'fa-shuttlecock',
        'label' => '🏸 Shuttlecocks',
        'max_qty' => 10,
        'default_img' => 'https://placehold.co/120x120/2b7e3a/white?text=🏸'
    ],
    'grip' => [
        'icon' => 'fa-hand-peace',
        'label' => '🎾 Grips / Overgrips',
        'max_qty' => 20,
        'default_img' => 'https://placehold.co/120x120/2b7e3a/white?text=🎾'
    ],
    'string' => [
        'icon' => 'fa-thread',
        'label' => '🧵 Badminton Strings',
        'max_qty' => 20,
        'default_img' => 'https://placehold.co/120x120/2b7e3a/white?text=🧵'
    ],
    'snack' => [
        'icon' => 'fa-cookie-bite',
        'label' => '🍪 Snacks',
        'max_qty' => 20,
        'default_img' => 'https://placehold.co/120x120/f39c12/white?text=🍪'
    ],
    'drink' => [
        'icon' => 'fa-tint',
        'label' => '🥤 Drinks',
        'max_qty' => 20,
        'default_img' => 'https://placehold.co/120x120/3498db/white?text=🥤'
    ]
];

// Default config for unknown categories
$defaultConfig = [
    'icon' => 'fa-cube',
    'label' => '📦 Products',
    'max_qty' => 10,
    'default_img' => 'https://placehold.co/120x120/2b7e3a/white?text=📦'
];

// ============================================================
// HELPER FUNCTION: GET PRODUCT IMAGE
// ============================================================
function getProductImage($product) {
    // Check if image exists in Pictures folder
    if (!empty($product['image_url'])) {
        // Try different path variations
        $paths = [
            '../Pictures/Admin_Module/products/' . $product['image_url'],
            '../Pictures/Admin_Module/products/' . basename($product['image_url']),
            '../Pictures/' . $product['image_url']
        ];
        
        foreach ($paths as $path) {
            if (file_exists(__DIR__ . '/' . $path)) {
                return $path;
            }
        }
        
        // If file doesn't exist but we have a URL, return it
        if (filter_var($product['image_url'], FILTER_VALIDATE_URL)) {
            return $product['image_url'];
        }
    }
    
    // Default images based on category
    $defaultImages = [
        'racket' => 'https://placehold.co/120x120/2b7e3a/white?text=🏸',
        'shuttlecock' => 'https://placehold.co/120x120/2b7e3a/white?text=🏸',
        'grip' => 'https://placehold.co/120x120/2b7e3a/white?text=🎾',
        'string' => 'https://placehold.co/120x120/2b7e3a/white?text=🧵',
        'snack' => 'https://placehold.co/120x120/f39c12/white?text=🍪',
        'drink' => 'https://placehold.co/120x120/3498db/white?text=🥤'
    ];
    
    $category = $product['category'] ?? 'racket';
    return $defaultImages[$category] ?? 'https://placehold.co/120x120/2b7e3a/white?text=🏸';
}

// ============================================================
// GET CATEGORY CONFIG
// ============================================================
function getCategoryConfig($category) {
    global $categoryConfig, $defaultConfig;
    return $categoryConfig[$category] ?? $defaultConfig;
}

// Convert PHP array to JavaScript object
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
            width: 55px; 
            text-align: center; 
            padding: 0.4rem; 
            border: 1px solid #ddd;
            border-radius: 12px; 
            font-weight: 600; 
            background: rgba(254,253,248,0.9);
            -moz-appearance: textfield;
        }
        .qty-input::-webkit-outer-spin-button,
        .qty-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .qty-input[type="number"] {
            -moz-appearance: textfield;
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
        .no-products-msg { text-align: center; padding: 1.5rem; color: #999; font-style: italic; }
        
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
    <!-- ============================================================
         PROGRESS BAR
    ============================================================ -->
    <div class="progress-bar">
        <div class="progress-step completed"><div class="step-number"><i class="fas fa-check"></i></div><div class="step-label">Court</div></div>
        <div class="progress-step completed"><div class="step-number"><i class="fas fa-check"></i></div><div class="step-label">Time</div></div>
        <div class="progress-step active"><div class="step-number">3</div><div class="step-label">Add-ons</div></div>
        <div class="progress-step"><div class="step-number">4</div><div class="step-label">Payment</div></div>
    </div>
    
    <!-- ============================================================
         BOOKING SUMMARY BANNER
    ============================================================ -->
    <div class="booking-summary">
        <div><i class="fas fa-calendar-alt"></i> <strong><?php echo htmlspecialchars($booking['court_name']); ?></strong><br><?php echo date('M j, Y', strtotime($booking['booking_date'])); ?> • <?php echo date('h:i A', strtotime($booking['start_time'])); ?> - <?php echo date('h:i A', strtotime($booking['end_time'])); ?></div>
        <div>Court Fee: <strong>RM <?php echo number_format($booking['total_price'], 2); ?></strong></div>
    </div>
    
    <div class="row-2cols">
        <!-- ============================================================
             LEFT COLUMN - PRODUCTS
        ============================================================ -->
        <div>
            <!-- Category Tabs - Dynamically generated -->
            <div class="category-tabs">
                <button class="category-btn active" data-category="all"><i class="fas fa-th-large"></i> All</button>
                <?php foreach ($categories as $cat): 
                    $config = getCategoryConfig($cat);
                ?>
                <button class="category-btn" data-category="<?php echo htmlspecialchars($cat); ?>">
                    <i class="fas <?php echo htmlspecialchars($config['icon']); ?>"></i> 
                    <?php echo ucfirst(htmlspecialchars($cat)); ?>
                </button>
                <?php endforeach; ?>
            </div>
            
            <!-- Product Sections - Dynamically generated -->
            <?php foreach ($categories as $cat): 
                $config = getCategoryConfig($cat);
                $catProducts = $products[$cat] ?? [];
            ?>
            <div id="category-<?php echo htmlspecialchars($cat); ?>" class="product-category-section">
                <div class="product-section">
                    <div class="section-title">
                        <i class="fas <?php echo htmlspecialchars($config['icon']); ?>"></i> 
                        <?php echo htmlspecialchars($config['label']); ?>
                    </div>
                    <div class="products-grid">
                        <?php if(count($catProducts) > 0): ?>
                            <?php foreach($catProducts as $item): 
                                $existing_qty = $existing_addons[$item['id']] ?? 0;
                                $maxQty = $config['max_qty'];
                                $imgSrc = getProductImage($item);
                            ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <img src="<?php echo htmlspecialchars($imgSrc); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                         onerror="this.src='<?php echo htmlspecialchars($config['default_img']); ?>'">
                                </div>
                                <div class="product-info">
                                    <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="product-price">RM <?php echo number_format($item['price'], 2); ?></div>
                                    <div class="product-qty">
                                        <button type="button" class="qty-btn" onclick="changeQty(<?php echo $item['id']; ?>, -1)">-</button>
                                        <input type="number" class="qty-input" id="qty_<?php echo $item['id']; ?>" 
                                               value="<?php echo $existing_qty; ?>" min="0" max="<?php echo $maxQty; ?>" 
                                               data-id="<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>" 
                                               data-name="<?php echo htmlspecialchars($item['name']); ?>" 
                                               data-max="<?php echo $maxQty; ?>">
                                        <button type="button" class="qty-btn" onclick="changeQty(<?php echo $item['id']; ?>, 1)">+</button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-products-msg">No items available in this category</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- ============================================================
             RIGHT COLUMN - CART SUMMARY
        ============================================================ -->
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

<!-- ============================================================
     JAVASCRIPT FUNCTIONS
============================================================ -->
<script>
    // ============================================================
    // STATE VARIABLES
    // ============================================================
    let cart = [];
    const existingAddons = <?php echo $existing_addons_json; ?>;
    
    // ============================================================
    // CHANGE QUANTITY (with validation)
    // ============================================================
    function changeQty(productId, delta) {
        const qtyInput = document.getElementById('qty_' + productId);
        if (!qtyInput) return;
        
        let currentVal = parseInt(qtyInput.value) || 0;
        let newVal = currentVal + delta;
        const max = parseInt(qtyInput.getAttribute('max')) || 10;
        if (newVal < 0) newVal = 0;
        if (newVal > max) newVal = max;
        
        qtyInput.value = newVal;
        updateCart();
    }
    
    // ============================================================
    // VALIDATE SINGLE INPUT (called on blur/change)
    // ============================================================
    function validateQtyInput(input) {
        let rawValue = input.value.trim();
        rawValue = rawValue.replace(/[^0-9]/g, '');
        if (rawValue === '') rawValue = '0';
        
        let val = parseInt(rawValue, 10);
        const max = parseInt(input.getAttribute('max')) || 10;
        const min = parseInt(input.getAttribute('min')) || 0;
        
        if (isNaN(val) || val < min) val = 0;
        if (val > max) val = max;
        
        input.value = val;
        updateCart();
    }
    
    // ============================================================
    // UPDATE CART
    // ============================================================
    function updateCart() {
        cart = [];
        document.querySelectorAll('.qty-input').forEach(input => {
            let val = input.value.trim().replace(/[^0-9]/g, '');
            const qty = parseInt(val) || 0;
            
            if (qty > 0) {
                const id = input.getAttribute('data-id');
                const name = input.getAttribute('data-name');
                const price = parseFloat(input.getAttribute('data-price'));
                cart.push({ id: parseInt(id), name: name, qty: qty, price: price });
            }
        });
        displayCart();
    }
    
    // ============================================================
    // DISPLAY CART
    // ============================================================
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
    
    // ============================================================
    // FORM SUBMISSION
    // ============================================================
    document.getElementById('addonsForm').addEventListener('submit', function(e) {
        document.getElementById('cartData').value = JSON.stringify(cart);
    });
    
    // ============================================================
    // LOAD EXISTING ADD-ONS
    // ============================================================
    function loadExistingAddons() {
        for (const [productId, quantity] of Object.entries(existingAddons)) {
            const qtyInput = document.getElementById('qty_' + productId);
            if (qtyInput && quantity > 0) {
                qtyInput.value = quantity;
            }
        }
        updateCart();
    }
    
    // ============================================================
    // EVENT LISTENERS FOR INPUT VALIDATION
    // ============================================================
    document.querySelectorAll('.qty-input').forEach(input => {
        input.addEventListener('blur', function() {
            validateQtyInput(this);
        });
        
        input.addEventListener('change', function() {
            validateQtyInput(this);
        });
        
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        
        input.addEventListener('paste', function(e) {
            const pastedData = (e.clipboardData || window.clipboardData).getData('text');
            if (!/^\d*$/.test(pastedData)) {
                e.preventDefault();
            }
        });
        
        input.addEventListener('keydown', function(e) {
            const allowedKeys = [
                'Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 
                'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'
            ];
            if (allowedKeys.includes(e.key)) return;
            if (e.ctrlKey && ['a', 'c', 'v', 'x'].includes(e.key.toLowerCase())) return;
            if (!/^[0-9]$/.test(e.key)) {
                e.preventDefault();
            }
        });
    });
    
    // ============================================================
    // CATEGORY FILTER
    // ============================================================
    const categoryBtns = document.querySelectorAll('.category-btn');
    const categorySections = {};
    
    <?php foreach ($categories as $cat): ?>
    categorySections['<?php echo htmlspecialchars($cat); ?>'] = document.getElementById('category-<?php echo htmlspecialchars($cat); ?>');
    <?php endforeach; ?>
    
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
    
    // ============================================================
    // INITIALIZE
    // ============================================================
    loadExistingAddons();
    showCategory('all');
</script>
</body>
</html>