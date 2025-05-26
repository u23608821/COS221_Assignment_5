<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pick 'n Price—Review Visualisation Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
  <link href="../styles/review_dashboard.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="light">
  <nav class="navbar">
    <div class="container">
      <div class="nav-left">
        <a href="#" class="logo">
          <img src="https://URL_HERE.co.za/" alt="Logo Placeholder" />
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
          <span class="big-number">329</span>
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
            <span class="rating-value">4.2</span>
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
            <span class="rating-value">5.0</span>
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
            <span class="rating-value">1.5</span>
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

  <script src="../scripts/global.js"></script>
  <script src="../scripts/review_dashboard.js"></script>
</body>
</html>