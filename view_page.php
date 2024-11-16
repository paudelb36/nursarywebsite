<!-- view_page.php  -->
<?php
@include 'config.php';
session_start();

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
  $message = [];
  if (isset($_POST['add_to_cart'])) {
      if (!isset($user_id)) {
          $message[] = 'Please login first to continue shopping!';
      } else {
          
          if (isset($_POST['add_to_cart'])) {
              $result = addToCart($conn, $user_id, $_POST);
              $message[] = $result['message'];
          }
      }
  }
  ?>
<?php


function addToCart($conn, $user_id, $product_data)
{
    // Sanitize inputs
    $product_id = mysqli_real_escape_string($conn, $product_data['product_id']);
    $product_name = mysqli_real_escape_string($conn, $product_data['product_name']);
    $product_price = floatval($product_data['product_price']);
    $product_image = mysqli_real_escape_string($conn, $product_data['product_image']);
    $product_quantity = intval($product_data['product_quantity']);
    $product_size = mysqli_real_escape_string($conn, $product_data['product_size']);
    $product_colors = isset($product_data['product_color']) ? (array)$product_data['product_color'] : [];

    // Check stock with prepared statement
    $stock_stmt = $conn->prepare("SELECT stock_quantity FROM `products` WHERE id = ? FOR UPDATE");
    $stock_stmt->bind_param("i", $product_id);
    $stock_stmt->execute();
    $stock_result = $stock_stmt->get_result();
    $stock_data = $stock_result->fetch_assoc();
    $stock_stmt->close();

    if (!$stock_data || $stock_data['stock_quantity'] < $product_quantity) {
        return ['status' => 'error', 'message' => 'Insufficient stock!'];
    }

    // Get size_id
    $size_stmt = $conn->prepare("SELECT id FROM `product_sizes` WHERE product_id = ? AND size = ?");
    $size_stmt->bind_param("is", $product_id, $product_size);
    $size_stmt->execute();
    $size_result = $size_stmt->get_result();
    $size_row = $size_result->fetch_assoc();
    $size_id = $size_row ? $size_row['id'] : null;
    $size_stmt->close();

    // Check for existing cart entry
    $check_stmt = $conn->prepare("SELECT * FROM `cart` WHERE pid = ? AND user_id = ? AND size_id = ?");
    $check_stmt->bind_param("iii", $product_id, $user_id, $size_id);
    $check_stmt->execute();

    if ($check_stmt->get_result()->num_rows > 0) {
        return ['status' => 'error', 'message' => 'Product already in cart!'];
    }
    $check_stmt->close();

    // Begin transaction
    $conn->begin_transaction();
    try {
        // Update stock
        $new_stock = $stock_data['stock_quantity'] - $product_quantity;
        $update_stock_stmt = $conn->prepare("UPDATE `products` SET stock_quantity = ? WHERE id = ?");
        $update_stock_stmt->bind_param("ii", $new_stock, $product_id);
        $update_stock_stmt->execute();
        $update_stock_stmt->close();

        // Handle color insertions
        if (!empty($product_colors)) {
            foreach ($product_colors as $color) {
                $color = mysqli_real_escape_string($conn, $color);
                // Get color_id
                $color_stmt = $conn->prepare("SELECT id FROM `product_colors` WHERE product_id = ? AND color = ?");
                $color_stmt->bind_param("is", $product_id, $color);
                $color_stmt->execute();
                $color_result = $color_stmt->get_result();
                $color_row = $color_result->fetch_assoc();
                $color_stmt->close();

                if ($color_row) {
                    $insert_stmt = $conn->prepare("INSERT INTO `cart` (user_id, pid, size_id, color_id, name, price, quantity, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $insert_stmt->bind_param("iiiisdis", $user_id, $product_id, $size_id, $color_row['id'], $product_name, $product_price, $product_quantity, $product_image);
                    $insert_stmt->execute();
                    $insert_stmt->close();
                }
            }
        } else {
            // Insert without color
            $insert_stmt = $conn->prepare("INSERT INTO `cart` (user_id, pid, size_id, name, price, quantity, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("iiisdis", $user_id, $product_id, $size_id, $product_name, $product_price, $product_quantity, $product_image);
            $insert_stmt->execute();
            $insert_stmt->close();
        }

        $conn->commit();
        return ['status' => 'success', 'message' => 'Product added to cart!'];
    } catch (Exception $e) {
        $conn->rollback();
        return ['status' => 'error', 'message' => 'Failed to add product to cart: ' . $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">

    <style>
        .product-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        .product-image {
            width: 500px;
            background: #f8f9fa;
            padding: 20px;
        }

        .product-image img {
            width: 100%;
            height: 400px;
            object-fit: contain;
        }


        .product-info {
            padding: 20px 0;
        }

        .product-title {
            font-size: 24px;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .product-price {
            font-size: 20px;
            color: #444;
            margin-bottom: 20px;
        }

        .product-code {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }

        .availability {
            font-size: 14px;
            color: #666;
            font-style: italic;
            margin-bottom: 20px;
        }

        .selection-group {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;

        }

        .selection-label {
            display: block;
            font-size: medium;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .size-options {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .size-option {
            width: 40px;
            height: 40px;
            font-size: medium;
            border: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .size-option.selected {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .color-select {
            width: 100%;
            padding: 8px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            font-size: medium;

        }

        .quantity-input {
            width: 100px;
            padding: 8px;
            border: 1px solid #ddd;
            font-size: medium;

        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-cart {
            margin: 0;
            height: 40px;
            padding: 12px 24px;
            background: #ffc107;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-wishlist {
            padding: 12px 24px;
            background: #0056b3;
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .product-meta {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }

        .share-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .share-button {
            color: #666;
            text-decoration: none;
        }

        .color-options {
            display: flex;
            gap: 10px;
        }

        .color-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid #ddd;
            transition: all 0.3s ease;
        }

        .color-circle.selected {
            border-color: #007bff;
            transform: scale(1.1);
        }
    </style>
</head>

<body>

    <?php @include 'header.php'; ?>

    <section class="product-view">
        <?php
        if (isset($_GET['pid'])) {
            $pid = $_GET['pid'];
            $select_products = mysqli_query($conn, "SELECT p.*, ps.size, pc.color 
        FROM `products` p 
        LEFT JOIN `product_sizes` ps ON p.id = ps.product_id 
        LEFT JOIN `product_colors` pc ON p.id = pc.product_id 
        WHERE p.id = '$pid'") or die('query failed');

            if (mysqli_num_rows($select_products) > 0) {
                $product = mysqli_fetch_assoc($select_products);
                mysqli_data_seek($select_products, 0);

                $sizes = array();
                $colors = array();
                while ($row = mysqli_fetch_assoc($select_products)) {
                    if ($row['size'] && !in_array($row['size'], $sizes)) {
                        $sizes[] = $row['size'];
                    }
                    if ($row['color'] && !in_array($row['color'], $colors)) {
                        $colors[] = $row['color'];
                    }
                }
        ?>
                <div class="product-container">
                    <div class="product-image">
                        <img src="uploaded_img/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                    </div>

                    <div class="product-info">
                        <h1 class="product-title"><?php echo $product['name']; ?></h1>
                        <div class="product-price">Rs.<?php echo $product['price']; ?>/-</div>

                        <form action="" method="POST">
                            <div class="selection-group">
                                <label class="selection-label">Color</label>
                                <div class="color-options">
                                    <?php foreach ($colors as $color) { ?>
                                        <div class="color-circle"
                                            style="background-color: <?php echo $color; ?>"
                                            onclick="selectColor(this, '<?php echo $color; ?>')">
                                        </div>
                                    <?php } ?>
                                </div>
                                <input type="hidden" name="product_color[]" id="selected_color" required>
                            </div>


                            <div class="selection-group">
                                <label class="selection-label">Size</label>
                                <div class="size-options">
                                    <?php foreach ($sizes as $size) { ?>
                                        <div class="size-option" onclick="selectSize(this, '<?php echo $size; ?>')">
                                            <?php echo $size; ?>
                                        </div>
                                    <?php } ?>
                                </div>
                                <input type="hidden" name="product_size" id="selected_size" required>
                            </div>

                            <?php if ($product['stock_quantity'] > 0) { ?>
                                <div class="selection-group">
                                    <input type="number" name="product_quantity" value="1" min="1"
                                        max="<?php echo $product['stock_quantity']; ?>" class="quantity-input">
                                    <input type="submit" value="Add to Cart" name="add_to_cart" class="btn-cart">
                                </div>

                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="product_name" value="<?php echo $product['name']; ?>">
                                <input type="hidden" name="product_price" value="<?php echo $product['price']; ?>">
                                <input type="hidden" name="product_image" value="<?php echo $product['image']; ?>">

                            <?php } else { ?>
                                <p class="out-of-stock">Out of Stock</p>
                            <?php } ?>

                        </form>
                    </div>
                </div>
        <?php
            } else {
                echo '<p class="empty">No product details available!</p>';
            }
        }
        ?>
    </section>

    <script>
        function selectSize(element, size) {
            // Remove selected class from all size options
            document.querySelectorAll('.size-option').forEach(option => {
                option.classList.remove('selected');
            });

            // Add selected class to clicked element
            element.classList.add('selected');

            // Update hidden input
            document.getElementById('selected_size').value = size;
        }

        function selectColor(element, color) {
            document.querySelectorAll('.color-circle').forEach(circle => {
                circle.classList.remove('selected');
            });
            element.classList.add('selected');
            document.getElementById('selected_color').value = color;
        }
    </script>

    <?php @include 'footer.php'; ?>

</body>

</html>