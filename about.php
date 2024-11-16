<?php

@include 'config.php';

session_start();


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>about</title>

    <!-- font awesome cdn link  -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- custom admin css file link  -->
    <link rel="stylesheet" href="css/style.css">

</head>

<body>

    <?php @include 'header.php'; ?>

    <section class="heading">
        <!-- <h3>about us</h3> -->
        <p> <a href="home.php">home</a> / about </p>
    </section>

    <section class="about">

        <div class="flex">

            <div class="image">
                <img src="images/aboutus1.jpg" alt="">
            </div>

            <div class="content">
                <h3>why choose us?</h3>
                <p align="justify" style="font-size:1.8rem">At our clothing store, we believe in more than just selling clothes—we’re here to create a style journey for each customer. From the latest trends to timeless pieces, we focus on quality, comfort, and originality. Our commitment to sustainable fashion practices, exceptional customer service, and accessible prices makes us a top choice. We curate every item with care, aiming to inspire confidence and individuality with every wear. When you choose us, you’re choosing a brand that values authenticity, supports your unique style, and ensures you look and feel your best.</p>
                <a href="shop.php" class="btn">shop now</a>
            </div>

        </div>

        <div class="flex">

            <div class="content">
                <h3>what we provide?</h3>
                <p align="justify" style="font-size:1.8rem">We offer an extensive range of clothing for men and women, thoughtfully crafted to suit diverse tastes and occasions. From casual wear to formal attire, each collection brings together style, quality fabrics, and intricate detailing. Beyond clothing, our store provides a seamless shopping experience with user-friendly online browsing, secure payment options, and fast, reliable delivery. Our team is always ready to assist with personalized styling tips, size guidance, and a hassle-free return policy, making sure every purchase is a satisfying one. We’re here to cater to every need, making fashion fun, easy, and accessible.
                </p>
                <a href="contact.php" class="btn">contact us</a>
            </div>

            <div class="image">
                <img src="images/aboutus2.jpg" alt="">
            </div>

        </div>


    </section>













    <?php @include 'footer.php'; ?>

    <script src="js/script.js"></script>

</body>

</html>