<?php
// Determine the correct path to the .env file
$envPath = dirname(dirname(dirname(dirname(__FILE__)))); // Go up 4 levels to reach the project root
$envFile = $envPath . '/.env';

// Simple function to read .env file
function readEnvFile($path)
{
  if (!file_exists($path)) {
    // echo "ENV file not found at: $path<br>";
    return false;
  }

  // echo "ENV file found at: $path<br>";
  $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  $env = [];

  foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0) continue; // Skip comments
    if (empty(trim($line))) continue;             // Skip empty lines

    list($name, $value) = explode('=', $line, 2);
    $name = trim($name);
    $value = trim($value);

    $env[$name] = $value;
    // Optionally set as environment variable
    putenv("$name=$value");
  }

  return $env;
}

// Read environment variables from .env file
$env = readEnvFile($envFile);

// Get credentials
$username = $env ? $env['WHEATLEY_USERNAME'] : getenv("WHEATLEY_USERNAME");
$password = $env ? $env['WHEATLEY_PASSWORD'] : getenv("WHEATLEY_PASSWORD");



// For debugging - comment these out in production
// echo "Username from env: " . ($username ?: 'NOT FOUND') . "<br>";
// echo "Password from env: " . ($password ? '********' : 'NOT FOUND') . "<br>";
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pick 'n Price—All Products</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
  <link rel="stylesheet" href="../styles/products.css">

</head>

