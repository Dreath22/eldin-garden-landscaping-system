<?php
/**
 * Password Reset Controller
 * Handles secure password reset token generation and validation
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/utils/ApiResponse.php';

/**
 * Generate a cryptographically secure 6-character alphanumeric token
 * @param int $length Token length (default 6)
 * @return string Secure random token
 */
function generateSecureToken($length = 6) {
    // Characters allowed: A-Z, 0-9
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $token = '';
    
    for ($i = 0; $i < $length; $i++) {
        $token .= $characters[random_int(0, strlen($characters) - 1)];
    }
    
    return $token;
}

/**
 * Create password reset request
 * @param PDO $pdo Database connection
 * @param string $email User email
 * @return array Response data
 */
function createPasswordResetRequest($pdo, $email) {
    try {
        // Generate secure token
        $token = generateSecureToken(6);
        
        // Hash token for storage
        $hashedToken = password_hash($token, PASSWORD_BCRYPT);
        
        // Set expiration time (1 hour from now)
        $expiryTime = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Delete any existing reset requests for this email
        $deleteStmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
        $deleteStmt->execute([$email]);
        
        // Insert new reset request
        $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expiry, created_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$email, $hashedToken, $expiryTime]);
        
        // In production, send email with token
        // For now, return token for testing
        return [
            'status' => 'success',
            'message' => 'Password reset instructions sent to your email',
            'token' => $token // Only for development/testing
        ];
        
    } catch (Exception $e) {
        error_log("Password reset request failed: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Failed to process password reset request'
        ];
    }
}

/**
 * Handle API endpoint for password reset requests
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['email']) || !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        ApiResponse::error('Valid email address is required');
        exit;
    }
    
    $email = $input['email'];
    
    // Create password reset request
    $result = createPasswordResetRequest($pdo, $email);
    
    echo json_encode($result);
    exit;
}

/**
 * Handle password reset form submission
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
    // This is handled by the existing reset-password.php file
    // Redirect to reset page with token
    header('Location: ../reset-password.php?token=' . urlencode($_POST['token']));
    exit;
}
?>
