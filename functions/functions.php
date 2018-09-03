<?php

function clean($string){
    return htmlentities($string);
  
}
function redirect($location){
    return header("Location: {$location}");
}
function set_message($message){
    if(!empty($message)){
        $_SESSION['message'] = $message;
    }else{
        $message = "";
    }
    
}

function display_message(){
    
    if(isset($_SESSION['message'])){
        echo $_SESSION['message'];
        unset ($_SESSION['message']);
    }
}
function send_email($email,$subject,$msg,$headers){
  return mail($email,$subject,$msg,$headers);
    
}

function token_generator(){
   $token = $_SESSION['token']= md5(uniqid(mt_rand(), TRUE));
   return $token;
}
/**validation functions**/

function validate_user_registration(){
    $errors =[];
    $min = 3;
    $max = 40;
    if($_SERVER['REQUEST_METHOD'] =="POST"){
        $first_name         =   clean($_POST['first_name']);
        $last_name          =   clean($_POST['last_name']); 
        $username           =   clean($_POST['username']); 
        $email              =   clean($_POST['email']); 
        $password           =   clean($_POST['password']);
        $confirm_password   =   clean($_POST['confirm_password']);
     if(strlen($first_name) < $min ){
         $errors[] = "your first name cannot be less than {$min} characters.";
         
     }
     if(strlen($last_name) < $min ){
         $errors[] = "your last name cannot be less than {$min} characters.";
         
     }
     if(strlen($username) < $min ){
         $errors[] = "your username cannot be less than {$min} characters.";
         
     }
     if(username_exists($username)){
          $errors[] = "username taken.";
     }
     if(email_exists($email)){
          $errors[] = "email taken.";
     }
     if(strlen($email) > $max ){
         $errors[] = "your email cannot be greater than {$max} characters.";
         
     }
     if(strlen($password) < $min ){
         $errors[] = "your last name cannot be less than {$min} characters.";
         
     }
     if($password !==$confirm_password){
          $errors[] = "Your passwords do not match";
     }
     if(!empty($errors)){
         foreach ($errors as $error){
             echo $error;
         }
         
     }else{
         if(register_user($first_name, $last_name,$username,$email,$password)){
             set_message("<p class= 'bg-success text-center'> Please check your email for an activation link <p/>");
             
             echo 'user registered';
             redirect("index.php");
         }else{
            set_message("<p class= 'bg-danger text-center'> Sorry we couldnt register the user <p/>");
             
            echo 'user registered';
            redirect("index.php");
         }
     }
    }
}


function email_exists($email){
    $sql= "SELECT id FROM users WHERE email= '{$email}'";
    $result=  query($sql);
    if(row_count($result)==1 ){
        return TRUE;
        
    }else{
        return FALSE;
    }
}


function username_exists($username){
    $sql= "SELECT id FROM users WHERE username = '{$username}'";
    $result=  query($sql);
    if(row_count($result)==1 ){
        return TRUE;
        
    }else{
        return FALSE;
    }
}
//register user section
function register_user($first_name, $last_name,$username,$email,$password){
   $first_name  =  escape($first_name);
   $last_name   =  escape($last_name);
   $username    =  escape($username);
   $email       =  escape($email);
   $password    =  escape($password);
   
    if(email_exists($email)){
        return FALSE;
        
    }else if(username_exists($username)){
         return FALSE;
    }else{
        $password= md5($password);
        $validation_code= md5($username + microtime());
        
        $sql= "INSERT INTO users(first_name,last_name,username,email,password,validation_code,active) "
                . "VALUES ('$first_name','$last_name','$username','$email','$password','$validation_code',0)";
        
        $result=query($sql);
        confirm($result);
       $subject= "Activate Account";
       $msg = "Please click the link below to activate Account
       http://localhost/login/activate.php?email=$email&code=$validation_code";
       $header = "From: noreply@mywebsite.com";
       
       
        send_email($email,$subject,$msg,$headers);
        
        return TRUE;
    }
}

