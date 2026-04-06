<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../utils/sanitizeInput.php';
require_once __DIR__ . '/../utils/RateLimiter.php';
require_once __DIR__ . '/BookingService.php';
require_once __DIR__ . '/BookingRepository.php';

class BookingsController {
    
    private BookingService $service;
    
    public function __construct(PDO $pdo) {
        $repository = new BookingRepository($pdo);
        $this->service = new BookingService($repository);
    }
    
    public function list(array $params): void {
        // RATE LIMITING - Prevent abuse
        $clientId = RateLimiter::getClientIdentifier();
        if (!RateLimiter::checkLimit($clientId)) {
            http_response_code(429);
            echo json_encode(['success' => false, 'message' => 'Rate limit exceeded'], JSON_PRETTY_PRINT);
            return;
        }
        
        // Process request
        $result = $this->service->getList($params);
        
        // Return JSON response
        echo json_encode($result->toArray(), JSON_PRETTY_PRINT);
    }
    
    public function create(array $data): void {
        // RATE LIMITING - Prevent abuse
        $clientId = RateLimiter::getClientIdentifier();
        if (!RateLimiter::checkLimit($clientId)) {
            http_response_code(429);
            echo json_encode(['success' => false, 'message' => 'Rate limit exceeded'], JSON_PRETTY_PRINT);
            return;
        }
        
        // Process request
        $result = $this->service->create($data);
        
        // Return JSON response
        echo json_encode($result->toArray(), JSON_PRETTY_PRINT);
    }
    
    public function update(int $id, array $data): void {
        // RATE LIMITING - Prevent abuse
        $clientId = RateLimiter::getClientIdentifier();
        if (!RateLimiter::checkLimit($clientId)) {
            http_response_code(429);
            echo json_encode(['success' => false, 'message' => 'Rate limit exceeded'], JSON_PRETTY_PRINT);
            return;
        }
        
        // Process request
        $result = $this->service->update($id, $data);
        
        // Return JSON response
        echo json_encode($result->toArray(), JSON_PRETTY_PRINT);
    }
    
    public function delete(int $id): void {
        // RATE LIMITING - Prevent abuse
        $clientId = RateLimiter::getClientIdentifier();
        if (!RateLimiter::checkLimit($clientId)) {
            http_response_code(429);
            echo json_encode(['success' => false, 'message' => 'Rate limit exceeded'], JSON_PRETTY_PRINT);
            return;
        }
        
        // Process request
        $result = $this->service->delete($id);
        
        // Return JSON response
        echo json_encode($result->toArray(), JSON_PRETTY_PRINT);
    }
}

?>
