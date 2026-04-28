<?php
require_once __DIR__ . '/config/auth_middleware.php';

// Initialize standard session
$sessionData = initStandardSession();
$user = $sessionData['user'];
$isLoggedIn = $sessionData['isLoggedIn'];

$debugLink = ""; // Variable to hold the link for testing
$message = "";
$messageType = "";


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    // Logic: Always show success to prevent email enumeration
    $message = "If this email is registered, you will receive a reset link shortly.";
    $messageType = "success";

    if ($stmt->fetch()) {
        $token = bin2hex(random_bytes(32));
        $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));
        
        // Delete any existing tokens for this email first
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->execute([$email]);

        $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expiry) VALUES (?, ?, ?)");
        $stmt->execute([$email, $token, $expiry]);
        
        // Generate the link for manual testing
        $debugLink = "http://localhost/greenscape/reset-password.php?token=" . $token;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password - GreenScape Landscaping</title>
  <link rel="stylesheet" href="client-style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div class="auth-page">
    <div class="auth-container">
      
      <!-- Left Side: Branding (Consistent with Login/Signup) -->
      <div class="auth-image">
        <div class="auth-image-content">
          <h2>Secure Your Account</h2>
          <p>Don't worry, it happens to the best of us. Let's get you back to your garden.</p>
        </div>
      </div>

      <!-- Right Side: Reset Form -->
      <div class="auth-form">
        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Home</a>
        
        <div class="auth-logo">
          <div class="logo-icon"><i class="fas fa-leaf"></i></div>
          <span>GreenScape</span>
        </div>

        <h2>Reset Password</h2>
        <p>Enter your email address and we'll send you a link to reset your password.</p>

        <?php if($message): ?>
            <div style="padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; 
                <?php echo $messageType === 'success' ? 'background: rgba(34, 197, 94, 0.1); color: #166534; border: 1px solid #bbf7d0;' : 'background: rgba(239, 68, 68, 0.1); color: #991b1b; border: 1px solid #fecaca;'; ?>">
                <i class="fas fa-info-circle"></i> <?= $message ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="forgot-password.php">
          <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="e.g. yourname@email.com" required autofocus>
          </div>
          
          <button type="submit" class="btn btn-primary" style="width: 100%;">Send Reset Link</button>
        </form>

        <div class="auth-footer">
            <p>Remember your password? <a href="login.php">Log in</a></p>
        </div>

        <!-- Developer Debug Box -->
        <?php if($debugLink !== ""): ?>
          <div style="margin-top: 2rem; padding: 1.25rem; border: 1px dashed var(--primary-green); background: var(--light-cream); border-radius: 10px;">
            <p style="font-size: 0.75rem; color: var(--text-gray); margin-bottom: 0.5rem; text-transform: uppercase; font-weight: bold;">
                <i class="fas fa-tools"></i> Developer Preview:
            </p>
            <p style="font-size: 0.8rem; color: var(--text-dark); margin-bottom: 0.75rem;">Link generated for testing (XAMPP):</p>
            <a href="<?= $debugLink ?>" style="color: var(--primary-green); word-break: break-all; font-size: 0.85rem; font-weight: 600; text-decoration: underline;"><?= $debugLink ?></a>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>

  <script>
    // Simple fade in for the form
    document.querySelector('.auth-container').style.opacity = '0';
    window.onload = () => {
        document.querySelector('.auth-container').style.transition = 'opacity 0.6s ease';
        document.querySelector('.auth-container').style.opacity = '1';
    };
  </script>
</body>
</html>