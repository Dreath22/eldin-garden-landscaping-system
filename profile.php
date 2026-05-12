<?php
require_once __DIR__ . '/config/auth_middleware.php';

// Require authentication - will redirect if not logged in
//requireAuth();

// Initialize standard session
$sessionData = initStandardSession();
$user = $sessionData['user'];
$isLoggedIn = $sessionData['isLoggedIn'];

// Additional validation for calendar functionality
if (!$isLoggedIn || empty($user['id'])) {
    // Redirect to login if not authenticated
    header('Location: login.php');
    exit();
}
$message = '';
$messageType = '';

// Profile Update Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];

// Check if email is unique (excluding current user)
    $emailCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $emailCheck->execute([$email, $userId]);
    
    if ($emailCheck->fetch()) {
        $message = "This email is already in use by another account.";
        $messageType = "danger";
    } else {
        try {
            // Base query - using correct column names
            $sql = "UPDATE users SET name = ?, email = ?, phone_number = ? WHERE id = ?";
            $params = [$fullname, $email, $phone, $userId];

            // If password is provided, hash it and add to query
            if (!empty($password)) {
                $sql = "UPDATE users SET name = ?, email = ?, phone_number = ?, password = ? WHERE id = ?";
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $params = [$fullname, $email, $phone, $hashedPassword, $userId];
            }

            $updateStmt = $pdo->prepare($sql);
            $updateStmt->execute($params);
            
            // Refresh user data after update
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            $message = "Profile updated successfully!";
            $messageType = "success";
        } catch (PDOException $e) {
            $message = "Error updating profile: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

// Fetch user data
try{
  $userId = $sessionData['userId'];
  $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
  $stmt->execute([$userId]);
  $user = $stmt->fetch();

  // Fetch user's quote requests with LEFT JOIN to users table (only Consultation status)
  $quoteStmt = $pdo->prepare("SELECT b.*, s.service_name, u.name as user_name, u.email as user_email, u.phone_number as user_phone 
                              FROM bookings b 
                              LEFT JOIN services s ON b.service_id = s.id
                              LEFT JOIN users u ON b.user_id = u.id 
                              WHERE b.user_id = ? AND b.status = 'Consultation'
                              ORDER BY b.created_at DESC");
  $quoteStmt->execute([$userId]);
  $userQuotes = $quoteStmt->fetchAll();
}catch(Exception $e){
  echo "Error: " . $e->getMessage(); 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile - EldinGarden Landscaping</title>
  <link rel="stylesheet" href="client-style.css">
  <script src="/landscape/assets/tailwind.js"></script>
  <!-- Note: In production, consider using PostCSS build instead of CDN -->
  <link rel="stylesheet" 
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" 
      integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" 
      crossorigin="anonymous" 
      referrerpolicy="no-referrer" />
  
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
  
  <script id="tailwind-config">
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    "primary": "#1a4d2e",
                    "background-light": "#f8f6f6",
                    "background-dark": "#221610",
                },
                fontFamily: {
                    "display": ["Public Sans"]
                },
                borderRadius: {
                    "DEFAULT": "0.25rem",
                    "lg": "0.5rem",
                    "xl": "0.75rem",
                    "full": "9999px"
                },
            },
        },
    }
  </script>
  <style>
        /* Custom scrollbar for the modal content if needed */
        .cal-modal-unique-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .cal-modal-unique-scrollbar::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 10px;
        }
        
        /* Animation for modal entry */
        @keyframes cal-modal-unique-fade-in {
            from { opacity: 0; transform: scale(0.95) translateY(-10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .cal-modal-unique-animate {
            animation: cal-modal-unique-fade-in 0.3s ease-out forwards;
        }

        /* Calendar grid styling */
        .cal-modal-unique-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
        }
        
        .cal-modal-unique-day {
            aspect-ratio: 1 / 1;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            border-radius: 9999px;
            font-size: 0.875rem;
        }

        .cal-modal-unique-day:hover:not(.empty) {
            background-color: #f1f5f9;
        }

        .cal-modal-unique-selected {
            background-color: #3b82f6 !important;
            color: white !important;
        }

        .cal-modal-unique-today {
            border: 2px solid #3b82f6;
            font-weight: bold;
        }
    
    /* CSS Variables for consistent styling */
    :root {
      --primary-green: #1a4d2e;
      --white: #ffffff;
      --light-cream: #f8f6f6;
      --text-dark: #1f2937;
      --text-gray: #6b7280;
      --shadow: rgba(0, 0, 0, 0.1);
      --danger-red: #ef4444;
    }
    
    /* Match the hero style of other pages using primary green */
    .calendar-hero {
      height: 50vh;
      background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), var(--primary-green);
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      color: var(--white);
    }

    .calendar-container {
      background: var(--white);
      border-radius: 15px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.1);
      padding: 1.5rem;
      width: 100%;
      max-width: 600px;
      margin: 0 auto;
      border: 1px solid #e2e8f0;
    }

    .spinner {
      border: 3px solid #f3f3f3;
      border-top: 3px solid var(--primary-green);
      border-radius: 50%;
      width: 30px;
      height: 30px;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    .calendar-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
    }

    .calendar-controls {
      display: flex;
      gap: 1rem;
      align-items: center;
    }

    .calendar-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 10px;
    }

    .day-name {
      text-align: center;
      font-weight: bold;
      color: var(--primary-green);
      padding-bottom: 10px;
    }

    .calendar-day {
      aspect-ratio: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 8px;
      background: var(--light-cream);
      cursor: pointer;
      transition: all 0.2s;
      font-weight: 500;
    }

    .calendar-day:hover {
      background: var(--primary-green);
      color: var(--white);
    }

    .calendar-day.today {
      border: 2px solid var(--primary-green);
      color: var(--primary-green);
      font-weight: bold;
    }

    .calendar-day.empty {
      background: transparent;
      cursor: default;
    }

    .select-input {
      padding: 0.5rem;
      border-radius: 8px;
      border: 1px solid #ddd;
      font-family: inherit;
    }

    .calendar-day.has-booking {
      background: linear-gradient(145deg, #f0fdf4, #dcfce7);
      border: 1px solid #86efac;
      border-radius: 12px;
      position: relative;
      cursor: pointer;
      box-shadow: 0 2px 8px rgba(34, 197, 94, 0.1);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 2px;
    }

    .calendar-day.has-booking:hover {
      background: linear-gradient(145deg, #16a34a, #15803d);
      color: var(--white);
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(34, 197, 94, 0.25);
    }

    .day-number {
      font-weight: 600;
      font-size: 0.95rem;
      line-height: 1;
      color: #1f2937;
    }

    .calendar-day.has-booking:hover .day-number {
      color: white;
    }

    .booking-indicator {
      font-size: 0.55rem;
      background: linear-gradient(135deg, #16a34a, #15803d);
      color: white;
      padding: 2px 6px;
      border-radius: 8px;
      text-align: center;
      font-weight: 600;
      line-height: 1;
      letter-spacing: 0.2px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .calendar-day.has-booking:hover .booking-indicator {
      background: white;
      color: #16a34a;
    }

    .calendar-day.unavailable {
      background: linear-gradient(145deg, #fee2e2, #fecaca);
      border: 1px solid #fca5a5;
      color: #991b1b;
      cursor: not-allowed;
    }

    .calendar-day.unavailable:hover {
      background: linear-gradient(145deg, #fca5a5, #f87171);
      transform: none;
    }

    .unavailability-indicator {
      font-size: 0.55rem;
      background: #dc2626;
      color: white;
      padding: 2px 4px;
      border-radius: 4px;
      text-align: center;
      font-weight: 600;
      line-height: 1;
    }

    .calendar-day.unavailable:hover .unavailability-indicator {
      background: #991b1b;
    }

    .calendar-day {
      transition: all 0.2s ease;
    }

    .calendar-day:not(.has-booking):hover {
      background: #f8fafc;
      transform: translateY(-1px);
    }
  </style>
</head>
<body class="profile-page">
  <!-- Navigation -->
  <nav class="navbar">
    <a href="index.php" class="logo">
      <div class="logo-icon"><img src="assets/img/LOGO.png" alt="EldinGarden Logo" style="height: 24px; width: auto; vertical-align: middle;"></div>
      EldinGarden
    </a>
    <div class="menu-toggle" onclick="toggleMenu()">
      <span></span><span></span><span></span>
    </div>
    <ul class="nav-links" id="navLinks">
      <li><a href="index.php">Home</a></li>
      <li><a href="about.php">About</a></li>
      <li><a href="services.php">Services</a></li>
      <li><a href="gallery.php">Gallery</a></li>
      <li><a href="contact.php">Contact</a></li>
      <?php if ($isLoggedIn): ?>
        <li><a href="profile.php" class="btn-login">My Profile</a></li>
      <?php else: ?>
        <li><a href="login.php" class="btn-login">Log in</a></li>
      <?php endif; ?>
    </ul>
  </nav>

  <!-- Profile Header (Aligned with style.css) -->
  <header class="profile-header">
    <div class="profile-header-content">
      <div class="profile-avatar">
        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&background=random" alt="User Avatar">
      </div>
      <div class="profile-info">
        <h1>Welcome, <?php echo htmlspecialchars(explode(' ', $user['name'])[0]); ?>!</h1>
        <p>Manage your account settings and track your landscaping projects.</p>
      </div>
    </div>
  </header>

  <!-- Profile Content -->
  <main class="profile-content">
    
    <!-- Status Messages -->
    <?php if ($message): ?>
      <div style="padding: 1rem; border-radius: 10px; margin-bottom: 2rem; border: 1px solid; 
          <?php echo $messageType === 'success' ? 'background: #dcfce7; color: #166534; border-color: #bbf7d0;' : 'background: #fee2e2; color: #991b1b; border-color: #fecaca;'; ?>">
          <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i> 
          <?php echo $message; ?>
      </div>
    <?php endif; ?>

    <div class="profile-grid">
      
      <!-- Sidebar Navigation -->
      <aside >
        <div class="profile-menu profile-sidebar">
            <a href="#" class="profile-menu-item active"><i class="fas fa-user-edit"></i> Edit Profile</a>
            <a href="contact.php" class="profile-menu-item"><i class="fas fa-plus-circle"></i> New Request</a>
                        <a href="logout.php" class="profile-menu-item" style="color: var(--danger-red);"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
      </aside>

      <!-- Main Content Area -->
      <div class="profile-main">
        
        <!-- Account Details Form -->
        <div class="profile-card">
          <h3>Account Settings</h3>
          <form action="profile.php" method="POST">
              <div class="profile-form-grid">
                  <div class="form-group">
                      <label>Full Name</label>
                      <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['name']) ?? ''; ?>" required>
                  </div>
                  <div class="form-group">
                      <label>Email Address</label>
                      <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                  </div>
                  <div class="form-group">
                      <label>Phone Number</label>
                      <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>">
                  </div>
                  <div class="form-group">
                      <label>Update Password (Optional)</label>
                      <input type="password" name="password" placeholder="Leave blank to keep current">
                  </div>
              </div>

              <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end; align-items: center; gap: 1rem;">
                  <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
              </div>
          </form>
        </div>

        <!-- Calendar Section -->
        <div style="margin-top: 3rem;">
          <div class="section-header" style="text-align: left; margin-bottom: 2rem;">
              <h2 style="font-size: 1.8rem;">Booking Calendar</h2>
              <p>View and manage your upcoming appointments.</p>
          </div>
          <div class="calendar-container" style="max-width: 600px; margin: 0 auto;">
            <div class="calendar-header">
              <h3 id="monthDisplay">Month Year</h3>
              <div class="calendar-controls">
                <select id="monthSelect" class="select-input" onchange="updateCalendar()"></select>
                <select id="yearSelect" class="select-input" onchange="updateCalendar()"></select>
              </div>
            </div>
            <div class="calendar-grid" id="calendarGrid">
              <!-- JavaScript will populate this -->
            </div>
          </div>
        </div>

        <!-- Project Quote Requests -->
        <div style="margin-top: 3rem;"  >
          <div class="section-header" style="text-align: left; margin-bottom: 2rem;">
              <h2 style="font-size: 1.8rem;">My Quote Requests</h2>
              <p>Current and past inquiries for your outdoor projects.</p>
          </div>
          
          <?php if (empty($userQuotes)): ?>
              <div class="profile-card" style="text-align: center; padding: 3rem;">
                  <i class="fas fa-clipboard-list" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem; display: block;"></i>
                  <p style="color: var(--text-gray);">You haven't requested any quotes yet.</p>
                  <a href="contact.php" class="btn btn-primary" style="margin-top: 1rem; display: inline-block;">Request a Free Quote</a>
              </div>
          <?php else: ?>
              <div class="requests-container">
                  <?php foreach ($userQuotes as $quote): ?>
                      <div class="profile-card" style="margin-bottom: 1.5rem; padding: 0; overflow: hidden; border: 1px solid #e2e8f0;">
                          <!-- Card Header -->
                          <div style="background: #f8fafc; padding: 1.25rem 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                              <h4 style="margin: 0; color: var(--primary-green);"><?php echo htmlspecialchars($quote['service']); ?></h4>
                              <span style="font-size: 0.85rem; color: var(--text-gray);">
                                  <i class="far fa-calendar-alt"></i> 
                                  <?php echo date('M d, Y', strtotime($quote['created_at'])); ?>
                                  <span style="<?php echo ($quote['notifBoolean'] ?? false) ? 'color: #10b981;' : 'color: #f59e0b;'; ?>">
                                      <i class="<?php echo ($quote['notifBoolean'] ?? false) ? 'fas fa-check-circle' : 'fas fa-envelope'; ?>"></i>
                                      <?php echo ($quote['notifBoolean'] ?? false) ? 'Read' : 'Unread'; ?>
                                  </span>
                              </span>
                          </div>
                          <!-- Card Body -->
                          <div style="padding: .5rem;">
                              <div style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
                                  <div><p style="font-size: 0.85rem; font-weight: 700; color: var(--text-gray); text-transform: uppercase; margin-bottom: 5px;">Project Details <span style="background-color: lightgray; padding: 0.25rem 0.5rem; border-radius: 10px;"><?php echo htmlspecialchars($quote['booking_code']); ?></span></p>
                                  <p style="color: var(--text-dark); line-height: 1.5;"><?php echo nl2br(htmlspecialchars($quote['message'])); ?></p></div>
                                  <button class="more-details" data-id="<?php echo htmlspecialchars($quote['id']); ?>" style="font-size: 0.85rem;background-color: lightgray; padding: 0.25rem 0.5rem; border-radius: 10px;">more details</button>
                              </div>
                              <!-- <div style="display: flex; gap: 1rem; border-top: 1px solid #f1f5f9; padding-top: 1rem;">
                                  <span class="status-badge pending">Status: Reviewing</span>
                                  <span class="status-badge active" style="background: #f1f5f9; color: #475569;">Admin: Pending</span>
                              </div> -->
                          </div>
                      </div>
                  <?php endforeach; ?>
              </div>
          <?php endif; ?>
        
        <!-- User Bookings Section -->
        <div style="margin-top: 3rem;">
          <h3 style="margin-bottom: 1.5rem; color: var(--text-dark); font-size: 1.5rem;">Your Bookings</h3>
          <div id="user-bookings-container" class="requests-container">
            <!-- Bookings will be dynamically loaded here -->
            <div class="text-center py-8" id="bookings-loading">
              <div class="spinner" style="margin: 0 auto 1rem;"></div>
              <p style="color: var(--text-gray);">Loading your bookings...</p>
            </div>
            <div id="bookings-empty" style="display: none;" class="text-center py-8">
              <span class="material-symbols-outlined text-3xl mb-2" style="color: var(--text-gray);">event_note</span>
              <p style="color: var(--text-gray);">No bookings found</p>
              <a href="contact.php" class="btn btn-primary" style="margin-top: 1rem; display: inline-block;">Request a Quote</a>
            </div>
            <div id="bookings-list" style="display: none;">
              <!-- Booking cards will be inserted here -->
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="footer">
    <div class="footer-content">
      <div class="footer-section">
        <h3>EldinGarden</h3>
        <p>Professional landscaping services that bring your outdoor vision to life.</p>
      </div>
      <div class="footer-section">
        <h3>Quick Links</h3>
        <p><a href="index.php">Home</a></p>
        <p><a href="about.php">About Us</a></p>
        <p><a href="services.php">Services</a></p>
        <p><a href="gallery.php">Gallery</a></p>
        <p><a href="contact.php">Contact</a></p>
      </div>
      <div class="footer-section">
        <h3>Contact Us</h3>
        <p><i class="fas fa-phone"></i> 0945 547 5152</p>
        <p><i class="fas fa-envelope"></i> info@EldinGarden.com</p>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; 2005 EldinGarden Landscaping. All rights reserved.</p>
    </div>
  </footer>

  <div id="modalOverlay" class="modal-overlay fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" style="display: none;">
        
        <!-- Modal Content -->
        <div class="modal-content bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
            
            <!-- Header -->
            <div class="bg-gradient-to-r from-indigo-600 to-violet-600 px-8 py-6 text-white relative">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-xl"></div>
                <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white/5 rounded-full blur-xl"></div>
                
                <div class="relative z-10 flex justify-between items-start">
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight">Service Request</h2>
                        <p class="text-indigo-100 mt-1 text-sm">Complete the form below for your estimate</p>
                    </div>
                    <button onclick="closeModal('modalOverlay')" class="text-white/70 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Form Content -->
            <div id="formContent" class="overflow-y-auto custom-scroll p-8">
                <form id="clientForm" onsubmit="handleSubmit(event)">
                    
                    <!-- Client Editable Section -->
                    <div class="mb-8">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Client Information</h3>
                            <span class="text-xs text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full font-medium">Editable</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- Full Name -->
                            <div class="input-group">
                                <label for="fullName" class="input-label">Full Name</label>
                                <input type="text" id="fullName" name="fullName" class="input-field w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all bg-gray-50/50" placeholder=" " required="">
                            </div>

                            <!-- Email (Display) -->
                            <div class="input-group">
                                <label for="email" class="input-label">Email</label>
                                <input type="email" id="email" name="email" class="input-field w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-100 text-gray-500 cursor-not-allowed" placeholder=" " readonly tabindex="-1">
                                <div class="absolute right-3 top-3.5">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </div>
                            </div>

                            <!-- Service Name -->
                            <div class="input-group">
                                <label for="serviceName" class="input-label">Service Name</label>
                                <input type="text" id="serviceName" name="serviceName" class="input-field w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all bg-gray-50/50" placeholder=" " required="">
                            </div>

                            <!-- Area -->
                            <div class="input-group">
                                <label for="area" class="input-label">Area (sq ft / units)</label>
                                <input type="text" id="area" name="area" class="input-field w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all bg-gray-50/50" placeholder=" " required="">
                            </div>

                            <!-- Address -->
                            <div class="md:col-span-2">
                                <label for="address" class="input-label">Address</label>
                                <input type="text" id="address" name="address" class="input-field w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all bg-gray-50/50" placeholder=" " required="">
                            </div>

                            <!-- Message -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Additional Message</label>
                                <textarea id="message" name="message" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all bg-gray-50/50 resize-none" placeholder="Describe your requirements..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Cost Breakdown Section -->
                    <div class="mb-8" id="cost_breakdown">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Cost Estimate</h3>
                            <span class="text-xs text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full font-medium">Admin Calculated</span>
                        </div>

                        <div class="bg-gray-50 rounded-xl p-5 space-y-3 border border-gray-100">
                            <!-- Labor Cost -->
                            <div class="cost-card flex justify-between items-center p-3 bg-white rounded-lg border border-gray-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700">Labor Cost</span>
                                </div>
                                <span id="laborCost" class="text-sm font-bold text-gray-900 font-mono">$2,500.00</span>
                            </div>

                            <!-- Materials Cost -->
                            <div class="cost-card flex justify-between items-center p-3 bg-white rounded-lg border border-gray-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700">Materials Cost</span>
                                </div>
                                <span id="materialsCost" class="text-sm font-bold text-gray-900 font-mono">$1,800.50</span>
                            </div>

                            <!-- Utility/Misc Cost -->
                            <div class="cost-card flex justify-between items-center p-3 bg-white rounded-lg border border-gray-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700">Utility &amp; Miscellaneous</span>
                                </div>
                                <span id="miscCost" class="text-sm font-bold text-gray-900 font-mono">$450.75</span>
                            </div>

                            <!-- Divider -->
                            <div class="border-t-2 border-dashed border-gray-200 my-2"></div>

                            <!-- Total Estimate -->
                            <div class="flex justify-between items-center p-4 bg-gradient-to-r from-indigo-50 to-violet-50 rounded-xl border border-indigo-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-lg shadow-indigo-200">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <span class="block text-sm font-bold text-gray-900">Approximate Estimate</span>
                                        <span class="text-xs text-gray-500">Total project cost</span>
                                    </div>
                                </div>
                                <span id="totalEstimate" class="text-2xl font-bold text-indigo-600 font-mono">$4,751.25</span>
                            </div>
                        </div>
                    </div>
                    <!-- Appointment Date -->
                    <style>
                      .hid{display:none;}
                    </style>
                    <div id="appointment-container" class="md:col-span-2 hid" style="margin-top:1rem;">
                        <label for="appointment_date" class="input-label">Appointment Date</label>
                        <div class="flex gap-3">
                            <input type="datetime-local" id="appointment_date" name="appointment_date" class="input-field flex-1 px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all bg-gray-50/50" >
                        </div>
                    </div>
                    <div id="adminFeedbackSection" style="margin-top: 2rem; padding: 1rem; background: rgb(248, 249, 250); border-radius: 8px; border-left: 4px solid rgb(16, 185, 129);">
                      <div style="margin-bottom: 0.5rem;">
                        <h6 style="margin: 0; color: #333; font-size: 1rem; font-weight: 600;">
                          <i class="fas fa-comment-dots" style="color: #10b981; margin-right: 0.5rem;"></i>
                          Admin Feedback
                        </h6>
                        <p style="margin: 0.25rem 0 0 0; color: #666; font-size: 0.85rem;">Add notes or feedback for this consultation request</p>
                      </div>
                      <textarea id="adminFeedbackInput" placeholder="Enter your admin feedback here..." style="width: 100%; 
                              min-height: 80px; 
                              padding: 0.5rem; 
                              border: 1px solid #ddd; 
                              border-radius: 4px; 
                              resize: vertical;
                              font-family: inherit;
                              font-size: 0.9rem;" disable>hello</textarea>
                    </div>
                    

                    <!-- Actions -->
                    <div class="flex gap-3 pt-2">
                        <button type="button" onclick="closeModal()" class="flex-1 px-6 py-3 border border-gray-200 text-gray-700 font-medium rounded-xl hover:bg-gray-50 hover:border-gray-300 transition-all duration-200">
                            Close
                        </button>
                        <button id="cancelBtn" type="button" onclick="cancelBooking()" class="flex-1 px-6 py-3 border border-red-200 text-red-700 font-medium rounded-xl hover:bg-red-50 hover:border-red-300 transition-all duration-200">
                            Cancel Booking
                        </button>
                        <button id="actionBtn" type="button" onclick="handleActionButton()" class="flex-1 px-6 py-3 bg-indigo-600 text-white font-medium rounded-xl hover:bg-indigo-700 hover:shadow-lg hover:shadow-indigo-200 transition-all duration-200 flex items-center justify-center gap-2 group" disabled>
                            Loading...
                        </button>
                    </div>
                </form>
                
            </div>
            <!-- Success State (Hidden by default) -->
            <div id="successState" class="hidden flex-col items-center justify-center p-12 text-center">
                <div class="checkmark">
                    <svg class="checkmark-circle" viewBox="0 0 52 52">
                        <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none"></circle>
                        <path class="checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mt-4">Request Submitted!</h3>
                <p class="text-gray-500 mt-2 max-w-sm">We've received your service request. Our team will review the details and contact you shortly.</p>
                <button onclick="resetForm()" class="mt-8 px-8 py-3 bg-indigo-600 text-white font-medium rounded-xl hover:bg-indigo-700 transition-all">
                    Submit Another Request
                </button>
            </div>

        </div>
    </div>
    <div id="viewModal" class=""></div>

  <!-- Appointment Request Modal -->
  <div id="appointmentModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div class="modal-content" style="background: white; padding: 2rem; border-radius: 15px; max-width: 500px; width: 90%; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
      <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h3 style="margin: 0; color: var(--primary);">Request Appointment</h3>
        <button onclick="closeAppointmentModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #666;">&times;</button>
      </div>
      
      <form id="appointmentForm">
        <div class="form-group" style="margin-bottom: 1rem;">
          <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Preferred Date:</label>
          <input type="date" id="appointmentDate" required style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px;" readonly tabindex="-1">
        </div>
        
        <div class="form-group" style="margin-bottom: 1rem;">
          <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Preferred Time:</label>
          <select id="appointmentTime" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px;">
            <option value="">Select a time</option>
            <option value="09:00">9:00 AM</option>
            <option value="10:00">10:00 AM</option>
            <option value="11:00">11:00 AM</option>
            <option value="14:00">2:00 PM</option>
            <option value="15:00">3:00 PM</option>
            <option value="16:00">4:00 PM</option>
            <option value="17:00">5:00 PM</option>
          </select>
        </div>
        
        <div class="form-group" style="margin-bottom: 1rem;">
          <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Service Type:</label>
          <select id="appointmentService" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px;">
            <option value="">Select service</option>
            <option value="consultation">Consultation</option>
            <option value="landscaping">Landscaping</option>
            <option value="maintenance">Maintenance</option>
            <option value="design">Garden Design</option>
          </select>
        </div>
        
        <div class="form-group" style="margin-bottom: 1.5rem;">
          <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Additional Notes:</label>
          <textarea id="appointmentNotes" rows="3" placeholder="Any special requirements or notes..." style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; resize: vertical;"></textarea>
        </div>
        
        <div class="form-actions" style="display: flex; gap: 1rem; justify-content: flex-end;">
          <button type="button" onclick="closeAppointmentModal()" style="padding: 0.75rem 1.5rem; border: 1px solid #ddd; background: #f8f9fa; border-radius: 8px; cursor: pointer;">Cancel</button>
          <button type="submit" style="padding: 0.75rem 1.5rem; background: var(--primary); color: white; border: none; border-radius: 8px; cursor: pointer;">Request Appointment</button>
        </div>
      </form>
    </div>
  </div>
        
  <script src="/landscape/JS/profile_calendar_functions.js"></script>
  <script>
    const Profile = {
      unreadCount: 0, 
    }
    // Log quote requests data to console
    const userQuotes = <?php echo json_encode($userQuotes ?? []); ?>;
    console.log('User Quote Requests:', userQuotes);
    
    document.querySelectorAll(".more-details").forEach(button => {
      button.addEventListener('click', async ()=>{
        const quoteId = button.getAttribute('data-id');
        console.log('More details clicked for quote:', quoteId);
        
        // Find the quote data from userQuotes array
        const quote = userQuotes.find(q => q.id == quoteId);
        if (quote) {
          // Mark quote as read by updating notifBoolean
          try {
            const response = await fetch('/landscape/USER_API/BookingsController.php?action=update_notif_boolean', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body: JSON.stringify({
                booking_id: quoteId,
                notif_boolean: true
              })
            });
            
            const result = await response.json();
            if (result.status === 'success') {
              // Update the quote in local array to reflect read status
              const quoteIndex = userQuotes.findIndex(q => q.id == quoteId);
              if (quoteIndex !== -1) {
                userQuotes[quoteIndex].notifBoolean = true;
              }
              // Update the UI to show as read
              const statusSpan = button.closest('.profile-card').querySelector('span[style*="color"]');
              if (statusSpan) {
                statusSpan.innerHTML = '<i class="fas fa-check-circle"></i> Read';
                statusSpan.style.color = '#10b981';
              }
            }
          } catch (error) {
            console.error('Failed to mark quote as read:', error);
          }
          
          showQuoteDetails(quote);
        }
      });
    });

    function closeModal(id=null) {
      // Handle case where event object is passed instead of string
      let modalId = 'modalOverlay'; // default
      
      if (typeof id === 'string') {
        modalId = id;
      } else if (id && typeof id === 'object') {
        // Event object was passed, use default modal
        //console.warn('Event object passed to closeModal instead of string ID');
      }
      
      const element = document.getElementById(modalId);
      if (element) {
        element.style.display = 'none';
      } else {
        console.warn(`Modal element not found: ${modalId}`);
      }
    }

    function capitalize(str) {
      return str.split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
    }
  
    function dataProcessing(data){
      console.log("raw data: ", data)
      return {
        id: data.id,
        booking_code: data.booking_code,
        user_id: data.user_id,
        service_name: data.service_name,
        appointment_date: data.appointment_date,
        address: data.address,
        status: data.status || "",
        created_at: data.created_at,
        updated_at: data.updated_at,
        notes: data.notes,
        sqm: data.sqm,
        base_price: data.base_price,
        total_cost: data.total_cost,
        labor_cost: data.labor_cost,
        materials_cost: data.materials_cost,
        misc_cost: data.misc_cost,
        user_name: data.user_name,
        user_email: data.user_email,
        user_phone: data.user_phone
      }
    }

    // Function to calculate and update booking estimate
    async function updateBookingEstimate(serviceId, area, bookingId) {
      if (!serviceId || !area || !bookingId) {
        console.error('Missing required data for estimate calculation');
        return false;
      }

      try {
        // Get service base price from API
        const response = await fetch(`/landscape/USER_API/ServicesController.php?action=get_service_price&service_id=${serviceId}`);
        const result = await response.json();

        if (result.status === 'success' && result.data) {
          const basePrice = parseFloat(result.data.base_price) || 0;
          const sqm = parseFloat(area) || 0;
          const estimateTotal = basePrice * sqm;

          // Update booking with calculated values
          const updateResponse = await fetch('/landscape/USER_API/BookingsController.php?action=update_estimate', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              booking_id: bookingId,
              base_price: basePrice,
              sqm: sqm
            })
          });

          const updateResult = await updateResponse.json();
          
          if (updateResult.status === 'success') {
            console.log('Estimate updated successfully:', {
              basePrice: basePrice,
              sqm: sqm,
              estimateTotal: estimateTotal
            });
            return estimateTotal;
          } else {
            console.error('Failed to update estimate:', updateResult.message);
            return false;
          }
        } else {
          console.error('Failed to get service price:', result.message);
          return false;
        }
      } catch (error) {
        console.error('Error calculating estimate:', error);
        return false;
      }
    }

    function showQuoteDetails(data) {
      // Store booking data globally for access in other functions
      window.processedData = data;
      window.currentBookingId = data.id;
      
      // Show the modal first
      document.getElementById('modalOverlay').style.display = 'flex';
      
      // Update modal header
      const modalHeader = document.querySelector('#modalOverlay .text-2xl');
      if (modalHeader) {
        modalHeader.textContent = 'Quote Request Details';
      }
      
      // Update modal subheader
      const modalSubheader = document.querySelector('#modalOverlay .text-indigo-100');
      if (modalSubheader) {
        modalSubheader.textContent = `Booking Code: ${window.processedData.booking_code}`;
      }
      console.log("users: ",window.processedData.id)
      // Update editable badge to show status instead
      const editableBadge = document.querySelector('#modalOverlay .text-xs');
      if (editableBadge) {
        editableBadge.textContent = window.processedData.status || 'Pending';
        editableBadge.className = `text-xs px-2 py-0.5 rounded-full font-medium ${getStatusClass(window.processedData.status)}`;
      }
      
      const adminFeedback = document.querySelector("#adminFeedbackSection")
      if(window.processedData.admin_feedback){
        adminFeedback.style.display = "block";
        document.getElementById("adminFeedbackInput").value = window.processedData.admin_feedback;
      } else {
        adminFeedback.style.display = "none";
      }
      
      // Populate form fields with processed data
      document.getElementById('fullName').value = window.processedData.user_name || '';
      document.getElementById('email').value = window.processedData.user_email || '';
      document.getElementById('serviceName').value = window.processedData.service_name.split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ') + " Services" || 'General Landscaping';
      document.getElementById('area').value = window.processedData.sqm || '';
      document.getElementById('address').value = window.processedData.address || '';
      document.getElementById('message').value = window.processedData.notes || '';
      
      // Initialize appointment date if exists
      const appointmentDateInput = document.getElementById('appointment_date');
      if (appointmentDateInput && window.processedData.appointment_date) {
        

        // 1. Get current date/time
        const futureDate = new Date();
        // 2. Add 3 days
        futureDate.setDate(futureDate.getDate() + 3);

        // 3. Set the time to exactly 07:00 (Local Time)
        futureDate.setHours(7, 0, 0, 0);

        // 4. Format to YYYY-MM-DDTHH:mm
        // We use a custom string build to ensure it matches your local timezone
        const year = futureDate.getFullYear();
        const month = String(futureDate.getMonth() + 1).padStart(2, '0');
        const day = String(futureDate.getDate()).padStart(2, '0');
        const hours = String(futureDate.getHours()).padStart(2, '0');
        const minutes = String(futureDate.getMinutes()).padStart(2, '0');

        const formattedDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;

        // 5. Apply to the element
        appointmentDateInput.min = formattedDateTime; // Prevents selection before this time
        appointmentDateInput.value = formattedDateTime; // Sets the default display value
        
        // appointmentDateInput.value = window.processedData.appointment_date.slice(0, 16);
        
        // Update calendar button to show appointment is set
      }
      
      // Initialize calendar with current booking ID
      const bookingId = window.processedData.id;
      if (bookingId) {
        window.currentCalendarBookingId = bookingId;
      }
      
      // Make specified fields editable
      document.getElementById('fullName').readOnly = false;
      document.getElementById('serviceName').readOnly = true;  // Keep service readonly
      document.getElementById('serviceName').style.pointerEvents = 'none';  // Keep service readonly
      document.getElementById('area').readOnly = false;
      document.getElementById('address').readOnly = false;
      document.getElementById('message').readOnly = false;
      
      // Update cost display
      const laborCost = parseFloat(window.processedData.labor_cost) || 0;
      const materialsCost = parseFloat(window.processedData.materials_cost) || 0;
      const miscCost = parseFloat(window.processedData.misc_cost) || 0;
      
      // Always calculate total cost from individual costs for accuracy
      const calculatedTotalCost = laborCost + materialsCost + miscCost;
      
      // Use calculated total cost, but fallback to stored total_cost if calculation fails
      const totalCost = calculatedTotalCost || parseFloat(window.processedData.total_cost) || 0;
      
      // Display individual costs
      document.getElementById('laborCost').textContent = `₱${laborCost.toFixed(2)}`;
      document.getElementById('materialsCost').textContent = `₱${materialsCost.toFixed(2)}`;
      document.getElementById('miscCost').textContent = `₱${miscCost.toFixed(2)}`;
      
      // Display total cost
      document.getElementById('totalEstimate').textContent = `₱${totalCost.toFixed(2)}`;
      
      // Hide cost breakdown section if all costs are zero
      const costBreakdownSection = document.getElementById('cost_breakdown');
      if (costBreakdownSection) {
        if (laborCost === 0 && materialsCost === 0 && miscCost === 0) {
          costBreakdownSection.style.display = 'none';
        } else {
          costBreakdownSection.style.display = 'block';
        }
      }
      
      // Initialize button state based on costs
      updateActionButton();
      
      // Setup modal footer with multiple action buttons
      const submitButton = document.querySelector('button[type="submit"]');
      const cancelButton = document.querySelector('button[onclick="closeModal()"]');
      
      // Show all buttons
      if (cancelButton) {
        cancelButton.onclick = closeModal;
      }
      
      if (submitButton) {
        submitButton.style.display = 'block';
        submitButton.className = 'flex-1 px-6 py-3 bg-blue-600 text-white font-medium rounded-xl hover:bg-blue-700 transition-all duration-200';
        submitButton.textContent = 'Save';
        submitButton.type = 'button';
        submitButton.onclick = saveProfileChanges;
      }
      
      // Add approve and view buttons
      const modalFooter = document.querySelector('.modal-footer');
      if (modalFooter) {
        // Create approve button
        const approveButton = document.createElement('button');
        approveButton.textContent = 'Continue';
        approveButton.className = 'flex-1 px-6 py-3 bg-green-600 text-white font-medium rounded-xl hover:bg-green-700 transition-all duration-200';
        approveButton.type = 'button';
        approveButton.onclick = approveProfile;
        
        // Create view button
        const viewButton = document.createElement('button');
        viewButton.textContent = 'View Progress';
        viewButton.className = 'flex-1 px-6 py-3 bg-indigo-600 text-white font-medium rounded-xl hover:bg-indigo-700 transition-all duration-200';
        viewButton.type = 'button';
        viewButton.onclick = viewBookingProgress;
        
        // Add buttons to footer (after existing buttons)
        modalFooter.appendChild(approveButton);
        modalFooter.appendChild(viewButton);
      }

      const existingModalFooter = document.querySelector('.modal-footer');
      if (existingModalFooter) {
        // Get the Continue button and change it to Approve
        const existingButton = existingModalFooter.querySelector('button[onclick*="approveProfile"]');
        if (existingButton) {
          existingButton.textContent = 'Approve';
          existingButton.onclick = approveProfileFinal;
        }
      }
    }

    function getStatusClass(status) {
      const statusLower = (status || '').toLowerCase();
      switch(statusLower) {
        case 'approved':
        case 'completed':
          return 'bg-green-100 text-green-800';
        case 'pending':
        case 'reviewing':
          return 'bg-yellow-100 text-yellow-800';
        case 'cancelled':
          return 'bg-red-100 text-red-800';
        default:
          return 'bg-gray-100 text-gray-800';
      }
    }

    function toggleMenu() {
     document.getElementById('navLinks').classList.toggle('active'); 
    }
    const monthSelect = document.getElementById('monthSelect');
    const yearSelect = document.getElementById('yearSelect');
    const calendarGrid = document.getElementById('calendarGrid');
    const monthDisplay = document.getElementById('monthDisplay');

    const months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    const now = new Date();

    function init() {
      months.forEach((m, i) => {
        let opt = new Option(m, i);
        if (i === now.getMonth()) opt.selected = true;
        monthSelect.add(opt);
      });

      for (let y = 2020; y <= 2030; y++) {
        let opt = new Option(y, y);
        if (y === now.getFullYear()) opt.selected = true;
        yearSelect.add(opt);
      }
      
      // Fetch user bookings first, then update calendar
      fetchUserBookings().then(() => {
        // Auto-navigate to first month with bookings
        navigateToFirstBookingMonth();
        updateCalendar();
      });
    }

    function navigateToFirstBookingMonth() {
      if (!window.userBookings || !Array.isArray(window.userBookings) || window.userBookings.length === 0) {
        return; // No bookings to navigate to
      }

      // Find the earliest upcoming booking
      const now = new Date();
      const upcomingBookings = window.userBookings
        .filter(booking => booking.appointment_date && new Date(booking.appointment_date) >= now)
        .sort((a, b) => new Date(a.appointment_date) - new Date(b.appointment_date));

      if (upcomingBookings.length > 0) {
        const firstBooking = upcomingBookings[0];
        const bookingDate = new Date(firstBooking.appointment_date);
        
        // Set the month and year selects to the first booking's month
        monthSelect.value = bookingDate.getMonth();
        yearSelect.value = bookingDate.getFullYear();
        
        console.log(`Auto-navigated to ${bookingDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })} for first upcoming booking`);
      }
    }

    function updateCalendar() {
      const month = parseInt(monthSelect.value);
      const year = parseInt(yearSelect.value);
      const now = new Date();
      
      // Prepare the lookup map once per month change
      const bookingsMap = getBookingsMap();
      
      monthDisplay.innerText = `${months[month]} ${year}`;
      calendarGrid.innerHTML = '';

      // Render Day Headers
      ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"].forEach(d => {
        const div = document.createElement('div');
        div.className = 'day-name';
        div.innerText = d;
        calendarGrid.appendChild(div);
      });

      const firstDay = new Date(year, month, 1).getDay();
      const daysInMonth = new Date(year, month + 1, 0).getDate();

      // 1. Render Empty Slots
      for (let i = 0; i < firstDay; i++) {
        const div = document.createElement('div');
        div.className = 'calendar-day empty';
        calendarGrid.appendChild(div);
      }

      // 2. Render Actual Days
      for (let d = 1; d <= daysInMonth; d++) {
        const div = document.createElement('div');
        div.className = 'calendar-day';
        
        // Create local YYYY-MM-DD string
        const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        
        // Highlight "Today"
        if (d === now.getDate() && month === now.getMonth() && year === now.getFullYear()) {
          div.classList.add('today');
        }
        
        // Consistent DOM structure for all day types
        const dayNumber = document.createElement('div');
        dayNumber.className = 'day-number';
        dayNumber.innerText = d;
        div.appendChild(dayNumber);
        
        // Instant Lookups
        const dayBookings = bookingsMap[dateString] || [];
        const isUnavailable = window.unavailableDates?.includes(dateString);

        // Debug: Log specific dates that should have bookings
        if (dateString === '2026-05-19' || dateString === '2026-05-26') {
          console.log(`Profile Calendar Debug for ${dateString}:`, {
            dayBookings: dayBookings,
            bookingsLength: dayBookings.length,
            isUnavailable: isUnavailable,
            dateString: dateString
          });
        }

        if (isUnavailable) {
          div.classList.add('unavailable');
          div.onclick = () => alert(`Date ${dateString} is unavailable.`);
        } 
        else if (dayBookings.length > 0) {
          div.classList.add('has-booking');
          console.log(`Profile Calendar: Applied has-booking class to ${dateString}`);
          // Pass the date and the specific bookings for this day
          div.onclick = () => showBookingsForDate(dateString, dayBookings);
        } 
        // else {
        //   div.onclick = () => showAppointmentModal(dateString);
        // }
        
        calendarGrid.appendChild(div);
      }
    }

    /**
     * Creates a Map of bookings keyed by date string (YYYY-MM-DD)
     * call this at the start of updateCalendar to avoid redundant filtering.
     */
    function getBookingsMap() {
      const map = {};
      if (!window.userBookings || !Array.isArray(window.userBookings)) return map;

      console.log('Profile getBookingsMap - window.userBookings:', window.userBookings);
      
      window.userBookings.forEach(booking => {
        // Debug: Log every booking being processed
        console.log(`🔍 Processing booking: ID ${booking.id}, Status: ${booking.status}, Date: ${booking.appointment_date}, Has Date: ${!!booking.appointment_date}`);
        
        if (!booking.appointment_date) {
          console.log(`❌ SKIPPED booking ID ${booking.id}: No appointment_date`);
          return;
        }

        // Debug: Log booking status and date
        if (booking.appointment_date && booking.appointment_date.includes('2026-05')) {
          console.log(`Processing May booking: ID ${booking.id}, Status: ${booking.status}, Date: ${booking.appointment_date}`);
        }
        
        // Special debugging for booking ID 68
        if (booking.id === 68) {
          console.log(`🔍 SPECIAL DEBUG - Booking 68:`, {
            originalDate: booking.appointment_date,
            dateType: typeof booking.appointment_date,
            dateLength: booking.appointment_date?.length
          });
        }

        // Convert any date format to a consistent local YYYY-MM-DD
        const bDate = new Date(booking.appointment_date);
        const dateKey = `${bDate.getFullYear()}-${String(bDate.getMonth() + 1).padStart(2, '0')}-${String(bDate.getDate()).padStart(2, '0')}`;

        if (!map[dateKey]) map[dateKey] = [];
        map[dateKey].push(booking);
      });
      
      console.log('Profile getBookingsMap - created map:', map);
      console.log('Profile getBookingsMap - current month dates:', Object.keys(map).filter(date => 
        date.startsWith('2026-05') // Filter for current month (May 2026)
      ));
      
      return map;
    }

    function showBookingsForDate(dateString, bookings) {
      if (!bookings || bookings.length === 0) return;

      // Split string to avoid UTC shift when creating Date object
      const [y, m, d] = dateString.split('-').map(Number);
      const localDate = new Date(y, m - 1, d);
      
      const formattedDate = localDate.toLocaleDateString('en-PH', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
      });
      
      let bookingDetails = `Bookings for ${formattedDate}:\n${'-'.repeat(30)}\n`;
      
      bookings.forEach((booking, index) => {
        bookingDetails += `${index + 1}. ${booking.service_name || 'Service'}\n`;
        bookingDetails += `   Status: ${booking.status || 'Pending'}\n`;
        if (booking.booking_code) bookingDetails += `   Code: ${booking.booking_code}\n`;
        bookingDetails += '\n';
      });
      
      alert(bookingDetails);
    }

    function saveProfileChanges() {
      // Get current values from form
      const bookingId = window.currentBookingId || window.processedData.id;
      
      if (!bookingId) {
        alert('No booking ID found for saving');
        return;
      }
      
      const formData = {
        fullName: document.getElementById('fullName').value,
        email: document.getElementById('email').value,
        address: document.getElementById('address').value,
        area: document.getElementById('area').value,
        message: document.getElementById('message').value
      };
      
      console.log('Saving profile changes:', formData);
      
      // Send update request to set notifBoolean = false
      fetch('/landscape/USER_API/BookingsController.php?action=update_client_fields', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          booking_id: bookingId,
          address: formData.address,
          sqm: formData.area,
          notes: formData.message,
          notifBoolean: false // Client changes reset notification status
        })
      })
      .then(response => response.json())
      .then(result => {
        if (result.status === 'success') {
          alert('Changes saved successfully!');
          closeModal();
          // Optionally refresh the page or update the UI
          location.reload();
        } else {
          alert('Error: ' + (result.message || 'Failed to save changes'));
        }
      })
      .catch(error => {
        console.error('Error saving changes:', error);
        alert('Error saving changes. Please try again.');
      });
    }
    
    function viewBookingProgress() {
      console.log('Viewing booking progress');
      
      // Get current booking data
      const bookingId = window.currentBookingId || window.processedData.id;
      const currentStatus = window.processedData.status;
      
      // Create progress information
      const progressInfo = `
Booking Progress Details
=====================

Booking ID: ${window.processedData.booking_code || bookingId}
Current Status: ${currentStatus}
Created: ${window.processedData.created_at || 'N/A'}
Last Updated: ${window.processedData.updated_at || 'N/A'}

Cost Breakdown:
- Labor Cost: ₱${(parseFloat(window.processedData.labor_cost) || 0).toFixed(2)}
- Materials Cost: ₱${(parseFloat(window.processedData.materials_cost) || 0).toFixed(2)}
- Miscellaneous Cost: ₱${(parseFloat(window.processedData.misc_cost) || 0).toFixed(2)}
- Total Cost: ₱${(parseFloat(window.processedData.total_cost) || 
    (parseFloat(window.processedData.labor_cost || 0) + parseFloat(window.processedData.materials_cost || 0) + parseFloat(window.processedData.misc_cost || 0))).toFixed(2)}

Status Progress:
${getStatusProgress(currentStatus)}
      `;
      
      alert(progressInfo);
    }
    
    function getStatusProgress(status) {
      const statusLower = (status || '').toLowerCase();
      switch(statusLower) {
        case 'consultation':
          return '📋 Consultation → ⏳ Pending → 🚀 Active → ✅ Completed';
        case 'pending':
          return '✅ Consultation → 📋 Pending → 🚀 Active → ✅ Completed';
        case 'active':
          return '✅ Consultation → ✅ Pending → 📋 Active → ✅ Completed';
        case 'completed':
          return '✅ Consultation → ✅ Pending → ✅ Active → 📋 Completed';
        case 'cancelled':
          return '❌ Booking Cancelled';
        default:
          return '📋 Unknown Status';
      }
    }
    
    //UID 011
    const input = document.getElementById('appointment_date');

    // 1. Calculate the minimum date (3 days from today)
    const minDateObj = new Date();
    minDateObj.setDate(minDateObj.getDate() + 3); 

    // Format to YYYY-MM-DD
    const minDateIso = minDateObj.toISOString().split('T')[0];

    // Set the min attribute to 10:00 AM on that future date
    input.setAttribute('min', `${minDateIso}T10:00`);

    input.addEventListener('input', (e) => {
        const value = e.target.value;
        if (!value) return;

        const [selectedDate, selectedTime] = value.split('T');
        const unavailableDates = window.unavailableDates || [];
        
        const openTime = "10:00";
        const closeTime = "17:00";

        let errorMessage = '';

        // 2. Check for the 2-day lead time buffer
        const selectedDateObj = new Date(selectedDate);
        const today = new Date();
        today.setHours(0, 0, 0, 0); // Reset time for accurate day comparison
        
        // Calculate difference in days
        const diffTime = selectedDateObj - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays < 3) {
            errorMessage = 'Appointments must be booked at least 3 days in advance.';
        } else if (unavailableDates.includes(selectedDate)) {
            errorMessage = 'This date is Unavailable.';
        } else if (selectedTime < openTime || selectedTime > closeTime) {
            errorMessage = 'Appointments are only available between 10:00 AM and 5:00 PM.';
        }

        if (errorMessage) {
            alert(errorMessage); // Warning Modal UIMW 008
            e.target.value = '';
            e.target.focus();
        }
    });

    function approveProfile() {
      // Get current booking ID from processedData (assuming it's stored globally or passed)
      const bookingId = window.currentBookingId || window.processedData.id;
      
      if (!bookingId) {
        alert('No booking ID found for approval');// Warning Modal UIMW 008
        return;
      }
      document.getElementById("appointment-container").classList.remove('hid');
      document.getElementById('actionBtn').textContent = "Approve";
      
    }
    
    function approveProfileFinal() {
        // Get booking ID from processed data
        const bookingId = window.currentBookingId || window.processedData.id;
        
        if (!bookingId) {
            alert('No booking ID found for approval');// Warning Modal UIMW 008
            return;
        }
        
        const appointmentDateInput = document.getElementById('appointment_date');
        const appointmentDate = appointmentDateInput ? appointmentDateInput.value : null;
        //UID 111
        const dat = window.unavailableDate ?? [];
        if(dat.includes(appointmentDate.split('T')[0])){
          alert('This date is unavailale. Please select a different day.');// Warning Modal UIMW 008
          return;
        }
        if (!appointmentDate) {
          console.log('No appointment date set, showing calendar modal...');// Warning Modal UIMW 008
          return;
        }
        alert('processing the aproval', appointmentDate)// Info Modal UIMI 009
        processApprovalAfterAppointment(bookingId, appointmentDate);
    }
    
    
    // Function to process approval after appointment is set
    function processApprovalAfterAppointment(bookingId, appointmentDate) {
      console.log('Processing approval after appointment selection...');
      
      // Extract date part only (YYYY-MM-DD) for availability checking
      const dateOnly = appointmentDate.includes('T') ? appointmentDate.split('T')[0] : appointmentDate.split(' ')[0];
      
      // Check if date is available first
      fetch(`/landscape/USER_API/AppointmentController.php?action=check_availability&date=${dateOnly}`)
        .then(response => response.json())
        .then(result => {
          if (!result.is_available) {
            const errorMsg = result.validation_result && typeof result.validation_result === 'string' ? result.validation_result : 'Date is not available';
            throw new Error(errorMsg);
          }
          
          // Get current status to determine payment type
          const currentStatus = window.processedData.status;
          
          let paymentAmount = 0;
          let requirePayment = false;
          
          // Continue with payment processing
          processPaymentAndApproval(bookingId, appointmentDate, currentStatus);
        })
        .catch(error => {
          console.error('Date availability check failed:', error);
          alert(error.message || 'Failed to check date availability. Please try again.');
        });
    }
    
    function showUnavailableDateOptions(appointmentDate, callback) {
      // Default to choice 1 for one appointment per day scheduling
      console.log('🗓️ Auto-marking date as unavailable (one appointment per day policy)');
      markDateUnavailable(appointmentDate, 'Appointment booked - one appointment per day', callback);
    }
    
    function handleUnavailableDateChoice(choice, appointmentDate, callback) {
      switch(choice) {
        case '1':
          // Mark single date as unavailable
          markDateUnavailable(appointmentDate, 'Appointment booked', callback);
          break;
        case '2':
          // Keep date available
          console.log('Date kept available for other appointments');
          callback();
          break;
        case '3':
          // Set recurring weekly unavailability
          const dayOfWeek = new Date(appointmentDate).toLocaleDateString('en-PH', { weekday: 'long' });
          if (confirm('Mark every ' + dayOfWeek + ' as unavailable?')) {
            markRecurringUnavailable(appointmentDate, dayOfWeek, callback);
          } else {
            callback();
          }
          break;
        case '4':
          // Custom range
          showCustomRangeOptions(appointmentDate, callback);
          break;
        default:
          alert('Invalid choice. Please try again.');
          showUnavailableDateOptions(appointmentDate, callback);
          break;
      }
    }
    
    function markDateUnavailable(date, reason, callback) {
      fetch('/landscape/USER_API/AppointmentController.php?action=add_unavailable', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          date: date,
          reason: reason,
          admin_id: window.currentUser?.id || 1
        })
      })
      .then(response => response.json())
      .then(result => {
        if (result.status === 'success') {
          console.log('✅ Date marked as unavailable:', date);
          callback();
        } else {
          console.warn('⚠️ Could not mark date unavailable:', result.message);
          if (confirm('Continue with approval anyway?')) {
            callback();
          }
        }
      })
      .catch(error => {
        console.error('Error marking date unavailable:', error);
        if (confirm('Continue with approval anyway?')) {
          callback();
        }
      });
    }
    
    function markRecurringUnavailable(date, dayOfWeek, callback) {
      // For weekly recurring, we'll mark the next 4 occurrences
      const dates = [];
      const startDate = new Date(date);
      
      for (let i = 0; i < 4; i++) {
        const nextDate = new Date(startDate);
        nextDate.setDate(startDate.getDate() + (i * 7));
        dates.push({
          date: nextDate.toISOString().split('T')[0],
          reason: `Recurring ${dayOfWeek} appointment`
        });
      }
      
      fetch('/landscape/USER_API/AppointmentController.php?action=bulk_add_unavailable', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          dates: dates,
          admin_id: window.currentUser?.id || 1
        })
      })
      .then(response => response.json())
      .then(result => {
        if (result.status === 'success') {
          console.log('✅ Recurring dates marked as unavailable');
          alert(`Marked ${result.success} dates as unavailable`);
          callback();
        } else {
          console.warn('⚠️ Could not mark recurring dates unavailable');
          if (confirm('Continue with approval anyway?')) {
            callback();
          }
        }
      })
      .catch(error => {
        console.error('Error marking recurring dates unavailable:', error);
        if (confirm('Continue with approval anyway?')) {
          callback();
        }
      });
    }
    
    function showCustomRangeOptions(appointmentDate, callback) {
      const startDate = prompt('Enter start date (YYYY-MM-DD):', appointmentDate);
      const endDate = prompt('Enter end date (YYYY-MM-DD):');
      
      if (!startDate || !endDate) {
        callback();
        return;
      }
      
      const reason = prompt('Reason for unavailability:', 'Custom appointment booking period');
      
      // Generate date range
      const dates = [];
      const start = new Date(startDate);
      const end = new Date(endDate);
      
      for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
        dates.push({
          date: d.toISOString().split('T')[0],
          reason: reason
        });
      }
      
      fetch('/landscape/USER_API/AppointmentController.php?action=bulk_add_unavailable', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          dates: dates,
          admin_id: window.currentUser?.id || 1
        })
      })
      .then(response => response.json())
      .then(result => {
        if (result.status === 'success') {
          console.log('✅ Custom range dates marked as unavailable');
          alert(`Marked ${result.success} dates as unavailable`);
          callback();
        } else {
          console.warn('⚠️ Could not mark custom range unavailable');
          if (confirm('Continue with approval anyway?')) {
            callback();
          }
        }
      })
      .catch(error => {
        console.error('Error marking custom range unavailable:', error);
        if (confirm('Continue with approval anyway?')) {
          callback();
        }
      });
    }
    
    function processPaymentAndApproval(bookingId, appointmentDate, currentStatus) {
      let paymentAmount = 0;
      let requirePayment = false;
      
      // Determine payment requirements based on status transition
      if (currentStatus === 'Consultation') {
        // Consultation → Pending: NO payment required
        requirePayment = false;
        paymentAmount = 0;
        console.log('📋 Consultation → Pending: No payment required');
        
      } else if (currentStatus === 'Pending') {
        // Pending → Active: Initial payment required
        requirePayment = true;
        const totalCost = parseFloat(document.getElementById('totalEstimate').textContent.replace('$', '')) || 0;
        
        const paymentAmountStr = prompt('Initial payment required to activate booking.\n\nTotal Cost: $' + totalCost.toFixed(2) + '\n\nPlease enter initial payment amount:');
        
        if (!paymentAmountStr || isNaN(paymentAmountStr) || parseFloat(paymentAmountStr) <= 0) {
          alert('Please enter a valid initial payment amount greater than 0');
          return;
        }
        
        paymentAmount = parseFloat(paymentAmountStr);
        console.log('🚀 Pending → Active: Initial payment $' + paymentAmount.toFixed(2));
        
      } else if (currentStatus === 'Active') {
        // Active → Complete: Final payment required
        requirePayment = true;
        const totalCost = parseFloat(document.getElementById('totalEstimate').textContent.replace('$', '')) || 0;
        
        const remainingBalanceStr = prompt('Final payment required to complete booking.\n\nTotal Cost: $' + totalCost.toFixed(2) + '\n\nPlease enter final payment amount:');
        
        if (!remainingBalanceStr || isNaN(remainingBalanceStr) || parseFloat(remainingBalanceStr) <= 0) {
          alert('Please enter a valid final payment amount greater than 0');
          return;
        }
        
        paymentAmount = parseFloat(remainingBalanceStr);
        console.log('✅ Active → Complete: Final payment $' + paymentAmount.toFixed(2));
        
      } else {
        alert('Cannot approve booking from current status: ' + currentStatus);
        return;
      }
      
      // Call BookingsController update function to advance status
      fetch('/landscape/USER_API/BookingsController.php?action=update', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          id: bookingId,
          amount: paymentAmount,
          require_payment: requirePayment,
          appointment_date: appointmentDate
        })
      })
      .then(response => response.json())
      .then(result => {
        if (result.status === 'success') {
          const statusMessage = currentStatus === 'Consultation' ? 
            'Booking moved to Pending status!' : 
            'Payment processed and booking advanced successfully!';
          
          alert(statusMessage);
          closeModal();
          // Optionally refresh the page or update the UI
          location.reload();
        } else {
          alert('Error: ' + (result.message || 'Failed to approve booking'));
        }
      })
      .catch(error => {
        console.error('Error approving booking:', error);
        alert('Error approving booking. Please try again.');
      });
    }
    
    // View booking details - similar to admin booking view
    const viewBookingDetails = async(bookingId = null) =>{
      
      // Get booking data - either from parameter or processedData
      let booking;
      
      if (bookingId && window.userBookings) {
        // Find booking from userBookings array
        booking = window.userBookings.find(b => b.id == bookingId);
      } else {
        // Fallback to processedData (for quote details)
        booking = window.processedData;
      }
      
      if (!booking) {
        alert('Booking not found');
        return;
      }

      try {
            const response = await fetch('/landscape/USER_API/BookingsController.php?action=update_notif_boolean', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body: JSON.stringify({
                booking_id: bookingId,
                notif_boolean: true
              })
            });
            
            const result = await response.json();
            if (result.status === 'success') {
              console.log('Successfully marked booking as read:', bookingId);
              // Update the booking in local array to reflect read status
              const bookingIndex = window.userBookings.findIndex(b => b.id == bookingId);
              if (bookingIndex !== -1) {
                window.userBookings[bookingIndex].notifBoolean = true;
                console.log('Updated local booking data');
              }
              
              // Find and update the original button/card that was clicked
              const originalButton = document.querySelector(`button[data-id="${bookingId}"]`);
              if (originalButton) {
                const profileCard = originalButton.closest('.profile-card');
                if (profileCard) {
                  const statusSpan = profileCard.querySelector('span[style*="color"]');
                  if (statusSpan) {
                    statusSpan.innerHTML = '<i class="fas fa-check-circle"></i> Read';
                    statusSpan.style.color = '#10b981';
                    console.log('Updated UI to show as read');
                  }
                }
              }
            } else {
              console.error('Failed to update notification status:', result.message);
            }
          } catch (error) {
            console.error('Failed to mark booking as read:', error);
          }
      
      // Set current booking ID for other functions
      window.currentBookingId = booking.id;
      window.processedData = booking;
      
      console.log('🔍 Loading booking details for ID:', booking.id, ": ", booking);
      
      // Create modal similar to admin view
      const modalHTML = `<div class="relative w-full max-w-4xl max-h-[90vh] bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden flex flex-col">
      
      <!-- BEGIN: Fixed Header Section -->
      <header class="p-5 md:p-6 border-b border-slate-200 dark:border-slate-700 flex-shrink-0">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <div class="flex items-center gap-2 text-slate-500 dark:text-slate-400 mb-1">
              <span class="material-symbols-outlined text-sm">history</span>
              <span class="text-[10px] font-bold uppercase tracking-widest">Project History</span>
            </div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 dark:text-white">${booking.address || "No Address!!!!"}</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">ID: ${booking.booking_code} • ${capitalize(booking.service_name)}</p>
          </div>
          <div class="flex items-center gap-3">
            <span class="status-badge ${booking.status.toLowerCase()}">${booking.status}</span>
            <button onclick="closeModal('viewModal')" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-full transition-colors text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
              <span class="material-symbols-outlined">close</span>
            </button>
          </div>
        </div>
      </header>
      <!-- END: Header Section -->

      <!-- BEGIN: Scrollable Content Area -->
      <div class="flex-grow overflow-y-auto no-scrollbar">
        
        <!-- Progress Stepper Section -->
        <section class="p-6 bg-slate-50/50 dark:bg-slate-900/20 border-b border-slate-100 dark:border-slate-700/50">
          <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 relative" id="stepper-root">
            <!-- Step 1 -->
            <div class="step-item flex md:flex-col items-center gap-4 md:gap-2 z-10 flex-1 w-full" data-step="1">
              <div class="step-circle w-9 h-9 rounded-full flex items-center justify-center border-2 stepper-transition" data-purpose="status-indicator">
                <span class="material-symbols-outlined text-lg">mail</span>
              </div>
              <p class="text-xs font-semibold step-label">Quotation</p>
            </div>
            <!-- Step 2 -->
            <div class="step-item flex md:flex-col items-center gap-4 md:gap-2 z-10 flex-1 w-full" data-step="2">
              <div class="step-circle w-9 h-9 rounded-full flex items-center justify-center border-2 stepper-transition" data-purpose="status-indicator">
                <span class="material-symbols-outlined text-lg">check_circle</span>
              </div>
              <p class="text-xs font-semibold step-label">In Progress</p>
            </div>
            <!-- Step 3 -->
            <div class="step-item flex md:flex-col items-center gap-4 md:gap-2 z-10 flex-1 w-full" data-step="3">
              <div class="step-circle w-9 h-9 rounded-full flex items-center justify-center border-2 stepper-transition" data-purpose="status-indicator">
                <span class="material-symbols-outlined text-lg">pending_actions</span>
              </div>
              <p class="text-xs font-semibold step-label">Completed</p>
            </div>
            <!-- Step 4 -->
            <!--<div class="step-item flex md:flex-col items-center gap-4 md:gap-2 z-10 flex-1 w-full" data-step="4">
              <div class="step-circle w-9 h-9 rounded-full flex items-center justify-center border-2 stepper-transition" data-purpose="status-indicator">
                <span class="material-symbols-outlined text-lg">celebration</span>
              </div>
              <p class="text-xs font-semibold step-label">Completed</p>
            </div>-->
            <!-- Background Progress Line (Desktop) -->
            <div class="hidden md:block absolute top-[18px] left-0 w-full h-0.5 bg-slate-200 dark:bg-slate-700 -z-0">
              <div class="h-full bg-primary stepper-transition w-0" id="progress-bar-horizontal"></div>
            </div>
          </div>
        </section>

        <!-- Details Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-0">
          <!-- Documentation Section -->
          <section class="p-6 border-b lg:border-b-0 lg:border-r border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-2 mb-6">
              <span class="material-symbols-outlined text-primary">folder_open</span>
              <h2 class="font-bold text-base">Documentation</h2>
            </div>
            <div class="space-y-3" id="booking-files-container">
              <!-- Files will be dynamically inserted here -->
            </div>
          </section>

          <!-- Portfolio Section -->
          <section class="p-6">
            <div class="flex items-center gap-2 mb-6">
              <span class="material-symbols-outlined text-primary">photo_library</span>
              <h2 class="font-bold text-base">Project Portfolio</h2>
            </div>
            <div class="grid grid-cols-2 gap-3" id="portfolio-images-container">
              <!-- Portfolio images will be dynamically inserted here -->
            </div>
          </section>
        </div>
      </div>
      <!-- END: Scrollable Content Area -->

      <!-- BEGIN: Fixed Footer Section -->
      <footer class="p-5 md:p-6 bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 flex-shrink-0 flex flex-col sm:flex-row justify-between items-center gap-4">
        <p class="text-[10px] text-slate-500 font-medium"><!--Last modified by Admin • Jan 06, 2024--></p>
        <div class="flex gap-2 w-full sm:w-auto">
            <button onclick="closeModal('viewModal')" class="flex-1 sm:flex-none px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl transition-all">
                close
            </button>
            ${booking.status === 'Consultation' || booking.status === 'Pending' || booking.status === 'Active' ? `
            <button onclick="cancelBooking(window.currentBookingId)" class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-6 py-2 bg-red-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-red-600/20 hover:shadow-red-600/40 hover:scale-[1.02] transition-all">
                <span class="material-symbols-outlined text-sm">cancel</span>
                Cancel Booking
            </button>
        ` : `
            <button disabled class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-6 py-2 bg-gray-400 text-white rounded-xl text-sm font-bold cursor-not-allowed opacity-50">
                <span class="material-symbols-outlined text-sm">block</span>
                Cannot Cancel
            </button>
        `}
        </div>
      </footer>
      <!-- END: Footer Section -->

    </div>
            `;
      
      // Create and show modal
      const modalOverlay = document.getElementById("viewModal");
      
      modalOverlay.className = 'fixed inset-0 z-50 items-center justify-center p-4 bg-black/40 backdrop-blur-sm';
      modalOverlay.innerHTML = modalHTML;

      modalOverlay.style.display = 'flex';
      // Add close on backdrop click
      modalOverlay.addEventListener('click', (e) => {
        if (e.target === modalOverlay) {
          closeModal('viewModal');
        }
      });
      
      // Display booking files if available in booking data
      // Display files from booking data (now always available)
      displayBookingFilesFromBooking(booking);
      
      // Initialize progress stepper
      initializeProgressStepper(booking.status);
    }
    
    // Helper functions for progress display
    function getProgressClass(step, status) {
      const statusProgress = {
        'Consultation': 0,
        'Pending': 1,
        'Active': 2,
        'Completed': 3,
        'Cancelled': 0
      };
      
      const currentStep = statusProgress[status] || 0;
      
      if (currentStep >= step) {
        return 'bg-green-500 border-green-500 text-white';
      } else {
        return 'bg-gray-200 border-gray-300 text-gray-500';
      }
    }
    
    function getProgressWidth(status) {
      const statusProgress = {
        'Consultation': 0,
        'Pending': 33,
        'Active': 66,
        'Completed': 100,
        'Cancelled': 0
      };
      
      return statusProgress[status] || 0;
    }
    
    function toggleMenu() {
      document.getElementById('navLinks').classList.toggle('active');
    }

    // Function to display booking files dynamically
    function displayBookingFiles(files) {
      const container = document.getElementById('booking-files-container');
      if (!container) return;
      
      if (!files || files.length === 0) {
        container.innerHTML = `
          <div class="text-center py-8 text-slate-500 dark:text-slate-400">
            <span class="material-symbols-outlined text-2xl mb-2">folder_open</span>
            <p class="text-sm">No files uploaded yet</p>
          </div>
        `;
        return;
      }
      
      const fileIcons = {
        'blueprint': 'architecture',
        'quotation': 'request_quote', 
        'agreement': 'description',
        'projectDocumentation': 'folder_open',
        'portfolio': 'image'
      };
      
      const fileColors = {
        'blueprint': 'bg-red-50 dark:bg-red-900/20 text-red-600',
        'quotation': 'bg-blue-50 dark:bg-blue-900/20 text-blue-600',
        'agreement': 'bg-green-50 dark:bg-green-900/20 text-green-600',
        'projectDocumentation': 'bg-purple-50 dark:bg-purple-900/20 text-purple-600',
        'portfolio': 'bg-orange-50 dark:bg-orange-900/20 text-orange-600'
      };
      
      const fileLabels = {
        'blueprint': 'Blueprint',
        'quotation': 'Quotation',
        'agreement': 'Agreement', 
        'projectDocumentation': 'Documentation',
        'portfolio': 'Portfolio Images'
      };
      
      container.innerHTML = files.map(file => {
        const icon = fileIcons[file.file_type] || 'description';
        const colorClass = fileColors[file.file_type] || 'bg-slate-50 dark:bg-slate-900/20 text-slate-600';
        const label = fileLabels[file.file_type] || file.file_type;
        const fileSize = file.file_size ? `${(file.file_size / (1024 * 1024)).toFixed(1)} MB` : 'Unknown size';
        const uploadDate = file.uploaded_at ? new Date(file.uploaded_at).toLocaleDateString() : 'Unknown date';
        
        return `
          <div class="flex items-start gap-4 p-3 rounded-xl border border-transparent hover:border-slate-200 dark:hover:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-all cursor-pointer group"
               onclick="downloadFile('${file.file_path}', '${file.original_name}', '${file.file_type}')">
            <div class="p-2 ${colorClass} rounded-lg">
              <span class="material-symbols-outlined">${icon}</span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium truncate">${label}</p>
              <p class="text-[10px] text-slate-500">${uploadDate} • ${fileSize}</p>
              <p class="text-[9px] text-slate-400 mt-1">${label}</p>
            </div>
            <span class="material-symbols-outlined text-slate-400 text-lg group-hover:text-primary transition-colors">download</span>
          </div>
        `;
      }).join('');
    }

    // Function to download files
    window.downloadFile = (filePath, originalName, fileType) => {
      const downloadName = `${fileType}.${originalName.split('.').pop()}`;
      const downloadUrl = `/landscape/USER_API/download.php?file=${encodeURIComponent(filePath)}&name=${encodeURIComponent(downloadName)}`;
      
      // Create a temporary link to trigger download
      const link = document.createElement('a');
      link.href = downloadUrl;
      link.download = downloadName;
      link.style.display = 'none';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      
      console.log(`Downloading file: ${downloadName}`, { filePath, fileType });
    }

    // Function to display portfolio images dynamically
    function displayPortfolioImages(files) {
      const container = document.getElementById('portfolio-images-container');
      if (!container) return;
      
      if (!files || files.length === 0) {
        container.innerHTML = `
          <div class="col-span-2 text-center py-8 text-slate-500 dark:text-slate-400">
            <span class="material-symbols-outlined text-2xl mb-2">photo_library</span>
            <p class="text-sm">No portfolio images uploaded yet</p>
          </div>
        `;
        return;
      }
      
      // Filter only portfolio files
      const portfolioFiles = files.filter(file => file.file_type === 'portfolio');
      
      if (portfolioFiles.length === 0) {
        container.innerHTML = `
          <div class="col-span-2 text-center py-8 text-slate-500 dark:text-slate-400">
            <span class="material-symbols-outlined text-2xl mb-2">photo_library</span>
            <p class="text-sm">No portfolio images uploaded yet</p>
          </div>
        `;
        return;
      }
      
      container.innerHTML = portfolioFiles.map((file, index) => {
        const imageUrl = `/landscape/USER_API/download.php?file=${encodeURIComponent(file.file_path)}&view=1`;
        
        return `
          <div class="relative group aspect-square rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-700 shadow-inner">
            <img alt="Portfolio image ${index + 1}" class="w-full h-full object-cover" src="${imageUrl}" />
            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
              <button onclick="window.open('${imageUrl}', '_blank')" class="text-white bg-white/20 p-2 rounded-full backdrop-blur-sm hover:bg-white/30 transition-colors">
                <span class="material-symbols-outlined">zoom_in</span>
              </button>
            </div>
          </div>
        `;
      }).join('');
    }

    // Function to export booking report as PDF
    window.exportBookingReport = async(btn) => {
      const originalContent = btn.innerHTML;

      try {
        // Get current booking ID from the modal
        const bookingId = window.currentBookingId || window.processedData.id;
        
        if (!bookingId) {
          throw new Error('Booking ID not found');
        }
        
        // Show loading state
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-outlined animate-spin">hourglass_empty</span> Generating...';
        
        // Fetch booking data including files and transactions
        const response = await fetch(`/landscape/USER_API/generate_booking_report.php?id=${bookingId}`, {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json'
          }
        });
        
        if (!response.ok) {
          throw new Error('Failed to generate report');
        }
        
        // Create blob from response and download
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `booking_report_${bookingId}.pdf`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
        console.log(`Booking report generated for booking ${bookingId}`);
        
      } catch (error) {
        console.error('Error generating booking report:', error);
        alert('Failed to generate report. Please try again.');
      } finally {
        // Restore button state
        btn.disabled = false;
        btn.innerHTML = originalContent;
      }
    }

    // Function to display booking files from booking data
    function displayBookingFilesFromBooking(booking) {
      const files = booking.files || [];
      displayBookingFiles(files);
      displayPortfolioImages(files);
    }

    // Function to initialize progress stepper
    function initializeProgressStepper(status) {
      const statusProgress = {
        'Consultation': 0,
        'Pending': 1,
        'Active': 2,
        'Completed': 3,
        'Cancelled': 0
      };
      
      const currentStep = statusProgress[status] || 0;
      
      // Update step circles
      document.querySelectorAll('.step-item').forEach((step, index) => {
        const stepNum = index + 1;
        const circle = step.querySelector('.step-circle');
        
        if (stepNum <= currentStep) {
          circle.classList.add('bg-primary', 'text-white', 'border-primary');
          circle.classList.remove('bg-white', 'text-slate-400', 'border-slate-300');
        } else {
          circle.classList.add('bg-white', 'text-slate-400', 'border-slate-300');
          circle.classList.remove('bg-primary', 'text-white', 'border-primary');
        }
      });
      
      // Update progress bar
      const progressBar = document.getElementById('progress-bar-horizontal');
      if (progressBar) {
        const progressWidth = (currentStep / 3) * 100;
        progressBar.style.width = `${progressWidth}%`;
      }
    }

    // User-specific data fetching function
    async function fetchUserBookings(page = 1) {
      console.log('🔍 Profile fetchUserBookings called with page:', page);
      
      try {
        // Get current user ID from validated session
        const userId = window.currentUserId || <?php echo $user['id'] ?? 0; ?>;
        
        if (!userId) {
          console.error('User ID not found');
          return;
        }

        // Fetch all pages to get complete booking list
        let allBookings = [];
        let currentPage = 1;
        let hasMorePages = true;

        while (hasMorePages) {
          const params = new URLSearchParams({
            page: currentPage,
            user_id: userId, // Filter by specific user
            order: 'desc'
          });

          const apiURL = `/landscape/USER_API/BookingsController.php?action=list_by_user&${params.toString()}`;
          const response = await fetch(apiURL);
          
          if (!response.ok) {
            console.error('Failed to fetch bookings:', response.statusText);
            break;
          }

          const data = await response.json();
          
          if (data.status === 'success' && data.bookings && data.bookings.length > 0) {
            allBookings = allBookings.concat(data.bookings);
            currentPage++;
            
            // If we got less than 6 bookings, we're on the last page
            if (data.bookings.length < 6) {
              hasMorePages = false;
            }
          } else {
            hasMorePages = false;
          }
        }

        // Store all bookings globally
        window.userBookings = allBookings;
        
        console.log(`Found ${allBookings.length} total bookings for user ${userId}`);
        
        // Display bookings in the UI
        displayUserBookings(allBookings);
        
        // Also fetch unavailable dates
        await fetchUnavailableDates();
        
        // Update calendar to reflect new booking data
        if (typeof updateCalendar === 'function') {
          updateCalendar();
        }
        
        return { bookings: allBookings };
      } catch (error) {
        console.error('Fetch user bookings error:', error);
        return { bookings: [] };
      }
    }

    // Function to fetch unavailable dates
    //UID 78
    async function fetchUnavailableDates() {
      try {
        const response = await fetch('/landscape/USER_API/AppointmentController.php?action=get_unavailable_dates');
        const result = await response.json();
        
        if (result.status === 'success') {
          window.unavailableDates = result.unavailable_dates.map(d => d.unavailable_date) || [];
          console.log('Fetched unavailable dates:', window.unavailableDates);
        } else {
          window.unavailableDates = [];
          console.error('Failed to fetch unavailable dates:', result.message);
        }
      } catch (error) {
        console.error('Error fetching unavailable dates:', error);
        window.unavailableDates = [];
      }
    }

    // Function to display user bookings in the UI
    function displayUserBookings(bookings) {
      const loadingEl = document.getElementById('bookings-loading');
      const emptyEl = document.getElementById('bookings-empty');
      const listEl = document.getElementById('bookings-list');
      
      // Hide loading state
      if (loadingEl) loadingEl.style.display = 'none';
      
      if (!bookings || bookings.length === 0) {
        // Show empty state
        if (emptyEl) emptyEl.style.display = 'block';
        if (listEl) listEl.style.display = 'none';
        return;
      }
      
      // Show bookings list
      if (emptyEl) emptyEl.style.display = 'none';
      if (listEl) listEl.style.display = 'block';
      
      //filter the booking removing the Consultation from it
      filteredbookings = bookings.filter(b => b.status !== "Consultation");

      // Generate booking cards
      listEl.innerHTML = filteredbookings.map(booking => {
        const statusColors = {
          'Consultation': '#64748b',
          'Pending': '#f59e0b',
          'Active': '#10b981',
          'Completed': '#059669',
          'Cancelled': '#ef4444'
        };
        
        const statusColor = statusColors[booking.status] || '#64748b';
        const paymentProgress = booking.payment_progress_percent || 0;
        const amountPaid = booking.amount_paid || 0;
        const totalCost = booking.total_cost || 0;
        const amountToBePaid = booking.amount_to_be_paid || 0;
        //UID 212
        return `
          <div class="profile-card" style="margin-bottom: 1.5rem; padding: 0; overflow: hidden; border: 1px solid #e2e8f0;">
            <!-- Card Header -->
            <div style="background: #f8fafc; padding: 1.25rem 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
              <h4 style="margin: 0; color: var(--primary-green);">${booking.service_name || 'Service'}</h4>
              <span style="font-size: 0.85rem; color: var(--text-gray);">
                <i class="far fa-calendar-alt"></i> ${new Date(booking.appointment_date).toLocaleDateString()}
                <span style="background-color: ${booking.notifBoolean ? 'green; color: white' : 'yellow; color: black;'}; padding: 4px; border-radius: 10px;">${booking.notifBoolean ? 'Read' : 'Unread'}</span>
              </span>
            </div>
            
            <!-- Card Body -->
            <div style="padding: 1.5rem;">
              <!-- Booking Info -->
              <div style="margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                  <span style="background-color: #e2e8f0; padding: 0.25rem 0.5rem; border-radius: 10px; font-size: 0.8rem; font-weight: 600;">
                    ${booking.booking_code}
                  </span>
                  <span style="background-color: ${statusColor}; color: white; padding: 0.25rem 0.5rem; border-radius: 10px; font-size: 0.8rem; font-weight: 600;">
                    ${booking.status}
                  </span>
                </div>
                <p style="color: var(--text-dark); margin: 0.5rem 0;">
                  <i class="fas fa-map-marker-alt"></i> ${booking.address || 'No address provided'}
                </p>
                ${booking.notes ? `<p style="color: var(--text-gray); font-size: 0.9rem; margin: 0.5rem 0;"><i class="fas fa-sticky-note"></i> ${booking.notes}</p>` : ''}
              </div>
              
              <!-- Financial Summary -->
              <div style="background: #f8fafc; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                <h5 style="margin: 0 0 0.75rem 0; color: var(--text-dark); font-size: 0.9rem;">Financial Summary</h5>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 0.75rem; font-size: 0.85rem;">
                  <div>
                    <span style="color: var(--text-gray);">Total Cost:</span>
                    <strong style="color: var(--text-dark); display: block;">₱${totalCost.toFixed(2)}</strong>
                  </div>
                  <div>
                    <span style="color: var(--text-gray);">Paid:</span>
                    <strong style="color: var(--primary-green); display: block;">₱${amountPaid.toFixed(2)}</strong>
                  </div>
                  <div>
                    <span style="color: var(--text-gray);">Remaining:</span>
                    <strong style="color: ${amountToBePaid > 0 ? '#f59e0b' : '#10b981'}; display: block;">₱${amountToBePaid.toFixed(2)}</strong>
                  </div>
                </div>
                ${paymentProgress > 0 ? `
                  <div style="margin-top: 0.75rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem; font-size: 0.8rem;">
                      <span>Payment Progress</span>
                      <span>${paymentProgress.toFixed(1)}%</span>
                    </div>
                    <div style="background: #e2e8f0; height: 6px; border-radius: 3px; overflow: hidden;">
                      <div style="background: linear-gradient(90deg, var(--primary-green), #059669); height: 100%; width: ${paymentProgress}%; transition: width 0.3s ease;"></div>
                    </div>
                  </div>
                ` : ''}
              </div>
              
              <!-- Action Buttons -->
              <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button onclick="viewBookingDetails(${booking.id})" data-id="${booking.id}" class="more-details" style="font-size: 0.85rem; background-color: var(--primary-green); color: white; padding: 0.5rem 1rem; border-radius: 8px; border: none; cursor: pointer;">
                  <i class="fas fa-eye"></i> View Details
                </button>
                ${booking.status === 'Consultation' || booking.status === 'Pending' || booking.status === 'Active' ? `
                  <button onclick="cancelBooking(${booking.id})" class="more-details" data-id="${booking.id}" style="font-size: 0.85rem; background-color: #ef4444; color: white; padding: 0.5rem 1rem; border-radius: 8px; border: none; cursor: pointer;">
                    <i class="fas fa-times"></i> Cancel
                  </button>
                ` : ''}
              </div>
            </div>
          </div>
        `;
      }).join('');
    }

    // Function to update action button state
    function updateActionButton(action = null) {
      const actionBtn = document.getElementById('actionBtn');
      // const actionBtnText = document.getElementById('actionBtnText');
      
      if (!actionBtn) return;
      
      const hasCosts = (parseFloat(window.processedData.labor_cost) || 0) > 0 || 
                      (parseFloat(window.processedData.materials_cost) || 0) > 0 || 
                      (parseFloat(window.processedData.misc_cost) || 0) > 0;
      
      if (action === 'submit') {
        // User has made changes, show submit button
        actionBtn.disabled = false;
        actionBtn.textContent = 'Submit Changes';
        actionBtn.className = 'flex-1 px-6 py-3 bg-blue-600 text-white font-medium rounded-xl hover:bg-blue-700 hover:shadow-lg hover:shadow-blue-200 transition-all duration-200 flex items-center justify-center gap-2 group';
      } else if (hasCosts) {
        // Costs exist, show approve button
        actionBtn.disabled = false;
        actionBtn.textContent = 'Continue';
        actionBtn.className = 'flex-1 px-6 py-3 bg-indigo-600 text-white font-medium rounded-xl hover:bg-indigo-700 hover:shadow-lg hover:shadow-indigo-200 transition-all duration-200 flex items-center justify-center gap-2 group';
        
      }
      else {
        // No costs, disable approve button
        actionBtn.disabled = true;
        actionBtn.textContent = 'Approve (Costs Pending)';
        actionBtn.className = 'flex-1 px-6 py-3 bg-gray-400 text-white font-medium rounded-xl cursor-not-allowed flex items-center justify-center gap-2 group';
      }
    }
    
    // Function to handle action button click
    function handleActionButton() {
      const btn = document.getElementById('actionBtn').textContent;
      
      if (btn === 'Submit Changes') {
        saveProfileChanges();
      } else if (btn === 'Continue') {
        approveProfile();
      } else if (btn === 'Approve') {
        approveProfileFinal();
      }
    }
    
    // Function to cancel booking
    function cancelBooking() {
      const bookingId = window.currentBookingId || window.processedData.id;
      console.log("bookings ID: ", bookingId)

      const bookingStatus = window.processedData?.status;
      
      if (!bookingId) {
        alert('No booking ID found for cancellation');
        return;
      }
      
      // Check if booking can be cancelled
      if (bookingStatus === 'Completed' || bookingStatus === 'Cancelled') {
        alert(`Cannot cancel booking with status: ${bookingStatus}`);
        return;
      }
      
      if (!confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) {
        return;
      }
      
      fetch('/landscape/USER_API/BookingsController.php?action=cancel', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          booking_id: bookingId
        })
      })
      .then(response => response.json())
      .then(result => {
        if (result.status === 'success') {
          alert('Booking cancelled successfully!');
          closeModal();
          location.reload();
        } else {
          alert('Error: ' + (result.message || 'Failed to cancel booking'));
        }
      })
      .catch(error => {
        console.error('Error cancelling booking:', error);
        alert('Error cancelling booking. Please try again.');
      });
    }
    
    // Initialize calendar and bookings when DOM is loaded
    document.addEventListener('DOMContentLoaded', () => {
      init();
    });

    // Appointment Request Functions
    // function showAppointmentModal(date = '') {
    //   const modal = document.getElementById('appointmentModal');
    //   const dateInput = document.getElementById('appointmentDate');
      
    //   if (date) {
    //     dateInput.value = date;
    //   }
      
    //   modal.style.display = 'flex';
    // }

    function closeAppointmentModal() {
      const modal = document.getElementById('appointmentModal');
      modal.style.display = 'none';
      document.getElementById('appointmentForm').reset();
    }

    // Handle appointment form submission
    document.getElementById('appointmentForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      
      const date = document.getElementById('appointmentDate').value;
      const time = document.getElementById('appointmentTime').value;
      const service = document.getElementById('appointmentService').value;
      const notes = document.getElementById('appointmentNotes').value;
      
      if (!time) {
        alert('Please select a preferred time.');
        return;
      }
      
      if (!service) {
        alert('Please select a service type.');
        return;
      }
      
      // Show loading state
      const submitBtn = e.target.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerText;
      submitBtn.disabled = true;
      submitBtn.innerText = 'Requesting...';
      
      try {
        // Check if date is available first
        //UID 00
        const availabilityResponse = await fetch(`/landscape/USER_API/AppointmentController.php?action=check_availability&date=${date}`);
        const availabilityResult = await availabilityResponse.json();
        
        if (!availabilityResult.available) {
          throw new Error('This date is not available for appointments. Please select another date.');
        }
        
        // Submit appointment request
        const response = await fetch('/landscape/USER_API/AppointmentController.php?action=request_appointment', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            appointment_date: `${date} ${time}:00`,
            service_type: service,
            notes: notes || null,
            user_id: <?php echo $user['id'] ?? 0; ?> // Validated user ID from auth system
          })
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
          // Show success message
          alert(`Appointment requested for ${date} at ${time} for ${service}. We'll contact you soon to confirm.`);
          
          // Refresh calendar
          if (typeof fetchUserBookings === 'function') {
            fetchUserBookings().then(() => {
              if (typeof updateCalendar === 'function') updateCalendar();
            });
          }
          
          closeAppointmentModal();
        } else {
          throw new Error(result.message || 'Failed to request appointment');
        }
      } catch (error) {
        console.error('Error requesting appointment:', error);
        alert('Failed to request appointment: ' + error.message);
      } finally {
        // Restore button state
        submitBtn.disabled = false;
        submitBtn.innerText = originalText;
      }
    });

    // Update calendar click handlers for appointment requests
    document.addEventListener('DOMContentLoaded', () => {
      // Initialize calendar after DOM is ready
      setTimeout(() => {
        if (typeof init === 'function') {
          init();
        }
      }, 100);
      
      // Override existing calendar click handlers to show appointment modal
      const calendarGrid = document.getElementById('calendarGrid');
      if (calendarGrid) {
        calendarGrid.addEventListener('click', (e) => {
          const dayElement = e.target.closest('.calendar-day');
          if (dayElement && !dayElement.classList.contains('empty') && !dayElement.classList.contains('has-booking') && !dayElement.classList.contains('unavailable')) {
            const dayNumber = dayElement.querySelector('.day-number')?.innerText || dayElement.innerText;
            const monthSelect = document.getElementById('monthSelect');
            const yearSelect = document.getElementById('yearSelect');
            const month = parseInt(monthSelect.value);
            const year = parseInt(yearSelect.value);
            const date = new Date(year, month, parseInt(dayNumber)).toISOString().split('T')[0];
            
            // Show appointment modal
            //showAppointmentModal(date);
          }
        });
      }
    });
  </script>
</body>
</html>