<?php
// Temporary debug line
file_put_contents('debug.log', print_r($_GET, true));
$action = $_GET['action'] ?? 'summary'; 
require_once __DIR__ . '/../config/config.php';
// 3. Delegate to the right file
switch($action) {
    case 'summary':
        include  __DIR__ . '/Uploads/Summary.php';
        break;
    case 'list':
        include __DIR__ . '/Uploads/List.php';
        break;
    case 'create':
        // FIX: Changed REQUEST_REQUEST to REQUEST_METHOD
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            include __DIR__ . '/Uploads/Create.php';
        } else {
            http_response_code(405); // Method Not Allowed
            echo json_encode(['error' => 'POST request required for uploads']);
        }
        break;

    case 'update':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            include __DIR__ . '/Uploads/Update.php';
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'POST request required for updates']);
        }
        break;

    case 'delete':
        // Often DELETE is done via POST or a specific ID in GET
        include __DIR__ . '/Uploads/Delete.php';
        break;

    default:
        header("HTTP/1.0 404 Not Found");
        // Optional: include 'Uploads/404-error.php';
        echo json_encode(['error' => 'Action not found']);
        break;
}
?>