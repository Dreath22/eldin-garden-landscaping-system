<?php
require_once __DIR__ . '/config/auth_middleware.php';

// Get user info for personalized message
$sessionData = initStandardSession();
$user = $sessionData['user'];
$isLoggedIn = $sessionData['isLoggedIn'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - GreenScape Landscaping</title>
    <link rel="stylesheet" href="client-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .access-denied-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 80vh;
            text-align: center;
            padding: 2rem;
        }
        .error-code {
            font-size: 8rem;
            font-weight: bold;
            color: #dc3545;
            margin: 0;
            line-height: 1;
        }
        .error-message {
            font-size: 2rem;
            margin: 1rem 0;
            color: #333;
        }
        .error-description {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 2rem;
            max-width: 600px;
        }
        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        .btn {
            padding: 0.75rem 2rem;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-primary {
            background-color: #28a745;
            color: white;
        }
        .btn-primary:hover {
            background-color: #218838;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .lock-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="access-denied-container">
        <i class="fas fa-lock lock-icon"></i>
        <h1 class="error-code">403</h1>
        <h2 class="error-message">Access Denied</h2>
        
        <?php if ($isLoggedIn): ?>
            <p class="error-description">
                Sorry <strong><?= htmlspecialchars($user['fullname']) ?></strong>, you don't have permission to access this page.
                This area is restricted to administrators only.
            </p>
        <?php else: ?>
            <p class="error-description">
                You need to be logged in to access this page. Please sign in to continue.
            </p>
        <?php endif; ?>
        
        <div class="action-buttons">
            <?php if ($isLoggedIn): ?>
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Go to Homepage
                </a>
                <a href="profile.php" class="btn btn-secondary">
                    <i class="fas fa-user"></i> My Profile
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Go to Homepage
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
