<?php

require_once __DIR__ . '/BookingConfig.php';
require_once __DIR__ . '/BookingCriteria.php';

class BookingRepository {
    
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    public function getSummary(): array {
        $sql = "SELECT 
                COUNT(*) as total_bookings,
                SUM(CASE WHEN status = :pending THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = :active THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN status = :completed THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN status = :cancelled THEN 1 ELSE 0 END) as cancelled_count
                FROM bookings";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':pending' => 'Pending',
                ':active' => 'Active',
                ':completed' => 'Completed',
                ':cancelled' => 'Cancelled'
            ]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Repository getSummary error: " . $e->getMessage());
            return [];
        }
    }
    
    public function countByCriteria(BookingCriteria $criteria): int {
        $where = [];
        $params = [];
        
        if ($criteria->status !== 'all' && $criteria->getValidatedStatus()) {
            $where[] = "B.status = :status";
            $params[':status'] = $criteria->getValidatedStatus();
        }
        
        if ($criteria->category !== '0' && $criteria->category !== 'all') {
            $where[] = "S.id = :service_id";
            $params[':service_id'] = (int)$criteria->category;
        }
        
        if ($criteria->fromDate) {
            $where[] = "B.appointment_date >= :fromDate";
            $params[':fromDate'] = $criteria->fromDate;
        }
        
        if ($criteria->search) {
            $where[] = "(B.booking_code LIKE :search OR U.name LIKE :search OR U.email LIKE :search)";
            $params[':search'] = '%' . $criteria->search . '%';
        }
        
        $whereSql = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";
        
        $sql = "SELECT COUNT(*) FROM bookings AS B 
                INNER JOIN services AS S ON B.service_id = S.id 
                LEFT JOIN users AS U ON B.user_id = U.id
                $whereSql";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Repository countByCriteria error: " . $e->getMessage());
            return 0;
        }
    }
    
    public function findByCriteria(BookingCriteria $criteria): array {
        $where = [];
        $params = [];
        
        if ($criteria->status !== 'all' && $criteria->getValidatedStatus()) {
            $where[] = "B.status = :status";
            $params[':status'] = $criteria->getValidatedStatus();
        }
        
        if ($criteria->category !== '0' && $criteria->category !== 'all') {
            $where[] = "S.id = :service_id";
            $params[':service_id'] = (int)$criteria->category;
        }
        
        if ($criteria->fromDate) {
            $where[] = "B.appointment_date >= :fromDate";
            $params[':fromDate'] = $criteria->fromDate;
        }
        
        if ($criteria->search) {
            $where[] = "(B.booking_code LIKE :search OR U.name LIKE :search OR U.email LIKE :search)";
            $params[':search'] = '%' . $criteria->search . '%';
        }
        
        $whereSql = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";
        $orderBy = $criteria->getValidatedOrder();
        $limit = $criteria->getLimit();
        $offset = $criteria->getOffset();
        
        // Use CTE for transaction aggregates (migrated from original)
        $sql = "WITH transaction_aggregates AS (
                    SELECT 
                        T.booking_id,
                        SUM(CASE WHEN T.description LIKE '%Initial Payment%' THEN T.amount ELSE 0 END) AS initial_amount,
                        SUM(CASE WHEN T.description LIKE '%Final Payment%' THEN T.amount ELSE 0 END) AS final_amount,
                        SUM(CASE WHEN T.description LIKE '%Initial%' OR T.description LIKE '%Final%' THEN T.amount ELSE 0 END) AS total_booking_cost,
                        SUM(CASE WHEN T.status IN ('Active', 'Completed') AND (T.description LIKE '%Initial%' OR T.description LIKE '%Final%') THEN T.amount ELSE 0 END) AS amount_paid
                    FROM transactions T
                    WHERE T.type = 'Status Change'
                    AND (T.description LIKE '%Initial%' OR T.description LIKE '%Final%')
                    GROUP BY T.booking_id
                )
                SELECT 
                    B.id AS booking_id, B.booking_code, B.user_id, B.service_id,
                    B.appointment_date, B.address, B.status,
                    B.created_at, B.notes,
                    COALESCE(TA.initial_amount, 0.00) AS initial_amount,
                    COALESCE(TA.final_amount, 0.00) AS final_amount,
                    COALESCE(TA.total_booking_cost, 0.00) AS total_booking_cost,
                    COALESCE(TA.amount_paid, 0.00) AS amount_paid,
                    CASE 
                        WHEN B.status = 'Cancelled' THEN 
                            COALESCE(TA.amount_paid, 0.00)
                        ELSE 
                            COALESCE(TA.total_booking_cost, 0.00) - COALESCE(TA.amount_paid, 0.00)
                    END AS outstanding_balance,
                    S.name AS service_name,
                    S.base_price AS service_base_price,
                    U.name AS user_name,
                    U.email AS user_email
                FROM bookings AS B
                INNER JOIN services AS S ON B.service_id = S.id
                LEFT JOIN users AS U ON B.user_id = U.id
                LEFT JOIN transaction_aggregates TA ON B.id = TA.booking_id
                $whereSql
                ORDER BY $orderBy
                LIMIT :limit OFFSET :offset";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            
            // Bind regular parameters
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            // Bind pagination parameters
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Repository findByCriteria error: " . $e->getMessage());
            return [];
        }
    }
}
