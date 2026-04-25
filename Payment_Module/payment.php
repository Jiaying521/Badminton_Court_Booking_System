<?php
include 'db.php';

// Get booking_id from URL e.g. payment.php?booking_id=1
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

// Fetch booking details from database
$sql = "SELECT b.*, u.name as customer_name, c.name as court_name 
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN courts c ON b.court_id = c.id
        WHERE b.id = $booking_id AND b.status = 'Confirmed'";

$result = mysqli_query($conn, $sql);
$booking = mysqli_fetch_assoc($result);

// If booking not found, stop
if (!$booking) {
    die("<h2>Booking not found or not confirmed yet.</h2>");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Badminton Hub</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="payment-container">

    <!-- Booking Summary -->
    <div class="booking-summary">
        <h2>🏸 Booking Summary</h2>
        <table class="summary-table">
            <tr><td>Customer</td><td><?php echo htmlspecialchars($booking['customer_name']); ?></td></tr>
            <tr><td>Court</td><td><?php echo htmlspecialchars($booking['court_name']); ?></td></tr>
            <tr><td>Date</td><td><?php echo $booking['booking_date']; ?></td></tr>
            <tr><td>Time</td><td><?php echo $booking['start_time']; ?> - <?php echo $booking['end_time']; ?></td></tr>
            <tr><td>Session Type</td><td><?php echo $booking['session_type']; ?></td></tr>
            <tr class="total-row"><td>Total Amount</td><td>RM <?php echo number_format($booking['total_price'], 2); ?></td></tr>
        </table>
    </div>

    <!-- Payment Form -->
    <div class="payment-form">
        <h2>💳 Payment Details</h2>
        <form action="process_payment.php" method="POST">
            <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
            <input type="hidden" name="amount" value="<?php echo $booking['total_price']; ?>">

            <!-- Payment Method -->
            <div class="form-group">
                <label>Payment Method</label>
                <div class="payment-methods">
                    <label class="method-option">
                        <input type="radio" name="payment_method" value="Credit Card" checked> 💳 Credit Card
                    </label>
                    <label class="method-option">
                        <input type="radio" name="payment_method" value="Debit Card"> 🏦 Debit Card
                    </label>
                    <label class="method-option">
                        <input type="radio" name="payment_method" value="Online Banking"> 🌐 Online Banking
                    </label>
                </div>
            </div>

            <!-- Card Details -->
            <div class="form-group" id="card-details">
                <label>Cardholder Name</label>
                <input type="text" name="card_name" placeholder="Name on card" required>

                <label>Card Number</label>
                <input type="text" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19" id="card-number" required>

                <div class="card-row">
                    <div>
                        <label>Expiry Date</label>
                        <input type="text" name="expiry" placeholder="MM/YY" maxlength="5" required>
                    </div>
                    <div>
                        <label>CVV</label>
                        <input type="text" name="cvv" placeholder="123" maxlength="3" required>
                    </div>
                </div>
            </div>

            <!-- Discount -->
            <div class="form-group">
                <label>Discount Code (optional)</label>
                <div class="discount-row">
                    <input type="text" name="discount_code" id="discount_code" placeholder="Enter code">
                    <button type="button" onclick="applyDiscount()">Apply</button>
                </div>
                <p id="discount-msg"></p>
                <input type="hidden" name="discount_applied" id="discount_applied" value="0">
            </div>

            <!-- Final Amount Display -->
            <div class="final-amount">
                <span>Final Amount:</span>
                <span id="final-display">RM <?php echo number_format($booking['total_price'], 2); ?></span>
                <input type="hidden" name="final_amount" id="final_amount" value="<?php echo $booking['total_price']; ?>">
            </div>

            <button type="submit" class="pay-btn">Pay Now</button>
        </form>
    </div>

</div>

<script>
// Format card number with spaces
document.getElementById('card-number').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '').substring(0, 16);
    e.target.value = value.replace(/(.{4})/g, '$1 ').trim();
});

// Apply discount code (demo - code "BADMINTON10" = 10% off)
function applyDiscount() {
    const code = document.getElementById('discount_code').value.trim().toUpperCase();
    const originalAmount = <?php echo $booking['total_price']; ?>;
    const msg = document.getElementById('discount-msg');

    if (code === 'BADMINTON10') {
        const discount = originalAmount * 0.10;
        const final = originalAmount - discount;
        document.getElementById('discount_applied').value = discount.toFixed(2);
        document.getElementById('final_amount').value = final.toFixed(2);
        document.getElementById('final-display').textContent = 'RM ' + final.toFixed(2);
        msg.style.color = 'green';
        msg.textContent = '✅ 10% discount applied! You save RM ' + discount.toFixed(2);
    } else if (code === '') {
        msg.textContent = '';
    } else {
        msg.style.color = 'red';
        msg.textContent = '❌ Invalid discount code.';
    }
}
</script>

</body>
</html>