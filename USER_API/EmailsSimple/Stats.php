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
                            SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_emails,
                            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_emails,
                            SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled_emails,
                            SUM(total_recipients) as total_recipients,
                            SUM(sent_count) as total_sent,
                            SUM(opened_count) as total_opened,
                            SUM(clicked_count) as total_clicked,
                            AVG(CASE 
                                WHEN sent_count > 0 THEN (opened_count / sent_count) * 100 
                                ELSE 0 
                            END) as avg_open_rate,
                            AVG(CASE 
                                WHEN opened_count > 0 THEN (clicked_count / opened_count) * 100 
                                ELSE 0 
                            END) as avg_click_rate
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
                                e.sent_at,
                                e.total_sent,
                                e.opened_count,
                                e.sent_count,
                                CASE 
                                    WHEN e.sent_count > 0 THEN ROUND((e.opened_count / e.sent_count) * 100, 1)
                                    ELSE 0 
                                END as open_rate
                              FROM emails e
                              $recentWhere
                              ORDER BY e.created_at DESC 
                              LIMIT 5";
        
        $activityStmt = $pdo->query($recentActivitySql);
        $recentActivity = $activityStmt->fetchAll(PDO::FETCH_ASSOC);

        $stats = [
            'emails' => [
                'total' => (int)$emailStats['total_emails'],
                'sent' => (int)$emailStats['sent_emails'],
                'drafts' => (int)$emailStats['draft_emails'],
                'scheduled' => (int)$emailStats['scheduled_emails'],
                'total_recipients' => (int)$emailStats['total_recipients'],
                'total_sent' => (int)$emailStats['total_sent'],
                'total_opened' => (int)$emailStats['total_opened'],
                'total_clicked' => (int)$emailStats['total_clicked'],
                'avg_open_rate' => round((float)$emailStats['avg_open_rate'], 1),
                'avg_click_rate' => round((float)$emailStats['avg_click_rate'], 1)
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
                    'sent_at' => $activity['sent_at'],
                    'total_sent' => (int)$activity['total_sent'],
                    'opened' => (int)$activity['opened_count'],
                    'open_rate' => round((float)$activity['open_rate'], 1)
                ];
            }, $recentActivity)
        ];

        echo json_encode([
            'success' => true,
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
