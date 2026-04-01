<?php

$_GET['action'] = 'list';
require_once __DIR__ . '/BookingsController.php';
// header("Content-Type: application/json");
// date_default_timezone_set('Asia/Manila');
// require_once '../config/config.php';

// // Pagination & Filters
// $limit    = 6;
// $page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
// $status   = isset($_GET['status']) ? strtolower($_GET['status']) : 'all';
// $categoryFilter = isset($_GET['category']) ? $_GET['category'] : '0';
// $order    = isset($_GET['order']) ? $_GET['order'] : 'newest';
// $fromDate = isset($_GET['from']) ? $_GET['from'] : null;
// $offset   = ($page - 1) * $limit;

// // Validate Status
// $validStatuses = ['all', 'pending', 'active', 'completed', 'cancelled'];
// if ($status !== 'all' && !in_array($status, $validStatuses)) {
//     http_response_code(400);
//     echo json_encode(["status" => "error", "message" => "Invalid status filter"]);
//     exit;
// }

// try {
//     // 1. Get Global Stats
//     $stmtSummary = $pdo->query("SELECT COUNT(*) as total_bookings, 
//         SUM(status = 'Pending') as pending_count,
//         SUM(status = 'Active') as active_count,
//         SUM(status = 'Completed') as completed_count,
//         SUM(status = 'Cancelled') as cancelled_count 
//         FROM bookings");
//     $summaryData = $stmtSummary->fetch(PDO::FETCH_ASSOC);

//     // 2. Build Query Conditions
//     $where = [];
//     $params = [];

//     if ($status !== 'all') {
//         $where[] = "B.status = :status";
//         $params[':status'] = ucfirst($status);
//     }

//     // Filter by Category via joined Services table
//     if ($categoryFilter !== '0' && $categoryFilter !== 'all') {
//         // We compare against S.id because your data-id is 101, 102, etc.
//         $where[] = "S.id = :service_id";
//         $params[':service_id'] = (int)$categoryFilter;
//     }

//     if (!empty($fromDate)) {
//         $where[] = "B.appointment_date >= :fromDate";
//         $params[':fromDate'] = $fromDate;
//     }

//     $whereSql = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";

//     // 3. Get TOTAL filtered count
//     $countSql = "SELECT COUNT(*) FROM bookings AS B 
//                  INNER JOIN services AS S ON B.service_id = S.id 
//                  $whereSql";
//     $countStmt = $pdo->prepare($countSql);
//     foreach ($params as $key => $val) {
//         $countStmt->bindValue($key, $val);
//     }
//     $countStmt->execute();
//     $totalFilteredCount = (int)$countStmt->fetchColumn();

//     // 4. Get Data with Exact Formatting Requirements
//     $sortOptions = ["newest" => "B.appointment_date DESC", "oldest" => "B.appointment_date ASC"];
//     $orderBy = $sortOptions[$order] ?? "B.appointment_date DESC";

//     $sql = "SELECT 
//             B.id AS booking_id, B.booking_code, B.user_id, B.service_id,
//             B.appointment_date, B.address, B.total_amount, B.status,
//             B.created_at, B.notes, -- Added these
//             S.category, S.service_name,
//             U.name, U.email, U.phone_number -- Added phone_number
//         FROM bookings AS B
//         LEFT JOIN services AS S ON B.service_id = S.id
//         LEFT JOIN users AS U ON B.user_id = U.id
//         $whereSql 
//         ORDER BY $orderBy
//         LIMIT :limit OFFSET :offset";

//     $stmt = $pdo->prepare($sql);
//     foreach ($params as $key => $value) {
//         $stmt->bindValue($key, $value);
//     }
//     $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
//     $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
//     $stmt->execute();

//     $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
//     $formattedBookings = array_map(function ($b) {
//         return [
//         "id" => (int)$b['booking_id'],
//         "booking_code" => $b['booking_code'],
//         "user_id" => (int)$b['user_id'],
//         "service_id" => (int)$b['service_id'],
//         "appointment_date" => date("Y-m-d H:i", strtotime($b['appointment_date'])),
//         "address" => $b['address'],
//         "total_amount" => (float)$b['total_amount'],
//         "status" => $b['status'],
//         "created_at" => date("c", strtotime($b['created_at'])),
//         "category" => $b['service_name'] ?? 'N/A',
//         "avatar_url" => "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($b['name'] ?? 'User'),
//         "name" => $b['name'] ?? 'Unknown User',
//         "email" => $b['email'] ?? 'No Email',
//         "phone_number" => $b['phone_number'] ?? null,
//         "notes" => $b['notes'] ?? null
//         ];
//     }, $bookings);

//     echo json_encode([
//         "status" => "success",
//         "summary" => [
//             "total" => (int)$summaryData['total_bookings'],
//             "pending" => (int)($summaryData['pending_count'] ?? 0),
//             "active" => (int)($summaryData['active_count'] ?? 0),
//             "completed" => (int)($summaryData['completed_count'] ?? 0),
//             "cancelled" => (int)($summaryData['cancelled_count'] ?? 0),
//             "filtered" => $totalFilteredCount,
//             "total_pages" => ceil($totalFilteredCount / $limit)
//         ],
//         "bookings" => $formattedBookings,
//     ]);

// }
// catch (\PDOException $e) {
//     http_response_code(500);
//     echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
// }
?>