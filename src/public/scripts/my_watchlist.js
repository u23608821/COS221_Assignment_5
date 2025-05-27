const API_URL = "https://wheatley.cs.up.ac.za/u24634434/COS221/api.php";
const headers = new Headers();
headers.append("Authorization", "Basic " + btoa(WHEATLEY_USERNAME + ":" + WHEATLEY_PASSWORD));
headers.append("Content-Type", "application/json");

// DOM Elements
const watchlistContainer = document.querySelector('.watchlist-container');
const currentApiKey = localStorage.getItem('apiKey') || sessionStorage.getItem('apiKey') || getCookie('apiKey');

document.addEventListener('DOMContentLoaded', () => {
    if (!currentApiKey) {
        alert('Please log in to view your watchlist');
        window.location.href = '../html/login.php';
        return;
    }
    loadWatchlist();
});

function getCookie(name) {
    const cookieArr = document.cookie.split(';');
    for (let c of cookieArr) {
        const [key, val] = c.trim().split('=');
        if (key === name) return decodeURIComponent(val);
    }
    return null;
}

async function loadWatchlist() {
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
            displayWatchlist(result.data);
        } else {
            showEmptyWatchlist(result.message || 'No items in your watchlist');
        }
    } catch (error) {
        console.error('Error loading watchlist:', error);
        showEmptyWatchlist('Error loading watchlist. Please try again later.');
    }
}

function displayWatchlist(products) {
    watchlistContainer.innerHTML = '';

    if (products.length === 0) {
        showEmptyWatchlist('Your watchlist is empty');
        return;
    }

    products.forEach(product => {
        const watchlistItem = document.createElement('div');
        watchlistItem.className = 'watchlist-box';
        watchlistItem.dataset.productId = product.product_id;

        watchlistItem.innerHTML = `
            <div class="product-image">
                <img src="${product.image_url || '../../private/resources/placeholder.png'}" 
                     alt="${product.title}" 
                     onerror="this.src='../../private/resources/placeholder.png'">
            </div>
            <div class="watchlist-main-content">
                <div class="watchlist-text-content">
                    <h3 class="watchlist-title">${product.title}</h3>
                    <div class="best-price">
                        <span class="best-price-label">Best Price</span>
                        <span class="best-price-value">R${product.cheapest_price?.toFixed(2) || 'N/A'}</span>
                        <span class="retailer-label">From ${product.retailer_name || 'Unknown'}</span>
                    </div>
                </div>
                <div class="watchlist-actions">
                    <button class="watchlist-btn view-btn">View</button>
                    <button class="watchlist-btn delete-btn">Delete</button>
                </div>
            </div>
        `;

        watchlistContainer.appendChild(watchlistItem);
    });

    // Attach event listeners
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', handleViewProduct);
    });

    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', handleDeleteFromWatchlist);
    });
}

function showEmptyWatchlist(message) {
    watchlistContainer.innerHTML = `
        <div class="empty-watchlist">
            <span class="material-symbols-outlined">visibility_off</span>
            <p>${message}</p>
        </div>
    `;
}

function handleViewProduct(e) {
    const productId = e.target.closest('.watchlist-box').dataset.productId;
    sessionStorage.setItem('currentProductId', productId);
    window.location.href = '../html/view.php';
}

async function handleDeleteFromWatchlist(e) {
    const productBox = e.target.closest('.watchlist-box');
    const productId = productBox.dataset.productId;

    
        try {
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: headers,
                body: JSON.stringify({
                    type: 'removeFromWatchlist',
                    apikey: currentApiKey,
                    product_id: parseInt(productId)
                })
            });

            const result = await response.json();

            if (result.status === 'success') {
                productBox.remove();
                if (document.querySelectorAll('.watchlist-box').length === 0) {
                    showEmptyWatchlist('Your watchlist is empty');
                }
            } else {
                alert('Failed to remove item: ' + (result.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error removing from watchlist:', error);
            alert('Error removing item. Please try again.');
        }
    
}