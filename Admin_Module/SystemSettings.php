<?php
session_start();

// Only Superadmin can access system settings
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Superadmin') {
    header("Location: LoginPage.php");
    exit();
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$conn = mysqli_connect("localhost", "root", "", "badminton_hub");

$username     = $_SESSION['username'];
$role         = $_SESSION['role'];
$display_name = $username;

$message = "";

// =============================================
// HANDLE FORM SUBMISSIONS
// =============================================

// --- Handle Business Hours + Peak Hours Save ---
// This runs when admin submits the hours form
if (isset($_POST['save_hours'])) {

    // Get values from the form, trim removes extra spaces
    $open_time  = trim($_POST['open_time']);
    $close_time = trim($_POST['close_time']);
    $peak_start = trim($_POST['peak_start']);
    $peak_end   = trim($_POST['peak_end']);

    // Update each setting in the settings table
    // mysqli_real_escape_string() makes the value safe before putting in SQL
    $conn->query("UPDATE settings SET setting_value = '" . mysqli_real_escape_string($conn, $open_time)  . "' WHERE setting_key = 'open_time'");
    $conn->query("UPDATE settings SET setting_value = '" . mysqli_real_escape_string($conn, $close_time) . "' WHERE setting_key = 'close_time'");
    $conn->query("UPDATE settings SET setting_value = '" . mysqli_real_escape_string($conn, $peak_start) . "' WHERE setting_key = 'peak_start'");
    $conn->query("UPDATE settings SET setting_value = '" . mysqli_real_escape_string($conn, $peak_end)   . "' WHERE setting_key = 'peak_end'");

    // Also update ALL courts in court_availability to match new open/close time
    // This keeps court_availability in sync with the global settings
    $conn->query("UPDATE court_availability SET 
        start_time = '" . mysqli_real_escape_string($conn, $open_time) . ":00',
        end_time   = '" . mysqli_real_escape_string($conn, $close_time) . ":00'
    ");

    $message = "<div class='badge success' style='width:100%;padding:15px;margin-bottom:20px;'>Hours updated successfully!</div>";
}

// --- Handle Add Closed Day ---
if (isset($_POST['add_closed_day'])) {
    $closed_date = mysqli_real_escape_string($conn, $_POST['closed_date']);
    $reason      = mysqli_real_escape_string($conn, trim($_POST['reason']));

    // Check if this date already exists to avoid duplicate
    $check = mysqli_query($conn, "SELECT id FROM closed_days WHERE closed_date = '$closed_date'");
    if (mysqli_num_rows($check) > 0) {
        $message = "<div class='badge pending' style='width:100%;padding:15px;margin-bottom:20px;'>This date is already marked as closed.</div>";
    } else {
        mysqli_query($conn, "INSERT INTO closed_days (closed_date, reason) VALUES ('$closed_date', '$reason')");
        $message = "<div class='badge success' style='width:100%;padding:15px;margin-bottom:20px;'>Closed day added!</div>";
    }
}

// --- Handle Delete Closed Day ---
if (isset($_GET['delete_closed'])) {
    $did = intval($_GET['delete_closed']); // intval() makes sure it's a number
    mysqli_query($conn, "DELETE FROM closed_days WHERE id = $did");
    header("Location: SystemSettings.php?deleted=1");
    exit();
}

// --- Handle Add Promo Code ---
if (isset($_POST['add_promo'])) {
    $code           = strtoupper(trim($_POST['promo_code'])); // strtoupper = make all letters capital
    $discount_type  = mysqli_real_escape_string($conn, $_POST['discount_type']);
    $discount_value = floatval($_POST['discount_value']); // floatval = make sure it's a decimal number
    $valid_from     = $_POST['valid_from'];
    $valid_until    = $_POST['valid_until'];

    // Check if this code already exists
    $check = mysqli_query($conn, "SELECT id FROM promo_codes WHERE code = '" . mysqli_real_escape_string($conn, $code) . "'");
    if (mysqli_num_rows($check) > 0) {
        $message = "<div class='badge pending' style='width:100%;padding:15px;margin-bottom:20px;'>Promo code already exists!</div>";
    } else {
        mysqli_query($conn, "INSERT INTO promo_codes (code, discount_type, discount_value, valid_from, valid_until) 
            VALUES ('" . mysqli_real_escape_string($conn, $code) . "', '$discount_type', $discount_value, '$valid_from', '$valid_until')");
        $message = "<div class='badge success' style='width:100%;padding:15px;margin-bottom:20px;'>Promo code created!</div>";
    }
}

// --- Handle Toggle Promo Code Active/Inactive ---
if (isset($_GET['toggle_promo'])) {
    $pid        = intval($_GET['toggle_promo']);
    $new_status = intval($_GET['active']); // 1 = active, 0 = inactive
    mysqli_query($conn, "UPDATE promo_codes SET is_active = $new_status WHERE id = $pid");
    header("Location: SystemSettings.php?updated=1");
    exit();
}

// --- Handle Delete Promo Code ---
if (isset($_GET['delete_promo'])) {
    $pid = intval($_GET['delete_promo']);
    mysqli_query($conn, "DELETE FROM promo_codes WHERE id = $pid");
    header("Location: SystemSettings.php?deleted=1");
    exit();
}

// =============================================
// READ DATA FROM DATABASE (for displaying)
// =============================================

// Read all settings into an easy-to-use array
// After this, you can use $settings['open_time'] to get the value
$settings     = [];
$settings_res = mysqli_query($conn, "SELECT setting_key, setting_value FROM settings");
while ($row = mysqli_fetch_assoc($settings_res)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Read all closed days, ordered by date
$closed_days = mysqli_query($conn, "SELECT * FROM closed_days ORDER BY closed_date ASC");

// Read all promo codes, newest first
$promo_codes = mysqli_query($conn, "SELECT * FROM promo_codes ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Badminton Hub - System Settings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap">
    <link rel="stylesheet" href="SuperAdminDashboard.css">
    <link rel="stylesheet" href="AdminManagement.css">
    <link rel="stylesheet" href="SystemSettings.css">
</head>
<body>

    <?php include 'navbar.php'; ?>

    <main class="content">
        <div class="manage-container">

            <header class="management-header">
                <div>
                    <h1>System Settings</h1>
                    <p>Manage arena business hours, peak pricing, closed days, and promo codes.</p>
                </div>
            </header>

            <?php if ($message !== "") echo $message; ?>
            <?php if (isset($_GET['deleted'])): ?>
                <div class="badge pending" style="width:100%;padding:15px;margin-bottom:20px;">Deleted successfully.</div>
            <?php endif; ?>
            <?php if (isset($_GET['updated'])): ?>
                <div class="badge success" style="width:100%;padding:15px;margin-bottom:20px;">Updated successfully.</div>
            <?php endif; ?>

            <!-- ===================== SECTION 1: BUSINESS HOURS & PEAK RULES ===================== -->
            <div class="settings-card">
                <h3><i class="fas fa-clock"></i> Business Hours & Peak Pricing</h3>
                <p class="settings-desc">Changes here will apply to all courts automatically.</p>

                <!-- method="POST" means form data is sent to this same page -->
                <form method="POST" action="">
                    <div class="settings-grid">

                        <div class="settings-field">
                            <label>Opening Time</label>
                            <!-- value= prefills the current saved setting -->
                            <input type="time" name="open_time" class="form-control"
                                   value="<?php echo htmlspecialchars($settings['open_time'] ?? '08:00'); ?>" required>
                        </div>

                        <div class="settings-field">
                            <label>Closing Time</label>
                            <input type="time" name="close_time" class="form-control"
                                   value="<?php echo htmlspecialchars($settings['close_time'] ?? '01:00'); ?>" required>
                        </div>

                        <div class="settings-field">
                            <label>Peak Hour Start</label>
                            <input type="time" name="peak_start" class="form-control"
                                   value="<?php echo htmlspecialchars($settings['peak_start'] ?? '15:00'); ?>" required>
                        </div>

                        <div class="settings-field">
                            <label>Peak Hour End</label>
                            <input type="time" name="peak_end" class="form-control"
                                   value="<?php echo htmlspecialchars($settings['peak_end'] ?? '23:00'); ?>" required>
                        </div>

                    </div>

                    <!-- name="save_hours" is how PHP knows which form was submitted -->
                    <button type="submit" name="save_hours" class="btn-add-account" style="margin-top:15px;">
                        <i class="fas fa-save"></i> Save Hours
                    </button>
                </form>
            </div>

            <!-- ===================== SECTION 2: CLOSED DAYS ===================== -->
            <div class="settings-card">
                <h3><i class="fas fa-calendar-times"></i> Closed Days</h3>
                <p class="settings-desc">Mark dates when the arena is closed. Players cannot book on these days.</p>

                <!-- Add new closed day form -->
                <form method="POST" action="" style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px;">
                    <div>
                        <label>Date</label>
                        <input type="date" name="closed_date" class="form-control" required>
                    </div>
                    <div style="flex:1; min-width:200px;">
                        <label>Reason</label>
                        <input type="text" name="reason" class="form-control" placeholder="e.g. Public Holiday">
                    </div>
                    <div style="display:flex; align-items:flex-end;">
                        <button type="submit" name="add_closed_day" class="btn-add-account">
                            <i class="fas fa-plus"></i> Add
                        </button>
                    </div>
                </form>

                <!-- List of existing closed days -->
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reason</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($cd = mysqli_fetch_assoc($closed_days)): ?>
                        <tr>
                            <!-- date("d M Y") formats the date nicely, e.g. 01 Jan 2025 -->
                            <td><?php echo date("d M Y", strtotime($cd['closed_date'])); ?></td>
                            <td><?php echo htmlspecialchars($cd['reason']); ?></td>
                            <td>
                                <a href="SystemSettings.php?delete_closed=<?php echo $cd['id']; ?>"
                                   onclick="return confirm('Remove this closed day?');">
                                    <i class="fas fa-trash-alt" style="color:#ef4444; font-size:16px;"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- ===================== SECTION 3: PROMO CODES ===================== -->
            <div class="settings-card">
                <h3><i class="fas fa-tag"></i> Promo Codes</h3>
                <p class="settings-desc">Create discount codes for players to use during payment.</p>

                <!-- Add new promo code form -->
                <form method="POST" action="" style="margin-bottom:20px;">
                    <div class="settings-grid">

                        <div class="settings-field">
                            <label>Promo Code</label>
                            <input type="text" name="promo_code" class="form-control"
                                   placeholder="e.g. SAVE20" style="text-transform:uppercase;" required>
                        </div>

                        <div class="settings-field">
                            <label>Discount Type</label>
                            <select name="discount_type" class="form-control">
                                <!-- percentage = e.g. 10% off -->
                                <option value="percentage">Percentage (%)</option>
                                <!-- fixed = e.g. RM5 off -->
                                <option value="fixed">Fixed Amount (RM)</option>
                            </select>
                        </div>

                        <div class="settings-field">
                            <label>Discount Value</label>
                            <input type="number" name="discount_value" class="form-control"
                                   placeholder="e.g. 10" step="0.01" min="0" required>
                        </div>

                        <div class="settings-field">
                            <label>Valid From</label>
                            <input type="date" name="valid_from" class="form-control" required>
                        </div>

                        <div class="settings-field">
                            <label>Valid Until</label>
                            <input type="date" name="valid_until" class="form-control" required>
                        </div>

                    </div>

                    <button type="submit" name="add_promo" class="btn-add-account" style="margin-top:15px;">
                        <i class="fas fa-plus"></i> Create Promo Code
                    </button>
                </form>

                <!-- List of existing promo codes -->
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Valid Period</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($promo = mysqli_fetch_assoc($promo_codes)): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($promo['code']); ?></strong></td>
                            <td><?php echo $promo['discount_type'] === 'percentage' ? 'Percentage' : 'Fixed (RM)'; ?></td>
                            <td>
                                <?php
                                // Show the value with correct format
                                if ($promo['discount_type'] === 'percentage') {
                                    echo $promo['discount_value'] . '%';
                                } else {
                                    echo 'RM ' . number_format($promo['discount_value'], 2);
                                }
                                ?>
                            </td>
                            <td>
                                <?php echo date("d M Y", strtotime($promo['valid_from'])); ?>
                                –
                                <?php echo date("d M Y", strtotime($promo['valid_until'])); ?>
                            </td>
                            <td>
                                <?php if ($promo['is_active'] == 1): ?>
                                    <span class="badge success">Active</span>
                                <?php else: ?>
                                    <span class="badge pending">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($promo['is_active'] == 1): ?>
                                    <!-- Currently active, clicking will deactivate it -->
                                    <a href="SystemSettings.php?toggle_promo=<?php echo $promo['id']; ?>&active=0"
                                       style="margin-right:8px;" title="Deactivate">
                                        <i class="fas fa-toggle-on" style="color:#22c55e; font-size:18px;"></i>
                                    </a>
                                <?php else: ?>
                                    <!-- Currently inactive, clicking will activate it -->
                                    <a href="SystemSettings.php?toggle_promo=<?php echo $promo['id']; ?>&active=1"
                                       style="margin-right:8px;" title="Activate">
                                        <i class="fas fa-toggle-off" style="color:#94a3b8; font-size:18px;"></i>
                                    </a>
                                <?php endif; ?>

                                <a href="SystemSettings.php?delete_promo=<?php echo $promo['id']; ?>"
                                   onclick="return confirm('Delete this promo code?');">
                                    <i class="fas fa-trash-alt" style="color:#ef4444; font-size:18px;"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

    <script src="SuperAdminDashboard.js"></script>

</body>
</html>