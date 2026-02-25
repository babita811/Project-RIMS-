<?php
session_start();
var_dump($_SESSION);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Student Signup Form</title>
    <style>
        label::after {
            content: " *";
            color: red;
            font-weight: bold;
        }

        label[for="agree"]::after {
            content: "";
        }

        .checkbox-group label::after {
            content: "";
        }

        .error {
            color: red;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>

    <h1>Student Registration Form</h1>

    <form method ="POST" action ="register.php" id="signupForm">
        <div class="error">
            <?php
            if(isset($_SESSION['error'])){
                foreach($_SESSION['error'] as $error){
                    echo $error;
                    echo "<br>";
                }
            }
            ?>
        </div>
        <label for="username">Username</label>
        <input type="text" id="username" name = "username">
        <br>
        <label for="email">Email</label>
        <input type="text" id="email" name = "email">
        <br>
        <label for="dob">Date of Birth</label>
        <input type="date" id="dob"  name = "dob" placeholder="YYYY-MM-DD">
        <br>
        <label>Gender</label>
        <div class="radio-group">
            <label><input type="radio" name="gender" value="male"> Male</label>
            <label><input type="radio" name="gender" value="female"> Female</label>
        </div>
        <br>
        <label for="contact">Contact Number</label>
        <input type="text" id="contact" name ="contact">
        <br>
        <label for="faculty">Faculty</label>
        <select id="faculty" name="faculty">
            <option value="">-- Select Faculty --</option>
            <option value="engineering">Engineering</option>
            <option value="medicine">Medicine</option>
            <option value="business">Business</option>
            <option value="arts">Arts & Humanities</option>
            <option value="science">Science</option>
        </select>
        <br>
        <label for="hobbies">Hobbies</label>
        <div class="checkbox-group">
            <label><input type="checkbox" id="hobby-reading" name="hobbies[]" value="Reading"> Reading</label>
            <label><input type="checkbox" id="hobby-sports" name="hobbies[]" value="Sports"> Sports</label>
            <label><input type="checkbox" id="hobby-music" name="hobbies[]" value="Music"> Music</label>
            <label><input type="checkbox" id="hobby-travel" name="hobbies[]" value="Travel"> Travel</label>
            <label><input type="checkbox" id="hobby-others"  name="hobbies[]" value="Others"> Others</label>
            <br><br>

        </div>
        <label for="agree">
            <input type="checkbox" id="agree" name="agree"> I agree to the terms and conditions
        </label>
        <br>
        <input type="submit" name="submit"value="submit">
    </form>

    
</body>

</html>