<body class="light">
  <nav class="navbar">
    <div class="container">
      <div class="nav-left">
        <a href="../html/products.html" class="logo">
          <img src="https://wheatley.cs.up.ac.za/u24634434/COS221/Images/Logo.png" alt="Pick 'n Price Logo" />
        </a>
        <span class="menu-toggle" id="menuToggle">☰</span>
        <ul class="nav-links" id="navLinks">
          <li><a href="../html/products.html">All Products</a></li>
          <li><a href="../html/highest_rated.html">Top-Rated Products</a></li>
        </ul>
      </div>
      <div class="nav-actions">
        <div class="dropdown">
          <button class="btn-user dropdown-toggle" id="accountBtn" aria-haspopup="true" aria-expanded="false">
            <span class="material-symbols-outlined user-icon">account_circle</span>
            <span class="user-text">User</span>
            <span class="material-symbols-outlined arrow-icon">arrow_drop_down</span>
          </button>
          <div class="dropdown-menu" id="accountMenu" aria-label="Account options">
            <a href="../html/my_details.php"><span>My Details</span></a>
            <a href="../html/my_reviews.html"><span>My Reviews</span></a>
            <a href="../html/my_watchlist.html"><span>My Watchlist</span></a>
            <div class="dropdown-divider"></div>
            <a href="../html/login.php" class="signout"><span>Sign Out</span></a>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <main>
    <h1 class="page-header">All Products</h1>
    <p class="page-subheader">View all products and compare their prices across different retailers.</p>

    <div class="search-container">
      <div class="search-box">
        <span class="material-symbols-outlined search-icon">search</span>
        <input type="text" placeholder="Search for products..." class="search-input">
        <button class="search-btn">Search</button>
      </div>
      <div class="filter-options">
        <select class="filter-select">
          <option value="">All Categories</option>
          <option value="electronics">Electronics</option>
          <option value="home">Home & Kitchen</option>
          <option value="fashion">Fashion</option>
        </select>
        <select class="filter-select">
          <option value="">Sort By</option>
          <option value="price-low">Price: Low to High</option>
          <option value="price-high">Price: High to Low</option>
          <option value="rating">Rating: Best to Worst </option>
          <option value="rating">Rating: Worst to Best </option>
          <option value="price-high">Name: A–Z</option>
          <option value="price-high">Name: Z–A</option>
        </select>
        <select class="filter-select">
          <option value="">Price Range</option>
          <option value="range">R0,00–R99,99</option>
          <option value="range">R100,00–R999,99</option>
          <option value="range">R1000,00–R9999,99</option>
          <option value="range">R10000,00–R99999,99</option>
        </select>
      </div>
    </div>

    <div class="products-container" id="products-list">
      <!-- Product 1 -->
      <div class="product-box">
        <div class="product-image">
          <img src="/src/private/resources/dummyHeadphones.avif" alt="Wireless Headphones">
        </div>
        <div class="product-content">
          <div class="product-info">
            <h3 class="product-title">Sony WH-1000XM4 Headphones</h3>
            <div class="product-rating">
              <span class="material-symbols-outlined">star</span>
              <span class="material-symbols-outlined">star</span>
              <span class="material-symbols-outlined">star</span>
              <span class="material-symbols-outlined">star</span>
              <span class="material-symbols-outlined">star_half</span>
              <span class="rating-text">4.7 (1,243)</span>
            </div>
          </div>
          <div class="best-price">
            <span class="best-price-label">Best Price</span>
            <span class="best-price-value">R348.00</span>
            <span class="retailer-label">From Retailer Name</span>
          </div>
          <button class="compare-btn">Compare Prices</button>
        </div>
      </div>

      <!-- Product 2 -->
      <div class="product-box">
        <div class="product-image">
          <img src="/src/private/resources/dummyAppleWatch.png" alt="Smart Watch">
        </div>
        <div class="product-content">
          <div class="product-info">
            <h3 class="product-title">Apple Watch Series 8</h3>
            <div class="product-rating">
              <span class="material-symbols-outlined">star</span>
              <span class="material-symbols-outlined">star</span>
              <span class="material-symbols-outlined">star</span>
              <span class="material-symbols-outlined">star</span>
              <span class="material-symbols-outlined">star</span>
              <span class="rating-text">4.9 (892)</span>
            </div>
          </div>
          <div class="best-price">
            <span class="best-price-label">Best Price</span>
            <span class="best-price-value">R379.99</span>
            <span class="retailer-label">From Retailer Name</span>
          </div>
          <button class="compare-btn">Compare Prices</button>
        </div>
      </div>

      <!-- Product 3 -->
      <div class="product-box">
        <div class="product-image">
          <img src="/src/private/resources/dummyCoffee.jpg" alt="Coffee Maker">
        </div>
        <div class="product-content">
          <div class="product-info">
            <h3 class="product-title">Ninja CE251 Brewer</h3>
            <div class="product-rating">
              <span class="material-symbols-outlined">star</span>
              <span class="material-symbols-outlined">star</span>
              <span class="material-symbols-outlined">star</span>
              <span class="material-symbols-outlined">star</span>
              <span class="material-symbols-outlined">star_half</span>
              <span class="rating-text">4.5 (3,421)</span>
            </div>
          </div>
          <div class="best-price">
            <span class="best-price-label">Best Price</span>
            <span class="best-price-value">R89.00</span>
            <span class="retailer-label">From Retailer Name</span>
          </div>
          <button class="compare-btn">Compare Prices</button>
        </div>
      </div>

      <!-- Product 4 -->
      <div class="product-box">
        <div class="product-image">
          <img src="/src/private/resources/dummyLaptop.avif" alt="Laptop">
        </div>
        <div class="product-content">
          <div class="product-info">
            <h3 class="product-title">Dell XPS 13 Touchscreen Laptop</h3>
            <div class="product-rating">
              <span class="material-symbols-outlined">star</span>
              <span class="material-symbols-outlined">star</span>
              <span class="material-symbols-outlined">star</span>
              <span class="material-symbols-outlined">star</span>
              <span class="material-symbols-outlined">star_half</span>
              <span class="rating-text">4.6 (2,158)</span>
            </div>
          </div>
          <div class="best-price">
            <span class="best-price-label">Best Price</span>
            <span class="best-price-value">R1,099.00</span>
            <span class="retailer-label">From Retailer Name</span>
          </div>
          <button class="compare-btn">Compare Prices</button>
        </div>
      </div>
    </div>
  </main>

  <footer class="footer">
    <div class="footer-container">
      <span class="footer-left">© 2025 Pick 'n Price, The Primary Keys Group</span>
      <button class="btn" id="themeToggle" title="Toggle theme">
        <span class="material-symbols-outlined" id="themeIcon">dark_mode</span>
      </button>
    </div>
  </footer>

</body>

 <script>
    // Set global variables for authentication
    var WHEATLEY_USERNAME = "<?php echo $username; ?>";
    var WHEATLEY_PASSWORD = "<?php echo $password; ?>";
    console.log('Credentials loaded from PHP: ',
      WHEATLEY_USERNAME ? 'Username found' : 'Username missing',
      WHEATLEY_PASSWORD ? 'Password found' : 'Password missing');
  </script>

<script src="../scripts/products.js"></script>
<script src="../scripts/global.js"></script>



</html>