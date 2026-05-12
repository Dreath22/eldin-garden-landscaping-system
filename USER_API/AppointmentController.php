<?php
/**
 * AppointmentController Class
 * API endpoints for appointment availability management
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/AppointmentManager.php';

class AppointmentController {
    private $manager;
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->manager = new AppointmentManager($pdo);
    }
    
    /**
     * Main router for appointment requests
     */
    public function handleRequest() {
        $action = $_GET['action'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Add CORS headers
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        
        if ($method == 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        try {
            switch ($action) {
                case 'check_availability':
                    $this->checkAvailability();
                    break;
                    
                case 'get_available_dates':
                    $this->getAvailableDates();
                    break;
                    
                case 'get_unavailable_dates':
                    $this->getUnavailableDates();
                    break;
                    
                case 'add_appointment_availability':
                    $this->addAppointmentAvailability();
                    break;
                    
                case 'request_appointment':
                    $this->requestAppointment();
                    break;
                    
                case 'add_unavailable':
                    $this->addUnavailableDate();
                    break;
                    
                case 'update_unavailable':
                    $this->updateUnavailableDate();
                    break;
                    
                case 'remove_unavailable':
                    $this->removeUnavailableDate();
                    break;
                    
                case 'bulk_add_unavailable':
                    $this->bulkAddUnavailableDates();
                    break;
                    
                case 'get_business_hours':
                    $this->getBusinessHours();
                    break;
                    
                default:
                    $this->jsonError("Unknown action: $action", 400);
            }
        } catch (Exception $e) {
            $this->jsonError("Server error: " . $e->getMessage(), 500);
        }
    }
    
    /**
     * Check if a specific date is available
     * GET /api/appointment?action=check_availability&date=2026-05-15
     */
    private function checkAvailability() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonError("Method not allowed", 405);
        }
        
        $date = $_GET['date'] ?? '';
        if (empty($date)) {
            $this->jsonError("Date parameter is required", 400);
        }
        
        $result = $this->manager->checkAvailability($date);
        $this->jsonResponse($result);
    }
    
    /**
     * Get available dates in a range
     * GET /api/appointment?action=get_available_dates&start=2026-05-01&end=2026-05-31&user_id=123
     */
    private function getAvailableDates() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonError("Method not allowed", 405);
        }
        
        $startDate = $_GET['start'] ?? '';
        $endDate = $_GET['end'] ?? '';
        $userId = $_GET['user_id'] ?? '';
        
        if (empty($startDate) || empty($endDate) || empty($userId)) {
            $this->jsonError("Start date, end date, and user_id are required", 400);
        }
        
        $result = $this->manager->clientGetAvailableDates($startDate, $endDate, $userId);
        $this->jsonResponse($result);
    }
    
    /**
     * Get unavailable dates (public endpoint)
     * GET /api/appointment?action=get_unavailable_dates&start=2026-05-01&end=2026-05-31
     */
    private function getUnavailableDates() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonError("Method not allowed", 405);
        }
        
        $startDate = $_GET['start'] ?? null;
        $endDate = $_GET['end'] ?? null;
        
        $unavailableDates = $this->manager->getUnavailableDates($startDate, $endDate);
        
        $this->jsonResponse([
            'status' => 'success',
            'unavailable_dates' => $unavailableDates,
            'total_count' => count($unavailableDates)
        ]);
    }
    
    /**
     * Request an appointment (client endpoint)
     * POST /api/appointment?action=request_appointment
     * Body: {"booking_id": 123, "requested_date": "2026-05-15", "client_id": 456}
     */
    private function requestAppointment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError("Method not allowed", 405);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $bookingId = $input['booking_id'] ?? '';
        $requestedDate = $input['requested_date'] ?? '';
        $clientId = $input['client_id'] ?? '';
        
        if (empty($bookingId) || empty($requestedDate) || empty($clientId)) {
            $this->jsonError("booking_id, requested_date, and client_id are required", 400);
        }
        
        $result = $this->manager->clientRequestAppointment($bookingId, $requestedDate, $clientId);
        $this->jsonResponse($result);
    }
    
    /**
     * Add unavailable date (admin only)
     * POST /api/appointment?action=add_unavailable
     * Body: {"date": "2026-05-15", "reason": "Holiday", "admin_id": 1}
     */
    private function addUnavailableDate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError("Method not allowed", 405);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $date = $input['date'] ?? '';
        $reason = $input['reason'] ?? null;
        $adminId = $input['admin_id'] ?? '';
        
        if (empty($date) || empty($adminId)) {
            $this->jsonError("date and admin_id are required", 400);
        }
        
        $result = $this->manager->adminAddUnavailableDate($date, $reason, $adminId);
        $this->jsonResponse($result);
    }
    
    /**
     * Update unavailable date reason (admin only)
     * PUT /api/appointment?action=update_unavailable
     * Body: {"date": "2026-05-15", "reason": "Updated reason", "admin_id": 1}
     */
    private function updateUnavailableDate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            $this->jsonError("Method not allowed", 405);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $date = $input['date'] ?? '';
        $reason = $input['reason'] ?? '';
        $adminId = $input['admin_id'] ?? '';
        
        if (empty($date) || empty($reason) || empty($adminId)) {
            $this->jsonError("date, reason, and admin_id are required", 400);
        }
        
        $result = $this->manager->adminUpdateUnavailableDate($date, $reason, $adminId);
        $this->jsonResponse($result);
    }
    
    /**
     * Remove unavailable date (admin only)
     * DELETE /api/appointment?action=remove_unavailable
     * Body: {"date": "2026-05-15", "admin_id": 1}
     */
    private function removeUnavailableDate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->jsonError("Method not allowed", 405);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $date = $input['date'] ?? '';
        $adminId = $input['admin_id'] ?? '';
        
        if (empty($date) || empty($adminId)) {
            $this->jsonError("date and admin_id are required", 400);
        }
        
        $result = $this->manager->adminRemoveUnavailableDate($date, $adminId);
        $this->jsonResponse($result);
    }
    
    /**
     * Bulk add unavailable dates (admin only)
     * POST /api/appointment?action=bulk_add_unavailable
     * Body: {"dates": [{"date": "2026-12-25", "reason": "Christmas"}], "admin_id": 1}
     */
    private function bulkAddUnavailableDates() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError("Method not allowed", 405);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $dates = $input['dates'] ?? [];
        $adminId = $input['admin_id'] ?? '';
        
        if (empty($dates) || empty($adminId)) {
            $this->jsonError("dates array and admin_id are required", 400);
        }
        
        $result = $this->manager->availability->bulkAddUnavailableDates($dates, $adminId);
        $this->jsonResponse($result);
    }
    
    /**
     * Get business hours configuration
     * GET /api/appointment?action=get_business_hours
     */
    private function getBusinessHours() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonError("Method not allowed", 405);
        }
        
        $hours = $this->manager->getBusinessHours();
        
        $this->jsonResponse([
            'status' => 'success',
            'business_hours' => $hours
        ]);
    }
    
    /**
     * Add appointment availability record
     */
    public function addAppointmentAvailability() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError("Method Not Allowed. Expected POST, got " . $_SERVER['REQUEST_METHOD'], 405);
            return;
        }
        
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        $bookingId = $data['booking_id'] ?? 0;
        $appointmentDate = $data['appointment_date'] ?? '';
        $status = $data['status'] ?? 'booked';
        
        if (empty($bookingId) || empty($appointmentDate)) {
            $this->jsonError("Booking ID and appointment date are required", 400);
            return;
        }
        
        try {
            // Validate appointment date format
            $dateObj = DateTime::createFromFormat('Y-m-d H:i:s', $appointmentDate);
            if (!$dateObj) {
                $this->jsonError("Invalid appointment date format", 400);
                return;
            }
            
            // Check if booking exists
            $stmt = $this->pdo->prepare("SELECT id FROM bookings WHERE id = ?");
            $stmt->execute([$bookingId]);
            if (!$stmt->fetch()) {
                $this->jsonError("Booking not found", 404);
                return;
            }
            
            // Insert into appointment_availability table
            $sql = "INSERT INTO appointment_availability (booking_id, appointment_date, appointment_status, created_at) 
                    VALUES (?, ?, ?, NOW())";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$bookingId, $appointmentDate, $status]);
            
            $this->jsonResponse([
                'status' => 'success',
                'message' => 'Appointment availability added successfully',
                'data' => [
                    'booking_id' => $bookingId,
                    'appointment_date' => $appointmentDate,
                    'status' => $status
                ]
            ]);
            
        } catch (PDOException $e) {
            error_log("Database error in addAppointmentAvailability: " . $e->getMessage());
            $this->jsonError("Failed to add appointment availability", 500);
        }
    }
    
    /**
     * Send JSON response
     */
    private function jsonResponse($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Send JSON error response
     */
    private function jsonError($message, $code = 400) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => $message
        ]);
        exit;
    }
}

// Handle the request
$controller = new AppointmentController();
$controller->handleRequest();
?>
