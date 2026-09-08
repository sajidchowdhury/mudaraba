<?php
include "autoloader.inc.php";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Read JSON input
$input = file_get_contents('php://input');
$data  = json_decode($input, true);

// Validate JSON
if (!$data || !is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$month       = trim($data['month'] ?? '');
$remarks     = trim($data['remarks'] ?? 'N/A');
$ProfitType  = trim($data['ProfitType'] ?? '');
$amounts     = $data['amounts'] ?? [];
$form_token  = $data['form_token'] ?? '';
$ratios      = $data['ratios'] ?? [];
$sector_id      = $data['sector_id'] ?? '';
$total_amount = $data['total_amount'] ?? 0.00;
try {
    // Token validation - prevents double submission
    if (!isset($_SESSION['form_tokens'][$form_token])) {
        throw new Exception('Double submission detected');
    }
    unset($_SESSION['form_tokens'][$form_token]);

    // Month validation
    if (empty($month)) {
        throw new Exception('Month cannot be empty');
    }

    // Amount validation
    if (empty($amounts) || !is_array($amounts)) {
        throw new Exception('No investor data provided');
    }

    if (empty($sector_id)) {
        throw new Exception('Seelct a sector');
    }


    $List3 = new SectorProfit();
    $due   = $List3->SectorProfitDue($sector_id);
    if($due < $total_amount){
        throw new Exception('Maximum Advance Profit Adjustment for this sector ' .  $due   );

    }


    $transaction_month = $month;
    $transaction_date  = $month . '-' . date("d");
    $related_id        = 'New';


     
   $List3->UpdateSectorAdvanceProfit($total_amount,$sector_id,$transaction_date,$month) ;


    foreach ($amounts as $investor_id => $amount) {

        $investor_id = trim($investor_id);
        $amount      = floatval($amount);
        $ratio       = isset($ratios[$investor_id]) ? floatval($ratios[$investor_id]) : null;

        if (empty($investor_id)) {
            throw new Exception("Investor ID cannot be empty");
        }
        if ($amount <= 0) {
            throw new Exception("Amount for investor ID {$investor_id} must be greater than zero");
        }

        // Create and execute investment action
        $action = new InvestorAdvanceProfitAdjustmentContr(
            $investor_id,
            $amount,
            $remarks,
            $ProfitType,
            $transaction_date,
            $transaction_month,
            $related_id
        );

        $result = $action->Action();

        if (!$result || (is_array($result) && isset($result['success']) && !$result['success'])) {
            throw new Exception(is_array($result) ? ($result['message'] ?? 'Save failed') : 'Save failed');
        }
    }


    

    echo json_encode(['success' => true, 'message' => 'Data saved successfully']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
