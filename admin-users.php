<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>User Management - GreenScape Admin</title>
    <link rel="stylesheet" href="admin-style.css" />
    <link rel="stylesheet" href="modal.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <!-- Custom Style-->
    <style>
      .status-badge {
        text-transform: capitalize;
      }
    </style>
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
            <a href="admin-users.php" class="admin-nav-item active">
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
            <a href="admin-notifications.php" class="admin-nav-item">
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
            <input type="text" placeholder="Search users by name or email..." />
          </div>
          <div class="admin-header-actions">
            <a href="admin-notifications.php" class="admin-notification">
              <i class="fas fa-bell"></i>
              <span class="admin-notification-badge">5</span>
            </a>
            <div class="admin-user">
              <img
                src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100"
                alt="Admin"
              />
              <div>
                <p style="font-weight: 600; font-size: 0.9rem">
                  David Martinez
                </p>
                <p style="font-size: 0.75rem; color: var(--text-gray)">
                  Administrator
                </p>
              </div>
            </div>
          </div>
        </header>

        <!-- Content -->
        <div class="admin-content">
          <!-- Page Title -->
          <div class="admin-page-title">
            <div>
              <h2>User Management</h2>
              <p>Manage all users, view details, and take actions.</p>
            </div>
            <button class="btn btn-primary" data-action="showadd-user">
              <i class="fas fa-plus"></i> Add New User
            </button>
          </div>

          <!-- Stats Cards -->
          <div
            class="dashboard-stats"
            style="grid-template-columns: repeat(4, 1fr)"
          >
            <div class="stat-card">
              <div class="stat-card-header">
                <div>
                  <h3 id="totalUser"></h3>
                  <p>Total Users</p>
                </div>
                <div class="stat-card-icon blue">
                  <i class="fas fa-users"></i>
                </div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-card-header">
                <div>
                  <h3 id="activeUser"></h3>
                  <p>Active Users</p>
                </div>
                <div class="stat-card-icon green">
                  <i class="fas fa-user-check"></i>
                </div>
              </div>
            </div>
            
            <div class="stat-card">
              <div class="stat-card-header">
                <div>
                  <h3 id="bannedUser"></h3>
                  <p>Banned</p>
                </div>
                <div class="stat-card-icon red">
                  <i class="fas fa-user-slash"></i>
                </div>
              </div>
            </div>
          </div>

          <!-- Tabs -->
          <div class="tabs" id="userTabsContainer">
            <div class="tab active" data-tab="all">All Users</div>
            <div class="tab" data-tab="active">Active</div>
            <div class="tab" data-tab="pending">Pending</div>
            <div class="tab" data-tab="banned">Banned</div>
          </div>

          <!-- Filters -->
          <div class="filters-bar">
            <div class="filter-group">
              <label>Role:</label>
              <select id="roleFilter">
                <option value="all" selected>All Roles</option>
                <option value="customer">Customer</option>
                <option value="admin">Admin</option>
                <option value="staff">Staff</option>
              </select>
            </div>
            <div class="filter-group">
              <label>Sort by:</label>
              <select id="orderFilter">
                <option value="newest">Newest First</option>
                <option value="oldest">Oldest First</option>
                <option value="name-az">Name A-Z</option>
                <option value="name-za">Name Z-A</option>
              </select>
            </div>
            <div class="filter-group">
              <label>Date Range:</label>
              <input type="date" placeholder="From" id="dateFrom" />
              <span>to</span>
              <input type="date" placeholder="To" id="dateTo" />
            </div>
          </div>

          <!-- Users Table -->
          <div class="dashboard-card">
            <div class="dashboard-card-header">
              <h3><i class="fas fa-users"></i> All Users</h3>
              <div style="display: flex; gap: 0.5rem">
                <button class="btn btn-secondary btn-small" id="exportBtn">
                  <i class="fas fa-download"></i> Export
                </button>
                <button class="btn btn-danger btn-small">
                  <i class="fas fa-trash"></i> Bulk Delete
                </button>
              </div>
            </div>
            <div class="dashboard-card-body">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>
                      <input type="checkbox" class="checkbox" id="deleteAll" />
                    </th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th>Last Active</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="userTableBody"></tbody>
              </table>

              <!-- Pagination -->
              <div
                id="pagination"
                class="pagination"
                style="justify-content: center"
              ></div>
            </div>
          </div>
        </div>
      </main>
    </div>

    <!-- Add User Modal -->
    <div class="modal-overlay" id="addUserModal" style="display: none">
      <div class="modal modal-large">
        <div class="modal-header">
          <h3>
            <i class="fas fa-user-plus" style="color: var(--primary-green)"></i>
            Add New User
          </h3>
          <button
            class="modal-close"
            id="addUserModalClose"
            onclick="closeAddUserModal()"
          >
            &times;
          </button>
        </div>
        <div class="modal-body">
          <form id="addUserForm">
            <div class="form-row">
              <div class="form-group">
                <label for="firstName">First Name</label>
                <input
                  type="text"
                  id="firstName"
                  name="firstName"
                  placeholder="Enter first name"
                />
              </div>
              <div class="form-group">
                <label for="lastName">Last Name</label>
                <input
                  type="text"
                  id="lastName"
                  name="lastName"
                  placeholder="Enter last name"
                />
              </div>
            </div>
            <div class="form-group">
              <label for="email">Email Address</label>
              <input
                type="email"
                id="email"
                name="email"
                placeholder="Enter email address"
              />
            </div>
            <div class="form-group">
              <label for="phone">Phone Number</label>
              <input
                type="tel"
                id="phone"
                name="phone"
                placeholder="Enter phone number"
              />
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role">
                  <option value="customer">Customer</option>
                  <option value="staff">Staff</option>
                  <option value="admin">Admin</option>
                </select>
              </div>
              <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                  <option value="active">Active</option>
                  <option value="pending">Pending</option>
                  <option value="banned">Banned</option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label for="password">Temporary Password</label>
              <input
                type="password"
                id="password"
                name="temporaryPassword"
                placeholder="Enter temporary password"
              />
            </div>
            <div class="form-group">
              <label for="notes">Notes (Optional)</label>
              <textarea
                id="notes"
                rows="2"
                name="notes"
                placeholder="Any additional notes..."
              ></textarea>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button
            class="btn btn-small"
            style="background-color: #f1f5f9; color: var(--text-dark)"
            onclick="closeAddUserModal()"
          >
            Cancel
          </button>
          <button class="btn btn-primary btn-small" onclick="confirmAddUser()">
            Add User
          </button>
        </div>
      </div>
    </div>

    <!-- View User Modal -->
    <div class="modal-overlay" id="viewUserModal" style="display: none"></div>

    <!-- Edit User Modal -->
    <div class="modal-overlay" id="editUserModal" style="display: none"></div>

    <!-- Ban User Modal -->
    <div class="modal-overlay" id="banModal" style="display: none"></div>
    <script type="module" src="./JS/admin_user.js"></script>
  </body>
</html>
