<?php
// includes/InvestorProfit.inc.php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

try {
    // adjust the path to your autoloader / bootstrap if needed
    require_once 'autoloader.inc.php';

    // read JSON body
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        throw new Exception('Invalid JSON payload');
    }

    // CSRF
    $csrf = $data['csrf'] ?? null;
    if (!$csrf || !isset($_SESSION['csrf_token']) || $csrf !== $_SESSION['csrf_token']) {
        throw new Exception('Invalid CSRF token');
    }

    $month = $data['month'] ?? '';
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        throw new Exception('Invalid month format. Expected YYYY-MM.');
    }




    $estimatedprofit = isset($data['estimatedprofit']) ? (float)$data['estimatedprofit'] : null;
    $actualprofit = isset($data['actualprofit']) ? (float)$data['actualprofit'] : null;
    $MyAmount = $data['MyAmount'] ? (float)$data['MyAmount'] : 0.00;




    if ($actualprofit === null || $actualprofit <= 0) {
        throw new Exception('Actual profit must be provided and greater than 0.');
    }

    if (empty($data['investors']) || !is_array($data['investors'])) {
        throw new Exception('Investor list missing.');
    }

    // pass to controller
    $controller = new InvestorProfitContr($data['investors'], $month, $estimatedprofit, $actualprofit, $MyAmount);
    $result = $controller->Action();

    echo json_encode(['success' => true, 'message' => $result['message'] ?? 'Saved']);
    exit;

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
