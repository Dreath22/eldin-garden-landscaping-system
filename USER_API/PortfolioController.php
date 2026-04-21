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
        createPortfolio($pdo, $security);
        break;
    case 'update':
        updatePortfolio($pdo, $security);
        break;
    case 'delete':
        deletePortfolio($pdo, $security);
        break;
    case 'list':
    default:
        listPortfolios($pdo, $security);
        break;
}

// Directory management function
function createPortfolioDirectory($portfolioId) {
    $baseDir = '../uploads/gallery/';
    $portfolioDir = $baseDir . $portfolioId . '/';
    
    if (!is_dir($portfolioDir)) {
        mkdir($portfolioDir, 0755, true);
    }
    
    return $portfolioDir;
}

// File naming function
function generateFileName($directory, $fileExtension, $fileIndex) {
    return $directory . $fileIndex . '.' . $fileExtension;
}

// File upload processing function
function processPortfolioUploads($files, $portfolioDir) {
    $uploadedFiles = [];
    $totalSize = 0;
    $fileIndex = 1;
    
    foreach ($files['tmp_name'] as $key => $tmpName) {
        if ($files['error'][$key] === UPLOAD_ERR_OK) {
            $fileInfo = [
                'name' => $files['name'][$key],
                'type' => $files['type'][$key],
                'tmp_name' => $tmpName,
                'size' => $files['size'][$key],
                'error' => $files['error'][$key]
            ];
            
            // Validate file using existing FileUploadValidator
            $fileValidator = new FileUploadValidator($portfolioDir);
            $validation = $fileValidator->validateFile($fileInfo);
            
            if ($validation['valid']) {
                // Get file extension
                $extension = pathinfo($fileInfo['name'], PATHINFO_EXTENSION);
                
                // Generate numbered filename
                $fileName = generateFileName($portfolioDir, $extension, $fileIndex);
                
                // Move file to portfolio directory
                if (move_uploaded_file($tmpName, $fileName)) {
                    // Minimal file metadata for JSON storage
                    $fileMetadata = [
                        'stored_name' => basename($fileName), // Just "1.jpg", "2.png"
                        'upload_timestamp' => date('c') // ISO 8601 timestamp
                    ];
                    
                    $uploadedFiles[] = $fileMetadata;
                    $totalSize += $fileInfo['size'];
                    $fileIndex++;
                }
            }
        }
    }
    
    return [
        'files' => $uploadedFiles,
        'total_size' => $totalSize,
        'file_count' => count($uploadedFiles)
    ];
}

