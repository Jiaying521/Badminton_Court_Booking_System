<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'Email_System/phpmailer/src/Exception.php';
require 'Email_System/phpmailer/src/PHPMailer.php';
require 'Email_System/phpmailer/src/SMTP.php';

session_start();

$username = $_SESSION['username'];
$role = $_SESSION['role'];
$display_name = $username;

// --- Prevent browser caching (Solve the logout back-button issue) ---
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

// Database connection
$conn = mysqli_connect("localhost", "root", "", "badminton_hub");

// Security check - If no session, redirect immediately
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Superadmin') {
    header("Location: LoginPage.php");
    exit();
}

// Updated Function using PHPMailer
function sendTemporaryPassword($to_email, $username, $temp_pass) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        // Use your working credentials from SendResetLogic
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

// Handle account creation
if (isset($_POST['add_account'])) {
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role_to_add = mysqli_real_escape_string($conn, $_POST['role']);
    $spec = isset($_POST['spec']) ? mysqli_real_escape_string($conn, $_POST['spec']) : NULL;
    
    // Set is_coach based on role for DB compatibility
    $is_doc_val = ($role_to_add === 'Coach') ? 1 : 0;

    $temp_pass = bin2hex(random_bytes(4));
    $hashed_pass = password_hash($temp_pass, PASSWORD_DEFAULT);

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
        // Corrected INSERT: Omit 'id' for Auto_Increment and added required columns
        // Added is_coach and first_login to the SQL to match your database
        $sql = "INSERT INTO admins (username, email, password, role, status, specialisation, is_coach, first_login) 
                VALUES ('$user', '$email', '$hashed_pass', '$role_to_add', 'Inactive', '$spec', '$is_doc_val', 0)";
        
        if (mysqli_query($conn, $sql)) {
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
    $uid = intval($_GET['update_id']);
    $status = mysqli_real_escape_string($conn, $_GET['new_status']);
    mysqli_query($conn, "UPDATE admins SET status = '$status' WHERE id = $uid");
    header("Location: AdminManagement.php");
    exit();
}

// Handle delete
if (isset($_GET['delete_id'])) {
    $did = intval($_GET['delete_id']);
    // Prevent deleting superadmin for safety
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
    <link rel="stylesheet" href="AdminManagement.css">
</head>
<body>

    <nav class="nav-bar">
        <div class="nav-left">
            <button id="menu-toggle" class="menu-toggle">☰</button>
            <img src="Pictures/logo.png" alt="logo" class="logo">
            <span class="brand-name"><span class="text-primary">Badminton</span><span class="text-dark">Hub</span></span> 
        </div>

        <ul id="nav-menu" class="nav-links">
            <li><a href="SuperAdminDashboard.php">Dashboard</a></li>
            <?php if ($role === 'Superadmin'): ?>
                <!-- Superadmin Menu -->
                <li><a href="AdminManagement.php">Admin Management</a></li>
                <li><a href="#">System Settings</a></li>
            <?php endif; ?>
            <li><button id="logout-btn" class="logout-btn">Logout</button></li>
        </ul>

        <div class="user-info">
            <span id="welcome-text">Hello, <?php echo htmlspecialchars($display_name); ?>!</span>
        </div>
    </nav>

     
    

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
                    <input type="text" name="username" placeholder="Coach Management" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <select name="spec" required>
                        <option value="" disabled selected>Select Coaching Specialty</option>
                        <option value="Singles Coaching">Singles Coaching</option>
                        <option value="Doubles Coaching">Doubles Coaching</option>
                        <option value="Fitness & Conditioning">Fitness &amp; Conditioning</option>
                        <option value="Junior Development">Junior Development</option>
                        <option value="Tournament Preparation">Tournament Preparation</option>
                    </select>
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
                    <option value="Coach" <?php echo ($current_filter == 'Coach' ? 'selected' : ''); ?>>Coach Management</option>
                </select>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Staff Info</th>
                        <th>Role</th>
                        <th>Status Selection</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($row['username']); ?></strong><br>
                            <small style="color:#999;"><?php echo htmlspecialchars($row['email']); ?></small>
                        </td>
                        <td><span class="role-label"><?php echo $row['role']; ?></span></td>
                        <td>
                            <?php if ($row['role'] !== 'Superadmin'): ?>
                            <select class="status-select <?php 
                                if($row['status'] == 'Active') echo 'status-active';
                                elseif($row['status'] == 'Inactive') echo 'status-inactive';
                                else echo 'status-suspended';
                            ?>" onchange="location.href='AdminManagement.php?update_id=<?php echo $row['id']; ?>&new_status=' + this.value">
                                <option value="Active" <?php echo ($row['status'] == 'Active' ? 'selected' : ''); ?>>Active</option>
                                <option value="Inactive" <?php echo ($row['status'] == 'Inactive' ? 'selected' : ''); ?>>Inactive</option>
                                <option value="Suspended" <?php echo ($row['status'] == 'Suspended' ? 'selected' : ''); ?>>Suspended</option>
                            </select>
                            <?php else: ?>
                                <span class="status-master">MASTER</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['role'] !== 'Superadmin'): ?>
                            <a href="?delete_id=<?php echo $row['id']; ?>" onclick="return confirm('Delete permanently?')">
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

    <script>
        function toggleForm(id) {
            document.getElementById('adminForm').classList.remove('active');
            document.getElementById('coachForm').classList.remove('active');
            document.getElementById(id).classList.add('active');
        }
    </script>
    
    <script src="SuperAdminDashboard.js"></script>
</body>
</html>