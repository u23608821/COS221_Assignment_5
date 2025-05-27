<?php
// Determine the correct path to the .env file
$envPath = dirname(dirname(dirname(dirname(__FILE__)))); // Go up 4 levels to reach the project root
$envFile = $envPath . '/.env';

// Read environment variables
function readEnvFile($path)
{
  if (!file_exists($path)) {
    return false;
  }

  $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  $env = [];

  foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    if (empty(trim($line))) continue;

    list($name, $value) = explode('=', $line, 2);
    $name = trim($name);
    $value = trim($value);

    $env[$name] = $value;
    putenv("$name=$value");
  }

  return $env;
}

// Read environment variables
$env = readEnvFile($envFile);
$username = $env ? $env['WHEATLEY_USERNAME'] : getenv("WHEATLEY_USERNAME");
$password = $env ? $env['WHEATLEY_PASSWORD'] : getenv("WHEATLEY_PASSWORD");
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
        <a href="../html/products.php" class="logo">
          <img src="../../private/resources/Logo.png" alt="Logo Placeholder" />
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
            <a href="../html/my_watchlist.php"><span>My Watchlist</span></a>
            <div class="dropdown-divider"></div>
            <a href="../html/login.php" class="signout"><span>Sign Out</span></a>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <main>
    <h1 class="page-header">My Reviews</h1>
    <p class="page-subheader">All products reviews you have personally published can be viewed or deleted here.</p>

    <div class="reviews-container" id="reviewsContainer">
      <!-- Reviews will be loaded dynamically by JavaScript -->
      <div class="loading-message">Loading your reviews...</div>
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
    // Pass PHP variables to JavaScript safely
    const API_URL = 'https://wheatley.cs.up.ac.za/u24634434/COS221/api.php';
    const WHEATLEY_USERNAME = '<?php echo addslashes($username); ?>';
    const WHEATLEY_PASSWORD = '<?php echo addslashes($password); ?>';
  </script>
  <script src="../scripts/global.js"></script>
  <script src="../scripts/my_reviews.js"></script>
</body>

</html>