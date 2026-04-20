<?php
session_start();

/* --- Logout Logic --- */
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: LoginPage.php");
    exit();
}

/* --- AJAX Fetch Handling --- */
if (isset($_GET['ajax_fetch'])) {

    $raw_date = $_GET['ajax_fetch'];
    $target_date = date("Y-m-d", strtotime($raw_date));
    
    $u_name = isset($_SESSION['username']) ? $_SESSION['username'] : '';
    $u_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

    $conn = mysqli_connect("localhost", "root", "", "care_connect");
    
    if (!$conn) {
        header('Content-Type: application/json');
        echo json_encode(["error" => "Database connection failed"]);
        exit();
    }

    $sql = "SELECT COALESCE(u.name, CONCAT('User ID: ', a.user_id)) AS patient_name,
                   a.appointment_time, 
                   a.status, 
                   a.doctor_name 
            FROM appointments a 
            LEFT JOIN users u ON a.user_id = u.id 
            WHERE DATE(a.appointment_date) = '$target_date'";
    
    if (strtolower($u_role) === 'doctor') {
        $sql .= " AND TRIM(LOWER(a.doctor_name)) = TRIM(LOWER('$u_name'))";
    }
    
    $sql .= " ORDER BY a.appointment_time ASC LIMIT 10"; 
    $res = mysqli_query($conn, $sql);
    
    if (!$res) {
        header('Content-Type: application/json');
        echo json_encode(["error" => mysqli_error($conn), "sql" => $sql]); 
        exit();
    }

    $appointments = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $row['appointment_time'] = date("h:i A", strtotime($row['appointment_time']));
        if (strtolower($u_role) !== 'doctor') {
            $row['patient_name'] .= " (Dr. " . $row['doctor_name'] . ")";
        }
        $appointments[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($appointments);
    exit(); 
}

/* --- Security Check --- */
if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    header("Location: LoginPage.php");
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];

/* --- Display Name Logic --- */
// If the user is a Doctor, add "Dr."
$display_name = ($role === 'Doctor') ? "Dr. " . $username : $username;

/* --- Database Connection --- */
$db = mysqli_connect("localhost", "root", "", "care_connect");

/* --- First Time Login Check & Password Update Logic --- */
$status_query = mysqli_query($db, "SELECT status FROM admins WHERE username = '$username'");
$user_data = mysqli_fetch_assoc($status_query);
$current_status = $user_data['status'];

$update_error = "";
if (isset($_POST['update_initial_password'])) {
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    // Security requirement regex: At least 8 characters, letters, numbers, and symbols
    $strongPasswordRegex = "/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/";

    if ($new_pass !== $confirm_pass) {
        $update_error = "Passwords do not match!";
    } elseif (!preg_match($strongPasswordRegex, $new_pass)) {
        $update_error = "Security requirement: At least 8 characters, including letters, numbers, and symbols (@$!%*?&).";
    } else {
        $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
        // Update password and change status to 'Active'
        $update_sql = "UPDATE admins SET password = '$hashed_password', status = 'Active' WHERE username = '$username'";
        if (mysqli_query($db, $update_sql)) {
            header("Location: SuperAdminDashboard.php"); 
            exit();
        } else {
            $update_error = "Database error. Please try again.";
        }
    }
}

/* --- Dashboard Statistics --- */

/* Count total superadmins */
$query_super = mysqli_query($db, "SELECT COUNT(*) AS total FROM admins WHERE role = 'Superadmin'");
$total_superadmins = mysqli_fetch_assoc($query_super)['total'];

/* Count Total Admins (The clinic managers) */
$query_admin = mysqli_query($db, "SELECT COUNT(*) AS total FROM admins WHERE role = 'Admin'");
$total_admins = mysqli_fetch_assoc($query_admin)['total'];

/* Count Total Doctors */
$query_doctors = mysqli_query($db, "SELECT COUNT(*) AS total FROM admins WHERE role = 'Doctor'");
$total_doctors = mysqli_fetch_assoc($query_doctors)['total'];

/* Count today's appointments (Filtered by role) */
$today = date("Y-m-d");
$appt_filter = "";

