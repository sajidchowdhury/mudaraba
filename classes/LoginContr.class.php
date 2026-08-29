<?php 
class LoginContr extends Login {

  use SharedFunctionalityTrait;

  private $user_name ; 
  private $login_password ; 



  public function __construct( $user_name, $login_password){
  
    $this->user_name = $user_name;
    $this->login_password = $login_password;

  }

       

  public function LoginAdmin() {
date_default_timezone_set('Asia/Dhaka');


   if (
       !$this->clean($this->user_name)
       || !$this->clean($this->login_password)
       )
    {

       header("Location: ../login.php?mess=mess1");
       exit();
   }


 
    $result = $this->getAdmin($this->user_name, $this->login_password);


    if ($result['mess'] === 'ACTION_REQUIRED') {

    if (!isset($_SESSION)) {
        session_start();
        }


    $_SESSION['admin_access_token'] = $result['session_key']['id'];
    $_SESSION['employee_id'] = $result['session_key']['employee_id'];
    $_SESSION['brunch_id'] = $result['session_key']['branch_id'];
    $_SESSION['admin_access_name'] = $result['session_key']['EmployeeName'];
    $_SESSION['logintime'] = $result['session_key']['login_start'];
    $_SESSION['logouttime'] = $result['session_key']['login_end'];
    $_SESSION['role'] = $result['session_key']['role'];

       header('Location: ../dynamic-page.php?page=Home');
       exit();
    
    }else {

       header('Location: ../login.php?mess=mess'.$result['mess'].'');
       exit();
    }
      

 }




} // end of class