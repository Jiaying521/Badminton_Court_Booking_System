<<<<<<< HEAD
<?php
=======
﻿<?php
>>>>>>> 4f7d89e2ab6fd34035a3f9f62eab69b85313d81e
// Shared top navigation bar for all admin / superadmin / coach pages.
// Pages that include this file must set $base_path before include:
//   - all pages now live one level deep, so always use $ase_path = '../';


// This way the links keep working no matter which folder the page sits in.
if (!isset($base_path)) {
    $base_path = '';
}
?>

<nav class="nav-bar">
    <div class="nav-left">
        <button id="menu-toggle" class="menu-toggle">☰</button>
        <a href="<?php echo $base_path; ?>Dashboard/Dashboard.php" style="display:flex; align-items:center; gap:8px; text-decoration:none;">
            <img src="<?php echo $base_path; ?>../Pictures/Admin_Module/logo.png" alt="logo" class="logo">
            <span class="brand-name">
                <span class="text-primary">Smash</span>
                <span class="text-dark">Arena</span>
            </span>
        </a>
    </div>

    <ul id="nav-menu" class="nav-links">
        <li><a href="<?php echo $base_path; ?>Dashboard/Dashboard.php">Dashboard</a></li>

        <?php if ($role === 'Superadmin'): ?>
            <li><a href="<?php echo $base_path; ?>Superadmin/AdminManagement.php">Admin Management</a></li>
<<<<<<< HEAD
            <li><a href="<?php echo $base_path; ?>Courts_Management/ManageCourts.php">Court Management</a></li>
            <li><a href="<?php echo $base_path; ?>System_Settings/SystemSettings.php">System Settings</a></li>
=======
            <li><a href="<?php echo $base_path; ?>ManageCourts.php">Court Management</a></li>
            <li><a href="<?php echo $base_path; ?>SystemSettings.php">System Settings</a></li>
>>>>>>> 4f7d89e2ab6fd34035a3f9f62eab69b85313d81e

            <li class="dropdown">
                <a href="#" class="drop-btn">More Options ▼</a>
                <ul class="submenu">
<<<<<<< HEAD
                    <li><a href="<?php echo $base_path; ?>Coaches_Management/ManageCoaches.php">Manage Coach</a></li>
                    <li><a href="<?php echo $base_path; ?>Bookings_Management/ManageBookings.php">Manage Bookings</a></li>
                    <li><a href="<?php echo $base_path; ?>Customers_Management/ManageCustomers.php">Customer Management</a></li>
=======
                    <li><a href="<?php echo $base_path; ?>ManageCoaches.php">Manage Coach</a></li>
                    <li><a href="<?php echo $base_path; ?>ManageBookings.php">Manage Bookings</a></li>
                    <li><a href="<?php echo $base_path; ?>ManageCustomers.php">Customer Management</a></li>
>>>>>>> 4f7d89e2ab6fd34035a3f9f62eab69b85313d81e
                    <li><a href="#">Reports & Analytics</a></li>
                </ul>
            </li>

        <?php elseif ($role === 'Admin'): ?>
<<<<<<< HEAD
            <li><a href="<?php echo $base_path; ?>Courts_Management/ManageCourts.php">Court Management</a></li>
            <li><a href="<?php echo $base_path; ?>Bookings_Management/ManageBookings.php">Manage Bookings</a></li>
            <li><a href="<?php echo $base_path; ?>System_Settings/SystemSettings.php">System Settings</a></li>
=======
            <li><a href="<?php echo $base_path; ?>ManageCourts.php">Court Management</a></li>
            <li><a href="<?php echo $base_path; ?>ManageBookings.php">Manage Bookings</a></li>
            <li><a href="<?php echo $base_path; ?>SystemSettings.php">System Settings</a></li>
>>>>>>> 4f7d89e2ab6fd34035a3f9f62eab69b85313d81e

            <li class="dropdown">
                <a href="#" class="drop-btn">More Options ▼</a>
                <ul class="submenu">
<<<<<<< HEAD
                    <li><a href="<?php echo $base_path; ?>Coaches_Management/ManageCoaches.php">Manage Coach</a></li>
                    <li><a href="<?php echo $base_path; ?>Customers_Management/ManageCustomers.php">Customer Management</a></li>
=======
                    <li><a href="<?php echo $base_path; ?>ManageCoaches.php">Manage Coach</a></li>
                    <li><a href="<?php echo $base_path; ?>ManageCustomers.php">Customer Management</a></li>
>>>>>>> 4f7d89e2ab6fd34035a3f9f62eab69b85313d81e
                    <li><a href="#">Reports & Analytics</a></li>
                </ul>
            </li>

        <?php elseif ($role === 'Coach'): ?>
<<<<<<< HEAD
            <li><a href="<?php echo $base_path; ?>Bookings_Management/ManageBookings.php">My Bookings</a></li>
=======
            <li><a href="<?php echo $base_path; ?>ManageBookings.php">My Bookings</a></li>
