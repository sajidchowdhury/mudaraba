<?php

class Login extends Dbh {

    protected function getAdmin($user_name, $user_password) {


        
        date_default_timezone_set('Asia/Dhaka');
        $current_time = date("H:i:s");

        $stmt = $this->connect()->prepare('SELECT A.*,B.name as EmployeeName FROM users A JOIN employees B ON (A.employee_id = B.id ) WHERE A.user_name = :user_name');
        $stmt->bindParam(':user_name', $user_name);

        if (!$stmt->execute()) {
            return ['mess' => 18, 'session_key' => 'NOT_FOUND'];
        }

        if ($stmt->rowCount() === 0) {
            return ['mess' => 22, 'session_key' => 'NOT_FOUND'];
        }

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = null;


        // Check if user is blocked
        if ($user['status'] === 'Block') {
            return ['mess' => 20, 'session_key' => 'BLOCKED'];
        }

       if($user['role'] !== 'superadmin'){

            // Time check (office hour from DB)
            if ($current_time < $user['login_start'] || $current_time > $user['login_end']) {
            return ['mess' => 99, 'session_key' => 'NOT_ALLOWED_TIME'];
            }

       }
      
        // Password check
        if (password_verify($user_password, $user['hash_pass'])) {
            // Reset login attempts

            // Return session
            return ['mess' => 'ACTION_REQUIRED', 'session_key' => $user];
        } else {
            // Increase attempt count
            return ['mess' => 6, 'session_key' => 'NOT_FOUND'];
        }
    }







}
