<?php
@include 'config.php';
session_start();

// Initialize $user_id as null
$user_id = null;

// Check if the user is logged in and if the user_id is set in the session
if (isset($_SESSION['user_id'])) {
   $user_id = $_SESSION['user_id'];
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>home</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

   <!-- custom admin css file link  -->
   <link rel="stylesheet" href="css/style.css">
   <script>
      document.addEventListener('DOMContentLoaded', function() {

         let slideIndex = 1;
         showSlides(slideIndex);

         function changeSlide(n) {
            showSlides(slideIndex += n);
         }

         function currentSlide(n) {
            showSlides(slideIndex = n);
         }

         function showSlides(n) {
            let slides = document.getElementsByClassName("slide");
            let dots = document.getElementsByClassName("dot");

            if (n > slides.length) {
               slideIndex = 1
            }
            if (n < 1) {
               slideIndex = slides.length
            }

            for (let i = 0; i < slides.length; i++) {
               slides[i].style.display = "none";
            }
            for (let i = 0; i < dots.length; i++) {
               dots[i].className = dots[i].className.replace(" active", "");
            }

            slides[slideIndex - 1].style.display = "block";
            dots[slideIndex - 1].className += " active";
         }

         // Auto slide
         setInterval(() => {
            changeSlide(1);
         }, 5000);
      });
   </script>

</head>

<body>
   <?php @include 'header.php'; ?>

   <!-- Add this new slider section -->
   <section class="hero-slider">
      <div class="slider-container">
         <div class="slide fade">
            <img src="images/slider1.jpg" alt="Nursery Image 1">
            <div class="slide-content">
               <h2>Welcome to Wear It Store</h2>
               <p>Discover your perfect style with our latest collections</p>
            </div>
         </div>

         <div class="slide fade">
            <img src="images/slider2.jpg" alt="Nursery Image 2">
            <div class="slide-content">
               <h2>Premium Fashion Wear</h2>
               <p>Elevate your wardrobe with trendsetting designs</p>
            </div>
         </div>

         <div class="slide fade">
            <img src="images/slider3.jpg" alt="Nursery Image 3">
            <div class="slide-content">
               <h2>Luxury & Comfort</h2>
               <p>Where style meets comfort for every occasion</p>
            </div>
         </div>

         <a class="prev" onclick="changeSlide(-1)">❮</a>
         <a class="next" onclick="changeSlide(1)">❯</a>
      </div>
      <div class="dots">
         <span class="dot" onclick="currentSlide(1)"></span>
         <span class="dot" onclick="currentSlide(2)"></span>
         <span class="dot" onclick="currentSlide(3)"></span>
      </div>
   </section>

   <?php // Fetch products from database
   $select_products = mysqli_query($conn, "SELECT * FROM `products` LIMIT 4") or die('query failed');
   ?>
   <!-- Products Section -->
   <section class="products">
      <h1 class="title">Latest Products</h1>
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

   <script>
      function updateColorSelection(checkbox) {
         const circle = checkbox.nextElementSibling;
         if (checkbox.checked) {
            circle.style.border = '2px solid red';
            circle.style.transform = 'scale(1.1)';
         } else {
            circle.style.border = '2px solid #e9ecef';
            circle.style.transform = 'scale(1)';
         }
      }
   </script>

   <section class="home-contact">

      <div class="content">
         <h3>have any questions?</h3>

         <a href="contact.php" class="btn">contact us</a>
      </div>

   </section>




   <?php @include 'footer.php'; ?>

   <script src="js/script.js"></script>

</body>

</html>