<!-- admin_add_product.php  -->
<?php
@include 'config.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if (!isset($admin_id)) {
   header('location:login.php');
};

// Check if a message is set (product name already exists)
$message = '';
if (isset($_SESSION['product_exists_message'])) {
   $message = $_SESSION['product_exists_message'];
   unset($_SESSION['product_exists_message']); // Clear the message to avoid showing it again on refresh
}

if (isset($_POST['add_product'])) {
   $name = mysqli_real_escape_string($conn, $_POST['name']);
   $gender = mysqli_real_escape_string($conn, $_POST['gender']);
   $category = mysqli_real_escape_string($conn, $_POST['category']);  // Changed variable name from $type to $category
   $details = mysqli_real_escape_string($conn, $_POST['details']);
   $price = mysqli_real_escape_string($conn, $_POST['price']);
   $stock = mysqli_real_escape_string($conn, $_POST['stock']);
   $image = $_FILES['image']['name'];
   $image_size = $_FILES['image']['size'];
   $image_tmp_name = $_FILES['image']['tmp_name'];
   $image_folder = 'uploaded_img/' . $image;

   $select_product_name = mysqli_query($conn, "SELECT name FROM `products` WHERE name = '$name'") or die('query failed');

   if (mysqli_num_rows($select_product_name) > 0) {
      $_SESSION['product_exists_message'] = 'Product name already exists!';
      header('location:admin_add_product.php');
      exit();
   } else {
      $insert_product = mysqli_query($conn, "INSERT INTO `products`(name, gender, category, details, price, stock_quantity, image) 
         VALUES('$name', '$gender', '$category', '$details', '$price', '$stock', '$image')") or die('query failed');

      $product_id = mysqli_insert_id($conn);

      if ($insert_product) {

         if ($image_size > 2000000) {
            $message = 'Image size is too large!';
         } else {
            move_uploaded_file($image_tmp_name, $image_folder);

            // Handle sizes
            if (isset($_POST['size'])) {
               foreach ($_POST['size'] as $size) {
                  $size = mysqli_real_escape_string($conn, $size);
                  mysqli_query($conn, "INSERT INTO `product_sizes`(product_id, size, stock_quantity) VALUES('$product_id', '$size', '$stock')") or die('query failed');
               }
            }

            // Handle colors
            if (isset($_POST['colors'])) {
               $colors = explode(',', $_POST['colors']);
               foreach ($colors as $color) {
                  $color = mysqli_real_escape_string($conn, trim($color));
                  mysqli_query($conn, "INSERT INTO `product_colors`(product_id, color, stock_quantity) VALUES('$product_id', '$color', '$stock')") or die('query failed');
               }
            }

            $message = 'Product added successfully!';
         }
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
   <title>Products</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

   <!-- custom admin css file link  -->
   <link rel="stylesheet" href="css/admin_style.css">

   <style>
      /* Custom styles for displaying color inputs */
      .color-display {
         display: flex;
         flex-wrap: wrap;
         margin-top: 10px;
      }

      .color-display div {
         width: 30px;
         height: 30px;
         border-radius: 50%;
         margin-right: 5px;
         margin-bottom: 5px;
         border: 2px solid #ccc;
      }
   </style>
</head>

<body>
   <?php include 'admin_header.php'; ?>
   <section class="add-products">
      <form id="addProductForm" action="admin_add_product.php" method="POST" enctype="multipart/form-data">
         <!-- <h3>Add New Product</h3> -->

         <!-- Product Name -->
         <input type="text" class="box" required placeholder="Enter product name" name="name">

         <!-- Gender Selection -->
         <select class="box" required name="gender" id="genderSelect" onchange="updateCategories()">
            <option value="" disabled selected>Select Gender</option>
            <option value="men">Men</option>
            <option value="women">Women</option>
            <option value="unisex">Unisex</option>
         </select>

         <!-- Dynamic Category Selection -->
         <select class="box" required name="category" id="categorySelect" disabled>
            <option value="" disabled selected>Select Category</option>
         </select>

         <!-- Rest of your existing form elements -->
         <div class="customization-row">
            <!-- Size Section -->
            <div class="sizes-section">
               <h4>Select Sizes</h4>
               <div class="checkbox-group">
                  <label><input type="checkbox" name="size[]" value="S"> S</label>
                  <label><input type="checkbox" name="size[]" value="M"> M</label>
                  <label><input type="checkbox" name="size[]" value="L"> L</label>
                  <label><input type="checkbox" name="size[]" value="XL"> XL</label>
               </div>
            </div>

            <!-- Color Section -->
            <div class="colors-section">
               <h4>Select Colors</h4>
               <div class="color-picker-container">
                  <div class="color-controls">
                     <input type="color" id="colorPicker" class="box">
                     <button type="button" onclick="addColor()">Add Color</button>
                  </div>
                  <div class="color-display" id="colorDisplay"></div>
                  <input type="hidden" id="colorsInput" name="colors" value="">
               </div>
            </div>
         </div>

         <!-- Rest of your form content -->
         <div class="product-details-row">
            <!-- Price Input -->
            <div class="input-group">
               <label for="price">Price</label>
               <div class="number-input price-input">
                  <input type="number" id="price" name="price" class="box" min="0" required placeholder="0.00">
               </div>
            </div>

            <!-- Stock Input -->
            <div class="input-group">
               <label for="stock">Stock Quantity</label>
               <div class="number-input stock-input">
                  <input type="number" id="stock" name="stock" class="box" min="0" required placeholder="0">
               </div>
            </div>

            <!-- Image Upload -->
            <div class="input-group file-input-group">
               <label>Product Image</label>
               <div class="file-input-container">
                  <label class="file-input-label">
                     <span>
                        <i class="fas fa-cloud-upload-alt"></i>
                        Drop image here or click to upload
                     </span>
                     
                     <input type="file" name="image" accept="image/jpg, image/jpeg, image/png" required onchange="updateFileName(this)">
                     <div class="file-name" style="font-size: medium;"></div>
                     </label>
               </div>
               <div class="file-name"></div>
            </div>
         </div>

         <!-- Product Details -->
         <textarea name="details" class="box" placeholder="Enter product details" required style="outline: 2px solid #808080; border-radius: 5px;"></textarea>

         <!-- Submit Button -->
         <div class="submit-container">
            <input type="submit" value="Add Product" name="add_product" class="btn">
         </div>
      </form>
   </section>
   <script>
      // Add selected color to the color display
      // Initialize color picker with animation
      document.addEventListener('DOMContentLoaded', function() {
         const colorPicker = document.getElementById('colorPicker');
         const colorDisplay = document.getElementById('colorDisplay');

         // Add placeholder text if no colors
         updatePlaceholder();
      });

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
            // Get the color from the background-color style
            const rgbColor = circle.style.backgroundColor;
            // Convert RGB to Hex
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
         const fileNameDisplay = input.closest('.file-input-container').querySelector('.file-name');
         if (fileName) {
            fileNameDisplay.textContent = fileName;
         } else {
            fileNameDisplay.textContent = '';
         }
      }

      // Optional: Add drag and drop functionality
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
      });
   </script>
   <script>
      // Define categories for each gender
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
         women: [
            {
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

      // Function to update categories based on selected gender
      function updateCategories() {
         const genderSelect = document.getElementById('genderSelect');
         const categorySelect = document.getElementById('categorySelect');
         const selectedGender = genderSelect.value;

         // Clear existing options
         categorySelect.innerHTML = '<option value="" disabled selected>Select Category</option>';

         // Enable category select if gender is selected
         categorySelect.disabled = !selectedGender;

         // If gender is selected, populate categories
         if (selectedGender) {
            const categories = categoryMap[selectedGender];
            categories.forEach(category => {
               const option = document.createElement('option');
               option.value = category.value;
               option.textContent = category.label;
               categorySelect.appendChild(option);
            });
         }
      }

      // Initialize when document loads
      document.addEventListener('DOMContentLoaded', function() {
         const categorySelect = document.getElementById('categorySelect');
         categorySelect.disabled = true;
         updateCategories();
      });
   </script>
</body>

</html>