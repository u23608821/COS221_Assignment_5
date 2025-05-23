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
	<title>Login</title>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
	<link rel="stylesheet" href="../styles/login.css" />

	<!-- RECAPTCHA API --> 
	<script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>

</head>

<body>
	<main>
		<div class="login-box">
			<h1>Login</h1>
			<p class="subtitle">To access this webpage, please enter your username and password.</p>

			<form onsubmit="event.preventDefault(); clickLogin();">
				<div class="form-group">
					<label for="username">Email</label>
					<input type="text" id="username" placeholder="Enter email address" required />
				</div>
				<div class="form-group">
					<label for="password">Password</label>
					<input type="password" id="password" placeholder="Enter password" required />
				</div>	

				<!-- RECAPTCHA -->
				<div class="form-group">
					<div class="g-recaptcha" data-sitekey="6LeqnUYrAAAAAO__H-uxNMt8ro4K3OKBT_oF_hDl"></div>
				</div>


				<button type="submit">Login</button>
				<div class="create-account">
					<a href="../html/register.php">Create an account</a>
				</div>
			</form>

		</div>
	</main>

	<footer class="footer">
		<div class="footer-container">
			<span class="footer-left">© 2025 Pick 'n Price, The Primary Keys Group</span>
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

	<script src="../scripts/login.js"></script>


</body>

</html>