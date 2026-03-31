<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard</title>
    <link rel="stylesheet" href="SuperAdminDashboard.css">
</head>
<body>
    <!-- Top Navigation Bar -->
    <nav class="nav-bar">
        <div class="nav-left">
             <button id="menu-toggle" class="menu-toggle">☰</button>
             <img src="https://i.ibb.co/5gWCTmy0/image.png" alt="logo" class="logo">
             <span class="brand-name">Clinic Dashboard</span>    
        </div>

        <!-- Navigation Menu -->
        <ul id="nav-menu" class="nav-links">
            <li><a href="#">Dashboard</a></li>
            <li><a href="#">Admin Management</a></li>
            <li><a href="#">System Settings</a></li>
            
            <!-- More Options Dropdown (Now inside the UL) -->
            <li class="dropdown">
                <a href="#" class="drop-btn">More Options ▼</a>
                <ul class="submenu">
                    <li><a href="#">Doctor Management</a></li>
                    <li><a href="#">Schedule Management</a></li>
                    <li><a href="#">Patient List</a></li>
                    <li><a href="#">Appointment Management</a></li>
                    <li><a href="#">Reports & Analytics</a></li>
                    <li><a href="#">Notifications</a></li>
                    <li><a href="#">Conflict Management</a></li>
                    <li><a href="#">Appointment Settings</a></li>
                    <li><a href="#">Profile</a></li>
                </ul>
            </li>

            <li><button id="logout-btn" class="logout-btn">Logout</button></li>
        </ul>

        <!-- Welcome Message -->
        <div class="user-info">
            <span id="welcome-text"></span>
        </div>
    </nav>

    <!-- Background Overlay -->
    <div id="overlay" class="overlay"></div>

    <main class="content">
        <h1>Welcome to the Super Admin Dashboard</h1>
    </main>

    <script src="SuperAdminDashboard.js"></script>
</body>
</html>