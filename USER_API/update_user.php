<?php
header("Content-Type: application/json");
require_once '../config/config.php';
date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(["status" => "error", "message" => "Method Not Allowed"]));
}

$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input['id'])) {
    http_response_code(400);
    exit(json_encode(["status" => "error", "message" => "Invalid data or missing User ID"]));
}

// --- SANITIZE ---
$id        = (int)$input['id'];
$firstName = trim(strip_tags($input['firstName']));
$lastName  = trim(strip_tags($input['lastName']));
$fullName  = $firstName . ' ' . $lastName;
$email     = filter_var($input['email'], FILTER_SANITIZE_EMAIL);
$phone_number     = trim(strip_tags($input['phone_number']));
$role      = strtolower($input['role']);
$status    = strtolower($input['status']);
$notes     = trim(strip_tags($input['notes']));

// --- VALIDATE WHITELIST ---
$allowedRoles = ['customer', 'staff', 'admin'];
$allowedStatus = ['active', 'pending', 'banned'];

if (!in_array($role, $allowedRoles) || !in_array($status, $allowedStatus)) {
    http_response_code(422);
    exit(json_encode(["status" => "error", "message" => "Invalid role or status selection"]));
}

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    $sql = "UPDATE users 
            SET name = :name, 
                email = :email, 
                role = :role, 
                status = :status,
                phone_number = :phone_number,
                notes = :notes
            WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name'   => $fullName,
        ':email'  => $email,
        ':role'   => ucfirst($role),
        ':status' => $status,
        ':id'     => $id
        ':phone_number' => $phone_number,
        ':notes' => $notes
    ]);

    if ($stmt->rowCount() === 0) {
        // No rows changed (either ID doesn't exist or data is identical)
        echo json_encode(["status" => "success", "message" => "No changes made."]);
    } else {
        echo json_encode(["status" => "success", "message" => "User updated successfully."]);
    }

} catch (\PDOException $e) {
    if ($e->getCode() == 23000) {
        http_response_code(409);
        echo json_encode(["status" => "error", "message" => "Email already in use by another user."]);
    } else {
        error_log($e->getMesuserDatasage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Internal server error."]);
    }
}?>