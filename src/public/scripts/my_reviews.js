// DOM Elements
const accountBtn = document.getElementById("accountBtn");
const accountMenu = document.getElementById("accountMenu");
const themeToggle = document.getElementById("themeToggle");
const themeIcon = document.getElementById("themeIcon");
const menuToggle = document.getElementById("menuToggle");
const navLinks = document.getElementById("navLinks");
const reviewsContainer = document.getElementById("reviewsContainer");

// Theme Functions
function updateIcon() {
    themeIcon.textContent = document.body.classList.contains("dark") ? "light_mode" : "dark_mode";
}

function applySavedTheme() {
    const savedTheme = getCookie("theme");
    document.body.classList.toggle("dark", savedTheme === "dark");
    updateIcon();
}

// Initialize theme
window.addEventListener("load", applySavedTheme);
updateIcon();

// Event Listeners
accountBtn.addEventListener("click", () => {
    accountMenu.classList.toggle("show");
});

themeToggle.addEventListener("click", () => {
    document.body.classList.toggle("dark");
    const newTheme = document.body.classList.contains("dark") ? "dark" : "light";
    setCookie("theme", newTheme, 30);
    updateIcon();
});

menuToggle.addEventListener("click", () => {
    navLinks.classList.toggle("show");
});

window.addEventListener("click", (e) => {
    if (!accountBtn.contains(e.target) && !accountMenu.contains(e.target)) {
        accountMenu.classList.remove("show");
    }
});

// Cookie Functions
function setCookie(name, value, days) {
    const d = new Date();
    d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
    document.cookie = `${name}=${value}; expires=${d.toUTCString()}; path=/`;
}

function getCookie(name) {
    const cname = `${name}=`;
    const decodedCookie = decodeURIComponent(document.cookie);
    const ca = decodedCookie.split(';');
    for (let c of ca) {
        c = c.trim();
        if (c.indexOf(cname) === 0) return c.substring(cname.length, c.length);
    }
    return "";
}

// Utility function to create and send XHR requests
function sendRequest(method, url, payload, successCallback, errorCallback) {
    const xhr = new XMLHttpRequest();
    xhr.open(method, url, true);
    xhr.setRequestHeader("Content-Type", "application/json");
    xhr.setRequestHeader("Authorization", "Basic " + btoa(WHEATLEY_USERNAME + ":" + WHEATLEY_PASSWORD));

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            handleResponse(xhr, successCallback, errorCallback);
        }
    };

    xhr.onerror = function() {
        alert("Network Error: Could not connect to the server");
        if (errorCallback) errorCallback();
    };

    xhr.send(JSON.stringify(payload));
}

// Handle the response from the server
function handleResponse(xhr, successCallback, errorCallback) {
    if (xhr.status === 200) {
        try {
            const response = JSON.parse(xhr.responseText);
            if (response.status === 'success') {
                // Suppressed: alert(response.message || "Operation successful");
                if (successCallback) successCallback(Array.isArray(response.data) ? response.data : []);
            } else {
                // Suppressed: alert('Error: ' + (response.message || 'Unknown error'));
                if (errorCallback) errorCallback();
            }
        } catch (e) {
            console.error("Error parsing response:", e);
            // Suppressed: alert("Error processing response from server.");
            if (errorCallback) errorCallback();
        }
    } else {
        // Suppressed: alert("Server error: " + xhr.status);
        if (errorCallback) errorCallback();
    }
}

// Main Review Functions
document.addEventListener("DOMContentLoaded", function() {
    const apiKey = localStorage.getItem('apiKey');
    
    if (!apiKey) {
        alert('Please log in to view your reviews');
        window.location.href = '../html/login.php';
        return;
    }

    loadMyReviews(apiKey);
    updateUserGreeting(); 
});

function loadMyReviews(apiKey) {
    const payload = {
        type: "getMyReviews",
        apikey: apiKey
    };

    sendRequest('POST', API_URL, payload,
        (data) => displayMyReviews(data),
        () => showErrorState("Failed to load reviews.")
    );
}

function displayMyReviews(reviews) {
    reviewsContainer.innerHTML = '';

    if (!Array.isArray(reviews) || reviews.length === 0){
        showEmptyState("You haven't written any reviews yet.");
        return;
    }

    reviews.forEach((review) => {
        const reviewBox = createReviewBox(review);
        reviewsContainer.appendChild(reviewBox);
    });

    attachDeleteHandlers();
}

