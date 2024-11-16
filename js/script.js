let userBox = document.querySelector(".header .flex .account-box");

document.querySelector("#user-btn").onclick = () => {
  userBox.classList.toggle("active");
  navbar.classList.remove("active");
};

let navbar = document.querySelector(".header .flex .navbar");

document.querySelector("#menu-btn").onclick = () => {
  navbar.classList.toggle("active");
  userBox.classList.remove("active");
};

window.onscroll = () => {
  userBox.classList.remove("active");
  navbar.classList.remove("active");
};


//shop.php js code starts
      // Add event listeners when the document is ready
      document.addEventListener('DOMContentLoaded', function() {
         const filterBtn = document.getElementById('filterBtn');
         const filterPopup = document.getElementById('filterPopup');
         const closeFilterBtn = document.getElementById('closeFilterBtn');
         const filterOverlay = document.getElementById('filterOverlay');

         // Function to open filter popup
         function openFilterPopup() {
            filterPopup.classList.add('active');
            filterOverlay.classList.add('active');
         }

         // Function to close filter popup
         function closeFilterPopup() {
            filterPopup.classList.remove('active');
            filterOverlay.classList.remove('active');
         }

         // Event listeners for opening/closing popup
         filterBtn.addEventListener('click', openFilterPopup);
         closeFilterBtn.addEventListener('click', closeFilterPopup);
         filterOverlay.addEventListener('click', closeFilterPopup);

         // Close popup when clicking outside
         document.addEventListener('click', function(event) {
            if (!filterPopup.contains(event.target) &&
               !filterBtn.contains(event.target) &&
               filterPopup.classList.contains('active')) {
               closeFilterPopup();
            }
         });

         // Prevent popup from closing when clicking inside it
         filterPopup.addEventListener('click', function(event) {
            event.stopPropagation();
         });

         // Handle form submission
         const filterForm = document.querySelector('.filter-form');
         filterForm.addEventListener('submit', function(event) {
            // Form will submit normally - no need to prevent default
            closeFilterPopup();
         });

         // Price range validation
         const minPrice = document.querySelector('input[name="min_price"]');
         const maxPrice = document.querySelector('input[name="max_price"]');

         function validatePriceRange() {
            if (minPrice.value && maxPrice.value) {
               if (parseInt(minPrice.value) > parseInt(maxPrice.value)) {
                  maxPrice.setCustomValidity('Max price must be greater than min price');
               } else {
                  maxPrice.setCustomValidity('');
               }
            }
         }

         minPrice.addEventListener('input', validatePriceRange);
         maxPrice.addEventListener('input', validatePriceRange);

      });
 
      document.addEventListener('DOMContentLoaded', function() {
         const minPriceInput = document.getElementById('minPriceInput');
         const maxPriceInput = document.getElementById('maxPriceInput');
         const minPriceRange = document.getElementById('minPriceRange');
         const maxPriceRange = document.getElementById('maxPriceRange');
         const priceRangeLabel = document.getElementById('priceRangeLabel');

         // Function to update the label
         function updatePriceLabel() {
            priceRangeLabel.textContent = `NPR ${minPriceInput.value} - NPR ${maxPriceInput.value}`;
         }

         // Syncing range sliders with number inputs
         minPriceRange.addEventListener('input', function() {
            minPriceInput.value = minPriceRange.value;
            if (parseInt(minPriceRange.value) > parseInt(maxPriceRange.value)) {
               minPriceRange.value = maxPriceRange.value; // Prevent overlap
            }
            updatePriceLabel();
         });

         maxPriceRange.addEventListener('input', function() {
            maxPriceInput.value = maxPriceRange.value;
            if (parseInt(maxPriceRange.value) < parseInt(minPriceRange.value)) {
               maxPriceRange.value = minPriceRange.value; // Prevent overlap
            }
            updatePriceLabel();
         });

         // Syncing number inputs with range sliders
         minPriceInput.addEventListener('input', function() {
            minPriceRange.value = minPriceInput.value;
            if (parseInt(minPriceInput.value) > parseInt(maxPriceInput.value)) {
               minPriceInput.value = maxPriceInput.value; // Prevent overlap
            }
            updatePriceLabel();
         });

         maxPriceInput.addEventListener('input', function() {
            maxPriceRange.value = maxPriceInput.value;
            if (parseInt(maxPriceInput.value) < parseInt(minPriceInput.value)) {
               maxPriceInput.value = minPriceInput.value; // Prevent overlap
            }
            updatePriceLabel();
         });

         // Initial update
         updatePriceLabel();
      });
//shop.php js code ends
