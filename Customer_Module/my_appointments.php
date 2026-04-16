<?php
// Customer_Module/my_appointments.php
require_once __DIR__ . '/../config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: homepage.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// 处理取消操作
if (isset($_GET['cancel_id'])) {
    $cancel_id = (int)$_GET['cancel_id'];
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'Cancelled' WHERE id = ? AND user_id = ? AND status NOT IN ('Completed', 'Cancelled')");
    $stmt->execute([$cancel_id, $user_id]);
    header('Location: my_appointments.php');
    exit;
}

// 获取用户信息
$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// 获取用户的预约列表，关联医生信息
$stmt = $pdo->prepare("
    SELECT a.*, ad.username as doctor_name, ad.specialisation 
    FROM appointments a
    JOIN admins ad ON a.doctor_id = ad.id
    WHERE a.user_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$stmt->execute([$user_id]);
$appointments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments | CareConnect</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(145deg, #f6fafd 0%, #eef2f8 100%); color: #1a2c3e; scroll-behavior: smooth; }
        /* 导航栏玻璃效果（与 dashboard 一致） */
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 1rem 5%; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03); position: sticky; top: 0; z-index: 100; border-bottom: 1px solid rgba(0, 153, 255, 0.1); flex-wrap: wrap; }
        .logo { font-size: 1.9rem; font-weight: 800; background: linear-gradient(135deg, #0099ff, #2c6e9e); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .logo span { background: none; color: #2c3e66; }
        .nav-links { display: flex; gap: 2rem; align-items: center; flex-wrap: wrap; }
        .nav-links a { text-decoration: none; color: #2c3e66; font-weight: 500; transition: 0.2s; }
        .nav-links a:hover, .nav-links a.active { color: #0099ff; }
        .btn-outline { background: transparent; border: 1.5px solid #0099ff; padding: 0.4rem 1.2rem; border-radius: 40px; color: #0099ff; cursor: pointer; font-weight: 600; transition: 0.2s; }
        .btn-outline:hover { background: #0099ff; color: white; transform: translateY(-2px); }
        .dashboard-container { max-width: 1400px; margin: 2rem auto; padding: 0 5%; }
        /* 欢迎横幅 */
        .welcome-banner { background: linear-gradient(135deg, #0099ff, #2c3e66); color: white; padding: 2rem; border-radius: 32px; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .welcome-banner h1 { font-size: 2rem; margin-bottom: 0.5rem; }
        .action-buttons { display: flex; gap: 1rem; flex-wrap: wrap; }
        .action-btn { background: white; color: #0099ff; border: none; padding: 0.8rem 1.8rem; border-radius: 40px; font-weight: bold; cursor: pointer; transition: 0.2s; text-decoration: none; display: inline-block; font-size: 0.9rem; }
        .action-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        /* 搜索栏（类似筛选区域） */
        .search-section { background: white; padding: 1.5rem; border-radius: 28px; margin-bottom: 2rem; box-shadow: 0 8px 24px rgba(0,0,0,0.05); display: flex; justify-content: flex-end; }
        .search-box { display: flex; align-items: center; gap: 0.5rem; background: #f8fafc; padding: 0.3rem 0.8rem; border-radius: 60px; border: 1px solid #e2e8f0; }
        .search-box input { border: none; padding: 0.5rem; font-size: 0.9rem; outline: none; background: transparent; min-width: 200px; }
        .search-box button { background: #0099ff; border: none; color: white; padding: 0.3rem 0.8rem; border-radius: 40px; cursor: pointer; }
        /* 预约卡片网格 */
        .appointments-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem; margin-top: 1rem; }
        .appointment-card { background: white; border-radius: 24px; padding: 1.5rem; box-shadow: 0 8px 20px rgba(0,0,0,0.05); transition: 0.3s; border-bottom: 3px solid #0099ff; }
        .appointment-card:hover { transform: translateY(-5px); box-shadow: 0 16px 32px rgba(0,153,255,0.1); }
        .card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem; }
        .doctor-info { display: flex; align-items: center; gap: 0.8rem; }
        .doctor-avatar { width: 50px; height: 50px; border-radius: 50%; background: #eef2ff; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: bold; color: #0099ff; }
        .doctor-name { font-weight: 700; font-size: 1.2rem; color: #1e2a3e; }
        .doctor-spec { font-size: 0.8rem; color: #5b6e8c; }
        .status-badge { padding: 0.3rem 0.8rem; border-radius: 60px; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; }
        .status-Pending { background: #fef3c7; color: #b45309; }
        .status-Confirmed { background: #dbeafe; color: #1e40af; }
        .status-Completed { background: #d1fae5; color: #065f46; }
        .status-Cancelled { background: #fee2e2; color: #991b1b; }
        .appointment-details { margin: 1rem 0; padding: 0.8rem 0; border-top: 1px solid #eef2ff; border-bottom: 1px solid #eef2ff; }
        .detail-row { display: flex; align-items: center; gap: 0.8rem; margin-bottom: 0.6rem; font-size: 0.9rem; }
        .detail-icon { width: 24px; color: #0099ff; }
        .notes { background: #f8fafc; padding: 0.6rem; border-radius: 16px; margin-top: 0.8rem; font-size: 0.8rem; color: #5b6e8c; }
        .card-actions { margin-top: 1rem; display: flex; justify-content: flex-end; }
        .btn-cancel { background: #ef4444; color: white; border: none; padding: 0.4rem 1rem; border-radius: 40px; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: 0.2s; }
        .btn-cancel:hover { background: #dc2626; transform: scale(1.02); }
        .no-results { text-align: center; padding: 3rem; background: white; border-radius: 28px; color: #5b6e8c; }
        .main-footer { background: #0f212e; color: #cbd5e1; padding: 2rem 5%; margin-top: 3rem; text-align: center; border-radius: 24px 24px 0 0; }
        .success-message { background: #d1fae5; color: #065f46; padding: 0.8rem 1.2rem; border-radius: 60px; margin-bottom: 1.5rem; display: inline-block; }
        @media (max-width: 768px) {
            .navbar { flex-direction: column; gap: 1rem; }
            .welcome-banner { flex-direction: column; text-align: center; gap: 1rem; }
            .search-section { justify-content: center; }
            .appointments-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="logo">Care<span>Connect</span></div>
    <div class="nav-links">
        <a href="dashboard.php">Home</a>
        <a href="my_appointments.php" class="active">My Appointments</a>
        <a href="#">About Us</a>
        <span style="color:#0099ff;">Hi, <?php echo htmlspecialchars($user['name'] ?? explode('@', $user['email'])[0]); ?></span>
        <button class="btn-outline" id="logoutNavBtn">Logout</button>
    </div>
</nav>

<div class="dashboard-container">
    <!-- 欢迎横幅 -->
    <div class="welcome-banner">
        <div>
            <h1>📋 My Appointments</h1>
            <p>View and manage your upcoming visits</p>
        </div>
        <div class="action-buttons">
            <a href="book_appointment.php" class="action-btn">📅 Make Appointment</a>
            <a href="dashboard.php" class="action-btn">🏠 Back to Dashboard</a>
        </div>
    </div>

    <!-- 搜索栏 -->
    <div class="search-section">
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Search by doctor name...">
            <button id="searchBtn">🔍</button>
        </div>
    </div>

    <!-- 成功提示 -->
    <?php if (isset($_GET['success'])): ?>
        <div class="success-message">✅ Appointment booked successfully!</div>
    <?php endif; ?>

    <!-- 预约列表 -->
    <?php if (count($appointments) > 0): ?>
        <div class="appointments-grid" id="appointmentsGrid">
            <?php foreach ($appointments as $app): ?>
                <div class="appointment-card" data-doctor="<?= strtolower(htmlspecialchars($app['doctor_name'])) ?>">
                    <div class="card-header">
                        <div class="doctor-info">
                            <div class="doctor-avatar">
                                <?= strtoupper(substr($app['doctor_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="doctor-name"><?= htmlspecialchars($app['doctor_name']) ?></div>
                                <div class="doctor-spec"><?= htmlspecialchars($app['specialisation']) ?></div>
                            </div>
                        </div>
                        <span class="status-badge status-<?= $app['status'] ?>"><?= $app['status'] ?></span>
                    </div>
                    <div class="appointment-details">
                        <div class="detail-row">
                            <span class="detail-icon">📅</span>
                            <span><?= date('l, d F Y', strtotime($app['appointment_date'])) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-icon">⏰</span>
                            <span><?= date('h:i A', strtotime($app['appointment_time'])) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-icon">🩺</span>
                            <span><?= htmlspecialchars($app['appointment_type']) ?></span>
                        </div>
                        <?php if (!empty($app['notes'])): ?>
                            <div class="notes">
                                📝 Notes: <?= nl2br(htmlspecialchars($app['notes'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-actions">
                        <?php if (!in_array($app['status'], ['Cancelled', 'Completed'])): ?>
                            <a href="?cancel_id=<?= $app['id'] ?>" onclick="return confirm('Are you sure you want to cancel this appointment?')" class="btn-cancel">Cancel Appointment</a>
                        <?php else: ?>
                            <span style="font-size:0.8rem; color:#94a3b8;">—</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-results">
            <div style="font-size: 4rem; margin-bottom: 1rem;">📭</div>
            <h3>No appointments yet</h3>
            <p>Book your first appointment with one of our doctors.</p>
            <a href="book_appointment.php" class="action-btn" style="display: inline-block; margin-top: 1rem;">Book Now →</a>
        </div>
    <?php endif; ?>
</div>

<footer class="main-footer">
    <p>© 2025 CareConnect | Your health, our priority. | <a href="#" style="color:#0099ff;">Privacy Policy</a></p>
</footer>

<script>
    // 实时搜索功能
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const cards = document.querySelectorAll('.appointment-card');

    function filterCards() {
        const query = searchInput.value.toLowerCase().trim();
        cards.forEach(card => {
            const doctorName = card.getAttribute('data-doctor') || '';
            if (doctorName.includes(query) || query === '') {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }

    searchInput.addEventListener('keyup', filterCards);
    searchBtn.addEventListener('click', filterCards);

    // 退出登录
    const logoutBtn = document.getElementById('logoutNavBtn');
    if(logoutBtn) {
        logoutBtn.onclick = async () => {
            await fetch('/Clinic_Booking_System/logout.php', { method: 'POST' });
            window.location.href = '/Clinic_Booking_System/Customer_Module/homepage.php';
        };
    }
</script>
</body>
</html>