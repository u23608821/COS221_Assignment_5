
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

// Function to load products (simulated)
function loadProducts() {
  // This would be an API call in a real application
  setTimeout(() => {
    const productList = document.getElementById('product-list');
    productList.innerHTML = `
      <tr>
        <td>1</td>
        <td>Example Product</td>
        <td>R19.99</td>
        <td>Pick n Pay</td>
        <td>
          <button class="btn-sm">Edit</button>
          <button class="btn-sm btn-danger">Delete</button>
        </td>
      </tr>
      <tr>
        <td>2</td>
        <td>Another Product</td>
        <td>R49.99</td>
        <td>Woolworths</td>
        <td>
          <button class="btn-sm">Edit</button>
          <button class="btn-sm btn-danger">Delete</button>
        </td>
      </tr>
    `;
  }, 500);
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
  
  // Function to load products (simulated)
  function loadProducts() {
    // This would be an API call in a real application
    setTimeout(() => {
      const productList = document.getElementById('product-list');
      productList.innerHTML = `
        <tr>
          <td>1</td>
          <td>Example Product</td>
          <td>R19.99</td>
          <td>Pick n Pay</td>
          <td>
            <button class="btn-sm">Edit</button>
            <button class="btn-sm btn-danger">Delete</button>
          </td>
        </tr>
        <tr>
          <td>2</td>
          <td>Another Product</td>
          <td>R49.99</td>
          <td>Woolworths</td>
          <td>
            <button class="btn-sm">Edit</button>
            <button class="btn-sm btn-danger">Delete</button>
          </td>
        </tr>
      `;
    }, 500);
  }
  
  // Initialize the page
  document.addEventListener('DOMContentLoaded', function() {
    loadProducts();
  });