<?php
require_once __DIR__ . '/../config.php';

$coach_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($coach_id <= 0) {
    redirect('dashboard.php');
}

// Fetch coach
$stmt = $pdo->prepare("
    SELECT c.*, a.email
    FROM coaches c
    JOIN admins a ON c.admin_id = a.id
    WHERE c.id = ? AND c.is_active = 1
    LIMIT 1
");
$stmt->execute([$coach_id]);
$coach = $stmt->fetch();

if (!$coach) {
    redirect('dashboard.php');
}

$profile_img = !empty($coach['profile_img'])
    ? '../Pictures/Admin_Module/coaches/' . htmlspecialchars($coach['profile_img'])
    : '../Pictures/Admin_Module/coaches/default.png';

$avail       = $coach['availability_status'] ?? 'Available';
$avail_map   = [
    'Available' => ['color' => '#16a34a', 'bg' => '#dcfce7', 'icon' => '●'],
    'On Leave'  => ['color' => '#d97706', 'bg' => '#fef3c7', 'icon' => '●'],
    'Sick'      => ['color' => '#dc2626', 'bg' => '#fee2e2', 'icon' => '●'],
    'Off Day'   => ['color' => '#64748b', 'bg' => '#f1f5f9', 'icon' => '●'],
];
$ac = $avail_map[$avail] ?? $avail_map['Available'];

$back = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($coach['name']); ?> – Coach Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #f5f9f0 0%, #e8efe2 100%);
            min-height: 100vh;
            padding: 2rem 1rem;
            color: #1e2a2e;
        }

        .container { max-width: 600px; margin: 0 auto; }

        /* Back link */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #2b7e3a;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 24px;
            transition: gap 0.2s;
        }
        .back-link:hover { gap: 12px; }

        /* Card */
        .coach-card {
            background: #fff;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 12px 40px rgba(0,0,0,0.1);
        }

        /* Header / Hero */
        .coach-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 40px 32px 32px;
            text-align: center;
            position: relative;
        }

        .coach-hero::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 30px;
            background: #fff;
            border-radius: 30px 30px 0 0;
        }

        .coach-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #f59e0b;
            box-shadow: 0 0 0 4px rgba(245,158,11,0.2);
            margin-bottom: 16px;
        }

        .coach-name {
            font-size: 22px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 6px;
        }

        .coach-role-tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(245,158,11,0.15);
            color: #f59e0b;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 4px 14px;
            border-radius: 50px;
            border: 1px solid rgba(245,158,11,0.3);
        }

        /* Body */
        .coach-body { padding: 8px 32px 36px; }

        .avail-row {
            display: flex;
            justify-content: center;
            margin-bottom: 28px;
        }

        .avail-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 18px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 700;
        }

        /* Info grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 28px;
        }

        .info-item {
            background: #f8faf5;
            border-radius: 16px;
            padding: 16px 18px;
            border: 1px solid #e0e8dc;
        }

        .info-item .label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .info-item .label i { color: #2b7e3a; }

        .info-item .value {
            font-size: 15px;
            font-weight: 700;
            color: #1e2a2e;
        }

        .info-item.highlight .value { color: #2b7e3a; font-size: 17px; }

        /* Book CTA */
        .book-cta {
            text-align: center;
            padding-top: 4px;
        }

        .btn-book {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #2b7e3a, #1b5e2a);
            color: #fff;
            text-decoration: none;
            padding: 13px 32px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 15px;
            box-shadow: 0 4px 14px rgba(43,126,58,0.35);
            transition: all 0.25s;
        }

        .btn-book:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 22px rgba(43,126,58,0.5);
        }

        .btn-book.unavailable {
            background: #e2e8f0;
            color: #94a3b8;
            cursor: not-allowed;
            box-shadow: none;
            pointer-events: none;
        }

        @media (max-width: 480px) {
            .info-grid { grid-template-columns: 1fr; }
            .coach-hero { padding: 32px 20px 28px; }
            .coach-body { padding: 8px 20px 28px; }
        }
    </style>
</head>
<body>
<div class="container">

    <a href="<?php echo htmlspecialchars($back); ?>" class="back-link">
        <i class="fas fa-arrow-left"></i> Go Back
    </a>

    <div class="coach-card">

        <!-- Hero -->
        <div class="coach-hero">
            <img src="<?php echo $profile_img; ?>" alt="<?php echo htmlspecialchars($coach['name']); ?>" class="coach-avatar"
                 onerror="this.src='../Pictures/Admin_Module/coaches/default.png'">
            <div class="coach-name"><?php echo htmlspecialchars($coach['name']); ?></div>
            <div class="coach-role-tag"><i class="fas fa-whistle"></i> Coach</div>
        </div>

        <!-- Body -->
        <div class="coach-body">

            <!-- Availability -->
            <div class="avail-row">
                <span class="avail-pill" style="background:<?php echo $ac['bg']; ?>;color:<?php echo $ac['color']; ?>">
                    <?php echo $ac['icon']; ?> <?php echo htmlspecialchars($avail); ?>
                </span>
            </div>

            <!-- Info Grid -->
            <div class="info-grid">

                <div class="info-item highlight">
                    <div class="label"><i class="fas fa-tag"></i> Rate</div>
                    <div class="value">RM <?php echo number_format($coach['price_per_hour'], 2); ?>/hr</div>
                </div>

                <div class="info-item">
                    <div class="label"><i class="fas fa-star"></i> Specialty</div>
                    <div class="value"><?php echo htmlspecialchars($coach['specialty'] ?: '—'); ?></div>
                </div>

                <?php if ($coach['gender']): ?>
                <div class="info-item">
                    <div class="label"><i class="fas fa-venus-mars"></i> Gender</div>
                    <div class="value"><?php echo htmlspecialchars($coach['gender']); ?></div>
                </div>
                <?php endif; ?>

                <?php if ($coach['age']): ?>
                <div class="info-item">
                    <div class="label"><i class="fas fa-cake-candles"></i> Age</div>
                    <div class="value"><?php echo (int)$coach['age']; ?> yrs</div>
                </div>
                <?php endif; ?>

            </div>

            <!-- Book CTA -->
            <div class="book-cta">
                <?php if ($avail === 'Available'): ?>
                    <a href="book_court.php?coach_id=<?php echo $coach_id; ?>" class="btn-book">
                        <i class="fas fa-calendar-plus"></i> Book a Session
                    </a>
                <?php else: ?>
                    <a href="#" class="btn-book unavailable">
                        <i class="fas fa-calendar-xmark"></i> Not Available Now
                    </a>
                <?php endif; ?>
            </div>

        </div>
    </div>

</div>
</body>
</html>
