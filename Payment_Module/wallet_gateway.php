<?php
// wallet_gateway.php - The Mock Wallet Top-Up Authentication Interface
session_start();
$reload_amt = $_POST['reload_amount'] ?? 0;
$pay_method = $_POST['pay_method'] ?? 'Bank';
$return_to = $_POST['return_to'] ?? 'dashboard';
$b_id = $_POST['booking_id'] ?? 0;
$b_amt = $_POST['amount'] ?? 0;

// Security fallback to prevent negative data hacking
if ($reload_amt < 1) {
    die("Security Exception: Invalid transaction request detected.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authorize Top-Up | Smash Arena</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#f5f9f0; padding:2rem; display:flex; justify-content:center; align-items:center; min-height:100vh; }
        
        .gateway-card { background:white; padding:40px; border-radius:24px; width:100%; max-width:440px; text-align:center; box-shadow:0 10px 30px rgba(0,0,0,0.04); border-top: 6px solid #2b7e3a; }
        
        .form-group { margin-bottom: 16px; text-align: left; }
        .form-group label { display: block; font-size: 11px; font-weight: 700; color: #555; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .gateway-input { width: 100%; padding: 12px 14px; border-radius: 8px; border: 1px solid #ccc; outline: none; font-size: 14px; font-family: inherit; color: #333; background: #fafafa; transition: 0.2s; }
        .gateway-input:focus { border-color: #2b7e3a; background: #fff; box-shadow: 0 0 0 3px rgba(43,126,58,0.1); }
        
        .password-wrapper { position: relative; display: flex; align-items: center; width: 100%; }
        .toggle-password-eye { position: absolute; right: 15px; color: #666; cursor: pointer; transition: 0.2s; font-size: 16px; z-index: 10; }
        .toggle-password-eye:hover { color: #2b7e3a; }
        
        .btn-green { width: 100%; padding: 15px; margin-top: 20px; background-color: #2b7e3a; color: white; border: none; border-radius: 50px; font-weight: bold; cursor: pointer; font-size: 1rem; text-decoration:none; display:inline-block; transition: 0.2s; }
        .btn-green:hover { background-color: #1f5a2a; }
        
        .bank-badge-header { border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; }
        .secure-notice { font-size: 11px; color: #666; display: flex; align-items: center; gap: 5px; margin-top: 15px; background: #f9f9f9; padding: 10px 12px; border-radius: 6px; text-align: left; }
        .input-row { display: flex; gap: 12px; }
        .input-row .form-group { flex: 1; margin-bottom: 0; }

        .tng-btn { background: #005eb8; color: white; border: none; padding: 12px; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%; margin-top: 12px; transition: 0.2s; font-size: 13px; }
        .tng-btn:hover { background: #004487; }
        .countdown-text { font-size: 12px; color: #ffeb3b; font-weight: bold; margin-top: 5px; display: block; letter-spacing: 0.5px; }
    </style>
</head>
<body>
    <div class="gateway-card" id="gatewayMainContainer" style="transition: 0.3s;">
        <form action="process_reload.php" method="POST" id="reloadForm">
            <input type="hidden" name="reload_amount" value="<?php echo htmlspecialchars($reload_amt); ?>">
            <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($return_to); ?>">
            <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($b_id); ?>">
            <input type="hidden" name="amount" value="<?php echo htmlspecialchars($b_amt); ?>">

            <?php if ($pay_method == 'Bank'): ?>
                <div class="bank-badge-header">
                    <img src="https://images.icon-icons.com/1091/PNG/512/bank_78392.png" width="80">
                    <span style="font-size: 12px; font-weight: bold; color: #2b7e3a; background: #eaf5e6; padding: 4px 10px; border-radius: 20px;"><i class="fas fa-lock"></i> Secure Connection</span>
                </div>
                
                <h3 style="color:#1e3a2a; font-weight:700; margin-bottom: 5px; text-align: left;">FPX Central Login</h3>
                <p style="font-size: 13px; color:#666; text-align: left; margin-bottom: 20px;">Reloading: <strong style="color: #2b7e3a;">RM <?php echo number_format($reload_amt, 2); ?></strong></p>
                
                <div class="form-group">
                    <label>Online Banking Username</label>
                    <input type="text" placeholder="Enter your banking username" class="gateway-input" required autocomplete="off">
                </div>
                
                <div class="form-group">
                    <label>Password / Access Code</label>
                    <div class="password-wrapper">
                        <input type="password" id="reload_bank_pass" placeholder="••••••••••••" class="gateway-input" required>
                        <i class="fas fa-eye toggle-password-eye" onclick="togglePasswordVisibility('reload_bank_pass', this)"></i>
                    </div>
                </div>

                <div class="secure-notice">
                    <i class="fas fa-shield-alt" style="color: #2b7e3a;"></i>
                    <span>Isolated sandboxed payment verification protocol active.</span>
                </div>
                
                <button type="submit" class="btn-green">Authorize & Top Up</button>

            <?php elseif ($pay_method == 'Card'): ?>
                <div class="bank-badge-header">
                    <h3 style="color:#1a2930; font-weight:700; font-size: 16px;"><i class="fas fa-credit-card" style="color:#2b7e3a;"></i> Card Top-Up</h3>
                    <div style="display:flex; gap:5px; font-size: 20px; color: #888;">
                        <i class="fab fa-cc-visa" style="color:#1A1F71;"></i>
                        <i class="fab fa-cc-mastercard" style="color:#EB001B;"></i>
                    </div>
                </div>

                <p style="font-size: 13px; color:#666; text-align: left; margin-bottom: 20px;">Top-Up Allocation: <strong style="color: #2b7e3a;">RM <?php echo number_format($reload_amt, 2); ?></strong></p>
                
                <div class="form-group">
                    <label>Cardholder Name</label>
                    <input type="text" placeholder="e.g. AHMAD BIN ABDULLAH" class="gateway-input" style="text-transform: uppercase;" required autocomplete="off">
                </div>
                
                <div class="form-group">
                    <label>Card Number</label>
                    <input type="text" placeholder="4111 1111 1111 1111" maxlength="19" class="gateway-input" style="letter-spacing: 1.5px;" oninput="this.value = this.value.replace(/[^\d]/g, '').replace(/(.{4})/g, '$1 ').trim();" required autocomplete="off">
                </div>
                
                <div class="input-row">
                    <div class="form-group">
                        <label>Expiry Date</label>
                        <input type="text" placeholder="MM/YY" maxlength="5" style="text-align: center; letter-spacing: 1px;" class="gateway-input" oninput="this.value = this.value.replace(/[^\d]/g, '').replace(/(.{2})/g, '$1/').replace(/\/$/, '').trim();" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Security Code (CVV)</label>
                        <div class="password-wrapper">
                            <input type="password" id="reload_card_cvv" placeholder="•••" maxlength="3" style="text-align: center; letter-spacing: 2px;" class="gateway-input" oninput="this.value = this.value.replace(/[^\d]/g, '');" required>
                            <i class="fas fa-eye toggle-password-eye" onclick="togglePasswordVisibility('reload_card_cvv', this)"></i>
                        </div>
                    </div>
                </div>

                <div class="secure-notice">
                    <i class="fas fa-lock" style="color: #2b7e3a;"></i>
                    <span>Protected via SSL encryption standards.</span>
                </div>

                <button type="submit" class="btn-green">Authorize & Top Up</button>

            <?php else: ?>
                <div id="tngScanningView">
                    <div class="bank-badge-header" style="background: #005eb8; margin: -40px -40px 25px -40px; padding: 25px; border-radius: 24px 24px 0 0; border: none; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px;">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/4/4e/Touch_%27n_Go_eWallet_logo.svg/1200px-Touch_%27n_Go_eWallet_logo.svg.png" width="85" style="background: white; border-radius: 6px; padding: 5px;">
                        <span class="countdown-text" id="reloadTngTimer">Waiting for scan... (05:00)</span>
                    </div>
                    
                    <h3 style="color:#1e3a2a; font-weight:700; margin-bottom: 5px;">Scan to Complete Top-Up</h3>
                    
                    <div style="background: white; padding: 12px; display: inline-block; border-radius: 16px; margin: 15px 0; border: 1px solid #eee; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=SMASH-ARENA-RELOAD-AMT-<?php echo $reload_amt; ?>" alt="QR Code">
                    </div>
                    
                    <p style="font-weight:800; font-size:1.2rem; color: #005eb8; margin-bottom: 15px;">RELOAD AMOUNT: RM <?php echo number_format($reload_amt, 2); ?></p>
                    
                    <div class="form-group">
                        <label>Registered TNG Mobile Number</label>
                        <input type="text" id="tngMobileNo" placeholder="e.g. 60123456789" class="gateway-input" oninput="this.value = this.value.replace(/[^\d]/g, '');">
                    </div>

                    <button type="button" class="tng-btn" onclick="simulateReloadTngScan()"><i class="fas fa-mobile-alt"></i> Simulate App Scan & Pay</button>
                </div>

                <div id="tngLoadingView" style="display:none; padding: 40px 0; color: white;">
                    <i class="fas fa-circle-notch fa-spin" style="font-size: 3.5rem; margin-bottom: 20px;"></i>
                    <h3 style="font-weight: 700;">Processing Reload Allocation...</h3>
                    <p style="font-size:13px; opacity:0.8; margin-top:5px;">Verifying mock transaction ledger channels</p>
                </div>
            <?php endif; ?>

            <a href="wallet.php?return=<?php echo $return_to; ?>&booking_id=<?php echo $b_id; ?>&amount=<?php echo $b_amt; ?>" id="cancelBtnLink" style="display:block; margin-top:20px; color:#ff4d4d; text-decoration:none; font-weight:bold; font-size:14px;">← Cancel and Return</a>
        </form>
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

<?php if ($pay_method == 'TNG'): ?>
let duration = 300; 
const timerElement = document.getElementById('reloadTngTimer');
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

function simulateReloadTngScan() {
    clearInterval(interval);
    document.getElementById('tngScanningView').style.display = 'none';
    document.getElementById('cancelBtnLink').style.display = 'none';
    document.getElementById('tngLoadingView').style.display = 'block';
    
    const container = document.getElementById('gatewayMainContainer');
    container.style.background = '#005eb8';
    container.style.borderColor = '#005eb8';

    setTimeout(function() {
        document.getElementById('reloadForm').submit();
    }, 2000);
}
<?php endif; ?>
</script>
</body>
</html>