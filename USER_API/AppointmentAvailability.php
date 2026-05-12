<?php
/**
 * AppointmentAvailability Class
 * Manages unavailable dates for appointments
 */

class AppointmentAvailability {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Add an unavailable date with reason
     * @param string $date - Date in Y-m-d format
     * @param string $reason - Optional reason for unavailability
     * @param int $createdBy - User ID who created this record
     * @return array - Success/failure response
     */
    public function addUnavailableDate($date, $reason = null, $createdBy = null) {
        try {
            // Validate date format
            if (!$this->isValidDate($date)) {
                return ['status' => 'error', 'message' => 'Invalid date format. Use Y-m-d format.'];
            }
            
            // Check if date already exists and is active
            $existing = $this->getUnavailableDate($date);
            if ($existing && $existing['is_active']) {
                return ['status' => 'error', 'message' => 'Date is already marked as unavailable.'];
            }
            
            // If date exists but is inactive, reactivate it
            if ($existing && !$existing['is_active']) {
                $sql = "UPDATE appointment_availability 
                        SET reason = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP, is_active = TRUE 
                        WHERE unavailable_date = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$reason, $createdBy, $date]);
                
                return ['status' => 'success', 'message' => 'Date reactivated as unavailable.'];
            }
            
            // Insert new unavailable date
            $sql = "INSERT INTO appointment_availability (unavailable_date, reason, created_by) 
                    VALUES (?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$date, $reason, $createdBy]);
            
            return ['status' => 'success', 'message' => 'Unavailable date added successfully.'];
            
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Remove/deactivate an unavailable date
     * @param string $date - Date in Y-m-d format
     * @param int $updatedBy - User ID who updated this record
     * @return array - Success/failure response
     */
    public function removeUnavailableDate($date, $updatedBy = null) {
        try {
            // Validate date format
            if (!$this->isValidDate($date)) {
                return ['status' => 'error', 'message' => 'Invalid date format. Use Y-m-d format.'];
            }
            
            // Check if date exists
            $existing = $this->getUnavailableDate($date);
            if (!$existing) {
                return ['status' => 'error', 'message' => 'Date is not marked as unavailable.'];
            }
            
            if (!$existing['is_active']) {
                return ['status' => 'error', 'message' => 'Date is already deactivated.'];
            }
            
            // Deactivate the date (soft delete)
            $sql = "UPDATE appointment_availability 
                    SET updated_by = ?, updated_at = CURRENT_TIMESTAMP, is_active = FALSE 
                    WHERE unavailable_date = ? AND is_active = TRUE";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$updatedBy, $date]);
            
            if ($stmt->rowCount() === 0) {
                return ['status' => 'error', 'message' => 'No changes made.'];
            }
            
            return ['status' => 'success', 'message' => 'Unavailable date deactivated successfully.'];
            
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update the reason for an unavailable date
     * @param string $date - Date in Y-m-d format
     * @param string $reason - New reason
     * @param int $updatedBy - User ID who updated this record
     * @return array - Success/failure response
     */
    public function updateUnavailableDate($date, $reason, $updatedBy = null) {
        try {
            // Validate date format
            if (!$this->isValidDate($date)) {
                return ['status' => 'error', 'message' => 'Invalid date format. Use Y-m-d format.'];
            }
            
            // Check if date exists and is active
            $existing = $this->getUnavailableDate($date);
            if (!$existing || !$existing['is_active']) {
                return ['status' => 'error', 'message' => 'Date is not currently marked as unavailable.'];
            }
            
            // Update the reason
            $sql = "UPDATE appointment_availability 
                    SET reason = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE unavailable_date = ? AND is_active = TRUE";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$reason, $updatedBy, $date]);
            
            if ($stmt->rowCount() === 0) {
                return ['status' => 'error', 'message' => 'No changes made.'];
            }
            
            return ['status' => 'success', 'message' => 'Reason updated successfully.'];
            
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Check if a specific date is available for appointments
     * @param string $date - Date in Y-m-d format
     * @return bool - True if available, false if unavailable
     */
    public function isDateAvailable($date) {
        try {
            if (!$this->isValidDate($date)) {
                return false;
            }
            
            $sql = "SELECT 
                (SELECT COUNT(*) FROM appointment_availability WHERE unavailable_date = :date1 AND is_active = TRUE) as block_count,
                (SELECT COUNT(*) FROM bookings WHERE DATE(appointment_date) = :date2) as booking_count";
        
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':date1' => $date, ':date2' => $date]);
            $result = $stmt->fetch();

            // The date is available ONLY if both counts are zero
            return ($result['block_count'] == 0 && $result['booking_count'] == 0);
            
        } catch (PDOException $e) {
            error_log("Error checking date availability: " . $e->getMessage());
            return false; 
        }
    }
    
    /**
     * Get unavailable dates within a date range
     * @param string $startDate - Start date in Y-m-d format
     * @param string $endDate - End date in Y-m-d format
     * @return array - Array of unavailable dates with reasons
     */
    public function getUnavailableDates($startDate = null, $endDate = null) {
        try {
            $sql = "SELECT unavailable_date, reason, created_at, created_by, updated_at, updated_by 
                    FROM appointment_availability 
                    WHERE is_active = TRUE";
            
            $params = [];
            
            if ($startDate && $endDate) {
                if (!$this->isValidDate($startDate) || !$this->isValidDate($endDate)) {
                    return [];
                }
                $sql .= " AND unavailable_date BETWEEN ? AND ?";
                $params = [$startDate, $endDate];
            } elseif ($startDate) {
                if (!$this->isValidDate($startDate)) {
                    return [];
                }
                $sql .= " AND unavailable_date >= ?";
                $params = [$startDate];
            }
            
            $sql .= " ORDER BY unavailable_date ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error getting unavailable dates: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get details for a specific unavailable date
     * @param string $date - Date in Y-m-d format
     * @return array|null - Date details or null if not found
     */
    public function getUnavailableDate($date) {
        try {
            if (!$this->isValidDate($date)) {
                return null;
            }
            
            $sql = "SELECT * FROM appointment_availability 
                    WHERE unavailable_date = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$date]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error getting unavailable date: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all unavailable dates (including inactive ones) for admin
     * @return array - All unavailable dates
     */
    public function getAllUnavailableDates() {
        try {
            $sql = "SELECT * FROM appointment_availability 
                    ORDER BY unavailable_date DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error getting all unavailable dates: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Validate date format and ensure it's a valid date
     * @param string $date - Date string to validate
     * @return bool - True if valid, false otherwise
     */
    private function isValidDate($date) {
        if (empty($date)) {
            return false;
        }
        
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Get unavailable dates in a specific month
     * @param int $year - Year
     * @param int $month - Month (1-12)
     * @return array - Array of unavailable dates
     */
    public function getUnavailableDatesInMonth($year, $month) {
        try {
            $startDate = sprintf('%04d-%02d-01', $year, $month);
            $endDate = sprintf('%04d-%02d-%02d', $year, $month, cal_days_in_month(CAL_GREGORIAN, $month, $year));
            
            return $this->getUnavailableDates($startDate, $endDate);
            
        } catch (Exception $e) {
            error_log("Error getting unavailable dates in month: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Bulk add unavailable dates (useful for holidays)
     * @param array $dates - Array of ['date' => 'Y-m-d', 'reason' => 'string']
     * @param int $createdBy - User ID who created these records
     * @return array - Results of bulk operation
     */
    public function bulkAddUnavailableDates($dates, $createdBy = null) {
        $results = ['success' => 0, 'errors' => 0, 'details' => []];
        
        foreach ($dates as $dateInfo) {
            $date = $dateInfo['date'] ?? null;
            $reason = $dateInfo['reason'] ?? null;
            
            $result = $this->addUnavailableDate($date, $reason, $createdBy);
            
            if ($result['status'] === 'success') {
                $results['success']++;
                $results['details'][] = ['date' => $date, 'status' => 'success', 'message' => $result['message']];
            } else {
                $results['errors']++;
                $results['details'][] = ['date' => $date, 'status' => 'error', 'message' => $result['message']];
            }
        }
        
        return $results;
    }
}
?>
