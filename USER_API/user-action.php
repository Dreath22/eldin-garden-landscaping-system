<?php
header("Content-Type: application/json");

require_once '../config/config.php';

$action = isset($_GET['action']) ? $_GET['action'] : null;
$userId = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // GET USER BY ID
    if ($action === 'get' && $userId) {
        $stmt = $pdo->prepare("
            SELECT id, name, email, role, phone, joined_date, last_login, status, notes
            FROM users WHERE id = :id
        ");
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $userData = $stmt->fetch();

        if (!$userData) {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "User not found"]);
            exit;
        }

        echo json_encode([
            "status" => "success",
            "user" => [
                "id" => (int)$userData['id'],
                "name" => $userData['name'],
                "email" => $userData['email'],
                "phone" => $userData['phone'],
                "role" => $userData['role'],
                "joined_date" => $userData['joined_date'],
                "last_login" => $userData['last_login'],
                "status" => $userData['status'],
                "notes" => $userData['notes'],
                "avatar_url" => "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($userData['name'])
            ]
        ]);
    }
    // UPDATE USER
    elseif ($action === 'update' && $userId && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);

        $stmt = $pdo->prepare("
            UPDATE users 
            SET name = :name, email = :email, phone = :phone, role = :role, status = :status, notes = :notes
            WHERE id = :id
        ");

        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':name', $data['name'] ?? null);
        $stmt->bindValue(':email', $data['email'] ?? null);
        $stmt->bindValue(':phone', $data['phone'] ?? null);
        $stmt->bindValue(':role', $data['role'] ?? null);
        $stmt->bindValue(':status', $data['status'] ?? null);
        $stmt->bindValue(':notes', $data['notes'] ?? null);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "User updated successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to update user"]);
        }
    }
    // BAN USER
    elseif ($action === 'ban' && $userId && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);

        $stmt = $pdo->prepare("
            UPDATE users 
            SET status = 'banned', notes = CONCAT(notes, '\n[Banned: ', :reason, ']')
            WHERE id = :id
        ");

        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':reason', $data['reason'] ?? 'No reason provided');

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "User banned successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to ban user"]);
        }
    }
    // DELETE USER
    elseif ($action === 'delete' && $userId && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "User deleted successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to delete user"]);
        }
    }
    // ADD USER
    elseif ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);

        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, phone, role, status, notes, joined_date, password)
            VALUES (:name, :email, :phone, :role, :status, :notes, NOW(), :password)
        ");

        $stmt->bindValue(':name', $data['name'] ?? null);
        $stmt->bindValue(':email', $data['email'] ?? null);
        $stmt->bindValue(':phone', $data['phone'] ?? null);
        $stmt->bindValue(':role', $data['role'] ?? 'customer');
        $stmt->bindValue(':status', $data['status'] ?? 'pending');
        $stmt->bindValue(':notes', $data['notes'] ?? null);
        $stmt->bindValue(':password', password_hash($data['password'] ?? 'defaultpass', PASSWORD_DEFAULT));

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "User added successfully", "id" => $pdo->lastInsertId()]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to add user"]);
        }
    }
    else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid action or missing parameters"]);
    }

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

?>
