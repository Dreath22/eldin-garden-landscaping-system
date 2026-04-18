<?php
// PortfolioController.php
require_once '../config/config.php';
require_once __DIR__ . '/utils/InputValidator.php';
require_once __DIR__ . '/utils/FileUploadValidator.php';
require_once __DIR__ . '/utils/ApiResponse.php';
require_once __DIR__ . '/utils/SecurityMiddleware.php';

// Start session for CSRF
session_start();

// Initialize security middleware
$security = new SecurityMiddleware();

// Handle different actions based on the 'action' parameter
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'create':
        createPortfolio($pdo, $security, $_POST);
        break;
    case 'update':
        updatePortfolio($pdo, $security);
        break;
    case 'delete':
        deletePortfolio($pdo, $security);
        break;
    case 'list':
    default:
        listPortfolios($pdo);
        break;
}

function createPortfolio($pdo, $security, $_POST) {
    try {
        // Validate CSRF token for POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$security->validateCsrf()) {
            ApiResponse::forbidden('Invalid CSRF token');
        }
        
        // Check rate limiting
        $clientIp = $security->getClientIp();
        if (!$security->checkRateLimit($clientIp)) {
            ApiResponse::error('Rate limit exceeded', 429);
        }
        
        // Get and validate JSON input
        $data = $security->sanitizeJsonInput();
        
        // Define validation rules
        $rules = [
            'title' => [
                'required' => ['message' => 'Title is required'],
                'string' => ['min' => 3, 'max' => 255, 'message' => 'Title must be between 3 and 255 characters']
            ],
            'description' => [
                'required' => ['message' => 'Description is required'],
                'string' => ['min' => 10, 'max' => 2000, 'message' => 'Description must be between 10 and 2000 characters']
            ],
            'serviceId' => [
                'required' => ['message' => 'Service category is required'],
                'int' => ['min' => 1, 'message' => 'Invalid service ID']
            ],
            'status' => [
                'required' => ['message' => 'Status is required'],
                'enum' => ['values' => ['draft', 'live'], 'message' => 'Status must be either draft or live']
            ],
            'featured' => [
                'required' => ['message' => 'Featured is required'],
                'boolean' => ['message' => 'Featured must be a boolean value']
            ],
        ];
        
        // Validate input
        $validator = new InputValidator();
        $validator->validate($data, $rules);
        
        if ($validator->hasErrors()) {
            ApiResponse::validationError($validator->getErrors());
        }
        
        $sanitized = $validator->getSanitized();
        
        // Handle file uploads if present
        $uploadedFiles = [];
        if (isset($_FILES['files']) && is_array($_FILES['files'])) {
            $fileValidator = new FileUploadValidator('../uploads/portfolio/');
            
            foreach ($_FILES['files']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['files']['name'][$key],
                        'type' => $_FILES['files']['type'][$key],
                        'tmp_name' => $tmpName,
                        'error' => $_FILES['files']['error'][$key],
                        'size' => $_FILES['files']['size'][$key]
                    ];
                    
                    $validation = $fileValidator->validateFile($file);
                    if (!$validation['valid']) {
                        ApiResponse::validationError(['files' => $validation['errors']]);
                    }
                    
                    $uploadedPath = $fileValidator->moveUploadedFile($file);
                    if ($uploadedPath) {
                        $uploadedFiles[] = $uploadedPath;
                    }
                }
            }
        }
        
        // Insert into database
        $stmt = $pdo->prepare("
            INSERT INTO portfolios (title, description, dir_path, featured, total_file_size, file_count, services_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $sanitized['title'],
            $sanitized['description'],
            $_SESSION['user_id'] ?? 1 // Default to admin user if not logged in
        ]);
        
        $contentId = $pdo->lastInsertId();
        
        // Log security event
        $security->logSecurityEvent('portfolio_created', [
            'content_id' => $contentId,
            'title' => $sanitized['title']
        ]);
        
        ApiResponse::created([
            'id' => $contentId,
            'title' => $sanitized['title'],
            'description' => $sanitized['description'],
            'files' => $uploadedFiles
        ], 'Portfolio content created successfully');
        
    } catch (Exception $e) {
        error_log('Portfolio creation error: ' . $e->getMessage());
        ApiResponse::serverError('Failed to create portfolio content');
    }
}

function updatePortfolio($pdo, $security) {
    // Handle portfolio update
    ApiResponse::error('Update functionality not yet implemented', 501);
}

function deletePortfolio($pdo, $security) {
    // Handle portfolio deletion
    ApiResponse::error('Delete functionality not yet implemented', 501);
}

function listPortfolios($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT id, title, description, created_at, uploaded_by 
            FROM content 
            ORDER BY created_at DESC
        ");
        
        $portfolios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        ApiResponse::success($portfolios, 'Portfolios retrieved successfully');
        
    } catch (Exception $e) {
        error_log('Portfolio listing error: ' . $e->getMessage());
        ApiResponse::serverError('Failed to retrieve portfolios');
    }
}
?>