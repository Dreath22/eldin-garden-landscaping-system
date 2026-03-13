<?php
header("Content-Type: application/json");
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method Not Allowed"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid JSON input"]);
    exit;
}

// --- 1. INPUT SANITIZATION & NORMALIZATION ---
// strip_tags prevents basic HTML injection (XSS)
$firstName = trim(strip_tags($input['firstName'] ?? ''));
$lastName  = trim(strip_tags($input['lastName'] ?? ''));
$fullName  = trim($firstName . ' ' . $lastName);

// filter_var removes illegal characters from email
$email = filter_var(trim($input['email'] ?? ''), FILTER_SANITIZE_EMAIL);

$password = $input['temporaryPassword'] ?? '';

// --- 2. STRICT VALIDATION (Whitelisting) ---
$allowedRoles   = ['customer', 'editor', 'admin'];
$allowedStatuses = ['active', 'pending', 'inactive'];

$role   = in_array(strtolower($input['role'] ?? ''), $allowedRoles) ? strtolower($input['role']) : 'customer';
$status = in_array(strtolower($input['status'] ?? ''), $allowedStatuses) ? strtolower($input['status']) : 'active';

// Basic phone sanitization (keep digits, +, -, and spaces)
$phone_number = isset($input['phone_number']) ? preg_replace('/[^\d+ \-]/', '', $input['phone_number']) : null;

// --- 3. ERROR CHECKING ---
if (empty($firstName) || empty($lastName) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Valid name and email are required."]);
    exit;
}

if (strlen($password) < 8) { // Increased to 8 for better security
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Password must be at least 8 characters."]);
    exit;
}

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Hash the password using a strong, modern algorithm
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (name, email, role, status, password, phone_number, notes) 
            VALUES (:name, :email, :role, :status, :password, :phone_number, :notes)";
    
    $stmt = $pdo->prepare($sql);
    
    // Preparation of notes - avoid injecting user input directly here if possible
    $creationNote = "Added by admin on " . date("Y-m-d H:i:s");

    $stmt->execute([
        ':name'         => $fullName,
        ':email'        => $email,
        ':role'         => ucfirst($role), 
        ':status'       => $status,
        ':password'     => $hashedPassword,
        ':phone_number' => $phone_number,
        ':notes'        => $creationNote
    ]);

    echo json_encode([
        "status" => "success",
        "message" => "User " . htmlspecialchars($fullName) . " created successfully."
    ]);

} catch (\PDOException $e) {
    // 23000 is the ANSI SQL state for Integrity Constraint Violation (e.g. Unique key duplicate)
    if ($e->getCode() == 23000) {
        http_response_code(409);
        echo json_encode(["status" => "error", "message" => "That email is already registered."]);
    } else {
        error_log("DB Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "An internal error occurred."]);
    }
}