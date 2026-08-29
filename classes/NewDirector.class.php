<?php 

class NewDirector extends Dbh {

    
    protected function CreateData($director_name, $mobile) {
    try {
        $db = $this->connect();

date_default_timezone_set('Asia/Dhaka');



 
         if (empty($director_name)) {
            throw new Exception("Investor is empty");
          }

        

        // Check for duplicate customer based on mobile
        $checkStmt = $db->prepare("SELECT COUNT(*) as count FROM `directors` WHERE `name` = :director_name");
        $checkStmt->bindParam(':director_name', $director_name);
        $checkStmt->execute();
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            return ["status" => "error", "message" => "A director with this name number already exists."];
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        $employee_id = $_SESSION['employee_id'] ?? 'unknown';
        $created_at = date('Y-m-d');

        $stmt = $db->prepare("INSERT INTO `directors` 
            (`name`, `mobile`,  `created_at`)
            VALUES 
            (:director_name, :mobile, :created_at)");

        $stmt->bindParam(':director_name', $director_name);
        $stmt->bindParam(':mobile', $mobile);
        $stmt->bindParam(':created_at', $created_at);

        if (!$stmt->execute()) {
            throw new Exception("Database error: Failed to create Sector.");
        }

        return ["status" => "success", "message" => "Director created successfully."];

    } catch (Exception $e) {
        return ["status" => "error", "message" => $e->getMessage()];
    } finally {
        $stmt = null;
        $checkStmt = null;
    }
}

protected function UpdateData($director_name, $mobile, $related_id) {
    try {
        $db = $this->connect();

date_default_timezone_set('Asia/Dhaka');



        // Check for duplicate mobile excluding the current record
        $checkStmt = $db->prepare("SELECT COUNT(*) as count FROM `directors` 
            WHERE `name` = :director_name AND `id` <> :related_id");
        $checkStmt->bindParam(':director_name', $director_name);
        $checkStmt->bindParam(':related_id', $related_id);
        $checkStmt->execute();
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            return ["status" => "error", "message" => "Another director already uses this name."];
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        $updated_at = date('Y-m-d H:i:s');

        $stmt = $db->prepare("UPDATE `directors` SET
            `name` = :director_name,
            `mobile` = :mobile,
            `updated_at` = :updated_at
            WHERE `id` = :related_id");

        $stmt->bindParam(':director_name', $director_name);
        $stmt->bindParam(':mobile', $mobile);
        $stmt->bindParam(':updated_at', $updated_at);
        $stmt->bindParam(':related_id', $related_id);

        if (!$stmt->execute()) {
            throw new Exception("Database error: Failed to update director.");
        }

        return ["status" => "success", "message" => "Director updated successfully."];

    } catch (Exception $e) {
        return ["status" => "error", "message" => $e->getMessage()];
    } finally {
        $stmt = null;
        $checkStmt = null;
    }
}



    public function ListData() {
        $stmt = $this->connect()->prepare('SELECT * FROM directors  ');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function SingleData($id) {
        $stmt = $this->connect()->prepare('SELECT 
    A.*
FROM 
    directors A
 WHERE A.id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT); 
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ?: null;
    }
}
