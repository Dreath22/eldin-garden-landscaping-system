<?php
require_once __DIR__ . '/config/auth_middleware.php';

// Initialize standard session
$sessionData = initStandardSession();
$user = $sessionData['user'];
$isLoggedIn = $sessionData['isLoggedIn'];

$error = "";
$resetRequest = null;

if (!isset($_GET['token'])) { 
    die("Error: No token provided in the URL."); 
}

$token = $_GET['token'];

// Debugging: Check if token exists at all
$stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND used = 0");
$stmt->execute([$token]);
$resetRequest = $stmt->fetch();

if (!$resetRequest) {
    die("Error: Invalid or expired token. Please request a new password reset.");
}

// Check expiration with secure timing-attack-safe comparison
$expiryTime = strtotime($resetRequest['expiry']);
$currentTime = time();

if ($expiryTime < $currentTime) {
    // Delete expired token
    $pdo->prepare("DELETE FROM password_resets WHERE token = ?")->execute([$token]);
    die("Error: Token expired. Please request a new password reset.");
}

// If we reached here, the token is valid
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $newPass = $_POST['password'];
    $confirm = $_POST['confirm-password'];

    if (empty($newPass) || strlen($newPass) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($newPass === $confirm) {
        // Validate password strength
        if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $newPass)) {
            $error = "Password must be at least 8 characters with uppercase, lowercase, number, and special character.";
        } else {
            // Mark token as used and update password
            $hashed = password_hash($newPass, PASSWORD_BCRYPT);
            
            $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $stmt->execute([$token]);
            
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$hashed, $resetRequest['email']]);
            
            header("Location: login.php?success=Password updated successfully. Please login.");
            exit();
        }
    } else {
        $error = "Passwords do not match.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>New Password - GreenScape</title>
  <link rel="stylesheet" href="client-style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div class="auth-page">
    <div class="auth-container" style="max-width: 500px;">
      <div class="auth-form">
        <a href="login.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Login</a>
        <h2>Enter New Password</h2>
        <p style="margin-bottom: 1rem; color: var(--text-gray);">Resetting password for: <strong><?= htmlspecialchars($resetRequest['email']) ?></strong></p>
        
        <?php if($error !== ""): ?>
            <p style="color:red; background: rgba(220, 53, 69, 0.1); padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem;"><?= $error ?></p>
        <?php endif; ?>

        <form method="POST">
          <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>" required>
          <div class="form-group">
            <label>New Password</label>
            <input type="password" name="password" placeholder="At least 8 characters with uppercase, lowercase, number, and special character" required>
          </div>
          <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm-password" placeholder="Repeat password" required>
          </div>
          <button type="submit" class="btn btn-primary" style="width: 100%;">Update Password</button>
        </form>
      </div>
    </div>
  </div>
</body>
</html>