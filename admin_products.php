<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

@include 'config.php';

session_start();

// Let's first create a simple logging function
function debug_log($message) {
    error_log("[Product Delete Debug] " . $message);
    // Also write to a custom debug file
    file_put_contents('delete_debug.log', date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
}

debug_log("Script started");

$admin_id = $_SESSION['admin_id'];

if (!isset($admin_id)) {
   debug_log("Admin not logged in - redirecting to login");
   header('location:login.php');
   exit();
}

function deleteProduct($conn, $product_id) {
    debug_log("Starting deletion for product ID: $product_id");
    
    try {
        // First verify the product exists
        $check_product = mysqli_query($conn, "SELECT * FROM products WHERE id = '$product_id'");
        if (!$check_product) {
            debug_log("Database error checking product: " . mysqli_error($conn));
            throw new Exception("Database error checking product");
        }
        
        if (mysqli_num_rows($check_product) == 0) {
            debug_log("Product not found with ID: $product_id");
            throw new Exception("Product not found");
        }
        
        $product = mysqli_fetch_assoc($check_product);
        debug_log("Found product: " . $product['name']);

        // Start transaction
        mysqli_begin_transaction($conn);
        debug_log("Started transaction");

        // 1. Delete from product_colors
        $delete_colors = mysqli_query($conn, "DELETE FROM product_colors WHERE product_id = '$product_id'");
        if (!$delete_colors) {
            debug_log("Error deleting colors: " . mysqli_error($conn));
            throw new Exception("Failed to delete product colors");
        }
        debug_log("Deleted product colors");

        // 2. Delete from product_sizes
        $delete_sizes = mysqli_query($conn, "DELETE FROM product_sizes WHERE product_id = '$product_id'");
        if (!$delete_sizes) {
            debug_log("Error deleting sizes: " . mysqli_error($conn));
            throw new Exception("Failed to delete product sizes");
        }
        debug_log("Deleted product sizes");

        // 3. Delete from cart
        $delete_cart = mysqli_query($conn, "DELETE FROM cart WHERE pid = '$product_id'");
        if (!$delete_cart) {
            debug_log("Error deleting from cart: " . mysqli_error($conn));
            throw new Exception("Failed to delete from cart");
        }
        debug_log("Deleted from cart");

        // 4. Delete from wishlist
        $delete_wishlist = mysqli_query($conn, "DELETE FROM wishlist WHERE pid = '$product_id'");
        if (!$delete_wishlist) {
            debug_log("Error deleting from wishlist: " . mysqli_error($conn));
            throw new Exception("Failed to delete from wishlist");
        }
        debug_log("Deleted from wishlist");

        // 5. Delete from order_items
        $delete_orders = mysqli_query($conn, "DELETE FROM order_items WHERE pid = '$product_id'");
        if (!$delete_orders) {
            debug_log("Error deleting from order_items: " . mysqli_error($conn));
            throw new Exception("Failed to delete from order_items");
        }
        debug_log("Deleted from order_items");

        // 6. Finally delete the product
        $delete_product = mysqli_query($conn, "DELETE FROM products WHERE id = '$product_id'");
        if (!$delete_product) {
            debug_log("Error deleting product: " . mysqli_error($conn));
            throw new Exception("Failed to delete product");
        }
        debug_log("Deleted product successfully");

        // 7. Delete image file if exists
        if (!empty($product['image'])) {
            $image_path = 'uploaded_img/' . $product['image'];
            if (file_exists($image_path)) {
                if (!unlink($image_path)) {
                    debug_log("Warning: Failed to delete image file: $image_path");
                } else {
                    debug_log("Deleted image file: $image_path");
                }
            }
        }

        // Commit transaction
        mysqli_commit($conn);
        debug_log("Transaction committed successfully");

        return [
            'success' => true,
        ];

    } catch (Exception $e) {
        debug_log("Error occurred: " . $e->getMessage());
        mysqli_rollback($conn);
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Handle delete request
if (isset($_GET['delete'])) {
    debug_log("Delete request received for ID: " . $_GET['delete']);
    
    $delete_id = (int)$_GET['delete'];
    debug_log("Sanitized delete ID: $delete_id");
    
    // Check if delete_id is valid
    if ($delete_id <= 0) {
        debug_log("Invalid delete ID");
        $_SESSION['error'] = "Invalid product ID";
        header('location:admin_products.php');
        exit();
    }

    $result = deleteProduct($conn, $delete_id);
    
    if ($result['success']) {
        debug_log("Delete operation successful");
        $_SESSION['message'] = $result['message'];
    } else {
        debug_log("Delete operation failed: " . $result['message']);
        $_SESSION['error'] = $result['message'];
    }
    
    header('location:admin_products.php');
    exit();
}

// Display messages if they exist
if (isset($_SESSION['message'])) {
    echo "<div class='message'>" . $_SESSION['message'] . "</div>";
    unset($_SESSION['message']);
}

if (isset($_SESSION['error'])) {
    echo "<div class='error'>" . $_SESSION['error'] . "</div>";
    unset($_SESSION['error']);
}
?>

<!-- Rest of your HTML code remains the same -->

<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>products</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <link rel="stylesheet" href="css/admin_style.css">
</head>

<body>
   <?php @include 'admin_header.php'; ?>

   <section class="add-products">
      <a href="admin_add_product.php" class="btn">Add Product</a>
   </section>

   <section class="show-products">
      <table class="products-table" style="border: 1px solid red;">
         <thead>
            <tr>
               <th>Name</th>
               <th>Gender</th>
               <th>Type</th>
               <th>Price</th>
               <th>Stock Quantity</th>
               <th>Sizes</th>
               <th>Colors</th>
               <th>Image</th>
               <th>Action</th>
            </tr>
         </thead>
         <tbody>
            <?php
            $select_products = mysqli_query($conn, "SELECT * FROM `products`") or die('query failed');
            if (mysqli_num_rows($select_products) > 0) {
               while ($fetch_products = mysqli_fetch_assoc($select_products)) {
                  $product_id = $fetch_products['id'];

                  // Fetch sizes
                  $sizes_result = mysqli_query($conn, "SELECT size FROM `product_sizes` WHERE product_id = '$product_id'") or die('query failed');
                  $sizes = [];
                  while ($size_row = mysqli_fetch_assoc($sizes_result)) {
                     $sizes[] = $size_row['size'];
                  }

                  // Fetch colors
                  $colors_result = mysqli_query($conn, "SELECT color FROM `product_colors` WHERE product_id = '$product_id'") or die('query failed');
                  $colors = [];
                  while ($color_row = mysqli_fetch_assoc($colors_result)) {
                     $colors[] = $color_row['color'];
                  }
            ?>
                  <tr>
                     <td><?php echo $fetch_products['name']; ?></td>
                     <td><?php echo $fetch_products['gender']; ?></td>
                     <td><?php echo $fetch_products['category']; ?></td>
                     <td>Rs.<?php echo $fetch_products['price']; ?>/-</td>
                     <td><?php echo $fetch_products['stock_quantity']; ?></td>
                     <td><?php echo implode(', ', $sizes); ?></td>
                                      <td>
                     <div style="display: flex; gap: 5px; justify-content: center; align-items: center; flex-wrap: wrap;">
                         <?php 
                         $colors_result = mysqli_query($conn, "SELECT color FROM `product_colors` WHERE product_id = '$product_id'") or die('query failed');
                         while ($color_row = mysqli_fetch_assoc($colors_result)) {
                             $hexColor = $color_row['color'];
                             echo '<div style="
                                 width: 30px;
                                 height: 30px;
                                 border-radius: 50%;
                                 background-color: ' . $hexColor . ';
                                 border: 2px solid #e9ecef;
                                 display: inline-block;
                                 box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                                 margin: 2px;
                             "></div>';
                         }
                         ?>
                     </div>
</td>
                     <td><img src="uploaded_img/<?php echo $fetch_products['image']; ?>" alt="" class="product-image"></td>
                     <td>
                        <a href="admin_update_product.php?update=<?php echo $fetch_products['id']; ?>" class="option-btn">update</a>
                        <a href="admin_products.php?delete=<?php echo $fetch_products['id']; ?>" class="delete-btn" onclick="return confirm('delete this product?');">delete</a>
                     </td>
                  </tr>

            <?php
               }
            } else {
               echo '<tr><td colspan="8" class="empty">No products added yet!</td></tr>';
            }
            ?>
         </tbody>

      </table>
   </section>
   <script src="js/admin_script.js"></script>
</body>

</html>