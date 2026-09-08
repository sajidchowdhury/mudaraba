<?php 

class InvestmentAllocation extends Dbh {

    
    protected function CreateData($sector_id, $amount, $remarks , $type ,$transaction_date) {
    try {


        $db = $this->connect();

date_default_timezone_set('Asia/Dhaka');
            $sdu = new SectorDueManager();




        if (session_status() === PHP_SESSION_NONE) session_start();
        $employee_id = $_SESSION['employee_id'] ?? 'unknown';
        $created_at = date('Y-m-d');

        $stmt = $db->prepare("INSERT INTO `sector_investments` 
            (`sector_id`, `amount`, `type`, `remarks`, `transaction_date`, `created_at` )
            VALUES 
            (:sector_id, :amount, :type, :remarks, :transaction_date, :created_at)");

        $stmt->bindParam(':sector_id', $sector_id);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':remarks', $remarks);
        $stmt->bindParam(':transaction_date', $transaction_date);
        $stmt->bindParam(':created_at', $created_at);

        if (!$stmt->execute()) {
            throw new Exception("Database error: Failed to create investor investment.");
        }


        $amount = ($type == 'add' ) ? $amount : -$amount ; 
        $month = date("Y-m", strtotime($transaction_date));

        $sdu->updateDue($sector_id, $amount, $month);

        return ["status" => "success", "message" => "Created successfully."];

    } catch (Exception $e) {
        return ["status" => "error", "message" => $e->getMessage()];
    } finally {
        $stmt = null;
        $checkStmt = null;
    }
}


public function TotalInvestment($sector_id) {
    $sql = "
        SELECT due AS total_investment
        FROM 
            sector_due_ledger
        WHERE 
            sector_id = :sector_id
    ";

    $stmt = $this->connect()->prepare($sql);
    $stmt->bindParam(':sector_id', $sector_id, PDO::PARAM_INT);
    $stmt->execute();
    $total = $stmt->fetchColumn();

    return $total !== false ? (float) $total : 0.00;
}


public function AllInvestment() {
    $sql = "
        SELECT 
            SUM(due) AS total_investment
        FROM 
            sector_due_ledger
      
    ";

    $stmt = $this->connect()->prepare($sql);
    $stmt->execute();
    $total = $stmt->fetchColumn();

    return $total !== false ? (float) $total : 0.00;
}


public function SectorWiseTotalInvestment() {
    $sql = "
        SELECT 
            B.name as sectorName, A.due AS total_investment
        FROM 
            sector_due_ledger A 
            JOIN sectors B ON (A.sector_id = B.id) group by A.sector_id
      
    ";

    $stmt = $this->connect()->prepare($sql);
    $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

}





    public function ListData() {
        $stmt = $this->connect()->prepare('SELECT A.*,B.name FROM sector_investments A join sectors B ON (A.sector_id = B.id) ');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function SingleData($id) {
        $stmt = $this->connect()->prepare('SELECT 
    A.*
FROM 
    sector_investments A
 WHERE A.id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT); 
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ?: null;
    }
}
