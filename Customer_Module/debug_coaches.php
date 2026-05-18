<?php
require_once __DIR__ . '/../config.php';

echo "<h1>教练照片调试信息</h1>";

// 获取所有教练
$stmt = $pdo->query("SELECT * FROM coaches WHERE is_active = 1");
$coaches = $stmt->fetchAll();

echo "<h2>数据库中的教练数据：</h2>";
echo "<pre>";
foreach ($coaches as $coach) {
    echo "ID: " . $coach['id'] . "\n";
    echo "Name: " . $coach['name'] . "\n";
    echo "profile_img: " . ($coach['profile_img'] ?? 'NULL') . "\n";
    echo "---\n";
}
echo "</pre>";

echo "<h2>图片文件检查：</h2>";

// 检查目录是否存在
$baseDir = __DIR__ . '/../Admin_Module/Pictures/coaches/';
echo "基础路径: " . $baseDir . "<br>";
echo "目录是否存在: " . (file_exists($baseDir) ? '是' : '否') . "<br>";

if (file_exists($baseDir)) {
    echo "<h3>目录中的文件：</h3>";
    $files = scandir($baseDir);
    echo "<ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>" . $file . "</li>";
        }
    }
    echo "</ul>";
}

echo "<h2>测试每个教练的图片路径：</h2>";

function testImagePath($coach, $baseDir) {
    $paths = [];
    
    // 1. 如果数据库有 profile_img
    if (!empty($coach['profile_img'])) {
        $paths[] = $baseDir . $coach['profile_img'];
        $paths[] = '../Admin_Module/Pictures/coaches/' . $coach['profile_img'];
    }
    
    // 2. 按ID匹配
    $paths[] = $baseDir . 'coach' . $coach['id'] . '.jpg';
    $paths[] = $baseDir . 'coach' . $coach['id'] . '.png';
    $paths[] = $baseDir . 'coach_' . $coach['id'] . '.jpg';
    
    // 3. 按名称匹配
    $nameLower = strtolower(str_replace(' ', '_', $coach['name']));
    $paths[] = $baseDir . $nameLower . '.jpg';
    $paths[] = $baseDir . $nameLower . '.png';
    
    // 4. 默认
    $paths[] = $baseDir . 'default.png';
    
    echo "<h3>教练: " . $coach['name'] . "</h3>";
    $found = false;
    foreach ($paths as $path) {
        echo "检查: " . $path . " - ";
        if (file_exists($path)) {
            echo "<span style='color:green'>✓ 找到！</span><br>";
            echo "可访问URL: " . str_replace(__DIR__ . '/../', '', $path) . "<br>";
            $found = true;
            break;
        } else {
            echo "<span style='color:red'>✗ 不存在</span><br>";
        }
    }
    if (!$found) {
        echo "<span style='color:orange'>⚠ 没有找到任何图片文件</span><br>";
    }
}

foreach ($coaches as $coach) {
    testImagePath($coach, $baseDir);
}

echo "<h2>当前使用的图片URL：</h2>";
foreach ($coaches as $coach) {
    $imgUrl = getCoachImageUrl($coach);
    echo "<div>";
    echo "<strong>" . $coach['name'] . ":</strong> ";
    echo "<img src='" . $imgUrl . "' style='width: 60px; height: 60px; border-radius: 50%;' onerror=\"this.onerror=null; this.src='../Admin_Module/Pictures/coaches/default.png'; console.log('图片加载失败:', this.src);\">";
    echo " URL: " . $imgUrl;
    echo "</div><br>";
}

function getCoachImageUrl($coach) {
    if (!empty($coach['profile_img'])) {
        if (strpos($coach['profile_img'], '../') === 0) {
            return $coach['profile_img'];
        } else if (strpos($coach['profile_img'], 'Admin_Module/') === 0) {
            return '../' . $coach['profile_img'];
        } else {
            return '../Admin_Module/Pictures/coaches/' . $coach['profile_img'];
        }
    }
    return '../Admin_Module/Pictures/coaches/coach' . $coach['id'] . '.jpg';
}
?>