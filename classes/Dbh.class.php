<?php

class Dbh {
    protected function connect() {
        try {
            //inventory
          $username = 'osudlagb_mudaraba';  // Your database username
           $password = 'QI~]0J*Z(_1,';      // Your database password

         //$username = 'root'; 
           //$password = ''; 
            $dbh = new PDO('mysql:host=localhost;dbname=osudlagb_INVManagement', $username, $password);
            // Set PDO error mode to exception
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $dbh;
        } catch (PDOException $e) {
            // Print error message and terminate script
            die("Error! - " . $e->getMessage());
        }
    }


    static function checkAdmin($ID) {
        $dbh = new self();
        $pdo = $dbh->connect();
        
        $lc_fetch = $pdo->prepare("SELECT `id` FROM `admin` WHERE `user_type` = 'Admin' and id = ?");
        $lc_fetch->execute([$ID]);
        if ($lc_fetch->rowCount() > 0) {
            return true ; 
        }else{
            return false ; 
        }


    }
 

   
}
