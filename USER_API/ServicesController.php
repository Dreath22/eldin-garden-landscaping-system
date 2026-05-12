<?php
$action = $_GET['action'] ?? 'summary'; 
require_once __DIR__ . '/../config/config.php';
// 3. Delegate to the right file
switch($action) {
    case 'summary':
        include  __DIR__ . '/Services/Summary.php';
        break;
    case 'list':
        include __DIR__ . '/Services/List.php';
        break;
    case 'getServices':
        include __DIR__ . '/Services/get_services.php';
        break;
    case 'get_service_price':
        // Get service base price by ID
        $serviceId = $_GET['service_id'] ?? null;
        if (!$serviceId) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Service ID required']);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT id, service_name, base_price FROM services WHERE id = ?");
        $stmt->execute([$serviceId]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($service) {
            echo json_encode([
                'status' => 'success',
                'data' => $service
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Service not found']);
        }
        break;
    case 'create':
        // FIX: Changed REQUEST_REQUEST to REQUEST_METHOD
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            include __DIR__ . '/Services/Create.php';
        } else {
            http_response_code(405); // Method Not Allowed
            echo json_encode(['error' => 'POST request required for Services']);
        }
        break;

    case 'update':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            include __DIR__ . '/Services/Update.php';
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'POST request required for updates']);
        }
        break;

    case 'delete':
        // Often DELETE is done via POST or a specific ID in GET
        include __DIR__ . '/Services/Delete.php';
        break;

    default:
        header("HTTP/1.0 404 Not Found");
        // Optional: include 'Services/404-error.php';
        echo json_encode(['error' => 'Action not found']);
        break;
}
?>