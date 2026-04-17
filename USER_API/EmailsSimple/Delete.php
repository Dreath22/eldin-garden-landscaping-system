<?php
function deleteEmail($data, $pdo, $currentUser) {
    // Base query - show all emails for demo (in production, filter by user)
    
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Invalid email ID']);
        return;
    }
    
    try {
        $sql = "DELETE FROM emails WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        echo json_encode(['success' => true, 'message' => 'Email deleted successfully']);
    } catch (PDOException $e) {
        error_log("Simple email delete error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}




// Execute the function
deleteEmail($_GET, $pdo, $currentUser);
?>