<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'Email_System/phpmailer/src/Exception.php';
require 'Email_System/phpmailer/src/PHPMailer.php';
require 'Email_System/phpmailer/src/SMTP.php';

session_start();

// Security check - If no session, redirect immediately
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Superadmin') {
    header("Location: LoginPage.php");
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];
$display_name = $username;

// --- Prevent browser caching (Solve the logout back-button issue) ---
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

// Database connection
$conn = mysqli_connect("localhost", "root", "", "badminton_hub");

// Updated Function using PHPMailer
function sendTemporaryPassword($to_email, $username, $temp_pass) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'smasharenabadminton@gmail.com'; 
        $mail->Password   = 'hgrk ocze fowx rbrd';   
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('smasharenabadminton@gmail.com', 'Badminton Hub');
        $mail->addAddress($to_email);

        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Badminton Hub Credentials';
        $mail->Body    = "
        <div style='font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 30px;'>
            <div style='max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);'>
                <div style='background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding: 32px; text-align: center;'>
                    <p style='margin:0; font-size:32px;'>&#127992;</p>
                    <h1 style='margin: 10px 0 4px; color: #f59e0b; font-size: 22px;'>Badminton Hub</h1>
                    <p style='margin:0; color: rgba(255,255,255,0.6); font-size: 13px;'>Admin Portal</p>
                </div>
                <div style='padding: 36px 32px;'>
                    <h2 style='margin: 0 0 8px; color: #0f172a; font-size: 20px;'>Welcome, $username!</h2>
                    <p style='color: #64748b; font-size: 15px; line-height: 1.6; margin: 0 0 24px;'>Your admin account has been created on Badminton Hub. Use the temporary credentials below to log in for the first time.</p>
                    <div style='background: #fffbeb; border: 1.5px solid #fde68a; border-radius: 12px; padding: 20px 24px; margin-bottom: 24px;'>
                        <p style='margin: 0 0 8px; font-size: 12px; font-weight: 700; color: #92400e; text-transform: uppercase; letter-spacing: 0.5px;'>Your Temporary Password</p>
                        <p style='margin: 0; font-size: 24px; font-weight: 800; color: #0f172a; letter-spacing: 3px; font-family: monospace;'>$temp_pass</p>
                    </div>
                    <div style='background: #fef2f2; border-left: 4px solid #ef4444; border-radius: 8px; padding: 14px 18px; margin-bottom: 28px;'>
                        <p style='margin: 0; font-size: 13px; color: #991b1b;'>&#9888;&#65039; You will be required to change this password on your first login. Please keep these credentials private.</p>
                    </div>
                    <a href='http://localhost/fyp/Admin_Module/LoginPage.php' style='display: block; text-align: center; padding: 14px; background: linear-gradient(135deg, #f59e0b, #d97706); color: #0f172a; text-decoration: none; border-radius: 50px; font-weight: 800; font-size: 15px;'>Login to Admin Portal</a>
                </div>
                <div style='background: #f8fafc; padding: 20px 32px; text-align: center; border-top: 1px solid #e2e8f0;'>
                    <p style='margin: 0; font-size: 12px; color: #94a3b8;'>This email was sent by Badminton Hub Admin System. If you did not expect this, please contact your system administrator.</p>
                </div>
            </div>
        </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false; 
    }
}

$message = "";

// Handle edit coach profile
if (isset($_POST['update_coach'])) {
    $coach_id       = intval($_POST['coach_id']);
    $name           = mysqli_real_escape_string($conn, $_POST['coach_name']);
    $specialty      = mysqli_real_escape_string($conn, $_POST['specialty']);
    $phone          = mysqli_real_escape_string($conn, $_POST['phone']);
    $gender         = mysqli_real_escape_string($conn, $_POST['gender']);
    $age            = intval($_POST['age']);
    $price_per_hour = floatval($_POST['price_per_hour']);

    // Handle profile image upload
    $img_sql = "";
    if (isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] === 0) {
        $img_name    = time() . '_' . basename($_FILES['profile_img']['name']);
        $upload_path = 'Pictures/coaches/' . $img_name;

        // move_uploaded_file = save the uploaded file to the folder
        if (move_uploaded_file($_FILES['profile_img']['tmp_name'], $upload_path)) {
            $img_sql = ", profile_img = '$img_name'";
        }
    }

    mysqli_query($conn, "
        UPDATE coaches SET
            name           = '$name',
            specialty      = '$specialty',
            phone          = '$phone',
            gender         = '$gender',
            age            = $age,
            price_per_hour = $price_per_hour
            $img_sql
        WHERE id = $coach_id
    ");

    $message = "<div class='badge success' style='width:100%; padding:15px; margin-bottom:20px;'>Coach profile updated successfully!</div>";
}

