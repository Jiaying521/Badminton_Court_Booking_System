<?php
/* SystemSettings.php — Superadmin/Admin manages business hours, closed days, promo codes, vouchers, pricing & contact info. */

/* Start session (must be at the very top) */
session_start();

/* Access control */
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['Superadmin', 'Admin'])) {
    header("Location: ../LoginPage.php");
    exit();
}

/* Disable browser caching so settings always show fresh values */
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

/* Connect to database */
$conn = mysqli_connect("localhost", "root", "", "badminton_hub");
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

/* Logged-in user info from session */
$username     = $_SESSION['username'];
$role         = $_SESSION['role'];
$display_name = $username;

/* This page sits at Admin_Module root, so navbar links don't need a prefix. */
$base_path = '../';

/* Toast queue — every entry is shown as a floating notification at the bottom-left.
   Each entry is ['text' => string, 'type' => 'success' | 'pending' | 'error']. */
$toasts = [];


/* === Handle Form Submissions (POST and GET actions) === */

/* Action A: Save Business Hours & Peak Hours (triggered by "Save Hours" button) */
if (isset($_POST['save_hours'])) {

    // Get the time values from the form. trim() removes extra spaces.
    $open_time  = trim($_POST['open_time']);
    $close_time = trim($_POST['close_time']);
    $peak_start = trim($_POST['peak_start']);
    $peak_end   = trim($_POST['peak_end']);

    // Update each setting row in the settings table.
    // mysqli_real_escape_string() protects against SQL injection attacks.
    $conn->query("UPDATE settings SET setting_value = '" . mysqli_real_escape_string($conn, $open_time)  . "' WHERE setting_key = 'open_time'");
    $conn->query("UPDATE settings SET setting_value = '" . mysqli_real_escape_string($conn, $close_time) . "' WHERE setting_key = 'close_time'");
    $conn->query("UPDATE settings SET setting_value = '" . mysqli_real_escape_string($conn, $peak_start) . "' WHERE setting_key = 'peak_start'");
    $conn->query("UPDATE settings SET setting_value = '" . mysqli_real_escape_string($conn, $peak_end)   . "' WHERE setting_key = 'peak_end'");

    // Also sync the court_availability table so all courts match the new global hours.
    $conn->query("UPDATE court_availability SET 
        start_time = '" . mysqli_real_escape_string($conn, $open_time)  . ":00',
        end_time   = '" . mysqli_real_escape_string($conn, $close_time) . ":00'
    ");

    $toasts[] = ['text' => 'Hours updated successfully!', 'type' => 'success'];
}


/* Action B: Add a Closed Day (triggered by "Add" button) */
if (isset($_POST['add_closed_day'])) {

    $closed_date = mysqli_real_escape_string($conn, $_POST['closed_date']);
    $reason      = mysqli_real_escape_string($conn, trim($_POST['reason']));

    // Check if this date already exists to prevent duplicates.
    $check = mysqli_query($conn, "SELECT id FROM closed_days WHERE closed_date = '$closed_date'");

    if (mysqli_num_rows($check) > 0) {
        // Date already exists — show a warning.
        $toasts[] = ['text' => 'This date is already marked as closed.', 'type' => 'pending'];
    } else {
        // Date does not exist — insert a new record.
        mysqli_query($conn, "INSERT INTO closed_days (closed_date, reason) VALUES ('$closed_date', '$reason')");
        $toasts[] = ['text' => 'Closed day added!', 'type' => 'success'];
    }
}


/* Action C: Delete a Closed Day (trash icon, URL has ?delete_closed=ID) */
if (isset($_GET['delete_closed'])) {

    // intval() makes sure the ID is a plain integer — protects against SQL injection.
    $did = intval($_GET['delete_closed']);
    mysqli_query($conn, "DELETE FROM closed_days WHERE id = $did");

    // Redirect after deleting so refreshing the page will not delete again.
    header("Location: SystemSettings.php?deleted=1");
    exit();
}


/* Action D: Add a Promo Code (triggered by "Create Promo Code" button) */
if (isset($_POST['add_promo'])) {

    // strtoupper() converts all letters to uppercase, e.g. save20 becomes SAVE20
    $code           = strtoupper(trim($_POST['promo_code']));
    $discount_type  = mysqli_real_escape_string($conn, $_POST['discount_type']);
    $discount_value = floatval($_POST['discount_value']); // floatval() ensures it is a decimal number
    $valid_from     = $_POST['valid_from'];
    $valid_until    = $_POST['valid_until'];

    // Check if this promo code already exists.
    $check = mysqli_query($conn, "SELECT id FROM promo_codes WHERE code = '" . mysqli_real_escape_string($conn, $code) . "'");

    if (mysqli_num_rows($check) > 0) {
        // Already exists — show a warning.
        $toasts[] = ['text' => 'Promo code already exists!', 'type' => 'pending'];
    } else {
        // Does not exist — insert a new record.
        mysqli_query($conn, "INSERT INTO promo_codes (code, discount_type, discount_value, valid_from, valid_until) 
            VALUES ('" . mysqli_real_escape_string($conn, $code) . "', '$discount_type', $discount_value, '$valid_from', '$valid_until')");
        $toasts[] = ['text' => 'Promo code created!', 'type' => 'success'];
    }
}


/* Action E: Toggle Promo Code Active/Inactive (URL has ?toggle_promo=ID&active=0or1) */
if (isset($_GET['toggle_promo'])) {

    $pid        = intval($_GET['toggle_promo']);
    $new_status = intval($_GET['active']); // 1 = active, 0 = inactive

    mysqli_query($conn, "UPDATE promo_codes SET is_active = $new_status WHERE id = $pid");

    header("Location: SystemSettings.php?updated=1");
    exit();
}


/* Action F: Delete a Promo Code (trash icon, URL has ?delete_promo=ID) */
if (isset($_GET['delete_promo'])) {

    $pid = intval($_GET['delete_promo']);
    mysqli_query($conn, "DELETE FROM promo_codes WHERE id = $pid");

    header("Location: SystemSettings.php?deleted=1");
    exit();
}

/* Action G: Add Voucher */
if (isset($_POST['add_voucher'])) {
    $title           = mysqli_real_escape_string($conn, trim($_POST['voucher_title']));
    $discount_amount = floatval($_POST['discount_amount']);
    $points_required = intval($_POST['points_required']);
    $description     = mysqli_real_escape_string($conn, trim($_POST['description']));

    mysqli_query($conn, "INSERT INTO voucher (title, discount_amount, points_required, description) 
        VALUES ('$title', $discount_amount, $points_required, '$description')");
    $toasts[] = ['text' => 'Voucher created!', 'type' => 'success'];
}

/* Action H: Delete Voucher (blocks delete if any customer has already claimed it) */
if (isset($_GET['delete_voucher'])) {
    $vid = intval($_GET['delete_voucher']);

    // Check if anyone has already claimed/used this voucher before allowing the delete.
    $check = mysqli_query($conn, "SELECT COUNT(*) AS c FROM user_vouchers WHERE voucher_id = $vid");
    $used_count = (int) mysqli_fetch_assoc($check)['c'];

    if ($used_count > 0) {
        // Block hard delete — it would orphan customer voucher records.
        header("Location: SystemSettings.php?deleted=blocked");
        exit();
    }

    mysqli_query($conn, "DELETE FROM voucher WHERE id = $vid");
    header("Location: SystemSettings.php?deleted=1");
    exit();
}


/* Action I: Save Pricing & Cancellation Policy (off_peak_price, peak_price, cancellation_hours, cancellation_fee) */
if (isset($_POST['save_pricing'])) {

    /* floatval/intval lock the values to numbers so nobody can sneak SQL in */
    $off_peak_price     = floatval($_POST['off_peak_price']);
    $peak_price         = floatval($_POST['peak_price']);
    $cancellation_hours = intval($_POST['cancellation_hours']);
    $cancellation_fee   = floatval($_POST['cancellation_fee']);

    /* Update each pricing/cancellation row in the settings table */
    mysqli_query($conn, "UPDATE settings SET setting_value = '$off_peak_price'     WHERE setting_key = 'off_peak_price'");
    mysqli_query($conn, "UPDATE settings SET setting_value = '$peak_price'         WHERE setting_key = 'peak_price'");
    mysqli_query($conn, "UPDATE settings SET setting_value = '$cancellation_hours' WHERE setting_key = 'cancellation_hours'");
    mysqli_query($conn, "UPDATE settings SET setting_value = '$cancellation_fee'   WHERE setting_key = 'cancellation_fee'");

    $toasts[] = ['text' => 'Pricing & cancellation settings updated!', 'type' => 'success'];
}


/* Action J: Save Contact Information (contact_phone, contact_email, contact_whatsapp, address) */
if (isset($_POST['save_contact'])) {

    /* mysqli_real_escape_string protects against SQL injection on text fields */
    $contact_phone    = mysqli_real_escape_string($conn, trim($_POST['contact_phone']));
    $contact_email    = mysqli_real_escape_string($conn, trim($_POST['contact_email']));
    $contact_whatsapp = mysqli_real_escape_string($conn, trim($_POST['contact_whatsapp']));
    $address          = mysqli_real_escape_string($conn, trim($_POST['address']));

    mysqli_query($conn, "UPDATE settings SET setting_value = '$contact_phone'    WHERE setting_key = 'contact_phone'");
    mysqli_query($conn, "UPDATE settings SET setting_value = '$contact_email'    WHERE setting_key = 'contact_email'");
    mysqli_query($conn, "UPDATE settings SET setting_value = '$contact_whatsapp' WHERE setting_key = 'contact_whatsapp'");
    mysqli_query($conn, "UPDATE settings SET setting_value = '$address'          WHERE setting_key = 'address'");

    $toasts[] = ['text' => 'Contact information updated!', 'type' => 'success'];
}


/* Pick up redirect-based toasts (after delete/toggle operations) */
if (isset($_GET['deleted'])) {
    if ($_GET['deleted'] === 'blocked') {
        $toasts[] = ['text' => 'Cannot delete: this voucher has already been claimed by customers.', 'type' => 'error'];
    } else {
        $toasts[] = ['text' => 'Deleted successfully.', 'type' => 'pending'];
    }
}
if (isset($_GET['updated'])) {
    $toasts[] = ['text' => 'Updated successfully.', 'type' => 'success'];
}


/* === Read Data from Database (for displaying on the page) === */

// Load all settings into an array so we can use $settings['open_time'] etc.
$settings     = [];
$settings_res = mysqli_query($conn, "SELECT setting_key, setting_value FROM settings");
while ($row = mysqli_fetch_assoc($settings_res)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Load all closed days, sorted by date (earliest first).
$closed_days = mysqli_query($conn, "SELECT * FROM closed_days ORDER BY closed_date ASC");

// Load all promo codes, newest first.
$promo_codes = mysqli_query($conn, "SELECT * FROM promo_codes ORDER BY created_at DESC");

// Load all vouchers
$vouchers = mysqli_query($conn, "SELECT * FROM voucher ORDER BY points_required ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Badminton Hub - System Settings</title>

    <!-- Icon library -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="../Dashboard/Dashboard.css">
    <link rel="stylesheet" href="../Superadmin/AdminManagement.css">
    <link rel="stylesheet" href="SystemSettings.css">
</head>
<body>

    <!-- Navigation bar (loaded from a shared file) -->
    <?php include '../navbar.php'; ?>

    <main class="content">
        <div class="manage-container">

            <!-- Page Title -->
            <header class="management-header">
                <div>
                    <h1>System Settings</h1>
                    <p>Manage how the arena runs day to day, from hours and pricing to promos, vouchers and contact details.</p>
                </div>
            </header>

            <!-- Quick-jump tab bar: slider glides under the active tab as you scroll/click -->
            <nav class="settings-tabs" id="settingsTabs">
                <span class="settings-tab-slider" id="settingsTabSlider"></span>
                <a href="#sec-hours"   class="settings-tab"><i class="fas fa-clock"></i> Business Hours</a>
                <a href="#sec-pricing" class="settings-tab"><i class="fas fa-tag"></i> Pricing & Cancellation</a>
                <a href="#sec-closed"  class="settings-tab"><i class="fas fa-calendar-times"></i> Closed Days</a>
                <a href="#sec-promo"   class="settings-tab"><i class="fas fa-percent"></i> Promo Codes</a>
                <a href="#sec-voucher" class="settings-tab"><i class="fas fa-ticket-alt"></i> Vouchers</a>
                <a href="#sec-contact" class="settings-tab"><i class="fas fa-address-card"></i> Contact Info</a>
            </nav>


            <!-- Section 1: Business Hours & Peak Pricing -->
            <div class="settings-card" id="sec-hours">
                <h3><i class="fas fa-clock"></i> Business Hours & Peak Pricing</h3>
                <p class="settings-desc">Changes here will apply to all courts automatically.</p>

                <!-- method="POST" sends the form data back to this same page -->
                <form method="POST" action="">
                    <div class="settings-grid">

                        <div class="settings-field">
                            <label>Opening Time</label>
                            <!-- value= pre-fills the input with the current saved setting.
                                 ?? sets a fallback default if the setting does not exist yet. -->
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

                    <!-- name="save_hours" tells PHP which form was submitted -->
                    <button type="submit" name="save_hours" class="btn-add-account">
                        <i class="fas fa-save"></i> Save Hours
                    </button>
                </form>
            </div>


            <!-- Section 1.5: Pricing & Cancellation Policy -->
            <div class="settings-card" id="sec-pricing">
                <h3><i class="fas fa-tag"></i> Pricing & Cancellation Policy</h3>
                <p class="settings-desc">Court hourly rates and the cancellation rule shown to customers.</p>

                <form method="POST" action="">
                    <div class="settings-grid">

                        <div class="settings-field">
                            <label>Off-Peak Price (RM / hour)</label>
                            <input type="number" step="0.01" min="0" name="off_peak_price" class="form-control"
                                   value="<?php echo htmlspecialchars($settings['off_peak_price'] ?? '10.00'); ?>" required>
                        </div>

                        <div class="settings-field">
                            <label>Peak Price (RM / hour)</label>
                            <input type="number" step="0.01" min="0" name="peak_price" class="form-control"
                                   value="<?php echo htmlspecialchars($settings['peak_price'] ?? '15.00'); ?>" required>
                        </div>

                        <div class="settings-field">
                            <label>Cancellation Notice<br><span class="label-hint">(hours before booking)</span></label>
                            <input type="number" min="0" name="cancellation_hours" class="form-control"
                                   value="<?php echo htmlspecialchars($settings['cancellation_hours'] ?? '2'); ?>" required>
                        </div>

                        <div class="settings-field">
                            <label>Cancellation Fee (RM)</label>
                            <input type="number" step="0.01" min="0" name="cancellation_fee" class="form-control"
                                   value="<?php echo htmlspecialchars($settings['cancellation_fee'] ?? '10.00'); ?>" required>
                        </div>

                    </div>

                    <button type="submit" name="save_pricing" class="btn-add-account">
                        <i class="fas fa-save"></i> Save Pricing & Cancellation
                    </button>
                </form>
            </div>


            <!-- Section 1.6: Contact Information -->
            <div class="settings-card" id="sec-contact">
                <h3><i class="fas fa-address-card"></i> Contact Information</h3>
                <p class="settings-desc">These details show on the Contact Us, FAQ and Cancellation Policy pages.</p>

                <form method="POST" action="">
                    <div class="settings-grid">

                        <div class="settings-field">
                            <label>Phone Number</label>
                            <input type="text" name="contact_phone" class="form-control"
                                   value="<?php echo htmlspecialchars($settings['contact_phone'] ?? '+603-1234 5678'); ?>">
                        </div>

                        <div class="settings-field">
                            <label>Email Address</label>
                            <input type="email" name="contact_email" class="form-control"
                                   value="<?php echo htmlspecialchars($settings['contact_email'] ?? 'smasharenabadminton@gmail.com'); ?>">
                        </div>

                        <div class="settings-field">
                            <label>WhatsApp Number</label>
                            <input type="text" name="contact_whatsapp" class="form-control"
                                   value="<?php echo htmlspecialchars($settings['contact_whatsapp'] ?? '+60 12-345 6789'); ?>">
                        </div>

                        <div class="settings-field">
                            <label>Address</label>
                            <input type="text" name="address" class="form-control"
                                   value="<?php echo htmlspecialchars($settings['address'] ?? '123 Jalan Badminton, Kuala Lumpur, Malaysia'); ?>">
                        </div>

                    </div>

                    <button type="submit" name="save_contact" class="btn-add-account">
                        <i class="fas fa-save"></i> Save Contact Info
                    </button>
                </form>
            </div>


            <!-- Section 2: Closed Days -->
            <div class="settings-card" id="sec-closed">
                <h3><i class="fas fa-calendar-times"></i> Closed Days</h3>
                <p class="settings-desc">Mark dates when the arena is closed. Players cannot book on these days.</p>

                <!-- Form to add a new closed day -->
                <form method="POST" action="" style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px;">

                    <div class="settings-field">
                        <label>Date</label>
                        <input type="date" name="closed_date" class="form-control" required>
                    </div>

                    <div class="settings-field" style="flex:1; min-width:200px;">
                        <label>Reason</label>
                        <input type="text" name="reason" class="form-control" placeholder="e.g. Public Holiday">
                    </div>

                    <div style="display:flex; align-items:flex-end;">
                        <button type="submit" name="add_closed_day" class="btn-add-account">
                            <i class="fas fa-plus"></i> Add
                        </button>
                    </div>

                </form>

                <!-- Table listing all existing closed days -->
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
                                <!-- Clicking the trash icon deletes this row.
                                     confirm() shows a popup asking the user to confirm first. -->
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


            <!-- Section 3: Promo Codes -->
            <div class="settings-card" id="sec-promo">
                <h3><i class="fas fa-tag"></i> Promo Codes</h3>
                <p class="settings-desc">Create discount codes for players to use during payment.</p>

                <!-- Form to create a new promo code -->
                <form method="POST" action="" style="margin-bottom:20px;">
                    <div class="settings-grid">

                        <div class="settings-field">
                            <label>Promo Code</label>
                            <!-- text-transform:uppercase shows uppercase visually in the browser.
                                 The actual conversion is handled in PHP using strtoupper(). -->
                            <input type="text" name="promo_code" class="form-control"
                                   placeholder="e.g. SAVE20" style="text-transform:uppercase;" required>
                        </div>

                        <div class="settings-field">
                            <label>Discount Type</label>
                            <select name="discount_type" class="form-control">
                                <option value="percentage">Percentage (%)</option>  <!-- e.g. 10% off -->
                                <option value="fixed">Fixed Amount (RM)</option>    <!-- e.g. RM5 off -->
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

                    <button type="submit" name="add_promo" class="btn-add-account">
                        <i class="fas fa-plus"></i> Create Promo Code
                    </button>
                </form>

                <!-- Table listing all existing promo codes -->
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

                            <!-- Show a readable label for the discount type -->
                            <td><?php echo $promo['discount_type'] === 'percentage' ? 'Percentage' : 'Fixed (RM)'; ?></td>

                            <!-- Show the value with the correct unit based on type -->
                            <td>
                                <?php if ($promo['discount_type'] === 'percentage'): ?>
                                    <?php echo $promo['discount_value']; ?>%
                                <?php else: ?>
                                    RM <?php echo number_format($promo['discount_value'], 2); ?>
                                <?php endif; ?>
                            </td>

                            <!-- Show the validity date range -->
                            <td>
                                <?php echo date("d M Y", strtotime($promo['valid_from'])); ?>
                                –
                                <?php echo date("d M Y", strtotime($promo['valid_until'])); ?>
                            </td>

                            <!-- Show Active or Inactive badge -->
                            <td>
                                <?php if ($promo['is_active'] == 1): ?>
                                    <span class="badge success">Active</span>
                                <?php else: ?>
                                    <span class="badge pending">Inactive</span>
                                <?php endif; ?>
                            </td>

                            <!-- Toggle and Delete action buttons -->
                            <td>
                                <?php if ($promo['is_active'] == 1): ?>
                                    <!-- Currently active — clicking this will deactivate it (active=0) -->
                                    <a href="SystemSettings.php?toggle_promo=<?php echo $promo['id']; ?>&active=0"
                                       style="margin-right:8px;" title="Deactivate">
                                        <i class="fas fa-toggle-on" style="color:#22c55e; font-size:18px;"></i>
                                    </a>
                                <?php else: ?>
                                    <!-- Currently inactive — clicking this will activate it (active=1) -->
                                    <a href="SystemSettings.php?toggle_promo=<?php echo $promo['id']; ?>&active=1"
                                       style="margin-right:8px;" title="Activate">
                                        <i class="fas fa-toggle-off" style="color:#94a3b8; font-size:18px;"></i>
                                    </a>
                                <?php endif; ?>

                                <!-- Delete button -->
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


            <!-- Section 4: Voucher Management -->
            <div class="settings-card" id="sec-voucher">
                <h3><i class="fas fa-ticket-alt"></i> Voucher Management</h3>
                <p class="settings-desc">Create vouchers that players can redeem using their loyalty points.</p>

                <!-- Form to add voucher -->
                <form method="POST" action="" style="margin-bottom:20px;">
                    <div class="settings-grid">

                        <div class="settings-field">
                            <label>Voucher Title</label>
                            <input type="text" name="voucher_title" class="form-control"
                                placeholder="e.g. RM 5.00 Court Discount" required>
                        </div>

                        <div class="settings-field">
                            <label>Discount Amount (RM)</label>
                            <input type="number" name="discount_amount" class="form-control"
                                placeholder="e.g. 5.00" step="0.01" min="0" required>
                        </div>

                        <div class="settings-field">
                            <label>Points Required</label>
                            <input type="number" name="points_required" class="form-control"
                                placeholder="e.g. 50" min="1" required>
                        </div>

                        <div class="settings-field">
                            <label>Description</label>
                            <input type="text" name="description" class="form-control"
                                placeholder="e.g. Redeem with 50 points">
                        </div>

                    </div>

                    <button type="submit" name="add_voucher" class="btn-add-account">
                        <i class="fas fa-plus"></i> Create Voucher
                    </button>
                </form>

                <!-- Voucher table -->
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Discount</th>
                            <th>Points Required</th>
                            <th>Description</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($v = mysqli_fetch_assoc($vouchers)): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($v['title']); ?></strong></td>
                            <td>RM <?php echo number_format($v['discount_amount'], 2); ?></td>
                            <td><?php echo $v['points_required']; ?> pts</td>
                            <td><?php echo htmlspecialchars($v['description']); ?></td>
                            <td>
                                <a href="SystemSettings.php?delete_voucher=<?php echo $v['id']; ?>"
                                onclick="return confirm('Delete this voucher?');">
                                    <i class="fas fa-trash-alt" style="color:#ef4444; font-size:16px;"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        </div> <!-- end manage-container -->
    </main>

    <!-- Scroll-to-top button (same design and behaviour as the one on Manage Bookings) -->
    <button id="scrollTopBtn" onclick="window.scrollTo({top:0, behavior:'smooth'})" aria-label="Back to top">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Toast notification stack (bottom-left). Server-rendered toasts sit here on load. -->
    <div class="toast-container" id="toastContainer">
        <?php foreach ($toasts as $t): ?>
            <?php
                $icon  = $t['type'] === 'success' ? 'fa-check'
                       : ($t['type'] === 'error' ? 'fa-xmark' : 'fa-exclamation');
                $label = $t['type'] === 'success' ? 'Success'
                       : ($t['type'] === 'error' ? 'Error'   : 'Notice');
            ?>
            <div class="toast <?php echo htmlspecialchars($t['type']); ?>" data-toast>
                <span class="toast-icon"><i class="fas <?php echo $icon; ?>"></i></span>
                <div class="toast-body">
                    <span class="toast-label"><?php echo $label; ?></span>
                    <span class="toast-text"><?php echo htmlspecialchars($t['text']); ?></span>
                </div>
                <button class="toast-close" type="button" aria-label="Dismiss">&times;</button>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- JavaScript file -->
    <script src="../Dashboard/Dashboard.js"></script>

    <script>
    /* Tab bar — animated slider glides to whichever tab is active, and the active tab
       is whichever section currently dominates the viewport (or whichever tab you clicked) */
    (function () {
        const nav    = document.getElementById('settingsTabs');
        const slider = document.getElementById('settingsTabSlider');
        const tabs   = nav ? nav.querySelectorAll('.settings-tab') : [];
        const targets = Array.from(tabs)
            .map(t => document.querySelector(t.getAttribute('href')))
            .filter(Boolean);

        function moveSliderTo(tab) {
            if (!tab || !slider) return;
            /* offsetLeft/offsetWidth give us the position relative to the nav (the offset parent) */
            slider.style.width     = tab.offsetWidth + 'px';
            slider.style.transform = 'translateX(' + tab.offsetLeft + 'px)';
            slider.style.height    = tab.offsetHeight + 'px';
            slider.style.top       = tab.offsetTop + 'px';
            slider.style.opacity   = '1';
        }

        function setActive(id) {
            let activeTab = null;
            tabs.forEach(t => {
                const isMatch = t.getAttribute('href') === '#' + id;
                t.classList.toggle('is-active', isMatch);
                if (isMatch) activeTab = t;
            });
            moveSliderTo(activeTab);
        }

        if ('IntersectionObserver' in window && targets.length) {
            const io = new IntersectionObserver((entries) => {
                /* Pick the entry closest to the top of the viewport */
                const visible = entries
                    .filter(e => e.isIntersecting)
                    .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)[0];
                if (visible) setActive(visible.target.id);
            }, { rootMargin: '-100px 0px -55% 0px', threshold: 0 });

            targets.forEach(el => io.observe(el));
        }

        /* Hover preview — slider follows the cursor over the tabs, snaps back to active on mouse leave */
        let activeTabRef = null;
        tabs.forEach(tab => {
            tab.addEventListener('mouseenter', () => moveSliderTo(tab));
            tab.addEventListener('click', () => {
                activeTabRef = tab;
            });
        });
        if (nav) {
            nav.addEventListener('mouseleave', () => {
                const current = nav.querySelector('.settings-tab.is-active');
                if (current) moveSliderTo(current);
            });
        }

        /* Default-active on first paint */
        if (targets[0]) {
            requestAnimationFrame(() => setActive(targets[0].id));
        }

        /* Reposition slider on resize so width tracks new tab sizes */
        window.addEventListener('resize', () => {
            const current = nav && nav.querySelector('.settings-tab.is-active');
            if (current) moveSliderTo(current);
        });
    })();

    /* Scroll-to-top button visibility — appears once user scrolls past 300px */
    (function () {
        const btn = document.getElementById('scrollTopBtn');
        if (!btn) return;
        window.addEventListener('scroll', () => {
            btn.classList.toggle('show', window.scrollY > 300);
        });
    })();

    /* Toast — slide in on load, auto-dismiss after 4s, manual close on click */
    (function () {
        const toasts = document.querySelectorAll('#toastContainer [data-toast]');
        toasts.forEach((toast, i) => {
            /* Stagger entrance so multiple toasts don't all snap in at once */
            setTimeout(() => toast.classList.add('show'), 80 + i * 90);

            const dismiss = () => {
                toast.classList.add('hide');
                setTimeout(() => toast.remove(), 400);
            };
            toast.querySelector('.toast-close').addEventListener('click', dismiss);
            setTimeout(dismiss, 4000 + i * 200);
        });
    })();
    </script>

</body>
</html>