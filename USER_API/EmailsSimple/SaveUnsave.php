<?php
function saveEmail($data, $pdo, $currentUser) {
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Invalid email ID']);
        return;
    }
    
    try {
        // 1. Check if the record already exists
        $checkSql = "SELECT 1 FROM saved_emails WHERE user_id = :user_id AND email_id = :email_id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([':user_id' => $currentUser['id'], ':email_id' => $id]);
        
        if ($checkStmt->fetch()) {
            // 2. Record exists -> DELETE it (Unsave)
            $deleteSql = "DELETE FROM saved_emails WHERE user_id = :user_id AND email_id = :email_id";
            $deleteStmt = $pdo->prepare($deleteSql);
            $deleteStmt->execute([':user_id' => $currentUser['id'], ':email_id' => $id]);
            
            echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Email unsaved']);
        } else {
            // 3. Record doesn't exist -> INSERT it (Save)
            $insertSql = "INSERT INTO saved_emails (user_id, email_id) VALUES (:user_id, :email_id)";
            $insertStmt = $pdo->prepare($insertSql);
            $insertStmt->execute([':user_id' => $currentUser['id'], ':email_id' => $id]);
            
            echo json_encode(['success' => true, 'action' => 'inserted', 'message' => 'Email saved successfully']);
        }
        
    } catch (PDOException $e) {
        error_log("Email toggle error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
}

// Execute the function
saveEmail($_GET, $pdo, $currentUser);
?>