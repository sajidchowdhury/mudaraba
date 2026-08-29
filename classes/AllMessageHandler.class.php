<?php 
class AllMessageHandler {


    private $mess ;

 public function __construct ($mess){
           
    $this->mess = $mess;
        
 }


 public function ShowMess(){


        switch ($this->mess) {

            case 'mess1':
                return "Please fill-up with valid data";
                break;
            case 'mess2':
                return "Not a valid email address";
                break;
            case 'mess5':
                return "Password not valid";
                break;
            case 'mess6':
                return "Password Not Matched";
                break;
            case 'mess7':
                return "User already Exists";
                break;
            case 'mess8':
                return "Invalid Data";
                break;
            case 'mess9':
                return "Registration Success Check Your mail";
                break;
            case 'mess10':
                return "Email Send Failed!";
                break;
            case 'mess11':
                return "This email address already with an account";
                break;
            case 'mess12':
                return "Signup Failed";
                break;
            case 'mess13':
                return "Too many login attempts";
                break;
            case 'mess14':
                return "Suspicious Login Attempt";
                break;
            case 'mess15':
                return "Token Is not Valid";
                break;
            case 'mess16':
                return "Now you can login";
                break;
            case 'mess17':
                return "Login Failed";
            case 'mess18':
                return "Login Query Failed";
                break;
            case 'mess19':
                return "Account Not verified. Please check your email.";
                break;
            case 'mess20':
                return "This user is blocked";
                break;   
            case 'mess21':
                return "User Name or Password Not Exists";
                break;   
            case 'mess22':
                return "This Email is not registered please signup again";
                break;    
            case 'mess23':
                return "This User is already registered please try another name";
                break;    
            case 'mess24':
                return "A Mail sent to your email . Please check your inbox or spam";
                break;   
            case 'mess99':
                return "login not allowed this time";
                break;    
            case 'mess25':
                return "More then Two mails sent.Please check your inbox or spam or Try another email";
                break;    
            case 'mess26':
                return "This account is verified";
                break;  
            case 'mess27':
                return "This account registered with Google";
                break;  
            case 'mess28':
                return "Password Change successfully. Try to login";
            case 'mess29':
            return "Account verified successfully. Try to login";
            break;  
            case 'mess30':
            return "Insert Success";
            break;  
            case 'mess31':
            return "Update Success";
            case 'mess32':
            return "Duplicate Entry";
            case 'mess33':
            return "OTP SMS balance is over . Please call developer";
            break;  
            break;  
            default:
                return "";
                break;
        }
  
 }
}