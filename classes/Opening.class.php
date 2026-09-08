<?php 

class Opening extends Dbh {




    protected function CreateData($action_id, $amount, $remarks  ,$action_type,$transaction_date, $month) {
    try {


        $db = $this->connect();

       date_default_timezone_set('Asia/Dhaka');


        if (session_status() === PHP_SESSION_NONE) session_start();


        $created_at = date('Y-m-d');


         if($action_type == 'director' ){

        $due = new DirectorDueManager();
        $due->updateDue($action_id, $amount, $month);


        $stmt = $db->prepare("INSERT INTO `opening_director_due` 
            (`director_id`, `amount`, `remarks`, `month`,`transaction_date` )
            VALUES 
            (:action_id, :amount, :remarks, :month,:transaction_date)");

        $stmt->bindParam(':action_id', $action_id);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':remarks', $remarks);
        $stmt->bindParam(':month', $month);
        $stmt->bindParam(':transaction_date', $transaction_date);


         }


         if($action_type == 'investor' ){

        $due = new InvestorProfitDueManager();
        $due->updateDue($action_id, $amount, $month);


        $stmt = $db->prepare("INSERT INTO `opening_investor_profit_due` 
            (`investor_id`, `amount`, `remarks`, `month`,`transaction_date` )
            VALUES 
            (:action_id, :amount, :remarks, :month,:transaction_date)");

        $stmt->bindParam(':action_id', $action_id);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':remarks', $remarks);
        $stmt->bindParam(':month', $month);
        $stmt->bindParam(':transaction_date', $transaction_date);


        

         }

         if($action_type == 'sector' ){

        $due = new SectorProfitDueManager();
        $due->updateDue($action_id, $amount, $month);


        $stmt = $db->prepare("INSERT INTO `opening_sector_profit_due` 
            (`sector_id`, `amount`, `remarks`, `month`,`transaction_date` )
            VALUES 
            (:action_id, :amount, :remarks, :month,:transaction_date)");

        $stmt->bindParam(':action_id', $action_id);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':remarks', $remarks);
        $stmt->bindParam(':month', $month);
        $stmt->bindParam(':transaction_date', $transaction_date);




         }






         
        if (!$stmt->execute()) {
            throw new Exception("Database error: Failed to create investor investment.");
        }
        

    
        return ["status" => "success", "message" => "Created successfully."];

    } catch (Exception $e) {
        return ["status" => "error", "message" => $e->getMessage()];
    } finally {
        $stmt = null;
        $checkStmt = null;
    }
}



}
