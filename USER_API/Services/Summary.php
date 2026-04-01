<?php
/**
 * Get transaction summary statistics
 */
function getTransactionSummary($pdo) {
    try {
        $stmt = $pdo->query("
            WITH MonthlyStats AS (
                SELECT 
                    -- CURRENT MONTH
                    SUM(CASE WHEN type = 'Payment' AND status = 'Completed' 
                        AND transaction_date >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01') THEN amount ELSE 0 END) as curr_rev,
                    
                    SUM(CASE WHEN type = 'Expense' AND status = 'Completed' 
                        AND transaction_date >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01') THEN amount ELSE 0 END) as curr_exp,
                    
                    COUNT(CASE WHEN type IN ('Payment', 'Expense') 
                        AND transaction_date >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01') THEN id ELSE NULL END) as curr_trans,
                    
                    -- PREVIOUS MONTH
                    SUM(CASE WHEN type = 'Payment' AND status = 'Completed' 
                        AND transaction_date >= DATE_FORMAT(CURRENT_DATE - INTERVAL 1 MONTH, '%Y-%m-01') 
                        AND transaction_date < DATE_FORMAT(CURRENT_DATE, '%Y-%m-01') THEN amount ELSE 0 END) as prev_rev,
                    
                    SUM(CASE WHEN type = 'Expense' AND status = 'Completed' 
                        AND transaction_date >= DATE_FORMAT(CURRENT_DATE - INTERVAL 1 MONTH, '%Y-%m-01') 
                        AND transaction_date < DATE_FORMAT(CURRENT_DATE, '%Y-%m-01') THEN amount ELSE 0 END) as prev_exp,
                    
                    COUNT(CASE WHEN type IN ('Payment', 'Expense') 
                        AND transaction_date >= DATE_FORMAT(CURRENT_DATE - INTERVAL 1 MONTH, '%Y-%m-01') 
                        AND transaction_date < DATE_FORMAT(CURRENT_DATE, '%Y-%m-01') THEN id ELSE NULL END) as prev_trans
                FROM transactions
            )
            SELECT 
                curr_rev AS revenue_this_month, 
                curr_exp AS expenses_this_month,
                (curr_rev - curr_exp) AS net_profit_this_month,
                curr_trans AS transactions_this_month,
                
                ROUND(((curr_rev - prev_rev) / NULLIF(prev_rev, 0) * 100), 1) as revenue_growth,
                ROUND(((curr_exp - prev_exp) / NULLIF(prev_exp, 0) * 100), 1) as expense_growth,
                ROUND(((curr_trans - prev_trans) / NULLIF(prev_trans, 0) * 100), 1) as transactions_growth,
                
                ROUND((((curr_rev - curr_exp) - (prev_rev - prev_exp)) / NULLIF((prev_rev - prev_exp), 0) * 100), 1) as profit_growth
            FROM MonthlyStats
        ");
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Summary error: " . $e->getMessage());
        throw $e;
    }
}
?>