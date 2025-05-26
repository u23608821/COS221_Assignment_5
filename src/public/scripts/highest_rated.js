// Constants
const API_URL = "https://wheatley.cs.up.ac.za/u24634434/COS221/api.php"; // API base URL
const headers = new Headers();
headers.append("Authorization", "Basic " + btoa(WHEATLEY_USERNAME + ":" + WHEATLEY_PASSWORD));
headers.append("Content-Type", "application/json");

// DOM Elements - Only include elements that exist in highest_rated.html
const productsContainer = document.getElementById("products-list");
const themeToggle = document.getElementById("themeToggle");
const themeIcon = document.getElementById("themeIcon");
const menuToggle = document.getElementById("menuToggle");
const navLinks = document.getElementById("navLinks");


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
    if (themeIcon) {
        themeIcon.textContent = document.body.classList.contains("dark") ? "light_mode" : "dark_mode";
    }
}

function applySavedTheme() {
    const savedTheme = getCookie("theme");
    document.body.classList.toggle("dark", savedTheme === "dark");
    updateIcon();
}

async function loadProducts(searchTerm = '') {
    try {
        productsContainer.innerHTML = '<div class="loading-spinner">Loading top-rated products...</div>';

        const requestPayload = {
            type: 'getAllProducts',
            apikey: currentApiKey,
            sort_by: 'rating_desc',
            filter_by: {
                minimum_average_rating: 4.0
            }
        };

        if (searchTerm) {
            requestPayload.name = searchTerm;
        }

        const response = await fetch(API_URL, {
            method: 'POST',
            headers: headers,

            body: JSON.stringify(requestPayload)
        });

        const result = await response.json();
        console.log('API Response:', result); // For debugging

        if (result.status === 'success') {
            // The API returns products directly in the data property
            const products = result.data || [];
            displayProducts(products);
        } else {
            const errorMessage = result.message || 'Failed to load top-rated products';
            console.error('API Error:', errorMessage);
            productsContainer.innerHTML = `<p class="error-message">${errorMessage}</p>`;
        }

    } catch (error) {
        console.error('Network Error:', error);
        productsContainer.innerHTML = '<p class="error-message">Network error. Please check your connection.</p>';
    }
}

async function displayProducts(products) {
    productsContainer.innerHTML = '';

    if (!Array.isArray(products)) {
        productsContainer.innerHTML = '<p class="no-products">Invalid products data format</p>';
        return;
    }

    if (products.length === 0) {
        productsContainer.innerHTML = '<p class="no-products">No top-rated products found.</p>';
        return;
    }

    // Process each product
    for (const product of products) {
        // Ensure product has required properties
        const safeProduct = {
            product_id: product.product_id || 'N/A',
            title: product.title || 'Unnamed Product',
            image_url: product.image_url || '/src/private/resources/default.png',
            average_rating: product.average_rating || 0,
            cheapest_price: product.cheapest_price || null,
            retailer_name: product.retailer_name || 'Unknown retailer'
        };

        const productBox = document.createElement('div');
        productBox.className = 'product-box';

        // Generate star rating display
        let starsHtml = '';
        const rating = safeProduct.average_rating;

        if (rating > 0) {
            const fullStars = Math.floor(rating);
            const hasHalfStar = rating % 1 >= 0.5;

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
            // Show empty stars if no rating
            starsHtml = '<span class="material-symbols-outlined">star_outline</span>'.repeat(5);
        }

        productBox.innerHTML = `
            <div class="product-image">
                <img src="${safeProduct.image_url}" alt="${safeProduct.title}" onerror="this.src='/src/private/resources/default.png'">
            </div>
            <div class="product-content">
                <div class="product-info">
                    <h3 class="product-title">${safeProduct.title}</h3>
                    <div class="product-rating">
                        ${starsHtml}
                        <span class="rating-text">
                            ${rating ? rating.toFixed(1) : 'No rating'}
                        </span>
                    </div>
                </div>
                <div class="best-price">
                    ${safeProduct.cheapest_price ?
                `<span class="best-price-label">Best Price</span>
                         <span class="best-price-value">R${safeProduct.cheapest_price.toFixed(2)}</span>
                         <span class="retailer-label">From ${safeProduct.retailer_name}</span>` :
                '<span class="no-price">No prices available</span>'}
                </div>
                <button class="compare-btn" data-product-id="${safeProduct.product_id}">Compare Prices</button>
            </div>
        `;

        productsContainer.appendChild(productBox);
    }

    // Add event listeners to compare buttons
    document.querySelectorAll('.compare-btn').forEach(button => {
        button.addEventListener('click', function () {
            const productId = this.getAttribute('data-product-id');
            // Store the product ID in session storage
            sessionStorage.setItem('currentProductId', productId);
            // Navigate to view page
            window.location.href = '../html/view.php';
        });
    });
}

// Initialization
document.addEventListener('DOMContentLoaded', function () {
    applySavedTheme();
    loadProducts();

    // Only add event listeners if elements exist
    if (themeToggle) {
        themeToggle.addEventListener("click", function () {
            document.body.classList.toggle("dark");
            const newTheme = document.body.classList.contains("dark") ? "dark" : "light";
            setCookie("theme", newTheme, 30);
            updateIcon();
        });
    }

    if (menuToggle && navLinks) {
        menuToggle.addEventListener("click", () => navLinks.classList.toggle("show"));
    }

    updateIcon(); // Ensure correct icon on first load
});