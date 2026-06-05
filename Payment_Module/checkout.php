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

    // 🟢 FETCH AVAILABLE UNUSED VOUCHERS REDEEMED BY PLAYER
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* CSS reset to clean up default browser padding spaces */
        * { margin:0; padding:0; box-sizing:border-box; }
        /* Page styling layout parameters */
        body { font-family:'Inter',sans-serif; background:#f5f9f0; padding:2rem; }
        .container { max-width:1400px; margin:0 auto; }
        
        /* Layout steps header bar code styles — animated (matches addons.php) */
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
        .progress-step { text-align:center; flex:1; position: relative; }
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
        .progress-step.completed:not(:last-child)::after { background: #2b7e3a; }
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
        .progress-step.completed .step-number { background: #2b7e3a; color: white; }
        .progress-step .step-label { font-size: 0.75rem; color: #888; font-weight: 500; }
        .progress-step.active .step-label { color: #2b7e3a; font-weight: 700; }
        .progress-step.completed .step-label { color: #2b7e3a; }
        
        /* Split layout container: Left column is 2x wider than right column card summary box */
        .row-2cols { display:grid; grid-template-columns:2fr 1fr; gap:1.5rem; }
        
        /* Payment methods block layout styling blueprints templates elements */
        .payment-section { background:white; border-radius:24px; padding:1.5rem; margin-bottom:1.5rem; text-align: left; }
        .section-title { font-size:1.3rem; font-weight:700; color:#2b7e3a; margin-bottom:1.5rem; padding-bottom:0.5rem; border-bottom:2px solid #eaf5e6; }
        
        /* Interactive clickable card selection items buttons framework rules styles */
        .payment-option-card { border: 1px solid #e0e0e0; padding: 20px; border-radius: 16px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; background: white; transition: 0.2s; cursor: pointer; }
        .payment-option-card:hover { border-color: #2b7e3a; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .fee-badge { background: #eaf5e6; color: #2b7e3a; padding: 2px 10px; border-radius: 10px; font-weight: bold; font-size: 12px; margin-left: 8px; }
        
        /* Floating booking totals data overview panels side card */
        .cart-summary { background:white; border-radius:24px; padding:1.5rem; position:sticky; top:2rem; text-align: left; }
        .summary-item { display:flex; justify-content:space-between; padding:0.6rem 0; border-bottom:1px solid #eee; font-size:0.9rem; color:#555; }
        .cart-total { display:flex; justify-content:space-between; padding:1rem 0; margin-top:0.5rem; border-top:2px solid #2b7e3a; font-weight:700; font-size:1.3rem; color:#2b7e3a; }
        
        /* Dynamic main action interactive navigation execution buttons styles mapping templates links keys */
        .btn-continue { background:#2b7e3a; color:white; border:none; padding:1rem; border-radius:50px; width:100%; font-weight:700; font-size:1rem; cursor:pointer; margin-top:1rem; transition:0.2s; display: block; text-align: center; text-decoration: none; }
        .btn-continue:hover { background:#1f5a2a; transform:translateY(-2px); }
        .btn-skip { background:#e0e0e0; color:#333; border:none; padding:0.8rem; border-radius:50px; width:100%; margin-top:0.5rem; cursor:pointer; text-align: center; text-decoration: none; display: block; font-size: 0.9rem; }
        
        /* Secondary sub choices toggle box menu attributes */
        #online-choices { margin: 5px 0 15px 35px; padding: 15px; background: #fafdf7; border-left: 4px solid #2b7e3a; border-radius: 8px; border: 1px solid #eee; }
        
        /* Saved reward shop vouchers dropdown section wrapper card design rules components formatting style context templates blueprints */
        .voucher-select-box { margin-top: 15px; padding: 12px; background: #fcfcfc; border: 1px dashed #2b7e3a; border-radius: 14px; }
        .voucher-select-box label { font-size: 0.8rem; font-weight: 700; color: #2b7e3a; display: block; margin-bottom: 5px; text-transform: uppercase; }
        .voucher-dropdown { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc; background: white; font-family: inherit; font-size: 0.85rem; color: #333; outline: none; }
        .voucher-dropdown:focus { border-color: #2b7e3a; }

        /* Mobile breakdown responsive design triggers formatting adjustments context frames */
        @media (max-width:768px) { .row-2cols { grid-template-columns:1fr; } body { padding:1rem; } }
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
                        <label style="display: block; margin-bottom: 10px; cursor: pointer; font-size:14px;"><input type="radio" name="sub_method" value="Bank" checked> Online Banking (FPX Direct)</label>
                        <label style="display: block; margin-bottom: 10px; cursor: pointer; font-size:14px;"><input type="radio" name="sub_method" value="Card"> Visa / MasterCard / Debit</label>
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
    // Read the baseline baseline checkout billing cost from server templates values parameters lines tracking properties keys variables properties data
    const baseAmount = parseFloat(<?php echo $amount; ?>);
    // Find the selector element input layout container block row key inside current active document structures frameworks layouts
    const selector = document.getElementById('voucherSelector');
    // Grab selected choice item property dataset attributes values loops entries lists elements configurations properties rules
    const selectedOption = selector.options[selector.selectedIndex];
    
    // Pull the real numeric voucher cash discount subtraction value from custom dataset parameters configurations rows context frames footprints channels
    const discountAmount = parseFloat(selectedOption.getAttribute('data-discount')) || 0;
    
    // Execute live math: subtract voucher deduction value from baseline court fees parameters data calculations values lines tracking parameters rows
    let netFinalPayable = baseAmount - discountAmount;
    // Safety lock boundary boundary checker: stop values from crashing below RM 0 line limits criteria conditions logic breakdown crashes loops
    if (netFinalPayable < 0) netFinalPayable = 0;
    
    // Find layout summary display row references node elements inside current viewport layout layers parameters tracking variables maps
    const discountRow = document.getElementById('discountItemRow');
    const discountDisplay = document.getElementById('discountValueDisplay');
    
    // If voucher value attributes are active and greater than zero criteria points conditions parameters properties data keys properties rows keys paths
    if (discountAmount > 0) {
        // Force the layout display line block component active on screen layer view context models blocks graphics styling templates properties parameters paths
        discountRow.style.display = 'flex';
        // Format text string lines to display current cash deduction values metrics calculations values fields properties layout frames blueprint context
        discountDisplay.textContent = '-RM ' + discountAmount.toFixed(2);
    } else {
        // Toggle item out of sight container frames blueprint models framework layers tracking context parameters values metrics properties columns keys paths
        discountRow.style.display = 'none';
    }
    
    // Inject calculated net final payment total text back onto master screen container element counter display interface canvas layers components blocks rules elements fields
    document.getElementById('finalAmountPriceDisplay').textContent = 'MYR ' + netFinalPayable.toFixed(2);
}
</script>

</body>
</html>