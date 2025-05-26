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
  <title>Pick 'n Price—My Reviews</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
  <link rel="stylesheet" href="../styles/my_reviews.css">

</head>

<body class="light">
  <nav class="navbar">
    <div class="container">
      <div class="nav-left">
        <a href="../html/products.html" class="logo">
          <img src="https://URL_HERE.co.za/" alt="Logo Placeholder" />
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
            <a href="../html/my_details.html"><span>My Details</span></a>
            <a href="../html/my_reviews.html"><span>My Reviews</span></a>
            <a href="../html/my_watchlist.html"><span>My Watchlist</span></a>
            <div class="dropdown-divider"></div>
            <a href="../html/login.html" class="signout"><span>Sign Out</span></a>
          </div>
        </div>
      </div>
    </div>
  </nav>


  <main>
    <h1 class="page-header">My Reviews</h1>
    <p class="page-subheader">All products reviews you have personally published can be viewed here. </p>

    <div class="reviews-container">
      <!-- Review 1 -->
      <div class="review-box">
        <div class="product-image">
          <span>Product Image</span>
        </div>
        <div class="review-main-content">
          <div class="review-text-content">
            <h3 class="review-title">Wireless Headphones</h3>
            <p class="review-date">Reviewed on 12 May 2025</p>
            <div class="stars">
              <span class="star filled">★</span>
              <span class="star filled">★</span>
              <span class="star filled">★</span>
              <span class="star filled">★</span>
              <span class="star">★</span>
            </div>
            <h4 class="review-heading">Review</h4>
            <div class="review-text">
              This product exceeded my expectations. The quality is outstanding and it arrived sooner than expected. I
              would definitely recommend it to others looking for a reliable solution.
            </div>
          </div>
          <div class="review-actions">
            <button class="review-btn edit-btn">Edit</button>
            <button class="review-btn delete-btn">Delete</button>
          </div>
        </div>
      </div>

      <!-- Review 2 -->
      <div class="review-box">
        <div class="product-image">
          <span>Product Image</span>
        </div>
        <div class="review-main-content">
          <div class="review-text-content">
            <h3 class="review-title">Smart Watch</h3>
            <p class="review-date">Reviewed on 10 May 2025</p>
            <div class="stars">
              <span class="star filled">★</span>
              <span class="star filled">★</span>
              <span class="star filled">★</span>
              <span class="star">★</span>
              <span class="star">★</span>
            </div>
            <h4 class="review-heading">Review</h4>
            <div class="review-text">
              Good product overall, but there were some minor issues with the packaging. The product itself works well
              and meets my basic needs, though it could be improved in some areas.
            </div>
          </div>
          <div class="review-actions">
            <button class="review-btn edit-btn">Edit</button>
            <button class="review-btn delete-btn">Delete</button>
          </div>
        </div>
      </div>

      <!-- Review 3 -->
      <div class="review-box">
        <div class="product-image">
          <span>Product Image</span>
        </div>
        <div class="review-main-content">
          <div class="review-text-content">
            <h3 class="review-title">Bluetooth Speaker</h3>
            <p class="review-date">Reviewed on 8 May 2025</p>
            <div class="stars">
              <span class="star filled">★</span>
              <span class="star filled">★</span>
              <span class="star filled">★</span>
              <span class="star filled">★</span>
              <span class="star filled">★</span>
            </div>
            <h4 class="review-heading">Review</h4>
            <div class="review-text">
              Absolutely perfect! This is exactly what I was looking for. The quality is exceptional and it has all the
              features I need. Customer service was also very helpful when I had questions.
            </div>
          </div>
          <div class="review-actions">
            <button class="review-btn edit-btn">Edit</button>
            <button class="review-btn delete-btn">Delete</button>
          </div>
        </div>
      </div>

      <!-- Review 4 -->
      <div class="review-box">
        <div class="product-image">
          <span>Product Image</span>
        </div>
        <div class="review-main-content">
          <div class="review-text-content">
            <h3 class="review-title">Fitness Tracker</h3>
            <p class="review-date">Reviewed on 5 May 2025</p>
            <div class="stars">
              <span class="star filled">★</span>
              <span class="star filled">★</span>
              <span class="star">★</span>
              <span class="star">★</span>
              <span class="star">★</span>
            </div>
            <h4 class="review-heading">Review</h4>
            <div class="review-text">
              The product is okay for the price, but I expected better quality. It serves its basic purpose but doesn't
              feel very durable. Might consider other options next time.
            </div>
          </div>
          <div class="review-actions">
            <button class="review-btn edit-btn">Edit</button>
            <button class="review-btn delete-btn">Delete</button>
          </div>
        </div>
      </div>

      <!-- Review 5 -->
      <div class="review-box">
        <div class="product-image">
          <span>Product Image</span>
        </div>
        <div class="review-main-content">
          <div class="review-text-content">
            <h3 class="review-title">Wireless Earbuds</h3>
            <p class="review-date">Reviewed on 2 May 2025</p>
            <div class="stars">
              <span class="star filled">★</span>
              <span class="star filled">★</span>
              <span class="star filled">★</span>
              <span class="star filled">★</span>
              <span class="star">★</span>
            </div>
            <h4 class="review-heading">Review</h4>
            <div class="review-text">
              Very satisfied with this purchase. It arrived quickly and was easy to set up. The product looks great and
              functions perfectly. Only minor suggestion would be to include more detailed instructions.
            </div>
          </div>
          <div class="review-actions">
            <button class="review-btn edit-btn">Edit</button>
            <button class="review-btn delete-btn">Delete</button>
          </div>
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

   <script>
    // Set global variables for authentication
    var WHEATLEY_USERNAME = "<?php echo $username; ?>";
    var WHEATLEY_PASSWORD = "<?php echo $password; ?>";
    console.log('Credentials loaded from PHP: ',
      WHEATLEY_USERNAME ? 'Username found' : 'Username missing',
      WHEATLEY_PASSWORD ? 'Password found' : 'Password missing');
  </script>

</body>
<script src="../scripts/my_reviews.js"></script>
<script src="../scripts/global.js"></script>

</html>