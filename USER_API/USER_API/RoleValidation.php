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

session_start();

/**
 * Checks if the logged-in user has a specific role.
 * 
 * @param PDO $pdo Your database connection
 * @param string $requiredRole The role to check for (e.g., 'admin')
 * @return bool
 */
function hasRole($pdo, $requiredRole) {
    // 1. Check if user is even logged in
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    try {
        // 2. Fetch the role from the database
        $sql = "SELECT role FROM users WHERE id = ? LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 3. Compare roles
        return ($user && $user['role'] === $requiredRole);

    } catch (PDOException $e) {
        error_log("Role check error: " . $e->getMessage());
        return false;
    }
}
?>