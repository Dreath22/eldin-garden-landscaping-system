<?php
/**
 * Create Email with Validation
 */
require_once __DIR__ . '/../utils/EmailValidatorSimple.php';
/**
 * @param array $inputData   The decoded JSON data or POST array
 * @param PDO   $pdo         The database connection
 * @param array $currentUser The session/user data
 */
function createEmailSimple($inputData, $pdo, $currentUser) {
    // 1. Safety check for the input data
    if (!$inputData || !is_array($inputData)) {
        echo json_encode(['success' => false, 'error' => 'Invalid or missing input data']);
        return;
    }
    // 2. Permission check (Uncommented for safety)
    // $roleData = RoleValidation($pdo, $currentUser['id']);

    //     // Check if user exists and if the role string matches 'client'
    // if (!$roleData || $roleData['role'] !== 'Customer') {
    //     echo json_encode(['success' => false, 'error' => 'Permission denied: Client role required']);
    //     return;
    // }

    // 3. Validate input data using the passed parameter
    $validator = new EmailValidatorSimple();
    $validation = $validator->validateEmailData($inputData, $currentUser['id']);
    
    if (!$validation['valid']) {
        echo json_encode([
            'success' => false, 
            'error' => 'Validation failed',
            'validation_errors' => $validation['errors']
        ]);
        return;
    }
    
    try {
        // 4. Insert new email
        $stmt = $pdo->prepare("
            INSERT INTO emails (
                user_id, 
                service_id, 
                subject, 
                preview, 
                full_content
            ) VALUES (
                :user_id,
                :service_id,
                :subject,
                :preview,
                :full_content
            )
        ");
        
        $stmt->execute([
            ':user_id'      => $currentUser['id'],
            ':service_id'   => $validation['data']['service_id'] ?? null,
            ':subject'      => $validation['data']['subject'],
            ':preview'      => $validation['data']['preview'] ?? '',
            ':full_content' => $validation['data']['content'],
        ]);
        
        $emailId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Email created successfully',
            'data' => [
                'id' => (int)$emailId,
            ]
        ], JSON_PRETTY_PRINT);
        
    } catch (PDOException $e) {
        error_log("Create email error: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'error' => 'Database error: occurred while saving the email.'
        ]);
    }
}

if (!isset($currentUser)) {
    $currentUser = [
        'id' => 7
    ];
}
/**
 * EXECUTION LOGIC
 * This handles both standard Form POSTs and JSON Body requests
 */
$rawInput = file_get_contents('php://input');
$jsonData = json_decode($rawInput, true);

// If it's a JSON request, use $jsonData; otherwise, fallback to $_POST
$dataToProcess = !empty($jsonData) ? $jsonData : $_POST;

// Call the function
createEmailSimple($dataToProcess, $pdo, $currentUser);
?>