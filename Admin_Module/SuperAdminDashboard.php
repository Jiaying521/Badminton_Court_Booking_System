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
    $u_id = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;

    $conn = mysqli_connect("localhost", "root", "", "badminton_hub");
    
    if (!$conn) {
        header('Content-Type: application/json');
        echo json_encode(["error" => "Database connection failed"]);
        exit();
    }

    $safe_date = mysqli_real_escape_string($conn, $target_date);
    $safe_name = mysqli_real_escape_string($conn, $u_name);

    $sql = "SELECT COALESCE(u.name, CONCAT('User ID: ', b.user_id)) AS player_name,
                   b.start_time AS booking_time,
                   b.end_time,
                   b.status,
                   c.court_name,
                   COALESCE(co.name, 'No coach') AS coach_name
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.id
            LEFT JOIN courts c ON b.court_id = c.id
            LEFT JOIN coaches co ON b.coach_id = co.id
            WHERE b.booking_date = '$safe_date'";
    
    if (strtolower($u_role) === 'coach') {
        $sql .= " AND (co.admin_id = $u_id OR TRIM(LOWER(co.name)) = TRIM(LOWER('$safe_name')))";
    }
    
    $sql .= " ORDER BY b.start_time ASC LIMIT 10"; 
    $res = mysqli_query($conn, $sql);
    
    if (!$res) {
        header('Content-Type: application/json');
        echo json_encode(["error" => mysqli_error($conn), "sql" => $sql]); 
        exit();
    }

    $bookings = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $row['booking_time'] = date("h:i A", strtotime($row['booking_time']));
        $row['end_time'] = date("h:i A", strtotime($row['end_time']));
        if (strtolower($u_role) !== 'coach' && $row['coach_name'] !== 'No coach') {
            $row['player_name'] .= " (Coach: " . $row['coach_name'] . ")";
        }
        $bookings[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($bookings);
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
// Display name for coach
$display_name = $username;

/* --- Database Connection --- */
$db = mysqli_connect("localhost", "root", "", "badminton_hub");

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
            $admin_id = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;
            mysqli_query($db, "UPDATE coaches SET is_active = 1 WHERE admin_id = $admin_id");
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

/* Count Total Admins */
$query_admin = mysqli_query($db, "SELECT COUNT(*) AS total FROM admins WHERE role = 'Admin'");
$total_admins = mysqli_fetch_assoc($query_admin)['total'];

/* Count Total Coaches */
$query_coaches = mysqli_query($db, "SELECT COUNT(*) AS total FROM admins WHERE role = 'Coach'");
$total_coaches = mysqli_fetch_assoc($query_coaches)['total'];

/* Count today's bookings (Filtered by role) */
$today = date("Y-m-d");
$booking_join = "";
$booking_filter = "";

if (strtolower($role) === 'coach') {
    $admin_id = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;
    $safe_username = mysqli_real_escape_string($db, $username);
    $booking_join = " LEFT JOIN coaches co ON b.coach_id = co.id";
    $booking_filter = " AND (co.admin_id = $admin_id OR TRIM(LOWER(co.name)) = TRIM(LOWER('$safe_username')))";
}

$query_today = mysqli_query($db, "SELECT COUNT(*) AS today_bookings FROM bookings b $booking_join WHERE b.booking_date = '$today' $booking_filter");

if ($query_today) {
    $data_today = mysqli_fetch_assoc($query_today);
    $today_appointments = $data_today['today_bookings'];
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

/* --- Booking Chart Statistics --- */
$current_year = date("Y");

/* Initialize data arrays for 12 months */
$pending_data = array_fill(0, 12, 0);
$confirmed_data = array_fill(0, 12, 0);
$completed_data = array_fill(0, 12, 0);
$cancelled_data = array_fill(0, 12, 0);

/* Filter data if the user is a Coach */
$coach_filter = "";
$coach_join = "";
if (strtolower($role) === 'coach') { 
    $admin_id = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;
    $safe_username = mysqli_real_escape_string($db, $username);
    $coach_join = " LEFT JOIN coaches co ON b.coach_id = co.id";
    $coach_filter = " AND (co.admin_id = $admin_id OR TRIM(LOWER(co.name)) = TRIM(LOWER('$safe_username')))"; 
}

$status_sql = "SELECT MONTH(b.booking_date) AS month_num, b.status, COUNT(*) AS count
               FROM bookings b
               $coach_join
               WHERE YEAR(b.booking_date) = '$current_year' $coach_filter
               GROUP BY month_num, status";
               
$stats_result = mysqli_query($db, $status_sql);

/* Process result set into arrays */
if ($stats_result) {
    while ($row = mysqli_fetch_assoc($stats_result)) {
        if (!empty($row['month_num'])) {
            $m_idx = (int)$row['month_num'] - 1; 
            $status = $row['status'];
            $count = (int)$row['count'];

            if ($status == 'Pending') $pending_data[$m_idx] = $count;
            elseif ($status == 'Confirmed') $confirmed_data[$m_idx] = $count;
            elseif ($status == 'Completed') $completed_data[$m_idx] = $count;
            elseif ($status == 'Cancelled') $cancelled_data[$m_idx] = $count;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Badminton Hub - Dashboard</title>

    <!-- External CSS & Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap">
    <link rel="stylesheet" href="SuperAdminDashboard.css">

    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Flatpickr Calendar -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
</head>

<body>
    <!-- Top Navigation Bar -->
    <?php include 'navbar.php'; ?>

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
                    <div class="stat-info"><h3>Total Coaches</h3><p><?php echo $total_coaches; ?></p></div>
                </div>
            <?php endif; ?>

            <div class="stat-box">
                <div class="stat-icon orange"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-info">
                    <h3>Today's Bookings</h3>
                    <p><?php echo $today_appointments; ?></p>
                </div>
            </div>
        </section>

        <!-- Data Visualization and Shortcuts Layout -->
        <div class="dashboard-layout">
            
            <!-- Left: Booking Statistics -->
            <div class="data-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h2>Booking Statistics</h2>
                    <select id="statusFilter" onchange="filterStats()" style="padding: 5px; border-radius: 5px;">
                        <option value="All">Show All</option>
                        <option value="Pending">Pending</option>
                        <option value="Confirmed">Confirmed</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <div style="height: 300px; width: 100%;">
                    <canvas id="myChart"></canvas>
                </div>
            </div>

            <!-- Flatpickr Calendar Section -->
            <div class="calendar-section">
                <div class="calendar-header">
                    <h2>Bookings Calendar</h2>
                </div>
                
                <!-- Show calendar-->
                <div id="inline-calendar"></div>

                <div id="appointment-list" style="padding : 15px;">
                    <div id="mini-appt-list">
                        <p style="text-align:center; color:#999; font-size:13px;">Select a date to view bookings</p>
                    </div>

                    <a href="#" id="view-all-btn" class="view-all-btn" style="display:none;">View All Bookings</a> 
                </div>
                
            </div>

    </main>

    <!-- Force Password Change Modal (Visible only if status is Inactive) -->
    <?php if ($current_status === 'Inactive'): ?>
    <div class="force-change-overlay">
        <div class="force-change-card">
            <i class="fas fa-lock" style="font-size: 40px; color: #f59e0b; margin-bottom: 20px;"></i>
            <h2>Security Update</h2>
            <p>Welcome to Badminton Hub! Since this is your first login, please update your temporary password to activate your account.</p>
            
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
            pending: <?php echo json_encode($pending_data); ?>,
            confirmed: <?php echo json_encode($confirmed_data); ?>,
            completed: <?php echo json_encode($completed_data); ?>,
            cancelled: <?php echo json_encode($cancelled_data); ?>
        };
    </script>

    <!-- Dashboard Scripts -->
    <script src="SuperAdminDashboard.js"></script>
</body>
</html>
