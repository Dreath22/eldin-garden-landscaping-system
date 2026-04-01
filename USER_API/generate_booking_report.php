<?php
// Start session for user authentication
session_start();

// Include FPDF library (more commonly available)
require_once __DIR__ . '/../config/config.php';

// Try to include FPDF from common locations
$fpdf_paths = [
    __DIR__ . '/../vendor/fpdf/fpdf.php',
    __DIR__ . '/../fpdf/fpdf.php',
    __DIR__ . '/fpdf/fpdf.php',
    '/usr/share/php/fpdf/fpdf.php'
];

$fpdf_loaded = false;
$loaded_path = '';
foreach ($fpdf_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $fpdf_loaded = true;
        $loaded_path = $path;
        break;
    }
}

// Additional check: Verify FPDF class exists
if ($fpdf_loaded && !class_exists('FPDF')) {
    $fpdf_loaded = false;
}

if (!$fpdf_loaded) {
    // Fallback: Generate HTML report instead
    header("Content-Type: text/html");
    header("Content-Disposition: attachment; filename=\"booking_report_" . ($_GET['id'] ?? 'unknown') . ".html\"");
    
    // Get booking data for HTML report
    $booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($booking_id > 0) {
        try {
            $stmt = $pdo->prepare("
                SELECT b.*, u.name, u.email, u.phone_number, s.service_name, s.category
                FROM bookings b
                LEFT JOIN users u ON b.user_id = u.id
                LEFT JOIN services s ON b.service_id = s.id
                WHERE b.id = ?
            ");
            $stmt->execute([$booking_id]);
            $booking = $stmt->fetch();
            
            $stmt = $pdo->prepare("SELECT * FROM transactions WHERE booking_id = ? ORDER BY transaction_date DESC");
            $stmt->execute([$booking_id]);
            $transactions = $stmt->fetchAll();
            
            $stmt = $pdo->prepare("SELECT * FROM booking_files WHERE booking_id = ? ORDER BY uploaded_at ASC");
            $stmt->execute([$booking_id]);
            $files = $stmt->fetchAll();
        } catch (Exception $e) {
            // Set empty arrays if database fails
            $booking = ['booking_code' => 'Unknown'];
            $transactions = [];
            $files = [];
        }
    } else {
        $booking = ['booking_code' => 'Unknown'];
        $transactions = [];
        $files = [];
    }
    
    generateHTMLReport();
    exit;
}

// Set PDF headers only after confirming FPDF is available
header("Content-Type: application/pdf");
header("Content-Disposition: attachment; filename=\"booking_report_" . ($_GET['id'] ?? 'unknown') . ".pdf\"");

// Get booking ID
$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($booking_id === 0) {
    die("Invalid booking ID");
}

try {
    
    // Get booking details
    $stmt = $pdo->prepare("
        SELECT b.*, u.name, u.email, u.phone_number, s.service_name, s.category
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN services s ON b.service_id = s.id
        WHERE b.id = ?
    ");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        die("Booking not found");
    }
    
    // Get transactions
    $stmt = $pdo->prepare("
        SELECT * FROM transactions 
        WHERE booking_id = ? 
        ORDER BY transaction_date DESC
    ");
    $stmt->execute([$booking_id]);
    $transactions = $stmt->fetchAll();
    
    // Get booking files
    $stmt = $pdo->prepare("
        SELECT * FROM booking_files 
        WHERE booking_id = ? 
        ORDER BY uploaded_at ASC
    ");
    $stmt->execute([$booking_id]);
    $files = $stmt->fetchAll();
    
    // Create PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('Helvetica', '', 20);
    $pdf->Cell(0, 15, 'Booking Report', 0, 1, 'C');
    
    $pdf->SetFont('Helvetica', '', 16);
    $pdf->Cell(0, 10, 'Booking Code: ' . $booking['booking_code'], 0, 1, 'C');
    $pdf->Ln(10);
    
    // Booking Details Section
    $pdf->SetFont('Helvetica', '', 14);
    $pdf->Cell(0, 10, 'Booking Details', 0, 1, 'L');
    $pdf->SetFont('Helvetica', '', 11);
    
    $pdf->Cell(40, 7, 'Customer:', 0, 0);
    $pdf->Cell(0, 7, $booking['name'], 0, 1);
    
    $pdf->Cell(40, 7, 'Email:', 0, 0);
    $pdf->Cell(0, 7, $booking['email'], 0, 1);
    
    $pdf->Cell(40, 7, 'Phone:', 0, 0);
    $pdf->Cell(0, 7, $booking['phone_number'] ?? 'N/A', 0, 1);
    
    $pdf->Cell(40, 7, 'Service:', 0, 0);
    $pdf->Cell(0, 7, $booking['service_name'], 0, 1);
    
    $pdf->Cell(40, 7, 'Address:', 0, 0);
    $pdf->MultiCell(0, 7, $booking['address']);
    
    $pdf->Cell(40, 7, 'Appointment:', 0, 0);
    $pdf->Cell(0, 7, date('F j, Y g:i A', strtotime($booking['appointment_date'])), 0, 1);
    
    $pdf->Cell(40, 7, 'Status:', 0, 0);
    $pdf->Cell(0, 7, $booking['status'], 0, 1);
    
    $pdf->Ln(10);
    
    // Transactions Section
    $pdf->SetFont('Helvetica', '', 14);
    $pdf->Cell(0, 10, 'Transaction History', 0, 1, 'L');
    
    if (!empty($transactions)) {
        // Table header
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(30, 7, 'Date', 1, 0, 'C');
        $pdf->Cell(35, 7, 'Code', 1, 0, 'C');
        $pdf->Cell(60, 7, 'Description', 1, 0, 'C');
        $pdf->Cell(25, 7, 'Type', 1, 0, 'C');
        $pdf->Cell(20, 7, 'Status', 1, 0, 'C');
        $pdf->Cell(20, 7, 'Amount', 1, 1, 'C');
        
        // Table data
        $pdf->SetFont('Helvetica', '', 9);
        foreach ($transactions as $transaction) {
            $pdf->Cell(30, 6, date('M j, Y', strtotime($transaction['transaction_date'])), 1, 0, 'C');
            $pdf->Cell(35, 6, substr($transaction['transaction_code'], 0, 12), 1, 0, 'C');
            $pdf->Cell(60, 6, substr($transaction['description'], 0, 30), 1, 0, 'L');
            $pdf->Cell(25, 6, $transaction['type'], 1, 0, 'C');
            $pdf->Cell(20, 6, $transaction['status'], 1, 0, 'C');
            $pdf->Cell(20, 6, '$' . number_format($transaction['amount'], 2), 1, 1, 'R');
        }
    } else {
        $pdf->SetFont('Helvetica', '', 11);
        $pdf->Cell(0, 7, 'No transactions found.', 0, 1, 'L');
    }
    
    $pdf->Ln(10);
    
    // Files Section with Images and Document Previews
    $pdf->SetFont('Helvetica', '', 14);
    $pdf->Cell(0, 10, 'Uploaded Files', 0, 1, 'L');
    
    if (!empty($files)) {
        foreach ($files as $file) {
            $pdf->SetFont('Helvetica', '', 11);
            $pdf->Cell(0, 7, ucfirst($file['file_label']) . ': ' . $file['file_name'], 0, 1, 'L');
            $pdf->Cell(0, 5, '  Uploaded: ' . date('F j, Y g:i A', strtotime($file['uploaded_at'])), 0, 1, 'L');
            
            // Check if file exists
            $filePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $file['file_path'];
            if (file_exists($filePath)) {
                $mimeType = mime_content_type($filePath);
                $fileSize = filesize($filePath);
                $pdf->Cell(0, 5, '  File Size: ' . number_format($fileSize / 1024, 2) . ' KB', 0, 1, 'L');
                $pdf->Cell(0, 5, '  File Type: ' . $mimeType, 0, 1, 'L');
                
                // Handle image files
                if (strpos($mimeType, 'image/') === 0) {
                    try {
                        // Get image dimensions
                        list($width, $height) = getimagesize($filePath);
                        
                        // Calculate scaled dimensions to fit within PDF page
                        $maxWidth = 160; // Max width in mm
                        $maxHeight = 100; // Max height in mm
                        
                        $aspectRatio = $width / $height;
                        if ($width > $height) {
                            $displayWidth = min($maxWidth, $width * 0.264583); // Convert pixels to mm roughly
                            $displayHeight = $displayWidth / $aspectRatio;
                        } else {
                            $displayHeight = min($maxHeight, $height * 0.264583);
                            $displayWidth = $displayHeight * $aspectRatio;
                        }
                        
                        // Add image to PDF
                        $pdf->Image($filePath, 20, $pdf->GetY() + 5, $displayWidth, $displayHeight, '', '', '', false, 300, '', false, false, 0, true);
                        $pdf->Ln($displayHeight + 10);
                        
                    } catch (Exception $e) {
                        $pdf->Cell(0, 5, '  (Image could not be embedded: ' . $e->getMessage() . ')', 0, 1, 'L');
                        $pdf->Ln(5);
                    }
                }
                // Handle PDF files - extract and embed first page as image
                else if ($mimeType === 'application/pdf') {
                    try {
                        // Try to convert PDF first page to image using Imagick or GD
                        $firstPageImage = extractFirstPageFromPDF($filePath);
                        if ($firstPageImage) {
                            // Save temp image file
                            $tempImagePath = tempnam(sys_get_temp_dir(), 'pdf_preview_') . '.png';
                            file_put_contents($tempImagePath, $firstPageImage);
                            
                            // Add preview image to PDF
                            $pdf->Cell(0, 5, '  PDF Preview (First Page):', 0, 1, 'L');
                            $pdf->Image($tempImagePath, 20, $pdf->GetY() + 5, 140, 90, 'PNG', '', '', false, 150, '', false, false, 0, true);
                            $pdf->Ln(95);
                            
                            // Clean up temp file
                            unlink($tempImagePath);
                        } else {
                            $pdf->Cell(0, 5, '  (PDF preview could not be generated)', 0, 1, 'L');
                        }
                    } catch (Exception $e) {
                        $pdf->Cell(0, 5, '  (PDF processing error: ' . $e->getMessage() . ')', 0, 1, 'L');
                    }
                    $pdf->Ln(5);
                }
                // Handle text files - embed content directly
                else if (strpos($mimeType, 'text/') === 0 || in_array($mimeType, ['application/json', 'application/xml', 'text/csv'])) {
                    try {
                        $content = file_get_contents($filePath);
                        if ($content !== false) {
                            $pdf->Cell(0, 5, '  File Content Preview:', 0, 1, 'L');
                            $pdf->SetFont('Courier', '', 8);
                            
                            // Split content into lines and show first 20 lines
                            $lines = explode("\n", $content);
                            $maxLines = min(20, count($lines));
                            
                            for ($i = 0; $i < $maxLines; $i++) {
                                $line = substr($lines[$i], 0, 80); // Limit line length
                                if (strlen($lines[$i]) > 80) {
                                    $line .= '...';
                                }
                                $pdf->Cell(0, 3, '  ' . $line, 0, 1, 'L');
                            }
                            
                            if (count($lines) > 20) {
                                $pdf->Cell(0, 3, '  ... (' . (count($lines) - 20) . ' more lines)', 0, 1, 'L');
                            }
                            
                            $pdf->SetFont('Helvetica', '', 11);
                        }
                    } catch (Exception $e) {
                        $pdf->Cell(0, 5, '  (Could not read file content: ' . $e->getMessage() . ')', 0, 1, 'L');
                    }
                    $pdf->Ln(5);
                }
                // Handle Office documents - try to extract text or show info
                else if (in_array($mimeType, ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                           'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                           'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'])) {
                    try {
                        $pdf->Cell(0, 5, '  Document Type: ' . getDocumentTypeDescription($mimeType), 0, 1, 'L');
                        
                        // Try to extract text content (basic implementation)
                        $extractedText = extractTextFromDocument($filePath, $mimeType);
                        if ($extractedText) {
                            $pdf->Cell(0, 5, '  Content Preview:', 0, 1, 'L');
                            $pdf->SetFont('Courier', '', 8);
                            
                            $lines = explode("\n", $extractedText);
                            $maxLines = min(15, count($lines));
                            
                            for ($i = 0; $i < $maxLines; $i++) {
                                $line = substr($lines[$i], 0, 80);
                                if (strlen($lines[$i]) > 80) {
                                    $line .= '...';
                                }
                                $pdf->Cell(0, 3, '  ' . $line, 0, 1, 'L');
                            }
                            
                            $pdf->SetFont('Helvetica', '', 11);
                        } else {
                            $pdf->Cell(0, 5, '  (Document content could not be extracted)', 0, 1, 'L');
                        }
                    } catch (Exception $e) {
                        $pdf->Cell(0, 5, '  (Document processing error: ' . $e->getMessage() . ')', 0, 1, 'L');
                    }
                    $pdf->Ln(5);
                }
                // For other file types, just show info
                else {
                    $pdf->Cell(0, 5, '  (File type not supported for preview)', 0, 1, 'L');
                    $pdf->Ln(5);
                }
            } else {
                $pdf->Cell(0, 5, '  (File not found on server)', 0, 1, 'L');
                $pdf->Ln(5);
            }
            
            $pdf->Ln(3);
        }
    } else {
        $pdf->SetFont('Helvetica', '', 11);
        $pdf->Cell(0, 7, 'No files uploaded.', 0, 1, 'L');
    }
    
    // Notes Section
    if (!empty($booking['notes'])) {
        $pdf->Ln(10);
        $pdf->SetFont('Helvetica', '', 14);
        $pdf->Cell(0, 10, 'Notes', 0, 1, 'L');
        $pdf->SetFont('Helvetica', '', 11);
        $pdf->MultiCell(0, 7, $booking['notes']);
    }
    
    // Footer
    $pdf->Ln(20);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(0, 5, 'Generated on: ' . date('F j, Y g:i A'), 0, 1, 'C');
    $pdf->Cell(0, 5, 'Eldin Garden Landscaping System', 0, 1, 'C');
    
    // Output PDF
    $pdf->Output('booking_report_' . $booking['booking_code'] . '.pdf', 'D');
    
} catch (Exception $e) {
    // Log error for debugging
    error_log("PDF Generation Error: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());
    
    // Return error response instead of dying
    header("Content-Type: application/json");
    http_response_code(500);
    echo json_encode([
        "status" => "error", 
        "message" => "Error generating report: " . $e->getMessage()
    ]);
    exit;
}

// Helper function to extract first page from PDF as image
function extractFirstPageFromPDF($pdfPath) {
    // Try using ImageMagick first (most reliable)
    if (extension_loaded('imagick')) {
        try {
            $imagick = new Imagick();
            $imagick->setResolution(150, 150);
            $imagick->readImage($pdfPath . '[0]'); // Read first page only
            $imagick->setImageFormat('png');
            $imagick->scaleImage(800, 600, true);
            return $imagick->getImageBlob();
        } catch (Exception $e) {
            error_log("Imagick PDF processing failed: " . $e->getMessage());
        }
    }
    
    // Fallback: Try using Ghostscript command
    if (function_exists('exec')) {
        try {
            $tempFile = tempnam(sys_get_temp_dir(), 'pdf_preview_') . '.png';
            $command = "gs -dNOPAUSE -dBATCH -sDEVICE=png16m -dFirstPage=1 -dLastPage=1 -r150 -sOutputFile=\"$tempFile\" \"$pdfPath\" 2>&1";
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($tempFile)) {
                $imageData = file_get_contents($tempFile);
                unlink($tempFile);
                return $imageData;
            }
        } catch (Exception $e) {
            error_log("Ghostscript PDF processing failed: " . $e->getMessage());
        }
    }
    
    return null;
}

// Helper function to get document type description
function getDocumentTypeDescription($mimeType) {
    $descriptions = [
        'application/msword' => 'Microsoft Word 97-2003 Document',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Microsoft Word Document',
        'application/vnd.ms-excel' => 'Microsoft Excel 97-2003 Spreadsheet',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'Microsoft Excel Spreadsheet',
        'application/vnd.ms-powerpoint' => 'Microsoft PowerPoint 97-2003 Presentation',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'Microsoft PowerPoint Presentation'
    ];
    
    return $descriptions[$mimeType] ?? 'Unknown Document Type';
}

// Helper function to extract text from documents
function extractTextFromDocument($filePath, $mimeType) {
    // For text-based files, read directly
    if (strpos($mimeType, 'text/') === 0) {
        return file_get_contents($filePath);
    }
    
    // For CSV files, read as text
    if ($mimeType === 'text/csv') {
        return file_get_contents($filePath);
    }
    
    // Try using PHPWord for Word documents
    if (in_array($mimeType, ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])) {
        // Check if PHPWord is available
        if (class_exists('PhpOffice\\PhpWord\\IOFactory')) {
            try {
                $phpWord = \PhpOffice\PhpWord\IOFactory::load($filePath);
                $text = '';
                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        if (method_exists($element, 'getText')) {
                            $text .= $element->getText() . "\n";
                        }
                    }
                }
                return $text;
            } catch (Exception $e) {
                error_log("PHPWord extraction failed: " . $e->getMessage());
            }
        }
        
        // Fallback for .docx files (ZIP-based)
        if ($mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            try {
                $zip = new ZipArchive();
                if ($zip->open($filePath) === TRUE) {
                    $content = $zip->getFromName('word/document.xml');
                    $zip->close();
                    
                    if ($content) {
                        // Simple XML parsing to extract text
                        $content = strip_tags($content);
                        $content = html_entity_decode($content);
                        return $content;
                    }
                }
            } catch (Exception $e) {
                error_log("DOCX extraction failed: " . $e->getMessage());
            }
        }
    }
    
    // For Excel files, try basic extraction
    if (in_array($mimeType, ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])) {
        if (class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
                $text = '';
                foreach ($spreadsheet->getAllSheets() as $sheet) {
                    $text .= "Sheet: " . $sheet->getTitle() . "\n";
                    foreach ($sheet->getRowIterator() as $row) {
                        $rowData = [];
                        foreach ($row->getCellIterator() as $cell) {
                            $rowData[] = $cell->getValue();
                        }
                        $text .= implode("\t", $rowData) . "\n";
                    }
                    $text .= "\n";
                }
                return $text;
            } catch (Exception $e) {
                error_log("PhpSpreadsheet extraction failed: " . $e->getMessage());
            }
        }
    }
    
    return null;
}

