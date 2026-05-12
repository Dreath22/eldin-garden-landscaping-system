-- Appointment Integration Database Migration
-- This script enhances the existing appointment_availability table and adds appointment status tracking

-- Step 1: Add appointment status tracking to bookings table
ALTER TABLE bookings ADD COLUMN appointment_status 
ENUM('Scheduled', 'Confirmed', 'Completed', 'Cancelled', 'Rescheduled') 
DEFAULT 'Scheduled';

-- Step 2: Enhance existing appointment_availability table for history tracking
ALTER TABLE appointment_availability ADD COLUMN booking_id INT AFTER id;
ALTER TABLE appointment_availability ADD COLUMN appointment_status VARCHAR(20) DEFAULT 'available';
ALTER TABLE appointment_availability ADD COLUMN changed_by INT;
ALTER TABLE appointment_availability ADD COLUMN change_reason TEXT;
ALTER TABLE appointment_availability ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Step 3: Add foreign key for booking_id (set to NULL for existing unavailable dates)
ALTER TABLE appointment_availability ADD FOREIGN KEY (booking_id) REFERENCES bookings(id);

-- Step 4: Update existing records to have proper status
UPDATE appointment_availability SET appointment_status = 'unavailable' WHERE status = 'unavailable';
UPDATE appointment_availability SET appointment_status = 'available' WHERE status = 'available';

-- Step 5: Set created_at for existing records
UPDATE appointment_availability SET created_at = NOW() WHERE created_at IS NULL;

-- Step 6: Create index for better performance
CREATE INDEX idx_appointment_availability_booking_id ON appointment_availability(booking_id);
CREATE INDEX idx_appointment_availability_date_status ON appointment_availability(date, status);
CREATE INDEX idx_appointment_availability_changed_by ON appointment_availability(changed_by);

-- Step 7: Update existing bookings to have proper appointment status based on their current status
UPDATE bookings SET appointment_status = 'Scheduled' WHERE status = 'Pending';
UPDATE bookings SET appointment_status = 'Confirmed' WHERE status = 'Active';
UPDATE bookings SET appointment_status = 'Completed' WHERE status = 'Completed';
UPDATE bookings SET appointment_status = 'Cancelled' WHERE status = 'Cancelled';

-- Step 8: Create initial appointment history for existing bookings
INSERT INTO appointment_availability (date, status, booking_id, appointment_status, changed_by, change_reason, reason, created_at)
SELECT 
    DATE(appointment_date) as date,
    'booked' as status,
    id as booking_id,
    appointment_status,
    0 as changed_by, -- System migration
    'Initial appointment status migration' as change_reason,
    CONCAT('Migrated appointment: ', appointment_status) as reason,
    NOW() as created_at
FROM bookings 
WHERE appointment_date IS NOT NULL 
AND id NOT IN (SELECT booking_id FROM appointment_availability WHERE booking_id IS NOT NULL);

-- Migration complete
SELECT 'Appointment integration migration completed successfully' as status;
