<?php

@include 'config.php';

if (isset($_POST['add_to_cart']) || isset($_POST['add_to_wishlist'])) {
    if (!isset($user_id)) {
        header('location:login.php');
    }
}
if (isset($_POST['add_to_wishlist'])) {

    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];
    $product_image = $_POST['product_image'];

    $check_wishlist_numbers = mysqli_query($conn, "SELECT * FROM `wishlist` WHERE name = '$product_name' AND user_id = '$user_id'") or die('query failed');

    $check_cart_numbers = mysqli_query($conn, "SELECT * FROM `cart` WHERE name = '$product_name' AND user_id = '$user_id'") or die('query failed');

    if (mysqli_num_rows($check_wishlist_numbers) > 0) {
        $message[] = 'already added to wishlist';
    } elseif (mysqli_num_rows($check_cart_numbers) > 0) {
        $message[] = 'already added to cart';
    } else {
        mysqli_query($conn, "INSERT INTO `wishlist`(user_id, pid, name, price, image) VALUES('$user_id', '$product_id', '$product_name', '$product_price', '$product_image')") or die('query failed');
        $message[] = 'product added to wishlist';
    }
}

if (isset($_POST['add_to_cart'])) {

    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];
    $product_image = $_POST['product_image'];
    $product_quantity = $_POST['product_quantity'];

    $check_cart_numbers = mysqli_query($conn, "SELECT * FROM `cart` WHERE name = '$product_name' AND user_id = '$user_id'") or die('query failed');

    if (mysqli_num_rows($check_cart_numbers) > 0) {
        $message[] = 'already added to cart';
    } else {

        $check_wishlist_numbers = mysqli_query($conn, "SELECT * FROM `wishlist` WHERE name = '$product_name' AND user_id = '$user_id'") or die('query failed');

        if (mysqli_num_rows($check_wishlist_numbers) > 0) {
            mysqli_query($conn, "DELETE FROM `wishlist` WHERE name = '$product_name' AND user_id = '$user_id'") or die('query failed');
        }

        mysqli_query($conn, "INSERT INTO `cart`(user_id, pid, name, price, quantity, image) VALUES('$user_id', '$product_id', '$product_name', '$product_price', '$product_quantity', '$product_image')") or die('query failed');
        $message[] = 'product added to cart';
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>search page</title>

    <!-- font awesome cdn link  -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- custom admin css file link  -->
    <link rel="stylesheet" href="css/style.css">

</head>

<body>

    <?php @include 'header.php'; ?>

    <section class="heading">
        <!-- <h3>search page</h3> -->
        <p> <a href="home.php">home</a> / search </p>
    </section>

    <section class="search-form">
        <form action="" method="POST">
            <input type="text" class="box" placeholder="search products..." name="search_box">
            <input type="submit" class="btn" value="search" name="search_btn">
        </form>
    </section>
      <section class="products">
          <h1 class="title">Search Results</h1>
          <div class="box-container">
              <?php
              @include 'config.php';
              if (isset($_POST['search_btn'])) {
                  $search_box = mysqli_real_escape_string($conn, $_POST['search_box']);
                  $select_products = mysqli_query($conn, "SELECT * FROM `products` WHERE name LIKE '%{$search_box}%'") or die('query failed');
                  if (mysqli_num_rows($select_products) > 0) {
                      while ($fetch_products = mysqli_fetch_assoc($select_products)) {
                          $product_id = $fetch_products['id'];
                          $product_name = $fetch_products['name'];
                          $product_price = $fetch_products['price'];
                          $product_image = $fetch_products['image'];
              ?>
                      <div class="box">
                          <img src="uploaded_img/<?php echo $product_image; ?>" alt="" class="image">
                          <div class="name" style="font-weight: bolder;"><?php echo $product_name; ?></div>
                          <div class="price">Rs.<?php echo $product_price; ?>/-</div>
                          <a href="view_page.php?pid=<?php echo $product_id; ?>" class="btn">View Details</a>
                      </div>
              <?php
                      }
                  } else {
                      echo '<p class="empty">No products found!</p>';
                  }
              } else {
                  echo '<p class="empty">search something!</p>';
              }
              ?>
          </div>
      </section>




    <?php @include 'footer.php'; ?>

    <script src="js/script.js"></script>

</body>

</html>