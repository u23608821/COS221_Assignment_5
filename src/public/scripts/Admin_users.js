
const API_BASE_URL = "https://wheatley.cs.up.ac.za/u24634434/COS221/api.php"; // API base URL
const headers = new Headers();
headers.append("Authorization", "Basic " + btoa(WHEATLEY_USERNAME + ":" + WHEATLEY_PASSWORD));
headers.append("Content-Type", "application/json");

// Theme management functions
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

// Cookie management functions
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

// Get admin API key from storage
function getAdminApiKey() {
  return localStorage.getItem('apiKey');
}

// Event listeners for UI elements
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

// Initialize the page
window.addEventListener("load", function () {
  applySavedTheme();
  fetchAllUsers();
});

// Helper functions for showing messages
function showAlert(type, message) {
  const alertDiv = document.createElement('div');
  alertDiv.className = `alert alert-${type}`;
  alertDiv.textContent = message;

  document.body.appendChild(alertDiv);

  setTimeout(() => {
    alertDiv.remove();
  }, 5000);
}

// Add CSS for alerts
const style = document.createElement('style');
style.textContent = `
  .alert {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 5px;
    color: white;
    font-weight: 600;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    animation: slideIn 0.3s ease-out;
  }
  
  .alert-success {
    background-color: #4CAF50;
  }
  
  .alert-error {
    background-color: #F44336;
  }
  
  @keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
  }
`;
document.head.appendChild(style);

// User Management Functions

// Fetch all users from API
async function fetchAllUsers() {
  const adminApiKey = getAdminApiKey();
  if (!adminApiKey) {
    showAlert("error", "Admin session expired. Please log in again.");
    return;
  }

  const userTableBody = document.querySelector('#user-table tbody');
  userTableBody.innerHTML = '<tr><td colspan="10" class="loading">Loading users...</td></tr>';

  try {
    const response = await fetch(API_BASE_URL, {
      method: 'POST',
      headers: headers,

      body: JSON.stringify({
        type: "getAllUsers",
        apikey: adminApiKey
      })
    });

    const result = await response.json();

    if (result.status === 'success') {
      renderUsersTable(result.data);
    } else {
      userTableBody.innerHTML = '<tr><td colspan="10" class="error">Error loading users: ' + result.message + '</td></tr>';
      showAlert("error", "Failed to load users: " + result.message);
    }
  } catch (error) {
    console.error('Error loading users:', error);
    userTableBody.innerHTML = '<tr><td colspan="10" class="error">Failed to load users. Please try again.</td></tr>';
    showAlert("error", "Network error: " + error.message);
  }
}

// Render users in the table with null values handled
function renderUsersTable(users) {
  const userTableBody = document.querySelector('#user-table tbody');
  userTableBody.innerHTML = '';

  if (!users || users.length === 0) {
    userTableBody.innerHTML = '<tr><td colspan="10">No users found</td></tr>';
    return;
  }

  users.forEach(user => {
    const row = document.createElement('tr');
    row.innerHTML = `
      <td>${user.id}</td>
      <td>${user.name || 'N/A'}</td>
      <td>${user.surname || 'N/A'}</td>
      <td>${user.email || 'N/A'}</td>
      <td>${user.phone_number || 'N/A'}</td>
      <td><span class="role-badge ${user.user_type.toLowerCase()}">${user.user_type || 'N/A'}</span></td>
      <td>${user.city || 'N/A'}</td>
      <td>${user.salary ? 'R' + user.salary : 'N/A'}</td>
      <td>${user.position || 'N/A'}</td>
      <td class="actions">
        <button class="btn-sm btn-primary" onclick="populateModifyForm(${user.id})">Edit</button>
        <button class="btn-sm btn-danger" onclick="confirmDeleteUser(${user.id})">Delete</button>
      </td>
    `;
    userTableBody.appendChild(row);
  });
}

