<?php
// functions.php - 公共函数文件

// 获取系统设置
function getSetting($key, $default = '') {
    global $pdo;
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['setting_value'] : $default;
}

// 获取营业时间显示文本
function getOperatingHours() {
    $open = getSetting('open_time', '08:00');
    $close = getSetting('close_time', '01:00');
    
    // 格式化时间显示
    $open_display = date('h:i A', strtotime($open));
    $close_display = date('h:i A', strtotime($close));
    
    return "$open_display - $close_display";
}

// 获取价格显示
function getPriceDisplay() {
    $off_peak = getSetting('off_peak_price', '10');
    $peak = getSetting('peak_price', '15');
    $peak_start = getSetting('peak_start', '15:00');
    $peak_end = getSetting('peak_end', '01:00');
    
    $peak_start_display = date('h:i A', strtotime($peak_start));
    $peak_end_display = date('h:i A', strtotime($peak_end));
    
    return "$peak_start_display - $peak_end_display: RM $peak/hour | 8am - $peak_start_display: RM $off_peak/hour";
}
?>