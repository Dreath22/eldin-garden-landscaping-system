<?php
/**
 * Simple Email Management Controller
 * Works with existing authentication system
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/USER_API/RoleValidation.php';

// Get the action from URL parameter
$action = $_GET['action'] ?? 'list';

// Simple session-based authentication


// Rate limiting (commented out for testing)
// require_once __DIR__ . '/utils/RateLimiter.php';
// $rateLimiter = new RateLimiter('email_' . $action . '_' . $currentUser['id'], 60, 100);

// if (!$rateLimiter->allowRequest()) {
//     http_response_code(429);
//     echo json_encode(['success' => false, 'error' => 'Too many requests. Please try again later.']);
//     exit;
// }

try {
    switch ($action) {
        case 'list':
            include __DIR__ . '/EmailsSimple/List.php';
            break;
        case 'create':
            include __DIR__ . '/EmailsSimple/Create.php';
            break;
        case 'stats':
            include __DIR__ . '/EmailsSimple/Stats.php';
            break;
        case 'delete':
            include __DIR__ . '/EmailsSimple/Delete.php';
            break;
        case 'updateread':
            include __DIR__ . '/EmailsSimple/UpdateRead.php';
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Action not found']);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
