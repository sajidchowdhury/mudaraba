<?php 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();

    header('Content-Type: application/json'); // Ensure JSON response

    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(["status" => "error", "message" => "Invalid CSRF Token!"]);
        exit();
    }


        // Collect form data
        $investor_name     = $_POST['investor_name'] ?? '';
        $mobile          = $_POST['mobile'] ?? time() ;
        $address          = $_POST['address'] ?? 'n/a';
        $profit          = $_POST['profit'] ?? '';
        $start_profit_month          = $_POST['start_profit_month'] ?? '';
        $end_profit_month          = $_POST['end_profit_month'] ?? '';
        $related_id    = $_POST['related_id'] ?? 'New'; 
        $reference    = $_POST['reference'] ?? 'n/a'; 

    // Load necessary files
    include "autoloader.inc.php";

    // Create or update ledger account
    $action = new NewInvestorContr($investor_name, $reference, $mobile,$address,$profit, $start_profit_month , $end_profit_month , $related_id);
    $result = $action->Action(); 

    echo json_encode([
        "status" => $result['status'],
        "message" => $result['message']
    ]);
    exit();
}
