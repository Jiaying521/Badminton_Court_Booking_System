<?php
// Customer_Module/ajax_check_holiday.php
require_once __DIR__ . '/holidays.php';
header('Content-Type: application/json');

$date = $_GET['date'] ?? '';
if (empty($date)) {
    echo json_encode(['isHoliday' => false]);
    exit;
}

$holidays = getPublicHolidays();
$isHoliday = in_array($date, $holidays);
echo json_encode(['isHoliday' => $isHoliday]);