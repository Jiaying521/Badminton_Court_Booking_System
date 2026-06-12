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
require_once __DIR__ . '/../log_activity.php';

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

    logActivity($conn, 'Settings', 'System Settings',
                "Updated business hours: open $open_time – close $close_time, peak $peak_start – $peak_end");
    $toasts[] = ['text' => 'Hours updated successfully!', 'type' => 'success'];
}


/* Action B: Add a Closed Day (triggered by "Add" button) */
if (isset($_POST['add_closed_day'])) {

    $closed_date = mysqli_real_escape_string($conn, $_POST['closed_date']);
    $reason      = mysqli_real_escape_string($conn, trim($_POST['reason']));

    // Reject past dates — no point marking a day that has already passed.
    if (!empty($closed_date) && $closed_date < date('Y-m-d')) {
        $toasts[] = ['text' => 'Closed date cannot be in the past.', 'type' => 'error'];
        goto after_closed_day;
    }

    // Check if this date already exists to prevent duplicates.
    $check = mysqli_query($conn, "SELECT id FROM closed_days WHERE closed_date = '$closed_date'");

    if (mysqli_num_rows($check) > 0) {
        // Date already exists — show a warning.
        $toasts[] = ['text' => 'This date is already marked as closed.', 'type' => 'pending'];
    } else {
        // Date does not exist — insert a new record.
        mysqli_query($conn, "INSERT INTO closed_days (closed_date, reason) VALUES ('$closed_date', '$reason')");
        logActivity($conn, 'Settings', 'System Settings', "Added closed day: $closed_date" . ($reason ? " ($reason)" : ''));
        $toasts[] = ['text' => 'Closed day added!', 'type' => 'success'];
    }
    after_closed_day:
}


/* Action C: Delete a Closed Day (trash icon, URL has ?delete_closed=ID) */
if (isset($_GET['delete_closed'])) {

    $did    = intval($_GET['delete_closed']);
    $cd_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT closed_date FROM closed_days WHERE id = $did"));
    mysqli_query($conn, "DELETE FROM closed_days WHERE id = $did");
    logActivity($conn, 'Delete', 'System Settings', "Removed closed day: " . ($cd_row['closed_date'] ?? "ID $did"));

    $_SESSION['toasts'][] = ['text' => 'Closed day removed.', 'type' => 'pending'];
    header("Location: SystemSettings.php");
    exit();
}

