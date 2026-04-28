<?php
require_once __DIR__ . '/config/auth_middleware.php';

// Require authentication - will redirect if not logged in
//requireAuth();

// Initialize standard session
$sessionData = initStandardSession();
$user = $sessionData['user'];
$isLoggedIn = true; //$sessionData['isLoggedIn'];
$message = '';
$messageType = '';

// Profile Update Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];

// Check if email is unique (excluding current user)
    $emailCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $emailCheck->execute([$email, $userId]);
    
    if ($emailCheck->fetch()) {
        $message = "This email is already in use by another account.";
        $messageType = "danger";
    } else {
        try {
            // Base query - using correct column names
            $sql = "UPDATE users SET name = ?, email = ?, phone_number = ? WHERE id = ?";
            $params = [$fullname, $email, $phone, $userId];

            // If password is provided, hash it and add to query
            if (!empty($password)) {
                $sql = "UPDATE users SET name = ?, email = ?, phone_number = ?, password = ? WHERE id = ?";
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $params = [$fullname, $email, $phone, $hashedPassword, $userId];
            }

            $updateStmt = $pdo->prepare($sql);
            $updateStmt->execute($params);
            
            // Refresh user data after update
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            $message = "Profile updated successfully!";
            $messageType = "success";
        } catch (PDOException $e) {
            $message = "Error updating profile: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

// Fetch user data
try{
  $userId = 3;
  $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
  $stmt->execute([$userId]);
  $user = $stmt->fetch();

  // Fetch user's quote requests
  // $quoteStmt = $pdo->prepare("SELECT * FROM quotes WHERE user_id = ? ORDER BY created_at DESC");
  // $quoteStmt->execute([$userId]);
  // $userQuotes = $quoteStmt->fetchAll();
}catch(Exception $e){
  echo "Error: " . $e->getMessage(); 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile - GreenScape Landscaping</title>
  <link rel="stylesheet" href="client-style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="profile-page">
  <!-- Navigation -->
  <nav class="navbar">
    <a href="index.php" class="logo">
      <div class="logo-icon"><i class="fas fa-leaf"></i></div>
      GreenScape
    </a>
    <div class="menu-toggle" onclick="toggleMenu()">
      <span></span><span></span><span></span>
    </div>
    <ul class="nav-links" id="navLinks">
      <li><a href="index.php">Home</a></li>
      <li><a href="about.php">About</a></li>
      <li><a href="services.php">Services</a></li>
      <li><a href="gallery.php">Gallery</a></li>
      <li><a href="contact.php">Contact</a></li>
      <li><a href="profile.php" class="btn-login active">My Profile</a></li>
    </ul>
  </nav>

  <!-- Profile Header (Aligned with style.css) -->
  <header class="profile-header">
    <div class="profile-header-content">
      <div class="profile-avatar">
        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&background=random" alt="User Avatar">
      </div>
      <div class="profile-info">
        <h1>Welcome, <?php echo htmlspecialchars(explode(' ', $user['name'])[0]); ?>!</h1>
        <p>Manage your account settings and track your landscaping projects.</p>
      </div>
    </div>
  </header>

  <!-- Profile Content -->
  <main class="profile-content">
    
    <!-- Status Messages -->
    <?php if ($message): ?>
      <div style="padding: 1rem; border-radius: 10px; margin-bottom: 2rem; border: 1px solid; 
          <?php echo $messageType === 'success' ? 'background: #dcfce7; color: #166534; border-color: #bbf7d0;' : 'background: #fee2e2; color: #991b1b; border-color: #fecaca;'; ?>">
          <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i> 
          <?php echo $message; ?>
      </div>
    <?php endif; ?>

    <div class="profile-grid">
      
      <!-- Sidebar Navigation -->
      <aside class="profile-sidebar">
        <div class="profile-menu">
            <a href="#" class="profile-menu-item active"><i class="fas fa-user-edit"></i> Edit Profile</a>
            <a href="contact.php" class="profile-menu-item"><i class="fas fa-plus-circle"></i> New Request</a>
            <a href="logout.php" class="profile-menu-item" style="color: var(--danger-red);"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
      </aside>

      <!-- Main Content Area -->
      <div class="profile-main">
        
        <!-- Account Details Form -->
        <div class="profile-card">
          <h3>Account Settings</h3>
          <form action="profile.php" method="POST">
              <div class="profile-form-grid">
                  <div class="form-group">
                      <label>Full Name</label>
                      <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['name']) ?? ''; ?>" required>
                  </div>
                  <div class="form-group">
                      <label>Email Address</label>
                      <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                  </div>
                  <div class="form-group">
                      <label>Phone Number</label>
                      <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>">
                  </div>
                  <div class="form-group">
                      <label>Update Password (Optional)</label>
                      <input type="password" name="password" placeholder="Leave blank to keep current">
                  </div>
              </div>

              <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end; align-items: center; gap: 1rem;">
                  <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
              </div>
          </form>
        </div>

        <!-- Project Quote Requests -->
        <div style="margin-top: 3rem;">
          <div class="section-header" style="text-align: left; margin-bottom: 2rem;">
              <h2 style="font-size: 1.8rem;">My Quote Requests</h2>
              <p>Current and past inquiries for your outdoor projects.</p>
          </div>
          
          <?php if (empty($userQuotes)): ?>
              <div class="profile-card" style="text-align: center; padding: 3rem;">
                  <i class="fas fa-clipboard-list" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem; display: block;"></i>
                  <p style="color: var(--text-gray);">You haven't requested any quotes yet.</p>
                  <a href="contact.php" class="btn btn-primary" style="margin-top: 1rem; display: inline-block;">Request a Free Quote</a>
              </div>
          <?php else: ?>
              <div class="requests-container">
                  <?php foreach ($userQuotes as $quote): ?>
                      <div class="profile-card" style="margin-bottom: 1.5rem; padding: 0; overflow: hidden; border: 1px solid #e2e8f0;">
                          <!-- Card Header -->
                          <div style="background: #f8fafc; padding: 1.25rem 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                              <h4 style="margin: 0; color: var(--primary-green);"><?php echo htmlspecialchars($quote['service']); ?></h4>
                              <span style="font-size: 0.85rem; color: var(--text-gray);"><i class="far fa-calendar-alt"></i> <?php echo date('M d, Y', strtotime($quote['created_at'])); ?></span>
                          </div>
                          <!-- Card Body -->
                          <div style="padding: 1.5rem;">
                              <div style="margin-bottom: 1rem;">
                                  <p style="font-size: 0.85rem; font-weight: 700; color: var(--text-gray); text-transform: uppercase; margin-bottom: 5px;">Project Details</p>
                                  <p style="color: var(--text-dark); line-height: 1.5;"><?php echo nl2br(htmlspecialchars($quote['message'])); ?></p>
                              </div>
                              <div style="display: flex; gap: 1rem; border-top: 1px solid #f1f5f9; padding-top: 1rem;">
                                  <span class="status-badge pending">Status: Reviewing</span>
                                  <span class="status-badge active" style="background: #f1f5f9; color: #475569;">Admin: Pending</span>
                              </div>
                          </div>
                      </div>
                  <?php endforeach; ?>
              </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="footer">
    <div class="footer-content">
      <div class="footer-section">
        <h3>GreenScape</h3>
        <p>Professional landscaping services that bring your outdoor vision to life.</p>
      </div>
      <div class="footer-section">
        <h3>Quick Links</h3>
        <p><a href="index.php">Home</a></p>
        <p><a href="about.php">About Us</a></p>
        <p><a href="services.php">Services</a></p>
        <p><a href="gallery.php">Gallery</a></p>
        <p><a href="contact.php">Contact</a></p>
      </div>
      <div class="footer-section">
        <h3>Contact Us</h3>
        <p><i class="fas fa-phone"></i> (555) 123-4567</p>
        <p><i class="fas fa-envelope"></i> info@greenscape.com</p>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; 2026 GreenScape Landscaping. All rights reserved.</p>
    </div>
  </footer>

  <script>function toggleMenu() { document.getElementById('navLinks').classList.toggle('active'); }</script>
</body>
</html>