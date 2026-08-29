<?php 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();

    header('Content-Type: application/json'); // Ensure JSON response


$form_token = $_POST['form_token'] ?? '';
if (!isset($_SESSION['form_tokens'][$form_token])) {
   echo json_encode(['status' => 'error', 'message' => 'Double submission detected']);
    exit;
}
// Optional: expire it immediately after first use
unset($_SESSION['form_tokens'][$form_token]);



    // Collecting data from the form submission
        $related_id = $_POST['related_id'];
        $director_id = $_POST['director_id'];
        $amount = $_POST['amount'];
        $remarks = $_POST['remarks'];
        $transaction_date = $_POST['transaction_date'];
        $transaction_month = date("Y-m", strtotime($transaction_date));
    // CSRF Protection
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(["status" => "error", "message" => "Invalid CSRF Token!"]);
        exit();
    }

    // Load necessary files
    include "autoloader.inc.php";


    // Create a new customer entry
    $action = new WithdrawByDirectorContr($director_id, $amount, $remarks  ,$transaction_date, $transaction_month, $related_id);
    $result = $action->Action(); 

    // Return the result status
    echo json_encode(["status" => $result['status'], "message" => $result['message']]);
    exit();
}
