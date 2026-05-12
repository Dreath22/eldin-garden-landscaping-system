<?php
// 1. Force strict error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. Start buffer and session
ob_start();
session_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

if (!isset($pdo)) {
    die("Database connection error: PDO object not found.");
}

// 3. Fetch Data
$booking_id_raw = $_GET['id'] ?? 0;
$booking = null;

try {
    $stmt = $pdo->prepare("
        SELECT b.*, u.name, u.email, u.phone_number, s.service_name, s.base_price
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN services s ON b.service_id = s.id
        WHERE b.booking_code = ? OR b.id = ?
    ");
    $stmt->execute([$booking_id_raw, $booking_id_raw]);
    $booking = $stmt->fetch();

    if (!$booking) {
        die("Error: Booking not found in database.");
    }

    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE booking_id = ? ORDER BY transaction_date DESC");
    $stmt->execute([$booking['id']]);
    $transactions = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT id, parent_id, file_type, file_name as file_path, original_name 
        FROM files WHERE category = 'bookings' AND parent_id = ? 
    ");
    $stmt->execute([$booking['id']]);
    $files = $stmt->fetchAll();
    // var_dump($transactions);
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

// 4. Load FPDF
if (!class_exists('FPDF')) {
    $path = __DIR__ . '/../vendor/fpdf/fpdf.php'; 
    if (file_exists($path)) {
        require_once $path;
    } else {
        die("Critical Error: FPDF library not found.");
    }
}

// 5. PDF GENERATION
try {
    if (ob_get_length()) ob_end_clean();

    $pdf = new FPDF();
    $pdf->AddPage();
    
    // --- Header ---
    $pdf->SetFont('Arial', 'B', 20);
    $pdf->SetTextColor(33, 37, 41);
    $pdf->Cell(0, 15, 'OFFICIAL BOOKING REPORT', 0, 1, 'C');
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Code: ' . $booking['booking_code'], 0, 1, 'C');
    $pdf->Ln(5);

    // --- Customer & Appointment Section ---
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, '  Customer & Appointment Details', 0, 1, 'L', true);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Ln(2);

    // Helper to draw rows
    $details = [
        'Customer Name'    => $booking['name'],
        'Email Address'    => $booking['email'],
        'Phone Number'     => $booking['phone_number'] ?? 'N/A',
        'Service Type'     => $booking['service_name'],
        'Appointment Date' => date('F j, Y g:i A', strtotime($booking['appointment_date'])),
        'Current Status'   => strtoupper($booking['status'])
    ];

    foreach ($details as $label => $value) {
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(45, 8, $label . ':', 0, 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 8, (string)$value, 0, 1);
    }
    $pdf->Ln(10);

    // --- Transactions Table ---
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, '  Financial Transactions', 0, 1, 'L', true);
    $pdf->Ln(2);
    
    if (empty($transactions)) {
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(0, 10, 'No transactions found.', 0, 1);
    } else {
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(45, 8, 'Date', 1, 0, 'C');
        $pdf->Cell(100, 8, 'Description', 1, 0, 'C');
        $pdf->Cell(45, 8, 'Amount', 1, 1, 'C');
        
        $pdf->SetFont('Arial', '', 9);
        foreach ($transactions as $t) {
            $pdf->Cell(45, 8, date('Y-m-d H:i', strtotime($t['transaction_date'])), 1);
            $pdf->Cell(100, 8, ' ' . substr($t['description'], 0, 55), 1);
            $pdf->Cell(45, 8, '$' . number_format($t['amount'], 2), 1, 1, 'R');
        }
    }
    $pdf->Ln(10);

    // --- Files Section ---
    if (!empty($files)) {
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, '  Attached Images', 0, 1, 'L', true);
        $pdf->Ln(2);
        
        foreach ($files as $file) {
            $filePath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($file['file_path'], '/');
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            
            if (file_exists($filePath) && in_array($extension, ['jpg', 'jpeg', 'png'])) {
                if ($pdf->GetY() > 200) $pdf->AddPage();
                
                $pdf->SetFont('Arial', 'I', 9);
                $pdf->Cell(0, 7, 'File: ' . $file['original_name'], 0, 1);
                
                try {
                    // Render image at 50mm width
                    $pdf->Image($filePath, $pdf->GetX() + 5, $pdf->GetY(), 50);
                    $pdf->Ln(55);
                } catch (Exception $e) {
                    $pdf->Cell(0, 10, ' [Error loading image file]', 0, 1);
                }
            }
        }
    }

    // --- Final Output ---
    $pdf->Output('D', 'Report_' . $booking['booking_code'] . '.pdf');
    exit;

} catch (Exception $e) {
    die("PDF Generation Failed: " . $e->getMessage());
}