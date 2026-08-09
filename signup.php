<?php
include "dbcon.php";
if(isset($_POST['sign-up']))
{
    $name = $_POST['Name'];
    $email = $_POST['Email'];
    $phone = $_POST['Phone'];
    $address = $_POST['Address'];
    $photo=$_FILES['file_upload']['name'];
    $tempname=$_FILES['file_upload']['tmp_name'];
    $folder="product/".$photo;
    move_uploaded_file($tempname, $folder);

    $sql = "INSERT INTO student(full_name, email, phone, address) VALUES ('$name', '$email', '$phone', '$address')";
    $query = mysqli_query($conn, $sql);
    
    if($query){
        echo "<div class='alert alert-success text-center mt-3'>DATA INSERTED...</div>";
    } else {
        echo "<div class='alert alert-danger text-center mt-3'>Error: ".mysqli_error($conn)."</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edu-Core Sign Up</title>
</head>
<body>
     <div class="login-signup-contact-ka-same-box" style="height: 400px;">
        <h1>Signup Now</h1>
        <form method="POST">
            <input type="text" name="fullname" placeholder="Enter Full Name"><br>
            <input type="text" name="email" placeholder="Enter Email"><br>
            <input type="password" name="password" placeholder="Create Password"><br>
            <input type="password" name="confirm-password" placeholder="Confirm Password"><br>
            <input type="submit" name="signup" value="Signup">
        </form>
        <br>
        <span>Already have an account?</span>
        <br>
        <a href="index.php">Login Here</a>
    </div>
</body>
</html>