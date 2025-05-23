const accountBtn = document.getElementById("accountBtn");
const accountMenu = document.getElementById("accountMenu");
const themeToggle = document.getElementById("themeToggle");
const themeIcon = document.getElementById("themeIcon");
const menuToggle = document.getElementById("menuToggle");
const navLinks = document.getElementById("navLinks");

function updateIcon() {
  themeIcon.textContent = document.body.classList.contains("dark") ? "light_mode" : "dark_mode";
}

function applySavedTheme() {
  const savedTheme = getCookie("theme");
  if (savedTheme === "dark") {
    document.body.classList.add("dark");
  } else {
    document.body.classList.remove("dark");
  }
  updateIcon();
}

window.addEventListener("load", applySavedTheme);

accountBtn.addEventListener("click", function () {
  accountMenu.classList.toggle("show");
});

themeToggle.addEventListener("click", function () {
  document.body.classList.toggle("dark");
  const newTheme = document.body.classList.contains("dark") ? "dark" : "light";
  setCookie("theme", newTheme, 30);
  updateIcon();
});

menuToggle.addEventListener("click", function () {
  navLinks.classList.toggle("show");
});

window.addEventListener("click", function (e) {
  if (!accountBtn.contains(e.target) && !accountMenu.contains(e.target)) {
    accountMenu.classList.remove("show");
  }
});

updateIcon();

function setCookie(name, value, days) {
  const d = new Date();
  d.setTime(d.getTime() + (days*24*60*60*1000));
  let expires = "expires=" + d.toUTCString();
  document.cookie = name + "=" + value + ";" + expires + ";path=/";
}

function getCookie(name) {
  let cname = name + "=";
  let decodedCookie = decodeURIComponent(document.cookie);
  let ca = decodedCookie.split(';');
  for(let i = 0; i < ca.length; i++) {
    let c = ca[i].trim();
    if (c.indexOf(cname) === 0) {
      return c.substring(cname.length, c.length);
    }
  }
  return "";
}


// Quick Add User Functionality
async function addUser() {
    const name = document.getElementById('userName').value.trim();
    const surname = document.getElementById('userSurname').value.trim();
    const email = document.getElementById('userEmail').value.trim();
    const password = document.getElementById('userpassword').value.trim();
    const userType = document.getElementById('userType').value === 'staff' ? 'Admin' : 'Customer';

    // Basic validation
    if (!name || !surname || !email || !password) {
        alert('Please fill in all fields');
        return;
    }

    // Get admin API key from session or cookie (you'll need to implement this)
    const adminApiKey = getAdminApiKey(); 
    if (!adminApiKey) {
        alert('Admin not authenticated');
        return;
    }

    try {
        const response = await fetch('http://localhost:8000/api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: 'QuickAddUser',
                apikey: adminApiKey,
                name: name,
                surname: surname,
                email: email,
                user_type: userType,
                password: password
            })
        });

        const result = await response.json();
        
        if (result.status === 'success') {
            alert(`User added successfully! User ID: ${result.data.user_id}`);
            // Clear form
            document.getElementById('userName').value = '';
            document.getElementById('userSurname').value = '';
            document.getElementById('userEmail').value = '';
            document.getElementById('userpassword').value = '';
        } else {
            alert(`Error: ${result.message}`);
            if (result.data) {
                console.error('Validation errors:', result.data);
            }
        }
    } catch (error) {
        console.error('Error adding user:', error);
        alert('Failed to add user. Please try again.');
    }
}

// Quick Edit Product Price Functionality
async function editPrice() {
    const productId = document.getElementById('productIdSearch').value.trim();
    const retailerId = document.getElementById('retailerIdSearch').value.trim();
    const newPrice = document.getElementById('new-price').value.trim();

    if (!productId || !retailerId || !newPrice) {
        alert('Please fill in all fields');
        return;
    }

    // Get admin API key from session or cookie
    const adminApiKey = getAdminApiKey();
    if (!adminApiKey) {
        alert('Admin not authenticated');
        return;
    }

    try {
        const response = await fetch('http://localhost:8000/api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: 'QuickEditProductPrice',
                apikey: adminApiKey,
                product_id: parseInt(productId),
                retailer_id: parseInt(retailerId),
                price: parseFloat(newPrice)
            })
        });

        const result = await response.json();
        
        if (result.status === 'success') {
            alert(`Price ${result.message.toLowerCase()}`);
            // Clear form
            document.getElementById('productIdSearch').value = '';
            document.getElementById('retailerIdSearch').value = '';
            document.getElementById('new-price').value = '';
        } else {
            alert(`Error: ${result.message}`);
        }
    } catch (error) {
        console.error('Error updating price:', error);
        alert('Failed to update price. Please try again.');
    }
}

// Load Recent Reviews
async function loadRecentReviews() {
    // Get admin API key from session or cookie
    const adminApiKey = getAdminApiKey();
    if (!adminApiKey) {
        console.error('Admin not authenticated');
        return;
    }

    try {
        const response = await fetch('http://localhost:8000/api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: 'AdminRecentReviews',
                apikey: adminApiKey,
                number: 4 // Get 4 most recent reviews
            })
        });

        const result = await response.json();
        
        if (result.status === 'success') {
            updateReviewsUI(result.data);
        } else {
            console.error('Error loading reviews:', result.message);
        }
    } catch (error) {
        console.error('Error loading reviews:', error);
    }
}

function updateReviewsUI(reviews) {
    const reviewsContainer = document.querySelector('.reviews-list');
    if (!reviewsContainer) return;

    // Clear existing reviews
    reviewsContainer.innerHTML = '';

    // Add each review to the UI
    reviews.forEach(review => {
        const reviewItem = document.createElement('div');
        reviewItem.className = 'review-item';
        
        // Create stars based on score (assuming score is 1-5)
        const stars = '★'.repeat(review.score) + '☆'.repeat(5 - review.score);
        
        reviewItem.innerHTML = `
            <div class="review-meta">
                <div class="stars">${stars}</div>
                <span>on Product ID: ${review.product_id}</span>
            </div>
            <p class="review-text">"${review.description || 'No description'}"</p>
            <button class="btn-danger" onclick="deleteReview(${review.id})">
                <span class="material-symbols-outlined">delete</span> Delete
            </button>
        `;
        
        reviewsContainer.appendChild(reviewItem);
    });
}

// Delete Review Functionality
async function deleteReview(reviewId) {
    if (!confirm('Are you sure you want to delete this review?')) return;

    // Get admin API key from session or cookie
    const adminApiKey = getAdminApiKey();
    if (!adminApiKey) {
        alert('Admin not authenticated');
        return;
    }

    try {
        // You'll need to implement a DeleteReview endpoint in your API
        const response = await fetch('http://localhost:8000/api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: 'DeleteReview',
                apikey: adminApiKey,
                review_id: reviewId
            })
        });

        const result = await response.json();
        
        if (result.status === 'success') {
            alert('Review deleted successfully');
            loadRecentReviews(); // Refresh the list
        } else {
            alert(`Error: ${result.message}`);
        }
    } catch (error) {
        console.error('Error deleting review:', error);
        alert('Failed to delete review. Please try again.');
    }
}

// Helper function to get admin API key (you'll need to implement storage)
function getAdminApiKey() {
    
    return localStorage.getItem('adminApiKey') || 
           sessionStorage.getItem('adminApiKey') || 
           getCookie('adminApiKey');
}

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    // Load recent reviews when page loads
    loadRecentReviews();
    
    // Add event listeners for form submissions
    document.getElementById('userpassword').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') addUser();
    });
    
    document.getElementById('new-price').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') editPrice();
    });
});