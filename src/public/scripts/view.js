const API_URL = "https://wheatley.cs.up.ac.za/u24634434/COS221/api.php";
const headers = new Headers();
headers.append("Authorization", "Basic " + btoa(WHEATLEY_USERNAME + ":" + WHEATLEY_PASSWORD));
headers.append("Content-Type", "application/json");

// DOM Elements
const productImage = document.querySelector('.product-image-large');
const productTitle = document.querySelector('.product-title');
const bestPriceValue = document.querySelector('.best-price-value');
const retailerLabel = document.querySelector('.retailer-label');
const productDescription = document.querySelectorAll('.product-info .text')[0];
const productCategory = document.querySelectorAll('.product-info .text')[1];
const retailersContainer = document.querySelector('.retailer-prices');
const starRating = document.querySelector('.star-rating');
const ratingValue = document.querySelector('.rating-value');
const reviewCount = document.querySelector('.review-count');
const ratingNumber = document.querySelector('.rating-number');
const ratingBars = document.querySelector('.rating-bars');
const userReviews = document.querySelector('.user-reviews');
const addToWatchlistBtn = document.querySelector('.add-to-watchlist-btn');
const backButton = document.querySelector('.back-button');

// Authentication
const currentApiKey = localStorage.getItem('apiKey') || 
                      sessionStorage.getItem('apiKey') || 
                      getCookie('apiKey');

// Initialize the page
document.addEventListener('DOMContentLoaded', () => {
    if (backButton) {
        backButton.addEventListener('click', () => window.history.back());
    }
    
    if (addToWatchlistBtn) {
        addToWatchlistBtn.addEventListener('click', handleAddToWatchlist);
    }
    
    loadProductDetails();
    setupReviewModal();
});

// Helper Functions
function getCookie(name) {
    const cookieArr = document.cookie.split(';');
    for (let c of cookieArr) {
        const [key, val] = c.trim().split('=');
        if (key === name) return decodeURIComponent(val);
    }
    return null;
}

function formatDate(dateStr) {
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric', 
        month: 'long', 
        day: 'numeric'
    });
}

// Product Loading Functions
async function loadProductDetails() {
    const productId = sessionStorage.getItem('currentProductId');
    if (!productId) {
        window.location.href = '../html/products.php';
        return;
    }

    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: headers,
            body: JSON.stringify({
                type: 'getProductDetails',
                apikey: currentApiKey,
                product_id: parseInt(productId),
                return: 'All'
            })
        });

        const result = await response.json();

        if (result.status === 'success' && result.data) {
            displayProductDetails(result.data);
            if (currentApiKey) {
                await checkIfInWatchlist(productId);
            }
        } else {
            console.error('Error loading product:', result.message);
        }
    } catch (error) {
        console.error('Network error:', error);
    }
}

function displayProductDetails(product) {
    // Basic product info
    productImage.src = product.image_url || product.Image_url || '/fallback.png';
    productImage.alt = product.name || 'Product image';
    productTitle.textContent = product.name;
    productDescription.textContent = product.description;
    productCategory.textContent = product.category;

    // Pricing info
    if (product.cheapest_price) {
        bestPriceValue.textContent = `R${product.cheapest_price.toFixed(2)}`;
        retailerLabel.textContent = `From ${product.cheapest_retailer}`;
    } else {
        bestPriceValue.textContent = 'N/A';
        retailerLabel.textContent = 'No retailer available';
    }

    // Retailers list
    renderRetailers(product.retailers);

    // Reviews section
    if (product.average_review !== null) {
        renderReviewSummary(product);
        renderUserReviews(product.reviews);
    } else {
        showEmptyReviewsState();
    }
}

function renderRetailers(retailers) {
    const sortedRetailers = [...retailers].sort((a, b) => a.price - b.price);
    retailersContainer.innerHTML = '';
    
    sortedRetailers.forEach((retailer, index) => {
        const div = document.createElement('div');
        div.className = `retailer-box ${index === 0 ? 'best-retailer' : ''}`;
        div.innerHTML = `
            <div class="retailer-name">${retailer.retailer_name}</div>
            <div class="retailer-price">R${retailer.price.toFixed(2)}</div>
            <button class="buy-now-btn">Buy Now</button>
        `;
        retailersContainer.appendChild(div);
    });
}

function renderReviewSummary(product) {
    ratingValue.textContent = `(${product.average_review.toFixed(1)})`;
    ratingNumber.textContent = product.average_review.toFixed(1);
    reviewCount.textContent = `${product.reviews.length} reviews`;

    // Star rating
    const fullStars = Math.floor(product.average_review);
    const hasHalf = product.average_review % 1 >= 0.5;
    const starsContainer = document.createElement('div');
    starsContainer.className = 'star-rating';

    for (let i = 1; i <= 5; i++) {
        const star = document.createElement('i');
        if (i <= fullStars) {
            star.className = 'fas fa-star';
        } else if (i === fullStars + 1 && hasHalf) {
            star.className = 'fas fa-star-half-alt';
        } else {
            star.className = 'far fa-star';
        }
        starsContainer.appendChild(star);
    }

    starRating.replaceWith(starsContainer);

    // Rating distribution
    const dist = [0, 0, 0, 0, 0];
    product.reviews.forEach(r => dist[r.score - 1]++);
    ratingBars.innerHTML = '';
    
    for (let i = 5; i >= 1; i--) {
        const count = dist[i - 1];
        const percent = (count / product.reviews.length) * 100;
        const bar = document.createElement('div');
        bar.className = 'rating-bar';
        bar.innerHTML = `
            <span>${i} stars</span>
            <div class="bar-container">
                <div class="bar" style="width: ${percent}%;"></div>
            </div>
            <span>${count}</span>
        `;
        ratingBars.appendChild(bar);
    }
}

