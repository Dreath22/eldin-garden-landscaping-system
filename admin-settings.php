<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings - GreenScape Admin</title>
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
          <a href="admin-notifications.php" class="admin-nav-item">
            <i class="fas fa-bell"></i>
            <span>Notifications</span>
          </a>
        </div>
        <div class="admin-nav-section">
          <p class="admin-nav-title">Settings</p>
          <a href="admin-settings.php" class="admin-nav-item active">
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
          <input type="text" placeholder="Search settings...">
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
            <h2>Settings</h2>
            <p>Manage your admin dashboard and website settings.</p>
          </div>
          <button class="btn btn-primary" onclick="saveAllSettings()">
            <i class="fas fa-save"></i> Save All Changes
          </button>
        </div>

        <!-- General Settings -->
        <div class="settings-section">
          <div class="settings-section-header">
            <h3><i class="fas fa-cog"></i> General Settings</h3>
          </div>
          <div class="settings-section-body">
            <div class="form-row">
              <div class="form-group">
                <label for="siteName">Website Name</label>
                <input type="text" id="siteName" value="GreenScape Landscaping">
              </div>
              <div class="form-group">
                <label for="siteTagline">Tagline</label>
                <input type="text" id="siteTagline" value="Transforming Outdoor Spaces">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="adminEmail">Admin Email</label>
                <input type="email" id="adminEmail" value="admin@greenscape.com">
              </div>
              <div class="form-group">
                <label for="supportEmail">Support Email</label>
                <input type="email" id="supportEmail" value="support@greenscape.com">
              </div>
            </div>
            <div class="form-group">
              <label for="siteDescription">Website Description</label>
              <textarea id="siteDescription" rows="2">Professional landscaping services for residential and commercial properties. We create beautiful outdoor spaces.</textarea>
            </div>
          </div>
        </div>

        <!-- Business Settings -->
        <div class="settings-section">
          <div class="settings-section-header">
            <h3><i class="fas fa-building"></i> Business Information</h3>
          </div>
          <div class="settings-section-body">
            <div class="form-row">
              <div class="form-group">
                <label for="businessName">Business Name</label>
                <input type="text" id="businessName" value="GreenScape Landscaping LLC">
              </div>
              <div class="form-group">
                <label for="businessPhone">Phone Number</label>
                <input type="tel" id="businessPhone" value="+1 (555) 123-4567">
              </div>
            </div>
            <div class="form-group">
              <label for="businessAddress">Address</label>
              <input type="text" id="businessAddress" value="123 Garden Street, Green City, GC 12345">
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="businessHours">Business Hours</label>
                <input type="text" id="businessHours" value="Mon-Sat: 8:00 AM - 6:00 PM">
              </div>
              <div class="form-group">
                <label for="timezone">Timezone</label>
                <select id="timezone">
                  <option value="EST">Eastern Time (EST)</option>
                  <option value="CST">Central Time (CST)</option>
                  <option value="MST">Mountain Time (MST)</option>
                  <option value="PST">Pacific Time (PST)</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Notification Settings -->
        <div class="settings-section">
          <div class="settings-section-header">
            <h3><i class="fas fa-bell"></i> Notification Settings</h3>
          </div>
          <div class="settings-section-body">
            <div class="settings-row">
              <div class="settings-row-info">
                <h4>New Booking Notifications</h4>
                <p>Receive notifications when a new booking is made</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" checked>
                <span class="toggle-slider"></span>
              </label>
            </div>
            <div class="settings-row">
              <div class="settings-row-info">
                <h4>Payment Notifications</h4>
                <p>Receive notifications for successful payments</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" checked>
                <span class="toggle-slider"></span>
              </label>
            </div>
            <div class="settings-row">
              <div class="settings-row-info">
                <h4>New User Registration</h4>
                <p>Receive notifications when new users sign up</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" checked>
                <span class="toggle-slider"></span>
              </label>
            </div>
            <div class="settings-row">
              <div class="settings-row-info">
                <h4>System Alerts</h4>
                <p>Receive system error and warning notifications</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" checked>
                <span class="toggle-slider"></span>
              </label>
            </div>
            <div class="settings-row">
              <div class="settings-row-info">
                <h4>Email Digest</h4>
                <p>Receive daily summary of all activities</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox">
                <span class="toggle-slider"></span>
              </label>
            </div>
          </div>
        </div>

        <!-- Email Settings -->
        <div class="settings-section">
          <div class="settings-section-header">
            <h3><i class="fas fa-envelope"></i> Email Settings</h3>
          </div>
          <div class="settings-section-body">
            <div class="form-row">
              <div class="form-group">
                <label for="smtpHost">SMTP Host</label>
                <input type="text" id="smtpHost" value="smtp.greenscape.com">
              </div>
              <div class="form-group">
                <label for="smtpPort">SMTP Port</label>
                <input type="text" id="smtpPort" value="587">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="smtpUser">SMTP Username</label>
                <input type="text" id="smtpUser" value="noreply@greenscape.com">
              </div>
              <div class="form-group">
                <label for="smtpPass">SMTP Password</label>
                <input type="password" id="smtpPass" value="********">
              </div>
            </div>
            <div class="settings-row">
              <div class="settings-row-info">
                <h4>Enable SSL/TLS</h4>
                <p>Use secure connection for email sending</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" checked>
                <span class="toggle-slider"></span>
              </label>
            </div>
          </div>
        </div>

        <!-- Payment Settings -->
        <div class="settings-section">
          <div class="settings-section-header">
            <h3><i class="fas fa-credit-card"></i> Payment Settings</h3>
          </div>
          <div class="settings-section-body">
            <div class="form-row">
              <div class="form-group">
                <label for="currency">Currency</label>
                <select id="currency">
                  <option value="USD" selected>USD ($)</option>
                  <option value="EUR">EUR (€)</option>
                  <option value="GBP">GBP (£)</option>
                  <option value="CAD">CAD (C$)</option>
                </select>
              </div>
              <div class="form-group">
                <label for="taxRate">Tax Rate (%)</label>
                <input type="number" id="taxRate" value="8.5" step="0.01">
              </div>
            </div>
            <div class="settings-row">
              <div class="settings-row-info">
                <h4>Accept Credit Cards</h4>
                <p>Enable credit card payments</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" checked>
                <span class="toggle-slider"></span>
              </label>
            </div>
            <div class="settings-row">
              <div class="settings-row-info">
                <h4>Accept PayPal</h4>
                <p>Enable PayPal payments</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" checked>
                <span class="toggle-slider"></span>
              </label>
            </div>
            <div class="settings-row">
              <div class="settings-row-info">
                <h4>Require Deposit</h4>
                <p>Require deposit for bookings over $500</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox">
                <span class="toggle-slider"></span>
              </label>
            </div>
          </div>
        </div>

        <!-- Security Settings -->
        <div class="settings-section">
          <div class="settings-section-header">
            <h3><i class="fas fa-shield-alt"></i> Security Settings</h3>
          </div>
          <div class="settings-section-body">
            <div class="settings-row">
              <div class="settings-row-info">
                <h4>Two-Factor Authentication</h4>
                <p>Require 2FA for admin login</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox">
                <span class="toggle-slider"></span>
              </label>
            </div>
            <div class="settings-row">
              <div class="settings-row-info">
                <h4>Login Notifications</h4>
                <p>Receive email on new device login</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" checked>
                <span class="toggle-slider"></span>
              </label>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="sessionTimeout">Session Timeout (minutes)</label>
                <input type="number" id="sessionTimeout" value="60">
              </div>
              <div class="form-group">
                <label for="maxAttempts">Max Login Attempts</label>
                <input type="number" id="maxAttempts" value="5">
              </div>
            </div>
          </div>
        </div>

        <!-- Maintenance Settings -->
        <div class="settings-section">
          <div class="settings-section-header">
            <h3><i class="fas fa-tools"></i> Maintenance</h3>
          </div>
          <div class="settings-section-body">
            <div class="settings-row">
              <div class="settings-row-info">
                <h4>Maintenance Mode</h4>
                <p>Put website in maintenance mode</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox">
                <span class="toggle-slider"></span>
              </label>
            </div>
            <div class="form-group">
              <label for="maintenanceMessage">Maintenance Message</label>
              <textarea id="maintenanceMessage" rows="2">We're currently performing maintenance. Please check back soon!</textarea>
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 1rem;">
              <button class="btn btn-secondary" onclick="clearCache()">
                <i class="fas fa-broom"></i> Clear Cache
              </button>
              <button class="btn btn-secondary" onclick="backupDatabase()">
                <i class="fas fa-database"></i> Backup Database
              </button>
              <button class="btn btn-warning" onclick="resetSettings()">
                <i class="fas fa-undo"></i> Reset to Defaults
              </button>
            </div>
          </div>
        </div>

        <!-- Save Button -->
        <div style="margin-top: 2rem; text-align: right;">
          <button class="btn btn-primary btn-large" onclick="saveAllSettings()" style="padding: 1rem 3rem;">
            <i class="fas fa-save"></i> Save All Changes
          </button>
        </div>
      </div>
    </main>
  </div>

  <script>
    // Save All Settings
    function saveAllSettings() {
      alert('All settings have been saved successfully!');
    }

    // Clear Cache
    function clearCache() {
      if (confirm('Are you sure you want to clear the cache?')) {
        alert('Cache has been cleared successfully!');
      }
    }

    // Backup Database
    function backupDatabase() {
      alert('Database backup has been initiated. You will receive an email when it\'s ready.');
    }

    // Reset Settings
    function resetSettings() {
      if (confirm('Are you sure you want to reset all settings to defaults? This cannot be undone.')) {
        alert('Settings have been reset to defaults.');
      }
    }
  </script>
</body>
</html>
