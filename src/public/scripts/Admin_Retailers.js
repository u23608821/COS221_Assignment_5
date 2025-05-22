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
  
  // Here you would typically send this data to your backend
  console.log('New retailer submitted:', formData);
  
  // Simulate successful submission
  alert('Retailer added successfully!');
  this.reset();
});

// Retailer Selection Handling
document.getElementById('retailer-select').addEventListener('change', function() {
  const retailerDetails = document.getElementById('retailer-details');
  const selectedValue = this.value;
  
  if (selectedValue) {
    retailerDetails.classList.remove('hidden');
    
    // In a real app, you would fetch retailer details from your backend
    // This is just a simulation
    const fakeRetailerData = {
      '1': { name: 'Pick n Pay', email: 'contact@pnp.co.za' },
      '2': { name: 'Woolworths', email: 'info@woolworths.co.za' },
      '3': { name: 'Checkers', email: 'support@checkers.co.za' }
    };
    
    document.getElementById('edit-name').value = fakeRetailerData[selectedValue].name;
    document.getElementById('edit-email').value = fakeRetailerData[selectedValue].email;
  } else {
    retailerDetails.classList.add('hidden');
  }
});

// Update Retailer Button
document.getElementById('update-retailer').addEventListener('click', function() {
  const retailerId = document.getElementById('retailer-select').value;
  const name = document.getElementById('edit-name').value;
  const email = document.getElementById('edit-email').value;
  
  if (!retailerId) {
    alert('Please select a retailer first');
    return;
  }
  
  // Here you would send the update to your backend
  console.log(`Updating retailer ${retailerId}:`, { name, email });
  alert(`Retailer ${name} updated successfully!`);
});

// Delete Retailer Button
document.getElementById('delete-retailer').addEventListener('click', function() {
  const retailerId = document.getElementById('retailer-select').value;
  const name = document.getElementById('edit-name').value;
  
  if (!retailerId) {
    alert('Please select a retailer first');
    return;
  }
  
  if (confirm(`Are you sure you want to delete ${name}? This action cannot be undone.`)) {
    // Here you would send the delete request to your backend
    console.log(`Deleting retailer ${retailerId}`);
    alert(`Retailer ${name} deleted successfully!`);
    
    // Reset the form
    document.getElementById('retailer-select').value = '';
    document.getElementById('retailer-details').classList.add('hidden');
  }
});
