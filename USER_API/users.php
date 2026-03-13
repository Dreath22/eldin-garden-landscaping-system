<?php
header("Content-Type: application/json");
date_default_timezone_set('Asia/Manila');

// Ensure config.php exists and establishes the $pdo connection
require_once '../config/config.php';

// 1. Inputs & Sanitization
$limit = 10; 
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$status = $_GET['status'] ?? 'all';
$role = $_GET['role'] ?? 'all';
$order = $_GET['order'] ?? 'newest';
$fromDate = $_GET['from'] ?? null;
$toDate   = $_GET['to']   ?? null;
$offset = ($page - 1) * $limit;

// 2. Validation
if (!in_array($status, ['active', 'pending', 'banned', 'all'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid status filter"]);
    exit;
}
if (!in_array($role, ['admin', 'staff', 'customer', 'all'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid role filter"]);
    exit;
}
if (!in_array($order, ["newest", "oldest", "name-az", "name-za"])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid order filter"]);
    exit;
}

try {
    // 3. Get Global Stats
    $stmtSummary = $pdo->query("
        SELECT 
            COUNT(*) as total_users,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_users,
            SUM(CASE WHEN status = 'banned' THEN 1 ELSE 0 END) as banned_users
        FROM users
    ");
    $summaryData = $stmtSummary->fetch(PDO::FETCH_ASSOC);

    // 4. Build Filtered Query
    $where = [];
    $params = [];

    if ($status !== 'all') {
        $where[] = "status = :status";
        $params[':status'] = $status;
    }
    if ($role !== 'all') {
        $where[] = "role = :role";
        $params[':role'] = ucfirst(strtolower($role)); // Matches "Admin", "Customer", etc.
    }
    if (!empty($fromDate)) {
        $where[] = "joined_date >= :fromDate";
        $params[':fromDate'] = $fromDate;
    }
    if (!empty($toDate)) {
        $where[] = "joined_date <= :toDate";
        $params[':toDate'] = $toDate . " 23:59:59";
    }

    $whereSql = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";

    // 5. Get TOTAL count of filtered results
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $whereSql");
    $countStmt->execute($params);
    $totalFilteredCount = (int)$countStmt->fetchColumn();

    // 6. Get Actual User Data
    $sortOptions = [
        "newest"  => "joined_date DESC",
        "oldest"  => "joined_date ASC",
        "name-az" => "name ASC",
        "name-za" => "name DESC"
    ];
    $orderBy = $sortOptions[$order] ?? "joined_date DESC";
    
    $sql = "SELECT id, name, email, role, joined_date, last_login, status, phone_number, notes
            FROM users $whereSql 
            ORDER BY $orderBy 
            LIMIT :limit OFFSET :offset";
    
    $userStmt = $pdo->prepare($sql);
    
    foreach ($params as $key => $value) {
        $userStmt->bindValue($key, $value);
    }
    $userStmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $userStmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $userStmt->execute();

    $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 7. Map the users (The returned structure you requested)
    $formattedUsers = array_map(function($u) {
        return [
            "id"           => (int)$u['id'],
            "name"         => $u['name'],
            "email"        => $u['email'],
            "role"         => $u['role'],
            "joined_date"  => date("c", strtotime($u['joined_date'])), 
            "last_active"  => $u['last_login'] ? date("c", strtotime($u['last_login'])) : null,
            "status"       => $u['status'],
            "avatar_url"   => "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($u['name']),
            "phone_number" => $u['phone_number'] ?? null,
            "notes"        => $u['notes'] ?? null,
        ];
    }, $users);
    
    // 8. Final Response
    echo json_encode([
        "status" => "success",
        "summary" => [
            "total_users"      => (int)$summaryData['total_users'],
            "active_users"     => (int)($summaryData['active_users'] ?? 0),
            "pending_users"    => (int)($summaryData['pending_users'] ?? 0),
            "banned_users"     => (int)($summaryData['banned_users'] ?? 0),
            "roleFilterValue"  => $totalFilteredCount,
            "total_pages"      => ceil($totalFilteredCount / $limit)
        ],
        "users" => $formattedUsers,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>