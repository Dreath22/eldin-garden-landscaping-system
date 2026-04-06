<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../utils/sanitizeInput.php';
require_once __DIR__ . '/../utils/RateLimiter.php';
require_once __DIR__ . '/Summary.php';
require_once __DIR__ . '/ServiceService.php';
require_once __DIR__ . '/ServiceRepository.php';

function deleteService($id, $pdo) {
    // RATE LIMITING - Prevent abuse
    $clientId = RateLimiter::getClientIdentifier();
    if (!RateLimiter::checkLimit($clientId)) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Rate limit exceeded'], JSON_PRETTY_PRINT);
        return;
    }
    // Create dependencies
    $repository = new ServiceRepository($pdo);
    $service = new ServiceService($repository);
    
    $result = $service->deleteService($id);
    
    // Return JSON response
    echo json_encode($result->toArray(), JSON_PRETTY_PRINT);
}

?>