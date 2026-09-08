<?php

// Basic validation for month format YYYY-MM
$month = $_GET['month'] ?? '';
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid month format. Expecting YYYY-MM.']);
    exit;
}

// Load necessary classes / autoloader
include_once "autoloader.inc.php";

// Instantiate and fetch
try {
    $data = new SectorProfit();
    $response = $data->MonthlyProfitDetails($month);

    header('Content-Type: application/json');
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'details' => $e->getMessage()]);
}
