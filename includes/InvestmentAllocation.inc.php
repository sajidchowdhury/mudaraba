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
        $sector_id = $_POST['sector_id'];
        $amount = $_POST['amount'];
        $remarks = $_POST['remarks'];
        $type = $_POST['type'];
        $transaction_date = $_POST['transaction_date'];
        $current_inv = $_POST['current_inv'];

    // CSRF Protection
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(["status" => "error", "message" => "Invalid CSRF Token!"]);
        exit();
    }



    if ($type == 'withdraw' ) {
          if ($current_inv <   $amount) {  
        echo json_encode(["status" => "error", "message" => "NEED MORE INVESTMENT"]);
        exit();

          }
    }




    // Load necessary files
    include "autoloader.inc.php";


    // Create a new customer entry
    $action = new InvestmentAllocationContr($sector_id, $amount, $remarks , $type ,$transaction_date, $related_id);
    $result = $action->Action(); 

    // Return the result status
    echo json_encode(["status" => $result['status'], "message" => $result['message']]);
    exit();
}
