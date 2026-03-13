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
    exit(json_encode(["status" => "error", "message" => "Missing User ID"]));
}

$id     = (int)$input['id'];
$reason = trim(strip_tags($input['reason'] ?? 'Other'));
$notes  = trim(strip_tags($input['notes'] ?? ''));

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // We update the status to 'banned' and log the reason/notes
    // Note: Ensure your table has 'ban_reason' and 'ban_notes' columns
    $sql = "UPDATE users 
            SET status = 'banned', 
                ban_reason = :reason, 
                ban_notes = :notes 
            WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':reason' => $reason,
        ':notes'  => $notes,
        ':id'     => $id
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["status" => "success", "message" => "User has been banned."]);
    } else {
        echo json_encode(["status" => "error", "message" => "User not found or already banned."]);
    }

} catch (\PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Internal server error."]);
}
?>