>>>>>>> 4f7d89e2ab6fd34035a3f9f62eab69b85313d81e
            <li><a href="<?php echo $base_path; ?>Coach/CoachProfile.php">My Profile</a></li>
        <?php endif; ?>

        <li>
            <button id="logout-btn" class="logout-btn">
                Logout
            </button>
        </li>
    </ul>

    <div class="nav-right">
        <!-- Notification Bell -->
        <div class="notif-wrapper">
            <button id="notif-btn" class="notif-btn" title="Notifications">
                <i class="fas fa-bell"></i>
                <span id="notif-badge" class="notif-badge" style="display:none">0</span>
            </button>
            <div id="notif-dropdown" class="notif-dropdown">
                <div class="notif-header">
                    <span class="notif-header-title"><i class="fas fa-bell"></i> Notifications</span>
                    <button id="mark-all-read" class="mark-all-btn">Mark all read</button>
                </div>
                <div id="notif-list" class="notif-list">
                    <div class="notif-empty">No notifications</div>
                </div>
            </div>
        </div>

        <div class="user-info">
            <span id="welcome-text">
                Hello, <?php echo htmlspecialchars($display_name); ?>!
            </span>
        </div>
    </div>
</nav>

<div id="overlay" class="overlay"></div>

<script>
    // Tell the JS code where the project root is so fetch() URLs work
    // from pages inside subfolders (Superadmin/, Coach/) too.
    const NAV_BASE = "<?php echo $base_path; ?>";
</script>

<script>
    // Mobile menu toggle
    {
        const toggle  = document.getElementById('menu-toggle');
        const navMenu = document.getElementById('nav-menu');
        const overlay = document.getElementById('overlay');

        if (toggle && navMenu) {
            toggle.addEventListener('click', function () {
                navMenu.classList.toggle('active');
                if (overlay) overlay.classList.toggle('active');
            });
            if (overlay) {
                overlay.addEventListener('click', function () {
                    navMenu.classList.remove('active');
                    overlay.classList.remove('active');
                });
            }
        }

        const logoutBtn = document.getElementById("logout-btn");
        if (logoutBtn) {
            logoutBtn.addEventListener("click", function() {
                if (confirm("Are you sure you want to logout?")) {
                    window.location.href = NAV_BASE + "Dashboard/Dashboard.php?action=logout";
                }
            });
        }
    }

    // Notification system
    (function () {
        const btn       = document.getElementById('notif-btn');
        const dropdown  = document.getElementById('notif-dropdown');
        const badge     = document.getElementById('notif-badge');
        const list      = document.getElementById('notif-list');
        const markAllBtn = document.getElementById('mark-all-read');
        let isOpen      = false;

        function timeAgo(dateStr) {
            const diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
            if (diff < 60)    return 'just now';
            if (diff < 3600)  return Math.floor(diff / 60) + 'm ago';
            if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
            return Math.floor(diff / 86400) + 'd ago';
        }

        function typeIcon(type) {
            const map = {
                confirmed:   { fa: 'fa-check',         bg: '#dcfce7', color: '#16a34a' },
                cancelled:   { fa: 'fa-xmark',         bg: '#fee2e2', color: '#dc2626' },
                new_booking: { fa: 'fa-calendar-plus', bg: '#dbeafe', color: '#2563eb' },
                reminder:    { fa: 'fa-clock',         bg: '#fef3c7', color: '#d97706' }
            };
            const t = map[type] || { fa: 'fa-bell', bg: '#f1f5f9', color: '#64748b' };
            return `<span class="notif-type-icon" style="background:${t.bg};color:${t.color}"><i class="fas ${t.fa}"></i></span>`;
        }

        function render(notifications, unreadCount) {
            if (unreadCount > 0) {
                badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }

            if (!notifications.length) {
                list.innerHTML = `
                    <div class="notif-empty">
                        <i class="fas fa-bell-slash" style="font-size:28px;color:#cbd5e1;margin-bottom:10px;display:block;"></i>
                        No notifications yet
                    </div>`;
                return;
            }

            list.innerHTML = notifications.map(n => `
                <div class="notif-item${n.is_read == 0 ? ' unread' : ''}" data-id="${n.id}">
                    ${typeIcon(n.type)}
                    <div class="notif-body">
                        <div class="notif-title">${n.title}</div>
                        <div class="notif-msg">${n.message}</div>
                        <div class="notif-time"><i class="fas fa-clock" style="font-size:10px;margin-right:3px;"></i>${timeAgo(n.created_at)}</div>
                    </div>
                    ${n.is_read == 0 ? '<span class="notif-dot"></span>' : ''}
                </div>
            `).join('');

            list.querySelectorAll('.notif-item').forEach(item => {
                item.addEventListener('click', function () {
                    markRead(parseInt(this.dataset.id), this);
                });
            });
        }

        function fetchNotifications() {
            fetch(NAV_BASE + 'api/get_notifications.php')
                .then(r => r.json())
                .then(data => { if (data.success) render(data.notifications, data.unread_count); })
                .catch(() => {});
        }

        function markRead(id, element) {
            fetch(NAV_BASE + 'api/mark_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            }).then(() => {
                if (element) {
                    element.classList.remove('unread');
                    const dot = element.querySelector('.notif-dot');
                    if (dot) dot.remove();
                }
                fetchNotifications();
            }).catch(() => {});
        }

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            isOpen = !isOpen;
            dropdown.classList.toggle('active', isOpen);
            if (isOpen) fetchNotifications();
        });

        document.addEventListener('click', function (e) {
            if (isOpen && !dropdown.contains(e.target) && e.target !== btn && !btn.contains(e.target)) {
                isOpen = false;
                dropdown.classList.remove('active');
            }
        });

        markAllBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            fetch(NAV_BASE + 'api/mark_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: 0 })
            }).then(() => fetchNotifications()).catch(() => {});
        });

        fetchNotifications();
        setInterval(fetchNotifications, 60000);
    })();
</script>
