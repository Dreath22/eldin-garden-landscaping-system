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

try {
    $booking_id = (int)($input['id'] ?? 0);
    $reason = htmlspecialchars(strip_tags($_POST['notes'] ?? 'No reason provided'), ENT_QUOTES, 'UTF-8');
    
    // Generate a unique transaction code for your UNI column
    $transaction_code = 'TRX-' . strtoupper(bin2hex(random_bytes(4)));

    // 2. Start Database Transaction
    $pdo->beginTransaction();

    // UPDATE: Only change status, keep the notes column in 'bookings' clean
    $sql_booking = "UPDATE bookings 
                    SET status = 'Cancelled' 
                    WHERE id = :id 
                    AND status NOT IN ('Cancelled', 'Completed')";

    $stmt = $pdo->prepare($sql_booking);
    $stmt->execute([':id' => $booking_id]);

    // Check if the booking was actually available for cancellation
    if ($stmt->rowCount() > 0) {
        
        // INSERT: Log the cancellation details into your 'transactions' table
        $sql_log = "INSERT INTO transactions 
                    (transaction_code, booking_id, description, type, status, amount) 
                    VALUES (:code, :bid, :desc, 'Status Change', 'Completed', 0.00)";
        
        $logStmt = $pdo->prepare($sql_log);
        $logStmt->execute([
            ':code' => $transaction_code,
            ':bid'  => $booking_id,
            ':desc' => "CANCELLATION: " . $reason
        ]);

        // Commit both changes
        $pdo->commit();

        echo json_encode([
            "status" => "success", 
            "message" => "Booking #$booking_id cancelled and logged.",
            "transaction_code" => $transaction_code
        ]);
    } else {
        $pdo->rollBack(); // Nothing changed, so cancel the transaction
        echo json_encode([
            "status" => "error", 
            "message" => "Action failed: Booking is already finalized or ID is invalid."
        ]);
    }

} catch (\Exception $e) {
    // Rollback if ANY part of the process fails (SQL or logic)
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Cancellation Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Server error occurred."]);
}
?>