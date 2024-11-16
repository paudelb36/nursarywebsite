<?php
// Include the config.php file and start the session
@include 'config.php';
session_start();

// Check if the user is logged in and get the user_id from the session
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// If the user is not logged in, redirect them to the login page
if (!$user_id) {
    header('Location: login.php');
    exit();
}

// Fetch user information using prepared statement
$user_stmt = $conn->prepare("SELECT id, username, email, number FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if ($user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
} else {
    $message[] = 'Error: Unable to fetch user information.';
}

// Process the order if the form is submitted
if (isset($_POST['order'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $number = mysqli_real_escape_string($conn, $_POST['number']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $method = mysqli_real_escape_string($conn, $_POST['method']);
    $address = mysqli_real_escape_string($conn, $_POST['city']);

    date_default_timezone_set('Asia/Kathmandu');
    $placed_on = date('Y-m-d'); // Changed to match DATE format in database

    $cart_total = 0;
    $cart_products = array();

    // Fetch cart items with prepared statement
    $cart_stmt = $conn->prepare("SELECT c.*, p.name AS product_name, 
        ps.size AS size_name, pc.color AS color_name 
        FROM cart c 
        LEFT JOIN products p ON c.pid = p.id 
        LEFT JOIN product_sizes ps ON c.size_id = ps.id
        LEFT JOIN product_colors pc ON c.color_id = pc.id
        WHERE c.user_id = ?");
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();

    $order_items = array();

    if ($cart_result->num_rows > 0) {
        while ($cart_item = $cart_result->fetch_assoc()) {
            $cart_products[] = $cart_item['name'] . ' (' . $cart_item['quantity'] . ')';
            $sub_total = ($cart_item['price'] * $cart_item['quantity']);
            $cart_total += $sub_total;

            $order_items[] = array(
                'pid' => $cart_item['pid'],
                'name' => $cart_item['name'],
                'quantity' => $cart_item['quantity'],
                'price' => $cart_item['price'],
                'image' => $cart_item['image'],
                'size_id' => $cart_item['size_id'],
                'color_id' => $cart_item['color_id']
            );
        }
    }

    $products_name = implode(', ', $cart_products);

    if ($cart_total == 0) {
        $message[] = 'Your cart is empty!';
    } else {
        // Begin transaction
        $conn->begin_transaction();

        try {
            // Insert into orders table with prepared statement
            $order_stmt = $conn->prepare("INSERT INTO orders (user_id, name, number, email, method, address, products_name, total_price, placed_on, payment_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            
            $order_stmt->bind_param("issssssds", 
                $user_id, $name, $number, $email, $method, $address, 
                $products_name, $cart_total, $placed_on);
            
            $order_stmt->execute();
            $order_id = $conn->insert_id;

            // Insert order items with prepared statement - FIXED parameter types
            $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, pid, size_id, color_id, productsname, quantity, price, image) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

            foreach ($order_items as $item) {
                // Convert size_id and color_id to null if they're empty
                $size_id = empty($item['size_id']) ? null : $item['size_id'];
                $color_id = empty($item['color_id']) ? null : $item['color_id'];
                
                // Fixed bind_param types to match the correct parameter types
                $item_stmt->bind_param("iiiisids", 
                    $order_id,           // i (integer)
                    $item['pid'],        // i (integer)
                    $size_id,            // i (integer)
                    $color_id,           // i (integer)
                    $item['name'],       // s (string)
                    $item['quantity'],   // i (integer)
                    $item['price'],      // d (double/decimal)
                    $item['image']       // s (string)
                );
                $item_stmt->execute();
            }

            // Clear cart with prepared statement
            $clear_cart_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $clear_cart_stmt->bind_param("i", $user_id);
            $clear_cart_stmt->execute();

            // Commit transaction
            $conn->commit();
            $message[] = 'Order placed successfully!';

        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $message[] = 'Error placing order: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .checkout-container {
            display: flex;
            gap: 30px;
            padding: 20px;
        }

        .checkout-form {
            flex: 1;
            max-width: 33%;
        }

        .display-order {
            flex: 2;
            max-width: 66%;
        }

        .product-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .product-cell img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }

        .product-name {
            font-weight: 500;
        }

        .inputBox input[name="address"] {
            width: 100%;
            min-width: 300px;
            height: 80px;
            /* Taller input field */
            padding: 12px;
            resize: vertical;
        }

        .color-circle {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-block;
        }
    </style>
</head>

<body>
    <?php @include 'header.php'; ?>

    <section class="heading">
        <p><a href="home.php">home</a> / checkout</p>
    </section>

    <div class="checkout-container">
        <div class="checkout-form">
            <form action="" method="POST">
                <h2 class="order-title">Delivery Details</h2>
                <div class="inputBox">
                    <span>Name:</span>
                    <input type="text" name="name" value="<?php echo isset($user['username']) ? $user['username'] : ''; ?>" required>
                </div>
                <div class="inputBox">
                    <span>Number:</span>
                    <input type="text" name="number" value="<?php echo isset($user['number']) ? $user['number'] : ''; ?>" required>
                </div>
                <div class="inputBox">
                    <span>Email:</span>
                    <input type="email" name="email" value="<?php echo isset($user['email']) ? $user['email'] : ''; ?>" required>
                </div>
                <div class="inputBox">
                    <span>Address:</span>
                    <input type="text" name="city" rows="2" placeholder="e.g. New Baneswor, Kathmandu" required>
                </div>
                <div class="inputBox">
                    <span>Payment Method:</span>
                    <select name="method">
                        <option value="cash on delivery">Cash on Delivery</option>
                    </select>
                </div>
                <input type="submit" name="order" value="Order Now" class="btn">
            </form>
        </div>

        <div class="display-order">
            <div class="your-order">
                <h2 class="order-title">Your order</h2>
                <div class="table">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Size</th>
                                <th>Color</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Sub Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $cart_query = "SELECT c.*, ps.size, pc.color 
                                         FROM cart c
                                         LEFT JOIN product_sizes ps ON c.size_id = ps.id
                                         LEFT JOIN product_colors pc ON c.color_id = pc.id
                                         WHERE c.user_id = ?";
                            $stmt = $conn->prepare($cart_query);
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $cart_result = $stmt->get_result();

                            while ($item = $cart_result->fetch_assoc()) {
                            ?>
                                <tr>
                                    <td><?= $item['name'] ?></td>
                                    <td><?= $item['size'] ?></td>
                                    <td>
                                        <div class="color-circle" style="background-color: <?= $item['color'] ?>"></div>
                                    </td>
                                    <td>Rs.<?= $item['price'] ?>/-</td>
                                    <td><?= $item['quantity'] ?></td>
                                    <td>Rs.<?= $item['price'] * $item['quantity'] ?>/-</td>
                                </tr>
                            <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <style>
            .color-circle {
                width: 25px;
                height: 25px;
                border-radius: 50%;
                border: 2px solid #ddd;
                display: inline-block;
                margin: 0 auto;
            }
        </style>
    </div>

    <?php @include 'footer.php'; ?>
    <script src="js/script.js"></script>
</body>

</html>