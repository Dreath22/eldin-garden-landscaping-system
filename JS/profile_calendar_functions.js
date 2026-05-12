/**
 * Minimal Calendar Functions for Profile Page
 * Extracted from admin_booking.js - only essential functions for calendar integration
 */

// Toast system for notifications
const ToastSystem = {
    success: function(message, title = 'Success') {
        console.log('✅ ' + title + ': ' + message);
        // You can enhance this with actual toast UI if needed
    },
    error: function(message, title = 'Error') {
        console.log('❌ ' + title + ': ' + message);
        // You can enhance this with actual toast UI if needed
    }
};

// Function to check if a date is unavailable
window.isDateUnavailable = async function(date) {
  try {
    const response = await fetch('/landscape/USER_API/AppointmentController.php?action=get_unavailable_dates');
    const result = await response.json();
    
    if (result.status === 'success' && result.data) {
      const dateStr = date.toISOString().split('T')[0];
      return result.data.some(unavailableDate => unavailableDate.unavailable_date === dateStr);
    }
    
    return false;
  } catch (error) {
    console.error('Error checking unavailable dates:', error);
    return false;
  }
}

// Function to validate calendar date selection
window.validateCalendarDateSelection = async function(selectedDate, bookingId = null) {
  // Check if date is in the past
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  
  if (selectedDate < today) {
    showModal('error', 'Error', 'Cannot select dates in the past');
    return false;
  }
  
  // Check if date is too far in future (more than 30 days)
  const maxDate = new Date();
  maxDate.setDate(maxDate.getDate() + 30);
  
  if (selectedDate > maxDate) {
    showModal('error', 'Error', 'Cannot select dates more than 30 days in advance');
    return false;
  }
  
  // Check if date is unavailable
  const isUnavailable = await window.isDateUnavailable(selectedDate);
  if (isUnavailable) {
    showModal('error', 'Error', 'Selected date is unavailable. Please choose another date.');
    return false;
  }
  
  return true;
}

// Function to update booking appointment date and availability
window.profileUpdateBookingAppointmentDate = async function(bookingId, appointmentDate) {
  try {
    // First update the booking appointment date
    const bookingResponse = await fetch('/landscape/USER_API/BookingsController.php?action=update_appointment_date', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        booking_id: bookingId,
        appointment_date: appointmentDate.toISOString().slice(0, 19).replace('T', ' ')
      })
    });
    
    const bookingResult = await bookingResponse.json();
    
    if (bookingResult.status === 'success') {
      // Now add to availability table
      const availabilityResponse = await fetch('/landscape/USER_API/AppointmentController.php?action=add_appointment_availability', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          booking_id: bookingId,
          appointment_date: appointmentDate.toISOString().slice(0, 19).replace('T', ' '),
          status: 'booked'
        })
      });
      
      const availabilityResult = await availabilityResponse.json();
      
      if (availabilityResult.status === 'success') {
        showModal('success', 'Success', 'Appointment scheduled successfully! Date saved to both booking and availability records.');
        
        // Update the appointment date field in the profile
        const appointmentDateInput = document.getElementById('appointment_date');
        if (appointmentDateInput) {
          appointmentDateInput.value = appointmentDate.toISOString().slice(0, 16);
        }
        
        // Update the calendar button to show appointment is set
        const calendarBtn = document.getElementById('cal-modal-unique-trigger');
        if (calendarBtn) {
          calendarBtn.innerHTML = `<i class="fas fa-calendar-check"></i> Appointment Set`;
          calendarBtn.classList.add('bg-green-600', 'hover:bg-green-700');
          calendarBtn.classList.remove('bg-blue-600', 'hover:bg-blue-600');
        }
        
      } else {
        showModal('error', 'Warning', 'Booking updated but failed to add to availability: ' + (availabilityResult.message || 'Unknown error'));
      }
      
    } else {
      showModal('error', 'Error', 'Failed to update appointment date: ' + (bookingResult.message || 'Unknown error'));
    }
  } catch (error) {
    console.error('Error updating appointment date:', error);
    showModal('error', 'Error', 'Failed to update appointment date. Please try again.');
  }
}

