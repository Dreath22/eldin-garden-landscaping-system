<?php
function UpdateRead($id, $pdo, $currentUser){
    // 1. Safety check for the input data
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Invalid or missing input data']);
        return;
    }
    // 2. Permission check (Uncommented for safety)
    // $roleData = RoleValidation($pdo, $currentUser['id']);

    // // Check if user exists and if the role string matches 'client'
    // if (!$roleData || $roleData['role'] !== 'Admin') {
    //     echo json_encode(['success' => false, 'error' => 'Permission denied: Admin role required']);
    //     return;
    // }
    
    // 3. Update read status
    $stmt = $pdo->prepare("UPDATE emails SET status='read', read_id=:admin_id, time_update=:timenow WHERE id = :id");
    $stmt->execute([
        'id' => $id,
        'admin_id' => $currentUser['id'],
        'timenow' => date('Y-m-d H:i:s')
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Email marked as read']);
}
$currentUser = ['id' => 1];
UpdateRead($_GET['id'], $pdo, $currentUser);
?>