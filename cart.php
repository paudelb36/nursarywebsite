<?php
@include 'config.php';
session_start();

// User session handling
if (!isset($_SESSION['user_id'])) {
    if (!isset($_SESSION['temp_user_id'])) {
        $_SESSION['temp_user_id'] = uniqid('temp_user_');
    }
    $user_id = $_SESSION['temp_user_id'];
} else {
    $user_id = $_SESSION['user_id'];
}

function getCartItems($conn, $user_id)
{
    $query = "
        SELECT 
            c.*,
            p.stock_quantity,
            GROUP_CONCAT(DISTINCT ps.size) as sizes,
            GROUP_CONCAT(DISTINCT pc.color) as colors
        FROM `cart` c
        LEFT JOIN `products` p ON c.pid = p.id
        LEFT JOIN `product_sizes` ps ON c.size_id = ps.id
        LEFT JOIN `product_colors` pc ON c.color_id = pc.id
        WHERE c.user_id = ?
        GROUP BY c.id
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Delete item handling with prepared statements
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];

    $stmt = $conn->prepare("SELECT pid, quantity FROM `cart` WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($cart_item = $result->fetch_assoc()) {
        // Update stock in transaction
        $conn->begin_transaction();
        try {
            // Update stock
            $stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
            $stmt->bind_param("ii", $cart_item['quantity'], $cart_item['pid']);
            $stmt->execute();

            // Delete cart item
            $stmt = $conn->prepare("DELETE FROM `cart` WHERE id = ?");
            $stmt->bind_param("i", $delete_id);
            $stmt->execute();

            $conn->commit();
            header('location:cart.php');
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            die('Delete failed: ' . $e->getMessage());
        }
    }
}

