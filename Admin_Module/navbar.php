<?php
// navbar.php
// Include this file after session_start() and after $role and $display_name are set.
?>

<nav class="nav-bar">
    <div class="nav-left">
        <button id="menu-toggle" class="menu-toggle">☰</button>
        <img src="Pictures/logo.png" alt="logo" class="logo">
        <span class="brand-name">
            <span class="text-primary">Badminton</span>
            <span class="text-dark">Hub</span>
        </span>
    </div>

    <ul id="nav-menu" class="nav-links">
        <li><a href="SuperAdminDashboard.php">Dashboard</a></li>

        <?php if ($role === 'Superadmin'): ?>
            <li><a href="AdminManagement.php">Admin Management</a></li>
            <li><a href="ManageCourts.php">Court Management</a></li>
            <li><a href="#">System Settings</a></li>

        <?php elseif ($role === 'Admin'): ?>
            <li><a href="CoachManagement.php">Coach Management</a></li>
            <li><a href="BookingManagement.php">Bookings</a></li>
            <li><a href="ManageCourts.php">Court Management</a></li>
            <li><a href="ScheduleManagement.php">Court Schedule</a></li>
            <li class="dropdown">
                <a href="#" class="drop-btn">More Options ▼</a>
                <ul class="submenu">
                    <li><a href="PlayerList.php">Player List</a></li>
                    <li><a href="Reports.php">Reports & Analytics</a></li>
                    <li><a href="Notifications.php">Notifications</a></li>
                    <li><a href="ConflictManagement.php">Conflict Management</a></li>
                    <li><a href="Settings.php">Appointment Settings</a></li>
                </ul>
            </li>

        <?php elseif ($role === 'Coach'): ?>
            <li><a href="MyBookings.php">My Bookings</a></li>
            <li><a href="MySchedule.php">My Schedule</a></li>
            <li><a href="MyPlayers.php">My Players</a></li>
            <li><a href="Profile.php">Profile</a></li>
        <?php endif; ?>

        <li><button id="logout-btn" class="logout-btn" onclick="location.href='SuperAdminDashboard.php?action=logout'">Logout</button></li>
    </ul>

    <div class="user-info">
        <span id="welcome-text">Hello, <?php echo htmlspecialchars($display_name); ?>!</span>
    </div>
</nav>

<div id="overlay" class="overlay"></div>