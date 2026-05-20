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

// ========== 获取所有产品 ==========
$products = [
    'racket' => [],
    'shuttlecock' => [],
    'grip' => [],
    'string' => [],      // 新增：球线
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

// 获取球线 (strings)
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

// 获取产品图片 - 从数据库读取
function getProductImage($product) {
    // 1. 优先使用数据库中的 image_url
    if (!empty($product['image_url'])) {
        $imagePath = $product['image_url'];
        
        // 构建完整路径
        $fullPath = '../Admin_Module/Pictures/products/' . $imagePath;
        
        // 检查文件是否存在
        if (file_exists(__DIR__ . '/' . $fullPath)) {
            return $fullPath;
        }
        
        // 如果文件不存在，也返回路径让浏览器尝试
        return $fullPath;
    }
    
    // 2. 根据分类返回默认占位图
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
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add-ons | Smash Arena</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#f5f9f0; padding:2rem; }
        .container { max-width:1400px; margin:0 auto; }
        
        .progress-bar { display:flex; justify-content:space-between; margin-bottom:2rem; background:white; padding:1rem 2rem; border-radius:60px; }
        .progress-step { text-align:center; flex:1; }
        .progress-step .step-number { width:32px; height:32px; background:#e0e0e0; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; margin-bottom:0.3rem; }
        .progress-step.active .step-number { background:#2b7e3a; color:white; }
        .progress-step.completed .step-number { background:#2b7e3a; color:white; }
        .progress-step .step-label { font-size:0.75rem; color:#888; }
        .progress-step.active .step-label { color:#2b7e3a; font-weight:600; }
        
        .booking-summary { background:#eaf5e6; padding:1rem; border-radius:16px; margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; }
        
        .row-2cols { display:grid; grid-template-columns:2fr 1fr; gap:1.5rem; }
        
        .product-section { background:white; border-radius:24px; padding:1.5rem; margin-bottom:1.5rem; }
        .section-title { font-size:1.3rem; font-weight:700; color:#2b7e3a; margin-bottom:1rem; padding-bottom:0.5rem; border-bottom:2px solid #eaf5e6; }
        .products-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:1rem; }
        .product-card { background:white; border:1px solid #e0e0e0; border-radius:16px; padding:1rem; display:flex; gap:1rem; transition:0.2s; }
        .product-card:hover { border-color:#2b7e3a; box-shadow:0 4px 12px rgba(0,0,0,0.08); }
        .product-image { width:70px; height:70px; border-radius:12px; overflow:hidden; background:#e8efe2; flex-shrink:0; display:flex; align-items:center; justify-content:center; }
        .product-image img { width:100%; height:100%; object-fit:cover; }
        .product-info { flex:1; }
        .product-name { font-weight:700; color:#1e3a2a; margin-bottom:0.2rem; font-size:0.9rem; }
        .product-price { color:#e67e22; font-weight:600; font-size:0.85rem; }
        .product-qty { display:flex; align-items:center; gap:0.5rem; margin-top:0.5rem; }
        .qty-btn { width:28px; height:28px; border:1px solid #ddd; background:white; border-radius:8px; cursor:pointer; font-weight:600; }
        .qty-btn:hover { background:#2b7e3a; color:white; border-color:#2b7e3a; }
        .qty-input { width:50px; text-align:center; padding:0.3rem; border:1px solid #ddd; border-radius:8px; }
        
        .cart-summary { background:white; border-radius:24px; padding:1.5rem; position:sticky; top:2rem; }
        .cart-item { display:flex; justify-content:space-between; padding:0.5rem 0; border-bottom:1px solid #eee; font-size:0.9rem; }
        .cart-total { display:flex; justify-content:space-between; padding:1rem 0; margin-top:0.5rem; border-top:2px solid #2b7e3a; font-weight:700; font-size:1.2rem; color:#2b7e3a; }
        .btn-continue { background:#2b7e3a; color:white; border:none; padding:1rem; border-radius:50px; width:100%; font-weight:700; font-size:1rem; cursor:pointer; margin-top:1rem; transition:0.2s; }
        .btn-continue:hover { background:#1f5a2a; transform:translateY(-2px); }
        .btn-skip { background:#e0e0e0; color:#333; border:none; padding:0.8rem; border-radius:50px; width:100%; margin-top:0.5rem; cursor:pointer; }
        
        /* 🟢 BACK STEP LINK HOVER STYLING */
        .btn-back-link { display: block; text-align: center; color: #666; text-decoration: none; font-size: 0.85rem; font-weight: 600; margin-top: 15px; transition: 0.2s; }
        .btn-back-link:hover { color: #ff4d4d; text-decoration: underline; }

        @media (max-width:768px) { .row-2cols { grid-template-columns:1fr; } body { padding:1rem; } }
    </style>
</head>
<body>
<div class="container">
    <div class="progress-bar">
        <div class="progress-step completed"><div class="step-number">1</div><div class="step-label">Court</div></div>
        <div class="progress-step completed"><div class="step-number">2</div><div class="step-label">Time</div></div>
        <div class="progress-step active"><div class="step-number">3</div><div class="step-label">Add-ons</div></div>
        <div class="progress-step"><div class="step-number">4</div><div class="step-label">Payment</div></div>
    </div>
    
    <div class="booking-summary">
        <div>
            <strong>📅 <?php echo htmlspecialchars($booking['court_name']); ?></strong><br>
            <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?> • <?php echo date('h:i A', strtotime($booking['start_time'])); ?> - <?php echo date('h:i A', strtotime($booking['end_time'])); ?>
        </div>
        <div>
            Court Fee: <strong>RM <?php echo number_format($booking['total_price'], 2); ?></strong>
        </div>
    </div>
    
    <div class="row-2cols">
        <div>
            <div class="product-section">
                <div class="section-title"><i class="fas fa-table-tennis"></i> 🏸 Badminton Rackets</div>
                <div class="products-grid">
                    <?php if(count($products['racket']) > 0): ?>
                        <?php foreach($products['racket'] as $item): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="<?php echo getProductImage($item); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     onerror="this.src='https://placehold.co/120x120/2b7e3a/white?text=🏸'">
                            </div>
                            <div class="product-info">
                                <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="product-price">RM <?php echo number_format($item['price'], 2); ?></div>
                                <div class="product-qty">
                                    <button class="qty-btn" onclick="changeQty(<?php echo $item['id']; ?>, -1)">-</button>
                                    <input type="number" class="qty-input" id="qty_<?php echo $item['id']; ?>" value="0" min="0" max="3" data-id="<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>">
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
            
            <div class="product-section">
                <div class="section-title"><i class="fas fa-shuttlecock"></i> 🏸 Shuttlecocks</div>
                <div class="products-grid">
                    <?php if(count($products['shuttlecock']) > 0): ?>
                        <?php foreach($products['shuttlecock'] as $item): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="<?php echo getProductImage($item); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     onerror="this.src='https://placehold.co/120x120/2b7e3a/white?text=🏸'">
                            </div>
                            <div class="product-info">
                                <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="product-price">RM <?php echo number_format($item['price'], 2); ?> / tube</div>
                                <div class="product-qty">
                                    <button class="qty-btn" onclick="changeQty(<?php echo $item['id']; ?>, -1)">-</button>
                                    <input type="number" class="qty-input" id="qty_<?php echo $item['id']; ?>" value="0" min="0" max="10" data-id="<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>">
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
            
            <div class="product-section">
                <div class="section-title"><i class="fas fa-thread"></i> 🧵 Badminton Strings</div>
                <div class="products-grid">
                    <?php if(count($products['string']) > 0): ?>
                        <?php foreach($products['string'] as $item): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="<?php echo getProductImage($item); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     onerror="this.src='https://placehold.co/120x120/2b7e3a/white?text=🧵'">
                            </div>
                            <div class="product-info">
                                <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="product-price">RM <?php echo number_format($item['price'], 2); ?></div>
                                <div class="product-qty">
                                    <button class="qty-btn" onclick="changeQty(<?php echo $item['id']; ?>, -1)">-</button>
                                    <input type="number" class="qty-input" id="qty_<?php echo $item['id']; ?>" value="0" min="0" max="10" data-id="<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>">
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
            
            <div class="product-section">
                <div class="section-title"><i class="fas fa-hand-peace"></i> 🎾 Grips / Overgrips</div>
                <div class="products-grid">
                    <?php if(count($products['grip']) > 0): ?>
                        <?php foreach($products['grip'] as $item): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="<?php echo getProductImage($item); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     onerror="this.src='https://placehold.co/120x120/2b7e3a/white?text=🎾'">
                            </div>
                            <div class="product-info">
                                <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="product-price">RM <?php echo number_format($item['price'], 2); ?></div>
                                <div class="product-qty">
                                    <button class="qty-btn" onclick="changeQty(<?php echo $item['id']; ?>, -1)">-</button>
                                    <input type="number" class="qty-input" id="qty_<?php echo $item['id']; ?>" value="0" min="0" max="20" data-id="<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>">
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
            
            <div class="product-section">
                <div class="section-title"><i class="fas fa-cookie-bite"></i> 🍪 Snacks</div>
                <div class="products-grid">
                    <?php if(count($products['snack']) > 0): ?>
                        <?php foreach($products['snack'] as $item): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="<?php echo getProductImage($item); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     onerror="this.src='https://placehold.co/120x120/f39c12/white?text=🍪'">
                            </div>
                            <div class="product-info">
                                <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="product-price">RM <?php echo number_format($item['price'], 2); ?></div>
                                <div class="product-qty">
                                    <button class="qty-btn" onclick="changeQty(<?php echo $item['id']; ?>, -1)">-</button>
                                    <input type="number" class="qty-input" id="qty_<?php echo $item['id']; ?>" value="0" min="0" max="20" data-id="<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>">
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
            
            <div class="product-section">
                <div class="section-title"><i class="fas fa-tint"></i> 🥤 Drinks</div>
                <div class="products-grid">
                    <?php if(count($products['drink']) > 0): ?>
                        <?php foreach($products['drink'] as $item): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="<?php echo getProductImage($item); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     onerror="this.src='https://placehold.co/120x120/3498db/white?text=🥤'">
                            </div>
                            <div class="product-info">
                                <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="product-price">RM <?php echo number_format($item['price'], 2); ?></div>
                                <div class="product-qty">
                                    <button class="qty-btn" onclick="changeQty(<?php echo $item['id']; ?>, -1)">-</button>
                                    <input type="number" class="qty-input" id="qty_<?php echo $item['id']; ?>" value="0" min="0" max="20" data-id="<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>">
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
        
        <div>
            <div class="cart-summary">
                <h3><i class="fas fa-shopping-cart"></i> Your Cart</h3>
                <div id="cartItems"></div>
                <div class="cart-total" id="cartTotal">Total: RM 0.00</div>
                
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
        
        cart.forEach(item => {
            const itemTotal = item.qty * item.price;
            total += itemTotal;
            html += `
                <div class="cart-item">
                    <span>${item.name} x${item.qty}</span>
                    <span>RM ${itemTotal.toFixed(2)}</span>
                </div>
            `;
        });
        
        cartDiv.innerHTML = html || '<div style="color:#888; text-align:center; padding:1rem;">No items selected</div>';
        document.getElementById('cartTotal').innerHTML = `Add-ons Total: RM ${total.toFixed(2)}<br><small style="font-size:0.75rem; color:#888;">Court fee will be added later</small>`;
    }
    
    document.getElementById('addonsForm').addEventListener('submit', function(e) {
        document.getElementById('cartData').value = JSON.stringify(cart);
    });
    
    document.querySelectorAll('.qty-input').forEach(input => {
        input.addEventListener('change', function() {
            updateCart();
        });
    });
    
    updateCart();
</script>
</body>
</html>