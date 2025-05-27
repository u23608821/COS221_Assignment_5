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
  <title>Pick 'n Price—Review Visualisation Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
  <link href="../styles/review_dashboard.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
    <h1 class="page-header">Review Visualisation Dashboard</h1>
    <p class="page-subheader">Visualise and interpret reviews data about all products here.</p>

    <div class="dashboard-grid">
      <div class="dashboard-tile">
        <h2 class="tile-header">Review Score Piechart</h2>
        <div class="chart-container">
          <canvas id="pieChart"></canvas>
        </div>
      </div>

      <div class="dashboard-tile">
        <h2 class="tile-header">Review Score Bargraph</h2>
        <div class="chart-container">
          <canvas id="barChart"></canvas>
        </div>
      </div>

      <div class="dashboard-tile aggregate-tile">
        <h2 class="tile-header">Review Aggregate Data</h2>
        <div class="aggregate-content">
          <div class="aggregate-number">
            <span class="big-number">0</span>
            <span class="aggregate-label">total reviews</span>
          </div>

          <div class="aggregate-rating">
            <div class="stars">
              <span class="material-symbols-outlined">star</span>
              <span class="material-symbols-outlined">star</span>
              <span class="material-symbols-outlined">star</span>
              <span class="material-symbols-outlined">star</span>
              <span class="material-symbols-outlined">star_half</span>
            </div>
            <div class="rating-text">
              <span class="rating-value">0.0</span>
              <span class="aggregate-label">average rating</span>
            </div>
          </div>

          <div class="rating-range">
            <div class="range-item">
              <span class="range-label">Highest:</span>
              <div class="stars small">
                <span class="material-symbols-outlined">star</span>
                <span class="material-symbols-outlined">star</span>
                <span class="material-symbols-outlined">star</span>
                <span class="material-symbols-outlined">star</span>
                <span class="material-symbols-outlined">star</span>
              </div>
              <span class="rating-value">0.0</span>
            </div>

            <div class="range-item">
              <span class="range-label">Lowest:</span>
              <div class="stars small">
                <span class="material-symbols-outlined">star</span>
                <span class="material-symbols-outlined">star_half</span>
                <span class="material-symbols-outlined">grade</span>
                <span class="material-symbols-outlined">grade</span>
                <span class="material-symbols-outlined">grade</span>
              </div>
              <span class="rating-value">0.0</span>
            </div>
          </div>
        </div>
      </div>



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

  <script>
    // Set global variables for authentication
    var WHEATLEY_USERNAME = "<?php echo $username; ?>";
    var WHEATLEY_PASSWORD = "<?php echo $password; ?>";
    console.log('Credentials loaded from PHP: ',
      WHEATLEY_USERNAME ? 'Username found' : 'Username missing',
      WHEATLEY_PASSWORD ? 'Password found' : 'Password missing');
  </script>

  <script src="../scripts/global.js"></script>
  <script src="../scripts/review_dashboard.js"></script>
</body>

</html>