// Add Staff Member Form Submission
document.getElementById('user-form').addEventListener('submit', async function (e) {
  e.preventDefault();

  const adminApiKey = getAdminApiKey();
  if (!adminApiKey) {
    showAlert("error", "Admin session expired. Please log in again.");
    return;
  }

  const phoneNumber = document.getElementById('user-phone').value.trim().replace(/\D/g, '');

  const formData = {
    type: "AddNewStaff",
    apikey: adminApiKey,
    name: document.getElementById('user-name').value.trim(),
    surname: document.getElementById('user-surname').value.trim(),
    email: document.getElementById('user-email').value.trim(),
    phone_number: phoneNumber,
    password: document.getElementById('user-password').value,
    position: document.getElementById('user-position').value,
    salary: document.getElementById('user-salary').value
  };

  // Validate required fields
  if (!formData.name || !formData.surname || !formData.email || !formData.phone_number ||
    !formData.password || !formData.position || !formData.salary) {
    showAlert("error", "Please fill all required fields");
    return;
  }

  // Validate phone number (exactly 10 digits)
  if (formData.phone_number.length !== 10) {
    showAlert("error", "Phone number must be exactly 10 digits");
    return;
  }

  try {
    const response = await fetch(API_BASE_URL, {
      method: 'POST',
      headers: headers,

      body: JSON.stringify(formData)
    });

    const result = await response.json();

    if (result.status === 'success') {
      showAlert("success", "Staff member added successfully!");
      this.reset();
      fetchAllUsers(); // Refresh the users list
    } else {
      showAlert("error", "Failed to add staff: " + (result.message || "Unknown error"));
    }
  } catch (error) {
    console.error('Error adding staff:', error);
    showAlert("error", "Network error: " + error.message);
  }
});

// Populate modify form with user data
function populateModifyForm(userId) {
  // Switch to modify tab
  document.querySelector('.tab-btn[data-tab="modify"]').click();

  // Set the user ID
  document.getElementById('modify-user-id').value = userId;

  // Focus on the field to update
  document.getElementById('modify-field').focus();
}

// Update User Functionality
document.getElementById('update-user').addEventListener('click', async function () {
  const userId = document.getElementById('modify-user-id').value.trim();
  const field = document.getElementById('modify-field').value;
  const value = document.getElementById('modify-value').value.trim();

  if (!userId || !field || !value) {
    showAlert("error", "Please fill all required fields");
    return;
  }

  const adminApiKey = getAdminApiKey();
  if (!adminApiKey) {
    showAlert("error", "Admin session expired. Please log in again.");
    return;
  }

  const requestData = {
    type: "editUser",
    apikey: adminApiKey,
    id: parseInt(userId)
  };

  // Map the form field to the API field names
  switch (field) {
    case "name":
      requestData.name = value;
      break;
    case "surname":
      requestData.surname = value;
      break;
    case "email":
      requestData.email = value;
      break;
    case "phone_number":
      requestData.phone_number = value.replace(/\D/g, '');
      break;
    case "password":
      requestData.password = value;
      break;
    case "position":
      requestData.position = value;
      break;
    case "salary":
      requestData.salary = value;
      break;
  }

  try {
    const response = await fetch(API_BASE_URL, {
      method: 'POST',
      headers: headers,

      body: JSON.stringify(requestData)
    });

    const result = await response.json();

    if (result.status === 'success') {
      showAlert("success", `User ${userId} updated successfully!`);
      document.getElementById('modify-user-id').value = '';
      document.getElementById('modify-field').value = '';
      document.getElementById('modify-value').value = '';
      fetchAllUsers(); // Refresh the users list
    } else {
      showAlert("error", "Failed to update user: " + (result.message || "Unknown error"));
    }
  } catch (error) {
    console.error('Error updating user:', error);
    showAlert("error", "Network error: " + error.message);
  }
});

// Confirm and delete user
function confirmDeleteUser(userId) {
  if (confirm(`Are you sure you want to delete user ${userId}? This action cannot be undone.`)) {
    deleteUser(userId);
  }
}

// Delete User Functionality
async function deleteUser(userId) {
  const adminApiKey = getAdminApiKey();
  if (!adminApiKey) {
    showAlert("error", "Admin session expired. Please log in again.");
    return;
  }

  try {
    const response = await fetch(API_BASE_URL, {
      method: 'POST',
      headers: headers,

      body: JSON.stringify({
        type: "deleteUser",
        apikey: adminApiKey,
        user_id: parseInt(userId)
      })
    });

    const result = await response.json();

    if (result.status === 'success') {
      showAlert("success", `User ${userId} deleted successfully!`);
      fetchAllUsers(); // Refresh the users list
    } else {
      showAlert("error", "Failed to delete user: " + (result.message || "Unknown error"));
    }
  } catch (error) {
    console.error('Error deleting user:', error);
    showAlert("error", "Network error: " + error.message);
  }
}

// Tab Switching Functionality
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', function () {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));

    this.classList.add('active');
    const tabId = this.getAttribute('data-tab') + '-tab';
    document.getElementById(tabId).classList.add('active');
  });
});

// Add this to your Admin_users.js file, right after the tab switching functionality
document.getElementById('confirm-delete').addEventListener('click', function () {
  const userId = document.getElementById('delete-user-id').value.trim();

  if (!userId) {
    showAlert("error", "Please enter a user ID");
    return;
  }

  confirmDeleteUser(userId);
});