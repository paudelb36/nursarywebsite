<?php
@include 'config.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if (!isset($admin_id)) {
   header('location:login.php');
}

$message = '';
if (isset($_GET['update'])) {
   $update_id = $_GET['update'];
   $select_products = mysqli_query($conn, "SELECT * FROM `products` WHERE id = '$update_id'") or die('query failed');
   if (mysqli_num_rows($select_products) > 0) {
      $fetch_products = mysqli_fetch_assoc($select_products);
   }

   // Fetch sizes
   $sizes_result = mysqli_query($conn, "SELECT size FROM `product_sizes` WHERE product_id = '$update_id'") or die('query failed');
   $current_sizes = [];
   while ($size_row = mysqli_fetch_assoc($sizes_result)) {
      $current_sizes[] = $size_row['size'];
   }

   // Fetch colors
   $colors_result = mysqli_query($conn, "SELECT color FROM `product_colors` WHERE product_id = '$update_id'") or die('query failed');
   $current_colors = [];
   while ($color_row = mysqli_fetch_assoc($colors_result)) {
      $current_colors[] = $color_row['color'];
   }
}

if (isset($_POST['update_product'])) {
   $update_id = $_POST['update_id'];
   $name = mysqli_real_escape_string($conn, $_POST['name']);
   $gender = mysqli_real_escape_string($conn, $_POST['gender']);
   $category = mysqli_real_escape_string($conn, $_POST['category']);
   $details = mysqli_real_escape_string($conn, $_POST['details']);
   $price = mysqli_real_escape_string($conn, $_POST['price']);
   $stock = mysqli_real_escape_string($conn, $_POST['stock']);

   $select_other_products = mysqli_query($conn, "SELECT name FROM `products` WHERE name = '$name' AND id != '$update_id'") or die('query failed');

   if (mysqli_num_rows($select_other_products) > 0) {
      $message = 'Product name already exists!';
   } else {
      mysqli_query($conn, "UPDATE `products` SET name = '$name', gender = '$gender', category = '$category', 
         details = '$details', price = '$price', stock_quantity = '$stock' WHERE id = '$update_id'") or die('query failed');

      // Handle image update if new image is uploaded
      if (!empty($_FILES['image']['name'])) {
         $image = $_FILES['image']['name'];
         $image_size = $_FILES['image']['size'];
         $image_tmp_name = $_FILES['image']['tmp_name'];
         $image_folder = 'uploaded_img/' . $image;

         if ($image_size > 2000000) {
            $message = 'Image size is too large!';
         } else {
            // Delete old image
            $old_image = mysqli_query($conn, "SELECT image FROM `products` WHERE id = '$update_id'") or die('query failed');
            $fetch_old_image = mysqli_fetch_assoc($old_image);
            if ($fetch_old_image['image']) {
               unlink('uploaded_img/' . $fetch_old_image['image']);
            }

            move_uploaded_file($image_tmp_name, $image_folder);
            mysqli_query($conn, "UPDATE `products` SET image = '$image' WHERE id = '$update_id'") or die('query failed');
         }
      }

      // Update sizes
      mysqli_query($conn, "DELETE FROM `product_sizes` WHERE product_id = '$update_id'") or die('query failed');
      if (isset($_POST['size'])) {
         foreach ($_POST['size'] as $size) {
            $size = mysqli_real_escape_string($conn, $size);
            mysqli_query($conn, "INSERT INTO `product_sizes`(product_id, size, stock_quantity) VALUES('$update_id', '$size', '$stock')") or die('query failed');
         }
      }

      // Update colors
      mysqli_query($conn, "DELETE FROM `product_colors` WHERE product_id = '$update_id'") or die('query failed');
      if (!empty($_POST['colors'])) {
         $colors = explode(',', $_POST['colors']);
         foreach ($colors as $color) {
            $color = mysqli_real_escape_string($conn, trim($color));
            mysqli_query($conn, "INSERT INTO `product_colors`(product_id, color, stock_quantity) VALUES('$update_id', '$color', '$stock')") or die('query failed');
         }
      }

      $message = 'Product updated successfully!';
      header('location:admin_products.php'); // Add this line
      exit();
   }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Update Product</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <link rel="stylesheet" href="css/admin_style.css">

   <style>
      .current-image {
         max-width: 200px;
         margin: 10px 0;
         border-radius: 5px;
         box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      }

      .color-display {
         display: flex;
         flex-wrap: wrap;
         margin-top: 10px;
      }

      .color-circle {
         width: 30px;
         height: 30px;
         border-radius: 50%;
         margin-right: 5px;
         margin-bottom: 5px;
         border: 2px solid #ccc;
         position: relative;
         transition: all 0.3s ease;
      }

      .remove-color {
         position: absolute;
         top: -8px;
         right: -8px;
         background: #ff4444;
         color: white;
         border-radius: 50%;
         width: 20px;
         height: 20px;
         display: flex;
         align-items: center;
         justify-content: center;
         cursor: pointer;
         font-size: 14px;
         opacity: 0;
         transition: opacity 0.3s ease;
      }

      .color-circle:hover .remove-color {
         opacity: 1;
      }
   </style>
</head>

<body>
   <?php include 'admin_header.php'; ?>

   <section class="add-products">
      <form action="" method="POST" enctype="multipart/form-data">
         <input type="hidden" name="update_id" value="<?php echo $fetch_products['id']; ?>">
         <input type="hidden" name="old_image" value="<?php echo $fetch_products['image']; ?>">

         <input type="text" class="box" required placeholder="Enter product name" name="name" value="<?php echo $fetch_products['name']; ?>">

         <select class="box" required name="gender" id="genderSelect" onchange="updateCategories()">
            <option value="" disabled>Select Gender</option>
            <option value="men" <?php if ($fetch_products['gender'] == 'men') echo 'selected'; ?>>Men</option>
            <option value="women" <?php if ($fetch_products['gender'] == 'women') echo 'selected'; ?>>Women</option>
            <option value="unisex" <?php if ($fetch_products['gender'] == 'unisex') echo 'selected'; ?>>Unisex</option>
         </select>

         <select class="box" required name="category" id="categorySelect">
            <option value="" disabled>Select Category</option>
            <!-- Categories will be populated by JavaScript -->
         </select>

         <div class="customization-row">
            <div class="sizes-section">
               <h4>Select Sizes</h4>
               <div class="checkbox-group">
                  <label><input type="checkbox" name="size[]" value="S" <?php if (in_array('S', $current_sizes)) echo 'checked'; ?>> S</label>
                  <label><input type="checkbox" name="size[]" value="M" <?php if (in_array('M', $current_sizes)) echo 'checked'; ?>> M</label>
                  <label><input type="checkbox" name="size[]" value="L" <?php if (in_array('L', $current_sizes)) echo 'checked'; ?>> L</label>
                  <label><input type="checkbox" name="size[]" value="XL" <?php if (in_array('XL', $current_sizes)) echo 'checked'; ?>> XL</label>
               </div>
            </div>

            <div class="colors-section">
               <h4>Select Colors</h4>
               <div class="color-picker-container">
                  <div class="color-controls">
                     <input type="color" id="colorPicker" class="box">
                     <button type="button" onclick="addColor()">Add Color</button>
                  </div>
                  <div class="color-display" id="colorDisplay"></div>
                  <input type="hidden" id="colorsInput" name="colors" value="<?php echo implode(',', $current_colors); ?>">
               </div>
            </div>
         </div>

         <div class="product-details-row">
            <div class="input-group">
               <label for="price">Price</label>
               <div class="number-input price-input">
                  <input type="number" id="price" name="price" class="box" min="0" required value="<?php echo $fetch_products['price']; ?>">
               </div>
            </div>

            <div class="input-group">
               <label for="stock">Stock Quantity</label>
               <div class="number-input stock-input">
                  <input type="number" id="stock" name="stock" class="box" min="0" required value="<?php echo $fetch_products['stock_quantity']; ?>">
               </div>
            </div>

            <div class="input-group file-input-group">
               <label>Current Image</label>
               <img src="uploaded_img/<?php echo $fetch_products['image']; ?>" class="current-image">
               <div class="file-input-container">
                  <label class="file-input-label">
                     <span>
                        <i class="fas fa-cloud-upload-alt"></i>
                        Drop image here or click to upload
                     </span>

                     <input type="file" name="image" accept="image/jpg, image/jpeg, image/png" onchange="updateFileName(this)">
                     <div class="file-name" style="font-size: medium;"></div>
                  </label>
               </div>
               <div class="file-name"></div>
            </div>
         </div>

         <textarea name="details" class="box" placeholder="Enter product details"  style="outline: 2px solid #808080; border-radius: 5px;"></textarea>

         <div class="submit-container">
            <input type="submit" value="Update Product" name="update_product" class="btn">
            <a href="admin_products.php" class="delete-btn">Cancel</a>
         </div>
      </form>
   </section>

   <script>
      // Category mapping (same as in add_product.php)
      const categoryMap = {
         men: [{
               value: 'shirt',
               label: 'Shirt'
            },
            {
               value: 'tshirt',
               label: 'T-Shirt'
            },
            {
               value: 'pant',
               label: 'Pants'
            },
            {
               value: 'jeans',
               label: 'Jeans'
            },
            {
               value: 'jacket',
               label: 'Jacket'
            },
            {
               value: 'shorts',
               label: 'Shorts'
            },
            {
               value: 'sweater',
               label: 'Sweater'
            },
            {
               value: 'suit',
               label: 'Suit'
            },
            {
               value: 'blazer',
               label: 'Blazer'
            }
         ],
         women: [{
               value: 'skirt',
               label: 'Skirt'
            },

            {
               value: 'tshirt',
               label: 'T-Shirt'
            },
            {
               value: 'pant',
               label: 'Pants'
            },
            {
               value: 'jeans',
               label: 'Jeans'
            },
            {
               value: 'jacket',
               label: 'Jacket'
            },
            {
               value: 'shorts',
               label: 'Shorts'
            },
            {
               value: 'sweater',
               label: 'Sweater'
            },
            {
               value: 'jumpsuit',
               label: 'Jumpsuit'
            }
         ],
         unisex: [{
               value: 'tshirt',
               label: 'T-Shirt'
            },
            {
               value: 'sweater',
               label: 'Sweater'
            },
            {
               value: 'jacket',
               label: 'Jacket'
            },
            {
               value: 'hoodie',
               label: 'Hoodie'
            },
            {
               value: 'shorts',
               label: 'Shorts'
            },
            {
               value: 'jeans',
               label: 'Jeans'
            },
            {
               value: 'sweatpants',
               label: 'Sweatpants'
            }
         ]
      };

      // Function to update categories
      function updateCategories() {
         const genderSelect = document.getElementById('genderSelect');
         const categorySelect = document.getElementById('categorySelect');
         const selectedGender = genderSelect.value;
         const currentCategory = '<?php echo $fetch_products['category']; ?>';

         categorySelect.innerHTML = '<option value="" disabled>Select Category</option>';

         if (selectedGender) {
            const categories = categoryMap[selectedGender];
            categories.forEach(category => {
               const option = document.createElement('option');
               option.value = category.value;
               option.textContent = category.label;
               if (category.value === currentCategory) {
                  option.selected = true;
               }
               categorySelect.appendChild(option);
            });
         }
      }

      // Initialize colors
      document.addEventListener('DOMContentLoaded', function() {
         updateCategories();

         // Initialize existing colors
         const currentColors = '<?php echo implode(",", $current_colors); ?>'.split(',');
         currentColors.forEach(color => {
            if (color) {
               addExistingColor(color);
            }
         });
      });

      function addExistingColor(hexColor) {
         const colorDisplay = document.getElementById('colorDisplay');

         const colorCircle = document.createElement('div');
         colorCircle.className = 'color-circle';
         colorCircle.style.backgroundColor = hexColor;

         const removeBtn = document.createElement('div');
         removeBtn.className = 'remove-color';
         removeBtn.innerHTML = '×';
         removeBtn.onclick = function(e) {
            e.stopPropagation();
            colorCircle.style.transform = 'scale(0)';
            colorCircle.style.opacity = '0';
            setTimeout(() => {
               colorCircle.remove();
               updateColorsInput();
               updatePlaceholder();
            }, 300);
         };

         colorCircle.appendChild(removeBtn);
         colorDisplay.appendChild(colorCircle);
         updateColorsInput();
      }

      function addColor() {
         const colorPicker = document.getElementById('colorPicker');
         const colorDisplay = document.getElementById('colorDisplay');
         const colorsInput = document.getElementById('colorsInput');

         // Remove placeholder if exists
         const placeholder = colorDisplay.querySelector('.placeholder');
         if (placeholder) {
            placeholder.remove();
         }

         // Create color circle with animation
         const colorCircle = document.createElement('div');
         colorCircle.className = 'color-circle';
         colorCircle.style.backgroundColor = colorPicker.value;
         colorCircle.style.transform = 'scale(0)';

         // Create remove button
         const removeBtn = document.createElement('div');
         removeBtn.className = 'remove-color';
         removeBtn.innerHTML = '×';

         // Add click handler for removal with animation
         removeBtn.onclick = function(e) {
            e.stopPropagation();
            colorCircle.style.transform = 'scale(0)';
            colorCircle.style.opacity = '0';
            setTimeout(() => {
               colorCircle.remove();
               updateColorsInput();
               updatePlaceholder();
            }, 300);
         };

         colorCircle.appendChild(removeBtn);
         colorDisplay.appendChild(colorCircle);

         // Trigger animation
         requestAnimationFrame(() => {
            colorCircle.style.transform = 'scale(1)';
            colorCircle.style.opacity = '1';
         });

         updateColorsInput();
      }

      function updateColorsInput() {
         const colorsInput = document.getElementById('colorsInput');
         const colorCircles = document.querySelectorAll('.color-circle');
         const colors = Array.from(colorCircles).map(circle => {
            const rgbColor = circle.style.backgroundColor;
            const rgbValues = rgbColor.match(/\d+/g);
            const hexColor = '#' + rgbValues.map(x => {
               const hex = parseInt(x).toString(16);
               return hex.length === 1 ? '0' + hex : hex;
            }).join('');
            return hexColor;
         });
         colorsInput.value = colors.join(',');
      }

      function updatePlaceholder() {
         const colorDisplay = document.getElementById('colorDisplay');
         if (colorDisplay.children.length === 0) {
            const placeholder = document.createElement('div');
            placeholder.className = 'placeholder';
            placeholder.style.cssText = `
               width: 100%;
               text-align: center;
               padding: 2rem;
               color: #adb5bd;
               font-size: 1.1rem;
               font-style: italic;
            `;
            placeholder.textContent = 'Click "Add Color" to select colors for your product';
            colorDisplay.appendChild(placeholder);
         }
      }

      function updateFileName(input) {
         const fileName = input.files[0]?.name;
         const fileNameDisplay = input.closest('.file-input-group').querySelector('.file-name');
         if (fileName) {
            fileNameDisplay.textContent = fileName;
         } else {
            fileNameDisplay.textContent = '';
         }
      }

      // Add drag and drop functionality for image upload
      document.addEventListener('DOMContentLoaded', function() {
         const fileInput = document.querySelector('.file-input-label');

         ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileInput.addEventListener(eventName, preventDefaults, false);
         });

         function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
         }

         ['dragenter', 'dragover'].forEach(eventName => {
            fileInput.addEventListener(eventName, highlight, false);
         });

         ['dragleave', 'drop'].forEach(eventName => {
            fileInput.addEventListener(eventName, unhighlight, false);
         });

         function highlight(e) {
            fileInput.classList.add('dragover');
         }

         function unhighlight(e) {
            fileInput.classList.remove('dragover');
         }

         fileInput.addEventListener('drop', handleDrop, false);

         function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            const fileInput = document.querySelector('input[type="file"]');
            fileInput.files = files;
            updateFileName(fileInput);
         }

         // Show message if exists
         <?php if (!empty($message)) : ?>
            alert('<?php echo $message; ?>');
         <?php endif; ?>
      });
   </script>
</body>

</html>