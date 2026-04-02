<?php
// Customer_Module/dashboard.php
require_once __DIR__ . '/../config.php'; // 注意这行已经包含了会话启动

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header('Location: homepage.php');
    exit;
}

// 获取当前登录用户的信息
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id, email, nric, phone, created_at FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: homepage.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - CareConnect</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background-color: #f0f8ff;
            color: #1e2a3e;
        }
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 5%;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: #0099ff;
        }
        .logo span {
            color: #2c3e66;
        }
        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        .nav-links a {
            text-decoration: none;
            color: #2c3e66;
            font-weight: 500;
            transition: 0.3s;
        }
        .nav-links a:hover {
            color: #0099ff;
        }
        .btn-outline {
            background: transparent;
            border: 1.5px solid #0099ff;
            padding: 0.4rem 1.2rem;
            border-radius: 30px;
            color: #0099ff;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-outline:hover {
            background: #0099ff;
            color: white;
        }
        .btn-solid {
            background: #0099ff;
            border: none;
            padding: 0.4rem 1.2rem;
            border-radius: 30px;
            color: white;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-solid:hover {
            background: #0077cc;
        }
        .dashboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .welcome-card {
            background: linear-gradient(135deg, #0099ff 0%, #2c7ab1 100%);
            color: white;
            padding: 2rem;
            border-radius: 28px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 20px rgba(0,153,255,0.2);
        }
        .welcome-card h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .info-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border-left: 5px solid #0099ff;
        }
        .info-card h3 {
            color: #0099ff;
            margin-bottom: 1rem;
        }
        .info-card p {
            margin: 0.5rem 0;
            color: #4a627a;
        }
        .info-card .label {
            font-weight: 600;
            color: #1e2a3e;
            display: inline-block;
            width: 110px;
        }
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin: 2rem 0;
        }
        .action-btn {
            background: #0099ff;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 40px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.2s;
        }
        .action-btn.outline {
            background: white;
            border: 1px solid #0099ff;
            color: #0099ff;
        }
        .action-btn.outline:hover {
            background: #0099ff;
            color: white;
        }
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0,153,255,0.3);
        }
        .appointments-section {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-top: 1rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .appointments-section h3 {
            color: #0099ff;
            margin-bottom: 1rem;
        }
        .empty-msg {
            text-align: center;
            color: #94a3b8;
            padding: 2rem;
        }
        footer {
            background: #1e2a3e;
            color: #cbd5e1;
            text-align: center;
            padding: 1.5rem;
            margin-top: 3rem;
        }
        @media (max-width: 768px) {
            .dashboard-container { padding: 0 1rem; }
            .info-grid { grid-template-columns: 1fr; }
            .action-buttons { flex-direction: column; }
        }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="logo">Care<span>Connect</span></div>
    <div class="nav-links">
        <a href="homepage.php">Home</a>
        <a href="#">About Us</a>
        <span style="color:#0099ff;">Hi, <?php echo htmlspecialchars(explode('@', $user['email'])[0]); ?></span>
        <button class="btn-outline" id="logoutBtn">Logout</button>
    </div>
</nav>

<div class="dashboard-container">
    <div class="welcome-card">
        <h1>Welcome back, <?php echo htmlspecialchars(explode('@', $user['email'])[0]); ?>! 👋</h1>
        <p>Manage your appointments, update profile, and access health records.</p>
    </div>

    <div class="info-grid">
        <div class="info-card">
            <h3>📋 Personal Information</h3>
            <p><span class="label">Email:</span> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><span class="label">NRIC/Passport:</span> <?php echo htmlspecialchars($user['nric']); ?></p>
            <p><span class="label">Phone:</span> <?php echo htmlspecialchars($user['phone']); ?></p>
            <p><span class="label">Member since:</span> <?php echo date('d M Y', strtotime($user['created_at'])); ?></p>
        </div>
        <div class="info-card">
            <h3>🏥 Quick Actions</h3>
            <p>🔹 Book a new consultation</p>
            <p>🔹 View your medical history</p>
            <p>🔹 Update profile details</p>
            <p>🔹 Change password</p>
        </div>
    </div>

    <div class="action-buttons">
        <button class="action-btn" id="bookAppointmentBtn">📅 Book New Appointment</button>
        <button class="action-btn outline" id="viewAppointmentsBtn">📋 My Appointments</button>
        <button class="action-btn outline" id="changePasswordBtn">🔐 Change Password</button>
        <button class="action-btn outline" id="editProfileBtn">✏️ Edit Profile</button>
    </div>

    <div class="appointments-section" id="appointmentsSection">
        <h3>📌 Your Upcoming Appointments</h3>
        <div id="appointmentsList">
            <div class="empty-msg">You have no upcoming appointments. Click "Book New Appointment" to schedule.</div>
        </div>
    </div>
</div>

<footer>
    <p>© 2025 CareConnect | Your health, our priority.</p>
</footer>

<script>
    document.getElementById('logoutBtn').addEventListener('click', () => {
        fetch('../logout.php', { method: 'POST' })
            .finally(() => { window.location.href = 'homepage.php'; });
    });
    document.getElementById('bookAppointmentBtn').addEventListener('click', () => {
        window.location.href = 'book_appointment.php';
    });
    document.getElementById('viewAppointmentsBtn').addEventListener('click', () => {
        window.location.href = 'my_appointments.php';
    });
    document.getElementById('changePasswordBtn').addEventListener('click', () => {
        let newPwd = prompt("Enter new password (min 6 characters):");
        if (newPwd && newPwd.length >= 6) {
            fetch('../change_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ password: newPwd })
            })
            .then(res => res.json())
            .then(data => alert(data.message))
            .catch(err => alert('Error changing password'));
        } else if (newPwd) {
            alert('Password must be at least 6 characters.');
        }
    });
    document.getElementById('editProfileBtn').addEventListener('click', () => {
        window.location.href = 'edit_profile.php';
    });
</script>
</body>
</html>