// Update quantity handling
if (isset($_POST['update_quantity'])) {
    $cart_id = $_POST['cart_id'];
    $cart_quantity = $_POST['cart_quantity'];

    $conn->begin_transaction();
    try {
        // Get current cart item details
        $stmt = $conn->prepare("SELECT pid, quantity FROM `cart` WHERE id = ?");
        $stmt->bind_param("i", $cart_id);
        $stmt->execute();
        $cart_item = $stmt->get_result()->fetch_assoc();

        $quantity_diff = $cart_item['quantity'] - $cart_quantity;

        // Update stock
        $stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
        $stmt->bind_param("ii", $quantity_diff, $cart_item['pid']);
        $stmt->execute();

        // Update cart quantity
        $stmt = $conn->prepare("UPDATE `cart` SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $cart_quantity, $cart_id);
        $stmt->execute();

        $conn->commit();
        $message[] = 'Cart quantity updated!';
    } catch (Exception $e) {
        $conn->rollback();
        $message[] = 'Update failed: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
       

        .product-options {
            margin: 15px 0;
            padding: 15px;
            border-radius: 8px;
            background: #f9f9f9;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            position: relative;
        }

        .selected-label {
            position: absolute;
            top: -10px;
            left: 10px;
            background: #4CAF50;
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: small;
            font-weight: bold;
            text-transform: uppercase;
        }


        .selected-options {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: center;
        }

        .option-tag {
            padding: 8px 15px;
            border-radius: 6px;
            background: #fff;
            font-size: medium;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid #e0e0e0;
        }

        .color-circle {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: inline-block;
            border: 2px solid #ddd;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <?php @include 'header.php'; ?>
    <section class="heading">
        <!-- <h3>your wishlist</h3> -->
        <p> <a href="home.php">home</a> / cart </p>
    </section>
    <section class="shopping-cart">
        <h1 class="title">Shopping Cart</h1>

        <div class="box-container">
            <?php
            $grand_total = 0;
            $select_cart = getCartItems($conn, $user_id);

            if ($select_cart->num_rows > 0) {
                while ($item = $select_cart->fetch_assoc()) {
                    $sub_total = $item['price'] * $item['quantity'];
                    $grand_total += $sub_total;
            ?>
                    <div class="box">
                        <a href="cart.php?delete=<?= $item['id'] ?>" class="fas fa-times"
                            onclick="return confirm('Delete this from cart?');"></a>
                        <a href="view_page.php?pid=<?= $item['pid'] ?>" class="fas fa-eye"></a>

                        <img src="uploaded_img/<?= $item['image'] ?>" alt="" class="image">
                        <div class="name"><?= $item['name'] ?></div>
                        <div class="price">Rs.<?= $item['price'] ?>/-</div>

                        <div class="product-options">
                            <span class="selected-label">Selected</span>
                            <div class="selected-options">
                                <?php if ($item['sizes']) { ?>
                                    <div class="option-tag">
                                        Size: <?= $item['sizes'] ?>
                                    </div>
                                <?php } ?>

                                <?php if ($item['colors']) { ?>
                                    <div class="option-tag">
                                        <span class="color-circle" style="background-color: <?= $item['colors'] ?>"></span>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>


                        <form action="" method="post">
                            <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                            <input type="number" min="1" name="cart_quantity"
                                value="<?= $item['quantity'] ?>"
                                max="<?= $item['stock_quantity'] + $item['quantity'] ?>"
                                class="qty">
                            <input type="submit" name="update_quantity" value="update" class="option-btn">
                        </form>

                        <div class="sub-total">
                            Sub total: <span>Rs.<?= $sub_total ?>/-</span>
                        </div>
                    </div>
            <?php
                }
            } else {
                echo '<p class="empty">Your cart is empty</p>';
            }
            ?>
        </div>

        <div class="cart-total">
            <p>Grand Total: <span>Rs.<?= $grand_total ?>/-</span></p>
            <div class="flex">
                <a href="shop.php" class="option-btn">Continue Shopping</a>
                <?php if (isset($_SESSION['user_id'])) { ?>
                    <a href="check-out.php" class="option-btn <?= ($grand_total > 1) ? '' : 'disabled' ?>">
                        Proceed to Checkout
                    </a>
                <?php } else { ?>
                    <a href="login.php" class="btn">Log in to Proceed to Checkout</a>
                <?php } ?>
            </div>
        </div>
    </section>

    <?php @include 'footer.php'; ?>
    <script src="js/script.js"></script>
</body>

</html>

// Add this new function in the PHP section
if (isset($_POST['update_options'])) {
$cart_id = $_POST['cart_id'];
$new_size = $_POST['new_size'];
$new_color = $_POST['new_color'];

$conn->begin_transaction();
try {
// Update size
if ($new_size) {
$size_stmt = $conn->prepare("SELECT id FROM `product_sizes` WHERE product_id = (SELECT pid FROM cart WHERE id = ?) AND size = ?");
$size_stmt->bind_param("is", $cart_id, $new_size);
$size_stmt->execute();
$size_result = $size_stmt->get_result();
$size_row = $size_result->fetch_assoc();

$update_size = $conn->prepare("UPDATE `cart` SET size_id = ? WHERE id = ?");
$update_size->bind_param("ii", $size_row['id'], $cart_id);
$update_size->execute();
}

// Update color
if ($new_color) {
$color_stmt = $conn->prepare("SELECT id FROM `product_colors` WHERE product_id = (SELECT pid FROM cart WHERE id = ?) AND color = ?");
$color_stmt->bind_param("is", $cart_id, $new_color);
$color_stmt->execute();
$color_result = $color_stmt->get_result();
$color_row = $color_result->fetch_assoc();

$update_color = $conn->prepare("UPDATE `cart` SET color_id = ? WHERE id = ?");
$update_color->bind_param("ii", $color_row['id'], $cart_id);
$update_color->execute();
}

$conn->commit();
$message[] = 'Options updated successfully!';
} catch (Exception $e) {
$conn->rollback();
$message[] = 'Update failed: ' . $e->getMessage();
}
}

// Update the CSS styles