function activate_user(){
    if($_SERVER['REQUEST_METHOD']=="GET"){
     if(isset($_GET['email'])){
        $email= clean($_GET['email']);
        $validation_code=  clean($_GET['code']);
        $sql= "SELECT id FROM users WHERE email= '".escape($_GET['email'])."'AND validation_code = '".escape($_GET['validation_code'])."'";
        $result= query($sql);
        confirm($result);

        if(row_count($result)==1){
        echo "<p class 'bg-success'> Your account has been activated please login</p>";
        $sql2 =("UPDATE users SET active= 1,validation_code= 0  
        WHERE email= '".escape($email)."'AND 
        validation_code='".escape($validation_code)."'");
             $result2=query($sql2);
             confirm($result2);
             set_message("<p class='bg-success'>Your account has been activated</p>");
             redirect("login.php");
             
        }else{
            set_message("<p class='bg-danger'>Your account verification  is unsuccessful.Kindly reactivate</p>");
             redirect("login.php");
        }
     }  
        
    }
}
//function validate user login

function validate_user_login(){
    $errors =[];
    $min = 3;
    $max = 40;
    if($_SERVER['REQUEST_METHOD'] =="POST"){
       
        $email              =   clean($_POST['email']); 
        $password           =   clean($_POST['password']);
        $remember           =   isset($_POST['remember']);

    if(empty($email)){
        $errors[]="Email cannot be empty";
    }
    if(empty($password)){
        $errors[]="password cannot be empty";
    }
        
     if(!empty($errors)){
         foreach ($errors as $error){
             echo $error;
         }
         
     }else{
        if(login_user($email, $password,$remember)){
            redirect("admin.php");
     }else{
         echo "Your Credentials coudnt be verified";
     }
    }
}}

/*******user login */
function login_user($email, $password, $remember){
    $sql= "SELECT password, id FROM users WHERE email= '".escape($email)."' AND active= 1";
    $result=query($sql);
        if(row_count($result)==1){
            $row= fetch_array($result);
            $db_password=$row['password'];

            if(md5($password)===$db_password){
                if($remember=="on"){
                    setcookie('email', $email, time() + 300);//loged in for 5 mins
                }
                $_SESSION['email']=$email;
                
                return true;
            }

            return true;
        }else
        {
        return false;
    }

}//end of function 

/***Logged in function */
function logged_in(){
    if(isset($_SESSION['email']) || isset($_COOKIE['email'])){
        return true;
    }else{
        return false;
    }
}

/**recover password */
function recover_password(){

    if ($_SERVER['REQUEST_METHOD']=="POST"){

        if(isset($_SESSION['token'])&& $_POST['token']=== $_SESSION['token']){

                $email= clean($_POST['email']);

                if(email_exists($email)){

                        $validation_code= md5($email + microtime());

                        setcookie('temp_access_code', $validation_code,time() +60);

                       $sql="UPDATE users SET validation_code='"
                       .escape($validation_code)."'WHERE email= '".escape($email)."'";
                       $result=query($sql);
                       
                       
                       $subject="Please reset your password";                       
                        $message="Here is your password reset code{$validation_code}

                        Click here to reset your password http://localhost/code.php? email=$email&code=$validation_code";
                        
                        $headers= "From: no reply@mywebsite.com";
                       
                        if(!send_email($email,$subject,$message,$headers)){

                            echo "email couldnt be send";

                                 }}

                    set_message("<p class='bg-success'>Please check your email for a reset link</p>");
                    redirect("index.php");
                    }else{

                        echo "This email doesnt exist";
                    }
             
        } else{
            redirect("index.php");
        
    }
//token checks
if (isset($_POST['cancel_submit'])){
  redirect("login.php");  
}
}//post request

/**code validation */
function  validate_code(){
    if(isset($_COOKIE['temp_access_code'])){
        
        if(!isset($_GET['email']) && !isset($_GET['code'])){

        redirect("index.php");
            
        }else if(empty($_GET['email']) || empty($_GET['code'])){
            redirect("index.php");
        }else{
            if(isset($_POST['code'])){

               $email= clean($_GET['email']);
        
               $validation_code=clean($_POST['code']);
               $sql="SELECT id FROM users WHERE validation_code='"
               .escape($validation_code)."' AND email = '".escape($email)."'";
            $result=query($sql);
            if(row_count($result)==1){
                setcookie('temp_access_code', $validation_code,time() + 180);
           redirect("reset.php?email=$email&code=$validation_code");     
            }else{
                echo "wrong validation code";
            }
            }
        }

    }else{
        set_message("<p class='bg-danger text-center'>Sorry ths code is invalid </p>");
        redirect("recover.php");
    }
}
/**password reset function */
function password_reset(){
        if(isset($_COOKIE['temp_access_code'])){

            if(isset($_GET['email']) && isset($_GET['code'])){
                
                if(isset($_SESSION['token']) && isset($_POST['token'])){
                        if($_POST['token']=== $_SESSION['token']){
                            if($_POST['password']=== $_POST['confirm_password']){
                                $updated_password=md5($_POST['password']);
                            
                           $sql="UPDATE users SET password='".escape($updated_password)."' ,validation_code= 0
                           WHERE email='".escape($_GET['email'])."'";
                           $query($sql);
                           set_message("<p class='bg-primary text-center'>Your password has been updated </p>");
                           redirect("login.php");
                        }
                 }
            
                }
        
        }

        
        } else{
            set_message("<p class='bg-danger'>Sorry that link has expired</p>");
            redirect("recover.php");
            }
        }
    
    