function createPortfolio($pdo, $security) {
    try {
        // Validate CSRF token from FormData
        if (!isset($_POST['csrf_token']) || !$security->validateCsrf()) {
            ApiResponse::forbidden('Invalid CSRF token');
        }
        
        // Check rate limiting
        $clientIp = $security->getClientIp();
        if (!$security->checkRateLimit($clientIp)) {
            ApiResponse::error('Rate limit exceeded', 429);
        }
        
        // Get data from FormData instead of JSON
        $data = [
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'serviceId' => $_POST['serviceId'] ?? '',
            'status' => $_POST['status'] ?? 'draft',
            'featured' => $_POST['featured'] ?? 0
        ];
        
        // Define enhanced validation rules
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
                'int' => ['min' => 0, 'max' => 1, 'message' => 'Featured must be 0 or 1']
            ]
        ];
        
        // Validate input
        $validator = new InputValidator();
        $validator->validate($data, $rules);
        
        if ($validator->hasErrors()) {
            ApiResponse::validationError($validator->getErrors());
        }
        
        $sanitized = $validator->getSanitized();
        
        // Check if service exists before inserting
        $serviceCheck = $pdo->prepare("SELECT id FROM services WHERE id = ?");
        $serviceCheck->execute([$sanitized['serviceId']]);
        
        if (!$serviceCheck->fetch()) {
            ApiResponse::validationError(['serviceId' => 'Invalid service ID']);
        }
        
        // Insert portfolio into database first to get portfolio ID
        $stmt = $pdo->prepare("
            INSERT INTO portfolios (title, description, services_id, featured, total_file_size, file_count, dir_path, files) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $sanitized['title'],
            $sanitized['description'],
            $sanitized['serviceId'],
            $sanitized['featured'] ?? 0,
            0, // Initial total_file_size
            0, // Initial file_count
            '', // Initial dir_path
            '' // Initial files JSON
        ]);
        
        $portfolioId = $pdo->lastInsertId();
        
        // Create portfolio directory
        $portfolioDir = createPortfolioDirectory($portfolioId);
        
        // Process file uploads if present
        $uploadResult = ['files' => [], 'total_size' => 0, 'file_count' => 0];
        if (isset($_FILES['files']) && is_array($_FILES['files'])) {
            $uploadResult = processPortfolioUploads($_FILES['files'], $portfolioDir);
            
            // Insert individual files into files table
            foreach ($uploadResult['files'] as $fileData) {
                $fileInsertStmt = $pdo->prepare("
                    INSERT INTO files (parent_id, category, file_name, created_at) 
                    VALUES (?, 'portfolio', ?, ?)
                ");
                $fileInsertStmt->execute([
                    $portfolioId,
                    $fileData['stored_name'],
                    $fileData['upload_timestamp']
                ]);
            }
        }
        
        // Update database with file information
        $updateStmt = $pdo->prepare("
            UPDATE portfolios 
            SET dir_path = ?, total_file_size = ?, file_count = ?
            WHERE portfolio_id = ?
        ");
        
        $updateStmt->execute([
            $portfolioDir,
            $uploadResult['total_size'],
            $uploadResult['file_count'],
            $portfolioId
        ]);
        
        // Log security event
        $security->logSecurityEvent('portfolio_created', [
            'content_id' => $portfolioId,
            'title' => $sanitized['title']
        ]);
        
        ApiResponse::created([
            'id' => $portfolioId,
            'title' => $sanitized['title'],
            'description' => $sanitized['description'],
            'dir_path' => $portfolioDir,
            'total_file_size' => $uploadResult['total_size'],
            'file_count' => $uploadResult['file_count']
        ], 'Portfolio created successfully');
        
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

function listPortfolios($pdo, $security) {
    try {
        // Validate CSRF token from FormData
        if (!isset($_POST['csrf_token']) || !$security->validateCsrf()) {
            ApiResponse::forbidden('Invalid CSRF token');
        }
        
        // Check rate limiting
        $clientIp = $security->getClientIp();
        if (!$security->checkRateLimit($clientIp)) {
            ApiResponse::error('Rate limit exceeded', 429);
        }
        
        // Get data from FormData instead of JSON
        $data = [
            'tab' => $_GET['tab'] ?? 'all',
            'page' => (int)$_GET['page'] ?? 1,
            'sort' => $_GET['sort'] ?? 'new',
            'category' => (int)$_GET['category'] ?? 1,
            'limit' => (int)$_GET['limit'] ?? 6,
        ];


        $rules = [
            'tab' => [
                'required' => ['message' => 'tab is required'],
                 'enum' => ['values' => ['all' ,'draft', 'live'], 'message' => 'tab must be either draft, live or featured']//, 'featured'
            ],
            'page' => [
                'required' => ['message' => 'page is required'],
                'int' => ['min' => 0, 'message' => 'Invalid page']
            ],
            'sort' => [
                'required' => ['message' => 'Sort category is required'],
                'enum' => ['values' => ['new', 'old', 'a-z', 'z-a'], 'message' => 'Invalid sort category']
            ],
            'category' => [
                'required' => ['message' => 'Category is required'],
                'int' => ['min' => 1, 'message' => 'Invalid Service category']
            ],
            'limit' => [
                'required' => ['message' => 'Limit is required'],
                'int' => ['min' => 1, 'message' => 'Invalid limit']
            ],
        ];
        
        // Validate input
        $validator = new InputValidator();
        $validator->validate($data, $rules);
        
        if ($validator->hasErrors()) {
            ApiResponse::validationError($validator->getErrors());
        }
        
        $sanitized = $validator->getSanitized();

        $sanitized['page'] = max(1, (int)($sanitized['page'] ?? 1));
        $sanitized['limit'] = (int)($sanitized['limit'] ?? 6);
        $offset = ($sanitized['page'] - 1) * $sanitized['limit'];
        // Check if service exists before selecting
        if($sanitized['category'] !== 1) {
            $serviceCheck = $pdo->prepare("SELECT id FROM services WHERE id = ?");
            $serviceCheck->execute([$sanitized['category']]);
            
            if (!$serviceCheck->fetch()) {
                //ApiResponse::validationError(['category' => 'Invalid service ID']);
                $sanitized['category'] = 1;
            }
        }
        // 1. Build the conditions
        $statusCondition = match ($sanitized['tab'] ?? 'all') {
            'draft' => "AND p.status = 'DRAFT'",
            'live'  => "AND p.status = 'LIVE'",
            "featured" => "",
            'all'   => "",
            default => "", 
        };

        // 2. Identify if we have a service parameter
        $hasServiceParam = ((int)$sanitized['category'] !== 1);
        $serviceCondition = $hasServiceParam ? "AND p.services_id = ?" : "";

        $orderCondition = match ($sanitized['sort'] ?? 'new') {
            'old'   => "ORDER BY p.created_at ASC, p.portfolio_id ASC",
            'a-z'   => "ORDER BY p.title ASC",
            'z-a'   => "ORDER BY p.title DESC",
            'new'   => "ORDER BY p.created_at DESC, p.portfolio_id DESC",
            default => "ORDER BY p.created_at DESC, p.portfolio_id DESC",
        };

        // 3. Prepare the statement
        $stmt = $pdo->prepare("
            SELECT p.portfolio_id, p.title, p.description, p.files, p.dir_path, p.featured, 
                p.total_file_size, p.file_count, s.service_name, p.status, p.created_at,
                GROUP_CONCAT(f.file_name ORDER BY f.file_name ASC) as filenames
            FROM portfolios p
            LEFT JOIN services s ON p.services_id = s.id
            LEFT JOIN files f ON p.portfolio_id = f.parent_id AND f.category = 'portfolio'
            WHERE 1=1
            $statusCondition
            $serviceCondition
            GROUP BY p.portfolio_id
            $orderCondition
            LIMIT ? OFFSET ?
        ");

        // 4. Manual Binding (The safest way for LIMIT/OFFSET)
        $currParam = 1;

        // If category is not 1, it occupies the first '?' (after status which has no ?)
        if ($hasServiceParam) {
            $stmt->bindValue($currParam++, (int)$sanitized['category'], PDO::PARAM_INT);
        }

        // These will now correctly fill the next available '?' placeholders
        $stmt->bindValue($currParam++, (int)$sanitized['limit'], PDO::PARAM_INT);
        $stmt->bindValue($currParam++, (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        $portfolios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Build Count SQL with the same filters as the main query
        $countSql = "SELECT COUNT(*) FROM portfolios as p WHERE 1=1 $statusCondition $serviceCondition";
        $countStmt = $pdo->prepare($countSql);

        // If category filter is active, we need to bind it here too!
        if ($hasServiceParam) {
            $countStmt->bindValue(1, (int)$sanitized['category'], PDO::PARAM_INT);
        }

        $countStmt->execute();
        $totalRecords = (int)$countStmt->fetchColumn();
        $totalPages = ceil($totalRecords / $sanitized['limit']);

        // Prepare pagination data
        $paginationData = [
            'currentPage'  => (int)$sanitized['page'],
            'totalPages'   => $totalPages,
            'totalRecords' => $totalRecords,
            'limit'        => (int)$sanitized['limit'],
            'hasNextPage'  => $sanitized['page'] < $totalPages,
            'hasPrevPage'  => $sanitized['page'] > 1
        ];
        
        // Log security event
        $security->logSecurityEvent('portfolios_listed', [
            'count' => count($portfolios)
        ]);
        $data = [
            'data' => $portfolios,
            'pagination' => $paginationData
        ];
        
        // Send response with both data and pagination
        ApiResponse::success($data, 'Portfolios retrieved successfully');
        
    } catch (Exception $e) {
        error_log('Portfolio listing error: ' . $e->getMessage());
        ApiResponse::serverError('Failed to retrieve portfolios');
    }
}


?>