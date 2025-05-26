const API_URL = "https://wheatley.cs.up.ac.za/u24634434/COS221/api.php"; // API base URL
const headers = new Headers();
headers.append("Authorization", "Basic " + btoa(WHEATLEY_USERNAME + ":" + WHEATLEY_PASSWORD));
headers.append("Content-Type", "application/json");

// DOM Elements
const accountBtn = document.getElementById("accountBtn");
const accountMenu = document.getElementById("accountMenu");
const themeToggle = document.getElementById("themeToggle");
const themeIcon = document.getElementById("themeIcon");
const menuToggle = document.getElementById("menuToggle");
const navLinks = document.getElementById("navLinks");
const searchInput = document.querySelector(".search-input");
const searchBtn = document.querySelector(".search-btn");
const categoryFilter = document.querySelector(".filter-select:nth-of-type(1)");
const sortFilter = document.querySelector(".filter-select:nth-of-type(2)");
const priceFilter = document.querySelector(".filter-select:nth-of-type(3)");
const productsContainer = document.getElementById("products-list");

let currentApiKey =
    localStorage.getItem('apiKey') ||
    sessionStorage.getItem('apiKey') ||
    getCookie('apiKey');

// Helper functions
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

// Product loading functions
async function loadCategories() {
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: headers,

            body: JSON.stringify({
                type: 'getAllCategories',
                apikey: currentApiKey
            })
        });

        const result = await response.json();

        if (result.status === 'success') {
            populateCategoryFilter(result.data);
        } else {
            console.error('Error loading categories:', result.message);
            // Show error to user if needed
        }
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

function populateCategoryFilter(categories) {
    // Clear existing options except the first one
    while (categoryFilter.options.length > 1) {
        categoryFilter.remove(1);
    }

    // Add new categories
    categories.forEach(category => {
        const option = document.createElement('option');
        option.value = category;
        option.textContent = category;
        categoryFilter.appendChild(option);
    });
}

async function loadProducts(searchTerm = '', category = '', sortBy = '', priceRange = '') {
    try {
        productsContainer.innerHTML = '<div class="loading-spinner">Loading products...</div>';

        // Clean category param - only send category if not "All Categories"
        category = category === "__all__" ? "" : category;

        // Determine if we need to filter on client-side
        let localFilterBy = {};
        let filteredClientSide = false;

        if (priceRange) {
            const [min, max] = priceRange.split('-').map(str => parseFloat(str));
            localFilterBy.minPrice = min;
            localFilterBy.maxPrice = max;
            filteredClientSide = true;
        }

        // 🔄 API request to get products
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: headers,

            body: JSON.stringify({
                type: 'getAllProducts',
                apikey: currentApiKey,
                name: searchTerm || null,
                category: category || null,
                sort_by: sortBy || null
            })
        });

        const result = await response.json();

        if (result.status === 'success') {
            let products = result.data;

            // Apply price filtering manually if needed
            if (filteredClientSide && priceRange) {
                products = products.filter(p =>
                    p.cheapest_price !== null &&
                    p.cheapest_price >= localFilterBy.minPrice &&
                    p.cheapest_price <= localFilterBy.maxPrice
                );
            }

            displayProducts(products);
        } else {
            console.error('Error loading products:', result.message);
            productsContainer.innerHTML = '<p class="error-message">Failed to load products. Please try again later.</p>';
        }

    } catch (error) {
        console.error('Error loading products:', error);
        productsContainer.innerHTML = '<p class="error-message">Network error. Please check your connection.</p>';
    }
}


