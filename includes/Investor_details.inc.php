<?php 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();

    $investor_id = $_POST['id'] ?? null;

    if (!$investor_id) {
        echo json_encode(['error' => 'No ID provided']);
        exit;
    }

    include "autoloader.inc.php";

    $action = new Investments();
    $result = $action->TotalInvestmentByInvestor($investor_id);

    // Return JSON
    echo json_encode($result);
}
