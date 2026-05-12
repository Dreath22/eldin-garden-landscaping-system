<?php
/**
 * AppointmentManager Class
 * High-level appointment management with business logic and role separation
 */

require_once __DIR__ . '/AppointmentAvailability.php';

class AppointmentManager {
    private $availability;
    private $pdo;
    
    // Business rules configuration
    const BUSINESS_HOURS_START = '09:00';
    const BUSINESS_HOURS_END = '18:00';
    const BUSINESS_DAYS = [1, 2, 3, 4, 5]; // Monday-Friday (1-7 in PHP)
    const MIN_ADVANCE_HOURS = 24;
    const MAX_ADVANCE_DAYS = 30;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->availability = new AppointmentAvailability($pdo);
    }
    
    /**
     * Admin: Add an unavailable date
     * @param string $date - Date in Y-m-d format
     * @param string $reason - Reason for unavailability
     * @param int $adminId - Admin user ID
     * @return array - Success/failure response
     */
    public function adminAddUnavailableDate($date, $reason, $adminId) {
        // Validate admin permissions (you might want to add role checking here)
        if (!$this->isValidAdmin($adminId)) {
            return ['status' => 'error', 'message' => 'Insufficient permissions.'];
        }
        
        // Additional validation for admin operations
        $validation = $this->validateDateForAdmin($date);
        if ($validation !== true) {
            return ['status' => 'error', 'message' => $validation];
        }
        
        return $this->availability->addUnavailableDate($date, $reason, $adminId);
    }
    
    /**
     * Admin: Remove an unavailable date
     * @param string $date - Date in Y-m-d format
     * @param int $adminId - Admin user ID
     * @return array - Success/failure response
     */
    public function adminRemoveUnavailableDate($date, $adminId) {
        if (!$this->isValidAdmin($adminId)) {
            return ['status' => 'error', 'message' => 'Insufficient permissions.'];
        }
        
        return $this->availability->removeUnavailableDate($date, $adminId);
    }
    
    /**
     * Admin: Update an unavailable date reason
     * @param string $date - Date in Y-m-d format
     * @param string $reason - New reason
     * @param int $adminId - Admin user ID
     * @return array - Success/failure response
     */
    public function adminUpdateUnavailableDate($date, $reason, $adminId) {
        if (!$this->isValidAdmin($adminId)) {
            return ['status' => 'error', 'message' => 'Insufficient permissions.'];
        }
        
        return $this->availability->updateUnavailableDate($date, $reason, $adminId);
    }
    
    /**
     * Client: Request an appointment for a booking
     * @param int $bookingId - Booking ID
     * @param string $requestedDate - Requested date in Y-m-d format
     * @param int $clientId - Client user ID
     * @return array - Success/failure response
     */
    public function clientRequestAppointment($bookingId, $requestedDate, $clientId) {
        try {
            // Validate client permissions
            if (!$this->isValidClient($clientId)) {
                return ['status' => 'error', 'message' => 'Invalid client account.'];
            }
            
            // Validate requested date
            $validation = $this->validateAppointmentDate($requestedDate);
            if ($validation !== true) {
                return ['status' => 'error', 'message' => $validation];
            }
            
            // Check if client owns this booking
            if (!$this->clientOwnsBooking($bookingId, $clientId)) {
                return ['status' => 'error', 'message' => 'Booking not found or access denied.'];
            }
            
            // Check if date is available
            if (!$this->availability->isDateAvailable($requestedDate)) {
                $reason = $this->availability->getUnavailableDate($requestedDate);
                $unavailableReason = $reason ? $reason['reason'] : 'Date is unavailable';
                return [
                    'status' => 'error', 
                    'message' => 'Requested date is not available.',
                    'reason' => $unavailableReason,
                    'suggestions' => $this->suggestAlternativeDates($requestedDate)
                ];
            }
            
            // Get booking_code first
            $bookingCodeSql = "SELECT booking_code FROM bookings WHERE id = ? AND user_id = ? AND status IN ('Consultation', 'Pending')";
            $stmt = $this->pdo->prepare($bookingCodeSql);
            $stmt->execute([$bookingId, $clientId]);
            $booking = $stmt->fetch();
            
            if (!$booking) {
                return ['status' => 'error', 'message' => 'Booking not found or not eligible for appointment scheduling.'];
            }
            
            // Update booking with appointment date
            $sql = "UPDATE bookings SET appointment_date = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ? AND user_id = ? AND status IN ('Consultation', 'Pending')";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$requestedDate, $bookingId, $clientId]);
            
            if ($stmt->rowCount() === 0) {
                return ['status' => 'error', 'message' => 'Unable to update booking. Check booking status and ownership.'];
            }
            
            // Create transaction record
            $transactionCode = 'APT' . date('Y') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $sql = "INSERT INTO transactions (transaction_code, booking_id, booking_code, description, type, status, amount, notes, transaction_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $transactionCode,
                $bookingId,
                $booking['booking_code'],
                "Appointment scheduled for {$requestedDate}",
                'Appointment',
                'Scheduled',
                0,
                "Appointment date set by client"
            ]);
            
            return [
                'status' => 'success', 
                'message' => 'Appointment scheduled successfully!',
                'appointment_date' => $requestedDate,
                'transaction_code' => $transactionCode
            ];
            
        } catch (PDOException $e) {
            error_log("Error scheduling appointment: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Database error occurred.'];
        }
    }
    
    /**
     * Client: Get available dates in a range
     * @param string $startDate - Start date in Y-m-d format
     * @param string $endDate - End date in Y-m-d format
     * @param int $clientId - Client user ID
     * @return array - Available dates
     */
    public function clientGetAvailableDates($startDate, $endDate, $clientId) {
        if (!$this->isValidClient($clientId)) {
            return ['status' => 'error', 'message' => 'Invalid client account.'];
        }
        
        // Validate date range
        if (!$this->isValidDateRange($startDate, $endDate)) {
            return ['status' => 'error', 'message' => 'Invalid date range.'];
        }
        
        // Get all unavailable dates
        $unavailableDates = $this->availability->getUnavailableDates($startDate, $endDate);
        $unavailableDateList = array_column($unavailableDates, 'unavailable_date');
        
        // Generate all dates in range and filter unavailable ones
        $availableDates = [];
        $current = new DateTime($startDate);
        $end = new DateTime($endDate);
        
        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');
            
            // Check if date passes business rules
            if ($this->validateAppointmentDate($dateStr) === true && 
                !in_array($dateStr, $unavailableDateList)) {
                $availableDates[] = [
                    'date' => $dateStr,
                    'day_of_week' => $current->format('l'),
                    'formatted' => $current->format('M j, Y')
                ];
            }
            
            $current->add(new DateInterval('P1D'));
        }
        
        return [
            'status' => 'success',
            'available_dates' => $availableDates,
            'total_available' => count($availableDates),
            'unavailable_dates' => $unavailableDates
        ];
    }
    
    /**
     * Common: Validate appointment date according to business rules
     * @param string $date - Date in Y-m-d format
     * @return bool|string - True if valid, error message if invalid
     */
    public function validateAppointmentDate($date) {
        try {
            $dateObj = new DateTime($date);
            $now = new DateTime();
            $minDate = new DateTime("+" . self::MIN_ADVANCE_HOURS . " hours");
            $maxDate = new DateTime("+" . self::MAX_ADVANCE_DAYS . " days");
            
            // Check if date is in the past
            if ($dateObj < $now) {
                return "Cannot schedule appointments in the past.";
            }
            
            // Check minimum advance notice
            if ($dateObj < $minDate) {
                return "Appointments must be scheduled at least " . self::MIN_ADVANCE_HOURS . " hours in advance.";
            }
            
            // Check maximum advance booking
            if ($dateObj > $maxDate) {
                return "Appointments cannot be scheduled more than " . self::MAX_ADVANCE_DAYS . " days in advance.";
            }
            
            // Check if it's a weekend
            $dayOfWeek = (int)$dateObj->format('N'); // 1 (Monday) to 7 (Sunday)
            if (!in_array($dayOfWeek, self::BUSINESS_DAYS)) {
                return "Appointments are only available on weekdays (Monday-Friday).";
            }
            
            return true;
            
        } catch (Exception $e) {
            return "Invalid date format.";
        }
    }
    
    /**
     * Common: Suggest alternative dates around a requested date
     * @param string $requestedDate - Original requested date
     * @param int $daysForward - Number of days to look forward
     * @return array - Array of suggested dates
     */
    public function suggestAlternativeDates($requestedDate, $daysForward = 7) {
        $suggestions = [];
        $date = new DateTime($requestedDate);
        
        for ($i = 1; $i <= $daysForward; $i++) {
            $date->add(new DateInterval('P1D'));
            $dateStr = $date->format('Y-m-d');
            
            if ($this->validateAppointmentDate($dateStr) === true && 
                $this->availability->isDateAvailable($dateStr)) {
                $suggestions[] = [
                    'date' => $dateStr,
                    'formatted' => $date->format('M j, Y (l)'),
                    'days_from_request' => $i
                ];
                
                // Limit suggestions to 5
                if (count($suggestions) >= 5) {
                    break;
                }
            }
        }
        
        return $suggestions;
    }
    
    /**
     * Common: Check if a specific date is available
     * @param string $date - Date in Y-m-d format
     * @return array - Availability information
     */
    public function checkAvailability($date) {
        $validation = $this->validateAppointmentDate($date);
        $isAvailable = $this->availability->isDateAvailable($date);
        $unavailableInfo = null;
        
        if (!$isAvailable) {
            $unavailableInfo = $this->availability->getUnavailableDate($date);
        }
        
        return [
            'date' => $date,
            'is_available' => $isAvailable && $validation === true,
            'validation_result' => ($isAvailable && $validation === true) ? null : $validation,
            'unavailable_reason' => $unavailableInfo ? $unavailableInfo['reason'] : null,
            'suggestions' => (!$isAvailable || $validation !== true) ? $this->suggestAlternativeDates($date) : []
        ];
    }
    
    /**
     * Helper: Check if user is a valid admin
     * @param int $adminId - User ID
     * @return bool
     */
    private function isValidAdmin($adminId) {
        try {
            $sql = "SELECT id FROM users WHERE id = ? AND role IN ('admin', 'administrator')";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$adminId]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Helper: Check if user is a valid client
     * @param int $clientId - User ID
     * @return bool
     */
    private function isValidClient($clientId) {
        try {
            $sql = "SELECT id FROM users WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$clientId]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Helper: Check if client owns a booking
     * @param int $bookingId - Booking ID
     * @param int $clientId - Client user ID
     * @return bool
     */
    private function clientOwnsBooking($bookingId, $clientId) {
        try {
            $sql = "SELECT id FROM bookings WHERE id = ? AND user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$bookingId, $clientId]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Helper: Validate date for admin operations (less restrictive)
     * @param string $date - Date in Y-m-d format
     * @return bool|string - True if valid, error message if invalid
     */
    private function validateDateForAdmin($date) {
        try {
            $dateObj = new DateTime($date);
            return true;
        } catch (Exception $e) {
            return "Invalid date format. Use Y-m-d format.";
        }
    }
    
    /**
     * Helper: Validate date range
     * @param string $startDate - Start date
     * @param string $endDate - End date
     * @return bool
     */
    private function isValidDateRange($startDate, $endDate) {
        try {
            $start = new DateTime($startDate);
            $end = new DateTime($endDate);
            
            return $start <= $end && 
                   $start >= new DateTime() && 
                   $end <= new DateTime("+" . self::MAX_ADVANCE_DAYS . " days");
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get unavailable dates
     * @param string $startDate - Start date in Y-m-d format (optional)
     * @param string $endDate - End date in Y-m-d format (optional)
     * @return array - Array of unavailable dates with reasons
     */
    public function getUnavailableDates($startDate = null, $endDate = null) {
        return $this->availability->getUnavailableDates($startDate, $endDate);
    }
    
    /**
     * Get business hours configuration
     * @return array - Business hours info
     */
    public function getBusinessHours() {
        return [
            'start' => self::BUSINESS_HOURS_START,
            'end' => self::BUSINESS_HOURS_END,
            'days' => self::BUSINESS_DAYS,
            'min_advance_hours' => self::MIN_ADVANCE_HOURS,
            'max_advance_days' => self::MAX_ADVANCE_DAYS,
            'weekend_policy' => 'Closed on weekends'
        ];
    }
}
?>
