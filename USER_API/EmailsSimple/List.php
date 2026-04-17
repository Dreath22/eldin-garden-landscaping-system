<?php
/**
 * Simple Email List with User and Service Relationships
 */

function getEmailsSimple($inputParams, $pdo, $currentUser) {
    // Sanitize inputs
    $currentPage = filter_var($inputParams['currentPage'] ?? 1, FILTER_VALIDATE_INT) ?: 1;
    $currentTab = filter_var($inputParams['currentTab'] ?? 'all', FILTER_SANITIZE_STRING);
    $order = filter_var($inputParams['order'] ?? 'DESC', FILTER_SANITIZE_STRING);
    $limit = filter_var($inputParams['limit'] ?? 6, FILTER_VALIDATE_INT) ?: 6;
    $serviceId = filter_var($inputParams['service_id'] ?? null, FILTER_VALIDATE_INT);
    $offset = ($currentPage - 1) * $limit;

    $whereConditions = [];
    $sqlParams = [];

    // Base query - show all emails for demo (in production, filter by user)
    // if ($currentUser['role'] !== 'Admin') {
    //     $whereConditions[] = "e.user_id = :user_id";
    //     $sqlParams[':user_id'] = $currentUser['id'];
    // }
    $sqlParams = [':user_id' => (int)$currentUser['id']];
    
    // Handle service filtering
    if ($serviceId) {
        $whereConditions[] = "e.service_id = :service_id";
        $sqlParams[':service_id'] = $serviceId;
    }
    
    // Handle tab filtering
    switch ($currentTab) {
        case 'read':
            $whereConditions[] = "e.status = 'read'";
            break;
        case 'unread':
            $whereConditions[] = "e.status = 'unread'";
            break;
        case 'saved':
            $whereConditions[] = "se.user_id = :user_id2";
            $sqlParams[':user_id2'] = (int)$currentUser['id'];
            break;
        // 'all' or any other value - no additional filtering
    }

    // Order by creation date
    $orderBy = " ORDER BY e.created_at " . ($order === 'ASC' ? 'ASC' : 'DESC');

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    try {
        // Get total count
        $countSql = "SELECT COUNT(*) as total 
                    FROM emails e
                    LEFT JOIN saved_emails se ON e.id = se.email_id AND se.user_id = :user_id
                    $whereClause";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($sqlParams);
        $totalRecords = (int)$countStmt->fetchColumn();
        $totalPages = ceil($totalRecords / $limit);
        // Get paginated data with user and service information
        $sql = "SELECT 
                    e.id, 
                    u.name as user_name,
                    u.email as user_email,
                    s.service_name,
                    e.subject, 
                    e.preview, 
                    e.created_at,
                    e.status,
                    e.full_content,
                    u2.name as read_by_name,
                    se.id as save_id,
                    DATE_FORMAT(e.created_at, '%M %d, %Y') as formatted_date,
                    DATE_FORMAT(e.created_at, '%h:%i %p') as formatted_time
                FROM emails e
                LEFT JOIN users u ON e.user_id = u.id
                LEFT JOIN users u2 ON e.read_id = u2.id
                LEFT JOIN services s ON e.service_id = s.id
                LEFT JOIN saved_emails se ON e.id = se.email_id AND se.user_id = :user_id
                $whereClause
                $orderBy 
                LIMIT :limit 
                OFFSET :offset";
        
        $stmt = $pdo->prepare($sql);
        
        // Bind parameters
        foreach ($sqlParams as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format the data for frontend
        $formattedEmails = array_map(function($email) {
            return [
                'id' => (int)$email['id'],
                'sender_name' => $email['user_name'] ?: 'Unknown User',
                'sender_email' => $email['user_email'] ?: 'Unknown Email',
                'service_name' => $email['service_name'] ?? "Custom Service",
                'date' => $email['formatted_date'],
                'time' => $email['formatted_time'],
                'subject' => $email['subject'],
                'preview' => $email['preview'],
                'status' => $email['status'],
                "full_content" => $email['full_content'],
                "read_by_name" => $email['read_by_name'] ?: 'None',
                "save" => $email['save_id'] ? true : false
            ];
        }, $emails);

        // Get summary statistics
        $statsWhere = $currentUser['role'] !== 'Admin' ? "WHERE user_id = " . $currentUser['id'] : "";
        
        $statsSql = "SELECT 
                COUNT(*) as total_emails,
                SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as total_opened,
                SUM(CASE WHEN status = 'unread' THEN 1 ELSE 0 END) as total_unopened
            FROM emails $statsWhere";
        
        $statsStmt = $pdo->query($statsSql);
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

        $summary = [
            'total_emails' => (int)$stats['total_emails'],
            'total_opened' => (int)$stats['total_opened'],
            'total_unopened' => (int)$stats['total_unopened'],
        ];

        // Get services for filtering
        // $servicesSql = "SELECT id, service_name 
        //                FROM services 
        //                ORDER BY service_name";
        // $servicesStmt = $pdo->query($servicesSql);
        // $services = $servicesStmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => [
                'uploads' => $formattedEmails,
                'summary' => $summary,
                // 'services' => $services,
                'pagination' => [
                    'currentPage' => $currentPage,
                    'totalPages' => $totalPages,
                    'totalRecords' => $totalRecords,
                    'limit' => $limit,
                    'hasNextPage' => $currentPage < $totalPages,
                    'hasPrevPage' => $currentPage > 1
                ],
                'user_info' => $currentUser
            ]
        ], JSON_PRETTY_PRINT);

    } catch (PDOException $e) {
        error_log("Simple email list error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}


if (!isset($currentUser)) {
    $currentUser = [
        'id' => 7,

    ];
}
// Execute the function
getEmailsSimple($_GET, $pdo, $currentUser);
?>

