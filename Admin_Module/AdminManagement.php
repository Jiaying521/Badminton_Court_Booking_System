<?php
session_start();

// --- Prevent browser caching (Solve the logout back-button issue) ---
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

// Database connection
$conn = mysqli_connect("localhost", "root", "", "care_connect");

// Security check - If no session, redirect immediately
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Superadmin') {
    header("Location: LoginPage.php");
    exit();
}

// Function to send email logic
function sendTemporaryPassword($to_email, $username, $temp_pass) {
    $subject = "CareConnect Credentials";
    $message = "<html><body><h2>Welcome $username</h2><p>Temp Pass: $temp_pass</p></body></html>";
    $headers = "MIME-Version: 1.0" . "\r\n" . "Content-type:text/html;charset=UTF-8" . "\r\n" . "From: <noreply@careconnect.com>";
    return mail($to_email, $subject, $message, $headers);
}

$message = "";

// Handle account creation
if (isset($_POST['add_account'])) {
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $spec = isset($_POST['spec']) ? mysqli_real_escape_string($conn, $_POST['spec']) : NULL;

    $temp_pass = bin2hex(random_bytes(4));
    $hashed_pass = password_hash($temp_pass, PASSWORD_DEFAULT);

    $check = mysqli_query($conn, "SELECT id FROM admins WHERE username = '$user'");
    if (mysqli_num_rows($check) > 0) {
        $message = "<div class='badge pending' style='width:100%; padding:15px; margin-bottom:20px;'>Error: Username already exists!</div>";
    } else {
        // Status is set to 'Inactive' for new accounts
        $sql = "INSERT INTO admins (username, email, password, role, status, specialisation) 
                VALUES ('$user', '$email', '$hashed_pass', '$role', 'Inactive', '$spec')";
        if (mysqli_query($conn, $sql)) {
            sendTemporaryPassword($email, $user, $temp_pass);
            $message = "<div class='badge success' style='width:100%; padding:15px; margin-bottom:20px;'>Success: Account Created. Temporary Password: <b>$temp_pass</b></div>";
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
    mysqli_query($conn, "DELETE FROM admins WHERE id = $did AND role != 'Superadmin'");
    header("Location: AdminManagement.php");
    exit();
}

// Filter Logic
$current_filter = isset($_GET['filter']) ? $_GET['filter'] : 'All';
$query = "SELECT * FROM admins WHERE role != 'Superadmin'";
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
            <li><a href="AdminManagement.php">Admin Management</a></li>
            <li><a href="#">System Settings</a></li>
            <li><button id="logout-btn" class="logout-btn">Logout</button></li>
        </ul>
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
                <select id="statusFilter" style="width: 180px;" onchange="location.href='AdminManagement.php?filter=' + this.value">
                    <option value="All" <?php echo ($current_filter == 'All' ? 'selected' : ''); ?>>All Staff</option>
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
                            <select class="status-select <?php 
                                if($row['status'] == 'Active') echo 'status-active';
                                elseif($row['status'] == 'Inactive') echo 'status-inactive';
                                else echo 'status-suspended';
                            ?>" onchange="location.href='AdminManagement.php?update_id=<?php echo $row['id']; ?>&new_status=' + this.value">
                                <option value="Active" <?php echo ($row['status'] == 'Active' ? 'selected' : ''); ?>>Active</option>
                                <option value="Inactive" <?php echo ($row['status'] == 'Inactive' ? 'selected' : ''); ?>>Inactive</option>
                                <option value="Suspended" <?php echo ($row['status'] == 'Suspended' ? 'selected' : ''); ?>>Suspended</option>
                            </select>
                        </td>
                        <td>
                            <a href="?delete_id=<?php echo $row['id']; ?>" onclick="return confirm('Delete permanently?')">
                                <i class="fas fa-trash-alt delete-icon"></i>
                            </a>
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