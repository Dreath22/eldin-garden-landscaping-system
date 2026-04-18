<?php
class ApiResponse {
    public static function success($data = null, string $message = 'Success'): void {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c')
        ]);
        exit;
    }
    
    public static function error(string $message, int $code = 400, $errors = null): void {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
            'timestamp' => date('c')
        ]);
        exit;
    }
    
    public static function validationError(array $errors): void {
        self::error('Validation failed', 422, $errors);
    }
    
    public static function created($data = null, string $message = 'Resource created successfully'): void {
        http_response_code(201);
        self::success($data, $message);
    }
    
    public static function notFound(string $message = 'Resource not found'): void {
        self::error($message, 404);
    }
    
    public static function unauthorized(string $message = 'Unauthorized'): void {
        self::error($message, 401);
    }
    
    public static function forbidden(string $message = 'Forbidden'): void {
        self::error($message, 403);
    }
    
    public static function serverError(string $message = 'Internal server error'): void {
        self::error($message, 500);
    }
}
?>
