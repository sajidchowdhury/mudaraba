<?php 

class User extends Dbh {



protected function CreateData($employee_id, $user_name, $password, $status, $branch_id, $login_start, $login_end,$role) {
    try {
        $db = $this->connect();
        if (!$db) {
            throw new Exception("Database connection failed.");
        }

        // Check for duplicate user
        $checkStmt = $db->prepare("SELECT COUNT(*) as count FROM `users` WHERE `user_name` = :user_name");
        $checkStmt->bindValue(':user_name', $user_name);
        $checkStmt->execute();
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($result && $result['count'] > 0) {
            return ["status" => "error", "message" => "Duplicate user name."];
        }

        // Begin transaction
        $db->beginTransaction();
date_default_timezone_set('Asia/Dhaka');

        // Insert into users
        $insertStmt = $db->prepare("
            INSERT INTO `users` 
            (`user_name`, `employee_id`, `hash_pass`, `branch_id`, `status`, `login_start`, `login_end`,`role`) 
            VALUES 
            (:user_name, :employee_id, :hash_pass, :branch_id, :status, :login_start, :login_end, :role)
        ");

        $hash_pass = password_hash($password, PASSWORD_BCRYPT);

        $insertStmt->bindValue(':user_name', $user_name);
        $insertStmt->bindValue(':employee_id', $employee_id);
        $insertStmt->bindValue(':hash_pass', $hash_pass);
        $insertStmt->bindValue(':branch_id', $branch_id);
        $insertStmt->bindValue(':status', $status);
        $insertStmt->bindValue(':login_start', $login_start);
        $insertStmt->bindValue(':login_end', $login_end);
        $insertStmt->bindValue(':role', $role);

        if (!$insertStmt->execute()) {
            throw new Exception("Failed to insert user.");
        }

        // Fetch menus
        $menuStmt = $db->prepare("SELECT `id` FROM `menus` WHERE `is_a_parent_id`  = 'Yes' 
");
        if (!$menuStmt->execute()) {
            throw new Exception("Failed to fetch menus.");
        }

        $menus = $menuStmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$menus) {
            throw new Exception("No top-level menus found.");
        }

        // Insert permissions
        $permStmt = $db->prepare("
            INSERT INTO `user_permissions` 
            (`employee_id`, `menu_id`, `can_backdate`, `can_edit`, `can_delete`) 
            VALUES 
            (:employee_id, :menu_id, 0, 0, 0)
        ");

        foreach ($menus as $menu) {
            $permStmt->bindValue(':employee_id', $employee_id);
            $permStmt->bindValue(':menu_id', $menu['id']);

            if (!$permStmt->execute()) {
                throw new Exception("Failed to assign permissions for menu ID {$menu['id']}.");
            }
        }

        $db->commit();

        return ["status" => "success", "message" => "User created and permissions assigned."];

    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        return ["status" => "error", "message" => $e->getMessage()];
    } finally {
        $insertStmt = $menuStmt = $permStmt = $checkStmt = null;
    }
}




protected function UpdateData($employee_id, $user_name, $password, $status, $branch_id, $login_start, $login_end,$role, $related_id) {
    try {
        $db = $this->connect();
        if (!$db) {
            throw new Exception("Database connection failed.");
        }

        // Check for duplicate user_name except current user
        $checkStmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM `users` 
            WHERE `user_name` = :user_name 
              AND `id` <> :related_id
        ");
        $checkStmt->bindValue(':user_name', $user_name);
        $checkStmt->bindValue(':related_id', $related_id);
        $checkStmt->execute();

        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($result && $result['count'] > 0) {
            return ["status" => "error", "message" => "Duplicate user name."];
        }

        // Begin transaction
        $db->beginTransaction();
date_default_timezone_set('Asia/Dhaka');

        $stmt = $db->prepare("
            UPDATE `users` 
            SET 
                `employee_id` = :employee_id, 
                `user_name` = :user_name, 
                `hash_pass` = :hash_pass, 
                `status` = :status, 
                `branch_id` = :branch_id, 
                `login_start` = :login_start, 
                `login_end` = :login_end,
                `role` = :role
            WHERE `id` = :related_id
        ");

        $hash_pass = password_hash($password, PASSWORD_BCRYPT);

        // Bind parameters
        $stmt->bindValue(':employee_id', $employee_id);
        $stmt->bindValue(':user_name', $user_name);
        $stmt->bindValue(':hash_pass', $hash_pass);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':branch_id', $branch_id);
        $stmt->bindValue(':login_start', $login_start);
        $stmt->bindValue(':login_end', $login_end);
        $stmt->bindValue(':role', $role);
        $stmt->bindValue(':related_id', $related_id);

        if (!$stmt->execute()) {
            throw new Exception("Failed to update user.");
        }

        $db->commit();

        return ["status" => "success", "message" => "User updated successfully."];

    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        return ["status" => "error", "message" => $e->getMessage()];
    } finally {
        $stmt = $checkStmt = null;
    }
}





   public function DeleteItem($userId,$employeeId) {
    $pdo = null;

    try {
        // Step 1: Connect to the database
        $pdo = $this->connect();
        if (!$pdo) {
            throw new Exception("Database connection failed.");
        }

        // Step 2: Start transaction
        $pdo->beginTransaction();

       

        // Step 4: Delete the users
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :userId");
        if (!$stmt->execute([':userId' => $userId])) {
            throw new Exception("Failed to delete item from invoice.");
        }

        // Step 5: Recalculate user_permissions total
        $stmt = $pdo->prepare("DELETE FROM user_permissions WHERE employee_id = :employeeId");
        if (!$stmt->execute([':employeeId' => $employeeId])) {
            throw new Exception("Failed to delete item from invoice.");
        }


        // Step 8: Commit the transaction
        $pdo->commit();

        return ["status" => "success", "message" => "User delete  successfully."];

    } catch (Exception $e) {
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ["status" => "error", "message" => "Error: " . $e->getMessage()];
    }
}



    public function ListData(){
        $stmt = $this->connect()->prepare('SELECT A.*,B.name FROM users A JOIN employees B ON (A.employee_id = B.id)  ');
        $stmt->execute();
        $Data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = null;
        return  $Data;
       
    }

    public function SingleData($id) {
        $stmt = $this->connect()->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT); 
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch single record
    
        return $data ?: null; // Return null if no record found
    }
    
 
    function getUserMenus($employee_id) {
  // Fetch menu items for the given user
  $stmt = $this->connect()->prepare("
  SELECT m.id, m.parent_id, m.menu_name, m.menu_link, m.icon
  FROM menus m
  INNER JOIN user_permissions up ON m.id = up.menu_id
  WHERE up.employee_id = :employee_id
  ORDER BY m.sort_order
");
$stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
$stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function getUserSingleMenus($employee_id,$menuId) {
        // Fetch menu items for the given user
        $stmt = $this->connect()->prepare("
        SELECT m.id, m.parent_id, m.menu_name, m.menu_link, m.icon
        FROM menus m
        INNER JOIN user_permissions up ON m.id = up.menu_id
        WHERE up.employee_id = :employee_id AND up.menu_id = :menuId
        ORDER BY up.menu_id
      ");
      $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
      $stmt->bindParam(':menuId', $menuId, PDO::PARAM_INT);

      $stmt->execute();
              return $stmt->fetchAll(PDO::FETCH_ASSOC);
          }
      

    function AllMenu() {
        // Fetch menu items for the given user
        $stmt = $this->connect()->prepare("
        SELECT *
        FROM menus 
        WHERE is_a_parent_id  = 'Yes' 
        ORDER BY sort_order
      ");
      $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
          }
      

          function MenuByParent($parent_id) {
            // Fetch menu items for the given user
            $stmt = $this->connect()->prepare("
             SELECT m.id, m.parent_id, m.menu_name, m.menu_link, m.icon
        FROM menus m
        WHERE m.parent_id = :parent_id
        ORDER BY m.id
          ");
          $stmt->bindParam(':parent_id', $parent_id, PDO::PARAM_INT);
    
          $stmt->execute();
                  return $stmt->fetchAll(PDO::FETCH_ASSOC);
              }
          


public function addUserPermission($employee_id, $menu_id, $permission_type, $status) {
    $pdo = $this->connect();

    // Check if permission row already exists
    $stmt = $pdo->prepare("SELECT id FROM user_permissions WHERE employee_id=? AND menu_id=?");
    $stmt->execute([$employee_id, $menu_id]);

    if ($stmt->rowCount() > 0) {

    $stmt = $pdo->prepare("DELETE FROM `user_permissions` WHERE `employee_id` = :employee_id AND `menu_id` = :menu_id");
    $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
    $stmt->bindParam(':menu_id', $menu_id, PDO::PARAM_INT);
    $stmt->execute();

    return ["status" => "success", "message" => "Menu permission removed"];

                    

    } else {
        // Insert new record
        $sql = "INSERT INTO user_permissions (employee_id, menu_id, can_view) 
                VALUES (?, ?, ?)";
        $insert = $pdo->prepare($sql);
        return $insert->execute([$employee_id, $menu_id, $status]);
    }
}



             public function getUserPermissions($employee_id, $menu_link) {

        $query = "SELECT A.can_backdate, A.can_edit, A.can_delete FROM user_permissions A
        JOIN menus B ON (A.menu_id = B.id )
                  WHERE A.employee_id = ? AND B.menu_link = ?";
        $stmt = $this->connect()->prepare($query);
        $stmt->execute([$employee_id, $menu_link]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


            
}
