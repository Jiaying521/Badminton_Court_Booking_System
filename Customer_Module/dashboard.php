<?php
// Customer_Module/dashboard.php
require_once __DIR__ . '/../config.php';

// 未登录用户跳转到首页
if (!isset($_SESSION['user_id'])) {
    header('Location: homepage.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id, email, name, nric, phone FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// 获取筛选参数
$specialisation = $_GET['specialisation'] ?? '';
$gender = $_GET['gender'] ?? '';
$language = $_GET['language'] ?? '';
$doctor_name = $_GET['doctor_name'] ?? '';

// 查询医生（is_doctor = 1）
$sql = "SELECT * FROM admins WHERE is_doctor = 1";
$params = [];
if (!empty($specialisation)) {
    $sql .= " AND specialisation = ?";
    $params[] = $specialisation;
}
if (!empty($gender)) {
    $sql .= " AND gender = ?";
    $params[] = $gender;
}
if (!empty($language)) {
    $sql .= " AND FIND_IN_SET(?, language)";
    $params[] = $language;
}
if (!empty($doctor_name)) {
    $sql .= " AND username LIKE ?";
    $params[] = "%$doctor_name%";
}
$sql .= " ORDER BY username";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$doctors = $stmt->fetchAll();

// 获取所有专科列表
$spec_sql = "SELECT DISTINCT specialisation FROM admins WHERE is_doctor = 1 AND specialisation IS NOT NULL AND specialisation != ''";
$spec_stmt = $pdo->query($spec_sql);
$specialisations = $spec_stmt->fetchAll(PDO::FETCH_COLUMN);

// 获取所有语言列表
$lang_sql = "SELECT DISTINCT language FROM admins WHERE is_doctor = 1 AND language IS NOT NULL";
$lang_stmt = $pdo->query($lang_sql);
$lang_rows = $lang_stmt->fetchAll(PDO::FETCH_COLUMN);
$all_languages = [];
foreach ($lang_rows as $lang_str) {
    $langs = explode(',', $lang_str);
    foreach ($langs as $l) {
        $l = trim($l);
        if (!in_array($l, $all_languages)) $all_languages[] = $l;
    }
}
sort($all_languages);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareConnect | Patient Dashboard</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(145deg, #f6fafd 0%, #eef2f8 100%); color: #1a2c3e; scroll-behavior: smooth; }
        /* 导航栏玻璃效果 */
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
        /* 筛选表单 */
        .filter-section { background: white; padding: 1.5rem; border-radius: 28px; margin-bottom: 2rem; box-shadow: 0 8px 24px rgba(0,0,0,0.05); }
        .filter-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap: 1rem; align-items: end; }
        .filter-group { display: flex; flex-direction: column; gap: 0.3rem; }
        .filter-group label { font-weight: 600; color: #2c3e66; }
        .filter-group select, .filter-group input { padding: 0.6rem; border: 1px solid #ccc; border-radius: 40px; font-size: 0.9rem; outline: none; }
        .filter-group select:focus, .filter-group input:focus { border-color: #0099ff; }
        .search-btn, .reset-btn { background: #0099ff; color: white; border: none; padding: 0.6rem 1.2rem; border-radius: 40px; cursor: pointer; font-weight: 600; height: 42px; }
        .reset-btn { background: #e2e8f0; color: #2c3e66; }
        /* 医生卡片网格 */
        .doctors-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px,1fr)); gap: 1.5rem; margin-top: 1rem; }
        .doctor-card { background: white; border-radius: 24px; padding: 1.5rem; box-shadow: 0 8px 20px rgba(0,0,0,0.05); transition: 0.3s; border-bottom: 3px solid #0099ff; text-align: center; }
        .doctor-card:hover { transform: translateY(-5px); box-shadow: 0 16px 32px rgba(0,153,255,0.1); }
        .doctor-avatar { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin: 0 auto 1rem auto; display: block; border: 3px solid #0099ff; background: #eef2ff; }
        .doctor-name { font-size: 1.4rem; font-weight: bold; color: #0099ff; margin-bottom: 0.5rem; }
        .doctor-spec { color: #2c3e66; margin-bottom: 0.5rem; font-weight: 500; }
        .doctor-details { color: #5b6e8c; font-size: 0.9rem; margin-bottom: 0.3rem; }
        .book-btn { background: #0099ff; color: white; border: none; padding: 0.6rem 1rem; border-radius: 40px; cursor: pointer; font-weight: 600; margin-top: 1rem; width: 100%; transition: 0.2s; }
        .book-btn:hover { background: #0077cc; }
        .no-results { text-align: center; padding: 3rem; background: white; border-radius: 28px; color: #5b6e8c; }
        /* 服务分类区域 */
        .services-section { margin-top: 3rem; padding: 2rem 0; }
        .services-section h2 { font-size: 2.4rem; font-weight: 700; text-align: center; margin-bottom: 0.5rem; color: #1e2a3e; }
        .services-sub { text-align: center; color: #5b6e8c; margin-bottom: 2rem; font-size: 1rem; }
        .service-category { margin-bottom: 2.5rem; }
        .service-category h3 { font-size: 1.6rem; font-weight: 600; margin-bottom: 1.2rem; border-left: 6px solid #0099ff; padding-left: 1rem; color: #1e2a3e; }
        .service-cards { display: flex; flex-wrap: wrap; gap: 1.5rem; justify-content: center; }
        .card { background: white; border-radius: 24px; padding: 1.5rem; width: 240px; box-shadow: 0 8px 20px rgba(0,0,0,0.05); transition: 0.3s; text-align: center; border: 1px solid rgba(0, 153, 255, 0.1); }
        .card:hover { transform: translateY(-6px); box-shadow: 0 16px 32px rgba(0,153,255,0.12); border-color: rgba(0,153,255,0.3); }
        .card-icon { font-size: 2.8rem; margin-bottom: 0.8rem; background: #eef7ff; width: 70px; height: 70px; display: flex; align-items: center; justify-content: center; border-radius: 60px; margin-left: auto; margin-right: auto; }
        .card h4 { font-size: 1.2rem; font-weight: 600; color: #0099ff; margin-bottom: 0.5rem; }
        .card p { font-size: 0.85rem; color: #5b6e8c; line-height: 1.4; }
        /* 页脚 */
        .main-footer { background: #0f212e; color: #cbd5e1; padding: 2rem 5%; margin-top: 3rem; text-align: center; border-radius: 24px 24px 0 0; }
        @media (max-width: 768px) {
            .navbar { flex-direction: column; gap: 1rem; }
            .welcome-banner { flex-direction: column; text-align: center; gap: 1rem; }
            .filter-form { grid-template-columns: 1fr; }
            .doctors-grid { grid-template-columns: 1fr; }
            .service-cards { justify-content: center; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="logo">Care<span>Connect</span></div>
    <div class="nav-links">
        <a href="dashboard.php" class="active">Home</a>
        <a href="my_appointments.php">My Appointments</a>
        <a href="#">About Us</a>
        <span style="color:#0099ff;">Hi, <?php echo htmlspecialchars($user['name'] ?? explode('@', $user['email'])[0]); ?></span>
        <button class="btn-outline" id="logoutNavBtn">Logout</button>
    </div>
</nav>

<div class="dashboard-container">
    <!-- 欢迎横幅 -->
    <div class="welcome-banner">
        <div>
            <h1>Welcome back, <?php echo htmlspecialchars($user['name'] ?? 'Patient'); ?>!</h1>
            <p>Manage your health appointments and explore our services.</p>
        </div>
        <div class="action-buttons">
            <a href="book_appointment.php" class="action-btn">📅 Make Appointment</a>
            <a href="my_appointments.php" class="action-btn">📋 My Appointments</a>
            <a href="health_packages.php" class="action-btn">📦 Health Packages</a>
        </div>
    </div>

    <!-- 筛选医生表单 -->
    <div class="filter-section">
        <form method="GET" action="" class="filter-form">
            <div class="filter-group">
                <label>Specialisation</label>
                <select name="specialisation">
                    <option value="">All</option>
                    <?php foreach ($specialisations as $spec): ?>
                        <option value="<?php echo htmlspecialchars($spec); ?>" <?php echo ($specialisation == $spec) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($spec); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Gender</label>
                <select name="gender">
                    <option value="">All</option>
                    <option value="Male" <?php echo ($gender == 'Male') ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo ($gender == 'Female') ? 'selected' : ''; ?>>Female</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Language</label>
                <select name="language">
                    <option value="">All</option>
                    <?php foreach ($all_languages as $lang): ?>
                        <option value="<?php echo htmlspecialchars($lang); ?>" <?php echo ($language == $lang) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($lang); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Doctor Name</label>
                <input type="text" name="doctor_name" placeholder="Type doctor name" value="<?php echo htmlspecialchars($doctor_name); ?>">
            </div>
            <div class="filter-group">
                <button type="submit" class="search-btn">🔍 Search</button>
                <button type="button" class="reset-btn" id="resetFilter">Reset</button>
            </div>
        </form>
    </div>

    <!-- 医生列表 -->
    <h2 style="margin-bottom:1rem;">👨‍⚕️ Our Doctors</h2>
    <?php if (count($doctors) > 0): ?>
        <div class="doctors-grid">
            <?php foreach ($doctors as $doctor): ?>
                <div class="doctor-card">
                    <?php
                    // 根据性别和 ID 生成固定的真人头像 URL
                    $genderFolder = ($doctor['gender'] == 'Male') ? 'men' : 'women';
                    $avatarId = ($doctor['id'] % 99) + 1; // 1-99 之间
                    $avatarUrl = "https://randomuser.me/api/portraits/{$genderFolder}/{$avatarId}.jpg";
                    ?>
                    <img class="doctor-avatar" src="<?= $avatarUrl ?>" alt="<?= htmlspecialchars($doctor['username']) ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($doctor['username']) ?>&background=0099ff&color=fff&rounded=true&size=100&bold=true'">
                    <div class="doctor-name"><?php echo htmlspecialchars($doctor['username']); ?></div>
                    <div class="doctor-spec"><?php echo htmlspecialchars($doctor['specialisation']); ?></div>
                    <div class="doctor-details">⚤ <?php echo $doctor['gender']; ?> | 🗣️ <?php echo htmlspecialchars($doctor['language']); ?></div>
                    <div class="doctor-details">📝 <?php echo htmlspecialchars(substr($doctor['bio'] ?? '', 0, 80)) . '...'; ?></div>
                    <button class="book-btn" data-doctor-id="<?php echo $doctor['id']; ?>" data-doctor-name="<?php echo htmlspecialchars($doctor['username']); ?>">Book Appointment</button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-results">😞 No doctors found matching your criteria. Please try different filters.</div>
    <?php endif; ?>

    <!-- 服务分类区域（与 homepage 同步） -->
    <div class="services-section">
        <h2>Beyond Boundaries</h2>
        <div class="services-sub">Comprehensive medical services tailored to your needs</div>

        <div class="service-category">
            <h3>🏥 Primary & Preventive Care</h3>
            <div class="service-cards">
                <div class="card"><div class="card-icon">🩺</div><h4>General Consultation</h4><p>Expert diagnosis & treatment for common illnesses</p></div>
                <div class="card"><div class="card-icon">💉</div><h4>Vaccination Hub</h4><p>Flu, COVID‑19, travel & childhood vaccines</p></div>
                <div class="card"><div class="card-icon">📊</div><h4>Health Screening</h4><p>Comprehensive wellness & early detection</p></div>
                <div class="card"><div class="card-icon">🍎</div><h4>Nutrition Counseling</h4><p>Personalized diet plans & lifestyle coaching</p></div>
            </div>
        </div>

        <div class="service-category">
            <h3>🩻 Specialist & Digital Care</h3>
            <div class="service-cards">
                <div class="card"><div class="card-icon">🦷</div><h4>Dental Care</h4><p>Cleaning, fillings, orthodontics & more</p></div>
                <div class="card"><div class="card-icon">🧠</div><h4>Mental Health</h4><p>Counseling, therapy & stress management</p></div>
                <div class="card"><div class="card-icon">💻</div><h4>Telemedicine</h4><p>Video consultations from home</p></div>
                <div class="card"><div class="card-icon">🦵</div><h4>Physiotherapy</h4><p>Rehabilitation & pain relief</p></div>
            </div>
        </div>

        <div class="service-category">
            <h3>🏠 Home & Convenience</h3>
            <div class="service-cards">
                <div class="card"><div class="card-icon">🏡</div><h4>Home Care Nursing</h4><p>Post‑op & chronic care at home</p></div>
                <div class="card"><div class="card-icon">📦</div><h4>Pharmacy Delivery</h4><p>Medicines delivered to your doorstep</p></div>
                <div class="card"><div class="card-icon">🧪</div><h4>Lab Tests at Home</h4><p>Sample collection & digital reports</p></div>
                <div class="card"><div class="card-icon">🚑</div><h4>Emergency Hotline</h4><p>24/7 urgent assistance & ambulance</p></div>
            </div>
        </div>
    </div>
</div>

<footer class="main-footer">
    <p>© 2025 CareConnect | Your health, our priority. | <a href="#" style="color:#0099ff;">Privacy Policy</a></p>
</footer>

<script>
    const baseUrl = '/Clinic_Booking_System/';
    // 退出登录
    const logoutBtn = document.getElementById('logoutNavBtn');
    if(logoutBtn) {
        logoutBtn.onclick = async () => {
            await fetch(baseUrl + 'logout.php', { method: 'POST' });
            window.location.href = baseUrl + 'Customer_Module/homepage.php';
        };
    }

    // 医生卡片上的预约按钮
    const bookBtns = document.querySelectorAll('.book-btn');
    bookBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const doctorId = btn.getAttribute('data-doctor-id');
            const doctorName = btn.getAttribute('data-doctor-name');
            window.location.href = `book_appointment.php?doctor_id=${doctorId}&doctor_name=${encodeURIComponent(doctorName)}`;
        });
    });

    // 重置筛选
    const resetBtn = document.getElementById('resetFilter');
    if(resetBtn) resetBtn.onclick = () => { window.location.href = window.location.pathname; };
</script>
</body>
</html>