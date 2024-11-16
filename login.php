<!-- login.php  -->
<?php
@include 'config.php';
session_start();

if(isset($_POST['submit'])){
   $email = mysqli_real_escape_string($conn, $_POST['email']);
   $input_pass = $_POST['pass'];
   
   // Check user login with password_verify()
   $select_users = mysqli_query($conn, "SELECT * FROM `users` WHERE email = '$email'") or die('query failed');
   
   if(mysqli_num_rows($select_users) > 0){
      $row = mysqli_fetch_assoc($select_users);
      if(password_verify($input_pass, $row['password'])) {
         $_SESSION['user_name'] = $row['username'];
         $_SESSION['user_email'] = $row['email'];
         $_SESSION['user_id'] = $row['id'];
         header('location:home.php');
         exit();
      }
   }

   // Admin check with plain text password
   $select_admin = mysqli_query($conn, "SELECT * FROM `admin` WHERE email = '$email' AND password = '$input_pass'") or die('query failed');
   
   if(mysqli_num_rows($select_admin) > 0){
      $row = mysqli_fetch_assoc($select_admin);
      $_SESSION['admin_name'] = $row['username'];
      $_SESSION['admin_email'] = $row['email'];
      $_SESSION['admin_id'] = $row['id'];
      header('location:admin_page.php');
      exit();
   }
   
   $message[] = 'Incorrect email or password!';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Login</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

   <!-- custom css file link  -->
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
      <form action="" method="post">
         <h3>Login Now</h3>
         <input type="email" name="email" class="box" placeholder="Enter your email" required>
         <input type="password" name="pass" class="box" placeholder="Enter your password" required>
         <input type="submit" class="btn" name="submit" value="Login Now">
         <p>Don't have an account? <a href="register.php">Register Now</a></p>
      </form>
   </section>

</body>
</html>