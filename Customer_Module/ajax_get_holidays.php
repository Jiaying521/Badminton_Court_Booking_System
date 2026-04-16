<?php
// Customer_Module/ajax_get_holidays.php
require_once __DIR__ . '/holidays.php';
header('Content-Type: application/json');
echo json_encode(getPublicHolidays());