<?php
/**
 * Get transaction summary statistics
 */


function summary($pdo) {
    try {
        $stmt = $pdo->query('
        SELECT * FROM (
            SELECT 
                COUNT(*) AS total_services,
                SUM(CASE WHEN status="Active" THEN 1 ELSE 0  END) AS live_services,
                SUM(CASE WHEN status="Cancelled" THEN 1 ELSE 0 END) AS cancelled_services,
                SUM(CASE WHEN status="Active" THEN base_price ELSE 0 END) AS total_baseprice
            FROM services)
        AS stats;');
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Summary error: " . $e->getMessage());
        throw $e;
    }
}
?>