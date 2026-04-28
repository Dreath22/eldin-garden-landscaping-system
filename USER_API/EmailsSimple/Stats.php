<?php
/**
 * Simple Email Statistics
 */

function getEmailStatsSimple($pdo, $currentUser) {
    try {
        // Get email statistics
        $statsWhere = $currentUser['role'] !== 'Admin' ? "WHERE user_id = " . $currentUser['id'] : "";
        
        $emailStatsSql = "SELECT 
                            COUNT(*) as total_emails,
                            SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_emails,
                            SUM(CASE WHEN status = 'unread' THEN 1 ELSE 0 END) as unread_emails,
                            ROUND((SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as avg_open_rate
                        FROM emails 
                        $statsWhere";
        
        $emailStmt = $pdo->query($emailStatsSql);
        $emailStats = $emailStmt->fetch(PDO::FETCH_ASSOC);
        
        // Get service statistics
        $serviceStatsSql = "SELECT 
                              s.id as service_id,
                              s.service_name,
                              COUNT(e.id) as total_emails,
                              SUM(e.sent_count) as total_sent,
                              SUM(e.opened_count) as total_opened,
                              SUM(e.clicked_count) as total_clicked,
                              CASE 
                                  WHEN SUM(e.sent_count) > 0 THEN ROUND((SUM(e.opened_count) / SUM(e.sent_count)) * 100, 2)
                                  ELSE 0 
                              END as open_rate
                          FROM services s
                          LEFT JOIN emails e ON s.id = e.service_id
                          " . ($currentUser['role'] !== 'Admin' ? "WHERE e.user_id = " . $currentUser['id'] : "") . "
                          GROUP BY s.id, s.service_name
                          ORDER BY total_emails DESC";
        
        $serviceStmt = $pdo->query($serviceStatsSql);
        $serviceStats = $serviceStmt->fetchAll(PDO::FETCH_ASSOC);

        // Get recent activity
        $recentWhere = $currentUser['role'] !== 'Admin' ? "WHERE e.user_id = " . $currentUser['id'] : "";
        
        $recentActivitySql = "SELECT 
                                e.subject, 
                                e.created_at,
                                e.status
                              FROM emails e
                              $recentWhere
                              ORDER BY e.created_at DESC 
                              LIMIT 5";
        
        $activityStmt = $pdo->query($recentActivitySql);
        $recentActivity = $activityStmt->fetchAll(PDO::FETCH_ASSOC);

        $stats = [
            'emails' => [
                'total' => (int)$emailStats['total_emails'],
                'read' => (int)$emailStats['read_emails'],
                'unread' => (int)$emailStats['unread_emails'],
                'avg_open_rate' => round((float)$emailStats['avg_open_rate'], 1),
            ],
            'services' => array_map(function($service) {
                return [
                    'service_id' => (int)$service['service_id'],
                    'service_name' => $service['service_name'],
                    'total_emails' => (int)$service['total_emails'],
                    'total_sent' => (int)$service['total_sent'],
                    'total_opened' => (int)$service['total_opened'],
                    'total_clicked' => (int)$service['total_clicked'],
                    'open_rate' => round((float)$service['open_rate'], 1)
                ];
            }, $serviceStats),
            'recent_activity' => array_map(function($activity) {
                return [
                    'subject' => $activity['subject'],
                    'created_at' => $activity['created_at'],
                    'status' => $activity['status']
                ];
            }, $recentActivity)
        ];

        // Debug: Log final stats before response
        error_log("Final Stats Array: " . print_r($stats, true));
        
        echo json_encode([
            'success' => true,
            'total_emails' => $stats['emails']['total'],
            'total_opened' => $stats['emails']['read'],
            'avg_open_rate' => $stats['emails']['avg_open_rate'],
            'data' => $stats,
            'user_info' => $currentUser
        ], JSON_PRETTY_PRINT);

    } catch (PDOException $e) {
        error_log("Email stats error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

// Execute the function
getEmailStatsSimple($pdo, $currentUser);
?>
