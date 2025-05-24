const accountBtn = document.getElementById("accountBtn");
const accountMenu = document.getElementById("accountMenu");
const themeToggle = document.getElementById("themeToggle");
const themeIcon = document.getElementById("themeIcon");
const menuToggle = document.getElementById("menuToggle");
const navLinks = document.getElementById("navLinks");

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

// Retailer Form Handling
document.getElementById('retailer-form').addEventListener('submit', function(e) {
  e.preventDefault();
  
  const formData = {
    name: document.getElementById('retailer-name').value,
    email: document.getElementById('retailer-email').value,
    street_number: document.getElementById('retailer-street-number').value,
    street_name: document.getElementById('retailer-street-name').value,
    suburb: document.getElementById('retailer-suburb').value,
    city: document.getElementById('retailer-city').value,
    zipcode: document.getElementById('retailer-zipcode').value
  };
  
});

// Update Retailer Button
document.getElementById('update-retailer').addEventListener('click', function() {
  const retailerId = document.getElementById('retailer-select').value;
  const name = document.getElementById('edit-name').value;
  const email = document.getElementById('edit-email').value;


  if (result.status === 'success') {

    
    showSuccessMessage('retailerListMessage', 'Retailer updated successfully!');
    
    loadRetailers(); // Refresh the list
 
} else {
    let errorMessage = result.message;
    if (result.data) {
        errorMessage += '<br><small>' + Object.values(result.data).join('<br>') + '</small>';
    }
    showErrorMessage('retailerListMessage', errorMessage);
}

resetRetailerFormAndHideDetails();

  
});

function resetRetailerFormAndHideDetails() {
    const retailerSelect = document.getElementById('retailer-select');
    const detailsSection = document.getElementById('retailer-details');

    if (retailerSelect) retailerSelect.value = '';
    if (detailsSection) detailsSection.classList.add('hidden');
}


// Delete Retailer Button
document.getElementById('delete-retailer').addEventListener('click', function() {
  const retailerId = document.getElementById('retailer-select').value;
  const name = document.getElementById('edit-name').value;
  
  if (!retailerId) {
    alert('Please select a retailer first');
    return;
  }
  
  
    // Here you would send the delete request to your backend
    console.log(`Deleting retailer ${retailerId}`);
    alert(`Retailer ${name} deleted successfully!`);
    
    if (result.status === 'success') {
    showSuccessMessage('retailerListMessage', 'Retailer deleted successfully!');
    // Reset the form and UI
    document.getElementById('retailer-select').value = '';
    document.getElementById('retailer-details').classList.add('hidden');
    loadRetailers(); // Refresh the list
} else {
    showErrorMessage('retailerListMessage', result.message || 'Failed to delete retailer');
}


    // Reset the form
    document.getElementById('retailer-select').value = '';
    document.getElementById('retailer-details').classList.add('hidden');
  }
);


// Helper functions for messages
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

// Load all retailers
async function loadRetailers() {
    const adminApiKey = getAdminApiKey();
    if (!adminApiKey) {
        showErrorMessage('retailerListMessage', 'Admin session expired. Please log in again.');
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
            renderRetailersTable(result.data);
        } else {
            showErrorMessage('retailerListMessage', result.message || 'Failed to load retailers');
        }
    } catch (error) {
        console.error('Error loading retailers:', error);
        showErrorMessage('retailerListMessage', 'Failed to load retailers. Please try again.');
    }
}

// Populate retailer dropdown
function populateRetailerDropdown(retailers) {
    const retailerSelect = document.getElementById('retailer-select');
    if (!retailerSelect) return;

    // Clear existing options except the first one
    retailerSelect.innerHTML = '<option value="">-- Select a retailer --</option>';

    // Add retailers from API
    if (retailers && retailers.length > 0) {
        retailers.forEach(retailer => {
            const option = document.createElement('option');
            option.value = retailer.id;
            option.textContent = `${retailer.name} (${retailer.city})`;
            retailerSelect.appendChild(option);
        });
    }
}

// Render retailers table
function renderRetailersTable(retailers) {
    const tableBody = document.querySelector('.retailer-table tbody');
    if (!tableBody) return;

    if (!retailers || retailers.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="5">No retailers found</td></tr>';
        return;
    }

    let html = '';
    retailers.forEach(retailer => {
        const address = `${retailer.street_number} ${retailer.street_name}, ${retailer.suburb}, ${retailer.city}, ${retailer.zip_code}`;
        html += `
            <tr>
                <td>${retailer.id}</td>
                <td>${retailer.name}</td>
                <td>${retailer.email}</td>
                <td>${retailer.city}</td>
                <td>${address}</td>
            </tr>
        `;
    });

    tableBody.innerHTML = html;
}

