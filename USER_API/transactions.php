<?php
header("Content-Type: application/json");
date_default_timezone_set('Asia/Manila');
require_once '../config/config.php';

// // Pagination & Filters
// $limit    = 6;
// $offset   = ($page - 1) * $limit;
// $transactionType = isset($_GET['transactionType']) ? $_GET['transactionType'] : 'all';
// $status   = isset($_GET['status']) ? strtolower($_GET['status']) : 'all';
// $category = isset($_GET['category']) ? strtolower($_GET['category']) : 'all';
// $fromDate = isset($_GET['from']) ? $_GET['from'] : null;
// $toDate   = isset($_GET['to'])   ? $_GET['to']   : null;

// // Validate Status (Matching your ENUM)
// $validStatuses = ['all', 'pending', 'active', 'completed', 'cancelled'];
// if ($status !== 'all' && !in_array($status, $validStatuses)) {
//     http_response_code(400);
//     echo json_encode(["status" => "error", "message" => "Invalid status filter"]);
//     exit;
// }

// if (!in_array($transactionType, ['income', 'expenses', 'refunds', 'all'])) {
//     http_response_code(400);
//     echo json_encode(["status" => "error", "message" => "Invalid Transaction Type filter"]);
//     exit;
// }

// $validCategories = ['all', 'lawn maintenance', 'garden design', 'hardscaping', 'irrigation'];
// if ($category !== 'all' && !in_array($category, $validCategories)) {
//     http_response_code(400);
//     echo json_encode(["status" => "error", "message" => "Invalid category filter"]);
//     exit;
// }
            
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $stmtSummary = $pdo->query("
        WITH MonthlyStats AS (
            SELECT 
                -- CURRENT MONTH: Start of this month to now
                SUM(CASE WHEN type = 'Payment' AND status = 'Completed' 
                    AND transaction_date >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01') THEN amount ELSE 0 END) as curr_rev,
                
                SUM(CASE WHEN type = 'Expense' AND status = 'Completed' 
                    AND transaction_date >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01') THEN amount ELSE 0 END) as curr_exp,
                
                COUNT(CASE WHEN transaction_date >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01') THEN id ELSE NULL END) as curr_trans,
                
                -- PREVIOUS MONTH: Start of last month to end of last month
                SUM(CASE WHEN type = 'Payment' AND status = 'Completed' 
                    AND transaction_date >= DATE_FORMAT(CURRENT_DATE - INTERVAL 1 MONTH, '%Y-%m-01') 
                    AND transaction_date < DATE_FORMAT(CURRENT_DATE, '%Y-%m-01') THEN amount ELSE 0 END) as prev_rev,
                
                SUM(CASE WHEN type = 'Expense' AND status = 'Completed' 
                    AND transaction_date >= DATE_FORMAT(CURRENT_DATE - INTERVAL 1 MONTH, '%Y-%m-01') 
                    AND transaction_date < DATE_FORMAT(CURRENT_DATE, '%Y-%m-01') THEN amount ELSE 0 END) as prev_exp,
                
                COUNT(CASE WHEN transaction_date >= DATE_FORMAT(CURRENT_DATE - INTERVAL 1 MONTH, '%Y-%m-01') 
                    AND transaction_date < DATE_FORMAT(CURRENT_DATE, '%Y-%m-01') THEN id ELSE NULL END) as prev_trans
            FROM transactions
        )
        SELECT 
            -- Raw Values for the Cards
            curr_rev AS revenue_this_month, 
            curr_exp AS expenses_this_month,
            (curr_rev - curr_exp) AS net_profit_this_month,
            curr_trans AS transactions_this_month,
            
            -- Growth Percentages (The "+8%" logic)
            -- Formula: ((Current - Previous) / Previous) * 100
            ROUND(((curr_rev - prev_rev) / NULLIF(prev_rev, 0) * 100), 1) as revenue_growth,
            ROUND(((curr_exp - prev_exp) / NULLIF(prev_exp, 0) * 100), 1) as expense_growth,
            ROUND(((curr_trans - prev_trans) / NULLIF(prev_trans, 0) * 100), 1) as transaction_growth,
            
            -- Profit Growth Logic
            ROUND((((curr_rev - curr_exp) - (prev_rev - prev_exp)) / NULLIF((prev_rev - prev_exp), 0) * 100), 1) as profit_growth
        FROM MonthlyStats;
    ");
    $summaryData = $stmtSummary->fetch(PDO::FETCH_ASSOC);

    echo json_encode($summaryData);

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>