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
  <title>Pick 'n Price—Administrator Portal—Users</title>
  <link rel="icon" href="https://wheatley.cs.up.ac.za/u24634434/COS221/Images/Favicon.png" type="image/x-icon">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
  <link rel="stylesheet" href="../styles/Admin_users.css">
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
    <h1 class="page-header">User Management</h1>
    <p class="page-subheader">Add staff members and manage all users</p>

    <div class="admin-grid">
      <!-- Add Staff User Card -->
      <div class="admin-card user-card">
        <div class="card-header">
          <h3>
            <span class="material-symbols-outlined">person_add</span>
            Add New Staff Member
          </h3>
        </div>
        <div class="card-content">
          <form id="user-form" class="user-form">
            <div class="form-row">
              <div class="form-group">
                <label for="user-name">First Name*</label>
                <input type="text" id="user-name" name="name" required placeholder="First name">
              </div>

              <div class="form-group">
                <label for="user-surname">Last Name*</label>
                <input type="text" id="user-surname" name="surname" required placeholder="Last name">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="user-email">Email*</label>
                <input type="email" id="user-email" name="email" required placeholder="staff@example.com">
              </div>

              <div class="form-group">
                <label for="user-phone">Phone Number*</label>
                <input type="tel" id="user-phone" name="phone_number" required placeholder="+27 12 345 6789">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="user-password">Password*</label>
                <input type="password" id="user-password" name="password" required placeholder="Create password">
              </div>

              <div class="form-group">
                <label for="user-position">Position*</label>
                <select id="user-position" name="position" required>
                  <option value="">Select position</option>
                  <option value="Administrator">Administrator</option>
                  <option value="Database Administrator">Database Administrator</option>
                  <option value="Web Developer">Web Developer</option>
                  <option value="Content Manager">Content Manager</option>
                  <option value="Product Specialist">Product Specialist</option>
                  <option value="Customer Support">Customer Support</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="user-salary">Salary (ZAR)*</label>
                <input type="number" id="user-salary" name="salary" required placeholder="Monthly salary">
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn-primary">
                <span class="material-symbols-outlined">save</span>
                Add Staff Member
              </button>
              <button type="reset" class="btn-danger">
                <span class="material-symbols-outlined">clear</span>
                Clear
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- User Actions Card -->
      <div class="admin-card user-actions">
        <div class="card-header">
          <h3>
            <span class="material-symbols-outlined">manage_accounts</span>
            Manage Users
          </h3>
        </div>
        <div class="card-content">
          <div class="action-tabs">
            <button class="tab-btn active" data-tab="modify">Modify User</button>
            <button class="tab-btn" data-tab="delete">Delete User</button>
          </div>

          <!-- Modify User Tab -->
          <div id="modify-tab" class="tab-content active">
            <div class="form-group">
              <label for="modify-user-id">User ID*</label>
              <input type="text" id="modify-user-id" name="user_id" placeholder="Enter user ID">
            </div>

            <div class="form-group">
              <label for="modify-field">Field to Update*</label>
              <select id="modify-field" name="field">
                <option value="">Select field</option>
                <option value="name">First Name</option>
                <option value="surname">Last Name</option>
                <option value="email">Email</option>
                <option value="phone_number">Phone Number</option>
                <option value="password">Password</option>
                <option value="position">Position (Staff Only)</option>
                <option value="salary">Salary (Staff Only)</option>
              </select>
            </div>

            <div class="form-group">
              <label for="modify-value">New Value*</label>
              <input type="text" id="modify-value" name="value" placeholder="Enter new value">
            </div>

            <div class="form-actions">
              <button id="update-user" class="btn-primary">
                <span class="material-symbols-outlined">update</span>
                Update User
              </button>
            </div>
          </div>

          <!-- Delete User Tab -->
          <div id="delete-tab" class="tab-content">
            <div class="form-group">
              <label for="delete-user-id">User ID*</label>
              <input type="text" id="delete-user-id" name="user_id" placeholder="Enter user ID to delete">
            </div>

            <div class="form-actions">
              <button id="confirm-delete" class="btn-danger">
                <span class="material-symbols-outlined">delete_forever</span>
                Delete User
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Users List Card -->
    <div class="admin-card wide-card">
      <div class="card-header">
        <h3>
          <span class="material-symbols-outlined">groups</span>
          All Users
        </h3>
      </div>
      <div class="card-content">
        <div class="table-responsive">
          <table id="user-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Surname</th>
                <th>Email</th>
                <th>Phone</th>
                <th>User Type</th>
                <th>City</th>
                <th>Salary</th>
                <th>Position</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody></tbody>
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


  <script src="../scripts/Admin_users.js"></script>
</body>

</html>