function createReviewBox(review) {
    const reviewBox = document.createElement('div');
    reviewBox.className = 'review-box';
    reviewBox.dataset.reviewId = review.review_id;
    
    const starsHtml = createStarRating(review.score);
    const formattedDate = formatReviewDate(review.last_updated);
    const formattedPrice = formatPrice(review.cheapest_price);

    reviewBox.innerHTML = `
        <div class="product-image">
            <img src="${review.image_url || '../../private/resources/placeholder.png'}" 
                 alt="${review.product_name}" 
                 onerror="this.src='../../private/resources/placeholder.png'" />
        </div>
        <div class="review-main-content">
            <div class="review-text-content">
                <h3 class="review-title">${review.product_name}</h3>
                <p class="review-date">Reviewed on ${formattedDate}</p>
                <div class="stars">${starsHtml}</div>
                <h4 class="review-heading">Review</h4>
                <div class="review-text">${review.description}</div>
            </div>
            <div class="review-actions">
                <button class="review-btn delete-btn">Delete</button>
            </div>
        </div>
    `;
    
    return reviewBox;
}

function createStarRating(score) {
    let starsHtml = '';
    for (let i = 1; i <= 5; i++) {
        starsHtml += `<span class="star ${i <= score ? 'filled' : ''}">★</span>`;
    }
    return starsHtml;
}

function formatReviewDate(dateString) {
    const reviewDate = new Date(dateString);
    return reviewDate.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function formatPrice(price) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'ZAR'
    }).format(price);
}

// UI State Functions
function showLoadingState() {
    reviewsContainer.innerHTML = `
        <div class="loading-state">
            <div class="spinner"></div>
            <p>Loading your reviews...</p>
        </div>
    `;
}

function showEmptyState(message) {
    reviewsContainer.innerHTML = `
        <div class="empty-state">
            <span class="material-symbols-outlined">rate_review</span>
            <p>${message}</p>
        </div>
    `;
}

function showErrorState(message) {
    reviewsContainer.innerHTML = `
        <div class="error-state">
            <span class="material-symbols-outlined">error</span>
            <p>${message}</p>
        </div>
    `;
}

// Review Action Functions
function attachDeleteHandlers() {
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', handleDeleteClick);
    });
}

function handleDeleteClick(e) {
    const reviewBox = e.target.closest('.review-box');
    const reviewId = reviewBox.dataset.reviewId;
    
    showDeleteConfirmation(reviewId, reviewBox);
}

function showDeleteConfirmation(reviewId, reviewElement) {
    const popup = document.createElement('div');
    popup.className = "delete-popup";
    popup.innerHTML = `
        <div class="popup-content">
            <p>Are you sure you want to delete your review?</p>
            <div class="popup-buttons">
                <button class="popup-no">No</button>
                <button class="popup-yes">Yes</button>
            </div>
        </div>
    `;
    document.body.appendChild(popup);

    popup.querySelector('.popup-no').onclick = () => popup.remove();
    
    popup.querySelector('.popup-yes').onclick = () => {
        const apiKey = localStorage.getItem('apiKey');
        if (!apiKey) {
            alert('Session expired. Please log in again.');
            popup.remove();
            window.location.href = '../html/login.php';
            return;
        }
        
        deleteReview(apiKey, reviewId, reviewElement, popup);
    };
}

function deleteReview(apiKey, reviewId, reviewElement, popup) {
    const payload = {
        type: "deleteMyReview",
        apikey: apiKey,
        review_id: reviewId
    };
    
    console.log("Sending payload:", JSON.stringify(payload)); // Debugging log

    sendRequest('POST', API_URL, payload, 
        () => {
            popup.remove();
            reviewElement.remove();
            checkForEmptyReviews();
        },
        () => {
            popup.remove();
        }
    );
}

function checkForEmptyReviews() {
    if (document.querySelectorAll('.review-box').length === 0) {
        showEmptyState("You haven't written any reviews yet.");
    }
}

function updateUserGreeting() {
    const firstName = localStorage.getItem('name'); // Changed from 'first_name' to 'name'
    const userTextElement = document.querySelector('.user-text');
    
    if (userTextElement) {
        if (firstName) {
            userTextElement.textContent = `${firstName}`;
        } else {
            userTextElement.textContent = 'User';
        }
    }
}