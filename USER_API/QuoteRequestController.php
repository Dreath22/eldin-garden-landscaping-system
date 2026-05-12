<?php
// Quote Request Controller for Profile Quote and Bookings Integration
session_start();

header("Content-Type: application/json");
date_default_timezone_set('Asia/Manila');

require_once '../config/config.php';

// Router
$action = $_GET['action'] ?? '';

$controller = new QuoteRequestController($pdo);

switch ($action) {
    case 'create':
        $controller->create();
        break;
    case 'validate':
        $controller->validate();
        break;
    default:
        jsonError("Unknown action. Valid actions: create, validate.", 400);
}

class QuoteRequestController
{
    private $pdo;
    
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->pdo->exec("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");
    }
    
    /**
     * Emit a successful JSON response and exit.
     */
    function jsonResponse(array $data, int $code = 200): void
    {
        http_response_code($code);
        echo json_encode($data);
        exit;
    }
    
    /**
     * Emit an error JSON response and exit.
     */
    function jsonError(string $message, int $code = 400): void
    {
        http_response_code($code);
        echo json_encode(["status" => "error", "message" => $message]);
        exit;
    }
    
    /**
     * Validate quote request data
     */
    public function validate()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError("Method Not Allowed. Expected POST, got " . $_SERVER['REQUEST_METHOD'], 405);
        }
        
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (!$data || !is_array($data)) {
            $this->jsonError('Invalid input data.');
        }
        
        $errors = [];
        
        // Validate required fields
        $required_fields = ['name', 'phone', 'service_dropdown', 'sqm', 'address'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        
        // Validate SQM (minimum 15 as mentioned in plan)
        if (!empty($data['sqm'])) {
            $sqm = floatval($data['sqm']);
            if ($sqm < 15) {
                $errors[] = 'Service area must be at least 15 square meters';
            }
            if ($sqm > 10000) {
                $errors[] = 'Service area cannot exceed 10,000 square meters';
            }
        }
        
        // Validate phone number format
        if (!empty($data['phone'])) {
            $phone = preg_replace('/[^0-9+\s]/', '', $data['phone']);
            if (strlen($phone) < 10) {
                $errors[] = 'Phone number must be at least 10 digits';
            }
        }
        
        // Validate service exists
        if (!empty($data['service_dropdown'])) {
            $stmt = $this->pdo->prepare("SELECT id, service_name, base_price FROM services WHERE id = ? AND status = 'active'");
            $stmt->execute([(int)$data['service_dropdown']]);
            $service = $stmt->fetch();
            
            if (!$service) {
                $errors[] = 'Invalid service selected';
            }
        }
        
        if (!empty($errors)) {
            $this->jsonError('Validation errors: ' . implode(', ', $errors), 422);
        }
        
        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Validation passed',
            'service' => $service ?? null
        ]);
    }
    
    /**
     * Create quote request
     */
    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError("Method Not Allowed. Expected POST, got " . $_SERVER['REQUEST_METHOD'], 405);
        }
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            $this->jsonError("You must be logged in to request a quote.", 401);
        }
        
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (!$data || !is_array($data)) {
            $this->jsonError('Invalid input data.');
        }
        
        try {
            $this->pdo->beginTransaction();
            
            // Sanitize and capture inputs
            $userId = (int)$_SESSION['user_id'];
            $name = htmlspecialchars(trim($data['name'] ?? ''));
            $phone = htmlspecialchars(trim($data['phone'] ?? ''));
            $serviceId = (int)($data['service_dropdown'] ?? 0);
            $sqm = floatval($data['sqm'] ?? 0);
            $address = htmlspecialchars(trim($data['address'] ?? ''));
            $message = htmlspecialchars(trim($data['message'] ?? ''));
            
            // Validation
            $errors = [];
            
            if (empty($name)) $errors[] = 'Name is required';
            if (empty($phone)) $errors[] = 'Phone is required';
            if (empty($serviceId)) $errors[] = 'Service is required';
            if ($sqm < 15) $errors[] = 'Service area must be at least 15 square meters';
            if (empty($address)) $errors[] = 'Address is required';
            
            if (!empty($errors)) {
                throw new Exception('Validation errors: ' . implode(', ', $errors), 422);
            }
            
            // Fetch service details from database
            $serviceStmt = $this->pdo->prepare("SELECT id, service_name, base_price FROM services WHERE id = ? AND status = 'active'");
            $serviceStmt->execute([$serviceId]);
            $serviceData = $serviceStmt->fetch();
            
            if (!$serviceData) {
                throw new Exception('Invalid service selected', 422);
            }
            
            $serviceName = $serviceData['service_name'];
            $basePrice = floatval($serviceData['base_price']);
            
            // Calculate total cost with fallback
            $totalCost = $basePrice * $sqm;
            if ($totalCost <= 0 || !is_finite($totalCost)) {
                throw new Exception('Invalid cost calculation', 422);
            }
            
            // Generate unique booking code
            $bookingCode = 'Q' . date('Y') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            
            // Insert quote request as a consultation booking
            $stmt = $this->pdo->prepare("
                INSERT INTO bookings (
                    booking_code, user_id, service_id, appointment_date, address, 
                    status, notes, sqm, total_cost, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $appointmentDate = null; // No appointment date for quote/consultation requests
            
            $result = $stmt->execute([
                $bookingCode,
                $userId,
                $serviceId,
                $appointmentDate,
                $address,
                'Consultation',
                $message,
                $sqm,
                $totalCost
            ]);
            
            if (!$result) {
                throw new Exception('Failed to create quote request');
            }
            
            $bookingId = $this->pdo->lastInsertId();
            
            // Generate unique transaction code
            $transactionCode = 'QTX' . date('Y') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            
            // Create transaction record for quote request
            $transactionStmt = $this->pdo->prepare("
                INSERT INTO transactions (
                    transaction_code, booking_id, description, type, status, amount, notes, transaction_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $transactionDescription = "Consultation request for {$serviceName} - {$sqm}sqm at $" . number_format($basePrice, 2) . "/sqm";
            
            $transactionResult = $transactionStmt->execute([
                $transactionCode,
                $bookingId,
                $transactionDescription,
                'Consultations',
                'Quote',
                0, // Zero amount for consultation stage
                "Client message: " . $message
            ]);
            
            if (!$transactionResult) {
                throw new Exception('Failed to create transaction record');
            }
            
            $this->pdo->commit();
            
            $this->jsonResponse([
                'status' => 'success',
                'message' => 'Your consultation request has been sent successfully! We will contact you soon.',
                'booking_code' => $bookingCode,
                'transaction_code' => $transactionCode,
                'booking_id' => $bookingId,
                'estimated_cost' => number_format($totalCost, 2),
                'service_name' => $serviceName,
                'sqm' => $sqm,
                'base_price_per_sqm' => number_format($basePrice, 2)
            ]);
            
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            
            $code = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
            error_log("Quote Request Error: " . $e->getMessage());
            $this->jsonError($e->getMessage(), $code);
        }
    }
}

// Helper function for backward compatibility
function jsonError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(["status" => "error", "message" => $message]);
    exit;
}
?>