if (strtolower($role) === 'doctor') {
    $appt_filter = " AND doctor_name = '$username'";
}

$query_today = mysqli_query($db, "SELECT COUNT(*) AS today_appointments FROM appointments WHERE appointment_date = '$today' $appt_filter");

if ($query_today) {
    $data_today = mysqli_fetch_assoc($query_today);
    $today_appointments = $data_today['today_appointments'];
} else {
    $today_appointments = 0;
}

/* Count pending tasks */
$query_pending = mysqli_query($db, "SELECT COUNT(*) AS pending_requests FROM tasks WHERE status = 'Pending'");
if($query_pending){
    $data_pending = mysqli_fetch_assoc($query_pending);
    $pending_requests = $data_pending['pending_requests'];
} else {
    $pending_requests = 0;
}

/* --- Appointment Chart Statistics --- */
$current_year = date("Y");

/* Initialize data arrays for 12 months */
$completed_data = array_fill(0, 12, 0);
$cancelled_data = array_fill(0, 12, 0);
$rescheduled_data = array_fill(0, 12, 0);
$ongoing_data = array_fill(0, 12, 0);

/* Filter data if the user is a Doctor */
$doctor_filter = "";
if (strtolower($role) === 'doctor') { 
    $doctor_filter = " AND doctor_name = '$username'"; 
}

$status_sql = "SELECT MONTH(appointment_date) AS month_num, status, COUNT(*) AS count
               FROM appointments
               WHERE YEAR(appointment_date) = '$current_year' $doctor_filter
               GROUP BY month_num, status";
               
$stats_result = mysqli_query($db, $status_sql);

