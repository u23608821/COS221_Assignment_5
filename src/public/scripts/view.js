const API_URL = "https://wheatley.cs.up.ac.za/u24634434/COS221/api.php"; // API base URL
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

const currentApiKey =
  localStorage.getItem('apiKey') ||
  sessionStorage.getItem('apiKey') ||
  getCookie('apiKey');

document.querySelector('.back-button').addEventListener('click', () => {
  window.history.back();
});

document.addEventListener('DOMContentLoaded', loadProductDetails);

function getCookie(name) {
  const cookieArr = document.cookie.split(';');
  for (let c of cookieArr) {
    const [key, val] = c.trim().split('=');
    if (key === name) return decodeURIComponent(val);
  }
  return null;
}

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
    } else {
      console.error('Error loading product:', result.message);
    }
  } catch (error) {
    console.error('Network error:', error);
  }
}

function displayProductDetails(product) {
  productImage.src = product.image_url || product.Image_url || '/fallback.png';
  productImage.alt = product.name || 'Product image';
  productTitle.textContent = product.name;
  productDescription.textContent = product.description;
  productCategory.textContent = product.category;

  if (product.cheapest_price) {
    bestPriceValue.textContent = `R${product.cheapest_price.toFixed(2)}`;
    retailerLabel.textContent = `From ${product.cheapest_retailer}`;
  } else {
    bestPriceValue.textContent = 'N/A';
    retailerLabel.textContent = 'No retailer available';
  }

  // Sort and render retailers
  const sortedRetailers = [...product.retailers].sort((a, b) => a.price - b.price);
  retailersContainer.innerHTML = '';
  sortedRetailers.forEach((r, index) => {
    const div = document.createElement('div');
    div.className = `retailer-box ${index === 0 ? 'best-retailer' : ''}`;
    div.innerHTML = `
      <div class="retailer-name">${r.retailer_name}</div>
      <div class="retailer-price">R${r.price.toFixed(2)}</div>
      <button class="buy-now-btn">Buy Now</button>
    `;
    retailersContainer.appendChild(div);
  });

  // Reviews
  if (product.average_review !== null) {
    ratingValue.textContent = `(${product.average_review.toFixed(1)})`;
    ratingNumber.textContent = product.average_review.toFixed(1);
    reviewCount.textContent = `${product.reviews.length} reviews`;

    // Stars
    const fullStars = Math.floor(product.average_review);
    const hasHalf = product.average_review % 1 >= 0.5;
    const starEls = starRating.querySelectorAll('.material-symbols-outlined');
    starEls.forEach((star, i) => {
      if (i < fullStars) star.textContent = 'star';
      else if (i === fullStars && hasHalf) star.textContent = 'star_half';
      else star.textContent = 'star';
    });

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

    // User reviews
    userReviews.innerHTML = '';
    product.reviews.slice(0, 3).forEach(r => {
      const reviewEl = document.createElement('div');
      reviewEl.className = 'review';
      reviewEl.innerHTML = `
        <div class="review-header">
          <div class="review-stars">
            ${'<span class="material-symbols-outlined">star</span>'.repeat(r.score)}
            ${'<span class="material-symbols-outlined">star</span>'.repeat(5 - r.score)}
          </div>
          <div class="reviewer-name">${r.customer_name}</div>
          <div class="review-date">Reviewed on ${formatDate(r.updated_at)}</div>
        </div>
        <div class="review-content">${r.description}</div>
      `;
      userReviews.appendChild(reviewEl);
    });
  } else {
    reviewCount.textContent = 'No reviews yet';
    ratingBars.innerHTML = '<p>No ratings available</p>';
    userReviews.innerHTML = '<p>Be the first to review this product!</p>';
  }
}

function formatDate(dateStr) {
  return new Date(dateStr).toLocaleDateString('en-US', {
    year: 'numeric', month: 'long', day: 'numeric'
  });
}
