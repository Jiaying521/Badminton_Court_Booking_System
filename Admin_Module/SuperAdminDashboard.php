<?php
session_start();

/* --- Security Check --- */
if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    header("Location: LoginPage.php");
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];

/* --- Database Connection --- */
$db = mysqli_connect("localhost", "root", "", "care_connect");

/* --- Dashboard Statistics --- */

/* Count total superadmins */
$query_admins = mysqli_query($db, "SELECT COUNT(*) AS total_admins FROM admins WHERE role = 'superadmin'");
$data_admins = mysqli_fetch_assoc($query_admins);
$total_admins = $data_admins['total_admins'];

/* Count total doctors (admin role) */
$query_doctors = mysqli_query($db, "SELECT COUNT(*) AS total_doctors FROM admins WHERE role = 'admin'");
$data_doctors = mysqli_fetch_assoc($query_doctors);
$total_doctors = $data_doctors['total_doctors'];

/* Count today's appointments */
$today = date("Y-m-d");
$query_today = mysqli_query($db, "SELECT COUNT(*) AS today_appointments FROM appointments WHERE appointment_date = '$today'");
$data_today = mysqli_fetch_assoc($query_today);
$today_appointments = $data_today['today_appointments'];

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

/* SQL query for monthly status counts */
$status_sql = "SELECT MONTH(appointment_date) AS month_num, status, COUNT(*) AS count
               FROM appointments
               WHERE YEAR(appointment_date) = '$current_year'
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
            <?php if ($role === 'superadmin'): ?>
                <li><a href="AdminManagement.php">Admin Management</a></li>
                <li><a href="SystemSettings.php">System Settings</a></li>
            <?php else: ?>
                <li><a href="DoctorManagement.php">Doctor Management</a></li>
                <li><a href="AppointmentManagement.php">Appointments</a></li>
            <?php endif; ?>
            
            <li class="dropdown">
                <a href="#" class="drop-btn">More Options ▼</a>
                <ul class="submenu">
                    <?php if ($role === 'superadmin'): ?>
                        <li><a href="DoctorManagement.php">Doctor Management</a></li>
                        <li><a href="ScheduleManagement.php">Schedule Management</a></li>
                        <li><a href="PatientList.php">Patient List</a></li>
                        <li><a href="AppointmentManagement.php">Appointment Management</a></li>
                        <li><a href="Reports.php">Reports & Analytics</a></li>
                        <li><a href="Notifications.php">Notifications</a></li>
                        <li><a href="ConflictManagement.php">Conflict Management</a></li>
                        <li><a href="AppointmentSettings.php">Appointment Settings</a></li>
                    <?php else: ?>
                        <li><a href="ScheduleManagement.php">Schedule Management</a></li>
                        <li><a href="PatientList.php">Patient List</a></li>
                        <li><a href="Reports.php">Reports & Analytics</a></li>
                        <li><a href="Notifications.php">Notifications</a></li>
                        <li><a href="ConflictManagement.php">Conflict Management</a></li>
                        <li><a href="AppointmentSettings.php">Appointment Settings</a></li>
                    <?php endif; ?>
                    <li><a href="Profile.php">Profile</a></li>
                </ul>
            </li>
            <li><button id="logout-btn" class="logout-btn">Logout</button></li>
        </ul>

        <div class="user-info">
             <span id="welcome-text">Hello, <?php echo htmlspecialchars($username); ?>!</span>
        </div>
    </nav>

    <!-- Sidebar Overlay -->
    <div id="overlay" class="overlay"></div>

    <!-- Main Content Area -->
    <main class="content">
        <header class="dashboard-header">
            <div class="welcome-section">
                <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
                <p>Status: <span class="status-online">● Online</span> | Role: <?php echo strtoupper($role); ?></p>
            </div>
            
            <?php if ($role === 'superadmin'): ?>
            <div class="header-actions">
                <br>
                <a href="AdminManagement.php" class="action-btn"><i class="fas fa-user-plus"></i> Add Admin</a>
            </div>
            <?php endif; ?>
        </header>

        <!-- Statistics Cards Grid -->
        <section class="stats-grid">
            <div class="stat-box">
                <div class="stat-icon blue"><i class="fas fa-users-cog"></i></div>
                <div class="stat-info">
                    <h3>Total Admins</h3>
                    <p><?php echo $total_admins; ?></p>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-icon green"><i class="fas fa-user-md"></i></div>
                <div class="stat-info">
                    <h3>Doctors</h3>
                    <p><?php echo $total_doctors; ?></p>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-icon orange"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-info">
                    <h3>Today's Appt</h3>
                    <p><?php echo $today_appointments; ?></p>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-icon red"><i class="fas fa-exclamation-circle"></i></div>
                <div class="stat-info">
                    <h3>Pending Tasks</h3>
                    <p><?php echo $pending_requests; ?></p>
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
                
            </div>

            <!-- Quick Shortcuts -->
            <div class="shortcuts-section">
                <h2>Quick Shortcuts</h2>
                <div class="shortcut-list">
                    <a href="AddAppointment.php" class="shortcut-item"><i class="fas fa-calendar-plus"></i> New Appointment</a>
                    <a href="ScheduleManagement.php" class="shortcut-item"><i class="fas fa-calendar-check"></i> Schedule Availability</a>
                    <a href="PatientList.php" class="shortcut-item"><i class="fas fa-user-injured"></i> Patient Records</a>
                </div>
            </div>
        </div>
    </main>

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