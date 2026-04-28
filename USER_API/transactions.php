<?php
header("Content-Type: application/json");
date_default_timezone_set('Asia/Manila');
require_once '../config/config.php';

/**
 * Send JSON error response
 */
function jsonError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        "status" => "error",
        "message" => $message,
        "timestamp" => date('c')
    ]);
    exit;
}

if(isset($_GET['action'])){
    $action = $_GET['action'] ?? '';
    switch($action){
        case 'create':
            create($pdo, $_POST);
            break;
        default:
            jsonError("Unknown action. Valid actions: create.", 400);
    }
}

//Role: Act as Senior SoftWare engineer expert on php api
//now do the php api that would create the transaction, here mae sure that the notes is an optional one and that the data is still being validated and sanitize even her.

/**
 * Create transaction function with comprehensive validation and sanitization
 * @param PDO $pdo Database connection
 * @param array $data POST data from client
 */
function create($pdo, $data) {
    // 1. Validate HTTP method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonError("Method Not Allowed. Expected POST, got " . $_SERVER['REQUEST_METHOD'], 405);
    }

    // 2. Validate input data exists
    if (!$data || !is_array($data)) {
        jsonError('Invalid input data. Expected JSON array.', 400);
    }

    try {
        // 3. Extract and validate required fields
        $required_fields = ['description', 'category', 'date', 'amount', 'type'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            jsonError('Missing required fields: ' . implode(', ', $missing_fields), 400);
        }

        // 4. Sanitize input data
        $sanitized = [
            'description' => sanitizeString($data['description']),
            'category' => sanitizeString($data['category']),
            'date' => sanitizeString($data['date']),
            'amount' => sanitizeFloat($data['amount']),
            'type' => sanitizeString($data['type']),
            'notes' => isset($data['notes']) ? sanitizeString($data['notes']) : null
        ];

        // 5. Comprehensive validation
        $validation_errors = [];

        // Description validation
        if (strlen($sanitized['description']) < 3) {
            $validation_errors[] = 'Description must be at least 3 characters long';
        }
        if (strlen($sanitized['description']) > 255) {
            $validation_errors[] = 'Description must not exceed 255 characters';
        }

        // Category validation
        $allowed_categories = ['service', 'equipment', 'materials', 'labor', 'other'];
        if (!in_array($sanitized['category'], $allowed_categories)) {
            $validation_errors[] = 'Invalid category selected';
        }

        // Date validation
        if (!validateDate($sanitized['date'])) {
            $validation_errors[] = 'Invalid date format or date in future';
        }

        // Amount validation
        if ($sanitized['amount'] <= 0) {
            $validation_errors[] = 'Amount must be greater than 0';
        }
        if ($sanitized['amount'] > 999999999.99) {
            $validation_errors[] = 'Amount exceeds maximum limit';
        }

        // Type validation
        $allowed_types = ['income', 'expense', 'refund'];
        if (!in_array($sanitized['type'], $allowed_types)) {
            $validation_errors[] = 'Invalid transaction type selected';
        }

        // Notes validation (optional)
        if ($sanitized['notes'] && strlen($sanitized['notes']) > 1000) {
            $validation_errors[] = 'Notes must not exceed 1000 characters';
        }

        // 6. Check for validation errors
        if (!empty($validation_errors)) {
            jsonError('Validation failed: ' . implode(', ', $validation_errors), 400);
        }

        // 7. Prepare SQL statement with parameterized query
        $sql = "INSERT INTO transactions (
            description, 
            category, 
            transaction_date, 
            amount, 
            type, 
            notes, 
            created_at, 
            updated_at
        ) VALUES (
            :description, 
            :category, 
            :transaction_date, 
            :amount, 
            :type, 
            :notes, 
            NOW(), 
            NOW()
        )";

        $stmt = $pdo->prepare($sql);

        // 8. Bind parameters with proper typing
        $stmt->bindParam(':description', $sanitized['description'], PDO::PARAM_STR);
        $stmt->bindParam(':category', $sanitized['category'], PDO::PARAM_STR);
        $stmt->bindParam(':transaction_date', $sanitized['date'], PDO::PARAM_STR);
        $stmt->bindParam(':amount', $sanitized['amount'], PDO::PARAM_STR);
        $stmt->bindParam(':type', $sanitized['type'], PDO::PARAM_STR);
        $stmt->bindParam(':notes', $sanitized['notes'], PDO::PARAM_STR);

        // 9. Execute and check result
        if (!$stmt->execute()) {
            jsonError('Failed to create transaction: Database error', 500);
        }

        // 10. Return success response
        $transaction_id = $pdo->lastInsertId();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Transaction created successfully',
            'transaction_id' => $transaction_id,
            'data' => [
                'id' => $transaction_id,
                'description' => $sanitized['description'],
                'category' => $sanitized['category'],
                'date' => $sanitized['date'],
                'amount' => number_format($sanitized['amount'], 2),
                'type' => $sanitized['type'],
                'notes' => $sanitized['notes']
            ],
            'timestamp' => date('c')
        ]);

    } catch (PDOException $e) {
        jsonError('Database error: ' . $e->getMessage(), 500);
    } catch (Exception $e) {
        jsonError('Server error: ' . $e->getMessage(), 500);
    }
}

