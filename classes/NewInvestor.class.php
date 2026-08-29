<?php 

class NewInvestor extends Dbh {

    
    protected function CreateData($investor_name, $reference, $mobile, $address,$profit,$start_profit_month,$end_profit_month) {
    try {
        $db = $this->connect();

date_default_timezone_set('Asia/Dhaka');



 
         if (empty($investor_name)) {
            throw new Exception("Investor is empty");
          }


    




        // Check for duplicate customer based on mobile
        $checkStmt = $db->prepare("SELECT COUNT(*) as count FROM `investors` WHERE `mobile` = :mobile");
        $checkStmt->bindParam(':mobile', $mobile);
        $checkStmt->execute();
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            return ["status" => "error", "message" => "A investor with this mobile number already exists."];
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        $employee_id = $_SESSION['employee_id'] ?? 'unknown';
        $created_at = date('Y-m-d');

        $stmt = $db->prepare("INSERT INTO `investors` 
            (`name`, `reference`, `mobile`, `address`, `profit`,`start_profit_month`,`end_profit_month`,`created_at`)
            VALUES 
            (:investor_name, :reference , :mobile, :address, :profit, :start_profit_month, :end_profit_month, :created_at)");

        $stmt->bindParam(':investor_name', $investor_name);
        $stmt->bindParam(':reference', $reference);
        $stmt->bindParam(':mobile', $mobile);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':profit', $profit);
        $stmt->bindParam(':start_profit_month', $start_profit_month);
        $stmt->bindParam(':end_profit_month', $end_profit_month);
        $stmt->bindParam(':created_at', $created_at);

        if (!$stmt->execute()) {
            throw new Exception("Database error: Failed to create investor.");
        }

        return ["status" => "success", "message" => "Investor created successfully."];

    } catch (Exception $e) {
        return ["status" => "error", "message" => $e->getMessage()];
    } finally {
        $stmt = null;
        $checkStmt = null;
    }
}

protected function UpdateData($investor_name, $reference, $mobile, $address, $profit , $start_profit_month,$end_profit_month, $related_id) {
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
            `reference` = :reference,
            `mobile` = :mobile,
            `address` = :address,
            `profit` = :profit,
            `start_profit_month` = :start_profit_month,
            `end_profit_month` = :end_profit_month,
            `updated_at` = :updated_at
            WHERE `id` = :related_id");

        $stmt->bindParam(':investor_name', $investor_name);
        $stmt->bindParam(':reference', $reference);
        $stmt->bindParam(':mobile', $mobile);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':profit', $profit);
        $stmt->bindParam(':start_profit_month', $start_profit_month);
        $stmt->bindParam(':end_profit_month', $end_profit_month);
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



    public function ListData() {
        $stmt = $this->connect()->prepare('SELECT * FROM investors  ');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

        public function ListDataByInvestRatio() {
        $stmt = $this->connect()->prepare('SELECT * FROM investors group by profit ');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }




public function ListDataByDateRange($month) {
    $stmt = $this->connect()->prepare('
        SELECT * 
        FROM `investors` 
        WHERE :month1 >= start_profit_month 
          AND :month2 <= end_profit_month
    ');
    $stmt->bindValue(':month1', $month, PDO::PARAM_STR);
    $stmt->bindValue(':month2', $month, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


public function ListDataByDateRangeGroupByRatio($month,$Ratio) {
    $stmt = $this->connect()->prepare('
        SELECT * 
        FROM `investors` 
        WHERE 
        profit = :Ratio AND 
        :month1 >= start_profit_month 
    AND :month2 <= end_profit_month
    ');
    $stmt->bindValue(':Ratio', $Ratio, PDO::PARAM_INT);
    $stmt->bindValue(':month1', $month, PDO::PARAM_STR);
    $stmt->bindValue(':month2', $month, PDO::PARAM_STR);
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
