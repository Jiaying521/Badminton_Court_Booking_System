<?php
// Bring in our master configuration settings
require_once __DIR__ . '/../config.php';
// Include our MySQLi connection file to read/write to tables
include 'db_connect.php'; 

// Make sure the session is active so we can track who is logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the user ID from the active login session
$user_id = $_SESSION['user_id'] ?? 0;
if ($user_id == 0) {
    die("<script>window.location.href='../Customer_Module/homepage.php';</script>");
}

// Fetch REAL balance from database
// Prepare an SQL statement to look up the user's cash balance securely
$stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$user_data = $res->fetch_assoc();
// Save the balance number, default to 0.00 if it comes back empty
$current_balance = $user_data['wallet_balance'] ?? 0.00;

// Smart Return Logic
// Check the URL parameter to see if the user came from 'checkout' or just the regular dashboard link
$return_to = $_GET['return'] ?? 'dashboard'; 
$b_id = $_GET['booking_id'] ?? 0; // Remembers current booking ID if they are topping up mid-checkout
$b_amt = $_GET['amount'] ?? 0;    // Remembers checkout cost total if they are mid-checkout

// If they came from the checkout screen, build a back link to send them right back there
if ($return_to === 'checkout') {
    $back_url = "checkout.php?booking_id=$b_id&amount=$b_amt";
    $back_label = "Back to Checkout";
} else {
    // Otherwise, build a back link to send them back to their standard dashboard view
    $back_url = "../Customer_Module/dashboard.php";
    $back_label = "Back to Dashboard";
}

// FIXED RELATION QUERY: Maps payments back to the users table via bookings link row maps safely
$wallet_logs = [];

