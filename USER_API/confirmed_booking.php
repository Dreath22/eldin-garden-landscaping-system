<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");
require_once '../config/config.php';
date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(["status" => "error", "message" => "Method Not Allowed"]));
}

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['id'])) {
    http_response_code(400);
    exit(json_encode(["status" => "error", "message" => "Missing Booking ID"]));
}

$booking_id = (int)$input['id'];
$status = isset($input['status']) ? $input['status'] : 'active';

$validStatuses = ['pending', 'active', 'completed', 'cancelled'];
if ($status !== 'all' && !in_array($status, $validStatuses)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid status filter"]);
    exit;
}
try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Only update if current status allows it
    $sql = "UPDATE bookings 
            SET status = :status 
            WHERE id = :id 
            AND status NOT IN ('Cancelled', 'Completed')";//('Pending','Active','Completed','Cancelled')

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $booking_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "status" => "success", 
            "message" => "Booking #$booking_id has been successfully cancelled."
        ]);
    } else {
        // This triggers if ID doesn't exist OR if it's already Completed/Cancelled
        echo json_encode([
            "status" => "error", 
            "message" => "Action failed: Booking is either already finalized or ID is invalid."
        ]);
    }

} catch (\PDOException $e) {
    error_log("DB Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Server error occurred."]);
}
?>