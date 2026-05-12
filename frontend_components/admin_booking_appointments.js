                      /**
 * Appointment Management Functions for Admin Booking Interface
 * Add these functions to your existing admin_booking.js file
 */

// Enhanced confirm function with appointment management
const confirmAppointment = (id) => {
    const booking = state.allBookings.find(b => b.id === Number(id));
    const docID = document.getElementById('confirmModal');
    
    docID.innerHTML = `
        <div class="w-full max-w-2xl bg-white dark:bg-slate-900 rounded-xl shadow-2xl overflow-hidden">
            <div class="p-6 border-b border-slate-100 dark:border-slate-800">
                <h2 class="text-2xl font-bold text-primary">Confirm Booking & Appointment</h2>
                <p class="text-slate-500 dark:text-slate-400 mt-2">Review booking details and confirm appointment</p>
            </div>
            
            <div class="p-6 space-y-6">
                <!-- Existing payment section -->
                <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                        <div class="space-y-1.5">
                            <label for="downpayment-amount" class="text-[10px] font-bold text-slate-500 uppercase px-1">Downpayment (min: ₱${(booking.total_cost * 0.20).toFixed(2)} - max: ₱${booking.total_cost.toFixed(2)})</label>
                            <div class="relative flex items-center">
                                <span class="absolute left-3 text-slate-400 material-symbols-outlined text-base">payments</span>
                                <input id="downpayment-amount" type="number" min="0" max="${booking.total_cost}" step="0.01" placeholder="0.00" class="w-full pl-9 pr-3 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" />
                            </div>
                            <div id="downpayment-error" class="hidden text-xs text-red-500 mt-1">Minimum downpayment required: ₱${(booking.total_cost * 0.20).toFixed(2)}</div>
                        </div>

                        <div class="flex flex-col items-end gap-1">
                            <label class="flex flex-row-reverse items-center gap-3 cursor-pointer group">
                                <input id="initial-checkbox" type="checkbox" class="rounded border-slate-300 text-primary h-5 w-5 md:h-4 md:w-4 transition-all cursor-pointer" />
                                
                                <div class="text-right">
                                    <span class="block text-xs md:text-[11px] font-bold text-slate-700 dark:text-slate-200 uppercase tracking-tight">Verified & Received</span>
                                    <span class="block text-[10px] text-slate-400 leading-none">Confirm payment arrival</span>
                                </div>
                            </label>
                        </div>
                    </div>
                    <button data-type="confirm" onclick="confirmBooking(this, ${booking.id})" class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-3 md:py-2.5 rounded-lg transition-all shadow-md active:scale-95 text-sm uppercase tracking-wide">
                        Confirm & Activate Project
                    </button>
                </div>
                
                <!-- NEW: Appointment confirmation section -->
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                    <h3 class="font-semibold text-blue-800 dark:text-blue-200 mb-4">Appointment Details</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-slate-600 dark:text-slate-400">Scheduled Date:</span>
                            <span class="font-semibold">${formatAppointmentDateTime(booking.appointment_date)}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-slate-600 dark:text-slate-400">Current Status:</span>
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">Scheduled</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-slate-600 dark:text-slate-400">Service:</span>
                            <span class="font-semibold">${booking.service_name}</span>
                        </div>
                    </div>
                    
                    <div class="mt-4 p-3 bg-white dark:bg-slate-800 rounded-lg">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input id="confirm-appointment" type="checkbox" class="rounded border-blue-300 text-blue-600">
                            <span class="text-sm">Confirm appointment and notify client</span>
                        </label>
                    </div>
                </div>
                
                <!-- Action buttons -->
                <div class="flex gap-3">
                    <button onclick="confirmBookingWithAppointment(this, ${booking.id})" 
                            class="flex-1 bg-primary hover:bg-primary/90 text-white font-bold py-3 rounded-lg transition-all">
                        Confirm Booking & Appointment
                    </button>
                    <button onclick="closeModal('confirmModal')" 
                            class="flex-1 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold py-3 rounded-lg transition-all">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    `;
    
    docID.style.display = 'flex';
    
    // Add appointment confirmation validation
    const appointmentCheckbox = document.getElementById('confirm-appointment');
    const confirmButton = docID.querySelector('[onclick*="confirmBookingWithAppointment"]');
    
    function validateAppointmentConfirmation() {
        if (appointmentCheckbox.checked) {
            confirmButton.disabled = false;
            confirmButton.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            confirmButton.disabled = true;
            confirmButton.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }
    
    appointmentCheckbox.addEventListener('change', validateAppointmentConfirmation);
    validateAppointmentConfirmation();
    
    // Initialize payment validation
    initializePaymentValidation();
};

// Confirm booking with appointment
const confirmBookingWithAppointment = async (button, bookingId) => {
    const appointmentCheckbox = document.getElementById('confirm-appointment');
    
    if (!appointmentCheckbox.checked) {
        alert('Please confirm the appointment to proceed');
        return;
    }
    
    try {
        // Show loading state
        button.disabled = true;
        button.innerHTML = 'Processing...';
        
        // First, confirm the appointment
        const appointmentResponse = await fetch('/landscape/USER_API/BookingsController.php?action=confirm_appointment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                booking_id: bookingId,
                admin_id: getCurrentAdminId()
            })
        });
        
        const appointmentResult = await appointmentResponse.json();
        
        if (appointmentResult.status !== 'success') {
            throw new Error(appointmentResult.message);
        }
        
        // Then proceed with normal booking confirmation
        await confirmBooking(button, bookingId);
        
        // Show success message
        alert('Booking and appointment confirmed successfully!');
        closeModal('confirmModal');
        loadBookings(); // Refresh booking list
        
    } catch (error) {
        alert('Error: ' + error.message);
        button.disabled = false;
        button.innerHTML = 'Confirm Booking & Appointment';
    }
};

