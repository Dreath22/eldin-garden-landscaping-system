<?php
require_once __DIR__ . '/USER_API/UsersController.php';
$userId = $_SESSION['user_id'] ?? null;

$user = $userId ? getUserById($pdo, $userId) : null;

$isLoggedIn = (bool)$user;
// Check if user exists AND is allowed to be here
// $isLoggedIn = ($user && $user['status'] === 'active'); 

// If user is already logged in, redirect to appropriate page
if ($isLoggedIn) {
    if ($user['role'] == 'admin') {
        header("Location: admin-dashboard.php");
    } else {
        header("Location: profile.php");
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $pass = $_POST['password'];
    $confirm = $_POST['confirm-password'];

    if ($pass !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Email already registered.";
        } else {
            $hashedPass = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone_number, password) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$fullname, $email, $phone, $hashedPass])) {
                $newUserId = $pdo->lastInsertId();
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['user_name'] = $fullname;
                $_SESSION['role'] = "Customer";

                // Change 'success=' to 'status=success' to match your index.php JS
                header("Location: index.php?status=success");
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up - EldinGarden</title>
  <link rel="stylesheet" href="client-style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div class="auth-page">
    <div class="auth-container">
      <div class="auth-image">
        <div class="auth-image-content">
          <h2>Join Us!</h2>
          <p>Create an account to track your landscaping projects and get exclusive offers.</p>
        </div>
      </div>
      <div class="auth-form">
        <!-- Back Button -->
        <a href="index.php" class="back-btn">
          <i class="fas fa-arrow-left"></i> Back to Home
        </a>

        <a href="index.php" class="auth-logo">
          <div class="logo-icon">
            <img src="assets/img/LOGO.png" alt="EldinGarden Logo" style="height: 24px; width: auto; vertical-align: middle;">
          </div>
          EldinGarden
        </a>
        <h2>Create Account</h2>
        <?php if(isset($error)): ?><p style="color:red; margin-bottom: 1rem;"><?= $error ?></p><?php endif; ?>
        
        <form method="POST">
          <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="fullname" placeholder="Enter your full name" required>
          </div>
          <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="Enter your email" required>
          </div>
          <div class="form-group">
            <label>Phone Number</label>
            <input type="tel" name="phone" pattern="[+ 0-9()-]+" title="Please enter a valid phone number" placeholder="Enter your phone number">
          </div>
          <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters" placeholder="Create a password" required>
          </div>
          <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm-password" placeholder="Confirm your password" required>
          </div>
          <button type="submit" class="btn btn-primary" style="width: 100%;">Create Account</button>
        </form>

        <div class="auth-footer">
          <p>Already have an account? <a href="login.php">Sign in</a></p>
        </div>
      </div>
    </div>
  </div>
  <script>
    document.querySelector('form').addEventListener('submit', function(e) {
        // Select elements
        const phone = document.querySelector('input[name="phone"]');
        const password = document.querySelector('input[name="password"]');
        const confirmPassword = document.querySelector('input[name="confirm-password"]');
        
        // 1. Phone Number Validation (International Format)
        // Supports: +1234567890, 123-456-7890, (123) 456-7890
        const phoneRegex = /^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/;
        
        // 2. Password Strength Validation
        // Requirements: Min 8 chars, 1 Uppercase, 1 Lowercase, 1 Number, 1 Special Char
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

        let errors = [];

        if (!phoneRegex.test(phone.value)) {
            errors.push("Please enter a valid phone number.");
        }

        if (!passwordRegex.test(password.value)) {
            errors.push("Password must be at least 8 characters long and include uppercase, lowercase, a number, and a special character.");
        }

        if (password.value !== confirmPassword.value) {
            errors.push("Passwords do not match.");
        }

        // If there are errors, stop the form and alert the user
        if (errors.length > 0) {
            e.preventDefault();
            alert(errors.join("\n"));
        }
    });
  </script>
</body>
</html>