// 1. EASY HUMAN COMMENT: Upgraded SQL query to fetch raw amount, final_amount, and payment_method types to catch refunds live
$log_stmt = $conn->prepare("
    SELECT p.amount, p.final_amount, p.payment_method, p.payment_status, p.payment_date 
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    WHERE b.user_id = ?
    ORDER BY p.payment_date DESC 
    LIMIT 10
");
$log_stmt->bind_param("i", $user_id);
$log_stmt->execute();
$log_res = $log_stmt->get_result();
while ($log_row = $log_res->fetch_assoc()) {
    $wallet_logs[] = $log_row;
}

// 2. EASY HUMAN COMMENT: Read standalone top-ups from memory session tracking arrays if they exist
if (isset($_SESSION['recent_topups']) && is_array($_SESSION['recent_topups'])) {
    foreach ($_SESSION['recent_topups'] as $session_log) {
        // Add the top-up row into the front of our display array list
        $wallet_logs[] = [
            'amount' => $session_log['amount'],
            'final_amount' => $session_log['amount'], // Balance metric replication map
            'payment_method' => $session_log['payment_method'],
            'payment_status' => $session_log['payment_status'],
            'payment_date' => $session_log['payment_date']
        ];
    }
    
    // Sort all rows together so the newest date item always stays at the top of the card view
    usort($wallet_logs, function($item_a, $item_b) {
        return strtotime($item_b['payment_date']) - strtotime($item_a['payment_date']);
    });
    
    // Slice array table results row boundaries to 10 entries max lines
    $wallet_logs = array_slice($wallet_logs, 0, 10);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Wallet | Smash Arena</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
* { 
    margin: 0; 
    padding: 0; 
    box-sizing: border-box; 
} /* Baseline structural resets to remove default spacing anomalies */

body { 
    font-family: 'Inter', sans-serif; 
    background: #f5f9f0; 
    padding: 2rem; 
    display: flex; 
    justify-content: center; 
    align-items: center; 
    min-height: 100vh; 
}

.wallet-container { 
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 2rem; 
    width: 100%; 
    max-width: 1000px; 
    align-items: stretch; 
} /* EASY HUMAN COMMENT: Changes alignment to stretch to force equal column balance sizing levels */

.wallet-card { 
    background: white; 
    padding: 2.5rem; 
    border-radius: 32px; 
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05); 
    border: 1px solid #eaf5e6; 
    text-align: center; 
    display: flex; 
    flex-direction: column; 
    justify-content: space-between; 
} /* Left operations control panel grid wrapper cards blueprints components */

.balance-box { 
    background: linear-gradient(135deg, #2b7e3a, #113f19); 
    color: white; 
    padding: 2rem; 
    border-radius: 24px; 
    margin: 1.5rem 0; 
    box-shadow: 0 8px 20px rgba(43, 126, 58, 0.15); 
} /* Gradient balance indicator box context properties styles template layout rules */

.quick-grid { 
    display: grid; 
    grid-template-columns: repeat(3, 1fr); 
    gap: 12px; 
    margin-bottom: 20px; 
} /* Quick select grids mapping layout spacing definitions metrics */

.amt-btn { 
    background: #f8faf5; 
    border: 1.5px solid #e0e8dc; 
    padding: 14px; 
    border-radius: 14px; 
    cursor: pointer; 
    font-weight: 700; 
    color: #2b7e3a; 
    transition: 0.2s; 
    font-size: 0.95rem; 
} /* Quick choose amounts shortcut selection buttons aesthetics styles definitions blocks */

.amt-btn:hover { 
    background: #eaf5e6; 
    color: #1f5a2a; 
    border-color: #2b7e3a; 
}

.amt-btn.active { 
    background: #2b7e3a; 
    color: white; 
    border-color: #2b7e3a; 
    box-shadow: 0 4px 10px rgba(43, 126, 58, 0.2); 
}

.method-card { 
    border: 1.5px solid #e0e0e0; 
    padding: 16px; 
    border-radius: 14px; 
    margin-bottom: 12px; 
    display: flex; 
    align-items: center; 
    cursor: pointer; 
    text-align: left; 
    background: white; 
    transition: 0.2s; 
} /* External reload funding channel selector button option wrapper row card layout characteristics */

.method-card:hover { 
    border-color: #2b7e3a; 
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03); 
}

.method-card input[type="radio"] { 
    accent-color: #2b7e3a; 
    transform: scale(1.1); 
}

.topup-input { 
    width: 100%; 
    padding: 1rem; 
    border-radius: 16px; 
    border: 2px solid #e0e8dc; 
    margin-bottom: 15px; 
    text-align: center; 
    font-size: 1.6rem; 
    font-weight: 800; 
    color: #2b7e3a; 
    background: #fafdfa; 
    outline: none; 
    transition: 0.2s; 
} /* Large numerical custom deposit text field container block parameters layouts styling properties */

.topup-input:focus { 
    border-color: #2b7e3a; 
    background: white; 
    box-shadow: 0 0 0 4px rgba(43, 126, 58, 0.08); 
}

.btn-reload { 
    background: #2b7e3a; 
    color: white; 
    border: none; 
    padding: 1.1rem; 
    border-radius: 50px; 
    width: 100%; 
    font-weight: 700; 
    cursor: pointer; 
    font-size: 1rem; 
    transition: 0.2s; 
    box-shadow: 0 4px 12px rgba(43, 126, 58, 0.15); 
} /* Main confirmation continuation action button element attributes template styling scripts rules */

.btn-reload:hover { 
    background: #1f5a2a; 
    transform: translateY(-1px); 
}

.back-link { 
    display: inline-block; 
    margin-top: 1.5rem; 
    color: #666; 
    text-decoration: none; 
    font-size: 0.9rem; 
    font-weight: 600; 
    transition: 0.2s; 
}

.back-link:hover { 
    color: #ff4d4d; 
    text-decoration: underline; 
}

.history-section { 
    background: white; 
    padding: 2.5rem; 
    border-radius: 32px; 
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05); 
    border: 1px solid #eaf5e6; 
    text-align: left; 
    max-height: 595px; 
    display: flex; 
    flex-direction: column; 
} /* EASY HUMAN COMMENT: Set max-height limits properties to match left dashboard panel dimensions exactly */

