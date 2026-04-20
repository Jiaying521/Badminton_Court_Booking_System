<?php
require_once __DIR__ . '/holidays.php';
header('Content-Type: application/json');

$date = $_GET['date'] ?? '';
//Get the date from the URL (e.g., ?date=2025-12-25).
if (empty($date)) {
    echo json_encode(['isHoliday' => false]);
    exit;
}
//If there is no date, immediately reply {"isHoliday": false} and stop.
$holidays = getPublicHolidays();
//Call the function from holidays.php to get an array of holiday dates.
$isHoliday = in_array($date, $holidays);
//Check if the given date is inside that holiday array.
echo json_encode(['isHoliday' => $isHoliday]);