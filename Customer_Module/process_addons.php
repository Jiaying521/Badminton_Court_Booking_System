<?php
require_once __DIR__ . '/../config.php';
if (!isLoggedIn()) redirect('homepage.php');

$booking_id = $_POST['booking_id'] ?? 0;
$cart_data = $_POST['cart_data'] ?? '[]';

if (!$booking_id) redirect('dashboard.php');

// 获取预订信息
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ? AND status = 'Pending'");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) redirect('dashboard.php');

// 保存加购商品
$cart = json_decode($cart_data, true);
$addons_total = 0;

foreach ($cart as $item) {
    $product_id = $item['id'];
    $quantity = $item['qty'];
    $price = $item['price'];
    $addons_total += $quantity * $price;
    
    $stmt = $pdo->prepare("INSERT INTO booking_addons (booking_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    $stmt->execute([$booking_id, $product_id, $quantity, $price]);
}

// 更新预订总价
$new_total = $booking['total_price'] + $addons_total;
$stmt = $pdo->prepare("UPDATE bookings SET total_price = ? WHERE id = ?");
$stmt->execute([$new_total, $booking_id]);

// 跳转到支付页面
header("Location: ../Payment_Module/checkout.php?booking_id=$booking_id&amount=$new_total");
exit;
?>