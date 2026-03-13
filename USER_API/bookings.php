<?php
header("Content-Type: application/json");
date_default_timezone_set('Asia/Manila');
require_once '../config/config.php';

// Pagination & Filters
$limit    = 6;
$page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$status   = isset($_GET['status']) ? strtolower($_GET['status']) : 'all';
$category = isset($_GET['category']) ? strtolower($_GET['category']) : 'all';
$order    = isset($_GET['order']) ? $_GET['order'] : 'newest';
$fromDate = isset($_GET['from']) ? $_GET['from'] : null;
// $toDate   = isset($_GET['to'])   ? $_GET['to']       : null;
$offset   = ($page - 1) * $limit;

// Validate Status (Matching your ENUM)
$validStatuses = ['all', 'pending', 'active', 'completed', 'cancelled'];
if ($status !== 'all' && !in_array($status, $validStatuses)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid status filter"]);
    exit;
}

$validCategories = ['all', 'lawn maintenance', 'garden design', 'hardscaping', 'irrigation'];
if ($category !== 'all' && !in_array($category, $validCategories)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid category filter"]);
    exit;
}
            
try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // 1. Get Global Stats for Bookings
    $stmtSummary = $pdo->query("
        SELECT 
            COUNT(*) as total_bookings,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_count,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_count
        FROM bookings
    ");
    $summaryData = $stmtSummary->fetch(PDO::FETCH_ASSOC);

    // 2. Build Filtered Query
    $where = [];
    $params = [];

    if ($status !== 'all') {
        $where[] = "B.status = :status";
        $params[':status'] = ucfirst($status); // Match DB Enum case (Pending, etc)
    }
    if($category !== 'all') {
        $where[] = "B.service_id IN (SELECT id FROM services WHERE category = :category)";
        $params[':category'] = ucfirst($category); // Match DB case
    }
    if (!empty($fromDate)) {
        $where[] = "B.appointment_date >= :fromDate";
        $params[':fromDate'] = $fromDate;
    }
    // if (!empty($toDate)) {
    //     $where[] = "appointment_date <= :toDate";
    //     $params[':toDate'] = $toDate . " 23:59:59";
    // }

    $whereSql = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";

    // 3. Get TOTAL count of filtered results
    $countStmt = $pdo->prepare( "SELECT COUNT(*) FROM bookings AS B $whereSql");
    foreach ($params as $key => $val) { $countStmt->bindValue($key, $val); }
    $countStmt->execute();
    $totalFilteredCount = (int)$countStmt->fetchColumn();

    // 4. Get Booking Data
    $sortOptions = [
        "newest" => "appointment_date DESC",
        "oldest" => "appointment_date ASC",
        // "amount-high" => "total_amount DESC",
        // "amount-low"  => "total_amount ASC"
    ];
    $orderBy = $sortOptions[$order] ?? "appointment_date DESC";
    $sql = "SELECT 
            B.id AS booking_id, 
            B.booking_code, 
            B.user_id, 
            B.service_id, 
            B.appointment_date, 
            B.address, 
            B.total_amount, 
            B.status, 
            B.created_at, 
            B.notes,
            B.category,
            U.name,
            U.email,
            U.phone_number
        FROM bookings AS B
        LEFT JOIN users AS U ON B.user_id = U.id
        $whereSql 
        ORDER BY $orderBy
        LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();

    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format response data
    $formattedBookings = array_map(function($b) {
        return [
            "id"               => (int)$b['booking_id'],
            "booking_code"     => $b['booking_code'],
            "user_id"          => (int)$b['user_id'],
            "service_id"       => (int)$b['service_id'],
            "appointment_date" => date("Y-m-d H:i", strtotime($b['appointment_date'])),
            "address"          => $b['address'],
            "total_amount"     => (float)$b['total_amount'],
            "status"           => $b['status'],
            "created_at"       => date("c", strtotime($b['created_at'])),
            "category"         => $b['category'] ?? 'N/A', // Assuming you join with services to get category
            "avatar_url"  => "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($b['name']),
            "name"   => $b['name'],
            "email"       => $b['email'],
            "phone_number" => $b['phone_number'] ?? null,
            "notes"       => $b['notes'] ?? null
        ];
    }, $bookings);
    
    // 5. Final JSON Output
    echo json_encode([
        "status" => "success",
        "summary" => [
            "total" => (int)$summaryData['total_bookings'],
            "pending"     => (int)($summaryData['pending_count'] ?? 0),
            "active"      => (int)($summaryData['active_count'] ?? 0),
            "completed"   => (int)($summaryData['completed_count'] ?? 0),
            "cancelled"   => (int)($summaryData['cancelled_count'] ?? 0),
            "filtered"    => $totalFilteredCount,
            "total_pages" => ceil($totalFilteredCount / $limit)
        ],
        "bookings" => $formattedBookings,
    ]);

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>