function renderUserReviews(reviews) {
    userReviews.innerHTML = '';
    
    reviews.slice(0, 3).forEach(review => {
        const reviewEl = document.createElement('div');
        reviewEl.className = 'review';
        reviewEl.innerHTML = `
            <div class="review-header">
                <div class="review-stars">
                    ${'<i class="fas fa-star"></i>'.repeat(review.score)}
                    ${'<i class="far fa-star"></i>'.repeat(5 - review.score)}
                </div>
                <div class="reviewer-name">${review.customer_name}</div>
                <div class="review-date">Reviewed on ${formatDate(review.updated_at)}</div>
            </div>
            <div class="review-content">${review.description}</div>
        `;
        userReviews.appendChild(reviewEl);
    });
}

function showEmptyReviewsState() {
    reviewCount.textContent = 'No reviews yet';
    ratingBars.innerHTML = '<p>No ratings available</p>';
    userReviews.innerHTML = '<p>Be the first to review this product!</p>';
}

// Watchlist Functions
async function handleAddToWatchlist() {
    if (!addToWatchlistBtn) return;
    
    const productId = sessionStorage.getItem('currentProductId');
    if (!productId || !currentApiKey) return;

    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: headers,
            body: JSON.stringify({
                type: 'addToWatchlist',
                apikey: currentApiKey,
                product_id: parseInt(productId)
            })
        });

        const result = await response.json();

        if (result.status === 'success') {
            addToWatchlistBtn.textContent = 'In Watchlist';
            addToWatchlistBtn.disabled = true;
            console.log('Product added to your watchlist!');
        } else {
            console.error('Failed to add to watchlist: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error adding to watchlist:', error);
        console.error('Error adding to watchlist. Please try again.');
    }
}

async function checkIfInWatchlist(productId) {
    if (!addToWatchlistBtn) return;

    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: headers,
            body: JSON.stringify({
                type: 'getMyWatchlist',
                apikey: currentApiKey
            })
        });

        const result = await response.json();

        if (result.status === 'success' && result.data) {
            const inWatchlist = result.data.some(item => item.product_id == productId);
            if (inWatchlist) {
                addToWatchlistBtn.textContent = 'In Watchlist';
                addToWatchlistBtn.disabled = true;
            }
        }
    } catch (error) {
        console.error('Error checking watchlist:', error);
    }
}




// REVIEW FUNCTIONS 
function writeReview() {
    if (!currentApiKey) {
        alert('Please sign in to write a review');
        return;
    }

    const modal = document.getElementById('reviewModal');
    modal.style.display = 'flex';
    
    // Reset form
    document.getElementById('reviewText').value = '';
    document.getElementById('submitReviewBtn').disabled = true;
    document.getElementById('starRatingValue').textContent = '0 out of 5';
    
    // Reset stars
    const stars = document.querySelectorAll('#starRating .star');
    stars.forEach(star => {
        star.classList.remove('active', 'hover');
    });
}

function setupReviewModal() {
    const modal = document.getElementById('reviewModal');
    const closeBtn = document.getElementById('closeReviewModal');
    const stars = document.querySelectorAll('#starRating .star');
    const submitBtn = document.getElementById('submitReviewBtn');
    const reviewText = document.getElementById('reviewText');
    let selectedRating = 0;

    // Close modal when clicking X or outside
    closeBtn.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });

    // Star rating interaction
    stars.forEach(star => {
        star.addEventListener('mouseover', (e) => {
            const value = parseInt(e.target.getAttribute('data-value'));
            highlightStars(value);
        });

        star.addEventListener('mouseout', () => {
            highlightStars(selectedRating);
        });

        star.addEventListener('click', (e) => {
            selectedRating = parseInt(e.target.getAttribute('data-value'));
            document.getElementById('starRatingValue').textContent = `${selectedRating} out of 5`;
            submitBtn.disabled = selectedRating === 0 || reviewText.value.trim().length < 10;
        });
    });

    // Review text validation
    reviewText.addEventListener('input', () => {
        submitBtn.disabled = selectedRating === 0 || reviewText.value.trim().length < 10;
    });

    // Submit review
    submitBtn.addEventListener('click', async () => {
        const productId = sessionStorage.getItem('currentProductId');
        const description = reviewText.value.trim();
        
        try {
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: headers,
                body: JSON.stringify({
                    type: 'writeReview',
                    apikey: currentApiKey,
                    product_id: parseInt(productId),
                    score: selectedRating,
                    description: description
                })
            });

            const result = await response.json();

            if (result.status === 'success') {
                alert('Review submitted successfully!');
                modal.style.display = 'none';
                // Reload product details to show the new review
                loadProductDetails();
            } else {
                alert('Failed to submit review: ' + (result.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error submitting review:', error);
            alert('Error submitting review. Please try again.');
        }
    });
}

function highlightStars(count) {
    const stars = document.querySelectorAll('#starRating .star');
    stars.forEach((star, index) => {
        star.classList.toggle('hover', index < count);
    });
}