.history-title { 
    font-size: 1.2rem; 
    font-weight: 800; 
    color: #1e3a2a; 
    margin-bottom: 1.5rem; 
    display: flex; 
    align-items: center; 
    gap: 8px; 
    border-bottom: 2px solid #eaf5e6; 
    padding-bottom: 0.8rem; 
}

.history-list { 
    flex: 1; 
    overflow-y: auto; 
    padding-right: 8px; 
} /* EASY HUMAN COMMENT: Activates rolling overflow scrolling functionality for longer datasets tracking list items rows */

.history-list::-webkit-scrollbar { 
    width: 6px; 
}

.history-list::-webkit-scrollbar-track { 
    background: #f1f1f1; 
    border-radius: 10px; 
}

.history-list::-webkit-scrollbar-thumb { 
    background: #cbd5c0; 
    border-radius: 10px; 
}

.history-list::-webkit-scrollbar-thumb:hover { 
    background: #2b7e3a; 
}

.history-item { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    padding: 16px; 
    background: #fafdfa; 
    border: 1px solid #e0e8dc; 
    border-radius: 16px; 
    margin-bottom: 12px; 
    font-size: 0.9rem; 
}

.log-date { 
    font-size: 0.75rem; 
    color: #888; 
    display: block; 
    margin-top: 4px; 
}

.status-badge { 
    font-size: 0.7rem; 
    font-weight: 700; 
    padding: 4px 10px; 
    border-radius: 20px; 
    text-transform: uppercase; 
    margin-left: 6px; 
}

.badge-success { 
    background: #d4edda; 
    color: #155724; 
}

.badge-failed { 
    background: #f8d7da; 
    color: #721c24; 
}

.badge-refund {
    background: #e3f2fd;
    color: #0d47a1;
} /* EASY HUMAN COMMENT: Added custom blue styling badge parameters for refunded entries layout items tracking states */

@media (max-width: 850px) { 
    .wallet-container { 
        grid-template-columns: 1fr; 
    } 
    .history-section { 
        max-height: auto; 
        min-height: auto; 
    } 
} /* Fluid mobile layout breakdown parameters context rules triggers map stacking */
    </style>
