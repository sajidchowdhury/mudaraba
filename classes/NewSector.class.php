<?php 

class NewSector extends Dbh {

    
    protected function CreateData($sector_name, $mobile, $address) {
    try {
        $db = $this->connect();

date_default_timezone_set('Asia/Dhaka');



 
         if (empty($sector_name)) {
            throw new Exception("Investor is empty");
          }

           if (empty($mobile)) {
            throw new Exception("Mobile is empty");
          }

           if (empty($address)) {
            throw new Exception("Address is empty");
          }





        // Check for duplicate customer based on mobile
        $checkStmt = $db->prepare("SELECT COUNT(*) as count FROM `sectors` WHERE `name` = :sector_name");
        $checkStmt->bindParam(':sector_name', $sector_name);
        $checkStmt->execute();
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            return ["status" => "error", "message" => "A sector with this name number already exists."];
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        $employee_id = $_SESSION['employee_id'] ?? 'unknown';
        $created_at = date('Y-m-d');

        $stmt = $db->prepare("INSERT INTO `sectors` 
            (`name`, `mobile`, `address`, `created_at`)
            VALUES 
            (:sector_name, :mobile, :address, :created_at)");

        $stmt->bindParam(':sector_name', $sector_name);
        $stmt->bindParam(':mobile', $mobile);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':created_at', $created_at);

        if (!$stmt->execute()) {
            throw new Exception("Database error: Failed to create Sector.");
        }

        return ["status" => "success", "message" => "Sector created successfully."];

    } catch (Exception $e) {
        return ["status" => "error", "message" => $e->getMessage()];
    } finally {
        $stmt = null;
        $checkStmt = null;
    }
}

protected function UpdateData($sector_name, $mobile, $address, $related_id) {
    try {
        $db = $this->connect();

date_default_timezone_set('Asia/Dhaka');



        // Check for duplicate mobile excluding the current record
        $checkStmt = $db->prepare("SELECT COUNT(*) as count FROM `sectors` 
            WHERE `name` = :sector_name AND `id` <> :related_id");
        $checkStmt->bindParam(':sector_name', $sector_name);
        $checkStmt->bindParam(':related_id', $related_id);
        $checkStmt->execute();
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            return ["status" => "error", "message" => "Another sector already uses this name."];
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        $updated_at = date('Y-m-d H:i:s');

        $stmt = $db->prepare("UPDATE `sectors` SET
            `name` = :sector_name,
            `mobile` = :mobile,
            `address` = :address,
            `updated_at` = :updated_at
            WHERE `id` = :related_id");

        $stmt->bindParam(':sector_name', $sector_name);
        $stmt->bindParam(':mobile', $mobile);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':updated_at', $updated_at);
        $stmt->bindParam(':related_id', $related_id);

        if (!$stmt->execute()) {
            throw new Exception("Database error: Failed to update Investor.");
        }

        return ["status" => "success", "message" => "Sector updated successfully."];

    } catch (Exception $e) {
        return ["status" => "error", "message" => $e->getMessage()];
    } finally {
        $stmt = null;
        $checkStmt = null;
    }
}



    public function ListData() {
        $stmt = $this->connect()->prepare('SELECT * FROM sectors  ');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function SingleData($id) {
        $stmt = $this->connect()->prepare('SELECT 
    A.*
FROM 
    sectors A
 WHERE A.id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT); 
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ?: null;
    }

        public function SectorWiseAdvProfit($id) {
        $stmt = $this->connect()->prepare('SELECT 
    A.*
FROM 
    sector_profit_due_ledger A
 WHERE A.id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT); 
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ?: null;
    }



}
