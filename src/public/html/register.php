<?php
// Determine the correct path to the .env file
$envPath = dirname(dirname(dirname(dirname(__FILE__)))); // Go up 4 levels to reach the project root
$envFile = $envPath . '/.env';

// Simple function to read .env file
function readEnvFile($path)
{
  if (!file_exists($path)) {
    echo "ENV file not found at: $path<br>";
    return false;
  }

  echo "ENV file found at: $path<br>";
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
// $username = $env ? $env['WHEATLEY_USERNAME'] : getenv("WHEATLEY_USERNAME");
// $password = $env ? $env['WHEATLEY_PASSWORD'] : getenv("WHEATLEY_PASSWORD");



// For debugging
echo "Username from env: " . ($username ?: 'NOT FOUND') . "<br>";
echo "Password from env: " . ($password ? '********' : 'NOT FOUND') . "<br>";
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Create an account</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
  <link rel="stylesheet" href="../styles/register.css">


</head>

<body>
  <main>
    <div class="login-box">
      <h1>Register</h1>
      <p class="subtitle">Please complete this form to create an account.</p>

      <form>
        <div class="form-group">
          <label for="name">Full Name</label>
          <!-- We will need to split this into first and last name (DB has a first and last name field) -->
          <input type="text" id="name" placeholder="Enter your full name" value="Default User" required />
        </div>
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="text" id="email" placeholder="Enter your email address" value="default@user.co.za" required />
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" placeholder="Enter a password" value="Def@ult1" required />
        </div>

        <div class="form-group">
          <label for="accountType">Account Type</label>
          <div class="select-wrapper">
            <select id="accountType">
              <option value="" disabled selected hidden>Select an account type</option>
              <option value="customer">Customer</option>
              <option value="courier">Courier</option> <!-- We need to decide what user types we have -->
            </select>
            <span class="arrow">&#9662;</span>
          </div>
        </div>
        <button type="submit">Register</button>
      </form>
    </div>
  </main>

  <footer class="footer">
    <div class="footer-container">
      <span class="footer-left">© 2025 The Primary Keys Group</span>
    </div>
  </footer>

  <script>
    const WHEATLEY_USERNAME = "<?php echo $username; ?>";
    const WHEATLEY_PASSWORD = "<?php echo $password; ?>";
  </script>

  <script src="../scripts/register.js"></script>




</body>

</html>