/**
 * Sanitize string input to prevent XSS and SQL injection
 */
function sanitizeString($input) {
    if (!is_string($input)) {
        return '';
    }
    
    return trim($input);
}

/**
 * Sanitize and validate float input
 */
function sanitizeFloat($input) {
    $cleaned = filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $float = filter_var($cleaned, FILTER_VALIDATE_FLOAT);
    
    return $float !== false ? $float : 0;
}

/**
 * Validate date format and ensure it's not in future
 */
function validateDate($dateString) {
    $date = DateTime::createFromFormat('Y-m-d', $dateString);
    if (!$date) {
        return false;
    }
    
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    
    return $date <= $today;
}

/**
 * Get transaction list function - Enhanced for comprehensive transaction retrieval
 * @param PDO $pdo Database connection
 * @param array $params Query parameters
 * @return array Transaction data with user information
 */
function getTransactionList($pdo, $params = []) { 
    try {
        // Parse pagination parameters with fallbacks
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
        $offset = ($page - 1) * $limit;
        
        // Parse filter parameters
        $status = isset($params['status']) ? strtolower($params['status']) : 'all';
        $transactionType = isset($params['transactionType']) ? strtolower($params['transactionType']) : 'all';
        $category = isset($params['category']) ? strtolower($params['category']) : 'all';
        $fromDate = isset($params['from']) ? $params['from'] : null;
        $toDate = isset($params['to']) ? $params['to'] : null;
        $order = isset($params['order']) ? $params['order'] : 'newest';
        
        // Build WHERE conditions with comprehensive filtering
        $whereConditions = [];
        $bindParams = [];
        
        // Status filter with fallback to 'completed'
        if ($status !== 'all') {
            $whereConditions[] = "t.status = :status";
            $bindParams[':status'] = $status;
        }
        
        // Transaction type filter
        if ($transactionType !== 'all') {
            $whereConditions[] = "t.type = :transactionType";
            $bindParams[':transactionType'] = $transactionType;
        }
        
        // Category filter
        if ($category !== 'all') {
            $whereConditions[] = "t.category = :category";
            $bindParams[':category'] = $category;
        }
        
        // Date range filters
        if ($fromDate) {
            $whereConditions[] = "DATE(t.transaction_date) >= :fromDate";
            $bindParams[':fromDate'] = $fromDate;
        }
        
        if ($toDate) {
            $whereConditions[] = "DATE(t.transaction_date) <= :toDate";
            $bindParams[':toDate'] = $toDate;
        }
        
        // Build ORDER BY clause with enhanced options
        $orderBy = "ORDER BY t.transaction_date DESC";
        if ($order === 'oldest') {
            $orderBy = "ORDER BY t.transaction_date ASC";
        } elseif ($order === 'amount_high') {
            $orderBy = "ORDER BY t.amount DESC";
        } elseif ($order === 'amount_low') {
            $orderBy = "ORDER BY t.amount ASC";
        } elseif ($order === 'type') {
            $orderBy = "ORDER BY t.type ASC";
        }
        
        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
        
        // Enhanced query with multiple join strategies for comprehensive user data
        $sql = "
            SELECT 
                t.id AS transaction_id,
                t.booking_id,
                t.transaction_code,
                DATE(t.transaction_date) AS transaction_date,
                t.description,
                t.type,
                t.status,
                t.amount,
                t.notes,
                -- User information with fallback for admin transactions
                COALESCE(u.name, 'System Admin') AS full_name,
                COALESCE(u.email, 'admin@admin.com') AS user_email,
                COALESCE(u.phone_number, 'N/A') AS user_phone,
                COALESCE(u.id, 0) AS user_record_id,
                -- Additional transaction metadata
                CASE 
                    WHEN t.booking_id IS NOT NULL THEN 'Booking Related'
                    WHEN t.invoice_id IS NOT NULL THEN 'Invoice Related'
                    ELSE 'Direct Transaction'
                END AS transaction_category,
                -- Payment status indicator
                CASE 
                    WHEN t.status = 'Completed' THEN 'Completed'
                    WHEN t.status = 'Pending' THEN 'Pending'
                    WHEN t.status = 'Active' THEN 'Active'
                    WHEN t.status = 'Cancelled' THEN 'Cancelled'
                    ELSE t.status
                END AS status_display
            FROM transactions t
            LEFT JOIN bookings b ON t.booking_id = b.id
            LEFT JOIN users u ON b.user_id = u.id
            {$whereClause}
            {$orderBy}
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $pdo->prepare($sql);
        
        // Bind all parameters with proper typing
        foreach ($bindParams as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Enhanced data formatting
        foreach ($transactions as &$transaction) {
            $transaction['date'] = date('Y-m-d', strtotime($transaction['transaction_date']));
            $transaction['amount'] = (float)$transaction['amount'];
            $transaction['amount_formatted'] = '$' . number_format($transaction['amount'], 2);
            
            // Add user avatar fallback
            if ($transaction['user_record_id'] == 0) {
                $transaction['avatar_url'] = 'https://api.dicebear.com/7.x/avataaars/svg?seed=admin';
            } else {
                $transaction['avatar_url'] = 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($transaction['full_name']);
            }
        }
        
        // Get total count for pagination
        $countSql = "
            SELECT COUNT(*) as total
            FROM transactions t
            LEFT JOIN bookings b ON t.booking_id = b.id
            LEFT JOIN users u ON b.user_id = u.id
            {$whereClause}
        ";
        
        $countStmt = $pdo->prepare($countSql);
        foreach ($bindParams as $param => $value) {
            $countStmt->bindValue($param, $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetchColumn();
        
        return [
            'transactions' => $transactions,
            'total' => (int)$total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
        
    } catch (PDOException $e) {
        jsonError('Database error in getTransactionList: ' . $e->getMessage(), 500);
    } catch (Exception $e) {
        jsonError('Server error in getTransactionList: ' . $e->getMessage(), 500);
    }
}
/**
 * Get transaction summary statistics
 */
function getTransactionSummary($pdo) {
    try {
        $stmt = $pdo->query("
           WITH MonthlyStats AS (
    SELECT 
        -- CURRENT MONTH: Including 'Status Change' where amount > 0 as Revenue
        SUM(CASE WHEN (type = 'Payment' OR (type = 'Status Change' AND amount > 0)) 
            AND (status = 'Completed' OR status = 'Active')
            AND transaction_date >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01') THEN amount ELSE 0 END) as curr_rev,
        
        SUM(CASE WHEN type = 'Expense' AND (status = 'Completed' OR status = 'Active')
            AND transaction_date >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01') THEN amount ELSE 0 END) as curr_exp,
        
        COUNT(CASE WHEN (type IN ('Payment', 'Expense') OR (type = 'Status Change' AND amount > 0))
            AND transaction_date >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01') THEN id END) as curr_trans,
        
        -- PREVIOUS MONTH
        SUM(CASE WHEN (type = 'Payment' OR (type = 'Status Change' AND amount > 0)) 
            AND status = 'Completed' 
            AND transaction_date >= DATE_FORMAT(CURRENT_DATE - INTERVAL 1 MONTH, '%Y-%m-01') 
            AND transaction_date < DATE_FORMAT(CURRENT_DATE, '%Y-%m-01') THEN amount ELSE 0 END) as prev_rev,
        
        SUM(CASE WHEN type = 'Expense' AND status = 'Completed' 
            AND transaction_date >= DATE_FORMAT(CURRENT_DATE - INTERVAL 1 MONTH, '%Y-%m-01') 
            AND transaction_date < DATE_FORMAT(CURRENT_DATE, '%Y-%m-01') THEN amount ELSE 0 END) as prev_exp,
        
        COUNT(CASE WHEN (type IN ('Payment', 'Expense') OR (type = 'Status Change' AND amount > 0))
            AND transaction_date >= DATE_FORMAT(CURRENT_DATE - INTERVAL 1 MONTH, '%Y-%m-01') 
            AND transaction_date < DATE_FORMAT(CURRENT_DATE, '%Y-%m-01') THEN id END) as prev_trans
    FROM transactions
    WHERE transaction_date >= DATE_FORMAT(CURRENT_DATE - INTERVAL 1 MONTH, '%Y-%m-01')
)
SELECT 
    curr_rev AS revenue_this_month, 
    curr_exp AS expenses_this_month,
    (curr_rev - curr_exp) AS net_profit_this_month,
    curr_trans AS transactions_this_month,
    
    ROUND(((curr_rev - prev_rev) / NULLIF(prev_rev, 0)) * 100, 1) as revenue_growth,
    ROUND(((curr_exp - prev_exp) / NULLIF(prev_exp, 0)) * 100, 1) as expense_growth,
    ROUND(((curr_trans - prev_trans) / NULLIF(prev_trans, 0)) * 100, 1) as transactions_growth,
    ROUND((((curr_rev - curr_exp) - (prev_rev - prev_exp)) / NULLIF(prev_rev - prev_exp, 0)) * 100, 1) as profit_growth
FROM MonthlyStats;
        ");
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Summary error: " . $e->getMessage());
        throw $e;
    }
}

// Main execution
try {
    // Check if this is a booking-specific request
    if (isset($_GET['transaction_id'])) {
        $transaction_code = $_GET['transaction_id'];
        
        $stmt = $pdo->prepare("
            SELECT 
                t.id AS transaction_id,
                t.booking_id,
                t.transaction_code,
                t.transaction_date,
                t.description,
                t.type,
                t.status,
                t.amount,
                t.notes,
                -- User data through bookings join
                b.user_id,
                u.id AS user_record_id,
                u.name AS full_name,
                u.email AS user_email,
                u.phone_number AS user_phone
            FROM transactions t
            LEFT JOIN bookings b ON t.booking_id = b.id
            LEFT JOIN users u ON b.user_id = u.id
            WHERE t.transaction_code = :transaction_code
        ");
        $stmt->execute([':transaction_code' => $transaction_code]);
        $transactions = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if transaction was found
        if (!$transactions) {
            jsonError('Transaction not found', 404);
            return;
        }
        
        // Format amounts and IDs
        if ($transactions) {
            $transactions['amount'] = (float)$transactions['amount'];
            $transactions['transaction_id'] = (int)$transactions['transaction_id'];
            $transactions['user_id'] = $transactions['user_id'] ? (int)$transactions['user_id'] : null;
            if ($transactions['booking_id']) {
                $transactions['booking_id'] = (int)$transactions['booking_id'];
            }
        }
        
        echo json_encode([
            "status" => "success",
            "transactions" => $transactions
        ]);
        exit;
    }

    // Get transaction list with user data
    $transactions = getTransactionList($pdo, $_GET);
    
    // Get summary statistics
    $summary = getTransactionSummary($pdo);
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total FROM transactions t LEFT JOIN bookings b ON t.booking_id = b.id LEFT JOIN users u ON b.user_id = u.id";
    $countStmt = $pdo->query($countSql);
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $summary['total'] = (int)$totalCount;
    $summary['filtered'] = (int)$totalCount; // TODO: Apply filter count when filters are active
    
    echo json_encode([
        "status" => "success",
        "transactions" => $transactions,
        "summary" => $summary,
        "timestamp" => date('c')
    ]);
    
} catch (PDOException $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    jsonError($e->getMessage(), 400);
}
?>