<?php
/**
 * Centralized Authentication Middleware
 * Single source of truth for authentication and authorization
 */

require_once __DIR__ . '/config.php';

/**
 * Check if user is logged in
 * @return bool True if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user data
 * @return array|null User data or null if not logged in
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    static $user = null;
    if ($user === null) {
        global $pdo;
        $userId = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT id, fullname, email, role, status FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
    }
    
    return $user;
}

/**
 * Check if current user has specific role
 * @param string $role Role to check
 * @return bool True if user has the role
 */
function hasRole($role) {
    $user = getCurrentUser();
    return $user && $user['role'] === $role;
}

/**
 * Check if current user is admin
 * @return bool True if user is admin
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Require authentication - redirect if not logged in
 * @param string $redirectUrl Redirect URL (default: login.php)
 */
function requireAuth($redirectUrl = 'login.php') {
    if (!isLoggedIn()) {
        header("Location: $redirectUrl");
        exit();
    }
}

/**
 * Require admin role - redirect if not admin
 * @param string $redirectUrl Redirect URL for non-admins (default: 403_access_denied.php)
 */
function requireAdmin($redirectUrl = '403_access_denied.php') {
    requireAuth();
    
    if (!isAdmin()) {
        header("Location: $redirectUrl");
        exit();
    }
}

/**
 * Initialize session if not already started
 */
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Standard session initialization for all pages
 * @return array User data and login status
 */
function initStandardSession() {
    initSession();
    
    $userId = $_SESSION['user_id'] ?? null;
    $user = $userId ? getUserById($GLOBALS['pdo'], $userId) : null;
    $isLoggedIn = (bool)$user;
    
    return [
        'user' => $user,
        'isLoggedIn' => $isLoggedIn,
        'userId' => $userId
    ];
}

// Auto-initialize session when this file is included
initSession();
?>
