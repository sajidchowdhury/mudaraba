<?php 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();

    $sector_id = $_POST['id'] ?? null;

    if (!$sector_id) {
        echo json_encode(['error' => 'No ID provided']);
        exit;
    }

    include "autoloader.inc.php";





    $action = new InvestmentAllocation();
    $result = $action->TotalInvestment($sector_id);

    // Return JSON
    echo json_encode($result);
}
