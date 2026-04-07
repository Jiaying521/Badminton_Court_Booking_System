<?php
session_start();

/* --- Security Check --- */
if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    header("Location: LoginPage.php");
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];

/* --- Dashboard Statistics --- */

/* Total admins */
$db = mysqli_connect("localhost", "root", "", "care_connect");

/* Calculate total superadmins */
$query = mysqli_query($db, "SELECT COUNT(*) AS total_admins FROM admins WHERE role = 'superadmin'");
$data = mysqli_fetch_assoc($query);

/* Show real number of admins */
$total_admins = $data['total_admins'];

/* Doctors*/
$querey = mysqli_query($db, "SELECT COUNT(*) AS total_doctors FROM admins WHERE role = 'admin'");
$data = mysqli_fetch_assoc($querey);
$total_doctors = $data['total_doctors'];


$today_appointments = 15;
$pending_requests = 3;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareConnect - Dashboard</title>
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Main Style -->
    <link rel="stylesheet" href="SuperAdminDashboard.css">
</head>
<body>
    <!-- --- Top Navbar --- -->
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
                        <!-- Superadmin: Includes all Admin functions -->
                        <li><a href="DoctorManagement.php">Doctor Management</a></li>
                        <li><a href="ScheduleManagement.php">Schedule Management</a></li>
                        <li><a href="PatientList.php">Patient List</a></li>
                        <li><a href="AppointmentManagement.php">Appointment Management</a></li>
                        <li><a href="Reports.php">Reports & Analytics</a></li>
                        <li><a href="Notifications.php">Notifications</a></li>
                        <li><a href="ConflictManagement.php">Conflict Management</a></li>
                        <li><a href="AppointmentSettings.php">Appointment Settings</a></li>
                    <?php else: ?>
                        <!-- Admin: Remaining functions -->
                        <li><a href="ScheduleManagement.php">Schedule Management</a></li>
                        <li><a href="PatientList.php">Patient List</a></li>
                        <li><a href="Reports.php">Reports & Analytics</a></li>
                        <li><a href="Notifications.php">Notifications</a></li>
                        <li><a href="ConflictManagement.php">Conflict Management</a></li>
                        <li><a href="AppointmentSettings.php">Appointment Settings</a></li>
                    <?php endif; ?>
                    
                    <!-- Common items for both roles -->
                    <li><a href="Profile.php">Profile</a></li>
                </ul>
            </li>
            <li>
                <button id="logout-btn" class="logout-btn">Logout</button>
            </li>
        </ul>

        <div class="user-info">
             <span id="welcome-text">Hello, <?php echo htmlspecialchars($username); ?>!</span>
        </div>
    </nav>

    <!-- --- Mobile Sidebar Overlay --- -->
    <div id="overlay" class="overlay"></div>

    <!-- --- Main Content Area --- -->
    <main class="content">
        <header class="dashboard-header">
            <div class="welcome-section">
                <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
                <p>Status: <span class="status-online">● Online</span> | Role: <?php echo strtoupper($role); ?></p>
            </div>
            
            <?php if ($role === 'superadmin'): ?>
            <br> <!-- Manual line break kept as requested -->
            <div class="header-actions">
                <a href="AdminManagement.php" class="action-btn"><i class="fas fa-user-plus"></i> Add Admin</a>
            </div>
            <?php endif; ?>
        </header>

        <!-- --- Stats Cards Grid --- -->
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
                    <h3>Appointment</h3>
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

        <!-- --- Logs and Shortcuts Layout --- -->
        <div class="dashboard-layout">
            <div class="data-section">
                <h2>Recent System Logs</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="align-left">Action</th>
                            <th class="align-right">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="align-left">Admin Added</td>
                            <td class="align-right"><span class="badge success">Success</span></td>
                        </tr>
                        <tr>
                            <td class="align-left">Backup Completed</td>
                            <td class="align-right"><span class="badge success">Done</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="shortcuts-section">
                <h2>Quick Shortcuts</h2>
                <div class="shortcut-list">
                    <a href="Reports.php" class="shortcut-item"><i class="fas fa-chart-line"></i> View Statistics</a>
                    <a href="PatientList.php" class="shortcut-item"><i class="fas fa-user-injured"></i> Patient Records</a>
                </div>
            </div>
        </div>
    </main>

    <script src="SuperAdminDashboard.js"></script>
</body>
</html>