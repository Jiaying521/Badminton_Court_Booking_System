<?php
// navbar.php
// Include this file after session_start() and after $role and $display_name are set.
?>

<nav class="nav-bar">
    <div class="nav-left">
        <button id="menu-toggle" class="menu-toggle">☰</button>
        <img src="Pictures/logo.png" alt="logo" class="logo">
        <span class="brand-name">
            <span class="text-primary">Smash</span>
            <span class="text-dark">Arena</span>
        </span>
    </div>

    <ul id="nav-menu" class="nav-links">
        <li><a href="SuperAdminDashboard.php">Dashboard</a></li>

        <?php if ($role === 'Superadmin'): ?>
            <li><a href="AdminManagement.php">Admin Management</a></li>
            <li><a href="ManageCourts.php">Court Management</a></li>
            <li><a href="ManageBookings.php">Manage Bookings</a></li>

        <?php elseif ($role === 'Admin'): ?>
            <li><a href="ManageCourts.php">Coach Management</a></li>
            <li><a href="ManageBookings.php">Manage Bookings</a></li>

            <li class="dropdown">
                <a href="#" class="drop-btn">More Options ▼</a>
                <ul class="submenu">
                    <li><a href="#">Player List</a></li>
                    <li><a href="#">Reports & Analytics</a></li>
                    <li><a href="#">Notifications</a></li>
                </ul>
            </li>

        <?php elseif ($role === 'Coach'): ?>
            <li><a href="ManageBookings.php">My Bookings</a></li>
            <li><a href="#">My Schedule</a></li>
            <li><a href="#">My Profile</a></li>
        <?php endif; ?>

        <li>
            <button id="logout-btn" class="logout-btn">
                Logout
            </button>
        </li>   
    </ul>

    <div class="user-info">
        <span id="welcome-text">
            Hello, <?php echo htmlspecialchars($display_name); ?>!
        </span>
    </div>
</nav>

<div id="overlay" class="overlay"></div>

<script>

    {
        const logoutBtn = document.getElementById("logout-btn");
        if (logoutBtn) {
            logoutBtn.addEventListener("click", function() {
                if (confirm("Are you sure you want to logout?")) {
                    window.location.href = "SuperAdminDashboard.php?action=logout";
                }
            });
        }
    }

</script>