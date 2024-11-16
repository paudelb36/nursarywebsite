<?php
session_start();

// Get user data from database if logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $select_user = mysqli_query($conn, "SELECT * FROM `users` WHERE id = '$user_id'") or die('query failed');
    if(mysqli_num_rows($select_user) > 0) {
        $fetch_user = mysqli_fetch_assoc($select_user);
        $_SESSION['username'] = $fetch_user['username']; // Update session with current username
    }
}
?>

<header class="header">
    <div class="flex">
        <a href="home.php" class="logo"><i class="fa-solid fa-crown"></i> WearIt.</a>
          <nav class="navbar">
              <ul>
                  <li><a href="home.php">home</a></li>
                  <li><a href="shop.php">shop</a></li>
                  <?php if (isset($_SESSION['user_id'])) { ?>
                      <li><a href="orders.php">orders</a></li>
                  <?php } ?>
                  <li><a href="about.php">about</a></li>
                  <li><a href="contact.php">contact</a></li>
              </ul>
          </nav>

          <div class="icons">
              <div id="menu-btn" class="fas fa-bars"></div>
              <a href="search_page.php" class="fas fa-search"></a>
              <div id="user-btn" class="fas fa-user"></div>
            
              <?php if (isset($_SESSION['user_id'])) { ?>
                 

                  <?php
                  $user_id = $_SESSION['user_id'];
                  $select_cart_count = mysqli_query($conn, "SELECT * FROM `cart` WHERE user_id = '$user_id'") or die('query failed');
                  $cart_num_rows = mysqli_num_rows($select_cart_count);
                  ?>
                  <a href="cart.php"><i class="fas fa-shopping-cart"></i><span>(<?php echo $cart_num_rows; ?>)</span></a>
              <?php } ?>
          </div>
            <?php if (isset($_SESSION['user_id'])) { ?>
                <div class="account-box">
                    <p>username : <span><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest'; ?></span></p>
                    <a href="logout.php" class="delete-btn">logout</a>
                </div>
            <?php } else { ?>
                <div class="account-box">
                    <a href="login.php" class="delete-btn">login</a>
                    <a href="register.php" class="delete-btn">register</a>
                </div>
            <?php } ?>
        </div>
    </div>
</header>

<?php
if (isset($message)) {
    foreach ($message as $messageText) {
        echo '
        <div class="message">
            <span>' . htmlspecialchars($messageText) . '</span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
        </div>
        ';
    }
}
?>