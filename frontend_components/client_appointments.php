<?php
/**
 * Client Appointment Management Portal
 * Create this as a new file: client_appointments.php
 */

require_once __DIR__ . '/config/auth_middleware.php';
$sessionData = initStandardSession();

if (!$sessionData['isLoggedIn']) {
    header('Location: login.php');
    exit();
}

$user_id = $sessionData['user']['id'];

// Get user's appointments
$sql = "SELECT b.*, s.service_name 
        FROM bookings b 
        LEFT JOIN services s ON b.service_id = s.id
        WHERE b.user_id = ? 
        ORDER BY b.appointment_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - EldinGarden</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <style>
        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined';
            font-weight: normal;
            font-style: normal;
            font-size: 24px;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
        }
        
        .appointment-card {
            @apply bg-white rounded-lg shadow p-6 border border-gray-200 transition-all duration-200;
        }
        
        .appointment-card:hover {
            @apply shadow-lg border-gray-300 transform -translate-y-1;
        }
        
        .status-badge {
            @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium;
        }
        
        .status-scheduled { @apply bg-yellow-100 text-yellow-800; }
        .status-confirmed { @apply bg-green-100 text-green-800; }
        .status-completed { @apply bg-blue-100 text-blue-800; }
        .status-cancelled { @apply bg-red-100 text-red-800; }
        .status-rescheduled { @apply bg-purple-100 text-purple-800; }
        
        .stat-card {
            @apply bg-white rounded-lg shadow p-6 transition-all duration-200;
        }
        
        .stat-card:hover {
            @apply shadow-lg transform -translate-y-1;
        }
        
        @media (max-width: 768px) {
            .appointment-card {
                @apply p-4;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-semibold text-gray-900">EldinGarden</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="profile.php" class="text-gray-700 hover:text-gray-900">Profile</a>
                    <a href="logout.php" class="text-gray-700 hover:text-gray-900">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto p-6">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">My Appointments</h1>
            <p class="text-gray-600 mt-2">Manage and track your landscaping appointments</p>
        </div>

        <!-- Appointment Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="stat-card">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-full">
                        <span class="material-symbols-outlined text-blue-600">event</span>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Total</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo count($appointments); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-full">
                        <span class="material-symbols-outlined text-yellow-600">schedule</span>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Scheduled</p>
                        <p class="text-2xl font-semibold text-gray-900">
                            <?php echo count(array_filter($appointments, fn($a) => $a['appointment_status'] === 'Scheduled')); ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-full">
                        <span class="material-symbols-outlined text-green-600">check_circle</span>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Confirmed</p>
                        <p class="text-2xl font-semibold text-gray-900">
                            <?php echo count(array_filter($appointments, fn($a) => $a['appointment_status'] === 'Confirmed')); ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="flex items-center">
                    <div class="p-3 bg-gray-100 rounded-full">
                        <span class="material-symbols-outlined text-gray-600">done_all</span>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Completed</p>
                        <p class="text-2xl font-semibold text-gray-900">
                            <?php echo count(array_filter($appointments, fn($a) => $a['appointment_status'] === 'Completed')); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px">
                    <button onclick="filterAppointments('all')" class="filter-tab active px-6 py-3 border-b-2 border-blue-500 text-blue-600 font-medium">
                        All Appointments
                    </button>
                    <button onclick="filterAppointments('upcoming')" class="filter-tab px-6 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium">
                        Upcoming
                    </button>
                    <button onclick="filterAppointments('past')" class="filter-tab px-6 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium">
                        Past
                    </button>
                </nav>
            </div>
        </div>

        <!-- Appointments List -->
        <div class="space-y-4" id="appointments-list">
            <?php if (empty($appointments)): ?>
                <div class="bg-white rounded-lg shadow p-12 text-center">
                    <span class="material-symbols-outlined text-6xl text-gray-400 mb-4">calendar_today</span>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No appointments found</h3>
                    <p class="text-gray-500">You haven't scheduled any appointments yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($appointments as $appointment): ?>
                    <div class="appointment-card" data-status="<?php echo $appointment['appointment_status']; ?>" data-date="<?php echo $appointment['appointment_date']; ?>">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($appointment['service_name']); ?></h3>
                                    <?php
                                    $colorClass = 'status-' . strtolower($appointment['appointment_status']);
                                    ?>
                                    <span class="status-badge <?php echo $colorClass; ?>">
                                        <?php echo $appointment['appointment_status']; ?>
                                    </span>
                                </div>
                                
                                <div class="space-y-2 text-sm text-gray-600">
                                    <div class="flex items-center">
                                        <span class="material-symbols-outlined text-lg mr-2">event</span>
                                        <?php echo date('F j, Y \a\t g:i A', strtotime($appointment['appointment_date'])); ?>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="material-symbols-outlined text-lg mr-2">location_on</span>
                                        <?php echo htmlspecialchars($appointment['address']); ?>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="material-symbols-outlined text-lg mr-2">receipt</span>
                                        Booking: <?php echo htmlspecialchars($appointment['booking_code']); ?>
                                    </div>
                                </div>
                                
                                <?php if ($appointment['notes']): ?>
                                    <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                                        <div class="flex items-start">
                                            <span class="material-symbols-outlined text-lg mr-2 text-gray-500">note</span>
                                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($appointment['notes']); ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($appointment['admin_feedback']): ?>
                                    <div class="mt-3 p-3 bg-blue-50 rounded-lg">
                                        <div class="flex items-start">
                                            <span class="material-symbols-outlined text-lg mr-2 text-blue-600">feedback</span>
                                            <p class="text-sm text-blue-800"><?php echo htmlspecialchars($appointment['admin_feedback']); ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="ml-4 flex flex-col gap-2">
                                <?php if ($appointment['appointment_status'] === 'Scheduled'): ?>
                                    <button onclick="requestReschedule(<?php echo $appointment['id']; ?>)" 
                                            class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                                        <span class="material-symbols-outlined text-lg mr-1">calendar_month</span>
                                        Request Reschedule
                                    </button>
                                <?php endif; ?>
                                
                                <button onclick="viewAppointmentDetails(<?php echo $appointment['id']; ?>)" 
                                        class="px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-300 transition-colors flex items-center">
                                    <span class="material-symbols-outlined text-lg mr-1">visibility</span>
                                    View Details
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reschedule Request Modal -->
    <div id="rescheduleModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Request Reschedule</h3>
                    <button onclick="closeRescheduleModal()" class="text-gray-400 hover:text-gray-600">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                
                <form id="rescheduleForm">
                    <input type="hidden" id="bookingId" name="booking_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Preferred Date</label>
                        <input type="date" name="preferred_date" required 
                               class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Preferred Time</label>
                        <select name="preferred_time" required 
                                class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select time</option>
                            <?php
                            for ($hour = 9; $hour <= 17; $hour++) {
                                for ($minute = 0; $minute < 60; $minute += 30) {
                                    $time = sprintf('%02d:%02d', $hour, $minute);
                                    $display = $hour > 12 ? sprintf('%d:%02d PM', $hour - 12, $minute) : sprintf('%d:%02d AM', $hour, $minute);
                                    echo "<option value=\"$time\">$display</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reason for Reschedule</label>
                        <textarea name="reason" rows="3" required
                                  class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Please explain why you need to reschedule..."></textarea>
                    </div>
                    
                    <div class="flex gap-3">
                        <button type="submit" 
                                class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            Submit Request
                        </button>
                        <button type="button" onclick="closeRescheduleModal()" 
                                class="flex-1 bg-gray-200 text-gray-700 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Client Appointment Management JavaScript
        function requestReschedule(bookingId) {
            document.getElementById('bookingId').value = bookingId;
            document.getElementById('rescheduleModal').style.display = 'flex';
            
            // Set minimum date to tomorrow
            const dateInput = document.querySelector('input[name="preferred_date"]');
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            dateInput.min = tomorrow.toISOString().split('T')[0];
        }
        
        function closeRescheduleModal() {
            document.getElementById('rescheduleModal').style.display = 'none';
            document.getElementById('rescheduleForm').reset();
        }
        
        function viewAppointmentDetails(bookingId) {
            // This could open a detailed view modal
            alert(`View details for appointment ${bookingId}`);
        }
        
        function filterAppointments(filter) {
            const appointments = document.querySelectorAll('.appointment-card');
            const now = new Date();
            
            // Update active tab
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active', 'border-blue-500', 'text-blue-600');
                tab.classList.add('border-transparent', 'text-gray-500');
            });
            event.target.classList.add('active', 'border-blue-500', 'text-blue-600');
            event.target.classList.remove('border-transparent', 'text-gray-500');
            
            appointments.forEach(appointment => {
                const appointmentDate = new Date(appointment.dataset.date);
                let show = false;
                
                switch(filter) {
                    case 'all':
                        show = true;
                        break;
                    case 'upcoming':
                        show = appointmentDate >= now;
                        break;
                    case 'past':
                        show = appointmentDate < now;
                        break;
                }
                
                appointment.style.display = show ? 'block' : 'none';
            });
        }
        
        document.getElementById('rescheduleForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch('/landscape/USER_API/BookingsController.php?action=request_reschedule', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    alert('Reschedule request submitted successfully! We will contact you soon.');
                    closeRescheduleModal();
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Error submitting request: ' + error.message);
            }
        });
        
        // Initialize filter on page load
        document.addEventListener('DOMContentLoaded', () => {
            filterAppointments('all');
        });
    </script>
</body>
</html>
