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

// 获取所有产品分类
$categories = [
    'racket' => ['name' => '🏸 Badminton Rackets', 'icon' => 'fa-table-tennis'],
    'string' => ['name' => '🪡 Stringing Service', 'icon' => 'fa-pen-fancy'],
    'shuttlecock' => ['name' => '🏸 Shuttlecocks', 'icon' => 'fa-shuttlecock'],
    'grip' => ['name' => '🎾 Grips / Overgrips', 'icon' => 'fa-hand-peace'],
    'snack' => ['name' => '🍪 Snacks', 'icon' => 'fa-cookie-bite'],
    'drink' => ['name' => '🥤 Drinks', 'icon' => 'fa-tint']
];

// 获取所有产品
$products = [];
foreach ($categories as $cat => $info) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE category = ? AND is_active = 1 ORDER BY price");
    $stmt->execute([$cat]);
    $products[$cat] = $stmt->fetchAll();
}

// 拉力推荐选项
$tensions = [
    ['value' => '22-24', 'label' => '22-24 lbs (Beginner - Control)'],
    ['value' => '24-26', 'label' => '24-26 lbs (Intermediate - Balanced)'],
    ['value' => '26-28', 'label' => '26-28 lbs (Advanced - Power)'],
    ['value' => '28-30', 'label' => '28-30 lbs (Professional - Maximum Control)']
];
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
        .container { max-width:1200px; margin:0 auto; }
        
        /* Progress Bar */
        .progress-bar { display:flex; justify-content:space-between; margin-bottom:2rem; background:white; padding:1rem 2rem; border-radius:60px; }
        .progress-step { text-align:center; flex:1; }
        .progress-step .step-number { width:32px; height:32px; background:#e0e0e0; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; margin-bottom:0.3rem; }
        .progress-step.active .step-number { background:#2b7e3a; color:white; }
        .progress-step.completed .step-number { background:#2b7e3a; color:white; }
        .progress-step .step-label { font-size:0.75rem; color:#888; }
        .progress-step.active .step-label { color:#2b7e3a; font-weight:600; }
        
        .booking-summary { background:#eaf5e6; padding:1rem; border-radius:16px; margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; }
        
        .product-section { background:white; border-radius:24px; padding:1.5rem; margin-bottom:1.5rem; }
        .section-title { font-size:1.3rem; font-weight:700; color:#2b7e3a; margin-bottom:1rem; padding-bottom:0.5rem; border-bottom:2px solid #eaf5e6; }
        .products-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:1rem; }
        .product-item { border:1px solid #e0e0e0; border-radius:16px; padding:1rem; transition:0.2s; }
        .product-item:hover { border-color:#2b7e3a; box-shadow:0 4px 12px rgba(0,0,0,0.05); }
        .product-name { font-weight:700; color:#1e3a2a; }
        .product-price { color:#e67e22; font-weight:600; margin-top:0.3rem; }
        .product-qty { display:flex; align-items:center; gap:0.5rem; margin-top:0.5rem; }
        .qty-btn { width:28px; height:28px; border:1px solid #ddd; background:white; border-radius:8px; cursor:pointer; }
        .qty-input { width:50px; text-align:center; padding:0.3rem; border:1px solid #ddd; border-radius:8px; }
        
        .options-grid { display:flex; gap:0.8rem; flex-wrap:wrap; margin-top:0.5rem; }
        .option-btn { background:#eaf5e6; border:1px solid #c2d5bb; padding:0.5rem 1rem; border-radius:40px; cursor:pointer; transition:0.2s; font-size:0.85rem; }
        .option-btn:hover { background:#c2d5bb; }
        .option-btn.selected { background:#2b7e3a; color:white; border-color:#2b7e3a; }
        
        .row-2cols { display:grid; grid-template-columns:2fr 1fr; gap:1.5rem; }
        .cart-summary { background:white; border-radius:24px; padding:1.5rem; position:sticky; top:2rem; }
        .cart-item { display:flex; justify-content:space-between; padding:0.5rem 0; border-bottom:1px solid #eee; font-size:0.9rem; }
        .cart-total { display:flex; justify-content:space-between; padding:1rem 0; margin-top:0.5rem; border-top:2px solid #2b7e3a; font-weight:700; font-size:1.2rem; }
        .btn-continue { background:#2b7e3a; color:white; border:none; padding:1rem; border-radius:50px; width:100%; font-weight:700; font-size:1rem; cursor:pointer; margin-top:1rem; transition:0.2s; }
        .btn-continue:hover { background:#1f5a2a; transform:translateY(-2px); }
        .btn-skip { background:#e0e0e0; color:#333; border:none; padding:0.8rem; border-radius:50px; width:100%; margin-top:0.5rem; cursor:pointer; }
        
        @media (max-width:768px) { .row-2cols { grid-template-columns:1fr; } body { padding:1rem; } }
    </style>
</head>
<body>
<div class="container">
    <!-- Progress Bar -->
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
        <!-- Left: Products -->
        <div>
            <!-- Rackets Section -->
            <div class="product-section">
                <div class="section-title"><i class="fas fa-table-tennis"></i> <?php echo $categories['racket']['name']; ?></div>
                <div class="products-grid">
                    <?php foreach($products['racket'] as $item): ?>
                    <div class="product-item" data-id="<?php echo $item['id']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>" data-price="<?php echo $item['price']; ?>">
                        <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="product-price">RM <?php echo number_format($item['price'], 2); ?></div>
                        <div class="product-qty">
                            <button class="qty-btn" onclick="changeQty(this, -1)">-</button>
                            <input type="number" class="qty-input" value="0" min="0" max="3" data-id="<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>" onchange="updateCart()">
                            <button class="qty-btn" onclick="changeQty(this, 1)">+</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Shuttlecocks -->
            <div class="product-section">
                <div class="section-title"><i class="fas fa-shuttlecock"></i> <?php echo $categories['shuttlecock']['name']; ?></div>
                <div class="products-grid">
                    <?php foreach($products['shuttlecock'] as $item): ?>
                    <div class="product-item" data-id="<?php echo $item['id']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>" data-price="<?php echo $item['price']; ?>">
                        <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="product-price">RM <?php echo number_format($item['price'], 2); ?> / tube</div>
                        <div class="product-qty">
                            <button class="qty-btn" onclick="changeQty(this, -1)">-</button>
                            <input type="number" class="qty-input" value="0" min="0" max="10" data-id="<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>" onchange="updateCart()">
                            <button class="qty-btn" onclick="changeQty(this, 1)">+</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Grips -->
            <div class="product-section">
                <div class="section-title"><i class="fas fa-hand-peace"></i> <?php echo $categories['grip']['name']; ?></div>
                <div class="products-grid">
                    <?php foreach($products['grip'] as $item): ?>
                    <div class="product-item" data-id="<?php echo $item['id']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>" data-price="<?php echo $item['price']; ?>">
                        <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="product-price">RM <?php echo number_format($item['price'], 2); ?></div>
                        <div class="product-qty">
                            <button class="qty-btn" onclick="changeQty(this, -1)">-</button>
                            <input type="number" class="qty-input" value="0" min="0" max="20" data-id="<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>" onchange="updateCart()">
                            <button class="qty-btn" onclick="changeQty(this, 1)">+</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Snacks -->
            <div class="product-section">
                <div class="section-title"><i class="fas fa-cookie-bite"></i> <?php echo $categories['snack']['name']; ?></div>
                <div class="products-grid">
                    <?php foreach($products['snack'] as $item): ?>
                    <div class="product-item" data-id="<?php echo $item['id']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>" data-price="<?php echo $item['price']; ?>">
                        <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="product-price">RM <?php echo number_format($item['price'], 2); ?></div>
                        <div class="product-qty">
                            <button class="qty-btn" onclick="changeQty(this, -1)">-</button>
                            <input type="number" class="qty-input" value="0" min="0" max="20" data-id="<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>" onchange="updateCart()">
                            <button class="qty-btn" onclick="changeQty(this, 1)">+</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Drinks -->
            <div class="product-section">
                <div class="section-title"><i class="fas fa-tint"></i> <?php echo $categories['drink']['name']; ?></div>
                <div class="products-grid">
                    <?php foreach($products['drink'] as $item): ?>
                    <div class="product-item" data-id="<?php echo $item['id']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>" data-price="<?php echo $item['price']; ?>">
                        <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="product-price">RM <?php echo number_format($item['price'], 2); ?></div>
                        <div class="product-qty">
                            <button class="qty-btn" onclick="changeQty(this, -1)">-</button>
                            <input type="number" class="qty-input" value="0" min="0" max="20" data-id="<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>" onchange="updateCart()">
                            <button class="qty-btn" onclick="changeQty(this, 1)">+</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Right: Cart Summary -->
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
            </div>
        </div>
    </div>
</div>

<script>
    let cart = [];
    
    function changeQty(btn, delta) {
        const productDiv = btn.closest('.product-item');
        const qtyInput = productDiv.querySelector('.qty-input');
        let newVal = parseInt(qtyInput.value) + delta;
        if (newVal < 0) newVal = 0;
        const max = parseInt(qtyInput.getAttribute('max')) || 10;
        if (newVal > max) newVal = max;
        qtyInput.value = newVal;
        updateCart();
    }
    
    function updateCart() {
        cart = [];
        document.querySelectorAll('.product-item').forEach(item => {
            const qtyInput = item.querySelector('.qty-input');
            const qty = parseInt(qtyInput.value);
            if (qty > 0) {
                const id = qtyInput.getAttribute('data-id');
                const name = item.querySelector('.product-name').innerText;
                const price = parseFloat(qtyInput.getAttribute('data-price'));
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
        document.getElementById('cartTotal').innerHTML = `Add-ons Total: RM ${total.toFixed(2)}<br><small style="font-size:0.8rem;">Court fee will be added later</small>`;
    }
    
    document.getElementById('addonsForm').addEventListener('submit', function(e) {
        document.getElementById('cartData').value = JSON.stringify(cart);
    });
    
    updateCart();
</script>
</body>
</html>