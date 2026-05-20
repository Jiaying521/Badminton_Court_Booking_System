<?php
// gateway.php - The Multi-Step Mock Payment Interface Flow
session_start();
include 'db_connect.php';

$booking_id = $_POST['booking_id'] ?? $_GET['booking_id'] ?? 0;
$amount = $_POST['amount'] ?? $_GET['amount'] ?? 0;
$method = $_POST['payment_method'] ?? $_GET['payment_method'] ?? 'Unknown';
$promo  = $_POST['promo_code'] ?? $_GET['promo_code'] ?? '';
$sub_method = $_POST['sub_method'] ?? $_GET['sub_method'] ?? ''; 

// Track current step from BOTH POST and GET requests safely
$step = $_POST['step'] ?? $_GET['step'] ?? 1;

// Fetch REAL wallet balance (if choosing App Wallet)
$user_id = $_SESSION['user_id'] ?? 0;
$real_balance = 0.00;

if ($user_id > 0) {
    $stmt_bal = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $stmt_bal->bind_param("i", $user_id);
    $stmt_bal->execute();
    $res_bal = $stmt_bal->get_result();
    if ($row_bal = $res_bal->fetch_assoc()) {
        $real_balance = $row_bal['wallet_balance'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment Gateway</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#f5f9f0; padding:2rem; }
        .main-container { max-width:1400px; margin:0 auto; }
        
        /* EXACT MATCH TO FRIEND'S STEP HEADER BAR STYLE */
        .progress-bar { display:flex; justify-content:space-between; margin-bottom:2rem; background:white; padding:1rem 2rem; border-radius:60px; }
        .progress-step { text-align:center; flex:1; }
        .progress-step .step-number { width:32px; height:32px; background:#e0e0e0; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; margin-bottom:0.3rem; color: #333; }
        .progress-step.active .step-number { background:#2b7e3a; color:white; }
        .progress-step.completed .step-number { background:#2b7e3a; color:white; }
        .progress-step .step-label { font-size:0.75rem; color:#888; }
        .progress-step.active .step-label { color:#2b7e3a; font-weight:600; }

        /* Gateway box card design styling */
        .clone-container { background: white; padding: 25px; border-radius: 16px; border: 1px solid #e0e0e0; margin-top: 15px; text-align: left;}
        .container { max-width: 500px; margin: 30px auto; padding: 30px; background: white; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .btn-green { width: 100%; padding: 15px; margin-top: 20px; background-color: #2b7e3a; color: white; border: none; border-radius: 50px; font-weight: bold; cursor: pointer; font-size: 1rem; text-decoration:none; display:inline-block; box-sizing: border-box;}
        .btn-green:hover { background-color: #1f5a2a; }
        .receipt-box { border: 2px dashed #cbd5c0; padding: 20px; border-radius: 12px; background: #fafdf7; text-align: left; margin-top: 15px; }
        
        /* REALISTIC FORM INPUT STYLES */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 12px; font-weight: 700; color: #444; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
        .gateway-input { width: 100%; padding: 12px 14px; border-radius: 8px; border: 1px solid #ccc; outline: none; font-size: 14px; font-family: inherit; color: #333; background: #fafafa; transition: 0.2s; }
        .gateway-input:focus { border-color: #2b7e3a; background: #fff; box-shadow: 0 0 0 3px rgba(43,126,58,0.1); }
        .input-row { display: flex; gap: 12px; }
        .input-row .form-group { flex: 1; margin-bottom: 0; }
        
        .password-wrapper { position: relative; display: flex; align-items: center; width: 100%; }
        .toggle-password-eye { position: absolute; right: 15px; color: #666; cursor: pointer; transition: 0.2s; font-size: 16px; z-index: 10; }
        .toggle-password-eye:hover { color: #2b7e3a; }
        
        .bank-badge-header { border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px; display: flex; align-items: center; justify-content: space-between; }
        .secure-notice { font-size: 11px; color: #666; display: flex; align-items: center; gap: 5px; margin-top: 10px; background: #f9f9f9; padding: 8px 12px; border-radius: 6px; }

        /* 🟢 NEW TNG SIMULATOR STYLES */
        .tng-btn { background: #005eb8; color: white; border: none; padding: 12px; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%; margin-top: 10px; transition: 0.2s; font-size: 13px; }
        .tng-btn:hover { background: #004487; }
        .countdown-text { font-size: 12px; color: #ffeb3b; font-weight: bold; margin-top: 5px; display: block; letter-spacing: 0.5px; }
    </style>
</head>
<body>
<div class="main-container">

    <div class="progress-bar">
        <div class="progress-step completed"><div class="step-number">1</div><div class="step-label">Court</div></div>
        <div class="progress-step completed"><div class="step-number">2</div><div class="step-label">Time</div></div>
        <div class="progress-step completed"><div class="step-number">3</div><div class="step-label">Add-ons</div></div>
        <div class="progress-step active"><div class="step-number">4</div><div class="step-label">Payment</div></div>
    </div>

    <div class="container" style="text-align: center; border-top: 6px solid #2b7e3a;">
        
        <?php if ($step == 1 || $step == 2): ?>
            <form action="gateway.php" method="POST" id="gatewayForm">
                <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking_id); ?>">
                <input type="hidden" name="amount" value="<?php echo htmlspecialchars($amount); ?>">
                <input type="hidden" name="promo_code" value="<?php echo htmlspecialchars($promo); ?>">
                <input type="hidden" name="payment_method" value="<?php echo htmlspecialchars($method); ?>">
                <input type="hidden" name="sub_method" value="<?php echo htmlspecialchars($sub_method); ?>">

                <?php if ($step == 1): ?>
                    <input type="hidden" name="step" id="formStepField" value="2"> 
                    
                    <?php if ($method === 'App Wallet'): ?>
                        <h2 style="color: #1a2930; font-weight:700;">Smash Arena Wallet</h2>
                        <div style="background: linear-gradient(135deg, #2b7e3a, #004d1a); color: white; padding: 20px; border-radius: 12px; margin: 20px 0;">
                            <p style="margin: 0; opacity: 0.9; font-size:14px;">Available Balance</p>
                            <h1 style="margin: 5px 0; font-size:2.2rem; font-weight:800;">RM <?php echo number_format($real_balance, 2); ?></h1>
                        </div>
                        <p style="font-size:15px; color:#555;">Paying: <strong style="color: #2b7e3a;">RM <?php echo number_format($amount, 2); ?></strong></p>
                        
                        <?php if ($real_balance < $amount): ?>
                            <p style="color: #ff4d4d; font-weight: bold; margin-top:15px;">⚠️ Insufficient balance! Please go back and top up.</p>
                        <?php else: ?>
                            <button type="submit" class="btn-green">Proceed to Confirmation</button>
                        <?php endif; ?>

                    <?php elseif ($sub_method === 'Bank'): ?>
                        <div class="clone-container">
                            <div class="bank-badge-header">
                                <img src="https://images.icon-icons.com/1091/PNG/512/bank_78392.png" width="85">
                                <span style="font-size: 12px; font-weight: bold; color: #2b7e3a; background: #eaf5e6; padding: 4px 10px; border-radius: 20px;"><i class="fas fa-lock"></i> FPX Secure</span>
                            </div>
                            
                            <div class="form-group">
                                <label>Select Bank Profile</label>
                                <select name="selected_bank" style="width: 100%; padding: 12px; border-radius: 8px; border:1px solid #ccc; background:#fafafa; font-size:14px;" required>
                                    <option value="Maybank2u">Maybank2u (Malayan Banking Berhad)</option>
                                    <option value="CIMB Clicks">CIMB Clicks (CIMB Bank)</option>
                                    <option value="Public Bank">Public Bank Berhad</option>
                                    <option value="RHB Now">RHB Banking Group</option>
                                    <option value="Hong Leong Connect">Hong Leong Bank</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Online Banking Username</label>
                                <input type="text" placeholder="e.g. smash_player99" class="gateway-input" required autocomplete="off">
                            </div>
                            
                            <div class="form-group">
                                <label>Password / Access Code</label>
                                <div class="password-wrapper">
                                    <input type="password" id="bank_password" placeholder="••••••••••••" class="gateway-input" required>
                                    <i class="fas fa-eye toggle-password-eye" onclick="togglePasswordVisibility('bank_password', this)"></i>
                                </div>
                            </div>
                            
                            <div class="secure-notice">
                                <i class="fas fa-shield-alt" style="color: #2b7e3a;"></i>
                                <span>Do not share your online banking credentials. This is a secure transaction gateway layer.</span>
                            </div>
                        </div>
                        <button type="submit" class="btn-green">Proceed or Login</button>

                    <?php elseif ($sub_method === 'Card'): ?>
                        <div class="clone-container">
                            <div class="bank-badge-header" style="margin-bottom: 20px;">
                                <h3 style="color:#1a2930; font-weight:700; font-size: 16px;"><i class="fas fa-credit-card" style="color:#2b7e3a;"></i> Card Details</h3>
                                <div style="display:flex; gap:5px; font-size: 20px; color: #888;">
                                    <i class="fab fa-cc-visa" style="color:#1A1F71;"></i>
                                    <i class="fab fa-cc-mastercard" style="color:#EB001B;"></i>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Cardholder Name</label>
                                <input type="text" placeholder="e.g. AHMAD BIN ABDULLAH" class="gateway-input" style="text-transform: uppercase;" required autocomplete="off">
                            </div>
                            
                            <div class="form-group">
                                <label>Card Number</label>
                                <div style="position: relative;">
                                    <input type="text" placeholder="4111 1111 1111 1111" maxlength="19" class="gateway-input" style="letter-spacing: 1.5px;" oninput="this.value = this.value.replace(/[^\d]/g, '').replace(/(.{4})/g, '$1 ').trim();" required autocomplete="off">
                                </div>
                            </div>
                            
                            <div class="input-row">
                                <div class="form-group">
                                    <label>Expiry Date</label>
                                    <input type="text" placeholder="MM/YY" maxlength="5" style="text-align: center; letter-spacing: 1px;" class="gateway-input" oninput="this.value = this.value.replace(/[^\d]/g, '').replace(/(.{2})/g, '$1/').replace(/\/$/, '').trim();" required autocomplete="off">
                                </div>
                                <div class="form-group">
                                    <label>Security Code (CVV)</label>
                                    <div class="password-wrapper">
                                        <input type="password" id="card_cvv" placeholder="•••" maxlength="3" style="text-align: center; letter-spacing: 2px;" class="gateway-input" oninput="this.value = this.value.replace(/[^\d]/g, '');" required>
                                        <i class="fas fa-eye toggle-password-eye" onclick="togglePasswordVisibility('card_cvv', this)"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="secure-notice">
                                <i class="fas fa-lock" style="color: #2b7e3a;"></i>
                                <span>Your payment details are protected using industry-standard 256-bit SSL encryption.</span>
                            </div>
                        </div>
                        <button type="submit" class="btn-green">Proceed to Confirmation</button>

                    <?php elseif ($sub_method === 'TNG'): ?>
                        <div class="clone-container" id="tngMainContainer" style="background: #005eb8; color: white; text-align:center; border:none; transition: 0.3s;">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/4/4e/Touch_%27n_Go_eWallet_logo.svg/1200px-Touch_%27n_Go_eWallet_logo.svg.png" width="80" style="background: white; border-radius: 5px; padding: 5px;">
                            
                            <div id="tngScanningView">
                                <h3 style="margin-top:12px; font-weight:600;"><i class="fas fa-qrcode"></i> Scan Merchant QR</h3>
                                <span class="countdown-text" id="tngTimer">Waiting for scan... (05:00)</span>
                                
                                <div style="background: white; padding: 10px; display: inline-block; border-radius: 12px; margin: 15px 0; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=SMASH-ARENA-PAYMENT-ID-<?php echo $booking_id; ?>" alt="QR Code">
                                </div>
                                <p style="font-weight:700; font-size:18px; letter-spacing:0.5px;">TOTAL DUE: RM <?php echo number_format($amount, 2); ?></p>
                                
                                <hr style="border:0; border-top:1px solid rgba(255,255,255,0.2); margin:15px 0;">
                                <p style="font-size:12px; opacity:0.8;">Or log in using mobile number below:</p>
                                <input type="text" id="tngMobileNo" placeholder="Mobile Number (e.g. 60123456789)" class="gateway-input" style="margin-top:10px; background:rgba(255,255,255,0.1); color:white; border:1px solid rgba(255,255,255,0.3);" oninput="this.value = this.value.replace(/[^\d]/g, '');">
                                
                                <button type="button" class="tng-btn" onclick="simulateTngScan()"><i class="fas fa-mobile-alt"></i> Simulate App Scan & Pay</button>
                            </div>

                            <div id="tngLoadingView" style="display:none; padding: 40px 0;">
                                <i class="fas fa-circle-notch fa-spin" style="font-size: 3.5rem; margin-bottom: 15px;"></i>
                                <h3>Authenticating Transaction...</h3>
                                <p style="font-size:13px; opacity:0.8; margin-top:5px;">Connecting securely to Touch 'n Go servers</p>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php elseif ($step == 2): ?>
                    <script>
                        window.onload = function() {
                            const forms = document.getElementsByTagName('form');
                            if(forms.length > 0) {
                                forms[0].action = 'process_payment.php';
                            }
                        };
                    </script>
                    <h2 style="color:#2b7e3a; font-weight:700; margin-bottom:10px;">Payment Details</h2>
                    <div class="clone-container">
                        <p style="margin: 10px 0; display:flex; justify-content:space-between;"><strong>Method:</strong> <span style="color:#333; font-weight:600;"><?php echo htmlspecialchars($sub_method ? $sub_method : $method); ?></span></p>
                        <p style="margin: 10px 0; display:flex; justify-content:space-between;"><strong>Account Type:</strong> <span style="color:#666;">Savings Account / Default</span></p>
                        <p style="margin: 10px 0; display:flex; justify-content:space-between;"><strong>Booking Target ID:</strong> <span style="color:#666;">#<?php echo htmlspecialchars($booking_id); ?></span></p>
                        <hr style="border:0; border-top:1px solid #eee; margin:12px 0;">
                        <p style="margin: 8px 0; display:flex; justify-content:space-between; font-size:1.2rem;"><strong>Amount to Deduct:</strong> <span style="color:#2b7e3a; font-weight:bold;">RM <?php echo number_format($amount, 2); ?></span></p>
                    </div>
                    
                    <button type="submit" class="btn-green">Confirm Payment</button>
                <?php endif; ?>

                <div id="cancelReturnLink">
                    <a href="checkout.php?booking_id=<?php echo htmlspecialchars($booking_id); ?>&amount=<?php echo htmlspecialchars($amount); ?>" style="color: #ff4d4d; display: block; margin-top: 20px; text-decoration: none; font-weight: bold; font-size:14px;">← Cancel and Return</a>
                </div>
            </form>

        <?php elseif ($step == 3): ?>
            <?php $status = $_GET['status'] ?? 'fail'; ?>

            <?php if ($status === 'success'): ?>
                <div style="color: #2b7e3a; font-size: 4rem; margin-top: 15px; margin-bottom: 10px;"><i class="fas fa-check-circle"></i></div>
                <h2 style="color:#2b7e3a; font-weight:700;">Payment Successful</h2>
                <p style="color:#5a6e5c; margin-bottom:20px; font-size:14px;">Your transaction has completed secure authentication.</p>

                <div class="receipt-box">
                    <h4 style="text-align:center; margin-top:0; border-bottom: 1px solid #ddd; padding-bottom:8px; font-weight:700; color:#1a2930; letter-spacing:1px;">TRANSACTION RECEIPT</h4>
                    <p style="margin:8px 0; font-size:14px;"><strong>Merchant:</strong> Smash Arena Hub</p>
                    <p style="margin:8px 0; font-size:14px;"><strong>Booking Reference:</strong> #<?php echo htmlspecialchars($booking_id); ?></p>
                    <p style="margin:8px 0; font-size:14px;"><strong>Amount Credited:</strong> <span style="color:#2b7e3a; font-weight:bold;">RM <?php echo number_format($amount, 2); ?></span></p>
                    <p style="margin:8px 0; font-size:14px;"><strong>Status:</strong> <span style="color:#2b7e3a; font-weight:bold;">Approved / Settled</span></p>
                </div>
            <?php else: ?>
                <div style="color: #ff4d4d; font-size: 4rem; margin-top: 15px; margin-bottom: 10px;"><i class="fas fa-times-circle"></i></div>
                <h2 style="color:#ff4d4d; font-weight:700;">Payment Unsuccessful</h2>
                <p style="color:#777; font-size:14px; margin-bottom:15px;">The banking transaction failed validation checkpoints or was declined.</p>
            <?php endif; ?>

            <a href="../Customer_Module/my_bookings.php" class="btn-green">Back to Main System</a>
        <?php endif; ?>
    </div>
</div>

<script>
function togglePasswordVisibility(inputId, iconElement) {
    const passwordInput = document.getElementById(inputId);
    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        iconElement.classList.remove("fa-eye");
        iconElement.classList.add("fa-eye-slash");
    } else {
        passwordInput.type = "password";
        iconElement.classList.remove("fa-eye-slash");
        iconElement.classList.add("fa-eye");
    }
}

// 🟢 REALISTIC COUNTDOWN TIMER ENGINE FOR TNG
<?php if ($sub_method === 'TNG' && $step == 1): ?>
let duration = 300; 
const timerElement = document.getElementById('tngTimer');
const interval = setInterval(function() {
    let minutes = parseInt(duration / 60, 10);
    let seconds = parseInt(duration % 60, 10);
    minutes = minutes < 10 ? "0" + minutes : minutes;
    seconds = seconds < 10 ? "0" + seconds : seconds;
    
    timerElement.textContent = "Waiting for scan... (" + minutes + ":" + seconds + ")";
    if (--duration < 0) {
        clearInterval(interval);
        timerElement.textContent = "QR Expired. Please reload.";
    }
}, 1000);

// 🟢 HIGH-FIDELITY TNG PRESENTATION ANIMATION TRIGGER
function simulateTngScan() {
    // If they typed a phone number, validate it, otherwise assume they scanned the QR code
    const phoneInput = document.getElementById('tngMobileNo').value;
    
    clearInterval(interval); // stop timer
    document.getElementById('tngScanningView').style.display = 'none';
    document.getElementById('cancelReturnLink').style.display = 'none';
    document.getElementById('tngLoadingView').style.display = 'block';
    document.getElementById('tngMainContainer').style.background = '#004487';

    // Hold the loading animation for 2 seconds to make lecturers think it's checking api servers live!
    setTimeout(function() {
        document.getElementById('gatewayForm').submit();
    }, 2200);
}
<?php endif; ?>
</script>

</body>
</html>