// Function to enhance calendar modal with unavailable date checking
window.profileEnhanceCalendarModal = function(bookingId = null) {
  // Store the booking ID for this calendar instance
  window.profileCurrentCalendarBookingId = bookingId;
  
  const submitBtn = document.getElementById('cal-modal-unique-submit');
  const selectedText = document.getElementById('cal-modal-unique-selected-text');
  
  if (!submitBtn || !selectedText) return;
  
  // Override the submit button click handler
  const originalClick = submitBtn.onclick;
  
  submitBtn.onclick = async function() {
    if (!window.profileSelectedDate) {
      showModal('error', 'Error', 'Please select a date');
      return;
    }
    
    // Validate the selected date
    const isValid = await window.profileValidateCalendarDateSelection(window.profileSelectedDate, bookingId);
    
    if (!isValid) {
      return; // Validation function will show error message
    }
    
    // If valid, proceed with original functionality
    const result = window.profileSelectedDate.toLocaleDateString(undefined, { 
      weekday: 'long', 
      year: 'numeric', 
      month: 'long', 
      day: 'numeric' 
    });
    
    console.log("Validated Date:", result);
    
    // Show success toast
    const toast = document.getElementById('cal-modal-unique-toast');
    const toastMsg = document.getElementById('cal-modal-unique-toast-msg');
    
    if (toast && toastMsg) {
      toastMsg.innerText = `Appointment scheduled for ${result}`;
      toast.classList.remove('translate-y-20');
      toast.classList.add('translate-y-0');
    }
    
    // Update the appointment date field if this is for a booking
    const currentBookingId = window.profileCurrentCalendarBookingId || bookingId;
    if (currentBookingId) {
      const appointmentDateInput = document.getElementById('appointment_date');
      if (appointmentDateInput) {
        appointmentDateInput.value = window.profileSelectedDate.toISOString().slice(0, 16); // Format for datetime-local input
      }
      
      // Update current consultation ID if available
      if (typeof window.currentConsultationId !== 'undefined') {
        window.currentConsultationId = currentBookingId;
      }
      
      // Trigger appointment date update for the booking
      window.profileUpdateBookingAppointmentDate(currentBookingId, window.profileSelectedDate);
    }
    
    // Close modal after delay
    setTimeout(() => {
      const backdrop = document.getElementById('cal-modal-unique-backdrop');
      if (backdrop) {
        backdrop.classList.add('hidden');
        document.body.style.overflow = '';
      }
      
      if (toast) {
        toast.classList.remove('translate-y-0');
        toast.classList.add('translate-y-20');
      }
    }, 2000);
  };
  
  // Enhance day rendering to show unavailable dates
  const originalRenderCalendar = window.profileRenderCalendar;
  
  window.profileRenderCalendar = async function() {
    // Call original render function first
    originalRenderCalendar();
    
    // Then mark unavailable dates
    const daysContainer = document.getElementById('cal-modal-unique-days-container');
    if (!daysContainer) return;
    
    const year = window.profileCurrentDate.getFullYear();
    const month = window.profileCurrentDate.getMonth();
    
    try {
      // Get unavailable dates
      const response = await fetch('/landscape/USER_API/AppointmentController.php?action=get_unavailable_dates');
      const result = await response.json();
      
      if (result.status === 'success' && result.data) {
        const unavailableDates = result.data.map(item => item.unavailable_date);
        
        // Mark unavailable dates
        const dayElements = daysContainer.querySelectorAll('.cal-modal-unique-day:not(.empty)');
        dayElements.forEach(dayEl => {
          const day = parseInt(dayEl.innerText);
          const dayDate = new Date(year, month, day);
          const dateStr = dayDate.toISOString().split('T')[0];
          
          if (unavailableDates.includes(dateStr)) {
            dayEl.classList.add('unavailable');
            dayEl.style.backgroundColor = '#fee2e2';
            dayEl.style.color = '#991b1b';
            dayEl.style.cursor = 'not-allowed';
            dayEl.onclick = null; // Remove click handler
          }
        });
      }
    } catch (error) {
      console.error('Error loading unavailable dates for calendar:', error);
    }
  };
};

// Function to initialize calendar when modal is shown
window.profileInitCalendarWithUnavailableDates = function(bookingId = null) {
  // Wait for modal to be fully rendered
  setTimeout(() => {
    window.profileEnhanceCalendarModal(bookingId);
    window.profileRenderCalendar(); // Initial render with unavailable dates
  }, 100);
};

// Function to set appointment date from external source
window.profileSetAppointmentDate = function(dateString) {
  const date = new Date(dateString);
  if (!isNaN(date.getTime())) {
    window.profileSelectedDate = date;
    
    // Update calendar display
    window.profileCurrentDate = new Date(date);
    window.profileRenderCalendar();
    
    // Update selected text
    const selectedText = document.getElementById('cal-modal-unique-selected-text');
    if (selectedText) {
      selectedText.innerText = date.toLocaleDateString(undefined, { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
      });
    }
    
    // Enable submit button
    const submitBtn = document.getElementById('cal-modal-unique-submit');
    if (submitBtn) {
      submitBtn.disabled = false;
    }
  }
};
