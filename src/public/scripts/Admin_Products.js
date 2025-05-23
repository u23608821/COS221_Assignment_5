
const accountBtn = document.getElementById("accountBtn");
const accountMenu = document.getElementById("accountMenu");
const themeToggle = document.getElementById("themeToggle");
const themeIcon = document.getElementById("themeIcon");
const menuToggle = document.getElementById("menuToggle");
const navLinks = document.getElementById("navLinks");


function showSuccessMessage(elementId, message) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    element.innerHTML = message;
    element.className = 'result-message success';
    element.style.display = 'block';
    
    setTimeout(() => {
        element.style.display = 'none';
    }, 5000);
}

function showErrorMessage(elementId, message) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    element.innerHTML = message;
    element.className = 'result-message error';
    element.style.display = 'block';
    
    setTimeout(() => {
        element.style.display = 'none';
    }, 5000);
}

function getAdminApiKey() {
    return localStorage.getItem('adminApiKey') || 
           sessionStorage.getItem('adminApiKey') || 
           getCookie('adminApiKey');
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

function setCookie(name, value, days) {
  const d = new Date();
  d.setTime(d.getTime() + (days*24*60*60*1000));
  let expires = "expires=" + d.toUTCString();
  document.cookie = name + "=" + value + ";" + expires + ";path=/";
}

function getCookie(name) {
  let cname = name + "=";
  let decodedCookie = decodeURIComponent(document.cookie);
  let ca = decodedCookie.split(';');
  for(let i = 0; i < ca.length; i++) {
    let c = ca[i].trim();
    if (c.indexOf(cname) === 0) {
      return c.substring(cname.length, c.length);
    }
  }
  return "";
}

// Product Form Handling
document.getElementById('product-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const adminApiKey = getAdminApiKey();
    if (!adminApiKey) {
        showErrorMessage('productFormMessage', 'Admin session expired. Please log in again.');
        return;
    }

    const formData = {
        type: 'AddNewProduct',
        apikey: adminApiKey,
        name: document.getElementById('product-name').value.trim(),
        description: document.getElementById('product-description').value.trim(),
        price: parseFloat(document.getElementById('product-price').value),
        retailer_id: parseInt(document.getElementById('product-retailer').value),
        image_url: document.getElementById('product-image').value.trim(),
        category: document.getElementById('product-category').value.trim()
    };

    // Validate required fields
    if (!formData.name) {
        showErrorMessage('productFormMessage', 'Product name is required');
        return;
    }

    // Validate retailer_id and price - both must be provided if either exists
    if ((formData.retailer_id && !formData.price) || (!formData.retailer_id && formData.price)) {
        showErrorMessage('productFormMessage', 'Both retailer and price must be provided together');
        return;
    }

    try {
        const response = await fetch('http://localhost:8000/api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });

        const result = await response.json();
        
        if (result.status === 'success') {
            let successMessage = 'Product added successfully!';
            if (result.message.includes('updated')) {
                successMessage = 'Product price updated for retailer!';
            }
            showSuccessMessage('productFormMessage', successMessage);
            this.reset();
            loadProducts(); // Refresh the product list
        } else {
            let errorMessage = result.message;
            if (result.data) {
                errorMessage += '<br><small>' + Object.values(result.data).join('<br>') + '</small>';
            }
            showErrorMessage('productFormMessage', errorMessage);
        }
    } catch (error) {
        console.error('Error adding product:', error);
        showErrorMessage('productFormMessage', 'Failed to add product. Please try again.');
    }
});






// Function to load products from API
async function loadProducts() {
    const adminApiKey = getAdminApiKey();
    if (!adminApiKey) {
        console.error('Admin not authenticated');
        return;
    }

    const productList = document.getElementById('product-list');
    productList.innerHTML = '<tr><td colspan="5" class="loading">Loading products...</td></tr>';

    try {
        const response = await fetch('http://localhost:8000/api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: 'getAllProducts',
                apikey: adminApiKey
                
            })
        });

        const result = await response.json();
        
        if (result.status === 'success') {
            renderProducts(result.data);
        } else {
            productList.innerHTML = '<tr><td colspan="4" class="error">Error loading products: ' + result.message + '</td></tr>';
        }
    } catch (error) {
        console.error('Error loading products:', error);
        productList.innerHTML = '<tr><td colspan="4" class="error">Failed to load products. Please try again.</td></tr>';
    }
}





// Render products in the table
function renderProducts(products) {
    const productList = document.getElementById('product-list');

    if (!products || products.length === 0) {
        productList.innerHTML = '<tr><td colspan="4">No products found</td></tr>';
        return;
    }

    let html = '';
    products.forEach(product => {
        html += `
            <tr data-product-id="${product.id}">
                <td>${product.id}</td>
                <td>
                    <span class="product-name">${product.name}</span>
                    <input type="text" class="product-name-edit" value="${product.name}" style="display: none;">
                </td>
                <td>
                    <span class="product-description">${product.description}</span>
                    <input type="text" class="product-description-edit" value="${product.description}" style="display: none;">
                </td>
                <td class="actions">
                    <button class="btn-sm btn-edit" onclick="toggleEditMode(this, ${product.id})">Edit</button>
                    <button class="btn-sm btn-save" onclick="saveProduct(${product.id})" style="display: none;">Save</button>
                    <button class="btn-sm btn-cancel" onclick="cancelEdit(${product.id})" style="display: none;">Cancel</button>
                    <button class="btn-sm btn-danger" onclick="deleteProduct(${product.id})">Delete</button>
                </td>
            </tr>
        `;
    });

    productList.innerHTML = html;
}




