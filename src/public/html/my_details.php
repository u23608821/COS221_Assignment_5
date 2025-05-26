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
  <title>Pick 'n Price—My Details</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
  <link rel="stylesheet" href="../styles/my_details.css">
</head>


<body class="light" onload="loadUserDetails()">
  <nav class="navbar">
    <div class="container">
      <div class="nav-left">
        <a href="../html/products.html" class="logo">
          <img src="https://wheatley.cs.up.ac.za/u24634434/COS221/Images/Logo.png" alt="Logo Placeholder" />
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
    <h1 class="page-header">My Details</h1>
    <p class="page-subheader">View and edit your personal information here.</p>

    <div class="details-form">
      <form>
        <div class="form-row">
          <div class="form-group">
            <label for="firstName">First Name</label>
            <input type="text" id="firstName" placeholder="John" />
          </div>
          <div class="form-group">
            <label for="lastName">Last Name</label>
            <input type="text" id="lastName" placeholder="Doe" />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="tel" id="phone" placeholder="+27 12 345 6789" />
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" placeholder="john.doe@example.com" />
          </div>
        </div>

        <h4 class="address-heading">Address</h4>

        <div class="address-row">
          <div class="form-group small">
            <label for="streetNumber">Street No.</label>
            <input type="text" id="streetNumber" placeholder="123" />
          </div>
          <div class="form-group large">
            <label for="streetName">Street Name</label>
            <input type="text" id="streetName" placeholder="Main Street" />
          </div>
        </div>

        <div class="address-row">
          <div class="form-group">
            <label for="suburb">Suburb</label>
            <input type="text" id="suburb" placeholder="Sunnyville" />
          </div>
          <div class="form-group">
            <label for="city">City</label>
            <input type="text" id="city" placeholder="Johannesburg" />
          </div>
        </div>

        <div class="address-row">
          <div class="form-group small">
            <label for="postalCode">Postal Code</label>
            <input type="text" id="postalCode" placeholder="2000" />
          </div>
        </div>

        <button type="submit" class="save-btn">Save</button>
      </form>
    </div>
  </main>

  <footer class="footer">
    <div class="footer-container">
      <span class="footer-left"> © 2025 Pick 'n Price, The Primary Keys Group </span>
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

  <script src="../scripts/my_details.js"></script>
  <script src="../scripts/global.js"></script>
</body>

</html>