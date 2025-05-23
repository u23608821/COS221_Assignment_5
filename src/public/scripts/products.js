// DOM Elements
const accountBtn = document.getElementById("accountBtn");
const accountMenu = document.getElementById("accountMenu");
const themeToggle = document.getElementById("themeToggle");
const themeIcon = document.getElementById("themeIcon");
const menuToggle = document.getElementById("menuToggle");
const navLinks = document.getElementById("navLinks");
const productsContainer = document.getElementById("products-list");
const searchInput = document.querySelector(".search-input");
const searchBtn = document.querySelector(".search-btn");
const categoryFilter = document.querySelectorAll(".filter-select")[0];
const sortFilter = document.querySelectorAll(".filter-select")[1];
const priceRangeFilter = document.querySelectorAll(".filter-select")[2];

// Theme functions
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

// Cookie functions
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

// Event Listeners
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

// Initialize
updateIcon();
applySavedTheme();

// API Functions
async function fetchProducts(filters = {}) {
    try {
        // Get API key from cookies
        const apiKey = getCookie("userapikey");
        if (!apiKey) {
            window.location.href = "../html/login.php";
            return;
        }

        // Prepare request body
        const requestBody = {
            type: "getAllProducts",
            apikey: apiKey
        };

        // Apply filters if provided
        if (filters.name) requestBody.name = filters.name;
        if (filters.category) requestBody.category = filters.category;
        if (filters.sort_by) requestBody.sort_by = filters.sort_by;
        if (filters.include_no_price !== undefined) requestBody.include_no_price = filters.include_no_price;
        if (filters.include_no_rating !== undefined) requestBody.include_no_rating = filters.include_no_rating;
        if (filters.filter_by) requestBody.filter_by = filters.filter_by;
        if (filters.limit) requestBody.limit = filters.limit;

        // Make API request
        const response = await fetch('https://your-api-endpoint.com/api', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestBody)
        });

        const data = await response.json();

        if (data.status === "success") {
            return data.data;
        } else {
            console.error("Error fetching products:", data.message);
            return [];
        }
    } catch (error) {
        console.error("Error fetching products:", error);
        return [];
    }
}

function renderProducts(products) {
    // Clear existing products
    productsContainer.innerHTML = '';

    if (products.length === 0) {
        productsContainer.innerHTML = '<p class="no-products">No products found. Try adjusting your filters.</p>';
        return;
    }

    // Render each product
    products.forEach(product => {
        const productElement = document.createElement('div');
        productElement.className = 'product-box';
        
        // Format price with R and commas
        const formattedPrice = product.cheapest_price ? 
            `R${product.cheapest_price.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",")}` : 
            'No price available';
        
        // Create stars based on average rating
        const stars = createStarRating(product.average_rating);
        
        productElement.innerHTML = `
            <div class="product-image">
                <img src="${product.image_url || '../images/placeholder-product.jpg'}" alt="${product.title}">
            </div>
            <div class="product-content">
                <div class="product-info">
                    <h3 class="product-title">${product.title}</h3>
                    <div class="product-rating">
                        ${stars}
                        <span class="rating-text">${product.average_rating ? product.average_rating.toFixed(1) : 'No'} ${product.average_rating ? 'stars' : 'reviews'}</span>
                    </div>
                </div>
                <div class="best-price">
                    <span class="best-price-label">Best Price</span>
                    <span class="best-price-value">${formattedPrice}</span>
                    <span class="retailer-label">${product.retailer_name ? 'From ' + product.retailer_name : ''}</span>
                </div>
                <button class="compare-btn" data-product-id="${product.product_id}">Compare Prices</button>
            </div>
        `;
        
        productsContainer.appendChild(productElement);
    });

    // Add event listeners to compare buttons
    document.querySelectorAll('.compare-btn').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            // You can implement navigation to product details page here
            window.location.href = `product_details.html?product_id=${productId}`;
        });
    });
}

function createStarRating(rating) {
    if (!rating) return '';
    
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 >= 0.5;
    const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
    
    let starsHTML = '';
    
    // Full stars
    for (let i = 0; i < fullStars; i++) {
        starsHTML += '<span class="material-symbols-outlined">star</span>';
    }
    
    // Half star
    if (hasHalfStar) {
        starsHTML += '<span class="material-symbols-outlined">star_half</span>';
    }
    
    // Empty stars
    for (let i = 0; i < emptyStars; i++) {
        starsHTML += '<span class="material-symbols-outlined">star</span>';
    }
    
    return starsHTML;
}

// Filter and Search Functions
async function applyFilters() {
    const filters = {};
    
    // Search term
    if (searchInput.value) {
        filters.name = searchInput.value;
    }
    
    // Category filter
    if (categoryFilter.value) {
        filters.category = categoryFilter.value;
    }
    
    // Sort filter
    if (sortFilter.value) {
        switch (sortFilter.value) {
            case 'price-low':
                filters.sort_by = 'price_asc';
                break;
            case 'price-high':
                filters.sort_by = 'price_desc';
                break;
            case 'rating':
                filters.sort_by = 'rating_desc';
                break;
            case 'rating-worst':
                filters.sort_by = 'rating_asc';
                break;
            case 'name-asc':
                filters.sort_by = 'name_asc';
                break;
            case 'name-desc':
                filters.sort_by = 'name_desc';
                break;
        }
    }
    
    // Price range filter
    if (priceRangeFilter.value) {
        filters.filter_by = {};
        switch (priceRangeFilter.value) {
            case 'range1':
                filters.filter_by.max_price = 99.99;
                break;
            case 'range2':
                filters.filter_by.min_price = 100;
                filters.filter_by.max_price = 999.99;
                break;
            case 'range3':
                filters.filter_by.min_price = 1000;
                filters.filter_by.max_price = 9999.99;
                break;
            case 'range4':
                filters.filter_by.min_price = 10000;
                break;
        }
    }
    
    // Fetch and render products with filters
    const products = await fetchProducts(filters);
    renderProducts(products);
}

// Event Listeners for filters
searchBtn.addEventListener('click', applyFilters);
searchInput.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        applyFilters();
    }
});

categoryFilter.addEventListener('change', applyFilters);
sortFilter.addEventListener('change', applyFilters);
priceRangeFilter.addEventListener('change', applyFilters);

// Load products when page loads
document.addEventListener('DOMContentLoaded', async function() {
    // Set user name if available
    const userName = getCookie("username");
    if (userName) {
        document.querySelector('.user-text').textContent = userName;
    }
    
    // Load initial products
    const products = await fetchProducts();
    renderProducts(products);
    
    // Populate categories dropdown
    await populateCategories();
});

// Populate categories dropdown
async function populateCategories() {
    try {
        const apiKey = getCookie("userapikey");
        if (!apiKey) return;

        const response = await fetch('https://your-api-endpoint.com/api', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                type: "getAllCategories",
                apikey: apiKey
            })
        });

        const data = await response.json();

        if (data.status === "success" && data.data && data.data.length > 0) {
            // Clear existing options except the first one
            while (categoryFilter.options.length > 1) {
                categoryFilter.remove(1);
            }

            // Add new categories
            data.data.forEach(category => {
                if (category) { // Check if category is not null or empty
                    const option = document.createElement('option');
                    option.value = category;
                    option.textContent = category;
                    categoryFilter.appendChild(option);
                }
            });
        }
    } catch (error) {
        console.error("Error fetching categories:", error);
    }
}