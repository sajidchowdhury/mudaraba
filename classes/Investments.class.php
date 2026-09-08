<?php 

class Investments extends Dbh {

    
    protected function CreateData($investor_id, $amount, $remarks , $type ,$transaction_date, $transaction_month) {
    try {


        $db = $this->connect();

       date_default_timezone_set('Asia/Dhaka');


        if (session_status() === PHP_SESSION_NONE) session_start();
        $employee_id = $_SESSION['employee_id'] ?? 'unknown';
        $created_at = date('Y-m-d');

        $due = new InvestorDueManager();

        $stmt = $db->prepare("INSERT INTO `investment_transactions` 
            (`investor_id`, `amount`, `type`, `remarks`, `transaction_month`,`transaction_date`, `created_at` )
            VALUES 
            (:investor_id, :amount, :type, :remarks, :transaction_month,:transaction_date, :created_at)");

        $stmt->bindParam(':investor_id', $investor_id);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':remarks', $remarks);
        $stmt->bindParam(':transaction_month', $transaction_month);
        $stmt->bindParam(':transaction_date', $transaction_date);
        $stmt->bindParam(':created_at', $created_at);

         
        if (!$stmt->execute()) {
            throw new Exception("Database error: Failed to create investor investment.");
        }
        

         
        $amount = ($type == 'add' ) ? $amount : -$amount ;
        $due->updateDue($investor_id, $amount, $transaction_month);

        return ["status" => "success", "message" => "Created successfully."];

    } catch (Exception $e) {
        return ["status" => "error", "message" => $e->getMessage()];
    } finally {
        $stmt = null;
        $checkStmt = null;
    }
}




protected function UpdateData($investor_name, $mobile, $address, $related_id) {
    try {
        $db = $this->connect();

date_default_timezone_set('Asia/Dhaka');



        // Check for duplicate mobile excluding the current record
        $checkStmt = $db->prepare("SELECT COUNT(*) as count FROM `investors` 
            WHERE `mobile` = :mobile AND `id` <> :related_id");
        $checkStmt->bindParam(':mobile', $mobile);
        $checkStmt->bindParam(':related_id', $related_id);
        $checkStmt->execute();
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            return ["status" => "error", "message" => "Another Investor already uses this mobile number."];
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        $updated_at = date('Y-m-d H:i:s');

        $stmt = $db->prepare("UPDATE `investors` SET
            `name` = :investor_name,
            `mobile` = :mobile,
            `address` = :address,
            `updated_at` = :updated_at
            WHERE `id` = :related_id");

        $stmt->bindParam(':investor_name', $investor_name);
        $stmt->bindParam(':mobile', $mobile);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':updated_at', $updated_at);
        $stmt->bindParam(':related_id', $related_id);

        if (!$stmt->execute()) {
            throw new Exception("Database error: Failed to update Investor.");
        }

        return ["status" => "success", "message" => "Investor updated successfully."];

    } catch (Exception $e) {
        return ["status" => "error", "message" => $e->getMessage()];
    } finally {
        $stmt = null;
        $checkStmt = null;
    }
}



public function InvestmentTillMonth($investor_id, $transaction_month) {
    $sql = "SELECT 
                SUM(
                    CASE 
                        WHEN t.type = 'add' THEN t.amount
                        WHEN t.type = 'withdraw' THEN -t.amount
                        ELSE 0
                    END
                ) AS net_investment_till_month
            FROM investment_transactions t
            WHERE t.investor_id = :investor_id
              AND t.transaction_month <= :transaction_month";

    $stmt = $this->connect()->prepare($sql);
    $stmt->bindParam(':investor_id', $investor_id, PDO::PARAM_INT);
    $stmt->bindParam(':transaction_month', $transaction_month, PDO::PARAM_STR); // ✅ should be string
    $stmt->execute();

    $total = $stmt->fetchColumn();

    return $total !== false ? (float) $total : 0.00;
}


public function TotalInvestmentTillMonth($transaction_month) {
    $sql = "SELECT 
                SUM(
                    CASE 
                        WHEN t.type = 'add' THEN t.amount
                        WHEN t.type = 'withdraw' THEN -t.amount
                        ELSE 0
                    END
                ) AS net_investment_till_month
            FROM investment_transactions t
            WHERE t.transaction_month <= :transaction_month";

    $stmt = $this->connect()->prepare($sql);
    $stmt->bindParam(':transaction_month', $transaction_month, PDO::PARAM_STR); // ✅ should be string
    $stmt->execute();

    $total = $stmt->fetchColumn();

    return $total !== false ? (float) $total : 0.00;
}





public function TotalInvestmentByInvestor($investor_id) {

    $sql = "SELECT due AS total_investment FROM investor_due_ledger WHERE investor_id = :investor_id";

    $stmt = $this->connect()->prepare($sql);
    $stmt->bindParam(':investor_id', $investor_id, PDO::PARAM_INT);
    $stmt->execute();
    $total = $stmt->fetchColumn();

    return $total !== false ? (float) $total : 0.00;
}


public function TotalInvestment() {
    $sql = "SELECT SUM(due) AS total_investment FROM investor_due_ledger";

    $stmt = $this->connect()->prepare($sql);
    $stmt->execute();
    $total = $stmt->fetchColumn();

    return $total !== false ? (float) $total : 0.00;
}





    public function ListData() {
        $stmt = $this->connect()->prepare('SELECT A.*,B.name FROM investor_investments A join investors B ON (A.investor_id = B.id) ');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function SingleData($id) {
        $stmt = $this->connect()->prepare('SELECT 
    A.*
FROM 
    investors A
 WHERE A.id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT); 
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ?: null;
    }
}
