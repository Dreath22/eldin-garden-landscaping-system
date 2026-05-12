<?php
/**
 * Appointment Management Modals for Admin Booking Interface
 * Add these modals to your existing admin-bookings.php file
 */

<!-- Appointment Reschedule Modal -->
<div id="rescheduleModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <!-- Content will be populated by JavaScript -->
</div>

<!-- Appointment Details Modal -->
<div id="appointmentDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <!-- Content will be populated by JavaScript -->
</div>

<script>
/**
 * Appointment Modal Functions
 * Add these functions to your existing JavaScript
 */

// Enhanced booking card display with appointment status
function displayBookingWithAppointment(booking) {
    const statusColors = {
        'Scheduled': 'bg-yellow-100 text-yellow-800',
        'Confirmed': 'bg-green-100 text-green-800',
        'Completed': 'bg-blue-100 text-blue-800',
        'Cancelled': 'bg-red-100 text-red-800',
        'Rescheduled': 'bg-purple-100 text-purple-800'
    };
    
    const colorClass = statusColors[booking.appointment_status] || 'bg-gray-100 text-gray-800';
    
    return `
        <div class="booking-card" data-booking-id="${booking.id}">
            <div class="booking-header">
                <div class="booking-info">
                    <h3 class="booking-title">${booking.service_name}</h3>
                    <div class="booking-meta">
                        <span class="booking-code">${booking.booking_code}</span>
                        <span class="booking-status ${colorClass}">
                            <span class="material-symbols-outlined text-sm mr-1">event</span>
                            ${booking.appointment_status}
                        </span>
                    </div>
                </div>
                <div class="booking-actions">
                    ${generateAppointmentActions(booking)}
                </div>
            </div>
            
            <div class="booking-details">
                <div class="detail-item">
                    <span class="material-symbols-outlined">person</span>
                    <span>${booking.name || 'Client'}</span>
                </div>
                <div class="detail-item">
                    <span class="material-symbols-outlined">event</span>
                    <span>${formatAppointmentDateTime(booking.appointment_date)}</span>
                </div>
                <div class="detail-item">
                    <span class="material-symbols-outlined">location_on</span>
                    <span>${booking.address}</span>
                </div>
                ${booking.notes ? `
                    <div class="detail-item">
                        <span class="material-symbols-outlined">note</span>
                        <span>${booking.notes}</span>
                    </div>
                ` : ''}
            </div>
            
            <div class="booking-footer">
                <div class="payment-info">
                    <span class="payment-label">Total Cost:</span>
                    <span class="payment-amount">₱${parseFloat(booking.total_amount || booking.total_cost || 0).toFixed(2)}</span>
                </div>
                <div class="payment-info">
                    <span class="payment-label">Paid:</span>
                    <span class="payment-amount">₱${parseFloat(booking.total_paid_to_date || 0).toFixed(2)}</span>
                </div>
            </div>
        </div>
    `;
}

// Generate appointment action buttons
function generateAppointmentActions(booking) {
    let actions = '';
    
    // Standard booking actions
    actions += `
        <button onclick="editBooking(${booking.id})" class="btn btn-sm btn-secondary">
            <span class="material-symbols-outlined">edit</span>
            Edit
        </button>
    `;
    
    // Appointment-specific actions
    if (booking.appointment_status === 'Scheduled') {
        actions += `
            <button onclick="confirmAppointment(${booking.id})" class="btn btn-sm btn-primary">
                <span class="material-symbols-outlined">check_circle</span>
                Confirm
            </button>
        `;
    }
    
    if (booking.appointment_status !== 'Cancelled' && booking.appointment_status !== 'Completed') {
        actions += `
            <button onclick="rescheduleAppointment(${booking.id})" class="btn btn-sm btn-warning">
                <span class="material-symbols-outlined">calendar_month</span>
                Reschedule
            </button>
        `;
    }
    
    if (booking.appointment_status !== 'Cancelled') {
        actions += `
            <button onclick="cancelAppointment(${booking.id})" class="btn btn-sm btn-danger">
                <span class="material-symbols-outlined">cancel</span>
                Cancel
            </button>
        `;
    }
    
    return actions;
}