</head>
<body>
    
    <div class="wallet-container">
        
        <div class="wallet-card">
            <div>
                <h2 style="color: #2b7e3a; font-weight: 800; font-size: 1.6rem;"><i class="fas fa-wallet"></i> Smash Wallet</h2>
                
                <div class="balance-box">
                    <p style="opacity: 0.85; font-size: 0.9rem; font-weight: 500; letter-spacing: 0.5px;">Current Balance</p>
                    <h1 style="font-size: 2.8rem; margin: 5px 0; font-weight: 800; letter-spacing: -0.5px;">RM <?php echo number_format($current_balance, 2); ?></h1>
                </div>

                <p style="text-align: left; font-weight: 700; font-size: 0.9rem; color: #1e3a2a; margin-bottom: 10px;">Quick Select:</p>
                <div class="quick-grid">
                    <button type="button" class="amt-btn" onclick="selectAmt(10, this)">RM 10</button>
                    <button type="button" class="amt-btn" onclick="selectAmt(20, this)">RM 20</button>
                    <button type="button" class="amt-btn" onclick="selectAmt(50, this)">RM 50</button>
                </div>

                <form action="wallet_gateway.php" method="POST" onsubmit="return validateReload()">
                    <input type="hidden" name="return_to" value="<?php echo $return_to; ?>">
                    <input type="hidden" name="booking_id" value="<?php echo $b_id; ?>">
                    <input type="hidden" name="amount" value="<?php echo $b_amt; ?>">

                    <input type="number" name="reload_amount" id="reload_amt" class="topup-input" placeholder="0.00" min="1" max="1000" step="0.01" oninput="this.value = this.value.replace(/[^0-9.]/g, '');">
                    
                    <p style="text-align: left; font-weight: 700; font-size: 0.9rem; color: #1e3a2a; margin: 5px 0 10px;">Payment Method:</p>
                    
                    <label class="method-card">
                        <input type="radio" name="pay_method" value="Bank" checked>
                        <span style="margin-left:12px; font-weight: 600; color: #333; font-size: 0.95rem;">Online Banking</span>
                    </label>

                    <label class="method-card">
                        <input type="radio" name="pay_method" value="Card">
                        <span style="margin-left:12px; font-weight: 600; color: #333; font-size: 0.95rem;">Credit / Debit Card</span>
                    </label>
                    
                    <label class="method-card">
                        <input type="radio" name="pay_method" value="TNG">
                        <span style="margin-left:12px; font-weight: 600; color: #333; font-size: 0.95rem;">Touch 'n Go eWallet</span>
                    </label>

                    <button type="submit" class="btn-reload" style="margin-top: 15px;">Next Step →</button>
                </form>
            </div>
            
            <a href="<?php echo $back_url; ?>" class="back-link">← <?php echo $back_label; ?></a>
        </div>

        <div class="history-section">
            <div class="history-title">
                <i class="fas fa-history" style="color: #2b7e3a;"></i> Recent Wallet Transactions
            </div>
            
            <div class="history-list">
                <?php if (count($wallet_logs) > 0): ?>
                    <?php foreach ($wallet_logs as $log): 
                        // EASY HUMAN COMMENT: Dynamically handle string presentation titles and positive/negative indicators based on checkout vs refund type methods
                        $is_refund = ($log['payment_method'] === 'Refund' || $log['payment_method'] === 'Refund_Addons');
                        $display_amt = $is_refund ? (float)$log['final_amount'] : (float)$log['amount'];
                        
                        if ($log['payment_method'] === 'Refund') {
                            $display_title = "Full Court Cancel Refund";
                            $amt_color = "#0d47a1";
                            $prefix = "+RM ";
                            $badge_class = "status-badge badge-refund";
                            $badge_label = "Refunded";
                        } elseif ($log['payment_method'] === 'Refund_Addons') {
                            $display_title = "Equipment Add-on Refund";
                            $amt_color = "#0d47a1";
                            $prefix = "+RM ";
                            $badge_class = "status-badge badge-refund";
                            $badge_label = "Refunded";
                        } else {
                            $display_title = "Loaded via " . htmlspecialchars($log['payment_method']);
                            $amt_color = "#2b7e3a";
                            $prefix = "+RM ";
                            $badge_class = strtolower($log['payment_status']) === 'success' ? "status-badge badge-success" : "status-badge badge-failed";
                            $badge_label = strtolower($log['payment_status']) === 'success' ? "Success" : "Failed";
                        }
                    ?>
                        <div class="history-item">
                            <div>
                                <span style="font-weight: 600; color: #333;">
                                    <?php echo $display_title; ?>
                                </span>
                                <small class="log-date">
                                    <?php echo date('M j, Y • h:i A', strtotime($log['payment_date'])); ?>
                                </small>
                            </div>
                            <div style="text-align: right;">
                                <strong style="color: <?php echo $amt_color; ?>; font-size: 0.95rem;">
                                    <?php echo $prefix . number_format($display_amt, 2); ?>
                                </strong>
                                <br>
                                <span class="<?php echo $badge_class; ?>"><?php echo $badge_label; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #888; font-size: 0.85rem; padding: 2rem 0; text-align: center;">No top-up transaction records found.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <script>
        function selectAmt(v, buttonElement) { 
            document.getElementById('reload_amt').value = v; 
            document.querySelectorAll('.amt-btn').forEach(btn => btn.classList.remove('active'));
            buttonElement.classList.add('active');
        }
        
        document.getElementById('reload_amt').addEventListener('input', function() {
            document.querySelectorAll('.amt-btn').forEach(btn => btn.classList.remove('active'));
        });

        function validateReload() {
            const a = parseFloat(document.getElementById('reload_amt').value);
            if (isNaN(a) || a < 1) { 
                alert("Security Error: Invalid reload allocation amount. Minimum deposit is RM 1.00"); 
                return false; 
            }
            return true;
        }
    </script>
</body>
</html>