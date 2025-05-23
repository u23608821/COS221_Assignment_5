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
  <title>View Page</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
  <link href="../styles/view.css" rel="stylesheet">
</head>
<body class="light">
  <nav class="navbar">
    <div class="container">
      <div class="nav-left">
        <a href="#" class="logo">
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
            <a href="../html/login.php" class="signout"><span>Sign Out</span></a>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <main>
    <div class="back-button">
      <span class="material-symbols-outlined">arrow_back</span>
      <span>Back to products</span>
    </div>
    
    <div class="product-container" id="productContainer">
      <div class="product-image-container">
        <img class="product-image-large" id="productImage"  src="" alt="Product image">
      </div>
      <div class="product-info">
        <h1 class="product-title" id="productName">Product-Name</h1>
        <div class="best-price-container">
          <span class="best-price-label">Best Price</span>
          <span class="best-price-value" id="productCheapestPrice">R0.00</span>

          <span class="heading" >Description</span>
          <span class="text" id="productDescription">Product-Description. 
          </span>

          <span class="heading">Category</span>
          <span class="text">Electronics</span>


        </div>
      </div>
      <div class="retailer-scroll-box" id="retailerContainer">
      <div class="retailer-prices" id="retailerPricesContainer">
      </div>
      </div>
    </div>

    <div class="reviews-section" >
      <div class="reviews-header">
        <h2 class="reviews-title">Reviews</h2>
        <div class="star-rating">
          <span class="material-symbols-outlined">star</span>
          <span class="material-symbols-outlined">star</span>
          <span class="material-symbols-outlined">star</span>
          <span class="material-symbols-outlined">star</span>
          <span class="material-symbols-outlined">star_half</span>
          <span class="rating-value" id="productAvgReview">(0)</span>
          <span class="review-count">128 reviews</span>
        </div>
      </div>
      
      <div class="rating-summary">
        <div class="rating-overview">
          <div class="rating-number">4.6</div>
          <div class="rating-out-of">out of 5</div>
          <button class="write-review-btn" onclick="writeReview()">Write a review</button>
        </div>
        <div class="rating-bars">
          <div class="rating-bar">
            <span>5 stars</span>
            <div class="bar-container">
              <div class="bar" style="width: 70%;"></div>
            </div>
            <span>90</span>
          </div>
          <div class="rating-bar">
            <span>4 stars</span>
            <div class="bar-container">
              <div class="bar" style="width: 20%;"></div>
            </div>
            <span>26</span>
          </div>
          <div class="rating-bar">
            <span>3 stars</span>
            <div class="bar-container">
              <div class="bar" style="width: 7%;"></div>
            </div>
            <span>9</span>
          </div>
          <div class="rating-bar">
            <span>2 stars</span>
            <div class="bar-container">
              <div class="bar" style="width: 2%;"></div>
            </div>
            <span>3</span>
          </div>
          <div class="rating-bar">
            <span>1 star</span>
            <div class="bar-container">
              <div class="bar" style="width: 1%;"></div>
            </div>
            <span>  1</span>
          </div>
        </div>
      </div>
      
      <div class="user-reviews" id="userReviewsContainer">
        <div class="review">

        </div>              

      </div>
      
      <button class="view-more-reviews" onclick="viewMoreReviews()">View more reviews</button>
    </div>
  </main>
  
  <footer class="footer">
    <div class="footer-container">
      <span class="footer-left">© 2025 Pick 'n Price, The Primary Keys Group </span> 
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
  <script src="../scripts/global.js"></script>
  <script src="../scripts/view.js"></script>
</body>
</html>