// Add new retailer
document.getElementById('retailer-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const adminApiKey = getAdminApiKey();
    if (!adminApiKey) {
        showErrorMessage('retailerFormMessage', 'Admin session expired. Please log in again.');
        return;
    }

    const formData = {
        type: 'AddRetailer',
        apikey: adminApiKey,
        name: document.getElementById('retailer-name').value.trim(),
        email: document.getElementById('retailer-email').value.trim(),
        street_number: document.getElementById('retailer-street-number').value.trim(),
        street_name: document.getElementById('retailer-street-name').value.trim(),
        suburb: document.getElementById('retailer-suburb').value.trim(),
        city: document.getElementById('retailer-city').value.trim(),
        zip_code: document.getElementById('retailer-zipcode').value.trim()
    };

    // Validate required fields
    if (!formData.name || !formData.email) {
        showErrorMessage('retailerFormMessage', 'Name and email are required');
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
            showSuccessMessage('retailerFormMessage', 'Retailer added successfully!');
            this.reset();
            loadRetailers(); // Refresh the list
        } else {
            let errorMessage = result.message;
            if (result.data) {
                errorMessage += '<br><small>' + Object.values(result.data).join('<br>') + '</small>';
            }
            showErrorMessage('retailerFormMessage', errorMessage);
        }
    } catch (error) {
        console.error('Error adding retailer:', error);
        showErrorMessage('retailerFormMessage', 'Failed to add retailer. Please try again.');
    }
});

// Retailer selection change handler
document.getElementById('retailer-select').addEventListener('change', async function() {
    const retailerDetails = document.getElementById('retailer-details');
    const selectedId = this.value;
    
    if (!selectedId) {
        retailerDetails.classList.add('hidden');
        return;
    }

    const adminApiKey = getAdminApiKey();
    if (!adminApiKey) {
        showErrorMessage('retailerListMessage', 'Admin session expired. Please log in again.');
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
            const retailer = result.data.find(r => r.id === selectedId);
            if (retailer) {
                document.getElementById('edit-name').value = retailer.name;
                document.getElementById('edit-email').value = retailer.email;
                document.getElementById('edit-street-number').value = retailer.street_number || '';
                document.getElementById('edit-street-name').value = retailer.street_name || '';
                document.getElementById('edit-suburb').value = retailer.suburb || '';
                document.getElementById('edit-city').value = retailer.city || '';
                document.getElementById('edit-zipcode').value = retailer.zip_code || '';
                retailerDetails.classList.remove('hidden');
            }
        } else {
            showErrorMessage('retailerListMessage', result.message || 'Failed to load retailer details');
        }
    } catch (error) {
        console.error('Error loading retailer details:', error);
        showErrorMessage('retailerListMessage', 'Failed to load retailer details. Please try again.');
    }
});

// Update retailer
document.getElementById('update-retailer').addEventListener('click', async function() {
    const retailerId = document.getElementById('retailer-select').value;
    if (!retailerId) {
        showErrorMessage('retailerListMessage', 'Please select a retailer first');
        return;
    }

    const adminApiKey = getAdminApiKey();
    if (!adminApiKey) {
        showErrorMessage('retailerListMessage', 'Admin session expired. Please log in again.');
        return;
    }

    const formData = {
        type: 'EditRetailer',
        apikey: adminApiKey,
        retailer_id: retailerId,
        name: document.getElementById('edit-name').value.trim(),
        email: document.getElementById('edit-email').value.trim(),
        street_number: document.getElementById('edit-street-number').value.trim(),
        street_name: document.getElementById('edit-street-name').value.trim(),
        suburb: document.getElementById('edit-suburb').value.trim(),
        city: document.getElementById('edit-city').value.trim(),
        zip_code: document.getElementById('edit-zipcode').value.trim()
    };

    // Remove empty fields
    Object.keys(formData).forEach(key => {
        if (formData[key] === '') {
            delete formData[key];
        }
    });

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
            showSuccessMessage('retailerListMessage', 'Retailer updated successfully!');
            loadRetailers(); // Refresh the list
             resetRetailerFormAndHideDetails();
        } else {
            let errorMessage = result.message;
            if (result.data) {
                errorMessage += '<br><small>' + Object.values(result.data).join('<br>') + '</small>';
            }
            showErrorMessage('retailerListMessage', errorMessage);
        }
    } catch (error) {
        console.error('Error updating retailer:', error);
        showErrorMessage('retailerListMessage', 'Failed to update retailer. Please try again.');
    }
});

// Delete retailer
document.getElementById('delete-retailer').addEventListener('click', async function() {
    const retailerId = document.getElementById('retailer-select').value;
    const retailerName = document.getElementById('edit-name').value;
    
    if (!retailerId) {
        showErrorMessage('retailerListMessage', 'Please select a retailer first');
        return;
    }

    
    const adminApiKey = getAdminApiKey();
    if (!adminApiKey) {
        showErrorMessage('retailerListMessage', 'Admin session expired. Please log in again.');
        return;
    }

    try {
        const response = await fetch('http://localhost:8000/api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: 'deleteRetailer',
                apikey: adminApiKey,
                retailer_id: retailerId
            })
        });

        const result = await response.json();
        
        if (result.status === 'success') {
            showSuccessMessage('retailerListMessage', 'Retailer deleted successfully!');
            // Reset the form
            document.getElementById('retailer-select').value = '';
            document.getElementById('retailer-details').classList.add('hidden');
            loadRetailers(); // Refresh the list
        } else {
            showErrorMessage('retailerListMessage', result.message || 'Failed to delete retailer');
        }
    } catch (error) {
        console.error('Error deleting retailer:', error);
        showErrorMessage('retailerListMessage', 'Failed to delete retailer. Please try again.');
    }
});

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    loadRetailers();
});