// View appointment details
function viewAppointmentDetails(bookingId) {
    const booking = state.allBookings.find(b => b.id === Number(bookingId));
    const docID = document.getElementById('appointmentDetailsModal');
    
    docID.innerHTML = `
        <div class="appointment-modal-content">
            <div class="appointment-modal-header">
                <h2 class="text-2xl font-bold text-gray-900">Appointment Details</h2>
                <button onclick="closeModal('appointmentDetailsModal')" class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <div class="appointment-modal-body">
                <div class="space-y-6">
                    <!-- Service Information -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-900 mb-3">Service Information</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-sm text-gray-600">Service:</span>
                                <p class="font-medium">${booking.service_name}</p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600">Booking Code:</span>
                                <p class="font-medium">${booking.booking_code}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Appointment Information -->
                    <div class="bg-blue-50 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-900 mb-3">Appointment Information</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Date & Time:</span>
                                <span class="font-medium">${formatAppointmentDateTime(booking.appointment_date)}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Status:</span>
                                <span class="appointment-status appointment-status-${booking.appointment_status.toLowerCase()}">
                                    ${booking.appointment_status}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Client Information -->
                    <div class="bg-green-50 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-900 mb-3">Client Information</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Name:</span>
                                <span class="font-medium">${booking.name || 'N/A'}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Email:</span>
                                <span class="font-medium">${booking.email || 'N/A'}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Phone:</span>
                                <span class="font-medium">${booking.phone_number || 'N/A'}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Address:</span>
                                <span class="font-medium">${booking.address}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Information -->
                    <div class="bg-yellow-50 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-900 mb-3">Payment Information</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Total Cost:</span>
                                <span class="font-medium">₱${parseFloat(booking.total_amount || booking.total_cost || 0).toFixed(2)}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Amount Paid:</span>
                                <span class="font-medium">₱${parseFloat(booking.total_paid_to_date || 0).toFixed(2)}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Remaining:</span>
                                <span class="font-medium text-red-600">
                                    ₱${(parseFloat(booking.total_amount || booking.total_cost || 0) - parseFloat(booking.total_paid_to_date || 0)).toFixed(2)}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notes -->
                    ${booking.notes ? `
                        <div class="bg-purple-50 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-900 mb-3">Notes</h3>
                            <p class="text-gray-700">${booking.notes}</p>
                        </div>
                    ` : ''}
                    
                    <!-- Admin Feedback -->
                    ${booking.admin_feedback ? `
                        <div class="bg-orange-50 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-900 mb-3">Admin Feedback</h3>
                            <p class="text-gray-700">${booking.admin_feedback}</p>
                        </div>
                    ` : ''}
                </div>
            </div>
            
            <div class="appointment-modal-footer">
                <button onclick="closeModal('appointmentDetailsModal')" 
                        class="flex-1 bg-gray-200 text-gray-700 py-3 rounded-lg font-medium hover:bg-gray-300 transition-colors">
                    Close
                </button>
                ${generateAppointmentActionButtons(booking)}
            </div>
        </div>
    `;
    
    docID.style.display = 'flex';
}

// Generate action buttons for details modal
function generateAppointmentActionButtons(booking) {
    let buttons = '';
    
    if (booking.appointment_status === 'Scheduled') {
        buttons += `
            <button onclick="confirmAppointmentFromDetails(${booking.id})" 
                    class="flex-1 bg-green-600 text-white py-3 rounded-lg font-medium hover:bg-green-700 transition-colors">
                Confirm Appointment
            </button>
        `;
    }
    
    if (booking.appointment_status !== 'Cancelled' && booking.appointment_status !== 'Completed') {
        buttons += `
            <button onclick="rescheduleAppointment(${booking.id})" 
                    class="flex-1 bg-orange-600 text-white py-3 rounded-lg font-medium hover:bg-orange-700 transition-colors">
                Reschedule
            </button>
        `;
    }
    
    return buttons;
}

// Confirm appointment from details modal
async function confirmAppointmentFromDetails(bookingId) {
    if (!confirm('Are you sure you want to confirm this appointment?')) {
        return;
    }
    
    try {
        const response = await fetch('/landscape/USER_API/BookingsController.php?action=confirm_appointment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                booking_id: bookingId,
                admin_id: getCurrentAdminId()
            })
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
            alert('Appointment confirmed successfully!');
            closeModal('appointmentDetailsModal');
            loadBookings();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Error confirming appointment: ' + error.message);
    }
}

// Cancel appointment
async function cancelAppointment(bookingId) {
    if (!confirm('Are you sure you want to cancel this appointment? This action cannot be undone.')) {
        return;
    }
    
    try {
        const response = await fetch('/landscape/USER_API/BookingsController.php?action=cancel_appointment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                booking_id: bookingId,
                admin_id: getCurrentAdminId()
            })
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
            alert('Appointment cancelled successfully!');
            loadBookings();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Error cancelling appointment: ' + error.message);
    }
}

