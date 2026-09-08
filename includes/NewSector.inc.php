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
        $sector_name     = $_POST['sector_name'] ?? '';
        $mobile          = $_POST['mobile'] ?? '';
        $address          = $_POST['address'] ?? '';
        $related_id    = $_POST['related_id'] ?? 'New'; 

    // Load necessary files
    include "autoloader.inc.php";

    // Create or update ledger account
    $action = new NewSectorContr($sector_name, $mobile,$address, $related_id);
    $result = $action->Action(); 

    echo json_encode([
        "status" => $result['status'],
        "message" => $result['message']
    ]);
    exit();
}