// Handle availability status change
if (isset($_GET['avail_id']) && isset($_GET['avail_status'])) {
    $avail_id     = intval($_GET['avail_id']);
    $avail_status = mysqli_real_escape_string($conn, $_GET['avail_status']);

    mysqli_query($conn, "UPDATE coaches SET availability_status = '$avail_status' WHERE id = $avail_id");
    header("Location: AdminManagement.php?updated=1");
    exit();
}

// Handle account creation
if (isset($_POST['add_account'])) {
    $user        = mysqli_real_escape_string($conn, $_POST['username']);
    $email       = mysqli_real_escape_string($conn, $_POST['email']);
    $role_to_add = mysqli_real_escape_string($conn, $_POST['role']);
    $spec        = isset($_POST['spec']) ? mysqli_real_escape_string($conn, $_POST['spec']) : NULL;

    // Coach accounts log in through admins, while customer pages read public coach details from coaches.
    $is_coach_val = ($role_to_add === 'Coach') ? 1 : 0;

    $temp_pass   = bin2hex(random_bytes(4));
    $hashed_pass = password_hash($temp_pass, PASSWORD_DEFAULT);

    // Fix: HTML input value="0" always sends "0", so check > 0 instead of checking empty string
    $coach_price_raw = isset($_POST['coach_price_per_hour']) ? $_POST['coach_price_per_hour'] : '';
    $coach_price     = ($coach_price_raw !== '' && (float)$coach_price_raw > 0) ? (float)$coach_price_raw : NULL;
    $coach_price_sql = ($coach_price === NULL) ? "NULL" : $coach_price;

    // Check for duplicate username OR email
    $check = mysqli_query($conn, "SELECT username, email FROM admins WHERE username = '$user' OR email = '$email'");
    if (mysqli_num_rows($check) > 0) {
        $found = mysqli_fetch_assoc($check);
        if ($found['username'] === $user) {
            $message = "<div class='badge pending' style='width:100%; padding:15px; margin-bottom:20px;'>Error: Username already exists!</div>";
        } else {
            $message = "<div class='badge pending' style='width:100%; padding:15px; margin-bottom:20px;'>Error: Email address already exists!</div>";
        }
    } else {
        // Note: $coach_price_sql must NOT have quotes around it so NULL inserts correctly
        $sql = "INSERT INTO admins (username, email, password, role, status, specialisation, is_coach, coach_price_per_hour) 
                VALUES ('$user', '$email', '$hashed_pass', '$role_to_add', 'Inactive', '$spec', '$is_coach_val', $coach_price_sql)";
        
        if (mysqli_query($conn, $sql)) {
            $admin_id = mysqli_insert_id($conn);

            if ($role_to_add === 'Coach') {
                $coach_sql = "INSERT INTO coaches (admin_id, name, specialty, price_per_hour, is_active)
                              VALUES ('$admin_id', '$user', '$spec', $coach_price_sql, 0)";
                mysqli_query($conn, $coach_sql);
            }

            // Call PHPMailer function
            $mail_sent = sendTemporaryPassword($email, $user, $temp_pass);
            
            if ($mail_sent) {
                $message = "<div class='badge success' style='width:100%; padding:15px; margin-bottom:20px;'>Success: Account Created & Email Sent.</div>";
            } else {
                // If SMTP fails, it shows "Email skipped"
                $message = "<div class='badge success' style='width:100%; padding:15px; margin-bottom:20px;'>Success: Account Created (Email skipped).</div>";
            }
        } else {
            // Show error if DB structure still blocks insertion
            $message = "<div class='badge pending' style='width:100%; padding:15px; margin-bottom:20px;'>Database Error: " . mysqli_error($conn) . "</div>";
        }
    }
}

