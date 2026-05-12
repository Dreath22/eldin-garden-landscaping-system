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
    $currentPage = sanitizeInput($inputParams['currentPage'] ?? 1, 'int') ?: 1;
    $currentTab  = $inputParams['currentTab'] ?? 'all';
    $order       = validateInput($inputParams['order'] ?? 'DESC', ['DESC', 'ASC']);
    $limit       = sanitizeInput($inputParams['limit'] ?? 6, 'int') ?: 6;
    $offset      = ($currentPage - 1) * $limit;

    $whereConditions = [];
    $sqlParams = [];
    
    /**
     * 1. Status Mapping
     * Your DB uses 'Active', 'Inactive', 'Cancelled'.
     * We map the incoming tab to the exact ENUM string.
     */
    if ($currentTab !== 'all' && $currentTab !== 'popular') {
        $statusMap = [
            'active'    => 'Active',
            'inactive'  => 'Inactive',
            'cancelled' => 'Cancelled'
        ];
        
        if (isset($statusMap[$currentTab])) {
            $whereConditions[] = "status = :status";
            $sqlParams[':status'] = $statusMap[$currentTab];
        }
    }

    /**
     * 2. Order By Logic
     */
    if ($currentTab === 'popular') {
        // Sort by highest rating first, then most reviews
        $orderBy = " ORDER BY rating DESC, review_count DESC, service_name ASC";
    } else {
        $orderBy = " ORDER BY created_at $order";
    }

    $whereClause = !empty($whereConditions) ? ' WHERE ' . implode(' AND ', $whereConditions) : '';

    try {
        // Count total records for pagination
        $countSql = "SELECT COUNT(*) FROM services $whereClause";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($sqlParams);
        $totalRecords = (int)$countStmt->fetchColumn();
        $totalPages = ceil($totalRecords / $limit);

        // Fetch the data
        $sql = "SELECT * FROM services $whereClause $orderBy LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        
        foreach ($sqlParams as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        // LIMIT and OFFSET must be integers for PDO
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $summary = summary($pdo);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'uploads' => $uploads,
                'summary' => $summary,
                'pagination' => [
                    'currentPage'  => (int)$currentPage,
                    'totalPages'   => (int)$totalPages,
                    'totalRecords' => (int)$totalRecords,
                    'limit'        => (int)$limit,
                    'hasNextPage'  => $currentPage < $totalPages,
                    'hasPrevPage'  => $currentPage > 1
                ]
            ]
        ], JSON_PRETTY_PRINT);

    } catch (PDOException $e) {
        header('Content-Type: application/json');
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