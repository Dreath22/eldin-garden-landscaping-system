<?php
require_once __DIR__ . '/config/auth_middleware.php';

// Require admin access - will redirect if not admin
requireAdmin();

// Initialize standard session
$sessionData = initStandardSession();
$user = $sessionData['user'];
$isLoggedIn = $sessionData['isLoggedIn'];

$adminName = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - GreenScape</title>
  <link rel="stylesheet" href="client-style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div class="admin-page">
    <aside class="admin-sidebar">
      <div class="admin-sidebar-header">
        <a href="index.php" class="logo"><div class="logo-icon"><i class="fas fa-leaf"></i></div>GreenScape</a>
      </div>
      <nav class="admin-nav">
        <div class="admin-nav-section">
          <p class="admin-nav-title">Main</p>
          <a href="#" class="admin-nav-item active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
          <a href="logout.php" class="admin-nav-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
      </nav>
    </aside>

    <main class="admin-main">
      <header class="admin-header">
        <div class="admin-search"><i class="fas fa-search"></i><input type="text" placeholder="Search..."></div>
        <div class="admin-user">
          <div style="text-align: right;">
            <p style="font-weight: 600;"><?php echo htmlspecialchars($adminName); ?></p>
            <p style="font-size: 0.75rem; color: var(--text-gray);">System Administrator</p>
          </div>
        </div>
      </header>

      <div class="admin-content">
        <div class="admin-page-title">
          <h2>Admin Controls</h2>
          <p>Welcome, master of the garden. You have full access.</p>
        </div>
        <!-- Dashboard content here -->
      </div>
    </main>
  </div>
</body>
</html>