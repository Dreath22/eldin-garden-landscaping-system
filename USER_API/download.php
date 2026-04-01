<?php
// Secure file download endpoint
require_once '../config/config.php';

if (!isset($_GET['file'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'File parameter required']);
    exit;
}

$filePath = $_GET['file'];
$originalName = $_GET['name'] ?? basename($filePath);

// Security: Validate file path to prevent directory traversal
$filePath = str_replace('..', '', $filePath);
$filePath = ltrim($filePath, '/');

// Construct full path
$fullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $filePath;

// Security: Check if file exists and is within uploads directory
if (!file_exists($fullPath) || !is_file($fullPath)) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'File not found']);
    exit;
}

// Security: Ensure file is in uploads directory
$realPath = realpath($fullPath);
$uploadsPath = realpath($_SERVER['DOCUMENT_ROOT'] . '/uploads');
if (strpos($realPath, $uploadsPath) !== 0) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit;
}

// Get file info
$fileSize = filesize($fullPath);
$mimeType = mime_content_type($fullPath);

// Set headers for download
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . $fileSize);
header('Content-Disposition: attachment; filename="' . htmlspecialchars($originalName) . '"');
header('Cache-Control: private, no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Output file
readfile($fullPath);
exit;
?>
