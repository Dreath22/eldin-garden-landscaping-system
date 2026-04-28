<?php
// DashboardController.php
require_once '../config/config.php';
require_once __DIR__ . '/utils/InputValidator.php';
require_once __DIR__ . '/utils/FileUploadValidator.php';
require_once __DIR__ . '/utils/ApiResponse.php';
require_once __DIR__ . '/utils/SecurityMiddleware.php';

// Start session for CSRF
session_start();

// Initialize security middleware
$security = new SecurityMiddleware();

// Get HTTP method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

// Handle GET method for dashboard stats
if ($method === 'GET') {
    getDashboardStats($pdo, $security);
} else {
    ApiResponse::methodNotAllowed('Only GET method is supported');
}

// Dashboard functions implementation

function getDashboardStats($pdo, $security) {
    try {
        // Validate CSRF token from headers
        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        if (!$csrfToken || !$security->validateCsrf($csrfToken)) {
            ApiResponse::forbidden('Invalid CSRF token');
        }
        
        //Get user information
        
        // Build WHERE clause based on user role
        // $whereClause = $currentUser['role'] !== 'Admin' ? "WHERE user_id = " . $currentUser['id'] : "";
        
        // Get dashboard data from multiple tables
        $dashboardData = [];
        $currentUser['role'] = 'Admin';
        
        // Get total users
        if ($currentUser['role'] === 'Admin') {
            $totalUsersSql = "SELECT COUNT(*) as total_users FROM users";
            $totalUsersStmt = $pdo->query($totalUsersSql);
            $totalUsers = $totalUsersStmt->fetch(PDO::FETCH_ASSOC);
            $dashboardData['total_users'] = (int)$totalUsers['total_users'];
            
            // Get total revenue for this month
            $currentMonth = date('Y-m');
            $revenueSql = "SELECT SUM(amount) as total_revenue 
                          FROM transactions 
                          WHERE status = 'completed' 
                          AND DATE_FORMAT(transaction_date, '%Y-%m') = ?";
            $revenueStmt = $pdo->prepare($revenueSql);
            $revenueStmt->execute([$currentMonth]);
            $revenue = $revenueStmt->fetch(PDO::FETCH_ASSOC);
            $dashboardData['monthly_revenue'] = (float)$revenue['total_revenue'] ?: 0;
            
            // Get bookings with active status
            $activeBookingsSql = "SELECT COUNT(*) as active_bookings 
                                  FROM bookings 
                                  WHERE status IN ('confirmed', 'active', 'in_progress')";
            $activeBookingsStmt = $pdo->query($activeBookingsSql);
            $activeBookings = $activeBookingsStmt->fetch(PDO::FETCH_ASSOC);
            $dashboardData['active_bookings'] = (int)$activeBookings['active_bookings'];
            
            // Get bookings with pending status
            $pendingBookingsSql = "SELECT COUNT(*) as pending_bookings 
                                  FROM bookings 
                                  WHERE status = 'pending'";
            $pendingBookingsStmt = $pdo->query($pendingBookingsSql);
            $pendingBookings = $pendingBookingsStmt->fetch(PDO::FETCH_ASSOC);
            $dashboardData['pending_bookings'] = (int)$pendingBookings['pending_bookings'];
            
            // Get recent users (new to old)
            $recentUsersSql = "SELECT id, name, email, joined_date, status 
                              FROM users 
                              ORDER BY joined_date DESC 
                              LIMIT 10";
            $recentUsersStmt = $pdo->query($recentUsersSql);
            $dashboardData['recent_users'] = $recentUsersStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get recent transactions (new to old)
            $recentTransactionsSql = "SELECT t.id, t.transaction_code, t.booking_id, t.amount, t.status, t.transaction_date, u.name as user_name, u.email as user_email
                                     FROM transactions t
                                     LEFT JOIN bookings b ON b.id = t.booking_id
                                     LEFT JOIN users u ON u.id = b.user_id
                                     ORDER BY t.transaction_date DESC 
                                     LIMIT 10";
            $recentTransactionsStmt = $pdo->query($recentTransactionsSql);
            $dashboardData['recent_transactions'] = $recentTransactionsStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Get recent portfolios (for all users)
        $portfolioSql = "SELECT portfolio_id, title, status, created_at
                        FROM portfolios 
                        $whereClause 
                        ORDER BY created_at DESC 
                        LIMIT 5";
        $portfolioStmt = $pdo->query($portfolioSql);
        $dashboardData['recent_portfolios'] = $portfolioStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get recent services if user has access
        if ($currentUser['role'] === 'Admin') {
            $serviceSql = "SELECT id, service_name, created_at 
                          FROM services 
                          ORDER BY created_at DESC 
                          LIMIT 5";
            $serviceStmt = $pdo->query($serviceSql);
            $dashboardData['recent_services'] = $serviceStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        ApiResponse::success($dashboardData, 'Dashboard data retrieved successfully');
        
    } catch (Exception $e) {
        error_log('Dashboard stats error: ' . $e->getMessage());
        ApiResponse::serverError('Failed to retrieve dashboard data');
    }
}

?>