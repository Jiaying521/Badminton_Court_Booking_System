<?php
require_once __DIR__ . '/../config.php';
$packages = $pdo->query("SELECT * FROM health_packages WHERE is_active = 1 ORDER BY price")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Packages | CareConnect</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(145deg, #f6fafd 0%, #eef2f8 100%); color: #1a2c3e; padding: 2rem; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { font-size: 2rem; font-weight: 700; color: #0099ff; margin-bottom: 0.5rem; text-align: center; }
        .sub { text-align: center; color: #5b6e8c; margin-bottom: 2rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; }
        .card { background: white; border-radius: 32px; padding: 1.8rem; box-shadow: 0 12px 28px rgba(0,0,0,0.08); transition: 0.2s; }
        .card:hover { transform: translateY(-5px); }
        .price { font-size: 2rem; font-weight: 800; color: #0099ff; margin: 1rem 0; }
        .features { margin: 1rem 0; padding-left: 1.2rem; color: #4a627a; }
        .features li { margin-bottom: 0.4rem; }
        button { background: linear-gradient(105deg, #0099ff, #0077cc); color: white; border: none; padding: 0.8rem 1.5rem; border-radius: 60px; font-weight: 600; cursor: pointer; width: 100%; margin-top: 1rem; }
        button:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,153,255,0.3); }
        .back-link { display: inline-block; margin-top: 2rem; color: #0099ff; text-decoration: none; text-align: center; width: 100%; }
        @media (max-width: 768px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="container">
    <h1>💊 Health Packages</h1>
    <div class="sub">Choose the plan that suits you best</div>
    <div class="grid">
        <?php foreach ($packages as $pkg): ?>
            <div class="card">
                <h2><?= htmlspecialchars($pkg['name']) ?></h2>
                <p><?= htmlspecialchars($pkg['description']) ?></p>
                <div class="price">$<?= number_format($pkg['price'], 2) ?></div>
                <ul class="features">
                    <?php 
                    $features = explode("\n", $pkg['features']);
                    foreach ($features as $feature): ?>
                        <li>✓ <?= htmlspecialchars($feature) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button onclick="alert('Please contact our clinic to purchase this package. Call +603-1234 5678')">Enquire Now</button>
            </div>
        <?php endforeach; ?>
    </div>
    <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
</div>
</body>
</html>