const accountBtn = document.getElementById("accountBtn");
const accountMenu = document.getElementById("accountMenu");
const themeToggle = document.getElementById("themeToggle");
const themeIcon = document.getElementById("themeIcon");
const menuToggle = document.getElementById("menuToggle");
const navLinks = document.getElementById("navLinks");
const API_BASE_URL = "https://wheatley.cs.up.ac.za/u24634434/COS221/api.php"; // API base URL

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


//showing a nice error and success message. 
function showSuccessMessage(elementId, message) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    element.innerHTML = message;
    element.className = 'result-message success';
    element.style.display = 'block';
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        element.style.display = 'none';
    }, 5000);
}

function showErrorMessage(elementId, message) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    element.innerHTML = message;
    element.className = 'result-message error';
    element.style.display = 'block';
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        element.style.display = 'none';
    }, 5000);
}


// Quick Add User Functionality
async function addUser() {
    const name = document.getElementById('userName').value.trim();
    const surname = document.getElementById('userSurname').value.trim();
    const email = document.getElementById('userEmail').value.trim();
    const password = document.getElementById('userpassword').value.trim();
    const userType = document.getElementById('userType').value === 'staff' ? 'Admin' : 'Customer';

    // Clear any previous messages
    showSuccessMessage('userAddResult', '');
    showErrorMessage('userAddResult', '');

    // Basic validation
    if (!name || !surname || !email || !password) {
        showErrorMessage('userAddResult', 'Please fill in all fields');
        return;
    }

    // Validate email format
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showErrorMessage('userAddResult', 'Please enter a valid email address');
        return;
    }

    // Validate password complexity
    if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}/.test(password)) {
        showErrorMessage('userAddResult', 'Password must be at least 8 characters with uppercase, lowercase, number, and special character');
        return;
    }

    const adminApiKey = getAdminApiKey(); 
    if (!adminApiKey) {
        showErrorMessage('userAddResult', 'Admin session expired. Please log in again.');
        return;
    }

    try {
        let headers = new Headers();
        headers.append("Authorization", "Basic " + btoa(WHEATLEY_USERNAME + ":" + WHEATLEY_PASSWORD));

        const response = await fetch(API_BASE_URL, {
            method: 'POST',
            headers: headers,
            'Content-Type': 'application/json',
            
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
            const successMessage = `User added successfully!<br>
                                   <small>User ID: ${result.data.user_id}</small><br>
                                   <small>API Key: ${result.data.apikey}</small>`;
            showSuccessMessage('userAddResult', successMessage);
            
            // Clear form
            document.getElementById('userName').value = '';
            document.getElementById('userSurname').value = '';
            document.getElementById('userEmail').value = '';
            document.getElementById('userpassword').value = '';
        } else {
            let errorMessage = result.message;
            if (result.data) {
                errorMessage += '<br><small>' + Object.values(result.data).join('<br>') + '</small>';
            }
            showErrorMessage('userAddResult', errorMessage);
        }
    } catch (error) {
        console.error('Error adding user:', error);
        showErrorMessage('userAddResult', 'Failed to add user. Please try again.');
    }
}

// Quick Edit Product Price Functionality
async function editPrice() {
    const productId = document.getElementById('productIdSearch').value.trim();
    const retailerId = document.getElementById('retailerIdSearch').value.trim();
    const newPrice = document.getElementById('new-price').value.trim();

    // Clear any previous messages
    showSuccessMessage('priceEditResult', '');
    showErrorMessage('priceEditResult', '');

    if (!productId || !retailerId || !newPrice) {
        showErrorMessage('priceEditResult', 'Please fill in all fields');
        return;
    }

    const adminApiKey = getAdminApiKey();
    if (!adminApiKey) {
        showErrorMessage('priceEditResult', 'Admin session expired. Please log in again.');
        return;
    }

    try {
        const response = await fetch(API_BASE_URL, {
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
            const successMessage = `Price ${result.message.toLowerCase()}<br>
                                  <small>Product: ${result.data.product_id}</small><br>
                                  <small>Retailer: ${result.data.retailer_id}</small><br>
                                  <small>New Price: R${result.data.price.toFixed(2)}</small>`;
            showSuccessMessage('priceEditResult', successMessage);
            
            // Clear form
            document.getElementById('productIdSearch').value = '';
            document.getElementById('retailerIdSearch').value = '';
            document.getElementById('new-price').value = '';
        } else {
            let errorMessage = result.message;
            if (result.data) {
                errorMessage += '<br><small>' + Object.values(result.data).join('<br>') + '</small>';
            }
            showErrorMessage('priceEditResult', errorMessage);
        }
    } catch (error) {
        console.error('Error updating price:', error);
        showErrorMessage('priceEditResult', 'Failed to update price. Please try again.');
    }
}


//delete recent review.
async function deleteReview(productId, userId) {
   // if (!confirm('Are you sure you want to delete this review?')) return;

    const adminApiKey = getAdminApiKey();
    if (!adminApiKey) {
        alert('Admin session expired. Please log in again.');
        return;
    }

    try {
        const response = await fetch(API_BASE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: 'deleteRating',
                apikey: adminApiKey,
                user_id: userId,
                product_id: productId
            })
        });

        const result = await response.json();

        if (result.status === 'success') {
            // Just remove the first .review-item matching both productId and userId
            const allReviews = document.querySelectorAll('.review-item');
            for (let item of allReviews) {
                if (
                    item.innerHTML.includes(`on Product ID: ${productId}`) &&
                    item.innerHTML.includes(`by User ID: ${userId}`)
                ) {
                    const tempMsg = document.createElement('div');
                    tempMsg.className = 'result-message success';
                    tempMsg.innerHTML = 'Review deleted successfully';
                    item.appendChild(tempMsg);

                    setTimeout(() => {
                        tempMsg.remove();
                        loadRecentReviews(); // refresh
                    }, 3000);
                    break;
                }
            }
        } else {
            alert(`Error: ${result.message}`);
        }
    } catch (error) {
        console.error('Error deleting review:', error);
        alert('Failed to delete review. Please try again.');
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
                <span class="user-id">by User ID: ${review.user_id}</span>
            </div>
            <p class="review-text">"${review.description || 'No description'}"</p>
            <button class="btn-danger" onclick="deleteReview(${review.product_id}, ${review.user_id})">
            <span class="material-symbols-outlined">delete</span> Delete
            </button>
        `;
        
        reviewsContainer.appendChild(reviewItem);
    });
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
        const response = await fetch(API_BASE_URL, {
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