// Handle toggle status by fetching from DB 
if (isset($_GET['update_id']) && isset($_GET['new_status'])) {
    $uid    = intval($_GET['update_id']);
    $status = mysqli_real_escape_string($conn, $_GET['new_status']);
    mysqli_query($conn, "UPDATE admins SET status = '$status' WHERE id = $uid");
    $coach_active = ($status === 'Active') ? 1 : 0;
    mysqli_query($conn, "UPDATE coaches SET is_active = '$coach_active' WHERE admin_id = $uid");
    header("Location: AdminManagement.php");
    exit();
}

// Handle delete
if (isset($_GET['delete_id'])) {
    $did = intval($_GET['delete_id']);
    // Prevent deleting superadmin for safety
    mysqli_query($conn, "DELETE FROM coaches WHERE admin_id = $did");
    mysqli_query($conn, "DELETE FROM admins WHERE id = $did AND role != 'Superadmin'");
    header("Location: AdminManagement.php");
    exit();
}

// Filter Logic
$current_filter = isset($_GET['filter']) ? $_GET['filter'] : 'All';
$query = "SELECT * FROM admins WHERE 1=1"; 
if ($current_filter !== 'All') {
    $query .= " AND role = '$current_filter'";
}
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Badminton Hub - Admin Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap">
    <link rel="stylesheet" href="SuperAdminDashboard.css">
    <link rel="stylesheet" href="AdminManagement.css">
    <link rel="stylesheet" href="ManageCourts.css">
