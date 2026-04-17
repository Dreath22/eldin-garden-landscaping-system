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

//TODO: 
//      1. cancelled_booking.php
//      2. confimed_bookings.php
//      3. 
//      Done: addbooking.php and bookings.php

header("Content-Type: application/json");
date_default_timezone_set('Asia/Manila');

$data = json_decode(file_get_contents("php://input"), true);

// $pdo is already created by config.php (PDO::ERRMODE_EXCEPTION, FETCH_ASSOC, no emulated prepares)
require_once '../config/config.php';

// ─── ROUTER ───────────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';

$controller = new BookingsController($pdo);

switch ($action) {
    case 'list':
        $controller->list($_GET); // 2. Call as an instance method
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
    default:
        jsonError("Unknown action. Valid actions: list, create, confirm, cancel, get.", 400);
}

class BookingsController
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


    // 1. Fetch List (Replaces bookings.php)
    public function list($params)
    {
        // 1. Inputs & sanitization - use $params instead of $_GET for purity
        $limit = 6;
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $status = $params['status'] ?? 'all';
        $categoryFilter = $params['category'] ?? '0';
        $order = $params['order'] ?? 'newest';
        $fromDate = $params['from'] ?? null;
        $searchTerm = isset($params['search']) ? trim($params['search']) : null;
        $offset = ($page - 1) * $limit;

        // Validate Status
        $validStatuses = ['all', 'pending', 'active', 'completed', 'cancelled'];
        if ($status !== 'all' && !in_array($status, $validStatuses)) {
            jsonError("Invalid status filter");
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

            if ($status !== 'all') {
                $where[] = "B.status = :status";
                $nparams[':status'] = ucfirst($status);
            }

            // Filter by Category via joined Services table
            if ($categoryFilter !== '0' && $categoryFilter !== 'all') {
                $where[] = "S.id = :service_id";
                $nparams[':service_id'] = (int)$categoryFilter;
            }

            if (!empty($fromDate)) {
                $where[] = "B.appointment_date >= :fromDate";
                $nparams[':fromDate'] = $fromDate;
            }

            // Global search functionality with explicit aliases
            if (!empty($searchTerm)) {
                $where[] = "(B.booking_code LIKE :search OR U.name LIKE :search OR U.email LIKE :search)";
                $nparams[':search'] = '%' . $searchTerm . '%';
            }

            $whereSql = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";

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
            $sortOptions = ["newest" => "B.appointment_date DESC", "oldest" => "B.appointment_date ASC"];
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
                WHERE T.type = 'Status Change'
                AND (T.description LIKE '%Initial%' OR T.description LIKE '%Final%')
                GROUP BY T.booking_id
            )
            SELECT 
                B.id AS booking_id, B.booking_code, B.user_id, B.service_id,
                B.appointment_date, B.address, B.status,
                B.created_at, B.notes,
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
                $files_sql = "SELECT booking_id, file_type, file_path, original_name, uploaded_at
                             FROM booking_files 
                             WHERE booking_id IN ($placeholders)
                             ORDER BY booking_id, uploaded_at ASC";
                $files_stmt = $this->pdo->prepare($files_sql);
                $files_stmt->execute($booking_ids);
                $files_rows = $files_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Group files by booking_id
                foreach ($files_rows as $file) {
                    $files_data[$file['booking_id']][] = $file;
                }
            }
            
            $formattedBookings = array_map(function ($b) use ($files_data) {
                // Calculate payment progress percentage with division by zero protection
                $payment_progress_percent = 0.00;
                if ($b['total_booking_cost'] > 0) {
                    $payment_progress_percent = round((($b['amount_paid'] / $b['total_booking_cost']) * 100), 2);
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
                "category" => $b['service_name'] ?? 'N/A',
                "avatar_url" => "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($b['name'] ?? 'User'),
                "name" => $b['name'] ?? 'Unknown User',
                "email" => $b['email'] ?? 'No Email',
                "phone_number" => $b['phone_number'] ?? null,
                "notes" => $b['notes'] ?? null,
                "files" => $files_data[$b['booking_id']] ?? []
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

        }
        catch (\PDOException $e) {
            $this->jsonError("Database error: " . $e->getMessage());
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
            $this->jsonError("Method Not Allowed. Expected POST, got " . $_SERVER['REQUEST_METHOD'], 405);
        }

        // Check if this is multipart/form-data (file upload)
        if (strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
            $this->handleFileUpload();
            return;
        }

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!$data || !isset($data['id'])) {
            $this->jsonError("Missing Booking ID", 400);
        }

        $booking_id = (int)$data['id'];
        $notes = isset($data['notes']) ? htmlspecialchars(strip_tags($data['notes']), ENT_QUOTES, 'UTF-8') : '';

        try {
            $this->pdo->beginTransaction();
            
            // First get current status
            $sql = "SELECT status FROM bookings WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $booking_id]);
            $booking = $stmt->fetch();
            
            if (!$booking) {
                throw new Exception("Booking not found", 404);
            }
            
            $current_status = $booking['status'];
            
            // Determine next status
            $next_status = '';
            $description = '';
            
            switch ($current_status) {
                case 'Pending':
                    $next_status = 'Active';
                    $description = 'Status updated from Pending to Active';
                    break;
                case 'Active':
                    $next_status = 'Completed';
                    $description = 'Status updated from Active to Completed';
                    break;
                case 'Completed':
                case 'Cancelled':
                    throw new Exception("Cannot update status: booking is already " . $current_status, 400);
                default:
                    throw new Exception("Invalid current status: " . $current_status, 400);   
            }
            
            // Update booking status
            $sql_update = "UPDATE bookings 
                    SET status = :status 
                    WHERE id = :id";
            
            $stmt = $this->pdo->prepare($sql_update);
            $stmt->execute([':id' => $booking_id, ':status' => $next_status]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Update failed: Booking status may have been changed by another process.");
            }
            
            // Generate unique transaction code (max 20 chars)
            $transaction_code = 'TXN' . date('ymdHis') . substr(rand(100, 999), 0, 2);
            
            // Determine payment amount based on status transition
            $transaction_amount = 0.00;
            $payment_description = '';
            
            if ($current_status === 'Pending' && $next_status === 'Active') {
                // Initial Payment - use frontend amount provided
                if (!isset($data['amount']) || !is_numeric($data['amount'])) {
                    throw new Exception("Amount is required for Pending to Active transition", 400);
                }
                $transaction_amount = (float)$data['amount'];
                $payment_description = 'Initial Payment';
            } elseif ($current_status === 'Active' && $next_status === 'Completed') {
                // Final Payment - use frontend amount provided
                if (!isset($data['amount']) || !is_numeric($data['amount'])) {
                    throw new Exception("Amount is required for Active to Completed transition", 400);
                }
                $transaction_amount = (float)$data['amount'];
                $payment_description = 'Final Payment';
            }
            
            // Add transaction record
            $sql = "INSERT INTO transactions (transaction_code, booking_id, description, type, status, amount, notes) 
                    VALUES (:transaction_code, :booking_id, :description, :type, :status, :amount, :notes)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':transaction_code' => $transaction_code,
                ':booking_id' => $booking_id,
                ':description' => $payment_description,
                ':type' => 'Status Change',
                ':status' => $next_status,
                ':amount' => $transaction_amount,
                ':notes' => $notes
            ]);
            
            $transaction_id = $this->pdo->lastInsertId();
            
            // Create invoice when booking is confirmed (Pending to Active)
            $invoice_id = null;
            $invoice_number = null;
            if ($current_status === 'Pending' && $next_status === 'Active') {
                // Get booking details for invoice
                $stmt = $this->pdo->prepare("
                    SELECT b.*, u.id as user_id, s.service_name, s.base_price
                    FROM bookings b
                    LEFT JOIN users u ON b.user_id = u.id
                    LEFT JOIN services s ON b.service_id = s.id
                    WHERE b.id = ?
                ");
                $stmt->execute([$booking_id]);
                $booking_details = $stmt->fetch();
                
                if ($booking_details) {
                    // Generate invoice number
                    $prefix = 'INV-' . date('Y');
                    $stmt = $this->pdo->prepare("
                        SELECT invoice_number 
                        FROM invoices 
                        WHERE invoice_number LIKE ? 
                        ORDER BY id DESC 
                        LIMIT 1
                    ");
                    $stmt->execute(["$prefix%"]);
                    $lastInvoice = $stmt->fetch();
                    
                    if ($lastInvoice) {
                        $lastSequence = (int)substr($lastInvoice['invoice_number'], -4);
                        $newSequence = $lastSequence + 1;
                    } else {
                        $newSequence = 1;
                    }
                    
                    $invoice_number = $prefix . '-' . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
                    $invoice_date = date('Y-m-d');
                    $due_date = date('Y-m-d', strtotime('+30 days'));
                    
                    // Calculate amounts
                    $subtotal = $transaction_amount; // Use the payment amount as subtotal
                    $taxRate = 0.12; // 12% tax
                    $tax_amount = $subtotal * $taxRate;
                    $total_amount = $subtotal + $tax_amount;
                    
                    // Create invoice
                    $stmt = $this->pdo->prepare("
                        INSERT INTO invoices (
                            invoice_number, booking_id, user_id, invoice_date, due_date,
                            subtotal, tax_amount, total_amount, status, notes, transaction_id
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'sent', ?, ?)
                    ");
                    
                    $invoice_notes = "Auto-generated invoice for booking: " . $booking_details['service_name'];
                    $stmt->execute([
                        $invoice_number,
                        $booking_id,
                        $booking_details['user_id'],
                        $invoice_date,
                        $due_date,
                        $subtotal,
                        $tax_amount,
                        $total_amount,
                        $invoice_notes,
                        $transaction_id
                    ]);
                    
                    $invoice_id = $this->pdo->lastInsertId();
                    
                    // Update transaction with invoice_id
                    $stmt = $this->pdo->prepare("
                        UPDATE transactions SET invoice_id = ? WHERE id = ?
                    ");
                    $stmt->execute([$invoice_id, $transaction_id]);
                }
            }
            
            $this->pdo->commit();
            
            $response = [
                "status" => "success", 
                "message" => "Booking #" . $booking_id . " status updated from " . $current_status . " to " . $next_status,
                "transaction_code" => $transaction_code
            ];
            
            if ($invoice_id) {
                $response["invoice_id"] = $invoice_id;
                $response["invoice_number"] = $invoice_number;
            }
            
            $this->jsonResponse($response);
            
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $code = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
            error_log("Booking Confirmation Error: " . $e->getMessage());
            $this->jsonError($e->getMessage(), $code);
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
            
            // First get current status
            $sql = "SELECT status FROM bookings WHERE id = :id";
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

            // UPDATE: Only change status, keep the notes column in 'bookings' clean
            $sql_booking = "UPDATE bookings 
                            SET status = 'Cancelled' 
                            WHERE id = :id";

            $stmt = $this->pdo->prepare($sql_booking);
            $stmt->execute([':id' => $booking_id]);

            if ($stmt->rowCount() === 0) {
                throw new Exception("Update failed: Booking status may have been changed by another process.");
            }
                
            // INSERT: Log the cancellation details into your 'transactions' table
            $sql_log = "INSERT INTO transactions 
                        (transaction_code, booking_id, description, type, status, amount, notes) 
                        VALUES (:code, :bid, :desc, 'Status Change', 'Cancelled', 0.00, :notes)";
            
            $logStmt = $this->pdo->prepare($sql_log);
            $logStmt->execute([
                ':code' => $transaction_code,
                ':bid'  => $booking_id,
                ':desc' => $current_status . " to Cancelled",
                ':notes' => $notes
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

    // File Upload Handler for Multi-Stage Upload System with Verification-First Logic
    private function handleFileUpload()
    {
        // 1. CATCH SIZE ERRORS IMMEDIATELY
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
            $max = ini_get('post_max_size');
            $max_mb = round($max / (1024 * 1024), 1);
            $this->jsonError("The total upload size exceeds server limit of {$max_mb}M. Please upload smaller files.", 413);
        }

        if (!isset($_POST['id'])) {
            $this->jsonError("Missing Booking ID. This usually happens if file size is too large for server.", 400);
        }

        $booking_id = (int)$_POST['id'];
        $notes = isset($_POST['notes']) ? htmlspecialchars(strip_tags($_POST['notes']), ENT_QUOTES, 'UTF-8') : '';

        try {
            $this->pdo->beginTransaction();
            
            // Get current booking status and user ID from session
            $sql = "SELECT status, user_id FROM bookings WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $booking_id]);
            $booking = $stmt->fetch();
            
            if (!$booking) {
                throw new Exception("Booking not found", 404);
            }
            
            $current_status = $booking['status'];
            $user_id = $booking['user_id'];
            
            // Determine next status and required files based on current status
            $next_status = '';
            $description = '';
            $required_files = [];
            
            switch ($current_status) {
                case 'Pending':
                    $next_status = 'Active';
                    $description = 'Status updated from Pending to Active';
                    $required_files = ['blueprint', 'quotation', 'agreement'];
                    break;
                case 'Active':
                    $next_status = 'Completed';
                    $description = 'Status updated from Active to Completed';
                    $required_files = ['projectDocumentation'];
                    break;
                case 'Completed':
                case 'Cancelled':
                    throw new Exception("Cannot update status: booking is already " . $current_status, 400);
                default:
                    throw new Exception("Invalid current status: " . $current_status, 400);
            }
            
            // VERIFICATION-FIRST: Check if required files already exist
            $file_check = $this->checkBookingFiles($booking_id, $required_files);
            
            // If files are missing, validate and process uploads
            if (!empty($file_check['missing_files'])) {
                // Validate all required files are present in upload
                foreach ($required_files as $file_key) {
                    if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] !== UPLOAD_ERR_OK) {
                        throw new Exception("Required file missing or upload failed: " . $file_key, 400);
                    }
                }
                
                // Process file uploads
                $uploaded_files = [];
                foreach ($required_files as $file_key) {
                    $file = $_FILES[$file_key];
                    
                    // Validate file with current status context
                    $this->validateFile($file, $file_key, $current_status);
                    
                    // Create upload directory structure using the actual user ID from booking
                    $session_user_id = $user_id;
                    
                    $base_upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads';
                    $user_upload_dir = $base_upload_dir . '/users/' . $session_user_id;
                    $upload_dir = $user_upload_dir . '/' . $booking_id;
                    
                    // Create base directories if they don't exist
                    if (!file_exists($base_upload_dir)) {
                        mkdir($base_upload_dir, 0755, true);
                    }
                    if (!file_exists($user_upload_dir)) {
                        mkdir($user_upload_dir, 0755, true);
                    }
                    if (!file_exists($upload_dir)) {
                        if (!mkdir($upload_dir, 0755, true)) {
                            $error = error_get_last();
                            $error_msg = $error['message'] ?? 'Unknown error';
                            throw new Exception("Failed to create upload directory: {$upload_dir}. Error: {$error_msg}", 500);
                        }
                    }
                    
                    // Generate unique filename
                    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $safe_filename = $file_key . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_extension;
                    $file_path = $upload_dir . '/' . $safe_filename;
                    $relative_path = 'uploads/users/' . $session_user_id . '/' . $booking_id . '/' . $safe_filename;
                    
                    // Move file to destination
                    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                        throw new Exception("Failed to move uploaded file: " . $file_key, 500);
                    }
                    
                    // Save file information to database
                    $this->saveBookingFile($booking_id, $file_key, $relative_path, $file['name']);
                    
                    $uploaded_files[] = [
                        'label' => $file_key,
                        'original_name' => $file['name'],
                        'stored_path' => $relative_path,
                        'size_mb' => round($file['size'] / (1024 * 1024), 2)
                    ];
                }
            } elseif (!empty($file_check['invalid_paths'])) {
                throw new Exception("Some required files have invalid paths: " . implode(', ', $file_check['invalid_paths']), 400);
            }
            
            // Process optional portfolio files if they exist
            if (isset($_FILES['portfolioFiles']) && is_array($_FILES['portfolioFiles']['name'])) {
                $portfolio_files = $this->reArrayFiles($_FILES['portfolioFiles']);
                
                foreach ($portfolio_files as $index => $file) {
                    if ($file['error'] === UPLOAD_ERR_OK) {
                        // Validate portfolio file
                        $this->validateFile($file, 'portfolio', $current_status);
                        
                        // Create upload directory structure using the actual user ID from booking
                        $session_user_id = $user_id;
                        
                        $base_upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads';
                        $user_upload_dir = $base_upload_dir . '/users/' . $session_user_id;
                        $upload_dir = $user_upload_dir . '/' . $booking_id;
                        
                        // Create base directories if they don't exist
                        if (!file_exists($base_upload_dir)) {
                            mkdir($base_upload_dir, 0755, true);
                        }
                        if (!file_exists($user_upload_dir)) {
                            mkdir($user_upload_dir, 0755, true);
                        }
                        if (!file_exists($upload_dir)) {
                            if (!mkdir($upload_dir, 0755, true)) {
                                $error = error_get_last();
                                $error_msg = $error['message'] ?? 'Unknown error';
                                throw new Exception("Failed to create upload directory: {$upload_dir}. Error: {$error_msg}", 500);
                            }
                        }
                        
                        // Generate unique filename
                        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $safe_filename = 'portfolio_' . time() . '_' . $index . '_' . bin2hex(random_bytes(8)) . '.' . $file_extension;
                        $file_path = $upload_dir . '/' . $safe_filename;
                        $relative_path = 'uploads/users/' . $session_user_id . '/' . $booking_id . '/' . $safe_filename;
                        
                        // Move file to destination
                        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                            throw new Exception("Failed to move uploaded portfolio file: " . $file['name'], 500);
                        }
                        
                        // Save file information to database
                        $this->saveBookingFile($booking_id, 'portfolio', $relative_path, $file['name']);
                        
                        $uploaded_files[] = [
                            'label' => 'portfolio',
                            'original_name' => $file['name'],
                            'stored_path' => $relative_path,
                            'size_mb' => round($file['size'] / (1024 * 1024), 2)
                        ];
                    }
                }
            }
            
            // Update booking status
            $sql_update = "UPDATE bookings SET status = :status WHERE id = :id";
            $stmt = $this->pdo->prepare($sql_update);
            $stmt->execute([':id' => $booking_id, ':status' => $next_status]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Update failed: Booking status may have been changed by another process.");
            }
            
            // Generate unique transaction code (max 20 chars)
            $transaction_code = 'TXN' . date('ymdHis') . substr(rand(100, 999), 0, 2);
            
            // Determine payment amount based on status transition
            $transaction_amount = 0.00;
            $payment_description = '';
            
            if ($current_status === 'Pending' && $next_status === 'Active') {
                // Initial Payment - use frontend amount provided
                if (!isset($_POST['amount']) || !is_numeric($_POST['amount'])) {
                    throw new Exception("Amount is required for Pending to Active transition", 400);
                }
                $transaction_amount = (float)$_POST['amount'];
                $payment_description = 'Initial Payment';
            } elseif ($current_status === 'Active' && $next_status === 'Completed') {
                // Final Payment - use frontend amount provided
                if (!isset($_POST['amount']) || !is_numeric($_POST['amount'])) {
                    throw new Exception("Amount is required for Active to Completed transition", 400);
                }
                $transaction_amount = (float)$_POST['amount'];
                $payment_description = 'Final Payment';
            }
            
            // Add transaction record
            $sql = "INSERT INTO transactions (transaction_code, booking_id, description, type, status, amount, notes) 
                    VALUES (:transaction_code, :booking_id, :description, :type, :status, :amount, :notes)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':transaction_code' => $transaction_code,
                ':booking_id' => $booking_id,
                ':description' => $payment_description,
                ':type' => 'Status Change',
                ':status' => $next_status,
                ':amount' => $transaction_amount,
                ':notes' => $notes
            ]);
            
            $this->pdo->commit();
            
            // Get all booking files for response
            $all_files = $this->getBookingFiles($booking_id);
            
            $this->jsonResponse([
                "status" => "success", 
                "message" => "Booking #" . $booking_id . " status updated from " . $current_status . " to " . $next_status,
                "transaction_code" => $transaction_code,
                "uploaded_files" => $uploaded_files ?? [],
                "all_booking_files" => $all_files
            ]);
            
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $code = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
            error_log("File Upload Error: " . $e->getMessage());
            $this->jsonError($e->getMessage(), $code);
        }
    }
    
    /**
     * Check if booking has required files for status transition
     */
    private function checkBookingFiles(int $booking_id, array $required_file_types): array
    {
        $placeholders = str_repeat('?,', count($required_file_types) - 1) . '?';
        $sql = "SELECT file_type, file_path, original_name 
                FROM booking_files 
                WHERE booking_id = ? AND file_type IN ($placeholders)";
        
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
            echo json_encode([
                'success' => true,
                'booking' => $booking,
                'transactions' => $transactions,
                'files' => $files
            ]);

        } catch (Exception $e) {
            error_log("Error fetching booking: " . $e->getMessage());
            $this->jsonError("Failed to fetch booking: " . $e->getMessage(), 500);
        }
    }

    /**
     * Save uploaded file information to database
     */
    private function saveBookingFile(int $booking_id, string $file_type, string $file_path, string $original_name): bool
    {
        $sql = "INSERT INTO booking_files (booking_id, file_type, file_path, original_name) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                file_path = VALUES(file_path), 
                original_name = VALUES(original_name), 
                uploaded_at = CURRENT_TIMESTAMP";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$booking_id, $file_type, $file_path, $original_name]);
    }

    /**
     * Get booking files for display
     */
    private function getBookingFiles(int $booking_id): array
    {
        $sql = "SELECT file_type, file_path, original_name, uploaded_at 
                FROM booking_files 
                WHERE booking_id = ? 
                ORDER BY uploaded_at ASC";
        
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
    
}

?>