// Enhanced booking list display with appointment status
function displayBookingsWithAppointments(bookings) {
    const container = document.getElementById('bookingsList');
    
    if (bookings.length === 0) {
        container.innerHTML = `
            <div class="text-center py-12">
                <span class="material-symbols-outlined text-6xl text-gray-400 mb-4">calendar_today</span>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No bookings found</h3>
                <p class="text-gray-500">Get started by creating your first booking.</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = bookings.map(booking => displayBookingWithAppointment(booking)).join('');
}

// Initialize appointment features
document.addEventListener('DOMContentLoaded', () => {
    // Add appointment status filter if needed
    addAppointmentStatusFilter();
    
    // Add appointment search functionality
    addAppointmentSearch();
});

function addAppointmentStatusFilter() {
    const filterContainer = document.querySelector('.booking-filters');
    if (!filterContainer) return;
    
    const statusFilter = `
        <select id="appointment-status-filter" class="form-select">
            <option value="">All Statuses</option>
            <option value="Scheduled">Scheduled</option>
            <option value="Confirmed">Confirmed</option>
            <option value="Completed">Completed</option>
            <option value="Cancelled">Cancelled</option>
            <option value="Rescheduled">Rescheduled</option>
        </select>
    `;
    
    filterContainer.insertAdjacentHTML('beforeend', statusFilter);
    
    document.getElementById('appointment-status-filter').addEventListener('change', (e) => {
        filterBookingsByAppointmentStatus(e.target.value);
    });
}

function filterBookingsByAppointmentStatus(status) {
    const bookingCards = document.querySelectorAll('.booking-card');
    
    bookingCards.forEach(card => {
        const bookingId = card.dataset.bookingId;
        const booking = state.allBookings.find(b => b.id === Number(bookingId));
        
        if (!status || booking.appointment_status === status) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function addAppointmentSearch() {
    const searchInput = document.querySelector('#bookingSearch');
    if (!searchInput) return;
    
    searchInput.addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase();
        searchBookings(searchTerm);
    });
}

function searchBookings(searchTerm) {
    const bookingCards = document.querySelectorAll('.booking-card');
    
    bookingCards.forEach(card => {
        const bookingId = card.dataset.bookingId;
        const booking = state.allBookings.find(b => b.id === Number(bookingId));
        
        const searchableText = [
            booking.service_name,
            booking.booking_code,
            booking.name || '',
            booking.address,
            booking.appointment_status,
            booking.notes || ''
        ].join(' ').toLowerCase();
        
        if (searchableText.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>

<style>
/* Additional styles for appointment modals */
.booking-card {
    @apply bg-white rounded-lg shadow-md border border-gray-200 p-6 mb-4 transition-all duration-200;
}

.booking-card:hover {
    @apply shadow-lg border-gray-300 transform -translate-y-1;
}

.booking-header {
    @apply flex justify-between items-start mb-4;
}

.booking-title {
    @apply text-lg font-semibold text-gray-900;
}

.booking-meta {
    @apply flex items-center gap-2 mt-1;
}

.booking-code {
    @apply text-sm text-gray-500;
}

.booking-actions {
    @apply flex gap-2;
}

.booking-details {
    @apply space-y-2 mb-4;
}

.detail-item {
    @apply flex items-center gap-2 text-sm text-gray-600;
}

.detail-item .material-symbols-outlined {
    @apply text-gray-400;
}

.booking-footer {
    @apply flex justify-between items-center pt-4 border-t border-gray-200;
}

.payment-info {
    @apply text-sm;
}

.payment-label {
    @apply text-gray-600;
}

.payment-amount {
    @apply font-medium text-gray-900;
}

.btn {
    @apply px-3 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-1;
}

.btn-sm {
    @apply px-2 py-1 text-xs;
}

.btn-primary {
    @apply bg-blue-600 text-white hover:bg-blue-700;
}

.btn-secondary {
    @apply bg-gray-200 text-gray-700 hover:bg-gray-300;
}

.btn-warning {
    @apply bg-orange-600 text-white hover:bg-orange-700;
}

.btn-danger {
    @apply bg-red-600 text-white hover:bg-red-700;
}

@media (max-width: 768px) {
    .booking-header {
        @apply flex-col gap-3;
    }
    
    .booking-actions {
        @apply w-full justify-start;
    }
    
    .booking-footer {
        @apply flex-col gap-2;
    }
}
</style>
?>
