<?php
// Include your database connection (which provides $pdo)
require_once '../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Get JSON Input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input.']);
        exit;
    }

    // 2. Map and Sanitize Data
    $user_id      = filter_var($data['user_id'] ?? null, FILTER_SANITIZE_NUMBER_INT);
    $service_id   = filter_var($data['service_id'] ?? null, FILTER_SANITIZE_NUMBER_INT);
    $total_amount = filter_var($data['cost'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $address      = filter_var($data['address'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
    $notes        = filter_var($data['notes'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);

    // 3. Handle Appointment Date
    // If the frontend sends 'appointment_date' directly, use it. Otherwise, combine date/time.
    $appointment_date = $data['appointment_date'] ?? null;
    if (!$appointment_date && !empty($data['date']) && !empty($data['time'])) {
        $appointment_date = date('Y-m-d H:i:s', strtotime($data['date'] . ' ' . $data['time']));
    }

    // 4. Generate Booking Code
    $booking_code = 'BK-' . strtoupper(bin2hex(random_bytes(4)));

    // 5. Validation
    if (!$user_id || !$service_id || !$appointment_date || !$total_amount || !$address) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Missing required fields.',
            'received' => [ // Helpful for debugging
                'user' => $user_id,
                'service' => $service_id,
                'date' => $appointment_date,
                'cost' => $total_amount
            ]
        ]);
        exit;
    }

    try {
        $sql = "INSERT INTO bookings (booking_code, user_id, service_id, appointment_date, address, total_amount, notes, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')";

        $stmt = $pdo->prepare($sql);
        
        $result = $stmt->execute([
            $booking_code,
            $user_id,
            $service_id,
            $appointment_date,
            $address,
            $total_amount,
            $notes,
        ]);

        if ($result) {
            echo json_encode([
                'status' => 'success', 
                'message' => 'Booking created successfully!',
                'booking_code' => $booking_code
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
}
?>  