</head>
<body>

    <!-- Nav Bar -->
    <?php include 'navbar.php'; ?>
    
    <main class="content">
        <div class="manage-container">
            
            <header class="management-header">
                <div>
                    <h1>Staff Management</h1>
                    <p>Manage badminton hub staff accounts and permissions.</p>
                </div>
                <div class="btn-add-group">
                    <button class="btn-add-account" onclick="toggleForm('adminForm')">
                        <i class="fas fa-plus"></i> Admin
                    </button>
                    <button class="btn-add-account" style="background-color: #17a2b8;" onclick="toggleForm('coachForm')">
                        <i class="fas fa-user-shield"></i> Coach
                    </button>
                </div>
            </header>

            <?php if($message !== "") echo $message; ?>
            <?php if(isset($_GET['updated'])): ?>
                <div class="badge success" style="width:100%; padding:15px; margin-bottom:20px;">Status updated successfully!</div>
            <?php endif; ?>

            <!-- Add Admin Form -->
            <div id="adminForm" class="form-card">
                <h3>Create New Admin</h3>
                <form method="POST" class="form-grid">
                    <input type="hidden" name="role" value="Admin">
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <button type="submit" name="add_account" class="btn-create">Create Account</button>
                </form>
            </div>

            <!-- Coach Management Form -->
            <div id="coachForm" class="form-card">
                <h3>Coach Management</h3>
                <form method="POST" class="form-grid">
                    <input type="hidden" name="role" value="Coach">
                    <input type="text" name="username" placeholder="Coach Name" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <select name="spec" required>
                        <option value="" disabled selected>Select Coaching Specialty</option>
                        <option value="Singles Coaching">Singles Coaching</option>
                        <option value="Doubles Coaching">Doubles Coaching</option>
                        <option value="Fitness & Conditioning">Fitness &amp; Conditioning</option>
                        <option value="Junior Development">Junior Development</option>
                        <option value="Tournament Preparation">Tournament Preparation</option>
                    </select>
                    <input type="number" name="coach_price_per_hour" placeholder="Coach Price Per Hour (RM)" step="0.01" min="0" required>
                    <button type="submit" name="add_account" class="btn-create">Create Account</button>
                </form>
            </div>

            <div class="filter-bar">
                <i class="fas fa-filter"></i>
                <span>Filter By Role:</span>
                <select id="statusFilter" style="width: 200px;" onchange="location.href='AdminManagement.php?filter=' + this.value">
                    <option value="All" <?php echo ($current_filter == 'All' ? 'selected' : ''); ?>>All Staff</option>
                    <option value="Superadmin" <?php echo ($current_filter == 'Superadmin' ? 'selected' : ''); ?>>Superadmins Only</option>
                    <option value="Admin" <?php echo ($current_filter == 'Admin' ? 'selected' : ''); ?>>Admins Only</option>
                    <option value="Coach" <?php echo ($current_filter == 'Coach' ? 'selected' : ''); ?>>Coach Only</option>
                </select>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Staff Info</th>
                        <th style="text-align:center;">Role</th>
                        <th style="text-align:center;">Availability</th>
                        <th style="text-align:center;">Account Status</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>

                    <?php
                        // Fetch coach data if role is Coach
                        $coach_row2 = null;
                        if($row['role'] === 'Coach') {
                            $coach_q2   = mysqli_query($conn, "SELECT id, name, specialty, phone, gender, age, price_per_hour, profile_img, availability_status FROM coaches WHERE admin_id = {$row['id']} LIMIT 1");
                            $coach_row2 = mysqli_fetch_assoc($coach_q2);
                        }
                    ?>

                    <tr <?php if($row['role'] === 'Coach'): ?>
                        class="main-row"
                        onclick="openCoachEditModal(
                            <?php echo $coach_row2['id']; ?>,
                            '<?php echo addslashes($coach_row2['name'] ?? ''); ?>',
                            '<?php echo addslashes($coach_row2['specialty'] ?? ''); ?>',
                            '<?php echo addslashes($coach_row2['phone'] ?? ''); ?>',
                            '<?php echo addslashes($coach_row2['gender'] ?? ''); ?>',
                            '<?php echo $coach_row2['age'] ?? ''; ?>',
                            '<?php echo $coach_row2['price_per_hour'] ?? ''; ?>',
                            '<?php echo $coach_row2['profile_img'] ?? ''; ?>'
                        )"
                        style="cursor:pointer;"
                    <?php endif; ?>>

                        <td>#<?php echo $row['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($row['username']); ?></strong><br>
                            <small style="color:#999;"><?php echo htmlspecialchars($row['email']); ?></small>
                            <?php if ($row['role'] === 'Coach' && !empty($row['specialisation'])): ?>
                                <br><small style="color:#64748b;"><?php echo htmlspecialchars($row['specialisation']); ?> | RM <?php echo number_format((float)$row['coach_price_per_hour'], 2); ?>/hour</small>
                            <?php endif; ?>
                        </td>

                        <!-- Role: centered -->
                        <td style="text-align:center;">
                            <span class="role-label"><?php echo $row['role']; ?></span>
                        </td>

                        <!-- Availability: only for Coach, dash for others -->
                        <td style="text-align:center;" onclick="event.stopPropagation()">
                            <?php if($row['role'] === 'Coach' && $coach_row2): ?>
                                <?php
                                    $avail = $coach_row2['availability_status'] ?? 'Available';
                                    $avail_class = match($avail) {
                                        'Available' => 'status-active',
                                        'On Leave'  => 'status-inactive',
                                        'Sick'      => 'status-suspended',
                                        'Off Day'   => 'status-inactive',
                                        default     => 'status-inactive'
                                    };
                                ?>
                                <select class="status-select <?php echo $avail_class; ?>"
                                    onclick="event.stopPropagation()"
                                    onchange="location.href='AdminManagement.php?avail_id=<?php echo $coach_row2['id']; ?>&avail_status=' + this.value">
                                    <option value="Available" <?php echo $avail === 'Available' ? 'selected' : ''; ?>>Available</option>
                                    <option value="On Leave"  <?php echo $avail === 'On Leave'  ? 'selected' : ''; ?>>On Leave</option>
                                    <option value="Sick"      <?php echo $avail === 'Sick'      ? 'selected' : ''; ?>>Sick</option>
                                    <option value="Off Day"   <?php echo $avail === 'Off Day'   ? 'selected' : ''; ?>>Off Day</option>
                                </select>
                            <?php else: ?>
                                <span style="color:#cbd5e1;">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- Account Status: centered -->
                        <td style="text-align:center;" onclick="event.stopPropagation()">
                            <?php if ($row['role'] !== 'Superadmin'): ?>
                            <select class="status-select <?php 
                                if($row['status'] == 'Active') echo 'status-active';
                                elseif($row['status'] == 'Inactive') echo 'status-inactive';
                                else echo 'status-suspended';
                            ?>" onclick="event.stopPropagation()" onchange="location.href='AdminManagement.php?update_id=<?php echo $row['id']; ?>&new_status=' + this.value">
                                <option value="Active" <?php echo ($row['status'] == 'Active' ? 'selected' : ''); ?>>Active</option>
                                <option value="Inactive" <?php echo ($row['status'] == 'Inactive' ? 'selected' : ''); ?>>Inactive</option>
                                <option value="Suspended" <?php echo ($row['status'] == 'Suspended' ? 'selected' : ''); ?>>Suspended</option>
                            </select>
                            <?php else: ?>
                                <span class="status-master">MASTER</span>
                            <?php endif; ?>
                        </td>

                        <!-- Actions: centered, stopPropagation on td -->
                        <td style="text-align:center;" onclick="event.stopPropagation()">
                            <?php if ($row['role'] !== 'Superadmin'): ?>
                            <a href="?delete_id=<?php echo $row['id']; ?>" 
                                onclick="event.stopPropagation(); return confirm('Delete permanently?')">
                                <i class="fas fa-trash-alt delete-icon"></i>
                            </a>
                            <?php else: ?>
                                <i class="fas fa-lock" style="color:#ccc;"></i>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

        </div>
    </main>

    <!-- Edit Coach Modal -->
    <div class="modal-overlay" id="coachEditModal">
        <div class="modal-card">

            <div class="modal-header">
                <h2><i class="fas fa-pen"></i> Edit Coach Profile</h2>
                <button class="modal-close" onclick="closeCoachEditModal()">✕</button>
            </div>

            <!-- enctype needed for file upload -->
            <form action="AdminManagement.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="coach_id" id="coach-modal-id">

                <div class="modal-grid">

                    <!-- Profile Image -->
                    <div class="modal-field full-width" style="display:flex; flex-direction:column; align-items:center;">
                        <img id="coach-modal-img-preview"
                            src="Pictures/coaches/default.png"
                            style="width:80px; height:80px; border-radius:50%; object-fit:cover; margin-bottom:8px; border:3px solid #f59e0b;">
                        <label class="btn-create" style="cursor:pointer; padding:8px 16px; font-size:13px;">
                            <i class="fas fa-camera"></i> Change Photo
                            <input type="file" name="profile_img" id="coach-img-input" accept="image/*" style="display:none;">
                        </label>
                    </div>

                    <!-- Name -->
                    <div class="modal-field full-width">
                        <label>Name</label>
                        <input type="text" name="coach_name" id="coach-modal-name" required>
                    </div>

                    <!-- Specialty -->
                    <div class="modal-field full-width">
                        <label>Specialty</label>
                        <input type="text" name="specialty" id="coach-modal-specialty" required>
                    </div>

                    <!-- Phone -->
                    <div class="modal-field">
                        <label>Phone</label>
                        <input type="text" name="phone" id="coach-modal-phone">
                    </div>

                    <!-- Price -->
                    <div class="modal-field">
                        <label>Price Per Hour (RM)</label>
                        <input type="number" name="price_per_hour" id="coach-modal-price" step="0.01" min="0" required>
                    </div>

                    <!-- Gender -->
                    <div class="modal-field">
                        <label>Gender</label>
                        <select name="gender" id="coach-modal-gender">
                            <option value="">-- Select --</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>

                    <!-- Age -->
                    <div class="modal-field">
                        <label>Age</label>
                        <input type="number" name="age" id="coach-modal-age" min="18" max="80">
                    </div>

                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-modal-cancel" onclick="closeCoachEditModal()">Cancel</button>
                    <button type="submit" name="update_coach" class="btn-modal-save">Save Changes</button>
                </div>
            </form>

        </div>
    </div>

    <script src="AdminManagement.js"></script>
    <script src="SuperAdminDashboard.js"></script>

</body>
</html>