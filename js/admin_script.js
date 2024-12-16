let navbar = document.querySelector('.header .flex .navbar');
let userBox = document.querySelector('.header .flex .account-box');

document.querySelector('#menu-btn').onclick = () =>{
   navbar.classList.toggle('active');
   userBox.classList.remove('active');
}

document.querySelector('#user-btn').onclick = () =>{
   userBox.classList.toggle('active'); 
   navbar.classList.remove('active');
}

window.onscroll = () =>{
   navbar.classList.remove('active');
   userBox.classList.remove('active');
}

// this is the code for the admin_order pages
const viewDetailsButtons = document.querySelectorAll(".view-details-btn");
viewDetailsButtons.forEach((button) => {
   button.addEventListener("click", function () {
      const orderId = this.getAttribute("data-order-id");
      // Perform actions to show order details, e.g., display a modal or redirect to a details page
      // You can use JavaScript/AJAX to fetch and display details for the specific order with the orderId
   });
});



//this is the code for admin_products page
$(document).ready(function() {
   // Show product details modal
   $('.products-table').on('click', '.view-details', function() {
      var product_id = $(this).data('id');
      $.ajax({
         url: 'admin_products.php',
         method: 'post',
         data: {
            product_id: product_id
         },
         success: function(data) {
            $('.modal-body').html(data);
            $('#product-details').modal('show');
         }
      });
   });
});

