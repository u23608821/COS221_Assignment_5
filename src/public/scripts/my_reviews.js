const reviewsContainer = document.getElementById("reviewsContainer");

// Main Review Functions
document.addEventListener("DOMContentLoaded", function () {
    const apiKey = localStorage.getItem('apiKey');

    if (!apiKey) {
        alert('Please log in to view your reviews');
        window.location.href = '../html/login.php';
        return;
    }

    loadMyReviews(apiKey);
});

function loadMyReviews(apiKey) {
    showLoadingState();

    const xhr = new XMLHttpRequest();
    xhr.open('POST', API_URL, true);
    xhr.setRequestHeader("Content-Type", "application/json");
    xhr.setRequestHeader("Authorization", "Basic " + btoa(WHEATLEY_USERNAME + ":" + WHEATLEY_PASSWORD));

    const payload = {
        type: "getMyReviews",
        apikey: apiKey
    };

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);

                    if (response.status === 'success' && response.data) {
                        displayMyReviews(response.data);
                    } else {
                        showEmptyState(response.message || 'No reviews found');
                    }
                } catch (e) {
                    console.error("Error parsing response:", e);
                    showErrorState("Error loading reviews. Please try again later.");
                }
            } else if (xhr.status === 401) {
                showErrorState("Authentication failed. Please log in again.");
                setTimeout(() => {
                    window.location.href = '../html/login.php';
                }, 2000);
            } else {
                showErrorState(`Server error: ${xhr.status}. Please try again later.`);
            }
        }
    };

    xhr.onerror = function () {
        showErrorState("Network error. Please check your connection.");
    };

    xhr.send(JSON.stringify(payload));
}

function displayMyReviews(reviews) {
    reviewsContainer.innerHTML = '';

    if (reviews.length === 0) {
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
    reviewBox.dataset.productId = review.product_id;

    const starsHtml = createStarRating(review.score);
    const formattedDate = formatReviewDate(review.last_updated);
    const formattedPrice = formatPrice(review.cheapest_price);

    reviewBox.innerHTML = `
        <div class="product-image">
            <img src="${review.image_url || '../../private/resources/placeholder.png'}" 
                 alt="${review.product_name}" 
                 onerror="this.src='../../private/resources/placeholder.png'" />
            <div class="price-badge">${formattedPrice} at ${review.retailer_name}</div>
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
                <button class="review-btn edit-btn">Edit</button>
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

    popup.querySelector('.popup-no').onclick = function () {
        popup.remove();
    };

    popup.querySelector('.popup-yes').onclick = function () {
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
        type: "deleteReview",
        apikey: apiKey,
        review_id: reviewId
    };

    sendReviewRequest(payload,
        () => {
            // Success callback
            popup.remove();
            reviewElement.remove();
            checkForEmptyReviews();
        },
        () => {
            // Error callback
            popup.remove();
        }
    );
}

function sendReviewRequest(payload, successCallback, errorCallback) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', API_URL, true);
    xhr.setRequestHeader("Content-Type", "application/json");
    xhr.setRequestHeader("Authorization", "Basic " + btoa(WHEATLEY_USERNAME + ":" + WHEATLEY_PASSWORD));

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.status === 'success') {
                        alert(response.message || "Review deleted successfully");
                        if (successCallback) successCallback();
                    } else {
                        alert('Failed: ' + (response.message || 'Unknown error'));
                        if (errorCallback) errorCallback();
                    }
                } catch (e) {
                    console.error("Error parsing response:", e);
                    alert("Error processing response from server.");
                    if (errorCallback) errorCallback();
                }
            } else {
                alert("Server error: " + xhr.status);
                if (errorCallback) errorCallback();
            }
        }
    };

    xhr.onerror = function () {
        alert('Network Error: Could not connect to the server');
        if (errorCallback) errorCallback();
    };

    xhr.send(JSON.stringify(payload));
}

function checkForEmptyReviews() {
    if (document.querySelectorAll('.review-box').length === 0) {
        showEmptyState("You haven't written any reviews yet.");
    }
}