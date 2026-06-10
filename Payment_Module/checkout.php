<?php
// Start the session so we can grab the user's login info
session_start();
// Connect to our badminton hub database tables
include 'db_connect.php';

// 1. FETCH REAL BALANCE FROM DATABASE
// Get the logged-in user's ID, if they aren't logged in, default to 0
$user_id = $_SESSION['user_id'] ?? 0;
// Start their wallet balance at 0 before we pull the real number
$real_balance = 0.00;

// If we have a valid logged-in user
if ($user_id > 0) {
    // Look up the wallet balance for this specific user ID
    $stmt_bal = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    // Link the user ID safely to prevent hackers from messing with our SQL
    $stmt_bal->bind_param("i", $user_id);
    // Run the query
    $stmt_bal->execute();
    // Get the data back from the database
    $res_bal = $stmt_bal->get_result();
    // Turn the data into a clean array row we can read
    if ($row_bal = $res_bal->fetch_assoc()) {
        // Grab their real money balance and save it here
        $real_balance = $row_bal['wallet_balance'];
    }
}

// Grab the booking ID and court price from the URL address bar links
$booking_id = $_GET['booking_id'] ?? 0;
$amount = $_GET['amount'] ?? 0;

// Make sure the booking ID actually exists before running the page
if($booking_id) {
    // Join bookings with courts so we can pull the readable court names
    $check_sql = "
        SELECT bookings.*, courts.court_name 
        FROM bookings 
        JOIN courts ON bookings.court_id = courts.id
        WHERE bookings.id = '$booking_id' AND bookings.status = 'Pending'
    ";
    // Run the query directly on our database link
    $check_result = $conn->query($check_sql);
    
    // If the booking doesn't exist or isn't marked as 'Pending'
    if($check_result->num_rows == 0) {
        // Kill the page immediately and show an error message
        die("Error: Booking not found.");
    }
    
    // Fetch the booking record array row
    $booking_data = $check_result->fetch_assoc();
    // Store variables to print out on the screen below
    $court_id = $booking_data['court_id']; 
    $court_name = $booking_data['court_name'];
    $booking_date = $booking_data['booking_date']; 
    // Stitch the start time and end time together into a clean slot string
    $time_slot = $booking_data['start_time'] . ' to ' . $booking_data['end_time'];

    // 2. FETCH AVAILABLE UNUSED VOUCHERS REDEEMED BY PLAYER
    // Make an empty list box to store their claimable coupons
    $available_vouchers = [];
    // SQL query to look up vouchers they bought with points that haven't been used yet
    $v_sql = "
        SELECT uv.id AS user_voucher_id, v.title, v.discount_amount 
        FROM user_vouchers uv
        JOIN voucher v ON uv.voucher_id = v.id
        WHERE uv.user_id = '$user_id' AND uv.is_used = 0
    ";
    $v_result = $conn->query($v_sql);
    // If the database gives us the vouchers cleanly
    if($v_result) {
        // Loop through every single unused voucher we found for this user
        while($v_row = $v_result->fetch_assoc()) {
            // Push each voucher into our list array box
            $available_vouchers[] = $v_row;
        }
    }

} else {
    // If there's no booking ID in the URL, crash it right here
    die("Error: Missing booking ID.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment | Smash Arena</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

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

.container {
    max-width: 1400px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
}

.progress-bar {
    display: flex;
    justify-content: space-between;
    margin-bottom: 2rem;
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(10px);
    padding: 0.8rem 2rem;
    border-radius: 80px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.3);
    animation: fadeInDown 0.6s ease-out;
} /* Layout steps header bar code styles — animated */

@keyframes fadeInDown {
    from { 
        opacity: 0; 
        transform: translateY(-30px); 
    }
    to { 
        opacity: 1; 
        transform: translateY(0); 
    }
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
    box-shadow: 0 0 0 4px rgba(43, 126, 58, 0.2);
    animation: pulseStep 2s ease-in-out infinite;
}

@keyframes pulseStep {
    0%, 100% { 
        box-shadow: 0 0 0 0px rgba(43, 126, 58, 0.4); 
    }
    50% { 
        box-shadow: 0 0 0 6px rgba(43, 126, 58, 0.1); 
    }
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

.row-2cols { 
    display: grid; 
    grid-template-columns: 2fr 1fr; 
    gap: 1.5rem; 
} /* Split layout container: Left column is 2x wider */

.payment-section {
    background: rgba(255,255,255,0.7);
    backdrop-filter: blur(10px);
    border-radius: 28px;
    padding: 1.8rem;
    margin-bottom: 1.5rem;
    text-align: left;
    border: 1px solid rgba(255,255,255,0.3);
}

.section-title {
    font-family: 'Montserrat', 'Poppins', sans-serif;
    font-size: 1.3rem;
    font-weight: 700;
    color: #2b7e3a;
    margin-bottom: 1.5rem;
    padding-bottom: 0.7rem;
    border-bottom: 2px solid rgba(234,245,230,0.8);
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.payment-option-card { 
    border: 1px solid #e0e0e0; 
    padding: 20px; 
    border-radius: 16px; 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    margin-bottom: 15px; 
    background: white; 
    transition: 0.2s; 
    cursor: pointer; 
} /* Interactive clickable card selection items buttons */

.payment-option-card:hover { 
    border-color: #2b7e3a; 
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); 
}

.fee-badge { 
    background: #eaf5e6; 
    color: #2b7e3a; 
    padding: 2px 10px; 
    border-radius: 10px; 
    font-weight: bold; 
    font-size: 12px; 
    margin-left: 8px; 
}

.cart-summary {
    background: rgba(255,255,255,0.7);
    backdrop-filter: blur(10px);
    border-radius: 28px;
    padding: 1.8rem;
    position: sticky;
    top: 2rem;
    text-align: left;
    border: 1px solid rgba(255,255,255,0.3);
}

.cart-summary h3 {
    font-family: 'Montserrat', 'Poppins', sans-serif;
    font-size: 1.3rem;
    color: #1e3a2a;
    margin-bottom: 1.2rem;
    padding-bottom: 0.7rem;
    border-bottom: 2px solid rgba(234,245,230,0.8);
}

.summary-item {
    display: flex;
    justify-content: space-between;
    padding: 0.7rem 0;
    border-bottom: 1px solid rgba(240,240,240,0.8);
    font-size: 0.9rem;
    color: #4a5b4e;
}

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
    display: block; text-align: center; text-decoration: none;
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
    text-align: center; text-decoration: none; display: block; font-size: 0.9rem;
}

.btn-skip:hover { background: #e8e8e8; transform: translateY(-2px); }

#online-choices { 
    margin: 5px 0 15px 35px; 
    padding: 15px; 
    background: #fafdf7; 
    border-left: 4px solid #2b7e3a; 
    border-radius: 8px; 
    border: 1px solid #eee; 
} /* Secondary sub choices toggle box menu attributes */

.voucher-select-box { 
    margin-top: 15px; 
    padding: 12px; 
    background: #fcfcfc; 
    border: 1px dashed #2b7e3a; 
    border-radius: 14px; 
} /* Saved reward shop vouchers dropdown section wrapper card design rules */

.voucher-select-box label {
    font-family: 'Montserrat', 'Inter', sans-serif;
    font-size: 0.8rem;
    font-weight: 700;
    color: #2b7e3a;
    display: block;
    margin-bottom: 5px;
    text-transform: uppercase;
}

.voucher-dropdown { 
    width: 100%; 
    padding: 10px; 
    border-radius: 8px; 
    border: 1px solid #ccc; 
    background: white; 
    font-family: inherit; 
    font-size: 0.85rem; 
    color: #333; 
    outline: none; 
}

.voucher-dropdown:focus { 
    border-color: #2b7e3a; 
}

@media (max-width:768px) { 
    .row-2cols { 
        grid-template-columns: 1fr; 
    } 
    body { 
        padding: 1rem; 
    } 
} /* Mobile breakdown responsive design triggers */
    </style>
</head>
<body>
<div class="container">

    <div class="progress-bar">
        <div class="progress-step completed"><div class="step-number"><i class="fas fa-check"></i></div><div class="step-label">Court</div></div>
        <div class="progress-step completed"><div class="step-number"><i class="fas fa-check"></i></div><div class="step-label">Time</div></div>
        <div class="progress-step completed"><div class="step-number"><i class="fas fa-check"></i></div><div class="step-label">Add-ons</div></div>
        <div class="progress-step active"><div class="step-number">4</div><div class="step-label">Payment</div></div>
    </div>
    
    <form action="gateway.php" method="POST">
        <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
        <input type="hidden" name="amount" id="baseAmountField" value="<?php echo $amount; ?>">

        <div class="row-2cols">
            
            <div>
                <div class="payment-section">
                    <div class="section-title"><i class="fas fa-credit-card"></i> Select Payment Method</div>
                    
                    <div class="payment-option-card" onclick="document.getElementById('wallet_radio').checked = true; document.getElementById('online-choices').style.display='none'">
                        <div style="display: flex; align-items: center;">
                            <input type="radio" name="payment_method" value="App Wallet" id="wallet_radio"
                                <?php echo ($real_balance < $amount) ? 'disabled' : 'checked'; ?>>
                            <div style="margin-left: 15px;">
                                <strong style="font-size: 16px; color:#1e3a2a;">Smash Arena Wallet</strong>
                                <span class="fee-badge">Zero Fee</span>
                                <p style="margin: 4px 0 0 0; font-size: 13px; color: <?php echo ($real_balance < $amount) ? '#ff4d4d' : '#666'; ?>;">
                                    Balance: RM <?php echo number_format($real_balance, 2); ?>
                                    <?php if($real_balance < $amount) echo " (Insufficient Balance)"; ?>
                                </p>
                            </div>
                        </div>
                        <a href="wallet.php?return=checkout&booking_id=<?php echo $booking_id; ?>&amount=<?php echo $amount; ?>" class="btn-skip" style="margin-top:0; padding:6px 15px; width:auto; font-weight:bold; background:#2b7e3a; color:white;">Top Up</a>
                    </div>

                    <div class="payment-option-card" onclick="document.getElementById('online_radio').checked = true; document.getElementById('online-choices').style.display='block'">
                        <div style="display: flex; align-items: center;">
                            <input type="radio" name="payment_method" value="Online Payment" id="online_radio" <?php echo ($real_balance < $amount) ? 'checked' : ''; ?>>
                            <div style="margin-left: 15px;">
                                <strong style="font-size: 16px; color:#1e3a2a;">Online Payment Options</strong>
                                <p style="margin: 4px 0 0 0; font-size: 13px; color: #666;">Instant transaction via FPX Banking, Cards, or digital e-Wallets</p>
                            </div>
                        </div>
                    </div>

                    <div id="online-choices" style="display: <?php echo ($real_balance < $amount) ? 'block' : 'none'; ?>;">
                        <p style="font-size: 14px; font-weight: bold; margin-bottom: 12px; color: #2b7e3a;">Select Provider Gateway:</p>
                        <label style="display: block; margin-bottom: 10px; cursor: pointer; font-size:14px;"><input type="radio" name="sub_method" value="Bank" checked> Online Banking </label>
                        <label style="display: block; margin-bottom: 10px; cursor: pointer; font-size:14px;"><input type="radio" name="sub_method" value="Card"> Credit / Debit Card</label>
                        <label style="display: block; cursor: pointer; font-size:14px;"><input type="radio" name="sub_method" value="TNG"> Touch 'n Go eWallet App</label>
                    </div>

                </div>
            </div>
            
            <div>
                <div class="cart-summary">
                    <h3><i class="fas fa-receipt"></i> Booking Summary</h3>
                    <div style="margin-top: 15px; margin-bottom: 15px;">
                        <div class="summary-item"><strong>Venue Target:</strong> <span>Jebat Racquet Sports Centre</span></div>
                        <div class="summary-item"><strong>Court Unit:</strong> <span><?php echo htmlspecialchars($court_name); ?></span></div>
                        <div class="summary-item"><strong>Reserved Date:</strong> <span><?php echo date('M j, Y', strtotime($booking_date)); ?></span></div>
                        <div class="summary-item"><strong>Time Windows:</strong> <span><?php echo htmlspecialchars($time_slot); ?></span></div>
                    </div>

                    <div class="voucher-select-box">
                        <label><i class="fas fa-ticket-alt"></i> Apply Saved Voucher</label>
                        <select name="user_voucher_id" id="voucherSelector" class="voucher-dropdown" onchange="applyLiveVoucherDiscount()">
                            <option value="" data-discount="0">-- No Voucher Selected --</option>
                            <?php foreach ($available_vouchers as $v): ?>
                                <option value="<?php echo $v['user_voucher_id']; ?>" data-discount="<?php echo $v['discount_amount']; ?>">
                                    <?php echo htmlspecialchars($v['title']); ?> (-RM <?php echo number_format($v['discount_amount'], 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="summary-item" id="discountItemRow" style="display: none; color: #e67e22; font-weight: 600;">
                        <span>Voucher Discount:</span>
                        <span id="discountValueDisplay">-RM 0.00</span>
                    </div>

                    <div class="cart-total">
                        <span>Total Due:</span>
                        <span id="finalAmountPriceDisplay">MYR <?php echo number_format($amount, 2); ?></span>
                    </div>
                    
                    <button type="submit" class="btn-continue"><i class="fas fa-check-circle"></i> Continue to Payment</button>
                    <a href="../Customer_Module/addons.php?booking_id=<?php echo $booking_id; ?>" class="btn-skip">← Back to Add-ons</a>
                </div>
            </div>

        </div>
    </form>
</div>

<script>
function applyLiveVoucherDiscount() {
    const baseAmount = parseFloat(<?php echo $amount; ?>);
    const selector = document.getElementById('voucherSelector');
    const selectedOption = selector.options[selector.selectedIndex];
    const discountAmount = parseFloat(selectedOption.getAttribute('data-discount')) || 0;
    
    let netFinalPayable = baseAmount - discountAmount;
    if (netFinalPayable < 0) netFinalPayable = 0;
    
    const discountRow = document.getElementById('discountItemRow');
    const discountDisplay = document.getElementById('discountValueDisplay');
    
    if (discountAmount > 0) {
        discountRow.style.display = 'flex';
        discountDisplay.textContent = '-RM ' + discountAmount.toFixed(2);
    } else {
        discountRow.style.display = 'none';
    }
    
    document.getElementById('finalAmountPriceDisplay').textContent = 'MYR ' + netFinalPayable.toFixed(2);
}
</script>

</body>
</html>