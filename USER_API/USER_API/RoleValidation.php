<?php
require_once __DIR__ . '/../utils/sanitizeInput.php';
function RoleValidation($pdo, $id){
    //check if $id was int
    $id = sanitizeInput($id, 'int');
    if (!$id) {
        return false;
    }
    
    //check if $pdo was true
    if (!$pdo) {
        return false;
    }
    
    //check if user exists
    $stmt = $pdo->prepare("SELECT role, status FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!$user) {
        return false;
    }
    return $user;
}
?>