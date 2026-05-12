<?php
// Start session for user authentication
session_start();

// Safety Catch for POST Max Size Exceeded (only for actual file uploads, not JSON requests)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
    !empty($_FILES) && 
    isset($_SERVER['CONTENT_LENGTH']) && 
    (int)$_SERVER['CONTENT_LENGTH'] > 8 * 1024 * 1024) { // Only trigger if > 8MB
    
    http_response_code(413);
    header('Content-Type: application/json');
    echo json_encode([
        "status" => "error", 
        "message" => "POST size exceeded maximum allowed size. Please reduce file sizes and try again."
    ]);
    exit;
}

ob_start();

header("Content-Type: application/json");
date_default_timezone_set('Asia/Manila');

$data = json_decode(file_get_contents("php://input"), true);

// $pdo is already created by config.php (PDO::ERRMODE_EXCEPTION, FETCH_ASSOC, no emulated prepares)
require_once __DIR__ . '/../config/config.php';

// Check for any output before this point
$preOutput = ob_get_clean();
if (!empty($preOutput)) {
    // Log any unexpected output
    error_log("BookingsController pre-output: " . $preOutput);
    // Clean output buffer
    ob_clean();
}

// ─── ROUTER ───────────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';

$controller = new BookingsController($pdo);

switch ($action) {
    case 'list':
        $controller->list($_GET); // 2. Call as an instance method
        break;
    case 'list_by_user':
        $controller->listByUser($_GET); // User-specific booking list
        break;
    case 'create':
        // Get JSON data for create action
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $controller->create($data);
        break;
    case 'update':
        $controller->update($_GET);
        break;
    case 'cancel':
        $controller->cancel($_POST);
        break;
    case 'get':
        $controller->get($_GET);
        break;
    case 'update_costs':
        // Get JSON data for consultation cost update
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $controller->updateConsultationCosts($data);
        break;
    case 'update_estimate':
        // Get JSON data for estimate update
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $controller->updateBookingEstimate($data);
        break;
    case 'notif_Count':
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $controller->countNotif($data);
        break;
    case 'update_client_fields':
        // Get JSON data for client field updates
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $controller->updateClientFields($data);
        break;
    case 'update_notif_boolean':
        // Get JSON data for notifBoolean update
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $booking_id = $data['booking_id'] ?? null;
        $notif_boolean = $data['notif_boolean'] ?? null;
        
        if (!$booking_id || $notif_boolean === null) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'booking_id and notif_boolean are required']);
            break;
        }
        
        $success = $controller->updateNotifBoolean($booking_id, $notif_boolean);
        
        if ($success) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Notification status updated successfully',
                'data' => [
                    'booking_id' => $booking_id,
                    'notif_boolean' => $notif_boolean
                ]
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Failed to update notification status']);
        }
        break;
    case 'confirm_appointment':
        // Get JSON data for appointment confirmation
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $booking_id = $data['booking_id'] ?? null;
        $admin_id = $data['admin_id'] ?? 0;
        $result = $controller->confirmAppointment($booking_id, $admin_id);
        echo json_encode($result);
        break;
    case 'reschedule_appointment':
        // Get JSON data for appointment rescheduling
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $booking_id = $data['booking_id'] ?? null;
        $new_appointment_date = $data['new_appointment_date'] ?? null;
        $admin_id = $data['admin_id'] ?? 0;
        $reason = $data['reason'] ?? '';
        $result = $controller->rescheduleAppointment($booking_id, $new_appointment_date, $admin_id, $reason);
        echo json_encode($result);
        break;
    case 'update_admin_feedback':
        // Debug logging
        error_log("update_admin_feedback case hit");
        
        // Get JSON data for admin feedback update
        $json = file_get_contents('php://input');
        error_log("Raw JSON input: " . $json);
        
        $data = json_decode($json, true);
        error_log("Decoded data: " . json_encode($data));
        
        $controller->updateAdminFeedback($data);
        break;
    case 'update_consultation_status':
        // Get JSON data for consultation status update
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $controller->updateConsultationStatus($data);
        break;
    case 'update_appointment_date':
        // Get JSON data for appointment date update
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $controller->updateAppointmentDate($data);
        break;
    
    default:
        jsonError("Unknown action. Valid actions: list, create, confirm, cancel, get, update_costs, update_estimate, update_client_fields, update_admin_feedback, update_consultation_status, update_appointment_date.", 400);
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