/* Action D: Add a Promo Code (triggered by "Create Promo Code" button) */
if (isset($_POST['add_promo'])) {

    // strtoupper() converts all letters to uppercase, e.g. save20 becomes SAVE20
    $code           = strtoupper(trim($_POST['promo_code']));
    $discount_type  = mysqli_real_escape_string($conn, $_POST['discount_type']);
    $discount_value = floatval($_POST['discount_value']); // floatval() ensures it is a decimal number
    $valid_from_raw  = $_POST['valid_from']  ?? '';
    $valid_until_raw = $_POST['valid_until'] ?? '';
    $valid_from  = str_replace('T', ' ', $valid_from_raw);
    $valid_until = str_replace('T', ' ', $valid_until_raw);

    // Reject negative or zero discount values.
    if ($discount_value <= 0) {
        $_SESSION['toasts'][] = ['text' => 'Discount value must be greater than 0.', 'type' => 'error'];
        header("Location: SystemSettings.php#sec-promo");
        exit();
    }

    // Reject percentage discounts above 100%.
    if ($discount_type === 'percentage' && $discount_value > 100) {
        $_SESSION['toasts'][] = ['text' => 'Percentage discount cannot exceed 100%.', 'type' => 'error'];
        header("Location: SystemSettings.php#sec-promo");
        exit();
    }

    // Valid From cannot be in the past (compare datetime).
    if (!empty($valid_from) && $valid_from < date('Y-m-d H:i') ) {
        $_SESSION['toasts'][] = ['text' => 'Valid From cannot be in the past.', 'type' => 'error'];
        header("Location: SystemSettings.php#sec-promo");
        exit();
    }

    // Valid Until must be at least 10 minutes after Valid From.
    if (!empty($valid_from) && !empty($valid_until)) {
        $gap_minutes = (strtotime($valid_until) - strtotime($valid_from)) / 60;
        if ($gap_minutes < 10) {
            $_SESSION['toasts'][] = ['text' => 'Valid Until must be at least 10 minutes after Valid From.', 'type' => 'error'];
            header("Location: SystemSettings.php#sec-promo");
            exit();
        }
    }

    // Check if this promo code already exists.
    $check = mysqli_query($conn, "SELECT id FROM promo_codes WHERE code = '" . mysqli_real_escape_string($conn, $code) . "'");

    if (mysqli_num_rows($check) > 0) {
        // Already exists — show a warning.
        $_SESSION['toasts'][] = ['text' => 'Promo code already exists!', 'type' => 'pending'];
    } else {
        // Does not exist — insert a new record.
        $vf_sql = mysqli_real_escape_string($conn, $valid_from);
        $vu_sql = mysqli_real_escape_string($conn, $valid_until);
        mysqli_query($conn, "INSERT INTO promo_codes (code, discount_type, discount_value, valid_from, valid_until)
            VALUES ('" . mysqli_real_escape_string($conn, $code) . "', '$discount_type', $discount_value, '$vf_sql', '$vu_sql')");
        logActivity($conn, 'Create', 'System Settings', "Created promo code: $code ($discount_type $discount_value)");
        $_SESSION['toasts'][] = ['text' => 'Promo code created!', 'type' => 'success'];
    }

    // Redirect after POST so refreshing the page doesn't resubmit the form
    // (this was the cause of the repeated "Promo code already exists!" toast).
    header("Location: SystemSettings.php#sec-promo");
    exit();
}


/* Action E: Toggle Promo Code Active/Inactive (URL has ?toggle_promo=ID&active=0or1) */
if (isset($_GET['toggle_promo'])) {

    $pid        = intval($_GET['toggle_promo']);
    $new_status = intval($_GET['active']);
    $promo_row  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT code FROM promo_codes WHERE id = $pid"));

    mysqli_query($conn, "UPDATE promo_codes SET is_active = $new_status WHERE id = $pid");
    logActivity($conn, 'Status Change', 'System Settings',
                "Set promo code '" . ($promo_row['code'] ?? "ID $pid") . "' to " . ($new_status ? 'Active' : 'Inactive'));

    $_SESSION['toasts'][] = ['text' => 'Status updated to ' . ($new_status ? 'Active' : 'Inactive') . '.', 'type' => 'success'];
    header("Location: SystemSettings.php#sec-promo");
    exit();
}


/* Action F: Delete a Promo Code (trash icon, URL has ?delete_promo=ID) */
if (isset($_GET['delete_promo'])) {

    $pid       = intval($_GET['delete_promo']);
    $promo_del = mysqli_fetch_assoc(mysqli_query($conn, "SELECT code FROM promo_codes WHERE id = $pid"));
    mysqli_query($conn, "DELETE FROM promo_codes WHERE id = $pid");
    logActivity($conn, 'Delete', 'System Settings', "Deleted promo code: " . ($promo_del['code'] ?? "ID $pid"));

    $_SESSION['toasts'][] = ['text' => 'Promo code deleted.', 'type' => 'pending'];
    header("Location: SystemSettings.php#sec-promo");
    exit();
}

/* Ensure voucher.per_user_limit column exists (how many times one customer can redeem the same voucher) */
$limit_col = mysqli_query($conn, "SHOW COLUMNS FROM voucher LIKE 'per_user_limit'");
if (mysqli_num_rows($limit_col) == 0) {
    mysqli_query($conn, "ALTER TABLE voucher ADD per_user_limit INT NOT NULL DEFAULT 1");
}

/* Action G: Add Voucher */
if (isset($_POST['add_voucher'])) {
    $title           = mysqli_real_escape_string($conn, trim($_POST['voucher_title']));
    $discount_amount = floatval($_POST['discount_amount']);
    $points_required = intval($_POST['points_required']);
    $description     = mysqli_real_escape_string($conn, trim($_POST['description']));

    // Reject non-positive discount amounts.
    if ($discount_amount <= 0) {
        $toasts[] = ['text' => 'Discount amount must be greater than 0.', 'type' => 'error'];
        goto after_voucher;
    }

    // Reject non-positive points.
    if ($points_required < 1) {
        $toasts[] = ['text' => 'Points required must be at least 1.', 'type' => 'error'];
        goto after_voucher;
    }

    /* Optional availability window (datetime-local sends "Y-m-dTH:i", swap T for a space) */
    $valid_from_raw  = $_POST['valid_from']  ?? '';
    $valid_until_raw = $_POST['valid_until'] ?? '';

    // Available From cannot be in the past (compare date portion only).
    if (!empty($valid_from_raw) && substr($valid_from_raw, 0, 10) < date('Y-m-d')) {
        $toasts[] = ['text' => 'Available From cannot be in the past.', 'type' => 'error'];
        goto after_voucher;
    }

    // Available Until must be at least 10 minutes after Available From.
    if (!empty($valid_from_raw) && !empty($valid_until_raw)) {
        $gap_minutes = (strtotime($valid_until_raw) - strtotime($valid_from_raw)) / 60;
        if ($gap_minutes < 10) {
            $toasts[] = ['text' => 'Available Until must be at least 10 minutes after Available From.', 'type' => 'error'];
            goto after_voucher;
        }
    }

    $valid_from  = !empty($valid_from_raw)  ? "'" . mysqli_real_escape_string($conn, str_replace('T', ' ', $valid_from_raw))  . "'" : "NULL";
    $valid_until = !empty($valid_until_raw) ? "'" . mysqli_real_escape_string($conn, str_replace('T', ' ', $valid_until_raw)) . "'" : "NULL";

    /* Optional stock — empty means unlimited */
    $quantity = ($_POST['quantity'] !== '' && (int)$_POST['quantity'] >= 0) ? (int)$_POST['quantity'] : "NULL";

    /* How many times one customer can redeem this voucher (minimum 1) */
    $per_user_limit = max(1, intval($_POST['per_user_limit'] ?? 1));

    // Check if a voucher with this title already exists (case-insensitive).
    $title_check = mysqli_query($conn, "SELECT id FROM voucher WHERE LOWER(title) = LOWER('$title')");

    if (mysqli_num_rows($title_check) > 0) {
        // Already exists — show a warning, don't insert.
        $toasts[] = ['text' => 'A voucher with this name already exists!', 'type' => 'pending'];
        goto after_voucher;
    }

    mysqli_query($conn, "INSERT INTO voucher (title, discount_amount, points_required, description, valid_from, valid_until, quantity, per_user_limit)
        VALUES ('$title', $discount_amount, $points_required, '$description', $valid_from, $valid_until, $quantity, $per_user_limit)");
    logActivity($conn, 'Create', 'System Settings', "Created voucher: $title (RM$discount_amount, $points_required pts)");
    $toasts[] = ['text' => 'Voucher created!', 'type' => 'success'];
    after_voucher:
}

/* Action G2: Edit Voucher */
if (isset($_POST['edit_voucher'])) {
    $vid             = intval($_POST['voucher_id']);
    $title           = mysqli_real_escape_string($conn, trim($_POST['voucher_title']));
    $discount_amount = floatval($_POST['discount_amount']);
    $points_required = intval($_POST['points_required']);
    $description     = mysqli_real_escape_string($conn, trim($_POST['description']));

    // Reject non-positive discount amounts.
    if ($discount_amount <= 0) {
        $toasts[] = ['text' => 'Discount amount must be greater than 0.', 'type' => 'error'];
        goto after_edit_voucher;
    }

    // Reject non-positive points.
    if ($points_required < 1) {
        $toasts[] = ['text' => 'Points required must be at least 1.', 'type' => 'error'];
        goto after_edit_voucher;
    }

    $valid_from_raw  = $_POST['valid_from']  ?? '';
    $valid_until_raw = $_POST['valid_until'] ?? '';

    // Available Until must be at least 10 minutes after Available From.
    if (!empty($valid_from_raw) && !empty($valid_until_raw)) {
        $gap_minutes = (strtotime($valid_until_raw) - strtotime($valid_from_raw)) / 60;
        if ($gap_minutes < 10) {
            $toasts[] = ['text' => 'Available Until must be at least 10 minutes after Available From.', 'type' => 'error'];
            goto after_edit_voucher;
        }
    }

    $valid_from  = !empty($valid_from_raw)  ? "'" . mysqli_real_escape_string($conn, str_replace('T', ' ', $valid_from_raw))  . "'" : "NULL";
    $valid_until = !empty($valid_until_raw) ? "'" . mysqli_real_escape_string($conn, str_replace('T', ' ', $valid_until_raw)) . "'" : "NULL";
    $quantity    = ($_POST['quantity'] !== '' && (int)$_POST['quantity'] >= 0) ? (int)$_POST['quantity'] : "NULL";
    $per_user_limit = max(1, intval($_POST['per_user_limit'] ?? 1));

    // Check if another voucher already uses this title (case-insensitive, excluding itself).
    $title_check = mysqli_query($conn, "SELECT id FROM voucher WHERE LOWER(title) = LOWER('$title') AND id != $vid");

    if (mysqli_num_rows($title_check) > 0) {
        // Already exists — show a warning, don't update.
        $toasts[] = ['text' => 'A voucher with this name already exists!', 'type' => 'pending'];
        goto after_edit_voucher;
    }

    mysqli_query($conn, "UPDATE voucher SET
        title           = '$title',
        discount_amount = $discount_amount,
        points_required = $points_required,
        description     = '$description',
        valid_from      = $valid_from,
        valid_until     = $valid_until,
        quantity        = $quantity,
        per_user_limit  = $per_user_limit
        WHERE id = $vid");

    logActivity($conn, 'Update', 'System Settings', "Updated voucher: $title (ID $vid)");
    $toasts[] = ['text' => 'Voucher updated!', 'type' => 'success'];
    after_edit_voucher:
}

/* Action H: Delete Voucher (blocks delete if any customer has already claimed it) */
if (isset($_GET['delete_voucher'])) {
    $vid = intval($_GET['delete_voucher']);

    // Check if anyone has already claimed/used this voucher before allowing the delete.
    $check = mysqli_query($conn, "SELECT COUNT(*) AS c FROM user_vouchers WHERE voucher_id = $vid");
    $used_count = (int) mysqli_fetch_assoc($check)['c'];

    if ($used_count > 0) {
        // Block hard delete — it would orphan customer voucher records.
        $_SESSION['toasts'][] = ['text' => 'Cannot delete: this voucher has already been claimed by customers.', 'type' => 'error'];
        header("Location: SystemSettings.php#sec-voucher");
        exit();
    }

    $vdel = mysqli_fetch_assoc(mysqli_query($conn, "SELECT title FROM voucher WHERE id = $vid"));
    mysqli_query($conn, "DELETE FROM voucher WHERE id = $vid");
    logActivity($conn, 'Delete', 'System Settings', "Deleted voucher: " . ($vdel['title'] ?? "ID $vid"));
    $_SESSION['toasts'][] = ['text' => 'Voucher deleted.', 'type' => 'pending'];
    header("Location: SystemSettings.php#sec-voucher");
    exit();
}


/* Action I: Save Court Pricing (off_peak_price, peak_price) */
if (isset($_POST['save_pricing'])) {

    $off_peak_price = floatval($_POST['off_peak_price']);
    $peak_price     = floatval($_POST['peak_price']);

    mysqli_query($conn, "UPDATE settings SET setting_value = '$off_peak_price' WHERE setting_key = 'off_peak_price'");
    mysqli_query($conn, "UPDATE settings SET setting_value = '$peak_price'     WHERE setting_key = 'peak_price'");

    logActivity($conn, 'Settings', 'System Settings', "Updated court pricing: off-peak RM$off_peak_price, peak RM$peak_price");
    $toasts[] = ['text' => 'Pricing updated!', 'type' => 'success'];
}


/* Action J: Save Contact Information (Superadmin only — Admin must not be able to change global contact details) */
if (isset($_POST['save_contact']) && $role === 'Superadmin') {

    /* mysqli_real_escape_string protects against SQL injection on text fields */
    $contact_phone    = mysqli_real_escape_string($conn, trim($_POST['contact_phone']));
    $contact_email    = mysqli_real_escape_string($conn, trim($_POST['contact_email']));
    $contact_whatsapp = mysqli_real_escape_string($conn, trim($_POST['contact_whatsapp']));
    $address          = mysqli_real_escape_string($conn, trim($_POST['address']));

    mysqli_query($conn, "UPDATE settings SET setting_value = '$contact_phone'    WHERE setting_key = 'contact_phone'");
    mysqli_query($conn, "UPDATE settings SET setting_value = '$contact_email'    WHERE setting_key = 'contact_email'");
    mysqli_query($conn, "UPDATE settings SET setting_value = '$contact_whatsapp' WHERE setting_key = 'contact_whatsapp'");
    mysqli_query($conn, "UPDATE settings SET setting_value = '$address'          WHERE setting_key = 'address'");

    logActivity($conn, 'Settings', 'System Settings', "Updated contact information");
    $toasts[] = ['text' => 'Contact information updated!', 'type' => 'success'];
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

// Load all vouchers with how many have been claimed (used count comes from user_vouchers)
$vouchers = mysqli_query($conn, "
    SELECT v.*,
           (SELECT COUNT(*) FROM user_vouchers uv WHERE uv.voucher_id = v.id) AS claimed_count
    FROM voucher v
    ORDER BY v.points_required ASC
");
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
                <a href="#sec-pricing" class="settings-tab"><i class="fas fa-tag"></i> Court Pricing</a>
                <a href="#sec-closed"  class="settings-tab"><i class="fas fa-calendar-times"></i> Closed Days</a>
                <a href="#sec-promo"   class="settings-tab"><i class="fas fa-percent"></i> Promo Codes</a>
                <a href="#sec-voucher" class="settings-tab"><i class="fas fa-ticket-alt"></i> Vouchers</a>
                <?php if ($role === 'Superadmin'): ?>
                <a href="#sec-contact" class="settings-tab"><i class="fas fa-address-card"></i> Contact Info</a>
                <?php endif; ?>
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


            <!-- Section 1.5: Court Pricing -->
            <div class="settings-card" id="sec-pricing">
                <h3><i class="fas fa-tag"></i> Court Pricing</h3>
                <p class="settings-desc">Court hourly rates for off-peak and peak hours.</p>

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

                    </div>

                    <button type="submit" name="save_pricing" class="btn-add-account">
                        <i class="fas fa-save"></i> Save Pricing
                    </button>
                </form>
            </div>


            <!-- Section 1.6: Contact Information (Superadmin only) -->
            <?php if ($role === 'Superadmin'): ?>
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
            <?php endif; ?>


            <!-- Section 2: Closed Days -->
            <div class="settings-card" id="sec-closed">
                <h3><i class="fas fa-calendar-times"></i> Closed Days</h3>
                <p class="settings-desc">Mark dates when the arena is closed. Players cannot book on these days.</p>

                <!-- Form to add a new closed day -->
                <form method="POST" action="" style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px;">

                    <div class="settings-field">
                        <label>Date</label>
                        <input type="date" name="closed_date" class="form-control" required
                               min="<?php echo date('Y-m-d'); ?>">
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
                            <input type="datetime-local" name="valid_from" id="promo-valid-from" class="form-control" required
                                   min="<?php echo date('Y-m-d\TH:i'); ?>">
                        </div>

                        <div class="settings-field">
                            <label>Valid Until</label>
                            <input type="datetime-local" name="valid_until" id="promo-valid-until" class="form-control" required
                                   min="<?php echo date('Y-m-d\TH:i'); ?>">
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

                            <!-- Show the validity datetime range -->
                            <td>
                                <?php echo date("d M Y g:i A", strtotime($promo['valid_from'])); ?>
                                –
                                <?php echo date("d M Y g:i A", strtotime($promo['valid_until'])); ?>
                            </td>

                            <!-- Inline status dropdown — clicking opens a 2-option menu; selecting redirects via toggle_promo -->
                            <td>
                                <div class="promo-status-wrap" data-id="<?php echo $promo['id']; ?>"
                                     data-active="<?php echo $promo['is_active']; ?>">
                                    <!-- Badge button — no chevron, just the status label -->
                                    <button type="button" class="promo-status-btn <?php echo $promo['is_active'] == 1 ? 'is-active' : 'is-inactive'; ?>"
                                            onclick="togglePromoDropdown(this)">
                                        <?php echo $promo['is_active'] == 1 ? 'Active' : 'Inactive'; ?>
                                    </button>
                                    <!-- Floating option list — positioned by JS to escape table clipping -->
                                    <div class="promo-status-dropdown">
                                        <?php if ($promo['is_active'] == 1): ?>
                                            <span class="promo-status-dropdown-header" style="color:#16a34a;">Active</span>
                                        <?php else: ?>
                                            <span class="promo-status-dropdown-header" style="color:#a16207;">Inactive</span>
                                        <?php endif; ?>
                                        <a class="promo-status-option <?php echo $promo['is_active'] == 1 ? 'is-selected' : ''; ?>"
                                           href="SystemSettings.php?toggle_promo=<?php echo $promo['id']; ?>&active=1">Active</a>
                                        <a class="promo-status-option <?php echo $promo['is_active'] == 0 ? 'is-selected' : ''; ?>"
                                           href="SystemSettings.php?toggle_promo=<?php echo $promo['id']; ?>&active=0">Inactive</a>
                                    </div>
                                </div>
                            </td>

                            <!-- Action: delete only (toggle moved to Status column) -->
                            <td>
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
                                placeholder="e.g. 5.00" step="0.01" min="0.01" required>
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

                        <div class="settings-field">
                            <label>Available From <span class="ss-optional">Optional</span></label>
                            <input type="datetime-local" name="valid_from" id="voucher-valid-from" class="form-control"
                                   min="<?php echo date('Y-m-d\T00:00'); ?>">
                        </div>

                        <div class="settings-field">
                            <label>Available Until <span class="ss-optional">Optional</span></label>
                            <input type="datetime-local" name="valid_until" id="voucher-valid-until" class="form-control"
                                   min="<?php echo date('Y-m-d\T00:00'); ?>">
                        </div>

                        <div class="settings-field">
                            <label>Quantity <span class="ss-optional">Empty = unlimited</span></label>
                            <input type="number" name="quantity" class="form-control"
                                placeholder="e.g. 100" min="0">
                        </div>

                        <div class="settings-field">
                            <label>Redeem Limit Per Customer</label>
                            <input type="number" name="per_user_limit" class="form-control"
                                placeholder="e.g. 1" min="1" value="1">
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
                            <th>Valid Period</th>
                            <th>Stock</th>
                            <th>Limit / Person</th>
                            <th>Description</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($v = mysqli_fetch_assoc($vouchers)):
                            $claimed   = (int)($v['claimed_count'] ?? 0);
                            $has_qty   = $v['quantity'] !== null && $v['quantity'] !== '';
                            $remaining = $has_qty ? max(0, (int)$v['quantity'] - $claimed) : null;
                        ?>
                        <tr class="voucher-row"
                            data-id="<?php echo $v['id']; ?>"
                            data-title="<?php echo htmlspecialchars($v['title'], ENT_QUOTES); ?>"
                            data-discount="<?php echo $v['discount_amount']; ?>"
                            data-points="<?php echo $v['points_required']; ?>"
                            data-description="<?php echo htmlspecialchars($v['description'] ?? '', ENT_QUOTES); ?>"
                            data-from="<?php echo $v['valid_from']  ? date('Y-m-d\TH:i', strtotime($v['valid_from']))  : ''; ?>"
                            data-until="<?php echo $v['valid_until'] ? date('Y-m-d\TH:i', strtotime($v['valid_until'])) : ''; ?>"
                            data-quantity="<?php echo $has_qty ? (int)$v['quantity'] : ''; ?>"
                            data-limit="<?php echo max(1, (int)($v['per_user_limit'] ?? 1)); ?>"
                            onclick="openEditVoucher(this)" style="cursor:pointer;">
                            <td><strong><?php echo htmlspecialchars($v['title']); ?></strong></td>
                            <td>RM <?php echo number_format($v['discount_amount'], 2); ?></td>
                            <td><?php echo $v['points_required']; ?> pts</td>

                            <!-- Valid period: dash means always available -->
                            <td>
                                <?php if ($v['valid_from'] || $v['valid_until']): ?>
                                    <?php echo $v['valid_from']  ? date('d M Y g:i A', strtotime($v['valid_from']))  : 'Any'; ?>
                                    –
                                    <?php echo $v['valid_until'] ? date('d M Y g:i A', strtotime($v['valid_until'])) : 'Any'; ?>
                                <?php else: ?>
                                    <span style="color:#94a3b8;">Always</span>
                                <?php endif; ?>
                            </td>

                            <!-- Stock: remaining left of total, or unlimited -->
                            <td>
                                <?php if ($has_qty): ?>
                                    <span class="badge <?php echo $remaining > 0 ? 'success' : 'pending'; ?>">
                                        <?php echo $remaining; ?> left
                                    </span>
                                    <div style="font-size:11px; color:#94a3b8; margin-top:2px;"><?php echo $claimed; ?> / <?php echo (int)$v['quantity']; ?> used</div>
                                <?php else: ?>
                                    <span style="color:#94a3b8;">Unlimited</span>
                                <?php endif; ?>
                            </td>

                            <td><?php echo max(1, (int)($v['per_user_limit'] ?? 1)); ?> time(s)</td>

                            <td><?php echo htmlspecialchars($v['description']); ?></td>
                            <td onclick="event.stopPropagation()">
                                <a href="SystemSettings.php?delete_voucher=<?php echo $v['id']; ?>"
                                onclick="return confirm('Delete this voucher?');" title="Delete">
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

    <!-- Edit Voucher Modal -->
    <div class="modal-overlay" id="editVoucherModal">
        <div class="modal-card" style="max-width:560px;">
            <div class="modal-header">
                <h2><i class="fas fa-pen"></i> Edit Voucher</h2>
                <button class="modal-close" type="button" onclick="closeEditVoucher()">&times;</button>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="voucher_id" id="ev-id">

                <div class="modal-grid">

                    <div class="modal-field full-width">
                        <label>Voucher Title</label>
                        <input type="text" name="voucher_title" id="ev-title" required>
                    </div>

                    <div class="modal-field">
                        <label>Discount Amount (RM)</label>
                        <input type="number" name="discount_amount" id="ev-discount" step="0.01" min="0.01" required>
                    </div>

                    <div class="modal-field">
                        <label>Points Required</label>
                        <input type="number" name="points_required" id="ev-points" min="1" required>
                    </div>

                    <div class="modal-field">
                        <label>Available From</label>
                        <input type="datetime-local" name="valid_from" id="ev-from">
                    </div>

                    <div class="modal-field">
                        <label>Available Until</label>
                        <input type="datetime-local" name="valid_until" id="ev-until">
                    </div>

                    <div class="modal-field">
                        <label>Quantity <span class="ss-optional">Empty = unlimited</span></label>
                        <input type="number" name="quantity" id="ev-quantity" min="0">
                    </div>

                    <div class="modal-field">
                        <label>Redeem Limit Per Customer</label>
                        <input type="number" name="per_user_limit" id="ev-limit" min="1" required>
                    </div>

                    <div class="modal-field full-width">
                        <label>Description</label>
                        <input type="text" name="description" id="ev-description">
                    </div>

                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-modal-cancel" onclick="closeEditVoucher()">Cancel</button>
                    <button type="submit" name="edit_voucher" class="btn-modal-save">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal styling -->
    <?php include __DIR__ . '/../modal.php'; ?>

    <!-- Scroll-to-top -->
    <?php include __DIR__ . '/../scroll_top.php'; ?>

    <!-- Toast notifications -->
    <?php include __DIR__ . '/../toast/toast.php'; ?>

    <!-- JavaScript file -->
    <script src="../Dashboard/Dashboard.js"></script>

    <script>
    /* Fill the edit modal from the clicked row's data attributes, then open it */
    function openEditVoucher(el) {
        document.getElementById('ev-id').value          = el.dataset.id;
        document.getElementById('ev-title').value       = el.dataset.title;
        document.getElementById('ev-discount').value    = el.dataset.discount;
        document.getElementById('ev-points').value      = el.dataset.points;
        document.getElementById('ev-description').value = el.dataset.description;
        document.getElementById('ev-from').value        = el.dataset.from;
        document.getElementById('ev-until').value        = el.dataset.until;
        document.getElementById('ev-quantity').value    = el.dataset.quantity;
        document.getElementById('ev-limit').value       = el.dataset.limit;
        document.getElementById('editVoucherModal').classList.add('active');
    }

    function closeEditVoucher() {
        document.getElementById('editVoucherModal').classList.remove('active');
    }
    </script>

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

    </script>
    <script>
    /* Promo status inline dropdown
       The dropdown is moved to <body> on first open to escape all overflow clipping.
       Visibility is controlled directly via style.display (not CSS parent selectors,
       which break once the element is reparented). */

    // Track the currently open dropdown element (now lives in <body>)
    var _openPromoDropdown = null;
    var _openPromoWrap     = null;

    function togglePromoDropdown(btn) {
        var wrap     = btn.closest('.promo-status-wrap');
        var dropdown = wrap.querySelector('.promo-status-dropdown');

        // If this wrap is already open, close it
        if (_openPromoWrap === wrap) {
            _closePromoDropdown();
            return;
        }

        // Close any previously open dropdown
        _closePromoDropdown();

        // Move to <body> once to escape table/card overflow
        if (dropdown.parentElement !== document.body) {
            document.body.appendChild(dropdown);
        }

        // Show and position
        dropdown.style.display = 'block';
        var rect = btn.getBoundingClientRect();
        dropdown.style.top  = (rect.bottom + 4) + 'px';
        dropdown.style.left = rect.left + 'px';

        _openPromoDropdown = dropdown;
        _openPromoWrap     = wrap;
    }

    function _closePromoDropdown() {
        if (_openPromoDropdown) {
            _openPromoDropdown.style.display = 'none';
            _openPromoDropdown = null;
            _openPromoWrap     = null;
        }
    }

    // Close on outside click
    document.addEventListener('click', function (e) {
        if (_openPromoDropdown &&
            !e.target.closest('.promo-status-wrap') &&
            !e.target.closest('.promo-status-dropdown')) {
            _closePromoDropdown();
        }
    });

    // Reposition on scroll / resize
    function _repositionPromoDropdown() {
        if (!_openPromoWrap || !_openPromoDropdown) return;
        var btn  = _openPromoWrap.querySelector('.promo-status-btn');
        var rect = btn.getBoundingClientRect();
        _openPromoDropdown.style.top  = (rect.bottom + 4) + 'px';
        _openPromoDropdown.style.left = rect.left + 'px';
    }
    window.addEventListener('scroll', _repositionPromoDropdown, true);
    window.addEventListener('resize', _repositionPromoDropdown);
    </script>

    <script>
    /* ===================================================================
       Client-side date / value validation
       Runs on form submit so users get instant feedback without a round-trip.
       PHP also validates server-side for safety.
       =================================================================== */
    (function () {
        const TODAY_DATE     = '<?php echo date('Y-m-d\TH:i'); ?>';
        const TODAY_DATETIME = '<?php echo date('Y-m-d\T00:00'); ?>';
        const MIN_GAP_MIN    = 10; // promo / voucher "until" must be at least this many minutes after "from"

        /* Returns a datetime-local string (YYYY-MM-DDTHH:mm) `minutes` later than dtLocal */
        function addMinutes(dtLocal, minutes) {
            const d = new Date(dtLocal);
            d.setMinutes(d.getMinutes() + minutes);
            const pad = n => String(n).padStart(2, '0');
            return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate())
                 + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
        }

        /* True if (until - from) is less than MIN_GAP_MIN minutes */
        function gapTooSmall(fromVal, untilVal) {
            return (new Date(untilVal) - new Date(fromVal)) < MIN_GAP_MIN * 60000;
        }

        /* ── Promo Code form ── */
        const promoFrom  = document.getElementById('promo-valid-from');
        const promoUntil = document.getElementById('promo-valid-until');

        /* When "from" changes, push the "until" min forward so it can't be less than 10 minutes later */
        if (promoFrom && promoUntil) {
            promoFrom.addEventListener('change', function () {
                const minUntil = this.value ? addMinutes(this.value, MIN_GAP_MIN) : TODAY_DATE;
                promoUntil.min = minUntil;
                if (promoUntil.value && promoUntil.value < minUntil) {
                    promoUntil.value = minUntil;
                }
            });
        }

        /* Promo form submit guard */
        const promoForm = promoFrom && promoFrom.closest('form');
        if (promoForm) {
            promoForm.addEventListener('submit', function (e) {
                const discountInput = promoForm.querySelector('[name="discount_value"]');
                if (discountInput && parseFloat(discountInput.value) <= 0) {
                    e.preventDefault();
                    alert('Discount value must be greater than 0.');
                    discountInput.focus();
                    return;
                }
                const discountType = promoForm.querySelector('[name="discount_type"]');
                if (discountType && discountType.value === 'percentage' && parseFloat(discountInput.value) > 100) {
                    e.preventDefault();
                    alert('Percentage discount cannot exceed 100%.');
                    discountInput.focus();
                    return;
                }
                if (promoFrom.value && promoUntil.value && gapTooSmall(promoFrom.value, promoUntil.value)) {
                    e.preventDefault();
                    alert('Valid Until must be at least ' + MIN_GAP_MIN + ' minutes after Valid From.');
                    promoUntil.focus();
                }
            });
        }

        /* ── Voucher Create form ── */
        const vFrom  = document.getElementById('voucher-valid-from');
        const vUntil = document.getElementById('voucher-valid-until');

        if (vFrom && vUntil) {
            vFrom.addEventListener('change', function () {
                const minUntil = this.value ? addMinutes(this.value, MIN_GAP_MIN) : TODAY_DATETIME;
                vUntil.min = minUntil;
                if (vUntil.value && vUntil.value < minUntil) {
                    vUntil.value = minUntil;
                }
            });
        }

        const voucherForm = vFrom && vFrom.closest('form');
        if (voucherForm) {
            voucherForm.addEventListener('submit', function (e) {
                const discountInput = voucherForm.querySelector('[name="discount_amount"]');
                if (discountInput && parseFloat(discountInput.value) <= 0) {
                    e.preventDefault();
                    alert('Discount amount must be greater than 0.');
                    discountInput.focus();
                    return;
                }
                if (vFrom.value && vUntil.value && gapTooSmall(vFrom.value, vUntil.value)) {
                    e.preventDefault();
                    alert('Available Until must be at least ' + MIN_GAP_MIN + ' minutes after Available From.');
                    vUntil.focus();
                }
            });
        }

        /* ── Edit Voucher Modal form ── */
        const evFrom  = document.getElementById('ev-from');
        const evUntil = document.getElementById('ev-until');

        if (evFrom && evUntil) {
            evFrom.addEventListener('change', function () {
                const minUntil = this.value ? addMinutes(this.value, MIN_GAP_MIN) : TODAY_DATETIME;
                evUntil.min = minUntil;
                if (evUntil.value && evUntil.value < minUntil) {
                    evUntil.value = minUntil;
                }
            });
        }

        const evForm = evFrom && evFrom.closest('form');
        if (evForm) {
            evForm.addEventListener('submit', function (e) {
                const discountInput = evForm.querySelector('[name="discount_amount"]');
                if (discountInput && parseFloat(discountInput.value) <= 0) {
                    e.preventDefault();
                    alert('Discount amount must be greater than 0.');
                    discountInput.focus();
                    return;
                }
                if (evFrom.value && evUntil.value && gapTooSmall(evFrom.value, evUntil.value)) {
                    e.preventDefault();
                    alert('Available Until must be at least ' + MIN_GAP_MIN + ' minutes after Available From.');
                    evUntil.focus();
                }
            });
        }
    })();
    </script>

</body>
</html>