<?php
require_once __DIR__ . '/../config.php';
if (!isLoggedIn()) redirect('homepage.php');

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch all active coaches
$coaches = $pdo->query("
    SELECT c.*, a.email
    FROM coaches c
    JOIN admins a ON c.admin_id = a.id
    WHERE c.is_active = 1
    ORDER BY c.name ASC
")->fetchAll();

$avail_map = [
    'Available' => ['color' => '#16a34a', 'bg' => '#dcfce7'],
    'On Leave'  => ['color' => '#d97706', 'bg' => '#fef3c7'],
    'Sick'      => ['color' => '#dc2626', 'bg' => '#fee2e2'],
    'Off Day'   => ['color' => '#64748b', 'bg' => '#f1f5f9'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Coaches – Smash Arena</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #f5f9f0 0%, #e8efe2 100%);
            min-height: 100vh;
            color: #1e2a2e;
        }

        .container { max-width: 1100px; margin: 0 auto; padding: 2rem 1.5rem; }

        /* Navbar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
            flex-wrap: wrap;
            gap: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(43,126,58,0.15);
        }

        .logo-area { display: flex; align-items: center; gap: 0.8rem; text-decoration: none; }
        .logo-area img { height: 44px; }
        .logo-text { font-size: 1.3rem; font-weight: 800; color: #1e2a2e; }
        .logo-text span { color: #2b7e3a; }

        .nav-links { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
        .nav-links a { color: #2c4a2e; text-decoration: none; font-weight: 500; font-size: 0.9rem; transition: color 0.2s; }
        .nav-links a:hover, .nav-links a.active { color: #2b7e3a; }
        .nav-links a.active { font-weight: 700; }
        .user-greeting { color: #2b7e3a; font-weight: 500; font-size: 0.9rem; }
        .btn-logout { background: #fee2e2; color: #e67e22; padding: 0.3rem 1rem; border-radius: 50px; font-size: 0.8rem; }
        .btn-logout:hover { background: #e67e22; color: white; }

        /* Page header */
        .page-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 800;
            color: #1e2a2e;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: #64748b;
            font-size: 1rem;
        }

        /* Coaches grid */
        .coaches-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
        }

        /* Coach card */
        .coach-card {
            background: #fff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.07);
            border: 1px solid #e8f0e5;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .coach-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 32px rgba(0,0,0,0.12);
        }

        .card-hero {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            padding: 28px 24px 20px;
            text-align: center;
            position: relative;
        }

        .coach-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #f59e0b;
            box-shadow: 0 0 0 3px rgba(245,158,11,0.2);
            margin-bottom: 12px;
        }

        .coach-name {
            font-size: 1.1rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 4px;
        }

        .avail-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 12px;
            border-radius: 50px;
            font-size: 0.72rem;
            font-weight: 700;
        }

        .card-body {
            padding: 18px 22px 22px;
        }

        .coach-meta {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 18px;
        }

        .meta-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: #475569;
        }

        .meta-row i { color: #2b7e3a; width: 14px; text-align: center; font-size: 0.8rem; }
        .meta-row strong { color: #1e2a2e; }

        .card-actions {
            display: flex;
            gap: 10px;
        }

        .btn-view {
            flex: 1;
            padding: 10px;
            border-radius: 12px;
            border: 1.5px solid #2b7e3a;
            background: #fff;
            color: #2b7e3a;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
        }

        .btn-view:hover {
            background: #eaf5e6;
        }

        .btn-book {
            flex: 1;
            padding: 10px;
            border-radius: 12px;
            border: none;
            background: linear-gradient(135deg, #2b7e3a, #1b5e2a);
            color: #fff;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
        }

        .btn-book:hover { opacity: 0.88; }

        .btn-book.disabled {
            background: #e2e8f0;
            color: #94a3b8;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }

        .empty-state i { font-size: 3rem; margin-bottom: 16px; display: block; }

        @media (max-width: 600px) {
            .coaches-grid { grid-template-columns: 1fr; }
            .page-header h1 { font-size: 1.5rem; }
            .navbar { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
<div class="container">

    <!-- Navbar -->
    <div class="navbar">
        <a href="dashboard.php" class="logo-area">
            <img src="../Admin_Module/Pictures/logo.png" alt="Smash Arena" onerror="this.style.display='none'">
            <div class="logo-text">Smash <span>Arena</span></div>
        </a>
        <div class="nav-links">
            <a href="dashboard.php"><i class="fas fa-home"></i> Courts</a>
            <a href="my_bookings.php"><i class="fas fa-bookmark"></i> My Bookings</a>
            <a href="../Payment_Module/wallet.php"><i class="fas fa-wallet"></i> Wallet</a>
            <a href="coaches.php" class="active"><i class="fas fa-user-tie"></i> Coaches</a>
            <span class="user-greeting">🏸 <?php echo htmlspecialchars($user['name'] ?? 'Player'); ?></span>
            <a href="edit_profile.php" style="color:#2b7e3a;font-size:0.85rem;"><i class="fas fa-user-edit"></i> Profile</a>
            <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Header -->
    <div class="page-header">
        <h1><i class="fas fa-user-tie" style="color:#2b7e3a;"></i> Our Coaches</h1>
        <p>Browse our professional coaches and book a training session</p>
    </div>

    <!-- Grid -->
    <?php if (empty($coaches)): ?>
        <div class="empty-state">
            <i class="fas fa-user-slash"></i>
            No coaches available at the moment.
        </div>
    <?php else: ?>
        <div class="coaches-grid">
            <?php foreach ($coaches as $c):
                $avail  = $c['availability_status'] ?? 'Available';
                $ac     = $avail_map[$avail] ?? $avail_map['Available'];
                $img    = !empty($c['profile_img'])
                            ? '../Admin_Module/Pictures/coaches/' . htmlspecialchars($c['profile_img'])
                            : '../Admin_Module/Pictures/coaches/default.png';
            ?>
            <div class="coach-card">
                <div class="card-hero">
                    <img src="<?php echo $img; ?>"
                         alt="<?php echo htmlspecialchars($c['name']); ?>"
                         class="coach-avatar"
                         onerror="this.src='../Admin_Module/Pictures/coaches/default.png'">
                    <div class="coach-name"><?php echo htmlspecialchars($c['name']); ?></div>
                    <span class="avail-pill" style="background:<?php echo $ac['bg']; ?>;color:<?php echo $ac['color']; ?>;">
                        ● <?php echo htmlspecialchars($avail); ?>
                    </span>
                </div>

                <div class="card-body">
                    <div class="coach-meta">
                        <?php if ($c['specialty']): ?>
                        <div class="meta-row">
                            <i class="fas fa-star"></i>
                            <span><?php echo htmlspecialchars($c['specialty']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="meta-row">
                            <i class="fas fa-tag"></i>
                            <strong>RM <?php echo number_format($c['price_per_hour'], 2); ?></strong>
                            <span>/ hour</span>
                        </div>
                        <?php if ($c['gender']): ?>
                        <div class="meta-row">
                            <i class="fas fa-venus-mars"></i>
                            <span><?php echo htmlspecialchars($c['gender']); ?></span>
                            <?php if ($c['age']): ?>
                                <span style="color:#94a3b8;">· <?php echo (int)$c['age']; ?> yrs</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-actions">
                        <a href="view_coach.php?id=<?php echo $c['id']; ?>" class="btn-view">
                            <i class="fas fa-user"></i> View Profile
                        </a>
                        <?php if ($avail === 'Available'): ?>
                            <a href="book_court.php?coach_id=<?php echo $c['id']; ?>" class="btn-book">
                                <i class="fas fa-calendar-plus"></i> Book
                            </a>
                        <?php else: ?>
                            <span class="btn-book disabled">
                                <i class="fas fa-calendar-xmark"></i> Unavailable
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>
</body>
</html>
