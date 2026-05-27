<?php
// Coach Profile page — ONLY accessible to a logged-in Coach.
// Lives at Admin_Module/Coach/CoachProfile.php
// The coach can update their own name, specialty, phone, photo,
// availability status, gender and age. Price-per-hour and email
// are read-only here (only admins can change those).

session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../LoginPage.php");
    exit();
}
// Anyone who isn't a Coach gets bounced back to the dashboard.
if ($_SESSION['role'] !== 'Coach') {
    header("Location: ../Dashboard/Dashboard.php");
    exit();
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$conn         = mysqli_connect("localhost", "root", "", "badminton_hub");
$admin_id     = (int)$_SESSION['id'];
$username     = $_SESSION['username'];
$role         = $_SESSION['role'];
$display_name = $username;

// This page lives inside Admin_Module/Coach/, so navbar links need to step up one level.
$base_path = '../';

// Fetch coach record
$coach = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT c.*, a.email, a.username
    FROM coaches c
    JOIN admins a ON c.admin_id = a.id
    WHERE c.admin_id = $admin_id
    LIMIT 1
"));

if (!$coach) {
    header("Location: ../Dashboard/Dashboard.php");
    exit();
}

$success = '';
$error   = '';

if (isset($_POST['update_profile'])) {
    $name      = mysqli_real_escape_string($conn, trim($_POST['name']));
    $specialty = mysqli_real_escape_string($conn, $_POST['specialty']);
    $phone     = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $gender    = mysqli_real_escape_string($conn, $_POST['gender']);
    $age       = (isset($_POST['age']) && $_POST['age'] !== '' && (int)$_POST['age'] > 0)
                    ? (int)$_POST['age'] : 'NULL';
    $avail     = mysqli_real_escape_string($conn, $_POST['availability_status']);

    $img_sql = "";
    if (!empty($_POST['cropped_img_data'])) {
        $img_data    = str_replace('data:image/png;base64,', '', $_POST['cropped_img_data']);
        $img_data    = str_replace(' ', '+', $img_data);
        $img_decoded = base64_decode($img_data);
        $img_name    = time() . '_coach.png';
        // Pictures/ is at project root, so step two folders up from Coach/.
        $upload_path = '../../Pictures/Admin_Module/coaches/' . $img_name;

        if (file_put_contents($upload_path, $img_decoded)) {
            $img_sql = ", profile_img = '$img_name'";
        }
    }

    mysqli_query($conn, "
        UPDATE coaches SET
            name                = '$name',
            specialty           = '$specialty',
            phone               = '$phone',
            gender              = '$gender',
            age                 = $age,
            availability_status = '$avail'
            $img_sql
        WHERE admin_id = $admin_id
    ");

    mysqli_query($conn, "
        UPDATE admins SET specialisation = '$specialty'
        WHERE id = $admin_id
    ");

    // Re-fetch updated data
    $coach = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT c.*, a.email, a.username
        FROM coaches c
        JOIN admins a ON c.admin_id = a.id
        WHERE c.admin_id = $admin_id
        LIMIT 1
    "));

    $success = 'Profile updated successfully!';
}

$avail       = $coach['availability_status'] ?? 'Available';
$profile_img = !empty($coach['profile_img'])
                ? '../../Pictures/Admin_Module/coaches/' . htmlspecialchars($coach['profile_img'])
                : '../../Pictures/Admin_Module/coaches/default.png';

$avail_colors = [
    'Available' => ['bg' => '#dcfce7', 'color' => '#16a34a', 'icon' => 'fa-circle-check'],
    'On Leave'  => ['bg' => '#fef3c7', 'color' => '#d97706', 'icon' => 'fa-plane'],
    'Sick'      => ['bg' => '#fee2e2', 'color' => '#dc2626', 'icon' => 'fa-kit-medical'],
    'Off Day'   => ['bg' => '#f1f5f9', 'color' => '#64748b', 'icon' => 'fa-moon'],
];
$ac = $avail_colors[$avail] ?? $avail_colors['Available'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile – Smash Arena</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">

    <link rel="stylesheet" href="../Dashboard/Dashboard.css">
    <link rel="stylesheet" href="../Coaches_Management/ManageCoaches.css">
    <link rel="stylesheet" href="CoachProfile.css">
</head>
<body>

<?php include '../navbar.php'; ?>

<main class="content">

    <?php if ($success): ?>
        <div class="cp-alert success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="cp-alert error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Profile Hero -->
    <div class="cp-hero">
        <div class="cp-hero-avatar-wrap">
            <img id="hero-avatar" src="<?php echo $profile_img; ?>" alt="Profile Photo" class="cp-hero-avatar"
                 onerror="this.onerror=null;this.src='../../Pictures/Admin_Module/coaches/default.png'">
            <label class="cp-avatar-edit-btn" title="Change photo">
                <i class="fas fa-camera"></i>
                <input type="file" id="photo-input" accept="image/*" style="display:none;">
            </label>
        </div>

        <div class="cp-hero-info">
            <div class="cp-hero-name"><?php echo htmlspecialchars($coach['name']); ?></div>
            <div class="cp-hero-role"><i class="fas fa-whistle"></i> Coach</div>
            <div class="cp-hero-meta">
                <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($coach['email']); ?></span>
                <?php if ($coach['phone']): ?>
                    <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($coach['phone']); ?></span>
                <?php endif; ?>
                <span><i class="fas fa-tag"></i> RM <?php echo number_format($coach['price_per_hour'], 2); ?>/hr</span>
            </div>
            <div class="cp-avail-badge" style="background:<?php echo $ac['bg']; ?>;color:<?php echo $ac['color']; ?>">
                <i class="fas <?php echo $ac['icon']; ?>"></i>
                <?php echo $avail; ?>
            </div>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="cp-form-card">
        <div class="cp-form-header">
            <i class="fas fa-pen-to-square"></i>
            <span>Edit My Profile</span>
        </div>

        <form method="POST" action="CoachProfile.php">
            <input type="hidden" name="update_profile" value="1">
            <input type="hidden" name="cropped_img_data" id="cropped-img-data">

            <div class="cp-form-grid">

                <!-- Name -->
                <div class="cp-field full-width">
                    <label><i class="fas fa-user"></i> Display Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($coach['name']); ?>" required>
                </div>

                <!-- Specialty -->
                <div class="cp-field full-width">
                    <label><i class="fas fa-star"></i> Coaching Specialty</label>
                    <select name="specialty">
                        <option value="">— Select —</option>
                        <?php
                        $specs = ['Singles Coaching','Doubles Coaching','Fitness & Conditioning','Junior Development','Tournament Preparation'];
                        foreach ($specs as $s):
                            $sel = ($coach['specialty'] === $s) ? 'selected' : '';
                        ?>
                            <option value="<?php echo $s; ?>" <?php echo $sel; ?>><?php echo $s; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Phone -->
                <div class="cp-field">
                    <label><i class="fas fa-phone"></i> Phone Number</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($coach['phone'] ?? ''); ?>" placeholder="e.g. 012-3456789">
                </div>

                <!-- Availability -->
                <div class="cp-field">
                    <label><i class="fas fa-circle-dot"></i> Availability Status</label>
                    <select name="availability_status">
                        <?php foreach (['Available','On Leave','Sick','Off Day'] as $opt): ?>
                            <option value="<?php echo $opt; ?>" <?php echo ($avail === $opt) ? 'selected' : ''; ?>>
                                <?php echo $opt; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Gender -->
                <div class="cp-field">
                    <label><i class="fas fa-venus-mars"></i> Gender</label>
                    <select name="gender">
                        <option value="">— Select —</option>
                        <option value="Male"   <?php echo ($coach['gender'] === 'Male')   ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo ($coach['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>

                <!-- Age -->
                <div class="cp-field">
                    <label><i class="fas fa-cake-candles"></i> Age</label>
                    <input type="number" name="age" value="<?php echo $coach['age'] ?? ''; ?>" min="18" max="80" placeholder="e.g. 28">
                </div>

                <!-- Price (read-only) -->
                <div class="cp-field">
                    <label><i class="fas fa-tag"></i> Price Per Hour <span class="cp-readonly-tag">Set by Admin</span></label>
                    <input type="text" value="RM <?php echo number_format($coach['price_per_hour'], 2); ?>" readonly class="cp-readonly">
                </div>

                <!-- Email (read-only) -->
                <div class="cp-field">
                    <label><i class="fas fa-envelope"></i> Email Address <span class="cp-readonly-tag">Contact Admin to change</span></label>
                    <input type="text" value="<?php echo htmlspecialchars($coach['email']); ?>" readonly class="cp-readonly">
                </div>

            </div>

            <div class="cp-form-actions">
                <button type="submit" class="cp-btn-save">
                    <i class="fas fa-floppy-disk"></i> Save Changes
                </button>
            </div>
        </form>
    </div>

</main>

<!-- Crop Modal -->
<div class="modal-overlay" id="cropModal">
    <div class="modal-card" id="cropPanel" style="max-width:680px;">
        <div class="modal-header">
            <h2><i class="fas fa-crop-alt"></i> Crop Photo</h2>
        </div>
        <div id="crop-area" style="padding:0 28px 16px;">
            <img id="crop-img" style="display:block;width:100%;">
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-modal-cancel" onclick="cancelCrop()">Cancel</button>
            <button type="button" class="btn-modal-save"   onclick="applyCrop()">Crop & Use</button>
        </div>
    </div>
</div>

<!-- Cropper library (loads first so CoachProfile.js can use the Cropper class) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
<!-- All page-specific JS lives in CoachProfile.js -->
<script src="CoachProfile.js"></script>

</body>
</html>