// Reschedule appointment function
const rescheduleAppointment = (id) => {
    const booking = state.allBookings.find(b => b.id === Number(id));
    const docID = document.getElementById('rescheduleModal');
    
    docID.innerHTML = `
        <div class="w-full max-w-2xl bg-white dark:bg-slate-900 rounded-xl shadow-2xl overflow-hidden">
            <div class="p-6 border-b border-slate-100 dark:border-slate-800">
                <h2 class="text-2xl font-bold text-orange-600">Reschedule Appointment</h2>
                <p class="text-slate-500 dark:text-slate-400 mt-2">Select a new appointment date and time</p>
            </div>
            
            <div class="p-6 space-y-6">
                <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-xl">
                    <h3 class="font-semibold text-orange-800 dark:text-orange-200 mb-3">Current Appointment</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        ${formatAppointmentDateTime(booking.appointment_date)}
                    </p>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            New Appointment Date
                        </label>
                        <input type="date" id="new-appointment-date" class="w-full p-3 border border-slate-300 rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            New Appointment Time
                        </label>
                        <select id="new-appointment-time" class="w-full p-3 border border-slate-300 rounded-lg">
                            <option value="">Select time</option>
                            ${generateAppointmentTimeSlots()}
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Reason for Rescheduling
                        </label>
                        <textarea id="reschedule-reason" rows="3" 
                                  class="w-full p-3 border border-slate-300 rounded-lg"
                                  placeholder="Enter reason for rescheduling..."></textarea>
                    </div>
                </div>
                
                <div class="flex gap-3">
                    <button onclick="submitReschedule(this, ${booking.id})" 
                            class="flex-1 bg-orange-600 hover:bg-orange-700 text-white font-bold py-3 rounded-lg transition-all">
                        Reschedule Appointment
                    </button>
                    <button onclick="closeModal('rescheduleModal')" 
                            class="flex-1 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold py-3 rounded-lg transition-all">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    `;
    
    docID.style.display = 'flex';
    
    // Set minimum date to tomorrow
    const dateInput = document.getElementById('new-appointment-date');
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    dateInput.min = tomorrow.toISOString().split('T')[0];
    
    // Add date validation
    dateInput.addEventListener('change', validateRescheduleDate);
};

// Submit reschedule function
const submitReschedule = async (button, bookingId) => {
    const date = document.getElementById('new-appointment-date').value;
    const time = document.getElementById('new-appointment-time').value;
    const reason = document.getElementById('reschedule-reason').value;
    
    if (!date || !time || !reason) {
        alert('Please fill in all fields');
        return;
    }
    
    const newDateTime = `${date} ${time}:00`;
    
    try {
        button.disabled = true;
        button.innerHTML = 'Processing...';
        
        const response = await fetch('/landscape/USER_API/BookingsController.php?action=reschedule_appointment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                booking_id: bookingId,
                new_appointment_date: newDateTime,
                reason: reason,
                admin_id: getCurrentAdminId()
            })
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
            alert('Appointment rescheduled successfully!');
            closeModal('rescheduleModal');
            loadBookings(); // Refresh booking list
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Error rescheduling appointment: ' + error.message);
    } finally {
        button.disabled = false;
        button.innerHTML = 'Reschedule Appointment';
    }
};

// Helper functions
const generateAppointmentTimeSlots = () => {
    let slots = '<option value="">Select time</option>';
    for (let hour = 9; hour <= 17; hour++) {
        for (let minute = 0; minute < 60; minute += 30) {
            const time = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
            const display = hour > 12 ? `${hour - 12}:${minute.toString().padStart(2, '0')} PM` : `${hour}:${minute.toString().padStart(2, '0')} AM`;
            slots += `<option value="${time}">${display}</option>`;
        }
    }
    return slots;
};

const formatAppointmentDateTime = (dateTime) => {
    const date = new Date(dateTime);
    return date.toLocaleString('en-US', {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getCurrentAdminId = () => {
    // This should get the current admin ID from session or global variable
    return window.currentAdminId || 1; // Fallback to 1 for testing
};

const validateRescheduleDate = () => {
    const dateInput = document.getElementById('new-appointment-date');
    const selectedDate = new Date(dateInput.value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    // Check if date is in the past
    if (selectedDate < today) {
        alert('Please select a future date for rescheduling');
        dateInput.value = '';
        return false;
    }
    
    // Check if date is too far in future (more than 30 days)
    const maxDate = new Date();
    maxDate.setDate(maxDate.getDate() + 30);
    if (selectedDate > maxDate) {
        alert('Appointment cannot be scheduled more than 30 days in advance');
        dateInput.value = '';
        return false;
    }
    
    return true;
};

const closeModal = (modalId) => {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
};

// Add appointment status display function
const displayAppointmentStatus = (booking) => {
    const statusColors = {
        'Scheduled': 'bg-yellow-100 text-yellow-800',
        'Confirmed': 'bg-green-100 text-green-800',
        'Completed': 'bg-blue-100 text-blue-800',
        'Cancelled': 'bg-red-100 text-red-800',
        'Rescheduled': 'bg-purple-100 text-purple-800'
    };
    
    const colorClass = statusColors[booking.appointment_status] || 'bg-gray-100 text-gray-800';
    
    return `
        <div class="mt-2">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colorClass}">
                <span class="material-symbols-outlined text-sm mr-1">event</span>
                Appointment: ${booking.appointment_status}
            </span>
        </div>
    `;
};

// Export functions if using modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        confirmAppointment,
        rescheduleAppointment,
        displayAppointmentStatus,
        formatAppointmentDateTime
    };
}
