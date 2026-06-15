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

// 还原场地基础费用（total_price 可能已包含之前加购的金额）
$stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity * price), 0) AS total FROM booking_addons WHERE booking_id = ?");
$stmt->execute([$booking_id]);
$existing_addons_total = $stmt->fetchColumn();
$base_price = $booking['total_price'] - $existing_addons_total;

// 清除旧的加购记录，避免用户返回后重复插入/重复计费
$stmt = $pdo->prepare("DELETE FROM booking_addons WHERE booking_id = ?");
$stmt->execute([$booking_id]);

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

// 更新预订总价（基础场地费 + 新的加购金额）
$new_total = $base_price + $addons_total;
$stmt = $pdo->prepare("UPDATE bookings SET total_price = ? WHERE id = ?");
$stmt->execute([$new_total, $booking_id]);

// 跳转到支付页面
header("Location: ../Payment_Module/checkout.php?booking_id=$booking_id&amount=$new_total");
exit;
?>