/* Process result set into arrays */
if ($stats_result) {
    while ($row = mysqli_fetch_assoc($stats_result)) {
        if (!empty($row['month_num'])) {
            $m_idx = (int)$row['month_num'] - 1; 
            $status = $row['status'];
            $count = (int)$row['count'];

            if ($status == 'Completed') $completed_data[$m_idx] = $count;
            elseif ($status == 'Cancelled') $cancelled_data[$m_idx] = $count;
            elseif ($status == 'Rescheduled') $rescheduled_data[$m_idx] = $count;
            elseif ($status == 'Ongoing') $ongoing_data[$m_idx] = $count;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareConnect - Dashboard</title>

    <!-- External CSS & Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="SuperAdminDashboard.css">

    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Flatpickr Calendar -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
</head>

<body>
    <!-- Top Navigation Bar -->
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

            <?php elseif ($role === 'Admin'): ?>
                <!-- Admin Menu -->
                <li><a href="DoctorManagement.php">Doctor Management</a></li>
                <li><a href="AppointmentManagement.php">Appointments</a></li>
                <li><a href="ScheduleManagement.php">Schedule Management</a></li>
                <li class="dropdown">
                    <a href="#" class="drop-btn">More Options ▼</a>
                    <ul class="submenu">
                        <li><a href="PatientList.php">Patient List</a></li>
                        <li><a href="Reports.php">Reports & Analytics</a></li>
                        <li><a href="Notifications.php">Notifications</a></li>
                        <li><a href="ConflictManagement.php">Conflict Management</a></li>
                        <li><a href="Settings.php">Appointment Settings</a></li>
                    </ul>
                </li>

            <?php elseif ($role === 'Doctor'): ?>
                <!-- Doctor Menu -->
                <li><a href="MyAppointments.php">My Appointments</a></li>
                <li><a href="MySchedule.php">My Schedule</a></li>
                <li><a href="MyPatients.php">My Patients</a></li>
                <li><a href="Profile.php">Profile</a></li>
            <?php endif; ?>
            
            <li><button id="logout-btn" class="logout-btn" onclick="location.href='SuperAdminDashboard.php?action=logout'">Logout</button></li>

        </ul>

        <div class="user-info">
             <span id="welcome-text">Hello, <?php echo htmlspecialchars($display_name); ?>!</span>
        </div>
    </nav>

    <!-- Sidebar Overlay -->
    <div id="overlay" class="overlay"></div>

    <!-- Main Content Area -->
    <main class="content">
        <header class="dashboard-header">
            <div class="welcome-section">
                <h1>Welcome, <?php echo htmlspecialchars($display_name); ?>!</h1>
                <p>Status: <span class="status-online">● Online</span> | Role: <?php echo strtoupper($role); ?></p>
            </div>
            
            <?php if ($role === 'Superadmin'): ?>
            <div class="header-actions">
                <br>
                <a href="AdminManagement.php" class="action-btn"><i class="fas fa-user-plus"></i> Add Admin</a>
            </div>
            <?php endif; ?>
        </header>

        <!-- Statistics Cards Grid -->
        <section class="stats-grid">
            <?php if ($role === 'Superadmin'): ?>
                <div class="stat-box">
                    <div class="stat-icon blue"><i class="fas fa-user-shield"></i></div>
                    <div class="stat-info"><h3>Total Admins</h3><p><?php echo $total_admins; ?></p></div>
                </div>
            <?php endif; ?>

            <?php if ($role === 'Superadmin' || $role === 'Admin'): ?>
                <div class="stat-box">
                    <div class="stat-icon green"><i class="fas fa-user-md"></i></div>
                    <div class="stat-info"><h3>Total Doctors</h3><p><?php echo $total_doctors; ?></p></div>
                </div>
            <?php endif; ?>

            <div class="stat-box">
                <div class="stat-icon orange"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-info">
                    <h3>Today's Appt</h3>
                    <p><?php echo $today_appointments; ?></p>
                </div>
            </div>
        </section>

        <!-- Data Visualization and Shortcuts Layout -->
        <div class="dashboard-layout">
            
            <!-- Left: Appointment Statistics -->
            <div class="data-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h2>Appointments Statistics</h2>
                    <select id="statusFilter" onchange="filterStats()" style="padding: 5px; border-radius: 5px;">
                        <option value="All">Show All</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                        <option value="Rescheduled">Rescheduled</option>
                        <option value="Ongoing">Ongoing</option>
                    </select>
                </div>
                <div style="height: 300px; width: 100%;">
                    <canvas id="myChart"></canvas>
                </div>
            </div>

            <!-- Flatpickr Calendar Section -->
            <div class="calendar-section">
                <div class="calendar-header">
                    <h2>Appointment</h2>
                </div>
                
                <!-- Show calendar-->
                <div id="inline-calendar"></div>

                <div id="appointment-list" style="padding : 15px;">
                    <div id="mini-appt-list">
                        <p style="text-align:center; color:#999; font-size:13px;">Select a date to view appointments</p>
                    </div>

                    <a href="#" id="view-all-btn" class="view-all-btn" style="display:none;">View All Appointments</a> 
                </div>
                
            </div>

    </main>

    <!-- Force Password Change Modal (Visible only if status is Inactive) -->
    <?php if ($current_status === 'Inactive'): ?>
    <div class="force-change-overlay">
        <div class="force-change-card">
            <i class="fas fa-lock" style="font-size: 40px; color: #1E90FF; margin-bottom: 20px;"></i>
            <h2>Security Update</h2>
            <p>Welcome to CareConnect! Since this is your first login, please update your temporary password to activate your account.</p>
            
            <?php if ($update_error !== ""): ?>
                <div class="error-msg"><?php echo $update_error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="force-input-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" placeholder="Enter new password" required minlength="8">
                </div>
                <div class="force-input-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" placeholder="Confirm new password" required minlength="8">
                </div>
                <button type="submit" name="update_initial_password" class="btn-update-pass">
                    Update Password & Activate
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Passing PHP arrays to JavaScript -->
    <script>
        const chartData = {
            completed: <?php echo json_encode($completed_data); ?>,
            cancelled: <?php echo json_encode($cancelled_data); ?>,
            rescheduled: <?php echo json_encode($rescheduled_data); ?>,
            ongoing: <?php echo json_encode($ongoing_data); ?>
        };
    </script>

    <!-- Dashboard Scripts -->
    <script src="SuperAdminDashboard.js"></script>
</body>
</html>