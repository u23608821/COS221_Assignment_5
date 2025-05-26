// === Configuration Constants ===
const API_URL = '../scripts/fetchProducts.php';
const PRODUCTS_PER_PAGE = 12;
const RPC_TIMEOUT = 15000;

// === DOM Elements ===
const productsContainer = document.getElementById('products-list');
const loadingIndicator = document.getElementById('loadingIndicator');
const noProductsMessage = document.getElementById('noProductsMessage');
const searchInput = document.getElementById('searchInput');
const searchBtn = document.getElementById('searchBtn');
const categoryFilter = document.getElementById('categoryFilter');
const sortFilter = document.getElementById('sortFilter');
const priceRangeFilter = document.getElementById('priceRangeFilter');

// === Cookie Utilities ===
function setCookie(name, value, days) {
    const d = new Date();
    d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
    document.cookie = `${name}=${value};expires=${d.toUTCString()};path=/`;
}

function getCookie(name) {
    const cname = name + "=";
    const decodedCookie = decodeURIComponent(document.cookie);
    const ca = decodedCookie.split(';');
    for (let c of ca) {
        c = c.trim();
        if (c.indexOf(cname) === 0) {
            return c.substring(cname.length);
        }
    }
    return "";
}

// === Theme Utility ===
function updateIcon() {
    themeIcon.textContent = document.body.classList.contains("dark") ? "light_mode" : "dark_mode";
}

function applySavedTheme() {
    const savedTheme = getCookie("theme");
    document.body.classList.toggle("dark", savedTheme === "dark");
    updateIcon();
}

// === Error Display ===
function showErrorToUser(message) {
    const el = document.getElementById('api-error') || createErrorElement();
    el.textContent = message;
    el.style.display = 'block';
    setTimeout(() => el.style.display = 'none', 5000);
}

function createErrorElement() {
    const div = document.createElement('div');
    div.id = 'api-error';
    div.style.cssText = `
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
    document.body.appendChild(div);
    return div;
}

// === API Request ===
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
            limit: PRODUCTS_PER_PAGE,
            offset: (page - 1) * PRODUCTS_PER_PAGE,
            ...filters
        };

        const response = await fetch(API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestBody)
        });

        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const data = await response.json();

        if (data.status === "success") {
            return { products: data.data, total: data.total_count || data.data.length * page };
        } else {
            showErrorToUser(data.message || "Failed to load products");
            return { products: [], total: 0 };
        }
    } catch (error) {
        console.error("Fetch Error:", error);
        showErrorToUser("Network error. Please try again.");
        return { products: [], total: 0 };
    }
}

// === Rendering Functions ===
function renderProducts(products, append = false) {
    if (!append) productsContainer.innerHTML = '';
    
    if (!products.length && !append) {
        noProductsMessage.style.display = 'block';
        return;
    }

    noProductsMessage.style.display = 'none';

    products.forEach(product => {
        const productElement = document.createElement('div');
        productElement.className = 'product-box';

        const formattedPrice = product.cheapest_price ?
            `R${product.cheapest_price.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",")}` :
            'No price available';

        const stars = createStarRating(product.average_rating);

        productElement.innerHTML = `
            <div class="product-image">
                <img src="${product.image_url || '../images/placeholder-product.jpg'}" alt="${product.title}">
            </div>
            <div class="product-content">
                <div class="product-info">
                    <h3 class="product-title">${product.title}</h3>
                    <div class="product-rating">${stars}<span class="rating-text">${product.average_rating ? product.average_rating.toFixed(1) + ' stars' : 'No reviews'}</span></div>
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
}

function createStarRating(rating) {
    if (!rating) return '';
    const full = Math.floor(rating);
    const half = rating % 1 >= 0.5;
    const empty = 5 - full - (half ? 1 : 0);

    return (
        '<span class="material-symbols-outlined">star</span>'.repeat(full) +
        (half ? '<span class="material-symbols-outlined">star_half</span>' : '') +
        '<span class="material-symbols-outlined">star</span>'.repeat(empty)
    );
}

// === Filter Logic ===
function getCurrentFilters() {
    const filters = {};
    if (searchInput?.value) filters.name = searchInput.value;
    if (categoryFilter?.value) filters.category = categoryFilter.value;

    if (sortFilter?.value) {
        const sortMap = {
            'price-low': 'price_asc',
            'price-high': 'price_desc',
            'rating': 'rating_desc',
            'rating-worst': 'rating_asc',
            'name-asc': 'name_asc',
            'name-desc': 'name_desc'
        };
        filters.sort_by = sortMap[sortFilter.value];
    }

    if (priceRangeFilter?.value) {
        filters.filter_by = {};
        const val = priceRangeFilter.value;
        if (val === 'range1') filters.filter_by.max_price = 99.99;
        else if (val === 'range2') Object.assign(filters.filter_by, { min_price: 100, max_price: 999.99 });
        else if (val === 'range3') Object.assign(filters.filter_by, { min_price: 1000, max_price: 9999.99 });
        else if (val === 'range4') filters.filter_by.min_price = 10000;
    }

    return filters;
}

// === Initialization ===
document.addEventListener('DOMContentLoaded', async () => {
    applySavedTheme();
    updateIcon();

    const username = getCookie('username');
    if (username) {
        const userText = document.querySelector('.user-text');
        if (userText) userText.textContent = username;
    }

    await applyFilters();

    searchBtn?.addEventListener('click', applyFilters);
    searchInput?.addEventListener('keypress', e => e.key === 'Enter' && applyFilters());
    categoryFilter?.addEventListener('change', applyFilters);
    sortFilter?.addEventListener('change', applyFilters);
    priceRangeFilter?.addEventListener('change', applyFilters);

    accountBtn?.addEventListener("click", () => accountMenu.classList.toggle("show"));
    themeToggle?.addEventListener("click", () => {
        document.body.classList.toggle("dark");
        setCookie("theme", document.body.classList.contains("dark") ? "dark" : "light", 30);
        updateIcon();
    });
    window.addEventListener("click", e => {
        if (!accountBtn.contains(e.target) && !accountMenu.contains(e.target)) {
            accountMenu.classList.remove("show");
        }
    });
});