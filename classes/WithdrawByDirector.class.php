<?php 

class WithdrawByDirector extends Dbh {

    
    protected function CreateData($director_id, $amount, $remarks  ,$transaction_date, $transaction_month) {
    try {


        $db = $this->connect();

       date_default_timezone_set('Asia/Dhaka');


        if (session_status() === PHP_SESSION_NONE) session_start();
        $employee_id = $_SESSION['employee_id'] ?? 'unknown';
        $created_at = date('Y-m-d');

        $due = new DirectorDueManager();

        $stmt = $db->prepare("INSERT INTO `director_transactions` 
            (`director_id`, `amount`, `remarks`, `transaction_month`,`transaction_date`, `created_at` )
            VALUES 
            (:director_id, :amount, :remarks, :transaction_month,:transaction_date, :created_at)");

        $stmt->bindParam(':director_id', $director_id);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':remarks', $remarks);
        $stmt->bindParam(':transaction_month', $transaction_month);
        $stmt->bindParam(':transaction_date', $transaction_date);
        $stmt->bindParam(':created_at', $created_at);


         
        if (!$stmt->execute()) {
            throw new Exception("Database error: Failed to create investor investment.");
        }
        
        $due->updateDue($director_id, -$amount, $transaction_month);

    
        return ["status" => "success", "message" => "Created successfully."];

    } catch (Exception $e) {
        return ["status" => "error", "message" => $e->getMessage()];
    } finally {
        $stmt = null;
        $checkStmt = null;
    }
}



}
