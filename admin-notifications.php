<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications - GreenScape Admin</title>
  <link rel="stylesheet" href="admin-style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div class="admin-page">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
      <div class="admin-sidebar-header">
        <a href="index.html" class="logo">
          <div class="logo-icon">
            <i class="fas fa-leaf"></i>
          </div>
          GreenScape
        </a>
      </div>
      <nav class="admin-nav">
        <div class="admin-nav-section">
          <p class="admin-nav-title">Main</p>
          <a href="admin-dashboard.php" class="admin-nav-item">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
          </a>
          <a href="admin-users.php" class="admin-nav-item">
            <i class="fas fa-users"></i>
            <span>Users</span>
          </a>
          <a href="admin-bookings.php" class="admin-nav-item">
            <i class="fas fa-calendar-alt"></i>
            <span>Bookings</span>
          </a>
          <a href="admin-transactions.php" class="admin-nav-item">
            <i class="fas fa-exchange-alt"></i>
            <span>Transactions</span>
          </a>
        </div>
        <div class="admin-nav-section">
          <p class="admin-nav-title">Content</p>
          <a href="admin-upload.php" class="admin-nav-item">
            <i class="fas fa-cloud-upload-alt"></i>
            <span>Upload Content</span>
          </a>
          <a href="admin-gallery.php" class="admin-nav-item">
            <i class="fas fa-images"></i>
            <span>Gallery Manager</span>
          </a>
          <a href="admin-services.php" class="admin-nav-item">
            <i class="fas fa-tools"></i>
            <span>Services</span>
          </a>
        </div>
        <div class="admin-nav-section">
          <p class="admin-nav-title">Communication</p>
          <a href="admin-emails.php" class="admin-nav-item">
            <i class="fas fa-envelope"></i>
            <span>Email Updates</span>
          </a>
          <a href="admin-notifications.php" class="admin-nav-item active">
            <i class="fas fa-bell"></i>
            <span>Notifications</span>
          </a>
        </div>
        <div class="admin-nav-section">
          <p class="admin-nav-title">Settings</p>
          <a href="admin-settings.php" class="admin-nav-item">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
          </a>
          <a href="index.html" class="admin-nav-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
          </a>
        </div>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
      <!-- Header -->
      <header class="admin-header">
        <div class="admin-search">
          <i class="fas fa-search"></i>
          <input type="text" placeholder="Search notifications...">
        </div>
        <div class="admin-header-actions">
          <a href="admin-notifications.php" class="admin-notification">
            <i class="fas fa-bell"></i>
            <span class="admin-notification-badge">5</span>
          </a>
          <div class="admin-user">
            <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100" alt="Admin">
            <div>
              <p style="font-weight: 600; font-size: 0.9rem;">David Martinez</p>
              <p style="font-size: 0.75rem; color: var(--text-gray);">Administrator</p>
            </div>
          </div>
        </div>
      </header>

      <!-- Content -->
      <div class="admin-content">
        <!-- Page Title -->
        <div class="admin-page-title">
          <div>
            <h2>Notifications Center</h2>
            <p>View and manage system notifications and alerts.</p>
          </div>
          <div style="display: flex; gap: 0.5rem;">
            <button class="btn btn-secondary" onclick="markAllRead()">
              <i class="fas fa-check-double"></i> Mark All Read
            </button>
            <button class="btn btn-primary" onclick="showSendNotificationModal()">
              <i class="fas fa-paper-plane"></i> Send Notification
            </button>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="dashboard-stats" style="grid-template-columns: repeat(4, 1fr);">
          <div class="stat-card">
            <div class="stat-card-header">
              <div>
                <h3>24</h3>
                <p>Unread</p>
              </div>
              <div class="stat-card-icon blue">
                <i class="fas fa-bell"></i>
              </div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-card-header">
              <div>
                <h3>156</h3>
                <p>Total Today</p>
              </div>
              <div class="stat-card-icon green">
                <i class="fas fa-inbox"></i>
              </div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-card-header">
              <div>
                <h3>8</h3>
                <p>System Alerts</p>
              </div>
              <div class="stat-card-icon orange">
                <i class="fas fa-exclamation-triangle"></i>
              </div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-card-header">
              <div>
                <h3>3</h3>
                <p>User Reports</p>
              </div>
              <div class="stat-card-icon red">
                <i class="fas fa-flag"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
          <div class="tab active" onclick="switchTab('all')">All Notifications</div>
          <div class="tab" onclick="switchTab('unread')">Unread</div>
          <div class="tab" onclick="switchTab('system')">System</div>
          <div class="tab" onclick="switchTab('bookings')">Bookings</div>
          <div class="tab" onclick="switchTab('users')">Users</div>
        </div>

        <!-- Notifications List -->
        <div class="dashboard-card">
          <div class="dashboard-card-header">
            <h3><i class="fas fa-bell"></i> Recent Notifications</h3>
            <div style="display: flex; gap: 0.5rem;">
              <button class="btn btn-secondary btn-small">
                <i class="fas fa-filter"></i> Filter
              </button>
              <button class="btn btn-secondary btn-small">
                <i class="fas fa-cog"></i> Settings
              </button>
            </div>
          </div>
          <div class="dashboard-card-body">
            <div class="notification-list">
              <!-- Notification 1 -->
              <div class="notification-item unread">
                <div class="notification-icon green">
                  <i class="fas fa-calendar-check"></i>
                </div>
                <div class="notification-content">
                  <h4>New Booking Received</h4>
                  <p>Sarah Johnson booked Lawn Maintenance Service for Feb 20, 2026</p>
                </div>
                <span class="notification-time">2 minutes ago</span>
                <div class="table-actions">
                  <button class="table-btn view" title="View" onclick="viewNotification('New Booking')"><i class="fas fa-eye"></i></button>
                  <button class="table-btn edit" title="Mark as Read" onclick="markAsRead(this)"><i class="fas fa-check"></i></button>
                </div>
              </div>

              <!-- Notification 2 -->
              <div class="notification-item unread">
                <div class="notification-icon blue">
                  <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="notification-content">
                  <h4>Payment Received</h4>
                  <p>Received $599.00 from Michael Chen for Garden Design Package</p>
                </div>
                <span class="notification-time">15 minutes ago</span>
                <div class="table-actions">
                  <button class="table-btn view" title="View" onclick="viewNotification('Payment Received')"><i class="fas fa-eye"></i></button>
                  <button class="table-btn edit" title="Mark as Read" onclick="markAsRead(this)"><i class="fas fa-check"></i></button>
                </div>
              </div>

              <!-- Notification 3 -->
              <div class="notification-item unread">
                <div class="notification-icon orange">
                  <i class="fas fa-user-plus"></i>
                </div>
                <div class="notification-content">
                  <h4>New User Registration</h4>
                  <p>Emily Rodriguez just created a new account</p>
                </div>
                <span class="notification-time">1 hour ago</span>
                <div class="table-actions">
                  <button class="table-btn view" title="View" onclick="viewNotification('New User')"><i class="fas fa-eye"></i></button>
                  <button class="table-btn edit" title="Mark as Read" onclick="markAsRead(this)"><i class="fas fa-check"></i></button>
                </div>
              </div>

              <!-- Notification 4 -->
              <div class="notification-item unread">
                <div class="notification-icon red">
                  <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="notification-content">
                  <h4>System Alert: Low Storage</h4>
                  <p>Server storage is at 85% capacity. Consider cleaning up old files.</p>
                </div>
                <span class="notification-time">2 hours ago</span>
                <div class="table-actions">
                  <button class="table-btn view" title="View" onclick="viewNotification('System Alert')"><i class="fas fa-eye"></i></button>
                  <button class="table-btn edit" title="Mark as Read" onclick="markAsRead(this)"><i class="fas fa-check"></i></button>
                </div>
              </div>

              <!-- Notification 5 -->
              <div class="notification-item">
                <div class="notification-icon green">
                  <i class="fas fa-check-circle"></i>
                </div>
                <div class="notification-content">
                  <h4>Service Completed</h4>
                  <p>Hardscaping Project for James Wilson has been completed</p>
                </div>
                <span class="notification-time">3 hours ago</span>
                <div class="table-actions">
                  <button class="table-btn view" title="View" onclick="viewNotification('Service Completed')"><i class="fas fa-eye"></i></button>
                  <button class="table-btn delete" title="Delete"><i class="fas fa-trash"></i></button>
                </div>
              </div>

              <!-- Notification 6 -->
              <div class="notification-item">
                <div class="notification-icon blue">
                  <i class="fas fa-star"></i>
                </div>
                <div class="notification-content">
                  <h4>New Review Received</h4>
                  <p>Robert Kim left a 5-star review for Irrigation System Install</p>
                </div>
                <span class="notification-time">5 hours ago</span>
                <div class="table-actions">
                  <button class="table-btn view" title="View" onclick="viewNotification('New Review')"><i class="fas fa-eye"></i></button>
                  <button class="table-btn delete" title="Delete"><i class="fas fa-trash"></i></button>
                </div>
              </div>

              <!-- Notification 7 -->
              <div class="notification-item">
                <div class="notification-icon orange">
                  <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="notification-content">
                  <h4>Upcoming Appointment</h4>
                  <p>Reminder: Garden Design consultation with Lisa Anderson tomorrow at 10:00 AM</p>
                </div>
                <span class="notification-time">Yesterday</span>
                <div class="table-actions">
                  <button class="table-btn view" title="View" onclick="viewNotification('Upcoming Appointment')"><i class="fas fa-eye"></i></button>
                  <button class="table-btn delete" title="Delete"><i class="fas fa-trash"></i></button>
                </div>
              </div>

              <!-- Notification 8 -->
              <div class="notification-item">
                <div class="notification-icon red">
                  <i class="fas fa-flag"></i>
                </div>
                <div class="notification-content">
                  <h4>User Report: Issue with Booking</h4>
                  <p>Customer reported an issue with their recent booking #BK-2026-045</p>
                </div>
                <span class="notification-time">Yesterday</span>
                <div class="table-actions">
                  <button class="table-btn view" title="View" onclick="viewNotification('User Report')"><i class="fas fa-eye"></i></button>
                  <button class="table-btn delete" title="Delete"><i class="fas fa-trash"></i></button>
                </div>
              </div>
            </div>

            <!-- Pagination -->
            <div class="pagination">
              <p class="pagination-info">Showing 1-8 of 156 notifications</p>
              <div class="pagination-controls">
                <button class="pagination-btn" disabled><i class="fas fa-chevron-left"></i></button>
                <button class="pagination-btn active">1</button>
                <button class="pagination-btn">2</button>
                <button class="pagination-btn">3</button>
                <span style="padding: 0.5rem;">...</span>
                <button class="pagination-btn">20</button>
                <button class="pagination-btn"><i class="fas fa-chevron-right"></i></button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <!-- Send Notification Modal -->
  <div class="modal-overlay" id="sendNotificationModal" style="display: none;">
    <div class="modal">
      <div class="modal-header">
        <h3><i class="fas fa-paper-plane" style="color: var(--primary-green);"></i> Send Notification</h3>
        <button class="modal-close" onclick="closeSendNotificationModal()">&times;</button>
      </div>
      <div class="modal-body">
        <form id="sendNotificationForm">
          <div class="form-group">
            <label for="notificationType">Notification Type</label>
            <select id="notificationType">
              <option value="info">Information</option>
              <option value="success">Success</option>
              <option value="warning">Warning</option>
              <option value="error">Error</option>
            </select>
          </div>
          <div class="form-group">
            <label for="notificationRecipients">Recipients</label>
            <select id="notificationRecipients">
              <option value="all">All Users</option>
              <option value="customers">Customers Only</option>
              <option value="staff">Staff Only</option>
              <option value="admins">Admins Only</option>
            </select>
          </div>
          <div class="form-group">
            <label for="notificationTitle">Title</label>
            <input type="text" id="notificationTitle" placeholder="Enter notification title">
          </div>
          <div class="form-group">
            <label for="notificationMessage">Message</label>
            <textarea id="notificationMessage" rows="4" placeholder="Enter notification message"></textarea>
          </div>
          <div class="form-group">
            <label>
              <input type="checkbox"> Send as Email too
            </label>
          </div>
          <div class="form-group">
            <label>
              <input type="checkbox"> Schedule for later
            </label>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-small" style="background-color: #f1f5f9; color: var(--text-dark);" onclick="closeSendNotificationModal()">Cancel</button>
        <button class="btn btn-primary btn-small" onclick="sendNotification()">Send Notification</button>
      </div>
    </div>
  </div>

  <!-- View Notification Modal -->
  <div class="modal-overlay" id="viewNotificationModal" style="display: none;">
    <div class="modal">
      <div class="modal-header">
        <h3><i class="fas fa-bell" style="color: var(--primary-green);"></i> Notification Details</h3>
        <button class="modal-close" onclick="closeViewNotificationModal()">&times;</button>
      </div>
      <div class="modal-body">
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
          <div class="notification-icon green" style="width: 50px; height: 50px;">
            <i class="fas fa-calendar-check" style="font-size: 1.5rem;"></i>
          </div>
          <div>
            <h3 id="viewNotificationTitle">New Booking Received</h3>
            <p style="color: var(--text-gray); font-size: 0.85rem;">2 minutes ago</p>
          </div>
        </div>
        <div style="padding: 1rem; background-color: #f8fafc; border-radius: 8px; margin-bottom: 1rem;">
          <p style="color: var(--text-dark); line-height: 1.6;">Sarah Johnson has booked Lawn Maintenance Service for February 20, 2026 at 10:00 AM.</p>
        </div>
        <div style="margin-bottom: 1rem;">
          <p style="color: var(--text-gray); font-size: 0.85rem; margin-bottom: 0.5rem;">Customer Details</p>
          <div class="table-user">
            <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100" alt="User">
            <div class="table-user-info">
              <h4>Sarah Johnson</h4>
              <p>sarah@email.com | +1 (555) 123-4567</p>
            </div>
          </div>
        </div>
        <div>
          <p style="color: var(--text-gray); font-size: 0.85rem; margin-bottom: 0.5rem;">Booking Details</p>
          <p><i class="fas fa-tools" style="width: 20px; color: var(--text-gray);"></i> Lawn Maintenance Service</p>
          <p><i class="fas fa-calendar" style="width: 20px; color: var(--text-gray);"></i> Feb 20, 2026 at 10:00 AM</p>
          <p><i class="fas fa-map-marker-alt" style="width: 20px; color: var(--text-gray);"></i> 123 Garden St, Green City</p>
          <p><i class="fas fa-dollar-sign" style="width: 20px; color: var(--text-gray);"></i> $199.00</p>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-small" style="background-color: #f1f5f9; color: var(--text-dark);" onclick="closeViewNotificationModal()">Close</button>
        <a href="admin-bookings.php" class="btn btn-primary btn-small">View Booking</a>
      </div>
    </div>
  </div>

  <script>
    // Send Notification Modal
    function showSendNotificationModal() {
      document.getElementById('sendNotificationModal').style.display = 'flex';
    }

    function closeSendNotificationModal() {
      document.getElementById('sendNotificationModal').style.display = 'none';
    }

    function sendNotification() {
      alert('Notification has been sent successfully!');
      closeSendNotificationModal();
    }

    // View Notification Modal
    function viewNotification(title) {
      document.getElementById('viewNotificationTitle').textContent = title;
      document.getElementById('viewNotificationModal').style.display = 'flex';
    }

    function closeViewNotificationModal() {
      document.getElementById('viewNotificationModal').style.display = 'none';
    }

    // Mark as Read
    function markAsRead(btn) {
      const item = btn.closest('.notification-item');
      item.classList.remove('unread');
      item.style.opacity = '0.7';
      btn.remove();
    }

    // Mark All Read
    function markAllRead() {
      document.querySelectorAll('.notification-item.unread').forEach(item => {
        item.classList.remove('unread');
        item.style.opacity = '0.7';
        const btn = item.querySelector('.table-actions .edit');
        if (btn) btn.remove();
      });
      alert('All notifications marked as read.');
    }

    // Tab Switching
    function switchTab(tab) {
      document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
      event.target.classList.add('active');
    }

    // Close modals when clicking outside
    document.querySelectorAll('.modal-overlay').forEach(modal => {
      modal.addEventListener('click', function(e) {
        if (e.target === this) {
          this.style.display = 'none';
        }
      });
    });
  </script>
</body>
</html>
