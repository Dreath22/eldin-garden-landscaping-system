<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../utils/sanitizeInput.php';
require_once __DIR__ . '/../utils/RateLimiter.php';
require_once __DIR__ . '/ServiceService.php';
require_once __DIR__ . '/ServiceRepository.php';

// RATE LIMITING - Prevent abuse
$clientId = RateLimiter::getClientIdentifier();
if (!RateLimiter::checkLimit($clientId)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Rate limit exceeded'], JSON_PRETTY_PRINT);
    exit;
}

// Create dependencies
$repository = new ServiceRepository($pdo);
$service = new ServiceService($repository);

// Extract request data
$requestData = [
    'name' => $_POST['name'] ?? null,
    'description' => $_POST['description'] ?? null,
    'features' => $_POST['features'] ?? null,
    'baseprice' => $_POST['baseprice'] ?? null,
    'duration' => $_POST['duration'] ?? null,
    'status' => $_POST['status'] ?? null
];

// Process create
$result = $service->createService($requestData);

// Return JSON response
echo json_encode($result->toArray(), JSON_PRETTY_PRINT);
?>