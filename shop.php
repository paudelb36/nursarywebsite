<!-- //shop.php  -->
<?php
@include 'config.php';
session_start();

// Initialize $user_id as null
$user_id = null;

// Check if the user is logged in and if the user_id is set in the session
if (isset($_SESSION['user_id'])) {
   $user_id = $_SESSION['user_id'];
}

// Handle the filtering functionality
$gender_filter = isset($_POST['gender']) ? $_POST['gender'] : '';
$category_search = isset($_POST['categorySearch']) ? $_POST['categorySearch'] : '';
$category_filter = isset($_POST['category']) ? $_POST['category'] : '';
$min_price = isset($_POST['min_price']) ? $_POST['min_price'] : 0;
$max_price = isset($_POST['max_price']) ? $_POST['max_price'] : 10000;
$price_range = isset($_POST['price_range']) ? $_POST['price_range'] : 1000;

$select_products_query = "SELECT * FROM `products` WHERE price BETWEEN '$min_price' AND '$max_price'";

if ($gender_filter != '') {
   $select_products_query .= " AND gender = '$gender_filter'";
}
if ($category_filter != '') {
   $select_products_query .= " AND category = '$category_filter'";
}
if ($category_search != '') {
   $select_products_query .= " AND category LIKE '%$category_search%'";
}

$select_products = mysqli_query($conn, $select_products_query) or die('Query failed');
?>

<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Shop</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
</head>

<body>
   <?php @include 'header.php'; ?>
   <section class="heading">
      <p><a href="home.php">Home</a> / Shop</p>
   </section>

   <div id="filterOverlay" class="filter-overlay"></div>

   <!-- Filter Section -->
   <section class="filter-section">
      <button class="filter-btn" id="filterBtn">
         <i class="fas fa-filter"></i> Filter Products
      </button>
      <div class="filter-popup" id="filterPopup">
         <button class="close-filter-btn" id="closeFilterBtn">&times;</button>
         <form action="" method="POST" class="filter-form">
            <!-- Gender Filter -->
            <div>
               <!-- <label for="gender">Gender:</label> -->
               <select name="gender" id="gender">
                  <option value="">Select Gender</option>
                  <option value="male" <?php echo ($gender_filter == 'male') ? 'selected' : ''; ?>>Male</option>
                  <option value="female" <?php echo ($gender_filter == 'female') ? 'selected' : ''; ?>>Female</option>
                  <option value="unisex" <?php echo ($gender_filter == 'unisex') ? 'selected' : ''; ?>>Unisex</option>
               </select>
            </div>

            <!-- Category Search -->
            <div>
               <!-- <label for="categorySearch">Search Category:</label> -->
               <input type="text" name="categorySearch" id="categorySearch" placeholder="Search for category" value="<?php echo $category_search; ?>">
            </div>

            <!-- Price Range -->
            <div class="dual-slider">
               <label for="price-range">Price Range:</label>
               <div class="price-input-container">
                  <span>Min</span>
                  <input type="number" id="minPriceInput" name="min_price" value="<?php echo $min_price; ?>" min="0" max="10000">
                  <span>-</span>
                  <input type="number" id="maxPriceInput" name="max_price" value="<?php echo $max_price; ?>" min="0" max="10000">
                  <span>Max</span>
               </div>
               <div class="slider-containers">
                  <input type="range" id="minPriceRange" min="0" max="10000" step="10" value="<?php echo $min_price; ?>">
                  <input type="range" id="maxPriceRange" min="0" max="10000" step="10" value="<?php echo $max_price; ?>">
               </div>
               <span id="priceRangeLabel">NPR <?php echo $min_price; ?> - NPR <?php echo $max_price; ?></span>
            </div>

            <button type="submit" class="apply-filter-btn">Apply Filters</button>
         </form>
      </div>
   </section>

   <!-- Products Section -->
   <section class="products">
      <h1 class="title">All Products</h1>
      <div class="box-container">
         <?php
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
            echo '<p class="empty">No products available!</p>';
         }
         ?>
      </div>
   </section>

   <?php @include 'footer.php'; ?>
   <script src="js/script.js"></script>
</body>

</html>