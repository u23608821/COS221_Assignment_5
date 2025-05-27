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
  <title>Pick 'n Price—Administrator Portal—Retailers</title>
  <link rel="icon" href="https://wheatley.cs.up.ac.za/u24634434/COS221/Images/Favicon.png" type="image/x-icon">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
  <link rel="stylesheet" href="../styles/Admin_Retailers.css">
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
          <li><a href="../html/Admin.php">Admin Home</a></li>
          <li><a href="../html/Admin_Products.php">Products</a></li>
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
    <h1 class="page-header">Retailer Management</h1>
    <p class="page-subheader">Manage retail partners and their locations</p>

    <div class="admin-grid">
      <!-- Add Retailer Card -->
      <div class="admin-card retailer-card">
        <div class="card-header">
          <h3>
            <span class="material-symbols-outlined">add_business</span>
            Add New Retailer
          </h3>
        </div>
        <div class="card-content">
          <form id="retailer-form" class="retailer-form">
            <div class="form-group">
              <label for="retailer-name">Retailer Name*</label>
              <input type="text" id="retailer-name" name="name" required placeholder="e.g. Pick n Pay">
            </div>

            <div class="form-group">
              <label for="retailer-email">Email*</label>
              <input type="email" id="retailer-email" name="email" required placeholder="contact@retailer.com">
            </div>

            <div class="address-grid">
              <div class="form-group">
                <label for="retailer-street-number">Street Number*</label>
                <input type="text" id="retailer-street-number" name="street_number" required placeholder="123">
              </div>

              <div class="form-group">
                <label for="retailer-street-name">Street Name*</label>
                <input type="text" id="retailer-street-name" name="street_name" required placeholder="Main Road">
              </div>

              <div class="form-group">
                <label for="retailer-suburb">Suburb*</label>
                <input type="text" id="retailer-suburb" name="suburb" required placeholder="Sandton">
              </div>

              <div class="form-group">
                <label for="retailer-city">City*</label>
                <input type="text" id="retailer-city" name="city" required placeholder="Johannesburg">
              </div>

              <div class="form-group">
                <label for="retailer-zipcode">Zip Code*</label>
                <input type="text" id="retailer-zipcode" name="zipcode" required placeholder="2196">
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn-primary">
                <span class="material-symbols-outlined">save</span>
                Add Retailer
              </button>
              <button type="reset" class="btn-danger">
                <span class="material-symbols-outlined">clear</span>
                Clear
              </button>
            </div>
            <div id="retailerFormMessage" class="result-message"></div>
          </form>
        </div>
      </div>

      <!-- Retailer Actions Card -->
      <div class="admin-card retailer-actions">
        <div class="card-header">
          <h3>
            <span class="material-symbols-outlined">edit</span>
            Manage Retailers
          </h3>
        </div>
        <div class="card-content">
          <div class="form-group">
            <label for="retailer-select">Select Retailer</label>
            <select id="retailer-select" class="retailer-select">

            </select>
          </div>

          <div id="retailer-details" class="retailer-details hidden">
            <div class="form-group">
              <label for="edit-name">Retailer Name</label>
              <input type="text" id="edit-name" name="edit-name">
            </div>

            <div class="form-group">
              <label for="edit-email">Email</label>
              <input type="email" id="edit-email" name="edit-email">
            </div>

            <div class="address-grid">
              <div class="form-group">
                <label for="edit-street-number">Street Number</label>
                <input type="text" id="edit-street-number" name="edit-street-number">
              </div>

              <div class="form-group">
                <label for="edit-street-name">Street Name</label>
                <input type="text" id="edit-street-name" name="edit-street-name">
              </div>

              <div class="form-group">
                <label for="edit-suburb">Suburb</label>
                <input type="text" id="edit-suburb" name="edit-suburb">
              </div>

              <div class="form-group">
                <label for="edit-city">City</label>
                <input type="text" id="edit-city" name="edit-city">
              </div>

              <div class="form-group">
                <label for="edit-zipcode">Zip Code</label>
                <input type="text" id="edit-zipcode" name="edit-zipcode">
              </div>
            </div>

            <div class="form-actions">
              <button id="update-retailer" class="btn-primary">
                <span class="material-symbols-outlined">update</span>
                Update
              </button>
              <button id="delete-retailer" class="btn-danger">
                <span class="material-symbols-outlined">delete</span>
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    </div>

    <!-- Retailers List Card -->
    <div class="admin-card">
      <div class="card-header">
        <h3>
          <span class="material-symbols-outlined">storefront</span>
          All Retailers
        </h3>
      </div>
      <div id="retailerListMessage" class="result-message"></div>
      <div class="card-content">
        <div class="table-responsive">
          <table class="retailer-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Location</th>
                <th>Address</th>
              </tr>
            </thead>
            <tbody>

            </tbody>
          </table>
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

  <script src="../scripts/Admin_Retailers.js"></script>
</body>

</html>