<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    header('Content-Type: application/json');

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data.']);
        exit;
    }

    $adv_adjust = floatval($data['adv_adjust'] ?? 0);
    $investors = $data['investors'] ?? [];
    $sectors   = $data['sectors'] ?? [];

    // ✅ Additional fields from JS if needed
    $AllocatedFUnd = floatval($data['AllocatedFUnd'] ?? 0);
    $adv_profit_adjusting_fund = floatval($data['adv_profit_adjusting_fund'] ?? 0);
    $RemainingFund = floatval($data['RemainingFund'] ?? 0);
    $type = trim($data['type'] ?? '');

    // ✅ Validation logic

    

    if ($adv_adjust > 0  && $type === '') {
        echo json_encode(['status' => 'error', 'message' => 'Select Invest Ratio.']);
        exit;
    }

    if ($RemainingFund > $adv_profit_adjusting_fund) {
        echo json_encode(['status' => 'error', 'message' => 'Not enough Adv Profit Adjusting Fund.']);
        exit;
    }

    if (!($AllocatedFUnd > 0 || $adv_profit_adjusting_fund > 0)) {
        echo json_encode(['status' => 'error', 'message' => 'Either Adv Profit Adjusting or Allocated Fund must be greater than 0.']);
        exit;
    }



    include "autoloader.inc.php";

    try {
        $controller = new AdvanceProfitAdjustmentTypeAContr($adv_adjust, $investors, $sectors);
        $result = $controller->saveAdjustment();

        echo json_encode([
            'status' => $result['status'],
            'message' => $result['message']
        ]);

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
    }
    exit;
}
