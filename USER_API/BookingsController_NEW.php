<?php
// Start session for user authentication
session_start();

// Safety Catch for POST Max Size Exceeded
if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
    !empty($_FILES) && 
    isset($_SERVER['CONTENT_LENGTH']) && 
    (int)$_SERVER['CONTENT_LENGTH'] > 8 * 1024 * 1024) {
    
    http_response_code(413);
    header('Content-Type: application/json');
    echo json_encode([
        "success" => false, 
        "message" => "POST size exceeded maximum allowed size. Please reduce file sizes and try again."
    ]);
    exit;
}

header("Content-Type: application/json");
date_default_timezone_set('Asia/Manila');

// Load configuration
require_once '../config/config.php';

// Load the new architecture
require_once __DIR__ . '/Bookings/BookingsController.php';

// ─── ROUTER ───────────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';

$controller = new BookingsController($pdo);

switch ($action) {
    case 'list':
        $controller->list($_GET);
        break;
    case 'create':
        // Get JSON data for create action
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $controller->create($data ?? []);
        break;
    case 'update':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $controller->update($id, $data ?? []);
        break;
    case 'delete':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $controller->delete($id);
        break;
    default:
        http_response_code(400);
        echo json_encode([
            "success" => false, 
            "message" => "Unknown action. Valid actions: list, create, update, delete."
        ], JSON_PRETTY_PRINT);
        break;
}
?>
