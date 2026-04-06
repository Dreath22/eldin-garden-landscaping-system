<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../utils/sanitizeInput.php';
require_once __DIR__ . '/Summary.php';
/**
 * Fetches a single record by ID
 */
function getIdRecord($id, $pdo) {
    try {
        $sql = "SELECT * FROM services WHERE id = :id LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($record) {
            echo json_encode(['success' => true, 'data' => $record], JSON_PRETTY_PRINT);
        } else {
            echo json_encode(['success' => false, 'error' => 'Record not found'], JSON_PRETTY_PRINT);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Query failed: ' . $e->getMessage()]);
    }
}

/**
 * Lists records with filters and pagination
 */
function listRecords($inputParams, $pdo) {
    // Sanitize inputs using your existing utility
    $currentPage = sanitizeInput($inputParams['currentPage'] ?? 1, 'int') ?: 1;
    $currentTab  = validateInput($inputParams['currentTab'] ?? 'all', ['all', 'active', 'inactive', 'cancelled', 'popular']);
    $order       = validateInput($inputParams['order'] ?? 'DESC', ['DESC', 'ASC']);
    $limit       = sanitizeInput($inputParams['limit'] ?? 6, 'int') ?: 6;
    $offset = ($currentPage - 1) * $limit;

    $whereConditions = [];
    $sqlParams = [];
    $orderBy = "";
    
    if ($currentTab && $currentTab == 'popular') {
        $orderBy = " ORDER BY rating" . $order .", review_count DESC,". $order . " service_name ASC";
    } else {
        $orderBy = " ORDER BY created_at " . $order;
    }

    if ($currentTab && $currentTab !== 'all' && $currentTab !== 'popular') {
        $whereConditions[] = "status = :status";
        $sqlParams[':status'] = $currentTab;
    }


    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    try {
        // 1. Get Total Count Efficiently (Using COUNT(*) instead of fetching all rows)
        $countSql = "SELECT COUNT(*) FROM services $whereClause";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($sqlParams);
        $totalRecords = (int)$countStmt->fetchColumn();
        $totalPages = ceil($totalRecords / $limit);

        

        // 3. Fetch Paginated Data
        $sql = "SELECT * FROM services $whereClause $orderBy LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        
        // Bind filters
        foreach ($sqlParams as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        // Bind pagination (must be INT)
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $summary = summary($pdo);
        echo json_encode([
            'success' => true,
            'data' => [
                'uploads' => $uploads,
                'summary' => $summary,
                'pagination' => [
                    'currentPage'  => (int)$currentPage,
                    'totalPages'   => $totalPages,
                    'totalRecords' => $totalRecords,
                    'limit'        => (int)$limit,
                    'hasNextPage'  => $currentPage < $totalPages,
                    'hasPrevPage'  => $currentPage > 1
                ]
            ]
        ], JSON_PRETTY_PRINT);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

// Execution Logic
if (isset($_GET['id'])) {
    $id = sanitizeInput($_GET['id'], 'int');
    getIdRecord($id, $pdo);
} else {
    // Avoid naming the function 'list' as it is a reserved keyword in some PHP versions
    listRecords($_GET, $pdo);
}
?>