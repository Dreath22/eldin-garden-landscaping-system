<?php
// Database connection
require_once __DIR__ . '/config/config.php';

try {
    // Get booking 53 details
    $stmt = $pdo->prepare("SELECT id, amount_paid, refund_amount, status, created_at FROM bookings WHERE id = 53");
    $stmt->execute();
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($booking) {
        echo "Booking ID 53 Details:\n";
        echo "ID: " . $booking['id'] . "\n";
        echo "Amount Paid: " . $booking['amount_paid'] . "\n";
        echo "Refund Amount: " . $booking['refund_amount'] . "\n";
        echo "Status: " . $booking['status'] . "\n";
        echo "Created At: " . $booking['created_at'] . "\n";
        
        // Calculate expected refund
        $expected_refund = $booking['amount_paid'] / 2;
        echo "\nExpected Refund (50%): " . $expected_refund . "\n";
        echo "Actual Refund: " . $booking['refund_amount'] . "\n";
        echo "Match: " . ($expected_refund == $booking['refund_amount'] ? "YES" : "NO") . "\n";
    } else {
        echo "Booking 53 not found\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
