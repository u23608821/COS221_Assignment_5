// Constants
const API_URL = 'http://localhost:8000/api.php';

// DOM Elements
const accountBtn = document.getElementById("accountBtn");
const accountMenu = document.getElementById("accountMenu");
const themeToggle = document.getElementById("themeToggle");
const themeIcon = document.getElementById("themeIcon");
const menuToggle = document.getElementById("menuToggle");
const navLinks = document.getElementById("navLinks");
const searchInput = document.querySelector(".search-input");
const searchBtn = document.querySelector(".search-btn");
const productsContainer = document.getElementById("products-list");

let currentApiKey =
    localStorage.getItem('apiKey') ||
    sessionStorage.getItem('apiKey') ||
    getCookie('apiKey');

// Cookie helpers
function setCookie(name, value, days) {
    const d = new Date();
    d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
    let expires = "expires=" + d.toUTCString();
    document.cookie = name + "=" + value + ";" + expires + ";path=/";
}

function getCookie(name) {
    let cname = name + "=";
    let decodedCookie = decodeURIComponent(document.cookie);
    let ca = decodedCookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i].trim();
        if (c.indexOf(cname) === 0) {
            return c.substring(cname.length, c.length);
        }
    }
    return "";
}

// Theme logic
function updateIcon() {
    themeIcon.textContent = document.body.classList.contains("dark") ? "light_mode" : "dark_mode";
}

function applySavedTheme() {
    const savedTheme = getCookie("theme");
    document.body.classList.toggle("dark", savedTheme === "dark");
    updateIcon();
}

// Product loading
async function loadProducts(searchTerm = '') {
    try {
        productsContainer.innerHTML = '<div class="loading-spinner">Loading top-rated products...</div>';

        const requestPayload = {
            type: 'getAllProducts',
            apikey: currentApiKey,
            sort_by: 'rating_desc',
            filter_by: {
                minimum_average_rating: 4.0
            },
            include_no_rating: false
        };

        if (searchTerm) requestPayload.name = searchTerm;

        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(requestPayload)
        });

        const result = await response.json();

        if (result.status === 'success') {
            displayProducts(result.data);
        } else {
            console.error('Error loading products:', result.message);
            productsContainer.innerHTML = '<p class="error-message">Failed to load top-rated products. Please try again later.</p>';
        }

    } catch (error) {
        console.error('Error loading products:', error);
        productsContainer.innerHTML = '<p class="error-message">Network error. Please check your connection.</p>';
    }
}

// Display each product
async function displayProducts(products) {
    productsContainer.innerHTML = '';

    if (products.length === 0) {
        productsContainer.innerHTML = '<p class="no-products">No products found matching your criteria.</p>';
        return;
    }

    for (const product of products) {
        let averageRating = product.average_rating;
        let reviewCount = 0;

        // Fetch detailed review stats
        try {
            const detailResponse = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type: 'getProductDetails',
                    apikey: currentApiKey,
                    product_id: product.product_id,
                    return: "Reviews"
                })
            });

            const detailResult = await detailResponse.json();

            if (detailResult.status === 'success' && detailResult.data) {
                const reviews = Array.isArray(detailResult.data.reviews) ? detailResult.data.reviews : [];
                reviewCount = reviews.length;
                averageRating = detailResult.data.average_review ?? null;
            }

        } catch (err) {
            console.warn(`Details failed for product ID ${product.product_id}`, err);
        }

        const productBox = document.createElement('div');
        productBox.className = 'product-box';

        // Generate stars
        let starsHtml = '';
        if (averageRating) {
            const fullStars = Math.floor(averageRating);
            const hasHalfStar = averageRating % 1 >= 0.5;

            for (let i = 0; i < 5; i++) {
                if (i < fullStars) {
                    starsHtml += '<span class="material-symbols-outlined">star</span>';
                } else if (i === fullStars && hasHalfStar) {
                    starsHtml += '<span class="material-symbols-outlined">star_half</span>';
                } else {
                    starsHtml += '<span class="material-symbols-outlined">star_outline</span>';
                }
            }
        } else {
            for (let i = 0; i < 5; i++) {
                starsHtml += '<span class="material-symbols-outlined">star_outline</span>';
            }
        }

        const title = product.title || 'Unnamed Product';
        const image = product.image_url || '/src/private/resources/default.png';
        const price = product.cheapest_price ? `R${product.cheapest_price.toFixed(2)}` : null;

        productBox.innerHTML = `
            <div class="product-image">
                <img src="${image}" alt="${title}">
            </div>
            <div class="product-content">
                <div class="product-info">
                    <h3 class="product-title">${title}</h3>
                    <div class="product-rating">
                        ${starsHtml}
                        <span class="rating-text">
                            ${averageRating ? averageRating.toFixed(1) : 'No reviews'}
                            ${reviewCount ? ` (${reviewCount})` : ''}
                        </span>
                    </div>
                </div>
                <div class="best-price">
                    ${price ? 
                        `<span class="best-price-label">Best Price</span>
                         <span class="best-price-value">${price}</span>
                         <span class="retailer-label">From ${product.retailer_name}</span>` :
                        '<span class="no-price">No prices available</span>'}
                </div>
                <button class="compare-btn" data-product-id="${product.product_id}">Compare Prices</button>
            </div>
        `;

        productsContainer.appendChild(productBox);
    }

    // Compare button listeners
    document.querySelectorAll('.compare-btn').forEach(button => {
        button.addEventListener('click', function () {
            const productId = this.getAttribute('data-product-id');
            console.log('Compare prices for product:', productId);
            // You can redirect or open a modal here
        });
    });
}

// Search handlers
function performSearch() {
    const searchTerm = searchInput.value.trim();
    loadProducts(searchTerm);
}

searchBtn.addEventListener('click', performSearch);
searchInput.addEventListener('keypress', function (e) {
    if (e.key === 'Enter') performSearch();
});

// Initialization
document.addEventListener('DOMContentLoaded', function () {
    applySavedTheme();
    loadProducts();
});

// Theme & menu handlers
accountBtn.addEventListener("click", () => accountMenu.classList.toggle("show"));

themeToggle.addEventListener("click", function () {
    document.body.classList.toggle("dark");
    const newTheme = document.body.classList.contains("dark") ? "dark" : "light";
    setCookie("theme", newTheme, 30);
    updateIcon();
});

menuToggle.addEventListener("click", () => navLinks.classList.toggle("show"));

window.addEventListener("click", function (e) {
    if (!accountBtn.contains(e.target) && !accountMenu.contains(e.target)) {
        accountMenu.classList.remove("show");
    }
});

updateIcon(); // Ensure correct icon on first load
