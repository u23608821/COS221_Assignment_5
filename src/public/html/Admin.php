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
  <title>Pick 'n Price—Administrator Portal</title>
  <link rel="icon" href="https://wheatley.cs.up.ac.za/u24634434/COS221/Images/Favicon.png" type="image/x-icon">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
  <link rel="stylesheet" href="../styles/Admin.css">
</head>

<body class="light">
  <nav class="navbar">
    <div class="container">
      <div class="nav-left">
        <a href="#" class="logo">
          <img src="https://wheatley.cs.up.ac.za/u24634434/COS221/Images/Logo.png" alt="Logo" />
        </a>
        <span class="menu-toggle" id="menuToggle">☰</span>
        <ul class="nav-links" id="navLinks">
          <li><a href="../html/Admin_Products.php">Products</a></li>
          <li><a href="../html/Admin_Retailers.php">Retailers</a></li>
          <li><a href="../html/Admin_Users.php">Users</a></li>
        </ul>
      </div>
      <div class="nav-actions">
        <div class="dropdown">
          <button class="btn-user dropdown-toggle" id="accountBtn" aria-haspopup="true" aria-expanded="false">
            <span class="material-symbols-outlined user-icon">account_circle</span>
            <span class="user-text">Administrator</span>
            <span class="material-symbols-outlined arrow-icon">arrow_drop_down</span>
          </button>
          <div class="dropdown-menu" id="accountMenu" aria-label="Account options">
            <a href="../html/login.php" class="signout"><span>Sign Out</span></a>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <main>
    <!-- Quick Add User -->
    <div class="admin-card">
      <div class="card-header">
        <span class="material-symbols-outlined">group_add</span>
        <h3>Quick Add User</h3>
      </div>
      <div class="card-content">
        <div class="form-group">
          <label>Name</label>
          <input type="text" id="userName" placeholder="First name" required>
          <small class="form-hint">Only letters, max 50 chars</small>
        </div>
        <div class="form-group">
          <label>Surname</label>
          <input type="text" id="userSurname" placeholder="Last name" required>
          <small class="form-hint">Only letters, max 50 chars</small>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" id="userEmail" placeholder="example@email.com" required>
          <small class="form-hint">Valid email format</small>
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" id="userpassword" placeholder="Password" required>
          <small class="form-hint">Min 8 chars, upper/lower/number/special</small>
        </div>
        <div class="form-group">
          <label>User Type</label>
          <select id="userType">
            <option value="customer">Customer</option>
            <option value="staff">Admin</option>
          </select>
        </div>
        <button class="btn-primary" onclick="addUser()">
          <span class="material-symbols-outlined">person_add</span> Add User
        </button>
        <div id="userAddResult" class="result-message"></div>
      </div>
    </div>

    <!-- quick edit a product price -->
    <div class="admin-card">
      <div class="card-header">
        <span class="material-symbols-outlined">edit</span>
        <h3>Quick Edit Product Price</h3>
      </div>
      <div class="card-content">
        <div class="form-group">
          <label>Product ID</label>
          <input type="number" id="productIdSearch" placeholder="Enter product ID" required>
        </div>
        <div class="form-group">
          <label>Retailer ID</label>
          <input type="number" id="retailerIdSearch" placeholder="Enter retailer ID" required>
        </div>
        <div class="form-group">
          <label>New Price (R)</label>
          <input type="number" id="new-price" placeholder="Enter New Price" step="0.01" min="0" required>
        </div>
        <button class="btn-primary" onclick="editPrice()">
          <span class="material-symbols-outlined">edit</span> Update Price
        </button>
        <div id="priceEditResult" class="result-message"></div>
      </div>
    </div>

    <!-- show the recent reviews -->
    <div class="admin-card">
      <div class="card-header">
        <span class="material-symbols-outlined">reviews</span>
        <h3>Recent Reviews</h3>
        <button class="btn-refresh" onclick="loadRecentReviews()">
          <span class="material-symbols-outlined">refresh</span>
        </button>
      </div>
      <div class="card-content">
        <div class="reviews-list" id="reviewsList">
          <!-- Reviews will be loaded dynamically -->
          <div class="loading-reviews">Loading reviews...</div>
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

  <script src="../scripts/Admin.js"></script>
</body>

</html>