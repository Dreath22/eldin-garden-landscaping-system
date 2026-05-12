<?php
/**
 * Debug what the BookingsController is actually receiving
 */

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Capture any output before JSON
ob_start();

echo "Debug Info:\n";
echo "Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'Not set') . "\n";
echo "GET params: " . json_encode($_GET) . "\n";
echo "POST raw: " . file_get_contents('php://input') . "\n";
echo "POST parsed: " . json_encode($_POST) . "\n";

// Try to include the controller
try {
    require_once '../config/config.php';
    echo "Config loaded successfully\n";
    
    // Test the database connection
    global $pdo;
    if ($pdo) {
        echo "Database connection OK\n";
    } else {
        echo "Database connection FAILED\n";
    }
    
    // Test the action routing
    $action = $_GET['action'] ?? '';
    echo "Action: '$action'\n";
    
    if ($action === 'update_costs') {
        echo "Would route to updateConsultationCosts\n";
        
        // Parse JSON input
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if ($data === null) {
            echo "JSON parse failed: " . json_last_error_msg() . "\n";
        } else {
            echo "JSON data received: " . json_encode($data) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

$output = ob_get_clean();

// Return as JSON for debugging
header('Content-Type: application/json');
echo json_encode([
    'debug_output' => $output,
    'server_vars' => [
        'method' => $_SERVER['REQUEST_METHOD'],
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'Not set',
        'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 'Not set'
    ],
    'get_data' => $_GET,
    'post_data' => $_POST,
    'raw_input' => file_get_contents('php://input')
]);

?>