class BookingsController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->pdo->exec("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");
    }
    
    public function countNotif($data)
    {

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError("Method Not Allowed. Expected POST, got " . $_SERVER['REQUEST_METHOD'], 405);
            return;
        }
        
        $userId = (int)$data['user_id'] ?? 17;
        
        try {
            $sql = "SELECT COUNT(*) AS notifCounts FROM bookings WHERE notifBoolean = 0 AND user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode([
                'status' => 'success',
                'message' => 'Results.',
                'data' => [
                    'bookingCount' => (int)$result['notifCounts'],
                ]
            ]);
        }catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update appointment date for a booking
     */
    public function updateAppointmentDate($data)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError("Method Not Allowed. Expected POST, got " . $_SERVER['REQUEST_METHOD'], 405);
            return;
        }
        
        $bookingId = $data['booking_id'] ?? 0;
        $appointmentDate = $data['appointment_date'] ?? '';
        
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
            
            // Check if date is in the past
            $now = new DateTime();
            if ($dateObj < $now) {
                $this->jsonError("Appointment date cannot be in the past", 400);
                return;
            }
            
            // Check if date is too far in future (more than 30 days)
            $maxDate = (clone $now)->add(new DateInterval('P30D'));
            if ($dateObj > $maxDate) {
                $this->jsonError("Appointment date cannot be more than 30 days in advance", 400);
                return;
            }
            
            $this->pdo->beginTransaction();
            
            // Update appointment date
            $sql = "UPDATE bookings SET appointment_date = :appointment_date WHERE id = :booking_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':appointment_date' => $appointmentDate,
                ':booking_id' => $bookingId
            ]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Booking not found or no changes made");
            }
            
            // Log the change in appointment availability table
            $this->logAppointmentChange(
                $bookingId,
                'Date Changed',
                'Date Changed',
                null,
                $appointmentDate,
                $_SESSION['user_id'] ?? 0,
                'Appointment date updated via calendar'
            );
            
            $this->pdo->commit();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Appointment date updated successfully',
                'data' => [
                    'booking_id' => $bookingId,
                    'appointment_date' => $appointmentDate
                ]
            ]);
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate estimate total based on service base price and square meters
     * @param int $serviceId Service ID
     * @param float $sqm Square meters
     * @return float Calculated estimate total
     */
    private function calculateEstimateTotal($serviceId, $sqm) {
        try {
            $stmt = $this->pdo->prepare("SELECT base_price FROM services WHERE id = ?");
            $stmt->execute([$serviceId]);
            $service = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$service || $sqm <= 0) {
                return 0.00;
            }
            
            return (float)$service['base_price'] * (float)$sqm;
        } catch (Exception $e) {
            error_log("Error calculating estimate total: " . $e->getMessage());
            return 0.00;
        }
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
    public function jsonError(string $message, int $code = 400): void
    {
        http_response_code($code);
        echo json_encode(["status" => "error", "message" => $message]);
        exit;
    }

    /**
     * Enhanced database error handler with context and logging
     */
    private function handleDatabaseError(PDOException $e, string $context): void
    {
        $errorId = uniqid('DB_ERROR_', true);
        $timestamp = date('Y-m-d H:i:s');
        
        // Log detailed error information
        error_log("[$errorId] [$timestamp] Database error in $context: " . $e->getMessage());
        error_log("[$errorId] Error details: " . json_encode([
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]));
        
        // Return user-friendly error
        $this->jsonError("Database operation failed. Please try again.", 500);
    }

    /**
     * Comprehensive input validation for booking operations
     */
    private function validateBookingParams(array $params): array
    {
        $errors = [];
        
        // Validate page parameter
        if (isset($params['page']) && (!is_numeric($params['page']) || $params['page'] < 1)) {
            $errors['page'] = 'Page must be a positive integer';
        }
        
        // Validate limit parameter
        if (isset($params['limit']) && (!is_numeric($params['limit']) || $params['limit'] < 1 || $params['limit'] > 100)) {
            $errors['limit'] = 'Limit must be between 1 and 100';
        }
        
        // Validate status parameter
        $validStatuses = ['all', 'consultation', 'pending', 'active', 'completed', 'cancelled'];
        if (isset($params['status']) && !in_array($params['status'], $validStatuses)) {
            $errors['status'] = 'Invalid status. Must be one of: ' . implode(', ', $validStatuses);
        }
        
        // Validate category parameter
        if (isset($params['category']) && (!is_numeric($params['category']) || $params['category'] < 0)) {
            $errors['category'] = 'Category must be a non-negative integer';
        }
        
        // Validate order parameter
        $validOrders = ['newest', 'oldest', 'updated_newest', 'updated_oldest'];
        if (isset($params['order']) && !in_array($params['order'], $validOrders)) {
            $errors['order'] = 'Invalid order. Must be one of: ' . implode(', ', $validOrders);
        }
        
        // Validate search parameter
        if (isset($params['search']) && strlen(trim($params['search'])) > 100) {
            $errors['search'] = 'Search term must be 100 characters or less';
        }
        
        return $errors;
    }

    // 1. Fetch List (Replaces bookings.php)
    public function list($params)
    {
        // 1. Validate inputs
        $validationErrors = $this->validateBookingParams($params);
        if (!empty($validationErrors)) {
            $this->jsonError("Validation failed: " . implode(', ', $validationErrors), 400);
            return;
        }
        
        // 2. Sanitize inputs
        $limit = min(100, max(1, isset($params['limit']) ? (int)$params['limit'] : 6));
        $page = min(1000, max(1, isset($params['page']) ? (int)$params['page'] : 1));
        $status = $params['status'] ?? 'all';
        $categoryFilter = $params['category'] ?? '0';
        $order = $params['order'] ?? 'newest';
        $fromDate = $params['from'] ?? null;
        $searchTerm = isset($params['search']) ? substr(trim($params['search']), 0, 100) : null;
        $consultationStatus = $params['consultation_status'] ?? 'all';
        $offset = ($page - 1) * $limit;

        // Validate Status
        $validStatuses = ['all', 'consultation' ,'pending', 'active', 'completed', 'cancelled'];
        if ($status !== 'all' && !in_array($status, $validStatuses)) {
            this->jsonError("Invalid status filter");
        }

        try {
            // 1. Get Global Stats
            $stmtSummary = $this->pdo->query("SELECT COUNT(*) as total_bookings, 
                SUM(status = 'Pending') as pending_count,
                SUM(status = 'Active') as active_count,
                SUM(status = 'Completed') as completed_count,
                SUM(status = 'Cancelled') as cancelled_count 
                FROM bookings");
            $summaryData = $stmtSummary->fetch(PDO::FETCH_ASSOC);

            // 2. Build Query Conditions with explicit table aliases
            $where = [];
            $nparams = [];

            // Status filtering
            if ($status !== 'all') {
                $where[] = "B.status = :status";
                $nparams[':status'] = $status;
            }

            // Notification status filtering
            if ($consultationStatus !== 'all') {
                if ($consultationStatus === 'consulted') {
                    $where[] = "B.notifBoolean = 1";
                } elseif ($consultationStatus === 'not_consulted') {
                    $where[] = "B.notifBoolean = 0";
                } elseif ($consultationStatus === 'unavailable') {
                    $where[] = "B.notifBoolean = 0";
                } elseif ($consultationStatus === 'available') {
                    $where[] = "B.notifBoolean = 0";
                }
            }

            // Filter by Category via joined Services table
            if ($categoryFilter !== '0' && $categoryFilter !== 'all') {
                $where[] = "S.id = :service_id";
                $nparams[':service_id'] = $categoryFilter;
            }

            // Filter by date (updated_at)
            if ($fromDate && !empty($fromDate)) {
                $where[] = "DATE(B.updated_at) >= :from_date";
                $nparams[':from_date'] = $fromDate;
            }

            // Search functionality
            if ($searchTerm && !empty($searchTerm)) {
                $where[] = "(B.booking_code LIKE :search OR B.address LIKE :search OR U.name LIKE :search OR U.email LIKE :search OR S.service_name LIKE :search)";
                $nparams[':search'] = '%' . $searchTerm . '%';
            }

            // Build WHERE clause
            $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

            // 3. Get TOTAL filtered count
            $countSql = "SELECT COUNT(*) FROM bookings AS B 
                     INNER JOIN services AS S ON B.service_id = S.id 
                     LEFT JOIN users AS U ON B.user_id = U.id
                     $whereSql";
            $countStmt = $this->pdo->prepare($countSql);

            foreach ($nparams as $key => $val) {
                $countStmt->bindValue($key, $val);
            }
            $countStmt->execute();
            $totalFilteredCount = (int)$countStmt->fetchColumn();

        // 4. Get Data with CTE for better performance
            $sortOptions = [
                "newest" => "B.appointment_date DESC", 
                "oldest" => "B.appointment_date ASC",
                "updated_newest" => "B.updated_at DESC", 
                "updated_oldest" => "B.updated_at ASC"
            ];
            $orderBy = $sortOptions[$order] ?? "B.appointment_date DESC";

            // Use CTE to pre-calculate transaction aggregates
            $sql = "WITH transaction_aggregates AS (
                SELECT 
                    T.booking_id,
                    SUM(CASE WHEN T.description LIKE '%Initial Payment%' THEN T.amount ELSE 0 END) AS initial_amount,
                    SUM(CASE WHEN T.description LIKE '%Final Payment%' THEN T.amount ELSE 0 END) AS final_amount,
                    SUM(CASE WHEN T.description LIKE '%Initial%' OR T.description LIKE '%Final%' THEN T.amount ELSE 0 END) AS total_booking_cost,
                    SUM(CASE WHEN T.status IN ('Active', 'Completed') AND (T.description LIKE '%Initial%' OR T.description LIKE '%Final%') THEN T.amount ELSE 0 END) AS amount_paid
                FROM transactions T
                WHERE T.type IN ('Status Change', 'Payment')
                AND (T.description LIKE '%Initial%' OR T.description LIKE '%Final%')
                GROUP BY T.booking_id
            )
            SELECT 
                B.id AS booking_id, B.booking_code, B.user_id, B.service_id,
                B.appointment_date, B.address, B.status,
                B.created_at, B.updated_at, B.notes, B.admin_feedback, B.sqm,
                B.notifBoolean,
                COALESCE(B.total_cost, 0.00) AS total_cost,
                COALESCE(B.base_price, 0.00) AS base_price,
                COALESCE(B.labor_cost, 0.00) AS labor_cost,
                COALESCE(B.materials_cost, 0.00) AS materials_cost,
                COALESCE(B.misc_cost, 0.00) AS misc_cost,
                COALESCE(B.refund_amount, 0.00) AS refund_amount,
                S.service_name AS service_name,
                COALESCE(TA.initial_amount, 0.00) AS initial_amount,
                COALESCE(TA.final_amount, 0.00) AS final_amount,
                COALESCE(TA.total_booking_cost, 0.00) AS total_booking_cost,
                COALESCE(TA.amount_paid, 0.00) AS amount_paid,
                CASE 
                    WHEN B.status = 'Cancelled' THEN 
                        (SELECT CASE 
                            WHEN description LIKE '%Pending to Cancelled%' THEN 'Pending'
                            WHEN description LIKE '%Active to Cancelled%' THEN 'Active'
                            ELSE NULL 
                         END
                         FROM transactions 
                         WHERE booking_id = B.id AND status = 'Cancelled' 
                         AND type = 'Status Change' 
                         ORDER BY id DESC LIMIT 1)
                    ELSE NULL 
                END AS previous_status,
                CASE 
                    WHEN B.status = 'Cancelled' THEN 
                        (SELECT amount FROM transactions 
                         WHERE booking_id = B.id AND status = 'Active' 
                         AND type = 'Status Change' AND description LIKE '%Initial Payment%'
                         ORDER BY id DESC LIMIT 1)
                    ELSE NULL 
                END AS cancelled_initial_amount,
                S.service_name, S.service_name,
                U.name, U.email, U.phone_number
                FROM bookings AS B
                INNER JOIN services AS S ON B.service_id = S.id
                LEFT JOIN users AS U ON B.user_id = U.id
                LEFT JOIN transaction_aggregates TA ON B.id = TA.booking_id
                $whereSql 
                ORDER BY $orderBy
            LIMIT :limit OFFSET :offset";

            $stmt = $this->pdo->prepare($sql);
            foreach ($nparams as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();

            $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get files for all bookings in a single query for efficiency
            $booking_ids = array_column($bookings, 'booking_id');
            $files_data = [];
            
            if (!empty($booking_ids)) {
                $placeholders = str_repeat('?,', count($booking_ids) - 1) . '?';
                $files_sql = "SELECT parent_id as booking_id, file_type, file_name as file_path, original_name, created_at as uploaded_at
                             FROM files 
                             WHERE category = 'bookings' AND parent_id IN ($placeholders)
                             ORDER BY parent_id, created_at ASC";
                $files_stmt = $this->pdo->prepare($files_sql);
                $files_stmt->execute($booking_ids);
                $files_rows = $files_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Group files by booking_id with pre-allocation for performance
                $files_data = [];
                foreach ($files_rows as $file) {
                    if (!isset($files_data[$file['booking_id']])) {
                        $files_data[$file['booking_id']] = [];
                    }
                    $files_data[$file['booking_id']][] = $file;
                }
            }
            
            $formattedBookings = array_map(function ($b) use ($files_data) {
                // Calculate payment progress percentage with division by zero protection
                $payment_progress_percent = 0.00;
                if ($b['total_cost'] > 0) {
                    // Ensure amount paid doesn't exceed total cost and calculate progress
                    $amount_paid = min((float)($b['amount_paid'] ?? 0), (float)($b['total_cost'] ?? 0));
                    $payment_progress_percent = round((($amount_paid / (float)($b['total_cost'] ?? 0)) * 100), 2);
                }
                
                return [
                "id" => (int)$b['booking_id'],
                "booking_code" => $b['booking_code'],
                "user_id" => (int)$b['user_id'],
                "service_id" => (int)$b['service_id'],
                "appointment_date" => date("Y-m-d H:i", strtotime($b['appointment_date'])),
                "address" => $b['address'],
                "total_amount" => (float)($b['labor_cost'] + $b['materials_cost'] + $b['misc_cost']) ?? 0.00,
                "initial_amount" => (float)$b['initial_amount'] ?? 0.00,
                "final_amount" => (float)$b['final_amount'] ?? 0.00,
                "total_paid_to_date" => min((float)$b['amount_paid'], (float)$b['total_cost']),
                "payment_progress_percent" => $payment_progress_percent,
                "status" => $b['status'],
                "previous_status" => $b['previous_status'] ?? null,
                "cancelled_initial_amount" => (float)$b['cancelled_initial_amount'] ?? 0.00,
                "created_at" => date("c", strtotime($b['created_at'])),
                "updated_at" => date("c", strtotime($b['updated_at'])),
                "notifBoolean" => (bool)($b['notifBoolean'] ?? false),
                "total_cost" => (float)($b['labor_cost'] + $b['materials_cost'] + $b['misc_cost']) ?? 0.00,
                "base_price" => (float)$b['base_price'] ?? 0.00,
                "calculated_estimate" => $this->calculateEstimateTotal($b['service_id'], $b['sqm']),
                "category" => $b['service_name'] ?? 'N/A',
                "avatar_url" => "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($b['name'] ?? 'User'),
                "name" => $b['name'] ?? 'Unknown User',
                "email" => $b['email'] ?? 'No Email',
                "phone_number" => $b['phone_number'] ?? null,
                "notes" => $b['notes'] ?? null,
                "admin_feedback" => $b["admin_feedback"] ?? null,
                "files" => $files_data[$b['booking_id']] ?? [],
                "sqm" => (int)$b['sqm'] ?? null,
                "refund_amount" => (float)$b['refund_amount'] ?? 0.00,
                "labor_cost" => (float)$b['labor_cost'] ?? 0.00,
                "materials_cost" => (float)$b['materials_cost'] ?? 0.00,
                "misc_cost" => (float)$b['misc_cost'] ?? 0.00,
                "service_name" => $b['service_name'] ?? null,
                ];
                }, $bookings);

            $this->jsonResponse([
                "status" => "success",
                "summary" => [
                    "total" => (int)$summaryData['total_bookings'],
                    "pending" => (int)($summaryData['pending_count'] ?? 0),
                    "active" => (int)($summaryData['active_count'] ?? 0),
                    "completed" => (int)($summaryData['completed_count'] ?? 0),
                    "cancelled" => (int)($summaryData['cancelled_count'] ?? 0),
                    "filtered" => $totalFilteredCount,
                    "total_pages" => ceil($totalFilteredCount / $limit)
                ],
                "bookings" => $formattedBookings,
            ]);

        }catch (PDOException $e) {
            $this->jsonError("Database error: " . $e->getMessage());
        }
    }

    public function listByUser($params)
    {
        // 1. Inputs & sanitization
        $limit = 6;
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $status = 'all';
        $order = 'newest';
        $userId = isset($params['user_id']) ? (int)$params['user_id'] : 0;
        $offset = ($page - 1) * $limit;

        // Return early if no user ID
        if ($userId <= 0) {
            $this->jsonResponse([
                "status" => "failure",
                "bookings" => [],
                "errorMsg" => "Invalid UserID"
            ]);

            return;
        }

        try {
            // Build query conditions
            $where = ["B.user_id = :user_id"];
            $nparams = [':user_id' => $userId];

            // Build WHERE clause
            $whereSql = "WHERE " . implode(" AND ", $where);

            // Get total count
            $countSql = "SELECT COUNT(*) FROM bookings AS B 
                     INNER JOIN services AS S ON B.service_id = S.id 
                     LEFT JOIN users AS U ON B.user_id = U.id
                     {$whereSql}";
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute($nparams);
            $totalFilteredCount = (int)$countStmt->fetchColumn();

            // Always order by newest
            $orderBy = "B.appointment_date DESC";

            // Main query with total cost and notifBoolean fields
            $sql = "SELECT 
                B.id AS booking_id, 
                B.booking_code, 
                B.user_id, 
                B.service_id,
                B.appointment_date, 
                B.address, 
                B.status,
                B.created_at, 
                B.updated_at, 
                B.notes, 
                B.sqm,
                B.admin_feedback,
                COALESCE(B.labor_cost, 0.00) AS labor_cost,
                COALESCE(B.materials_cost, 0.00) AS materials_cost,
                COALESCE(B.misc_cost, 0.00) AS misc_cost,
                COALESCE(B.refund_amount, 0.00) AS refund_amount,
                (COALESCE(B.labor_cost, 0.00) + COALESCE(B.materials_cost, 0.00) + COALESCE(B.misc_cost, 0.00)) AS total_cost,
                COALESCE(B.amount_paid, 0.00) AS amount_paid,
                COALESCE(B.notifBoolean, 0) AS notifBoolean,
                S.service_name AS service_name,
                U.name,
                U.email, 
                U.phone_number
                FROM bookings AS B
                INNER JOIN services AS S ON B.service_id = S.id
                LEFT JOIN users AS U ON B.user_id = U.id
                {$whereSql}
                ORDER BY $orderBy
                LIMIT :limit OFFSET :offset";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            
            foreach ($nparams as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get files for all bookings in a single query for efficiency
            $booking_ids = array_column($bookings, 'booking_id');
            $files_data = [];
            
            if (!empty($booking_ids)) {
                $placeholders = str_repeat('?,', count($booking_ids) - 1) . '?';
                $files_sql = "SELECT parent_id as booking_id, file_type, file_name as file_path, original_name, created_at as uploaded_at
                             FROM files 
                             WHERE category = 'bookings' AND parent_id IN ($placeholders)
                             ORDER BY parent_id, created_at ASC";
                $files_stmt = $this->pdo->prepare($files_sql);
                $files_stmt->execute($booking_ids);
                $files_rows = $files_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Group files by booking_id with pre-allocation for performance
                $files_data = [];
                foreach ($files_rows as $file) {
                    if (!isset($files_data[$file['booking_id']])) {
                        $files_data[$file['booking_id']] = [];
                    }
                    $files_data[$file['booking_id']][] = $file;
                }
            }
            
            $formattedBookings = array_map(function ($b) use ($files_data) {
                // Calculate payment progress percentage with division by zero protection
                $payment_progress_percent = 0.00;
                $amount_to_be_paid = 0.00; // Default value
                if ($b['total_cost'] > 0) {
                    // Use actual amount_paid from database
                    $amount_paid = min((float)($b['amount_paid'] ?? 0), (float)($b['total_cost'] ?? 0));
                    $payment_progress_percent = round((($amount_paid / (float)($b['total_cost'] ?? 0)) * 100), 2);
                    // Calculate amount to be paid (remaining balance)
                    $amount_to_be_paid = max(0, (float)($b['total_cost'] ?? 0) - $amount_paid);
                }
                
                return [
                    "id" => (int)$b['booking_id'],
                    "booking_code" => $b['booking_code'],
                    "user_id" => (int)$b['user_id'],
                    "service_id" => (int)$b['service_id'],
                    "appointment_date" => date("Y-m-d H:i", strtotime($b['appointment_date'])),
                    "address" => $b['address'],
                    "labor_cost" => (float)$b['labor_cost'] ?? 0.00,
                    "materials_cost" => (float)$b['materials_cost'] ?? 0.00,
                    "misc_cost" => (float)$b['misc_cost'] ?? 0.00,
                    "total_cost" => (float)$b['total_cost'] ?? 0.00,
                    "amount_paid" => (float)($b['amount_paid'] ?? 0),
                    "amount_to_be_paid" => $amount_to_be_paid ?? 0.00,
                    "notifBoolean" => (int)$b['notifBoolean'] ?? 0,
                    "status" => $b['status'],
                    "created_at" => date("c", strtotime($b['created_at'])),
                    "updated_at" => date("c", strtotime($b['updated_at'])),
                    "base_price" => (float)$b['base_price'] ?? 0.00,
                    "calculated_estimate" => $this->calculateEstimateTotal($b['service_id'], $b['sqm']),
                    "category" => $b['service_name'] ?? 'N/A',
                    "avatar_url" => "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($b['name'] ?? 'User'),
                    "name" => $b['name'] ?? 'Unknown User',
                    "email" => $b['email'] ?? 'No Email',
                    "phone_number" => $b['phone_number'] ?? null,
                    "notes" => $b['notes'] ?? null,
                    "admin_feedback" => $b["admin_feedback"] ?? null,
                    "files" => $files_data[$b['booking_id']] ?? [],
                    "sqm" => (int)$b['sqm'] ?? null,
                    "service_name" => $b['service_name'] ?? null,
                    "refund_amount" => (float)$b['refund_amount'] ?? 0.00,
                ];
            }, $bookings);

            $this->jsonResponse([
                "status" => "success",
                "bookings" => $formattedBookings
            ]);

        } catch (PDOException $e) {
            $this->handleDatabaseError($e, 'listByUser operation');
        }
    }

    // 2. Add Booking (Replaces add_booking.php)
    public function create($data)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError("Method Not Allowed. Expected POST, got " . $_SERVER['REQUEST_METHOD'], 405);
        }

        // Use the $data parameter directly instead of json_decode
        if (!$data || !is_array($data)) {
            $this->jsonError('Invalid input data.');
        }
        
        // 1. Map and Sanitize Data
        $user_id = filter_var($data['user_id'] ?? null, FILTER_SANITIZE_NUMBER_INT);
        $service_id = filter_var($data['service_id'] ?? null, FILTER_SANITIZE_NUMBER_INT);
        $address = $data['address'] ?? ''; // Raw storage - handle escaping at output layer
        $notes = filter_var($data['notes'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);

        // Ensure user_id and service_id are integers
        $user_id = (int)$user_id;
        $service_id = (int)$service_id;

        // 3. Handle Appointment Date
        // If the frontend sends 'appointment_date' directly, use it. Otherwise, combine date/time.
        $appointment_date = $data['appointment_date'] ?? null;
        if (!$appointment_date && !empty($data['date']) && !empty($data['time'])) {
            $appointment_date = date('Y-m-d H:i:s', strtotime($data['date'] . ' ' . $data['time']));
        }

        // 4. Generate Booking Code
        $booking_code = 'BK-' . strtoupper(bin2hex(random_bytes(4)));

        // 5. Validation
        $missing_fields = [];
        if (!$user_id) $missing_fields[] = 'user_id';
        if (!$service_id) $missing_fields[] = 'service_id';
        if (!$appointment_date) $missing_fields[] = 'appointment_date';
        if (!$address) $missing_fields[] = 'address';
        
        if (!empty($missing_fields)) {
            $this->jsonError('Missing required fields: ' . implode(', ', $missing_fields));
        }
        
        // NEW: Appointment availability validation
        if ($appointment_date) {
            $availability_check = $this->validateAppointmentAvailability($appointment_date);
            if (!$availability_check['available']) {
                $this->jsonError($availability_check['message'], 400);
                return;
            }
        }

        // Date validation - allow 30-minute buffer for server lag
        $appointment_time = strtotime($appointment_date);
        $buffered_time = time() - (30 * 60); // 30 minutes ago
        if ($appointment_time < $buffered_time) {
            $this->jsonError('Appointment date cannot be more than 30 minutes in the past');
        }

        try {
            $sql = "INSERT INTO bookings (booking_code, user_id, service_id, appointment_date, address, notes, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'Pending')";

            $stmt = $this->pdo->prepare($sql);

            $result = $stmt->execute([
                $booking_code,
                $user_id,
                $service_id,
                $appointment_date,
                $address,
                $notes,
            ]);

            if ($result) {
                $this->jsonResponse([
                    'status' => 'success',
                    'message' => "Booking {$booking_code} created successfully!",
                    'booking_code' => $booking_code
                ]);
            }
        }
        catch (PDOException $e) {
            // 1. Log the real error to your server's error log so you can fix it
            error_log("Database Error: " . $e->getMessage());

            // 2. Show the user a polite, professional message
            $this->jsonError('We encountered a problem saving your booking. Please try again later.', 500);
        }

    }

    // 3. Confirm Booking (Replaces confirmed_booking.php)
    public function update($params)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError("Method Not Allowed", 405);
        }

        $isMultipart = (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false);
        $data = $isMultipart ? $_POST : json_decode(file_get_contents('php://input'), true);

        if (!$data || !isset($data['id'])) {
            $this->jsonError("Missing Booking ID", 400);
        }

        $booking_id = (int)$data['id'];
        $notes = isset($data['notes']) ? htmlspecialchars(strip_tags($data['notes']), ENT_QUOTES, 'UTF-8') : '';

        try {
            $this->pdo->beginTransaction();

            // 1. Fetch current booking state
            $stmt = $this->pdo->prepare("SELECT status, booking_code, user_id FROM bookings WHERE id = ?");
            $stmt->execute([$booking_id]);
            $booking = $stmt->fetch();
            if (!$booking) throw new Exception("Booking not found", 404);

            // 2. Handle Files (Multipart logic)
            if ($isMultipart) {
                $this->handleFileUpload($booking_id, $booking['user_id'], $booking['status']);
            }

            // 3. Determine Transition & Amount
            $current_status = $booking['status'];
            $next_status = match($current_status) {
                'Consultation' => 'Pending',
                'Pending'      => 'Active',
                'Active'       => 'Completed',
                default        => throw new Exception("Invalid status transition from $current_status", 400)
            };

            $amount_to_add = (float)($data['amount'] ?? 0.00);

            // 4. Update Booking Table (The Cumulative Fix)
            $sql_update = "UPDATE bookings SET 
                            status = :status, 
                            amount_paid = amount_paid + :amount, 
                            notifBoolean = 0,
                            appointment_date = COALESCE(:adate, appointment_date)
                        WHERE id = :id";
            
            $this->pdo->prepare($sql_update)->execute([
                ':status' => $next_status,
                ':amount' => $amount_to_add,
                ':adate'  => $data['appointment_date'] ?? null,
                ':id'     => $booking_id
            ]);

            // 5. Record Transaction (Schema Matching)
            $transaction_code = 'TXN' . date('ymdHis') . rand(10, 99); 
            $payment_description = ($next_status === 'Active') ? 'Initial Payment' : (($next_status === 'Completed') ? 'Final Payment' : 'Status Update');

            $sql_trans = "INSERT INTO transactions (transaction_code, booking_id, booking_code, description, type, status, amount, notes) 
                        VALUES (:t_code, :b_id, :b_code, :desc, :type, :stat, :amt, :notes)";
            
            $this->pdo->prepare($sql_trans)->execute([
                ':t_code' => $transaction_code,
                ':b_id'   => $booking_id,
                ':b_code' => $booking['booking_code'],
                ':desc'   => $payment_description,
                ':type'   => 'Status Change', 
                ':stat'   => $next_status,     
                ':amt'    => $amount_to_add,  
                ':notes'  => $notes
            ]);

            $this->pdo->commit();
            $this->jsonResponse(["status" => "success", "transaction_code" => $transaction_code]);

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            error_log("Update Error: " . $e->getMessage());
            $this->jsonError($e->getMessage(), 500);
        }
    }

    // 4. Cancel Booking (Replaces cancelled_booking.php)
    public function cancel($params)
    {
        // if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        //     $this->jsonError("Method Not Allowed. Expected POST, got " . $_SERVER['REQUEST_METHOD'], 405);
        // }

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        if (!$data || !isset($data['id'])) {
            $this->jsonError("Missing Booking ID", 400);
        }

        $booking_id = (int)$data['id'];
        $notes = isset($data['notes']) ? htmlspecialchars(strip_tags($data['notes']), ENT_QUOTES, 'UTF-8') : '';

        try {
            $this->pdo->beginTransaction();
            
            // First get current status and booking details
            $sql = "SELECT status, amount_paid, total_cost, booking_code FROM bookings WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $booking_id]);
            $booking = $stmt->fetch();
            
            if (!$booking) {
                throw new Exception("Booking not found", 404);
            }
            
            $current_status = $booking['status'];
            
            // Check if booking can be cancelled
            if ($current_status === 'Cancelled' || $current_status === 'Completed') {
                throw new Exception("Cannot cancel booking: booking is already " . $current_status, 400);
            }
            
            // Generate a unique transaction code (max 20 chars)
            $transaction_code = 'CAN' . date('ymdHis') . substr(rand(100, 999), 0, 2);

            // UPDATE: Change status and reset notifBoolean
            $sql_booking = "UPDATE bookings 
                            SET status = 'Cancelled', notifBoolean = 0, refund_amount = :refund_amount
                            WHERE id = :id";

            $stmt = $this->pdo->prepare($sql_booking);
            $stmt->execute([':id' => $booking_id, ':refund_amount' => (float)($booking['amount_paid'] / 2)]);

            if ($stmt->rowCount() === 0) {
                throw new Exception("Update failed: Booking status may have been changed by another process.");
            }
            
            $refund_amount = (float)($booking['total_cost'] / 2);
            // INSERT: Log the cancellation details into your 'transactions' table
            $sql_log = "INSERT INTO transactions 
                        (transaction_code, booking_id, booking_code, description, type, status, amount, notes) 
                        VALUES (:code, :bid, :booking_code, :desc, 'Status Change', 'Cancelled', :amount, :notes)";
            
            $logStmt = $this->pdo->prepare($sql_log);
            $logStmt->execute([
                ':code' => $transaction_code,
                ':bid'  => $booking_id,
                ':booking_code' => $booking['booking_code'],
                ':desc' => $current_status . " to Cancelled",
                ':amount' => -$refund_amount,
                ':notes' => $notes
            ]);
            
            // INSERT: Add expense transaction for refund amount (negative)
            
            $refund_transaction_code = 'REF' . date('ymdHis') . substr(rand(100, 999), 0, 2);
            
            $sql_refund = "INSERT INTO transactions 
                           (transaction_code, booking_id, booking_code, description, type, status, amount, notes) 
                           VALUES (:code, :bid, :booking_code, :desc, 'Expense', 'Completed', :amount, :notes)";
            
            $refundStmt = $this->pdo->prepare($sql_refund);
            $refundStmt->execute([
                ':code' => $refund_transaction_code,
                ':bid'  => $booking_id,
                ':booking_code' => $booking['booking_code'],
                ':desc' => "Refund for cancelled booking (50% of payment)",
                ':amount' => -$refund_amount, // Negative amount for expense
                ':notes' => "Automatic refund: " . $current_status . " to Cancelled"
            ]);

            // Commit both changes
            $this->pdo->commit();

            $this->jsonResponse([
                "status" => "success", 
                "message" => "Booking #" . $booking_id . " status updated from " . $current_status . " to Cancelled",
                "transaction_code" => $transaction_code
            ]);

        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $code = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
            error_log("Cancellation Error: " . $e->getMessage());
            $this->jsonError($e->getMessage(), $code);
        }
    }

    private function handleFileUpload($booking_id, $user_id, $current_status)
    {
        // 1. Catch size errors immediately (Server-side limit check)
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
            $max = ini_get('post_max_size');
            $this->jsonError("The total upload size exceeds server limit of {$max}.", 413);
        }

        // 2. Determine required files based on the status passed from update()
        if ($current_status === 'Pending') {
            $required_files = ['blueprint', 'quotation', 'agreement'];
        } elseif ($current_status === 'Active') {
            $required_files = ['projectDocumentation'];
        } else {
            throw new Exception("Cannot upload files for status: " . $current_status, 400);
        }
        
        // 3. Process Required Files
        foreach ($required_files as $file_key) {
            if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Required file missing or upload failed: " . $file_key, 400);
            }

            $file = $_FILES[$file_key];
            
            // Use your existing validation logic
            $this->validateFile($file, $file_key, $current_status);
            
            // Construct Path once using the passed $user_id and $booking_id
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . "/uploads/users/{$user_id}/{$booking_id}";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Unique Filename Generation
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $safe_filename = $file_key . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_extension;
            $relative_path = "uploads/users/{$user_id}/{$booking_id}/{$safe_filename}";
            
            if (move_uploaded_file($file['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . '/' . $relative_path)) {
                // Log file to the booking_files table
                $this->saveBookingFile($booking_id, $file_key, $relative_path, $file['name']);
            } else {
                throw new Exception("Failed to move uploaded file: " . $file_key, 500);
            }
        }
        
        // 4. Process Optional Portfolio Files (if any)
        if (isset($_FILES['portfolioFiles']) && is_array($_FILES['portfolioFiles']['name'])) {
            $portfolio_files = $this->reArrayFiles($_FILES['portfolioFiles']);
            
            foreach ($portfolio_files as $index => $file) {
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $this->validateFile($file, 'portfolio', $current_status);
                    
                    $safe_filename = 'portfolio_' . time() . '_' . $index . '_' . bin2hex(random_bytes(4)) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                    $relative_path = "uploads/users/{$user_id}/{$booking_id}/{$safe_filename}";
                    
                    if (move_uploaded_file($file['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . '/' . $relative_path)) {
                        $this->saveBookingFile($booking_id, 'portfolio', $relative_path, $file['name']);
                    }
                }
            }
        }
    }
    
    /**
     * Check if booking has required files for status transition
     */
    private function checkBookingFiles(int $booking_id, array $required_file_types): array
    {
        $placeholders = str_repeat('?,', count($required_file_types) - 1) . '?';
        $sql = "SELECT file_type, file_name as file_path, original_name 
                FROM files 
                WHERE category = 'bookings' AND parent_id = ? AND file_type IN ($placeholders)";
        
        $stmt = $this->pdo->prepare($sql);
        $params = array_merge([$booking_id], $required_file_types);
        $stmt->execute($params);
        
        $existing_files = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);
        
        $missing_files = [];
        $invalid_paths = [];
        
        foreach ($required_file_types as $file_type) {
            if (!isset($existing_files[$file_type])) {
                $missing_files[] = $file_type;
            } else {
                $file_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $existing_files[$file_type]['file_path'];
                if (!file_exists($file_path)) {
                    $invalid_paths[] = $file_type;
                }
            }
        }
        
        return [
            'missing_files' => $missing_files,
            'invalid_paths' => $invalid_paths,
            'existing_files' => $existing_files
        ];
    }

    // 5. Get Single Booking (for invoice modal)
    public function get($params)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonError("Method Not Allowed. Expected GET, got " . $_SERVER['REQUEST_METHOD'], 405);
            return;
        }

        // Validate booking ID
        if (!isset($params['id'])) {
            $this->jsonError("Missing booking ID", 400);
            return;
        }

        $booking_id = (int)$params['id'];

        try {
            // Get booking details with user and service information
            $sql = "SELECT b.*, u.name, u.email, u.phone_number, s.service_name, s.category
                    FROM bookings b
                    LEFT JOIN users u ON b.user_id = u.id
                    LEFT JOIN services s ON b.service_id = s.id
                    WHERE b.id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$booking_id]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking) {
                $this->jsonError("Booking not found", 404);
                return;
            }

            // Get transactions for this booking
            $sql = "SELECT * FROM transactions 
                    WHERE booking_id = ? 
                    ORDER BY transaction_date DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$booking_id]);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get files for this booking
            $files = $this->getBookingFiles($booking_id);

            // Return all data
            $this->jsonResponse([
                'success' => true,
                'booking' => $booking,
                'transactions' => $transactions,
                'files' => $files
            ]);


            // 4. Get Data with CTE for better performance
            $sortOptions = [
                "newest" => "B.appointment_date DESC", 
                "oldest" => "B.appointment_date ASC",
                "updated_newest" => "B.updated_at DESC", 
                "updated_oldest" => "B.updated_at ASC"
            ];
            $orderBy = $sortOptions[$order] ?? "B.appointment_date DESC";

            // Use CTE to pre-calculate transaction aggregates
            $sql = "WITH transaction_aggregates AS (
                SELECT 
                    T.booking_id,
                    SUM(CASE WHEN T.description LIKE '%Initial Payment%' THEN T.amount ELSE 0 END) AS initial_amount,
                    SUM(CASE WHEN T.description LIKE '%Final Payment%' THEN T.amount ELSE 0 END) AS final_amount,
                    SUM(CASE WHEN T.description LIKE '%Initial%' OR T.description LIKE '%Final%' THEN T.amount ELSE 0 END) AS total_booking_cost,
                    SUM(CASE WHEN T.status IN ('Active', 'Completed') AND (T.description LIKE '%Initial%' OR T.description LIKE '%Final%') THEN T.amount ELSE 0 END) AS amount_paid
                FROM transactions T
                WHERE T.type IN ('Status Change', 'Payment')
                AND (T.description LIKE '%Initial%' OR T.description LIKE '%Final%')
                GROUP BY T.booking_id
            )
            SELECT 
                B.id AS booking_id, B.booking_code, B.user_id, B.service_id,
                B.appointment_date, B.address, B.status,
                B.created_at, B.updated_at, B.notes, B.admin_feedback, B.sqm,
                B.notifBoolean,
                COALESCE(B.total_cost, 0.00) AS total_cost,
                COALESCE(B.base_price, 0.00) AS base_price,
                COALESCE(B.labor_cost, 0.00) AS labor_cost,
                COALESCE(B.materials_cost, 0.00) AS materials_cost,
                COALESCE(B.misc_cost, 0.00) AS misc_cost,
                COALESCE(B.refund_amount, 0.00) AS refund_amount,
                S.service_name AS service_name,
                COALESCE(TA.initial_amount, 0.00) AS initial_amount,
                COALESCE(TA.final_amount, 0.00) AS final_amount,
                COALESCE(TA.total_booking_cost, 0.00) AS total_booking_cost,
                COALESCE(TA.amount_paid, 0.00) AS amount_paid,
                CASE 
                    WHEN B.status = 'Cancelled' THEN 
                        (SELECT CASE 
                            WHEN description LIKE '%Pending to Cancelled%' THEN 'Pending'
                            WHEN description LIKE '%Active to Cancelled%' THEN 'Active'
                            ELSE NULL 
                         END
                         FROM transactions 
                         WHERE booking_id = B.id AND status = 'Cancelled' 
                         AND type = 'Status Change' 
                         ORDER BY id DESC LIMIT 1)
                    ELSE NULL 
                END AS previous_status,
                CASE 
                    WHEN B.status = 'Cancelled' THEN 
                        (SELECT amount FROM transactions 
                         WHERE booking_id = B.id AND status = 'Active' 
                         AND type = 'Status Change' AND description LIKE '%Initial Payment%'
                         ORDER BY id DESC LIMIT 1)
                    ELSE NULL 
                END AS cancelled_initial_amount,
                S.service_name, S.service_name,
                U.name, U.email, U.phone_number
                FROM bookings AS B
                INNER JOIN services AS S ON B.service_id = S.id
                LEFT JOIN users AS U ON B.user_id = U.id
                LEFT JOIN transaction_aggregates TA ON B.id = TA.booking_id
                $whereSql 
                ORDER BY $orderBy
            LIMIT :limit OFFSET :offset";

            $stmt = $this->pdo->prepare($sql);
            foreach ($nparams as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();

            $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get files for all bookings in a single query for efficiency
            $booking_ids = array_column($bookings, 'booking_id');
            $files_data = [];
            
            if (!empty($booking_ids)) {
                $placeholders = str_repeat('?,', count($booking_ids) - 1) . '?';
                $files_sql = "SELECT parent_id as booking_id, file_type, file_name as file_path, original_name, created_at as uploaded_at
                             FROM files 
                             WHERE category = 'bookings' AND parent_id IN ($placeholders)
                             ORDER BY parent_id, created_at ASC";
                $files_stmt = $this->pdo->prepare($files_sql);
                $files_stmt->execute($booking_ids);
                $files_rows = $files_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Group files by booking_id with pre-allocation for performance
                $files_data = [];
                foreach ($files_rows as $file) {
                    if (!isset($files_data[$file['booking_id']])) {
                        $files_data[$file['booking_id']] = [];
                    }
                    $files_data[$file['booking_id']][] = $file;
                }
            }
            
            $formattedBookings = array_map(function ($b) use ($files_data) {
                // Calculate payment progress percentage with division by zero protection
                $payment_progress_percent = 0.00;
                if ($b['total_cost'] > 0) {
                    // Ensure amount paid doesn't exceed total cost and calculate progress
                    $amount_paid = min((float)($b['amount_paid'] ?? 0), (float)($b['total_cost'] ?? 0));
                    $payment_progress_percent = round((($amount_paid / (float)($b['total_cost'] ?? 0)) * 100), 2);
                }
                
                return [
                "id" => (int)$b['booking_id'],
                "booking_code" => $b['booking_code'],
                "user_id" => (int)$b['user_id'],
                "service_id" => (int)$b['service_id'],
                "appointment_date" => date("Y-m-d H:i", strtotime($b['appointment_date'])),
                "address" => $b['address'],
                "total_amount" => (float)$b['total_booking_cost'],
                "initial_amount" => (float)$b['initial_amount'],
                "final_amount" => (float)$b['final_amount'],
                "total_paid_to_date" => (float)$b['amount_paid'],
                "payment_progress_percent" => $payment_progress_percent,
                "status" => $b['status'],
                "previous_status" => $b['previous_status'] ?? null,
                "cancelled_initial_amount" => (float)$b['cancelled_initial_amount'] ?? 0.00,
                "created_at" => date("c", strtotime($b['created_at'])),
                "updated_at" => date("c", strtotime($b['updated_at'])),
                "notifBoolean" => (bool)($b['notifBoolean'] ?? false),
                "total_cost" => (float)$b['total_cost'] ?? 0.00,
                "base_price" => (float)$b['base_price'] ?? 0.00,
                "calculated_estimate" => $this->calculateEstimateTotal($b['service_id'], $b['sqm']),
                "category" => $b['service_name'] ?? 'N/A',
                "avatar_url" => "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($b['name'] ?? 'User'),
                "name" => $b['name'] ?? 'Unknown User',
                "email" => $b['email'] ?? 'No Email',
                "phone_number" => $b['phone_number'] ?? null,
                "notes" => $b['notes'] ?? null,
                "admin_feedback" => $b["admin_feedback"] ?? null,
                "files" => $files_data[$b['booking_id']] ?? [],
                "sqm" => (int)$b['sqm'] ?? null,
                "refund_amount" => (float)$b['refund_amount'] ?? 0.00,
                "labor_cost" => (float)$b['labor_cost'] ?? 0.00,
                "materials_cost" => (float)$b['materials_cost'] ?? 0.00,
                "misc_cost" => (float)$b['misc_cost'] ?? 0.00,
                "service_name" => $b['service_name'] ?? null,
                ];
                }, $bookings);

            $this->jsonResponse([
                "status" => "success",
                "summary" => [
                    "total" => (int)$summaryData['total_bookings'],
                    "pending" => (int)($summaryData['pending_count'] ?? 0),
                    "active" => (int)($summaryData['active_count'] ?? 0),
                    "completed" => (int)($summaryData['completed_count'] ?? 0),
                    "cancelled" => (int)($summaryData['cancelled_count'] ?? 0),
                    "filtered" => $totalFilteredCount,
                    "total_pages" => ceil($totalFilteredCount / $limit)
                ],
                    "bookings" => $formattedBookings,
            ]);

        } catch (PDOException $e) {
            $this->handleDatabaseError($e, 'listByUser operation');
        }
    }

    /**
     * Save uploaded file information to database
     */
    private function saveBookingFile(int $booking_id, string $file_type, string $file_path, string $original_name): bool
    {
        $sql = "INSERT INTO files (category, parent_id, file_type, file_name, original_name) 
                VALUES ('bookings', ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                file_name = VALUES(file_name), 
                original_name = VALUES(original_name), 
                created_at = CURRENT_TIMESTAMP";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$booking_id, $file_type, $file_path, $original_name]);
    }

    /**
     * Get booking files for display
     */
    private function getBookingFiles(int $booking_id): array
    {
        $sql = "SELECT file_type, file_name as file_path, original_name, created_at as uploaded_at 
                FROM files 
                WHERE category = 'bookings' AND parent_id = ? 
                ORDER BY created_at ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$booking_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // File validation helper with stage-specific rules
    private function validateFile($file, $file_label, $current_status)
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new Exception("File too large: " . $file_label, 400);
                case UPLOAD_ERR_PARTIAL:
                    throw new Exception("File upload was incomplete: " . $file_label, 400);
                case UPLOAD_ERR_NO_FILE:
                    throw new Exception("No file uploaded: " . $file_label, 400);
                default:
                    throw new Exception("Upload error occurred: " . $file_label, 400);
            }
        }
        
        // Define stage-specific validation rules
        $validation_rules = [
            'blueprint' => [
                'allowed_extensions' => ['pdf', 'dwg', 'jpg', 'png'],
                'allowed_mime_types' => [
                    'application/pdf',
                    'image/vnd.dwg',
                    'image/jpeg',
                    'image/png'
                ],
                'max_size' => 15 * 1024 * 1024 // 15MB
            ],
            'quotation' => [
                'allowed_extensions' => ['pdf', 'xlsx', 'docx', 'txt', 'csv'],
                'allowed_mime_types' => [
                    'application/pdf',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'text/plain',
                    'text/csv',
                    'application/csv'
                ],
                'max_size' => 5 * 1024 * 1024 // 5MB
            ],
            'agreement' => [
                'allowed_extensions' => ['pdf', 'jpg'],
                'allowed_mime_types' => [
                    'application/pdf',
                    'image/jpeg'
                ],
                'max_size' => 5 * 1024 * 1024 // 5MB
            ],
            'projectDocumentation' => [
                'allowed_extensions' => ['zip', 'pdf', 'docx', 'png'],
                'allowed_mime_types' => [
                    'application/zip',
                    'application/pdf',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'image/png'
                ],
                'max_size' => 25 * 1024 * 1024 // 25MB
            ],
            'portfolio' => [
                'allowed_extensions' => ['png', 'jpg', 'jpeg'],
                'allowed_mime_types' => [
                    'image/png',
                    'image/jpeg'
                ],
                'max_size' => 10 * 1024 * 1024 // 10MB
            ]
        ];
        
        // Get validation rules for current file label
        $rules = $validation_rules[$file_label] ?? null;
        if (!$rules) {
            throw new Exception("Invalid file label: " . $file_label, 400);
        }
        
        // Validate file size based on stage-specific rules
        if ($file['size'] > $rules['max_size']) {
            $max_mb = round($rules['max_size'] / (1024 * 1024), 1);
            throw new Exception("File too large (max {$max_mb}MB): " . $file_label, 400);
        }
        
        // Validate file extension
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, $rules['allowed_extensions'])) {
            throw new Exception("Invalid file type for " . $file_label . ". Allowed: " . implode(', ', $rules['allowed_extensions']), 400);
        }
        
        // Validate MIME type using finfo
        // TODO: Consider implementing more robust MIME-type validation
        // - Use fileinfo extension for better MIME detection
        // - Validate against a whitelist of safe MIME types
        // - Consider implementing virus scanning for uploaded files
        // - Add file signature validation to prevent MIME type spoofing
        if (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime_type, $rules['allowed_mime_types'])) {
                throw new Exception("Invalid file format for " . $file_label . ". MIME: " . $mime_type, 400);
            }
        }
    }
    
    /**
     * Reorganize $_FILES array for multiple file uploads
     */
    private function reArrayFiles($file_post) {
        $file_ary = array();
        $file_count = count($file_post['name']);
        $file_keys = array_keys($file_post);
        
        for ($i=0; $i<$file_count; $i++) {
            foreach ($file_keys as $key) {
                $file_ary[$i][$key] = $file_post[$key][$i];
            }
        }
        
        return $file_ary;
    }
    
    /**
     * Update consultation costs with validation and sanitization
     */
    public function updateConsultationCosts($data)
    {
        try {
            // Validate required fields
            $required = ['booking_id', 'labor_cost', 'materials_cost', 'misc_cost'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || $data[$field] === '') {
                    throw new Exception("Missing required field: $field");
                }
            }
            
            // Sanitize and validate inputs
            $bookingId = filter_var($data['booking_id'], FILTER_SANITIZE_NUMBER_INT);
            $laborCost = filter_var($data['labor_cost'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $materialsCost = filter_var($data['materials_cost'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $miscCost = filter_var($data['misc_cost'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            
            // Validate numeric values
            if (!is_numeric($bookingId) || $bookingId <= 0) {
                throw new Exception("Invalid booking ID");
            }
            
            if (!is_numeric($laborCost) || $laborCost < 0) {
                throw new Exception("Labor cost must be a positive number");
            }
            
            if (!is_numeric($materialsCost) || $materialsCost < 0) {
                throw new Exception("Materials cost must be a positive number");
            }
            
            if (!is_numeric($miscCost) || $miscCost < 0) {
                throw new Exception("Miscellaneous cost must be a positive number");
            }
            
            // Calculate total cost
            $totalCost = $laborCost + $materialsCost + $miscCost;
            
            // Check if booking exists and is in consultation status
            $checkSql = "SELECT id, status FROM bookings WHERE id = ?";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$bookingId]);
            $booking = $checkStmt->fetch();
            
            if (!$booking) {
                throw new Exception("Booking not found");
            }
            
            if ($booking['status'] !== 'Consultation') {
                throw new Exception("Costs can only be updated for consultation bookings");
            }
            
            // Update the booking costs and mark as notifBoolean
            $updateSql = "UPDATE bookings SET 
                         labor_cost = ?, 
                         materials_cost = ?, 
                         misc_cost = ?,
                         total_cost = ?,
                         notifBoolean = 1
                         WHERE id = ?";
            
            $updateStmt = $this->pdo->prepare($updateSql);
            $result = $updateStmt->execute([$laborCost, $materialsCost, $miscCost, $totalCost, $bookingId]);
            
            if (!$result) {
                throw new Exception("Failed to update consultation costs");
            }
            
            // Return success response
            echo json_encode([
                'status' => 'success',
                'message' => 'Consultation costs updated successfully',
                'data' => [
                    'booking_id' => $bookingId,
                    'labor_cost' => number_format($laborCost, 2),
                    'materials_cost' => number_format($materialsCost, 2),
                    'misc_cost' => number_format($miscCost, 2),
                    'total_cost' => number_format($totalCost, 2)
                ]
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Update booking estimate with base price and calculated total
     */
    public function updateBookingEstimate($data)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError("Method Not Allowed. Expected POST, got " . $_SERVER['REQUEST_METHOD'], 405);
        }

        if (!isset($data['booking_id']) || !isset($data['sqm'])) {
            $this->jsonError("Missing required fields: booking_id, sqm", 400);
        }

        $bookingId = (int)$data['booking_id'];
        $sqm = (float)$data['sqm'];
        $updateBasePrice = isset($data['base_price']);
        
        // Get service_id for calculation
        $stmt = $this->pdo->prepare("SELECT service_id FROM bookings WHERE id = ?");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$booking) {
            $this->jsonError("Booking not found", 404);
        }
        
        // Calculate estimate automatically
        $estimateTotal = $this->calculateEstimateTotal($booking['service_id'], $sqm);

        try {
            $this->pdo->beginTransaction();
            
            // Build dynamic SQL based on whether base_price should be updated
            if ($updateBasePrice) {
                $basePrice = (float)$data['base_price'];
                $sql = "UPDATE bookings SET 
                        base_price = :base_price,
                        sqm = :sqm,
                        updated_at = CURRENT_TIMESTAMP
                        WHERE id = :booking_id";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    ':base_price' => $basePrice,
                    ':sqm' => $sqm,
                    ':booking_id' => $bookingId
                ]);
            } else {
                // Only update sqm, keep base_price unchanged
                $sql = "UPDATE bookings SET 
                        sqm = :sqm,
                        updated_at = CURRENT_TIMESTAMP
                        WHERE id = :booking_id";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    ':sqm' => $sqm,
                    ':booking_id' => $bookingId
                ]);
            }

            if ($stmt->rowCount() === 0) {
                throw new Exception("Booking not found or no changes made");
            }

            $this->pdo->commit();

            echo json_encode([
                'status' => 'success',
                'message' => 'Booking estimate updated successfully',
                'data' => [
                    'booking_id' => $bookingId,
                    'base_price' => number_format($basePrice ?? 0, 2),
                    'sqm' => number_format($sqm, 2),
                    'calculated_estimate' => number_format($estimateTotal, 2)
                ]
            ]);

        } catch (Exception $e) {
            $this->pdo->rollBack();
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Update client editable fields and reset consultation status
     */
    public function updateClientFields($data)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError("Method Not Allowed. Expected POST, got " . $_SERVER['REQUEST_METHOD'], 405);
        }

        if (!isset($data['booking_id'])) {
            $this->jsonError("Missing required field: booking_id", 400);
        }

        $bookingId = (int)$data['booking_id'];
        $address = $data['address'] ?? null;
        $sqm = $data['sqm'] ?? null;
        $notes = $data['notes'] ?? null;

        try {
            $this->pdo->beginTransaction();
            
            // Build dynamic update query
            $updateFields = [];
            $updateValues = [];
            
            if ($address !== null) {
                $updateFields[] = "address = :address";
                $updateValues[':address'] = $address;
            }
            
            if ($sqm !== null) {
                $updateFields[] = "sqm = :sqm";
                $updateValues[':sqm'] = $sqm;
            }
            
            if ($notes !== null) {
                $updateFields[] = "notes = :notes";
                $updateValues[':notes'] = $notes;
            }
            
            // Always reset notifBoolean status when client makes changes
            $updateFields[] = "notifBoolean = 0";
            $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
            
            $updateValues[':booking_id'] = $bookingId;
            
            if (empty($updateFields)) {
                throw new Exception("No fields to update");
            }
            
            $sql = "UPDATE bookings SET " . implode(', ', $updateFields) . " WHERE id = :booking_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($updateValues);

            if ($stmt->rowCount() === 0) {
                throw new Exception("Booking not found or no changes made");
            }

            $this->pdo->commit();

            echo json_encode([
                'status' => 'success',
                'message' => 'Client fields updated successfully',
                'data' => [
                    'booking_id' => $bookingId,
                    'notifBoolean_reset' => true
                ]
            ]);

        } catch (Exception $e) {
            $this->pdo->rollBack();
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Update admin feedback for a booking
     */
    public function updateAdminFeedback($data)
    {
        // Debug logging
        error_log("updateAdminFeedback called with data: " . json_encode($data));
        error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("Method not allowed: " . $_SERVER['REQUEST_METHOD']);
            $this->jsonError("Method Not Allowed. Expected POST, got " . $_SERVER['REQUEST_METHOD'], 405);
            return;
        }
        
        $bookingId = $data['booking_id'] ?? 0;
        $adminFeedback = $data['admin_feedback'] ?? '';
        $notifBoolean = isset($data['notifBoolean']) ? (bool)$data['notifBoolean'] : false;
        
        error_log("Extracted bookingId: " . $bookingId);
        error_log("Extracted adminFeedback: " . $adminFeedback);
        
        if (empty($bookingId)) {
            error_log("Booking ID is empty");
            $this->jsonError("Booking ID is required", 400);
            return;
        }
        
        try {
            $this->pdo->beginTransaction();
            
            // First check if booking exists
            $checkSql = "SELECT id, admin_feedback FROM bookings WHERE id = :booking_id";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([':booking_id' => $bookingId]);
            $existingBooking = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("Existing booking check: " . json_encode($existingBooking));
            
            if (!$existingBooking) {
                error_log("Booking ID $bookingId not found in database");
                $this->jsonError("Booking not found", 404);
                return;
            }
            
            error_log("Current admin_feedback: '" . $existingBooking['admin_feedback'] . "'");
            error_log("New admin_feedback: '" . $adminFeedback . "'");
            
            // Check if values are actually different
            if ($existingBooking['admin_feedback'] === $adminFeedback) {
                error_log("No changes needed - admin_feedback is already the same");
                $this->jsonError("No changes made - admin feedback is already the same", 400);
                return;
            }
            
            $sql = "UPDATE bookings SET admin_feedback = :admin_feedback, notifBoolean = :notifBoolean WHERE id = :booking_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':admin_feedback' => $adminFeedback,
                ':notifBoolean' => $notifBoolean ? 1 : 0,
                ':booking_id' => $bookingId
            ]);
            
            error_log("Update executed, rowCount: " . $stmt->rowCount());
            
            if ($stmt->rowCount() === 0) {
                error_log("Unexpected: Update affected 0 rows after passing all checks");
                throw new Exception("Update failed unexpectedly");
            }
            
            $this->pdo->commit();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Admin feedback updated successfully',
                'data' => [
                    'booking_id' => $bookingId,
                    'admin_feedback' => $adminFeedback
                ]
            ]);
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Update consultation status for a booking
     */
    public function updateConsultationStatus($data)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError("Method Not Allowed. Expected POST, got " . $_SERVER['REQUEST_METHOD'], 405);
            return;
        }
        
        $bookingId = $data['booking_id'] ?? 0;
        $consultationStatus = $data['consultation_status'] ?? '';
        
        if (empty($bookingId)) {
            $this->jsonError("Booking ID is required", 400);
            return;
        }
        
        if (empty($consultationStatus)) {
            $this->jsonError("Consultation status is required", 400);
            return;
        }
        
        // Validate notification status values
        $validStatuses = ['consulted', 'not_consulted', 'unavailable', 'available'];
        if (!in_array($consultationStatus, $validStatuses)) {
            $this->jsonError("Invalid notification status. Valid values: " . implode(', ', $validStatuses), 400);
            return;
        }
        
        try {
            $this->pdo->beginTransaction();
            
            $sql = "UPDATE bookings SET notifBoolean = :notifBoolean WHERE id = :booking_id";
            $stmt = $this->pdo->prepare($sql);
            
            // Set notifBoolean flag based on status
            $notifBooleanValue = 0;
            switch($consultationStatus) {
                case 'consulted':
                    $notifBooleanValue = 1;
                    break;
                case 'not_consulted':
                    $notifBooleanValue = 0;
                    break;
                case 'unavailable':
                    $notifBooleanValue = 0;
                    break;
                case 'available':
                    $notifBooleanValue = 0;
                    break;
            }
            
            $stmt->execute([
                ':notifBoolean' => $notifBooleanValue,
                ':booking_id' => $bookingId
            ]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Booking not found or no changes made");
            }
            
            $this->pdo->commit();
            
            echo json_encode([
                'status' => 'success',
                'message' => "Notification status updated to {$consultationStatus}",
                'data' => [
                    'booking_id' => $bookingId,
                    'consultation_status' => $consultationStatus,
                    'notifBoolean' => $notifBooleanValue
                ]
            ]);
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update notifBoolean specifically for a booking
     * @param int $bookingId The booking ID
     * @param bool $notifBooleanValue The notifBoolean value (0 or 1)
     * @return bool Success status
     */
    public function updateNotifBoolean($bookingId, $notifBooleanValue) {
        try {
            $sql = "UPDATE bookings SET notifBoolean = :notifBoolean, updated_at = CURRENT_TIMESTAMP WHERE id = :booking_id";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':notifBoolean' => $notifBooleanValue ? 1 : 0,
                ':booking_id' => $bookingId
            ]);
            
            return $result && $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Error updating notifBoolean: " . $e->getMessage());
            return false;
        }
    }

    // NEW: Appointment availability validation method
    private function validateAppointmentAvailability($appointment_date) {
        // Check business hours
        $appointment_time = strtotime($appointment_date);
        $hour = date('H', $appointment_time);
        $day_of_week = date('N', $appointment_time); // 1-7 (Mon-Sun)
        
        if ($day_of_week > 5) { // Weekend
            return ['available' => false, 'message' => 'Appointments only available Monday-Friday'];
        }
        
        if ($hour < 9 || $hour >= 18) { // Outside business hours
            return ['available' => false, 'message' => 'Appointments available 9:00 AM - 6:00 PM'];
        }
        
        // Check advance booking rules
        $min_advance = 24; // hours
        $max_advance = 30; // days
        $now = time();
        $hours_diff = ($appointment_time - $now) / 3600;
        
        if ($hours_diff < $min_advance) {
            return ['available' => false, 'message' => 'Appointments must be booked at least 24 hours in advance'];
        }
        
        if ($hours_diff > ($max_advance * 24)) {
            return ['available' => false, 'message' => 'Appointments cannot be booked more than 30 days in advance'];
        }
        
        // Check unavailable dates
        $date_only = date('Y-m-d', $appointment_time);
        $sql = "SELECT COUNT(*) as unavailable_count FROM appointment_availability 
                WHERE unavailable_date = ? AND is_active = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$date_only]);
        $result = $stmt->fetch();
        
        if ($result['unavailable_count'] > 0) {
            return ['available' => false, 'message' => 'Selected date is not available'];
        }
        
        // Check for existing appointments at same time
        $time_slot = date('H:i', $appointment_time);
        $sql = "SELECT COUNT(*) as conflict_count FROM bookings 
                WHERE DATE(appointment_date) = ? 
                AND TIME(appointment_date) = ? 
                AND status NOT IN ('Cancelled', 'Completed')";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$date_only, $time_slot]);
        $conflicts = $stmt->fetch();
        
        if ($conflicts['conflict_count'] >= 3) { // Max 3 appointments per hour
            return ['available' => false, 'message' => 'Selected time slot is fully booked'];
        }
        
        return ['available' => true];
    }
    
    // NEW: Appointment confirmation method
    public function confirmAppointment($booking_id, $admin_id) {
        $sql = "UPDATE bookings SET appointment_status = 'Confirmed' WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$booking_id]);
        
        // Get booking details for notification
        $booking = $this->getBookingDetails($booking_id);
        
        // Log confirmation
        $this->logAppointmentChange($booking_id, 'Scheduled', 'Confirmed', null, null, $admin_id, 'Appointment confirmed by admin');
        
        return ['status' => 'success', 'message' => 'Appointment confirmed successfully'];
    }
    
    // NEW: Appointment rescheduling method
    public function rescheduleAppointment($booking_id, $new_appointment_date, $admin_id, $reason) {
        // Get current appointment details
        $current = $this->getBookingDetails($booking_id);
        $old_date = $current['appointment_date'];
        
        // Validate new appointment availability
        $availability_check = $this->validateAppointmentAvailability($new_appointment_date);
        if (!$availability_check['available']) {
            return ['status' => 'error', 'message' => $availability_check['message']];
        }
        
        // Update appointment
        $sql = "UPDATE bookings SET appointment_date = ?, appointment_status = 'Rescheduled' WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$new_appointment_date, $booking_id]);
        
        // Log reschedule
        $this->logAppointmentChange($booking_id, 'Scheduled', 'Rescheduled', $old_date, $new_appointment_date, $admin_id, $reason);
        
        return ['status' => 'success', 'message' => 'Appointment rescheduled successfully'];
    }
    
    // NEW: Appointment history logging using enhanced appointment_availability table
    private function logAppointmentChange($booking_id, $old_status, $new_status, $old_date, $new_date, $changed_by, $reason) {
        // Create availability record for appointment history
        $sql = "INSERT INTO appointment_availability 
                (unavailable_date, status, booking_id, appointment_status, changed_by, change_reason, created_by) 
                VALUES (?, 'booked', ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        
        $appointment_date = date('Y-m-d', strtotime($new_date ?: $old_date));
        $status_description = "$old_status → $new_status";
        
        $stmt->execute([
            $appointment_date,
            $booking_id,
            $new_status,
            $changed_by,
            $reason,
            $changed_by
        ]);
        
        // Also log rescheduling if date changed
        if ($old_date && $new_date && $old_date !== $new_date) {
            $old_date_only = date('Y-m-d', strtotime($old_date));
            $new_date_only = date('Y-m-d', strtotime($new_date));
            
            // Mark new date as booked
            $sql = "INSERT INTO appointment_availability 
                    (unavailable_date, status, booking_id, appointment_status, changed_by, change_reason, created_by) 
                    VALUES (?, 'booked', ?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $new_date_only,
                $booking_id,
                'Rescheduled',
                $changed_by,
                "Rescheduled from $old_date to $new_date",
                $changed_by
            ]);
        }
    }
    
    // NEW: Helper method to get booking details
    private function getBookingDetails($booking_id) {
        $sql = "SELECT * FROM bookings WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$booking_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
}

?>