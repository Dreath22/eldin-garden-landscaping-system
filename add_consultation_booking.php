<?php
/**
 * Add a consultation booking to the database
 */

require_once __DIR__ . '/config/config.php';

try {
    // Consultation booking data
    $consultationData = [
        'user_id' => 3,
        'service_id' => 102,
        'booking_code' => 'BK-' . strtoupper(bin2hex(random_bytes(4))),
        'address' => '456 Garden Avenue, Landscaping District',
        'status' => 'Consultation',
        'notes' => 'Client requested consultation for garden redesign and landscape planning',
        'sqm' => 750,
        'appointment_date' => date('Y-m-d H:i:s', strtotime('+3 days')),
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Insert the consultation booking
    $sql = "INSERT INTO bookings (user_id, service_id, booking_code, address, status, notes, sqm, appointment_date, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $consultationData['user_id'],
        $consultationData['service_id'],
        $consultationData['booking_code'],
        $consultationData['address'],
        $consultationData['status'],
        $consultationData['notes'],
        $consultationData['sqm'],
        $consultationData['appointment_date'],
        $consultationData['created_at']
    ]);
    
    if ($result) {
        $bookingId = $pdo->lastInsertId();
        
        echo "✅ Consultation booking added successfully!\n";
        echo "📋 Booking Details:\n";
        echo "   ID: {$bookingId}\n";
        echo "   Booking Code: {$consultationData['booking_code']}\n";
        echo "   Status: {$consultationData['status']}\n";
        echo "   Address: {$consultationData['address']}\n";
        echo "   SQM: {$consultationData['sqm']}\n";
        echo "   Appointment: {$consultationData['appointment_date']}\n";
        echo "   Notes: {$consultationData['notes']}\n";
        
        // Verify the booking was inserted
        $verifySql = "SELECT * FROM bookings WHERE id = ?";
        $verifyStmt = $pdo->prepare($verifySql);
        $verifyStmt->execute([$bookingId]);
        $insertedBooking = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($insertedBooking) {
            echo "\n✅ Verification: Booking found in database with status '{$insertedBooking['status']}'\n";
        } else {
            echo "\n❌ Verification: Could not find the inserted booking\n";
        }
        
    } else {
        echo "❌ Failed to insert consultation booking\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
