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




// Tab Switching Functionality
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    // Remove active class from all buttons and tabs
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    
    // Add active class to clicked button and corresponding tab
    this.classList.add('active');
    const tabId = this.getAttribute('data-tab') + '-tab';
    document.getElementById(tabId).classList.add('active');
  });
});

// Add User Form Submission
document.getElementById('user-form').addEventListener('submit', function(e) {
  e.preventDefault();
  
  const formData = {
    username: document.getElementById('user-username').value,
    email: document.getElementById('user-email').value,
    password: document.getElementById('user-password').value,
    role: document.getElementById('user-role').value
  };
  
  // Here you would send this data to your backend
  console.log('New user submitted:', formData);
  alert('User created successfully!');
  this.reset();
});

// Update User Functionality
document.getElementById('update-user').addEventListener('click', function() {
  const userId = document.getElementById('modify-user-id').value;
  const field = document.getElementById('modify-field').value;
  const value = document.getElementById('modify-value').value;
  
  if (!userId || !field || !value) {
    alert('Please fill all required fields');
    return;
  }
  
  // Here you would send the update to your backend
  console.log(`Updating user ${userId}:`, { field, value });
  alert(`User ${userId} updated successfully!`);
  
  // Clear the form
  document.getElementById('modify-user-id').value = '';
  document.getElementById('modify-field').value = '';
  document.getElementById('modify-value').value = '';
});

// Delete User Functionality
document.getElementById('confirm-delete').addEventListener('click', function() {
  const userId = document.getElementById('delete-user-id').value;
  
  if (!userId) {
    alert('Please enter a user ID');
    return;
  }
  
  if (confirm(`Are you sure you want to delete user ${userId}? This action cannot be undone.`)) {
    // Here you would send the delete request to your backend
    console.log(`Deleting user ${userId}`);
    alert(`User ${userId} deleted successfully!`);
    document.getElementById('delete-user-id').value = '';
  }
});

// Add Staff Member Form Submission
document.getElementById('user-form').addEventListener('submit', function(e) {
  e.preventDefault();
  
  const formData = {
    name: document.getElementById('user-name').value,
    surname: document.getElementById('user-surname').value,
    email: document.getElementById('user-email').value,
    phone_number: document.getElementById('user-phone').value,
    password: document.getElementById('user-password').value,
    position: document.getElementById('user-position').value,
    salary: document.getElementById('user-salary').value,
    user_type: 'staff' // Automatically set as staff
  };
  
  // Here you would send this data to your backend
  console.log('New staff member submitted:', formData);
  alert('Staff member added successfully!');
  this.reset();
});

// Update User Functionality
document.getElementById('update-user').addEventListener('click', function() {
  const userId = document.getElementById('modify-user-id').value;
  const field = document.getElementById('modify-field').value;
  const value = document.getElementById('modify-value').value;
  
  if (!userId || !field || !value) {
    alert('Please fill all required fields');
    return;
  }
  
  // Here you would send the update to your backend
  console.log(`Updating user ${userId}:`, { field, value });
  alert(`User ${userId} updated successfully!`);
  
  // Clear the form
  document.getElementById('modify-user-id').value = '';
  document.getElementById('modify-field').value = '';
  document.getElementById('modify-value').value = '';
});

// Delete User Functionality
document.getElementById('confirm-delete').addEventListener('click', function() {
  const userId = document.getElementById('delete-user-id').value;
  
  if (!userId) {
    alert('Please enter a user ID');
    return;
  }
  
  if (confirm(`Are you sure you want to delete user ${userId}? This action cannot be undone.`)) {
    // Here you would send the delete request to your backend
    console.log(`Deleting user ${userId}`);
    alert(`User ${userId} deleted successfully!`);
    document.getElementById('delete-user-id').value = '';
  }
});