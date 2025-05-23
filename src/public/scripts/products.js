// Configuration constants
const API_URL = 'https://wheatley.cs.up.ac.za/u24634434/COS221/api.php';
const WHEATLEY_USERNAME = 'u24634434'; // Replace with actual
const WHEATLEY_PASSWORD = 'Norirotti218754'; // Replace with actual
const productsPerPage = 10; // Define how many products per page

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
async function fetchProducts(filters = {}, page = 1) {
    try {
        const apiKey = getCookie("userapikey");
        if (!apiKey) {
            window.location.href = "../html/login.php";
            return { products: [], total: 0 };
        }

        const requestBody = {
            type: "getAllProducts",
            apikey: apiKey,
            limit: productsPerPage,
            offset: (page - 1) * productsPerPage,
        };

        // Apply filters if provided
        if (filters.name) requestBody.name = filters.name;
        if (filters.category) requestBody.category = filters.category;
        if (filters.sort_by) requestBody.sort_by = filters.sort_by;
        if (filters.include_no_price !== undefined) requestBody.include_no_price = filters.include_no_price;
        if (filters.include_no_rating !== undefined) requestBody.include_no_rating = filters.include_no_rating;
        if (filters.filter_by) requestBody.filter_by = filters.filter_by;

        const response = await fetch(API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Basic ' + btoa(WHEATLEY_USERNAME + ":" + WHEATLEY_PASSWORD)
            },
            body: JSON.stringify(requestBody)
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.status === "success") {
            return {
                products: data.data,
                total: data.total_count || data.data.length * page
            };
        } else {
            console.error("API Error:", data.message);
            showErrorToUser(data.message || "Failed to load products");
            return { products: [], total: 0 };
        }
    } catch (error) {
        console.error("Fetch Error:", error);
        showErrorToUser("Network error. Please try again.");
        return { products: [], total: 0 };
    }
}

// Alternative XMLHttpRequest version if needed
function fetchProductsXHR(filters = {}, page = 1, callback) {
    const apiKey = getCookie("userapikey");
    if (!apiKey) {
        window.location.href = "../html/login.php";
        return;
    }

    const requestBody = {
        type: "getAllProducts",
        apikey: apiKey,
        limit: productsPerPage,
        offset: (page - 1) * productsPerPage,
    };

    const xhr = new XMLHttpRequest();
    xhr.open('POST', API_URL, true);
    xhr.setRequestHeader("Content-Type", "application/json");
    xhr.setRequestHeader("Authorization", "Basic " + btoa(WHEATLEY_USERNAME + ":" + WHEATLEY_PASSWORD));

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    if (data.status === "success") {
                        callback({
                            products: data.data,
                            total: data.total_count || data.data.length * page
                        });
                    } else {
                        console.error("API Error:", data.message);
                        showErrorToUser(data.message);
                        callback({ products: [], total: 0 });
                    }
                } catch (e) {
                    console.error("Parsing Error:", e);
                    showErrorToUser("Invalid server response");
                    callback({ products: [], total: 0 });
                }
            } else {
                console.error("HTTP Error:", xhr.status);
                showErrorToUser("Server error: " + xhr.status);
                callback({ products: [], total: 0 });
            }
        }
    };

    xhr.send(JSON.stringify(requestBody));
}

// Helper function to display errors to users
function showErrorToUser(message) {
    const errorElement = document.getElementById('api-error') || createErrorElement();
    errorElement.textContent = message;
    errorElement.style.display = 'block';
    
    setTimeout(() => {
        errorElement.style.display = 'none';
    }, 5000);
}

function createErrorElement() {
    const errorDiv = document.createElement('div');
    errorDiv.id = 'api-error';
    errorDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px;
        background: #ff4444;
        color: white;
        border-radius: 5px;
        z-index: 10000;
        display: none;
    `;
    document.body.appendChild(errorDiv);
    return errorDiv;
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
    const { products } = await fetchProducts(filters);
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
    const { products } = await fetchProducts();
    renderProducts(products);
    
    // Populate categories dropdown
    await populateCategories();
});

// Populate categories dropdown
async function populateCategories() {
    try {
        const apiKey = getCookie("userapikey");
        if (!apiKey) return;

        const response = await fetch(API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Basic ' + btoa(WHEATLEY_USERNAME + ":" + WHEATLEY_PASSWORD)
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
                if (category) {
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