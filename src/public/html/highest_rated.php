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


?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pick 'n Price—Top-Rated Products</title>
  <link rel="icon" href="https://wheatley.cs.up.ac.za/u24634434/COS221/Images/Favicon.png" type="image/x-icon">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
  <link rel="stylesheet" href="../styles/highest_rated.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>

<body class="light">
  <nav class="navbar">
    <div class="container">
      <div class="nav-left">
        <a href="../html/products.php" class="logo">
          <img src="../../private/resources/Logo.png" alt="Pick 'n Price Logo" />
        </a>
        <span class="menu-toggle" id="menuToggle">☰</span>
        <ul class="nav-links" id="navLinks">
          <li><a href="../html/products.php">All Products</a></li>
          <li><a href="../html/highest_rated.php">Top-Rated Products</a></li>
          <li><a href="../html/review_dashboard.php">Reviews Dashboard</a></li>
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
            <a href="../html/my_reviews.php"><span>My Reviews</span></a>
            <a href="/my_watchlist.php"><span>My Watchlist</span></a>
            <div class="dropdown-divider"></div>
            <a href="../html/login.php" class="signout"><span>Sign Out</span></a>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <main>
    <h1 class="page-header">Top-Rated Products</h1>
    <p class="page-subheader">View products that received the highest ratings.</p>

    <!-- Full-width Rating Banner -->
    <div class="fullwidth-rating-banner">
      All products with more than 4 stars
    </div>

    <div class="products-container" id="products-list">
      <!-- Products will be loaded here dynamically -->
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

<script src="../scripts/highest_rated.js"></script>
<script src="../scripts/global.js"></script>



</html>