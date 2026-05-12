<?php
// Start session and validate admin access
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if user has admin privileges
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: profile.php');
    exit();
}

// Get current admin ID
$adminId = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EldinGarden</title>
    <link rel="stylesheet" href="admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="/landscape/assets/tailwind.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Public Sans', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .dashboard-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        .dashboard-header h1 {
            color: #1a4d2e;
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
        }
        
        .dashboard-header p {
            color: #6c757d;
            margin: 0.5rem 0 0 0;
            font-size: 1.1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border-left: 4px solid #1a4d2e;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .stat-card h3 {
            color: #1a4d2e;
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .stat-card-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .stat-card-icon.blue { background: #e3f2fd; color: #2196f3; }
        .stat-card-icon.green { background: #e8f5e8; color: #4caf50; }
        .stat-card-icon.orange { background: #fff3e0; color: #ff9800; }
        .stat-card-icon.red { background: #ffebee; color: #f44336; }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin: 0.5rem 0 0 0;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .action-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-decoration: none;
            color: inherit;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            text-decoration: none;
            color: inherit;
        }
        
        .action-card-icon {
            font-size: 3rem;
            color: #1a4d2e;
            margin-bottom: 1rem;
        }
        
        .action-card h3 {
            color: #1a4d2e;
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .action-card p {
            color: #6c757d;
            margin: 0;
            font-size: 0.9rem;
        }
        
        .recent-activity {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .recent-activity h3 {
            color: #1a4d2e;
            margin: 0 0 1rem 0;
            font-size: 1.3rem;
            font-weight: 600;
        }
        
        .activity-item {
            padding: 1rem 0;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 600;
            color: #2c3e50;
            margin: 0 0 0.25rem 0;
        }
        
        .activity-time {
            color: #6c757d;
            font-size: 0.8rem;
            margin: 0;
        }
        
        .loading {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }
        
        .loading i {
            font-size: 2rem;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Admin Dashboard</h1>
            <p>Welcome back! Here's your overview of EldinGarden operations.</p>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-header">
                    <h3>Total Bookings</h3>
                    <div class="stat-card-icon blue">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
                <div class="stat-value" id="totalBookings">-</div>
                <div class="stat-label">All time bookings</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-header">
                    <h3>Active Bookings</h3>
                    <div class="stat-card-icon green">
                        <i class="fas fa-play-circle"></i>
                    </div>
                </div>
                <div class="stat-value" id="activeBookings">-</div>
                <div class="stat-label">Currently active</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-header">
                    <h3>Pending Consultations</h3>
                    <div class="stat-card-icon orange">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="stat-value" id="pendingConsultations">-</div>
                <div class="stat-label">Awaiting consultation</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-header">
                    <h3>Completed Today</h3>
                    <div class="stat-card-icon red">
                        <i class="fas fa-check-double"></i>
                    </div>
                </div>
                <div class="stat-value" id="completedToday">-</div>
                <div class="stat-label">Finished today</div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="admin-bookings.php" class="action-card">
                <div class="action-card-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h3>Manage Bookings</h3>
                <p>View and manage all bookings</p>
            </a>
            
            <a href="admin-bookings.php#consultation" class="action-card">
                <div class="action-card-icon">
                    <i class="fas fa-user-md"></i>
                </div>
                <h3>Consultations</h3>
                <p>Manage consultation status</p>
            </a>
            
            <a href="admin-bookings.php#availability" class="action-card">
                <div class="action-card-icon">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <h3>Availability</h3>
                <p>Manage date availability</p>
            </a>
            
            <a href="profile.php" class="action-card">
                <div class="action-card-icon">
                    <i class="fas fa-user"></i>
                </div>
                <h3>User Profile</h3>
                <p>View your profile</p>
            </a>
        </div>
        
        <!-- Recent Activity -->
        <div class="recent-activity">
            <h3>Recent Activity</h3>
            <div id="recentActivity">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading recent activity...</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Load dashboard statistics
        async function loadDashboardStats() {
            try {
                const response = await fetch('/landscape/USER_API/BookingsController.php?action=list&limit=100');
                const result = await response.json();
                
                if (result.status === 'success') {
                    const bookings = result.bookings || [];
                    
                    // Calculate statistics
                    const totalBookings = bookings.length;
                    const activeBookings = bookings.filter(b => b.status === 'Active').length;
                    const pendingConsultations = bookings.filter(b => !b.notifBoolean).length;
                    
                    // Count completed today
                    const today = new Date().toISOString().split('T')[0];
                    const completedToday = bookings.filter(b => 
                        b.status === 'Completed' && 
                        b.updated_at && 
                        b.updated_at.startsWith(today)
                    ).length;
                    
                    // Update UI
                    document.getElementById('totalBookings').textContent = totalBookings;
                    document.getElementById('activeBookings').textContent = activeBookings;
                    document.getElementById('pendingConsultations').textContent = pendingConsultations;
                    document.getElementById('completedToday').textContent = completedToday;
                    
                    // Load recent activity
                    loadRecentActivity(bookings);
                }
            } catch (error) {
                console.error('Error loading dashboard stats:', error);
            }
        }
        
        // Load recent activity
        function loadRecentActivity(bookings) {
            const activityContainer = document.getElementById('recentActivity');
            
            if (!bookings || bookings.length === 0) {
                activityContainer.innerHTML = '<p style="text-align: center; color: #6c757d;">No recent activity found.</p>';
                return;
            }
            
            // Sort by updated_at date
            const sortedBookings = bookings.sort((a, b) => 
                new Date(b.updated_at || b.created_at) - new Date(a.updated_at || a.created_at)
            );
            
            // Take last 5 activities
            const recentBookings = sortedBookings.slice(0, 5);
            
            let activityHtml = '';
            recentBookings.forEach(booking => {
                const date = new Date(booking.updated_at || booking.created_at);
                const timeAgo = getTimeAgo(date);
                const statusColor = getStatusColor(booking.status);
                const statusIcon = getStatusIcon(booking.status);
                
                activityHtml += `
                    <div class="activity-item">
                        <div class="activity-icon" style="background: ${statusColor}20; color: ${statusColor};">
                            <i class="fas ${statusIcon}"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">Booking ${booking.booking_code} - ${booking.status}</div>
                            <div class="activity-time">${booking.user_name || 'Unknown'} • ${timeAgo}</div>
                        </div>
                    </div>
                `;
            });
            
            activityContainer.innerHTML = activityHtml;
        }
        
        // Get time ago string
        function getTimeAgo(date) {
            const now = new Date();
            const diff = now - date;
            const minutes = Math.floor(diff / 60000);
            const hours = Math.floor(diff / 3600000);
            const days = Math.floor(diff / 86400000);
            
            if (minutes < 1) return 'Just now';
            if (minutes < 60) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
            if (hours < 24) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
            return `${days} day${days > 1 ? 's' : ''} ago`;
        }
        
        // Get status color
        function getStatusColor(status) {
            const colors = {
                'Pending': '#ffc107',
                'Active': '#28a745',
                'Completed': '#17a2b8',
                'Cancelled': '#dc3545',
                'Consultation': '#6c757d'
            };
            return colors[status] || '#6c757d';
        }
        
        // Get status icon
        function getStatusIcon(status) {
            const icons = {
                'Pending': 'fa-clock',
                'Active': 'fa-play-circle',
                'Completed': 'fa-check-circle',
                'Cancelled': 'fa-times-circle',
                'Consultation': 'fa-user-md'
            };
            return icons[status] || 'fa-calendar';
        }
        
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', () => {
            loadDashboardStats();
        });
    </script>
</body>
</html>