// Toggle edit mode for a product
function toggleEditMode(button, productId) {
    const row = button.closest('tr');
    row.querySelector('.product-name').style.display = 'none';
    row.querySelector('.product-description').style.display = 'none';
    row.querySelector('.product-name-edit').style.display = 'inline-block';
    row.querySelector('.product-description-edit').style.display = 'inline-block';
    row.querySelector('.btn-edit').style.display = 'none';
    row.querySelector('.btn-save').style.display = 'inline-block';
    row.querySelector('.btn-cancel').style.display = 'inline-block';
}

// Cancel edit mode
function cancelEdit(productId) {
    const row = document.querySelector(`tr[data-product-id="${productId}"]`);
    if (!row) return;

    row.querySelector('.product-name').style.display = 'inline';
    row.querySelector('.product-description').style.display = 'inline';
    row.querySelector('.product-name-edit').style.display = 'none';
    row.querySelector('.product-description-edit').style.display = 'none';
    row.querySelector('.btn-edit').style.display = 'inline-block';
    row.querySelector('.btn-save').style.display = 'none';
    row.querySelector('.btn-cancel').style.display = 'none';
}



// Save product changes
async function saveProduct(productId) {
    const row = document.querySelector(`tr[data-product-id="${productId}"]`);
    if (!row) return;

    const newName = row.querySelector('.product-name-edit').value.trim();
    const newDescription = row.querySelector('.product-description-edit').value.trim();

    // Don't proceed if both name and description are empty
    if (!newName && !newDescription) {
        showErrorMessage('productListMessage', 'Please enter a new name and/or description.');
        return;
    }

    const adminApiKey = getAdminApiKey();
    if (!adminApiKey) {
        showErrorMessage('productListMessage', 'Admin session expired. Please log in again.');
        return;
    }

    // Build request payload with only provided fields
    const payload = {
        type: 'editProduct',
        apikey: adminApiKey,
        product_id: productId
    };

    if (newName) payload.name = newName;
    if (newDescription) payload.description = newDescription;

    try {
        const response = await fetch('http://localhost:8000/api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (result.status === 'success') {
            showSuccessMessage('productListMessage', 'Product updated successfully!');
            loadProducts(); // Refresh the list
        } else {
            showErrorMessage('productListMessage', result.message || 'Failed to update product');
            cancelEdit(productId); // Revert changes
        }
    } catch (error) {
        console.error('Error updating product:', error);
        showErrorMessage('productListMessage', 'Failed to update product. Please try again.');
        cancelEdit(productId); // Revert changes
    }
}


// Delete a product
async function deleteProduct(productId) {
    if (!confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
        return;
    }

    const adminApiKey = getAdminApiKey();
    if (!adminApiKey) {
        showErrorMessage('productListMessage', 'Admin session expired. Please log in again.');
        return;
    }

    try {
        const response = await fetch('http://localhost:8000/api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: 'deleteProduct',
                apikey: adminApiKey,
                product_id: productId
            })
        });

        const result = await response.json();
        
        if (result.status === 'success') {
            showSuccessMessage('productListMessage', 'Product deleted successfully!');
            loadProducts(); // Refresh the list
        } else {
            showErrorMessage('productListMessage', result.message || 'Failed to delete product');
        }
    } catch (error) {
        console.error('Error deleting product:', error);
        showErrorMessage('productListMessage', 'Failed to delete product. Please try again.');
    }
}

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    loadProducts();
});





// Product Form Handling
document.getElementById('product-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = {
      name: document.getElementById('product-name').value,
      description: document.getElementById('product-description').value,
      price: parseFloat(document.getElementById('product-price').value),
      retailer_id: parseInt(document.getElementById('product-retailer').value),
      image_url: document.getElementById('product-image').value,
      category: document.getElementById('product-category').value
    };
    
    // Here you would typically send this data to your backend
    console.log('Form submitted:', formData);
    
    // Simulate successful submission
    alert('Product saved successfully!');
    this.reset();
    
    // In a real app, you would refresh the product list here
    // loadProducts();
  });
  

  
  // Initialize the page
  document.addEventListener('DOMContentLoaded', function() {
    loadProducts();
    loadRetailers();
  });


  // Function to load retailers from API
async function loadRetailers() {
    const adminApiKey = getAdminApiKey();
    if (!adminApiKey) {
        console.error('Admin not authenticated');
        return;
    }

    try {
        const response = await fetch('http://localhost:8000/api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: 'GetAllRetailers',
                apikey: adminApiKey
            })
        });

        const result = await response.json();
        
        if (result.status === 'success') {
            populateRetailerDropdown(result.data);
        } else {
            console.error('Error loading retailers:', result.message);
        }
    } catch (error) {
        console.error('Error loading retailers:', error);
    }
}

// Populate retailer dropdown
function populateRetailerDropdown(retailers) {
    const retailerSelect = document.getElementById('product-retailer');
    if (!retailerSelect) return;

    // Clear existing options except the first one
    while (retailerSelect.options.length > 1) {
        retailerSelect.remove(1);
    }

    // Add retailers from API
    if (retailers && retailers.length > 0) {
        retailers.forEach(retailer => {
            const option = document.createElement('option');
            option.value = retailer.id;
            option.textContent = retailer.name;
            retailerSelect.appendChild(option);
        });
    }
}

