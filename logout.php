<?php
session_start();

// Check if the user was an admin before destroying session
$was_admin = isset($_SESSION['admin_id']);

// Destroy the session
session_unset();
session_destroy();

// Redirect based on previous user type
if($was_admin){
    header('location:login.php');
} else {
    header('location:home.php');
}
exit();
?>