async function displayProducts(products) {
    productsContainer.innerHTML = '';

    if (products.length === 0) {
        productsContainer.innerHTML = '<p class="no-products">No products found matching your criteria.</p>';
        return;
    }

    for (const product of products) {
        let averageRating = product.average_rating;
        let reviewCount = 0;
        try {
            const detailResponse = await fetch(API_URL, {
                method: 'POST',
                headers: headers,

                body: JSON.stringify({
                    type: 'getProductDetails',
                    apikey: currentApiKey,
                    product_id: product.product_id,
                    return: "Reviews"
                })
            });

            const detailResult = await detailResponse.json();

            if (detailResult.status === 'success') {
                const reviews = Array.isArray(detailResult.data) ? detailResult.data : [];
                reviewCount = reviews.length;
                // Optional: estimate average rating if needed
                if (reviews.length > 0) {
                    const total = reviews.reduce((sum, r) => sum + r.score, 0);
                    averageRating = total / reviews.length;
                } else {
                    averageRating = null;
                }
            } else {
                console.warn(`No reviews for product ID ${product.product_id}`, detailResult);
            }
        } catch (err) {
            console.warn(`Details failed for product ID ${product.product_id}`, err);
        }

        const productBox = document.createElement('div');
        productBox.className = 'product-box';

        let starsHtml = '';
        if (averageRating !== null) {
            const fullStars = Math.floor(averageRating);
            const hasHalfStar = averageRating % 1 >= 0.25 && averageRating % 1 < 0.75;
            const totalStars = hasHalfStar ? fullStars + 1 : fullStars;

            for (let i = 0; i < 5; i++) {
                if (i < fullStars) {
                    starsHtml += '<span class="material-symbols-outlined">star</span>';
                } else if (i === fullStars && hasHalfStar) {
                    starsHtml += '<span class="material-symbols-outlined">star_half</span>';
                } else {
                    starsHtml += '<span class="material-symbols-outlined">star_border</span>';
                }
            }
        } else {
            // If no rating, show all empty
            for (let i = 0; i < 5; i++) {
                starsHtml += '<span class="material-symbols-outlined">star_border</span>';
            }
        }

        productBox.innerHTML = `
            <div class="product-image">
                <img src="${product.image_url}" alt="${product.title}">
            </div>
            <div class="product-content">
                <div class="product-info">
                    <h3 class="product-title">${product.title}</h3>
                    <div class="product-rating">
                        ${starsHtml}
                        <span class="rating-text">
                            ${averageRating ? averageRating.toFixed(1) : 'No reviews'}
                            ${reviewCount ? ` (${reviewCount})` : ''}
                        </span>
                    </div>
                </div>
                <div class="best-price">
                    ${product.cheapest_price ?
                `<span class="best-price-label">Best Price</span>
                         <span class="best-price-value">R${product.cheapest_price.toFixed(2)}</span>
                         <span class="retailer-label">From ${product.retailer_name}</span>` :
                '<span class="no-price">No prices available</span>'}
                </div>
                <button class="compare-btn" data-product-id="${product.product_id}">Compare Prices</button>
            </div>
        `;

        productsContainer.appendChild(productBox);
    }

    // Add click events
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

// Event listeners for search and filters
searchBtn.addEventListener('click', performSearch);
searchInput.addEventListener('keypress', function (e) {
    if (e.key === 'Enter') performSearch();
});

// Attach event listeners for filters - don't add duplicate listeners
categoryFilter.addEventListener('change', performSearch);
sortFilter.addEventListener('change', performSearch);
priceFilter.addEventListener('change', performSearch);

function performSearch() {
    const searchTerm = searchInput.value.trim();
    const category = categoryFilter.value;
    const sortBy = getSortValue(sortFilter.value);
    const priceRange = priceFilter.value;
    
    console.log('Filtering products by:', {
        searchTerm: searchTerm || 'None',
        category: category || 'All',
        sortBy: sortBy || 'Default',
        priceRange: priceRange || 'Any'
    });
    
    loadProducts(searchTerm, category, sortBy, priceRange);
}

function getSortValue(selectValue) {
    switch (selectValue) {
        case 'price-low': return 'price_asc';
        case 'price-high': return 'price_desc';
        case 'rating': return 'rating_desc';
        case 'name-asc': return 'name_asc';
        case 'name-desc': return 'name_desc';
        default: return 'name_asc';
    }
}

// Initialize the page
document.addEventListener('DOMContentLoaded', function () {
    applySavedTheme();
    loadCategories();
    loadProducts();
});

// Existing event listeners
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

categoryFilter.addEventListener('change', () => {
    if (categoryFilter.value === '__all__') {
        searchInput.value = '';
        sortFilter.selectedIndex = 0;
        priceFilter.selectedIndex = 0;
    }
    performSearch();
});
