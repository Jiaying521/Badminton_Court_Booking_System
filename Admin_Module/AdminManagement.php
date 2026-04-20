<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'Email_System/phpmailer/src/Exception.php';
require 'Email_System/phpmailer/src/PHPMailer.php';
require 'Email_System/phpmailer/src/SMTP.php';

session_start();

$username = $_SESSION['username'];
$role = $_SESSION['role'];
$display_name = ($role === 'Doctor') ? "Dr. " . $username : $username;

// --- Prevent browser caching (Solve the logout back-button issue) ---
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

// Database connection
$conn = mysqli_connect("localhost", "root", "", "care_connect");

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
        $mail->Username   = 'adminclinic2026@gmail.com'; 
        $mail->Password   = 'wugc qoue fcta diqx';   
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('adminclinic2026@gmail.com', 'Care Connect Clinic');
        $mail->addAddress($to_email);

        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'CareConnect Credentials';
        $mail->Body    = "<html><body><h2>Welcome $username</h2><p>Your account has been created. Your temporary password is: <b>$temp_pass</b></p><p>Please change your password after logging in.</p></body></html>";

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
    
    // Set is_doctor based on role for DB compatibility
    $is_doc_val = ($role_to_add === 'Doctor') ? 1 : 0;

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
        // Added is_doctor and first_login to the SQL to match your database
        $sql = "INSERT INTO admins (username, email, password, role, status, specialisation, is_doctor, first_login) 
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
    <title>CareConnect - Admin Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="SuperAdminDashboard.css">
    <link rel="stylesheet" href="AdminManagement.css">
</head>
<body>

    <nav class="nav-bar">
        <div class="nav-left">
            <button id="menu-toggle" class="menu-toggle">☰</button>
            <img src="Pictures/logo.png" alt="logo" class="logo">
            <span class="brand-name"><span class="text-blue">Care</span><span class="text-dark">Connect</span></span> 
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
                    <p>Manage clinic staff accounts and permissions.</p>
                </div>
                <div class="btn-add-group">
                    <button class="btn-add-account" onclick="toggleForm('adminForm')">
                        <i class="fas fa-plus"></i> Admin
                    </button>
                    <button class="btn-add-account" style="background-color: #17a2b8;" onclick="toggleForm('doctorForm')">
                        <i class="fas fa-user-md"></i> Doctor
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

            <!-- Add Doctor Form -->
            <div id="doctorForm" class="form-card">
                <h3>Create New Doctor</h3>
                <form method="POST" class="form-grid">
                    <input type="hidden" name="role" value="Doctor">
                    <input type="text" name="username" placeholder="Doctor Name" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <select name="spec" required>
                        <option value="" disabled selected>Select Specialisation</option>
                        <option value="General Practice">General Practice</option>
                        <option value="Cardiology">Cardiology</option>
                        <option value="Dermatology">Dermatology</option>
                        <option value="Pediatrics">Pediatrics</option>
                        <option value="Neurology">Neurology</option>
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
                    <option value="Doctor" <?php echo ($current_filter == 'Doctor' ? 'selected' : ''); ?>>Doctors Only</option>
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
            document.getElementById('doctorForm').classList.remove('active');
            document.getElementById(id).classList.add('active');
        }
    </script>
    
    <script src="SuperAdminDashboard.js"></script>
</body>
</html>