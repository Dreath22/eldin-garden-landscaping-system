<?php
require_once __DIR__ . '/RateLimiter.php';

class SecurityMiddleware {
    private $csrfTokenLife = 3600; // 1 hour
    
    public function validateCsrf(): bool {
        $token = $_POST['csrf_token'] ?? '';
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        
        return hash_equals($sessionToken, $token) && 
               !empty($sessionToken) && 
               (time() - $_SESSION['csrf_token_time'] < $this->csrfTokenLife);
    }
    
    public function generateCsrfToken(): string {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        
        return $token;
    }
    
    public function checkRateLimit(string $identifier = null): bool {
        $clientId = $identifier ?? RateLimiter::getClientIdentifier();
        return RateLimiter::checkLimit($clientId);
    }
    
    public function sanitizeJsonInput(): array {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON input');
        }
        
        return $data;
    }
    
    public function getClientIp(): string {
        return RateLimiter::getClientIdentifier();
    }
    
    public function logSecurityEvent(string $event, array $context = []): void {
        $logEntry = [
            'timestamp' => date('c'),
            'event' => $event,
            'ip' => $this->getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'context' => $context
        ];
        
        error_log('SECURITY: ' . json_encode($logEntry));
    }
}
?>