function generateHTMLReport() {
    global $booking, $transactions, $files;
    
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Booking Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; }
        .section { margin: 20px 0; }
        .section h2 { color: #333; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .file-item { margin: 10px 0; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; }
        .file-image { max-width: 300px; max-height: 200px; margin: 10px 0; border: 1px solid #ccc; }
        .file-info { font-size: 0.9em; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Booking Report</h1>
        <h2>Booking Code: ' . htmlspecialchars($booking['booking_code']) . '</h2>
        <p>PDF generation library not available. Showing HTML report with embedded files.</p>
    </div>
    
    <div class="section">
        <h2>Booking Details</h2>
        <p><strong>Customer:</strong> ' . htmlspecialchars($booking['name']) . '</p>
        <p><strong>Email:</strong> ' . htmlspecialchars($booking['email']) . '</p>
        <p><strong>Phone:</strong> ' . htmlspecialchars($booking['phone_number'] ?? 'N/A') . '</p>
        <p><strong>Service:</strong> ' . htmlspecialchars($booking['service_name']) . '</p>
        <p><strong>Address:</strong> ' . htmlspecialchars($booking['address']) . '</p>
        <p><strong>Appointment:</strong> ' . date('F j, Y g:i A', strtotime($booking['appointment_date'])) . '</p>
        <p><strong>Status:</strong> ' . htmlspecialchars($booking['status']) . '</p>
    </div>
    
    <div class="section">
        <h2>Transaction History</h2>';
        
        if (!empty($transactions)) {
            echo '<table>
                <tr>
                    <th>Date</th>
                    <th>Code</th>
                    <th>Description</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Amount</th>
                </tr>';
            foreach ($transactions as $transaction) {
                echo '<tr>
                    <td>' . date('M j, Y', strtotime($transaction['transaction_date'])) . '</td>
                    <td>' . htmlspecialchars(substr($transaction['transaction_code'], 0, 12)) . '</td>
                    <td>' . htmlspecialchars($transaction['description']) . '</td>
                    <td>' . htmlspecialchars($transaction['type']) . '</td>
                    <td>' . htmlspecialchars($transaction['status']) . '</td>
                    <td>$' . number_format($transaction['amount'], 2) . '</td>
                </tr>';
            }
            echo '</table>';
        } else {
            echo '<p>No transactions found.</p>';
        }
        
        echo '</div>
    
    <div class="section">
        <h2>Uploaded Files</h2>';
        
        if (!empty($files)) {
            foreach ($files as $file) {
                $filePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $file['file_path'];
                echo '<div class="file-item">
                    <h3>' . ucfirst($file['file_label']) . ': ' . htmlspecialchars($file['file_name']) . '</h3>
                    <p class="file-info">Uploaded: ' . date('F j, Y g:i A', strtotime($file['uploaded_at'])) . '</p>';
                    
                if (file_exists($filePath)) {
                    $mimeType = mime_content_type($filePath);
                    $fileSize = filesize($filePath);
                    echo '<p class="file-info">File Size: ' . number_format($fileSize / 1024, 2) . ' KB</p>
                    <p class="file-info">File Type: ' . htmlspecialchars($mimeType) . '</p>';
                    
                    // Embed image if it's an image file
                    if (strpos($mimeType, 'image/') === 0) {
                        $imageData = base64_encode(file_get_contents($filePath));
                        echo '<img src="data:' . $mimeType . ';base64,' . $imageData . '" class="file-image" alt="' . htmlspecialchars($file['file_name']) . '" />';
                    }
                    // Handle PDF files - show preview
                    else if ($mimeType === 'application/pdf') {
                        echo '<div class="pdf-preview">';
                        echo '<p><strong>PDF Document:</strong></p>';
                        
                        // Try to generate preview using available methods
                        $previewImage = extractFirstPageFromPDF($filePath);
                        if ($previewImage) {
                            $imageData = base64_encode($previewImage);
                            echo '<img src="data:image/png;base64,' . $imageData . '" class="file-image" alt="PDF Preview" />';
                        } else {
                            echo '<p class="file-info">PDF preview not available. <a href="../' . htmlspecialchars($file['file_path']) . '" target="_blank">Open PDF in new tab</a></p>';
                        }
                        echo '</div>';
                    }
                    // Handle text files - show content preview
                    else if (strpos($mimeType, 'text/') === 0 || in_array($mimeType, ['application/json', 'application/xml', 'text/csv'])) {
                        $content = file_get_contents($filePath);
                        if ($content !== false) {
                            echo '<div class="text-preview">';
                            echo '<p><strong>Content Preview:</strong></p>';
                            echo '<pre style="background: #f5f5f5; padding: 10px; border-radius: 4px; max-height: 300px; overflow-y: auto; font-size: 12px;">';
                            $lines = explode("\n", $content);
                            $maxLines = min(50, count($lines));
                            for ($i = 0; $i < $maxLines; $i++) {
                                echo htmlspecialchars(substr($lines[$i], 0, 120));
                                if (strlen($lines[$i]) > 120) {
                                    echo '...';
                                }
                                echo "\n";
                            }
                            if (count($lines) > 50) {
                                echo "\n... (" . (count($lines) - 50) . " more lines)";
                            }
                            echo '</pre>';
                            echo '</div>';
                        }
                    }
                    // Handle Office documents
                    else if (in_array($mimeType, ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                               'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                               'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'])) {
                        echo '<div class="office-doc">';
                        echo '<p><strong>Document Type:</strong> ' . getDocumentTypeDescription($mimeType) . '</p>';
                        
                        // Try to extract text content
                        $extractedText = extractTextFromDocument($filePath, $mimeType);
                        if ($extractedText) {
                            echo '<p><strong>Content Preview:</strong></p>';
                            echo '<pre style="background: #f5f5f5; padding: 10px; border-radius: 4px; max-height: 200px; overflow-y: auto; font-size: 12px;">';
                            $lines = explode("\n", $extractedText);
                            $maxLines = min(30, count($lines));
                            for ($i = 0; $i < $maxLines; $i++) {
                                echo htmlspecialchars(substr($lines[$i], 0, 100));
                                if (strlen($lines[$i]) > 100) {
                                    echo '...';
                                }
                                echo "\n";
                            }
                            if (count($lines) > 30) {
                                echo "\n... (" . (count($lines) - 30) . " more lines)";
                            }
                            echo '</pre>';
                        } else {
                            echo '<p class="file-info">Content preview not available. <a href="../' . htmlspecialchars($file['file_path']) . '" download>Download file</a></p>';
                        }
                        echo '</div>';
                    }
                    // For other file types
                    else {
                        echo '<p class="file-info">Preview not available for this file type. <a href="../' . htmlspecialchars($file['file_path']) . '" download>Download file</a></p>';
                    }
                } else {
                    echo '<p class="file-info" style="color: red;">File not found on server</p>';
                }
                
                echo '</div>';
            }
        } else {
            echo '<p>No files uploaded.</p>';
        }
        
        echo '</div>';
        
        if (!empty($booking['notes'])) {
            echo '<div class="section">
                <h2>Notes</h2>
                <p>' . nl2br(htmlspecialchars($booking['notes'])) . '</p>
            </div>';
        }
        
        echo '<div class="section" style="text-align: center; margin-top: 30px;">
            <p><em>Generated on: ' . date('F j, Y g:i A') . '</em></p>
            <p><em>Eldin Garden Landscaping System</em></p>
        </div>
</body>
</html>';
}
?>
