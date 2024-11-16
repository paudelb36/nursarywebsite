<!-- register.php -->
<?php
@include 'config.php';

if (isset($_POST['submit'])) {
    // Modern input sanitization using htmlspecialchars instead of deprecated FILTER_SANITIZE_STRING
    $username = htmlspecialchars(trim($_POST['username']), ENT_QUOTES, 'UTF-8');
    $username = mysqli_real_escape_string($conn, $username);
    
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $email = mysqli_real_escape_string($conn, $email);
    
    $address = htmlspecialchars(trim($_POST['address']), ENT_QUOTES, 'UTF-8');
    $address = mysqli_real_escape_string($conn, $address);
    
    $number = htmlspecialchars(trim($_POST['number']), ENT_QUOTES, 'UTF-8');
    $number = mysqli_real_escape_string($conn, $number);
    
    // Use password_hash for secure password storage
    $pass = password_hash($_POST['pass'], PASSWORD_DEFAULT);
    $cpass = $_POST['cpass'];

    $select_users = mysqli_query($conn, "SELECT * FROM `users` WHERE email = '$email'") or die('query failed');

    if (mysqli_num_rows($select_users) > 0) {
        $message[] = 'User already exists!';
    } else {
        // Simple password length check
        if (strlen($_POST['pass']) < 4) {
            $message[] = 'Password must be at least 4 characters!';
        } 
        // Check if passwords match
        else if ($_POST['pass'] != $_POST['cpass']) {
            $message[] = 'Confirm password does not match!';
        } else {
            // Insert using prepared statement
            $insert_query = "INSERT INTO `users`(username, email, password, address, number) 
                           VALUES(?, ?, ?, ?, ?)";
                           
            $stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt, "sssss", $username, $email, $pass, $address, $number);
            
            if (mysqli_stmt_execute($stmt)) {
                $message[] = 'Registered successfully!';
                header('location:login.php');
                exit;
            } else {
                $message[] = 'Registration failed! Please try again.';
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php
    if (isset($message)) {
        foreach ($message as $msg) {
            echo '
            <div class="message">
                <span>' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</span>
                <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
            </div>
            ';
        }
    }
    ?>

    <section class="form-container">
        <form action="" method="post" onsubmit="return validateForm()">
            <h3>Register Now</h3>
            <input type="text" name="username" class="box" placeholder="Enter your username" required>
            <input type="email" name="email" class="box" placeholder="Enter your email" required>
            <input type="text" name="address" class="box" placeholder="Enter your address" required>
            <input type="text" name="number" class="box" placeholder="Enter your phone number" required>
            <input type="password" name="pass" class="box" placeholder="Enter your password" required minlength="4">
            <input type="password" name="cpass" class="box" placeholder="Confirm your password" required minlength="4">
            <input type="submit" class="btn" name="submit" value="Register Now">
            <p>Already have an account? <a href="login.php">Login Now</a></p>
        </form>
    </section>

    <script>
    function validateForm() {
        const pass = document.querySelector('input[name="pass"]').value;
        const cpass = document.querySelector('input[name="cpass"]').value;
        
        if (pass.length < 4) {
            alert('Password must be at least 4 characters!');
            return false;
        }
        
        if (pass !== cpass) {
            alert('Passwords do not match!');
            return false;
        }
        
        return true;
    }
    </script>
</body>
</html>