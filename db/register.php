<?php 
session_start();
var_dump($_SESSION);
$error = [];
if (isset($_POST) && !empty($_POST)) {
    if($_POST["submit"]== "submit"){
        // echo "Form Submitted";
        if(empty( $_POST['username'])){
            // echo "Username is empty";
            $error["username"] = "Username is required";
        }elseif( strlen($_POST["username"]) < 8){
            $error["username"] = "Username Must be 8 character long";
        }

        if(empty( $_POST['email'])){
            // echo "email is empty";
            $error["email"] = "Email is required";
        }elseif(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){
            $error['email']= "Please enter valid email";
        }

        if(empty( $_POST['dob'])){
            // echo "dob is empty";
            $error["dob"] = "DOB is required";
        }else{
            $pattern = "/^\d{2}\/\d{2}\/\d{4}$/";
            if(!preg_match($pattern, $_POST['dob'])){
                $error["dob"] = "DOB must follow dd/mm/yyyy pattern";
            }
        }

        if(empty( $_POST['gender'])){
            // echo "email is empty";
            $error["gender"] = "Gender is required";
        }

        if(empty( $_POST['contact'])){
            // echo "email is empty";
            $error["contact"] = "Contact is required";
        }else{
            $phone = $_POST['contact'];
            $pattern ='/^(98|97|96)[0-9]{8}$/';

            if(strlen($phone) !== 10){
                $error["contact"] = "Phone number must be 10 digit long";
            }elseif(!preg_match($pattern, $phone)){
                $error["contact"] = "Phone number must start with 98|97|96";
            }
        }

         if(empty( $_POST['faculty'])){
            // echo "email is empty";
            $error["faculty"] = "Faculty is required";
        }
         if(empty( $_POST['hobby'])){
            // echo "email is empty";
            $error["hobby"] = "Hobby is required";
        }
         if(empty( $_POST['agree'])){
            // echo "email is empty";
            $error["agree"] = "Please agree the terms and conditions";
        }

        if(!empty($error)){
            $_SESSION['error'] = $error;
            header("location:form.php");
        }else{
            unset($_SESSION['error']);
            $conn = new mysqli("localhost","root","","user_registration");
            if($conn->connect_error){
                die("Connection Failed: ". $conn->connect_error);   
            }
            $username = $_POST['username'];
            $email = $_POST['email'];
            $dob = $_POST['dob'];
            $gender = $_POST['gender'];
            $contact = $_POST['contact'];
            $faculty = $_POST['faculty'];
            $hobby = implode(",", $_POST['hobby']);
            $sql = "INSERT INTO users (username, email, dob, gender, contact, faculty, hobby) VALUES ('$username', '$email', '$dob', '$gender', '$contact', '$faculty', '$hobby')";
            if($conn->query($sql) === TRUE){
                $_SESSION['username'] = $username;
                header("location:dashboard.php");
            }else{
                echo "Error: ". $sql . "<br>" . $conn->error;
            }
            $conn->close();
        }
    }
}else{
    echo "<a href='form.php'>Please Register First </a>";
}
