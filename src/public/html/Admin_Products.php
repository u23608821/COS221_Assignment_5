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
  <title>Pick 'n Price—Administrator Portal—Products</title>
  <link rel="icon" href="https://wheatley.cs.up.ac.za/u24634434/COS221/Images/Favicon.png" type="image/x-icon">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
  <link rel="stylesheet" href="../styles/Admin_Product.css">
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
          <li><a href="../html/Admin.php" class="active">Admin Home</a></li>
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
    <h1 class="page-header">Product Management</h1>
    <p class="page-subheader">Add, edit, and manage products in the database</p>

    <div class="admin-card">
      <div class="card-header">
        <h3>
          <span class="material-symbols-outlined">add_circle</span>
          Add New Product
        </h3>
      </div>
      <div class="card-content">
        <form id="product-form" class="product-form">
          <div class="form-row">
            <div class="form-group">
              <label for="product-name">Product Name*</label>
              <input type="text" id="product-name" name="name" required placeholder="Enter product name">
            </div>

            <div class="form-group">
              <label for="product-price">Price*</label>
              <input type="number" id="product-price" name="price" step="0.01" min="0" required placeholder="0.00">
            </div>
          </div>

          <div class="form-group">
            <label for="product-description">Description</label>
            <textarea id="product-description" name="description" rows="5" placeholder="Enter detailed product description"></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="product-retailer">Retailer*</label>
              <select id="product-retailer" name="retailer_id" required>
                <option value="">Select a retailer</option>
                <!-- Retailers will be loaded dynamically -->
              </select>
            </div>

            <div class="form-group">
              <label for="product-category">Category</label>
              <input type="text" id="product-category" name="category" placeholder="Enter product category">
            </div>
          </div>

          <div class="form-group">
            <label for="product-image">Image URL</label>
            <input type="url" id="product-image" name="image_url" placeholder="https://example.com/image.jpg">
          </div>

          <div class="form-actions">
            <button type="submit" class="btn-primary">
              <span class="material-symbols-outlined">save</span>
              Save Product
            </button>
            <button type="reset" class="btn-danger">
              <span class="material-symbols-outlined">cancel</span>
              Clear Form
            </button>
          </div>
          <div id="productFormMessage" class="result-message"></div>
        </form>
      </div>
    </div>

    <div class="admin-card">
      <div class="card-header">
        <h3>
          <span class="material-symbols-outlined">list</span>
          Product List
        </h3>
      </div>
      <div class="card-content">
        <div class="table-responsive">
          <div id="productListMessage" class="result-message"></div>
          <table class="product-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Description</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="product-list">
              <!-- Product data will be loaded here via JavaScript -->
              <tr>
                <td colspan="4" class="loading">Loading products...</td>
              </tr>
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


  <script src="../scripts/Admin_Products.js"></script>
</body>

</html>