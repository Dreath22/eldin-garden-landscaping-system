<?php
// require_once __DIR__ . '/config/auth_middleware.php';

// // Require admin access - will redirect if not admin
// requireAdmin();

// // Initialize standard session
// $sessionData = initStandardSession();
// $user = $sessionData['user'];
// $isLoggedIn = $sessionData['isLoggedIn'];

// $adminName = $_SESSION['user_name'];

// // Get current admin ID (fallback to 0 for testing when not authenticated)
// $adminId = $_SESSION['user_id'] ?? 0;
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Booking Management - EldinGarden Admin</title>
    <link rel="stylesheet" href="admin-style.css" />
    <link rel="stylesheet" 
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" 
      integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" 
      crossorigin="anonymous" 
      referrerpolicy="no-referrer" />
      <script src="/landscape/assets/tailwind.js"></script>
    <script type="module" src="./JS/admin_booking.js"></script>
    <script type="module" src="./JS/invoice.js"></script>
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
  </head>
  <body>
    <div class="admin-page">
      <!-- Sidebar -->
      <aside class="admin-sidebar">
        <div class="admin-sidebar-header">
          <a href="index.html" class="logo">
            <div class="logo-icon">
              <img src="assets/img/LOGO.png" alt="EldinGarden Logo" style="height: 24px; width: auto; vertical-align: middle;">
            </div>
            EldinGarden
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
            <a href="admin-bookings.php" class="admin-nav-item active">
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
          <p class="admin-nav-title">Settings</p>
          <a href="logout.php" class="admin-nav-item">
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
            <input
              type="text"
              placeholder="Search bookings by ID or customer..."
            />
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
              <h2>Booking Management</h2>
              <p>Manage all service bookings and appointments.</p>
            </div>
            <button class="btn btn-primary" onclick="showAddBookingModal()">
              <i class="fas fa-plus"></i> New Booking
            </button>
          </div>

          <!-- Stats Cards -->
          <div class="dashboard-stats">
            <div class="stat-card">
              <div class="stat-card-header">
                <div>
                  <h3 id="activeBooking"></h3>
                  <p>Active Bookings</p>
                </div>
                <div class="stat-card-icon blue">
                  <i class="fas fa-calendar-check"></i>
                </div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-card-header">
                <div>
                  <h3 id="pendingBooking"></h3>
                  <p>Pending</p>
                </div>
                <div class="stat-card-icon orange">
                  <i class="fas fa-clock"></i>
                </div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-card-header">
                <div>
                  <h3 id="completedBooking"></h3>
                  <p>Completed Today</p>
                </div>
                <div class="stat-card-icon green">
                  <i class="fas fa-check-circle"></i>
                </div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-card-header">
                <div>
                  <h3 id="cancelledBooking"></h3>
                  <p>Cancelled</p>
                </div>
                <div class="stat-card-icon red">
                  <i class="fas fa-times-circle"></i>
                </div>
              </div>
            </div>
          </div>

          <!-- Calendar Section -->
          <div style="margin: 2rem 0;">
            <div class="admin-page-title" style="margin-bottom: 1.5rem;">
              <div>
                <h3>Booking Calendar</h3>
                <p>Visual overview of all scheduled appointments.</p>
              </div>
            </div>
            <div class="calendar-container" style="max-width: 500px; margin: 0 auto;">
              <div class="calendar-header">
                <h3 id="adminMonthDisplay">Month Year</h3>
                <div class="calendar-controls">
                  <select id="adminMonthSelect" class="select-input" onchange="updateAdminCalendar()"></select>
                  <select id="adminYearSelect" class="select-input" onchange="updateAdminCalendar()"></select>
                </div>
              </div>
              <div class="calendar-grid" id="adminCalendarGrid">
                <!-- JavaScript will populate this -->
              </div>
            </div>
          </div>

          
          <!-- Tabs -->
          <div class="tabs" id="userTabsContainer">
            <div class="tab active" data-tab="all">All Bookings</div>
            <div class="tab" data-tab="consultation">Consultations</div>
            <div class="tab" data-tab="pending">Pending</div>
            <div class="tab" data-tab="active">Active</div>
            <div class="tab" data-tab="completed">Completed</div>
            <div class="tab" data-tab="cancelled">Cancelled</div>
          </div>

          <!-- Filters -->
          <div class="filters-bar">
            <div class="filter-group">
              <label>Service:</label>
              <select id="serviceFilter">
              </select>
            </div>
            <div class="filter-group">
              <label>Date:</label>
              <input type="date" id="dateFilter"/>
            </div>
            <div class="filter-group">
              <label>Sort:</label>
              <select id="sortFilter">
                <option value="newest">Newest First</option>
                <option value="oldest">Oldest First</option>
                <option value="updated_newest">Updated Newest</option>
                <option value="updated_oldest">Updated Oldest</option>
              </select>
            </div>
          </div>

          <!-- Booking Cards Grid -->
          <div class="booking-grid" id="bookingContainer">
          </div>
        </div>

        <!-- Pagination -->
        <div
          id="pagination"
          style="display: flex; justify-content: center; margin-bottom: 3rem"
        ></div>
      </main>
    </div>
    <style>
        /* * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        } */

        /* body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        } */

        .trigger-btn-x {
            background: #fff;
            color: #667eea;
            border: none;
            padding: 14px 32px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }

        .trigger-btn-x:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }

        /* Modal Overlay */
        .modal-overlay-x {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: fadeIn-x 0.3s ease;
        }

        .modal-overlay-x.active {
            display: flex;
        }

        @keyframes fadeIn-x {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Modal Container */
        .modal-x {
            background: #fff;
            border-radius: 16px;
            width: 100%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideUp 0.4s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Modal Header */
        .modal-header-x {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 24px 30px;
            border-radius: 16px 16px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header-x h2 {
            font-size: 22px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-badge-x {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .close-btn-x {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .close-btn-x:hover {
            background: rgba(255,255,255,0.3);
        }

        /* Modal Body */
        .modal-body-x {
            padding: 30px;
        }

        /* Section Divider */
        .section-x {
            margin-bottom: 28px;
        }

        .section-title-x {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #667eea;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e8eaf6;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Form Grid */
        .form-grid-x {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        @media (max-width: 600px) {
            .form-grid-x {
                grid-template-columns: 1fr;
            }
        }

        .form-group-x {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group-x.full-width {
            grid-column: 1 / -1;
        }

        .label-x {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
        }

        .readonly-field {
            padding: 12px 14px;
            background: #f3f4f6;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            color: #6b7280;
            min-height: 44px;
            display: flex;
            align-items: center;
        }

        .readonly-field.message {
            min-height: 80px;
            align-items: flex-start;
            padding-top: 12px;
            white-space: pre-wrap;
        }

        input[type="number"] {
            padding: 12px 14px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.2s;
            background: #fafafa;
            width: 100%;
        }

        input[type="number"]:focus {
            outline: none;
            border-color: #667eea;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Cost Inputs */
        .cost-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        @media (max-width: 600px) {
            .cost-grid {
                grid-template-columns: 1fr;
            }
        }

        .cost-input-wrapper {
            position: relative;
        }

        .cost-input-wrapper::before {
            content: '₱';
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            font-weight: 600;
            pointer-events: none;
            z-index: 1;
        }

        .cost-input-wrapper input {
            padding-left: 28px;
        }

        .cost-total {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 18px 24px;
            border-radius: 10px;
            margin-top: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cost-total span {
            font-size: 14px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .cost-total strong {
            font-size: 28px;
            font-weight: 700;
        }

        /* Footer */
        .modal-x-footer {
            padding: 20px 30px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            background: #fafafa;
            border-radius: 0 0 16px 16px;
        }

        .btn-x {
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }

        .btn-secondary-x {
            background: #e5e7eb;
            color: #374151;
        }

        .btn-secondary-x:hover {
            background: #d1d5db;
        }

        .btn-primary-x {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary-x:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        /* Info row for read-only data */
        .info-row-x {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .info-row-x:last-child {
            border-bottom: none;
        }

        .info-label-x
         {
            font-size: 13px;
            color: #6b7280;
            font-weight: 500;
        }

        .info-value-x {
            font-size: 14px;
            color: #111827;
            font-weight: 600;
            text-align: right;
        }
    </style>
    
    <div class="modal-overlay-x" id="modalOverlay" onclick="closeModalOnOverlay(event)">
        <div class="modal-x" onclick="event.stopPropagation()">
            <div class="modal-header-x">
                <h2>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 8px;">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    Service Request Details
                </h2>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span class="admin-badge-x">Admin Mode</span>
                    <button class="close-btn-x" onclick="closeModal()">&times;</button>
                </div>
            </div>

            <div class="modal-body-x">
                <!-- Customer Information Section (Read-Only) -->
                <div class="modal-body-x">
                    <div class="section-x-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Customer Information
                    </div>
                    <div class="form-grid-x">
                        <div class="form-group-x">
                            <label class="label-x" >Full Name</label>
                            <div class="readonly-field" id="fullNamex">John Doe</div>
                        </div>
                        <div class="form-group-x">
                             <label class="label-x">Email</label>
                            <div class="readonly-field" id="emailx">john.doe@example.com</div>
                        </div>
                        <div class="form-group-x full-width">
                             <label class="label-x">Address</label>
                            <div class="readonly-field" id="address">123 Maple Street, Springfield, IL 62701</div>
                        </div>
                        <div class="form-group-x">
                             <label class="label-x">Service Name</label>
                            <div class="readonly-field" id="serviceName">Plumbing Repair</div>
                        </div>
                        <div class="form-group-x">
                             <label class="label-x">Area</label>
                            <div class="readonly-field" id="area">1,500 sq ft</div>
                        </div>
                        <div class="form-group-x">
                             <label class="label-x">Approximate Estimate</label>
                            <div class="readonly-field" id="approxEstimate">₱2,500.00</div>
                        </div>
                        <div class="form-group-x full-width">
                             <label class="label-x">Message / Details</label>
                            <div class="readonly-field message" id="message">Need plumbing repair for the kitchen sink and bathroom faucet. Water pressure has been low for the past week. Also need to check the water heater connection.</div>
                        </div>
                    </div>
                </div>

                <!-- Admin Section (Editable Costs) -->
                <div class="section-x">
                    <div class="section-title-x">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        Cost Breakdown
                    </div>
                    <div class="cost-grid">
                        <div class="form-group-x">
                             <label class="label-x">Labor Cost</label>
                            <div class="cost-input-wrapper">
                                <input type="number" id="laborCost" placeholder="0.00" min="0" step="0.01" value="800" oninput="calculateTotal()">
                            </div>
                        </div>
                        <div class="form-group-x">
                             <label class="label-x">Materials Cost</label>
                            <div class="cost-input-wrapper">
                                <input type="number" id="materialsCost" placeholder="0.00" min="0" step="0.01" value="450" oninput="calculateTotal()">
                            </div>
                        </div>
                        <div class="form-group-x">
                             <label class="label-x">Miscellaneous</label>
                            <div class="cost-input-wrapper">
                                <input type="number" id="miscCost" placeholder="0.00" min="0" step="0.01" value="120" oninput="calculateTotal()">
                            </div>
                        </div>
                    </div>
                    <div class="cost-total">
                        <span>Total Cost</span>
                        <strong id="totalCost">₱1,370.00</strong>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn-x btn-secondary-x " onclick="closeModal()">Close</button>
                <button class="btn-x btn-primary-x" onclick="saveChanges()">Save Changes</button>
            </div>
        </div>
    </div>
    <!-- Add Booking Modal -->
    <div id="addBookingModal" class="modal-overlay" style="display: none">
      <div class="modal modal-large">
        <div class="modal-header">
          <h3>
            <i
              class="fas fa-calendar-plus"
              style="color: var(--primary-green)"
            ></i>
            New Booking
          </h3>
          <button class="modal-close" onclick="closeAddBookingModal()">
            &times;
          </button>
        </div>
        <div class="modal-body">
          <form id="addBookingForm">
            <div class="form-group">
              <div class="form-group">
                <label for="customerSearch">Select Customer</label>
                <input
                  list="customerList"
                  id="customerSearch"
                  name="customer"
                  placeholder="Type to search customer..."
                  class="form-control"
                /><input type="hidden" id="selectedCustomerId">
                <datalist id="customerList">
                </datalist>
              </div>
            </div>
            <div class="form-group">
              <label for="service">Select Service</label>
              <input id="serviceSearch" list="serviceList" name="service" placeholder="Type to search services..." class="form-control"><input type="hidden" id="selectedServiceId">
              <datalist id="serviceList">
                </datalist>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="bookingDate">Date</label>
                <input type="date" id="bookingDate" />
              </div>
              <div class="form-group">
                <label for="bookingTime">Time</label>
                <input type="time" id="bookingTime" />
              </div>
            </div>
            <div class="form-group">
              <label for="cost">Cost</label>
              <input
                type="number"
                id="cost"
                placeholder="₱1000.00"
                step="0.01"
              />
              <style>
                /* Chrome, Safari, Edge, Opera */
                input#cost::-webkit-outer-spin-button,
                input#cost::-webkit-inner-spin-button {
                  -webkit-appearance: none;
                  margin: 0;
                }
              </style>
            </div>
            <div class="form-group">
              <label for="address">Service Address</label>
              <input
                type="text"
                id="address"
                placeholder="Enter full address"
              />
            </div>
            <div class="form-group">
              <label for="notes">Special Instructions</label>
              <textarea
                id="notes"
                rows="2"
                placeholder="Any special requirements..."
              ></textarea>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button
            class="btn btn-small"
            style="background-color: #f1f5f9; color: var(--text-dark)"
            onclick="closeAddBookingModal()"
          >
            Cancel
          </button>
          <button
            class="btn btn-primary btn-small"
            onclick="confirmAddBooking()"
          >
            Create Booking
          </button>
        </div>
      </div>
    </div>

    <!-- Confirm Modal -->
    <div id="confirmModal" style="display: none;" class="modal-overlay px-4">
      
    </div>

    <!-- Complete/Finalize Modal  -->
    <div id="completeModal" style="display: none;" class="modal-overlay">
    </div>

    <!-- Cancellation Modal  -->
    <div id="cancelModal" style="display: none;" class="modal-overlay">
        
    </div>
    <!-- Invoice Modal  -->
    <div id="invoiceModal" style="display: none;" class="modal-overlay">
        
    </div>
    <!-- View Modal -->
    <div id="viewModal" style="display: none;" class="modal-overlay">
    </div>
<div id="has-bookings-details" class="modal-overlay" style="display: none">

</div>
<style data-purpose="custom-transitions">
    .stepper-transition {
      transition: all 0.3s ease-in-out;
    }
    .material-symbols-outlined {
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }

    /* Calendar Styles */
    .calendar-container {
      background: white;
      border-radius: 15px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
      padding: 1rem;
      width: 100%;
    }

    .calendar-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
    }

    .calendar-controls {
      display: flex;
      gap: 0.5rem;
      align-items: center;
    }

    .calendar-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 6px;
    }

    .day-name {
      text-align: center;
      font-weight: bold;
      color: var(--primary);
      padding-bottom: 6px;
      font-size: 0.75rem;
    }

    .calendar-day {
      aspect-ratio: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 6px;
      background: #f8fafc;
      cursor: pointer;
      transition: all 0.2s;
      font-weight: 500;
      font-size: 0.8rem;
    }

    .calendar-day:hover {
      background: #e2e8f0;
    }

    .calendar-day.today {
      border: 2px solid var(--primary);
      color: var(--primary);
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
      border-radius: 8px;
      position: relative;
      cursor: pointer;
      box-shadow: 0 2px 8px rgba(34, 197, 94, 0.1);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 1px;
    }

    .calendar-day.has-booking:hover {
      background: linear-gradient(145deg, #16a34a, #15803d);
      color: white;
      transform: translateY(-1px);
      box-shadow: 0 4px 15px rgba(34, 197, 94, 0.25);
    }

    .day-number {
      font-weight: 600;
      font-size: 0.75rem;
      line-height: 1;
      color: #1f2937;
    }

    .calendar-day.has-booking:hover .day-number {
      color: white;
    }

    .booking-indicator {
      font-size: 0.45rem;
      background: linear-gradient(135deg, #16a34a, #15803d);
      color: white;
      padding: 1px 3px;
      border-radius: 4px;
      text-align: center;
      font-weight: 600;
      line-height: 1;
      letter-spacing: 0.1px;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .calendar-day.has-booking:hover .booking-indicator {
      background: rgba(255, 255, 255, 0.95);
      color: #16a34a;
    }

    .calendar-day:not(.has-booking):hover {
      background: #f8fafc;
      transform: translateY(-1px);
    }

    .calendar-day.unavailable {
      background: linear-gradient(145deg, #dc2626, #b91c1c);
      border: 2px solid #dc2626;
      color: white;
      cursor: not-allowed;
      position: relative;
      animation: subtle-pulse 2s infinite;
    }
    
    @keyframes subtle-pulse {
      0%, 100% {
        opacity: 1;
      }
      50% {
        opacity: 0.8;
      }
    }

    .calendar-day.unavailable:hover {
      background: linear-gradient(145deg, #b91c1c, #991b1b);
      transform: scale(1.05);
      box-shadow: 0 4px 8px rgba(220, 38, 38, 0.4);
    }

    .unavailability-indicator {
      font-size: 0.5rem;
      background: #7f1d1d;
      color: white;
      padding: 3px 6px;
      border-radius: 4px;
      font-weight: bold;
      line-height: 1;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
    }

    .calendar-day.unavailable:hover .unavailability-indicator {
      background: #5a0e0e;
      transform: scale(1.1);
    }
  </style>
    <script>
    // Make calculateTotal globally available for inline handlers
    function calculateTotal() {
      try {
        const laborCost = parseFloat(document.getElementById('laborCost').value) || 0;
        const materialsCost = parseFloat(document.getElementById('materialsCost').value) || 0;
        const miscCost = parseFloat(document.getElementById('miscCost').value) || 0;
        
        const totalCost = laborCost + materialsCost + miscCost;
        
        // Update total cost display
        const totalElement = document.getElementById('totalCost');
        if (totalElement) {
          totalElement.textContent = `₱${totalCost.toFixed(2)}`;
        }
        
        return totalCost;
      } catch (error) {
        console.error('Error calculating total:', error);
        return 0;
      }
    }
    </script>
    <script>
    function closeModalOnOverlay(event) {
        if (event.target === document.getElementById('modalOverlay')) {
            closeModal();
        }
    }
    </script>
    <script data-purpose="dynamic-stepper-logic">
  /**
   * Updates the visual state of the multi-stage stepper.
   * @param {number} activeStage - The current stage index (1-based).
   */
  function updateProgress(activeStage) {
    const steps = document.querySelectorAll('.step-item');
    const progressBar = document.getElementById('progress-bar-horizontal');

    if (!steps.length) return;

    // Configuration for UI states to keep code DRY (Don't Repeat Yourself)
    const activeClasses = ['bg-primary', 'border-primary', 'text-white'];
    const inactiveClasses = ['bg-slate-100', 'dark:bg-slate-700', 'border-slate-300', 'dark:border-slate-600', 'text-slate-400'];

    steps.forEach((step, index) => {
      const stageNum = index + 1;
      const circle = step.querySelector('.step-circle');
      const label = step.querySelector('.step-label');
      
      const isCompletedOrActive = stageNum <= activeStage;

      if (circle) {
        if (isCompletedOrActive) {
          // Apply active styling using spread operator for efficiency
          circle.classList.remove(...inactiveClasses);
          circle.classList.add(...activeClasses);
        } else {
          // Revert to pending styling
          circle.classList.remove(...activeClasses);
          circle.classList.add(...inactiveClasses);
        }
      }

      if (label) {
        // Toggle text color based on state
        label.classList.toggle('text-primary', isCompletedOrActive);
        label.classList.toggle('text-slate-400', !isCompletedOrActive);
      }
    });

    // Update progress bar width with boundary protection
    if (progressBar) {
      // Clamp the value between 1 and the total number of steps
      const safeStage = Math.min(Math.max(activeStage, 1), steps.length);
      const percentage = ((safeStage - 1) / (steps.length - 1)) * 100;
      
      // Use requestAnimationFrame for a smoother visual transition if needed
      progressBar.style.width = `${percentage}%`;
    }
  }

  // Initialize once the DOM is fully loaded
  document.addEventListener('DOMContentLoaded', () => {
    // Example: Initialize at Stage 3
    updateProgress(3);
  });
</script>
  

  <script data-purpose="dynamic-stepper-logic">

    
    // Use a state object to keep things organized
const CalendarState = {
  months: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
  now: new Date(),
  bookingsMap: {}, // To be populated: { "2024-05-20": [booking1, booking2] }
  unavailableMapArray: [], // To be populated: [{ unavailable_date: "2024-05-21", reason: "Maintenance" }, ...]
  unavailableMap: new Set() // To be populated: Set(["2024-05-21", "2024-05-22"])
};
// Data found: {unavailable_date: '2026-05-05', reason: 'hello', created_at: '2026-05-03 22:49:49', created_by: 2, updated_at: '2026-05-06 00:47:47', …}
function showAvailabilityModalx(date = '', data = null) {
  console.log("Hello: ", data)
  const modal = document.getElementById('availabilityModal');
  
  // 1. Handle the Reason: Default to empty string if data is null or reason is null
  const reason = data && data.reason ? data.reason : "";
  document.getElementById('availabilityReason').value = reason;
  
  // 2. Handle the Status: If data exists, it's 'unavailable'. If null, it's 'available'
  document.getElementById('availabilityStatus').value = data ? 'unavailable' : 'available';

  // 3. Set the Date
  if (date) {
    document.getElementById('availabilityDate').value = date;
  }

  modal.style.display = 'flex';
}
async function initAdminCalendar() {
  // Populate Selects
  CalendarState.months.forEach((m, i) => {
    adminMonthSelect.add(new Option(m, i, i === CalendarState.now.getMonth(), i === CalendarState.now.getMonth()));
  });

  const currentYear = CalendarState.now.getFullYear();
  for (let y = 2020; y <= 2030; y++) {
    adminYearSelect.add(new Option(y, y, y === currentYear, y === currentYear));
  }

  await fetchUnavailableDates(); // Assuming this populates window.unavailableDates
  
  // Fetch all bookings for calendar (not just the paginated ones)
  if (typeof window.fetchAllBookingsForCalendar === 'function') {
    console.log('Fetching all bookings for calendar...');
    await window.fetchAllBookingsForCalendar();
  } else {
    console.log('fetchAllBookingsForCalendar not available, using fallback...');
    // Fallback: wait a bit and try again, then use original method
    await new Promise(resolve => setTimeout(resolve, 500));
    if (typeof window.fetchAllBookingsForCalendar === 'function') {
      console.log('fetchAllBookingsForCalendar now available, calling...');
      await window.fetchAllBookingsForCalendar();
    } else {
      console.log('Still not available, using direct API call');
      // Direct API call as backup
      try {
        const response = await fetch('/landscape/USER_API/BookingsController.php?action=list&status=all&order=oldest&limit=100');
        const data = await response.json();
        console.log('Direct API call fetched:', data.bookings.length, 'bookings');
        window.allBookings = data.bookings;
      } catch (error) {
        console.error('Direct API call failed:', error);
        await waitForBookingsData();
      }
    }
  }

  CalendarState.unavailableMapArray = window.unavailableDates || [];

  // Index unavailable dates
  CalendarState.unavailableMap = new Set(
    (window.unavailableDates || []).map(item => item.unavailable_date)
  );

  // Index bookings: Group them by date string
  CalendarState.bookingsMap = (window.allBookings || []).reduce((acc, booking) => {
    if (booking.appointment_date) {
      // Extract date part directly from the string to avoid timezone issues
      // Format from API: "2026-02-20 10:00"
      const dateKey = booking.appointment_date.split(' ')[0];
      if (!acc[dateKey]) acc[dateKey] = [];
      acc[dateKey].push(booking);
    }
    return acc;
  }, {});
  
  // Debug: Log the bookings map to verify dates are being processed
  console.log('CalendarState.bookingsMap:', CalendarState.bookingsMap);
  console.log('Current month dates with bookings:', Object.keys(CalendarState.bookingsMap).filter(date => 
    date.startsWith('2026-05') // Filter for current month (May 2026)
  ));
  
  // Debug: Show all booking appointment dates
  console.log('All booking appointment dates:', (window.allBookings || []).map(b => ({
    id: b.id,
    appointment_date: b.appointment_date,
    extracted_date: b.appointment_date ? b.appointment_date.split(' ')[0] : 'N/A'
  })));
  
  updateAdminCalendar();
}

/**
 * Wait for booking data to be loaded by admin_booking.js
 */
function waitForBookingsData() {
  return new Promise((resolve) => {
    console.log('waitForBookingsData called. window.allBookings:', window.allBookings);
    
    if (window.allBookings && window.allBookings.length > 0) {
      console.log('Booking data already available:', window.allBookings.length, 'bookings');
      resolve();
      return;
    }
    
    // Check every 100ms for up to 5 seconds
    let attempts = 0;
    const maxAttempts = 50; // 5 seconds
    
    const checkInterval = setInterval(() => {
      attempts++;
      
      console.log(`Checking for booking data... Attempt ${attempts}/${maxAttempts}`);
      
      if (window.allBookings && window.allBookings.length > 0) {
        console.log('Booking data loaded:', window.allBookings.length, 'bookings');
        clearInterval(checkInterval);
        resolve();
      } else if (attempts >= maxAttempts) {
        console.warn('Booking data not available after 5 seconds, proceeding without it');
        console.log('Final window.allBookings value:', window.allBookings);
        clearInterval(checkInterval);
        resolve();
      }
    }, 100);
  });
}

adminCalendarGrid.onclick = (e) => {
    // 1. Find the parent day element
    const dayEl = e.target.closest('.calendar-day');
    
    // 2. Ignore clicks on empty slots or the grid background
    if (!dayEl || dayEl.classList.contains('empty')) return;
    console.log("Classes found:", dayEl.className); // Check if 'unavailable' is here
    console.log("Date found:", dayEl.dataset.date);
    const date = dayEl.dataset.date;
    
    // 3. Extract states using classList (more reliable than checking style colors)
    const hasBookings = dayEl.classList.contains('has-booking');
    const isUnavailable = dayEl.classList.contains('unavailable') || dayEl.style.backgroundColor === 'red';

    // 4. Prioritize Logic
    // If it has bookings, we usually want to see those first
    if (hasBookings) {
        console.log("Opening Bookings for:", date);
        showAdminBookingsForDate(date, CalendarState.bookingsMap[date] || []);
    } 
    // If no bookings, check if it's an unavailable/blocked date
    else if (isUnavailable) {
        // Direct lookup is much faster and cleaner than converting to an array and using .find()
      const availabilityArray = CalendarState.unavailableMapArray || [];
      
      // Safety check: only use .find() if it's actually an array
      const dayData = Array.isArray(availabilityArray) 
          ? availabilityArray.find(d => d.unavailable_date === date)
          : null;

      console.log("Data found:", dayData);
      showAvailabilityModalx(date, dayData);
      }
      // Default: It's a plain empty day
      else {
          console.log("Opening Availability Modal (Empty Day) for:", date);
          showAvailabilityModalx(date);
      }
    };
/**
 * Displays booking details for a selected date.
 * @param {string} date - ISO date string (YYYY-MM-DD)
 * @param {Array} bookings - Array of booking objects for that date
 */
function showAdminBookingsForDate(date, bookings) {
    const b = bookings[0];
    const container = document.getElementById("has-bookings-details");
    if (!container) return;

    const formattedDate = new Date(date).toLocaleDateString('en-US', { 
        weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' 
    });
    console.log("sa: ", b)
    // Helper for dynamic badge colors
    const getStatusStyle = (status) => {
        const s = (status || '').toLowerCase();
        if (s.includes('consult')) return 'background:#dcfce7; color:#166534;';
        if (s.includes('pend')) return 'background:#fef9c3; color:#854d0e;';
        if (s.includes('confirm')) return 'background:#e0e7ff; color:#3730a3;';
        return 'background:#f1f5f9; color:#475569;';
    };
    console.log("bookings: ", b)

    container.innerHTML = `
      <div style="background-color: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); overflow: hidden; width: 100%; max-width: 450px; border: 1px solid #eef2f7; font-family: sans-serif;">
        
        <!-- Header -->
        <div class="booking-details-header" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; background-color: #f8fafc; border-bottom: 1px solid #edf2f7;">
            <h3 style="margin: 0; font-size: 1.05rem; color: #1e293b;">Bookings for <span style="color: #6366f1;">${formattedDate}</span></h3>
            <button onclick="document.getElementById('has-bookings-details').style.display='none'" style="background: #e2e8f0; border: none; border-radius: 50%; width: 26px; height: 26px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #475569; font-size: 18px;">&times;</button>
        </div>

        <!-- List -->
        <div class="bookings-list" style="padding: 15px; max-height: 450px; overflow-y: auto;">
            
                <div class="booking-item" style="display: flex; flex-direction: column; padding: 15px; margin-bottom: 12px; border-radius: 10px; border: 1px solid #f1f5f9; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                    
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                        <strong style="font-size: 0.95rem; color: #0f172a;">${b.service_name || 'General Service'}</strong>
                        <span style="font-size: 0.7rem; font-weight: 700; padding: 3px 8px; border-radius: 12px; text-transform: uppercase; ${getStatusStyle(b.status)}">
                            ${b.status}
                        </span>
                    </div>

                    <div style="display: flex; align-items: center; gap: 6px; color: #64748b; font-size: 0.85rem;">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8z"></path></svg>
                        <span>${b.name || 'Guest'}</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px; color: #64748b; font-size: 0.85rem;">
                      <span>Address: </span>
                      <span>${b.address || 'N/A'}</span>
                    </div>

                    <div style="margin-top: 10px; padding-top: 8px; border-top: 1px solid #f8fafc; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.75rem; color: #94a3b8;">Code: ${b.booking_code || 'N/A'}</span>
                        <button style="background: none; border: none; color: #6366f1; font-size: 0.8rem; font-weight: 600; cursor: pointer; padding: 0;" onclick="handleModalAction('view','${b.id}');  document.getElementById('has-bookings-details').style.display='none'">View →</button>
                    </div>
                </div>
            
        </div>
      </div>
    `;

    container.style.display = 'flex';
}

function updateAdminCalendar() {
    const month = parseInt(adminMonthSelect.value);
    const year = parseInt(adminYearSelect.value);
    const monthStr = String(month + 1).padStart(2, '0');
    const todayStr = CalendarState.now.toISOString().split('T')[0];

    adminMonthDisplay.textContent = `${CalendarState.months[month]} ${year}`;

    const fragment = document.createDocumentFragment();
    adminCalendarGrid.innerHTML = '';

    // 1. Render Day Headers (Sun-Sat)
    ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"].forEach(d => {
        const div = document.createElement('div');
        div.className = 'day-name';
        div.textContent = d;
        fragment.appendChild(div);
    });

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    // 2. Render Empty Padding
    for (let i = 0; i < firstDay; i++) {
        const div = document.createElement('div');
        div.className = 'calendar-day empty';
        fragment.appendChild(div);
    }

    // 3. Render Actual Days
    for (let d = 1; d <= daysInMonth; d++) {
        const div = document.createElement('div');
        const dStr = String(d).padStart(2, '0');
        const dateKey = `${year}-${monthStr}-${dStr}`;
        
        div.className = 'calendar-day';
        div.dataset.date = dateKey; // Store date here for event delegation

        const dayBookings = CalendarState.bookingsMap[dateKey] || [];
        const isUnavailable = CalendarState.unavailableMap.has(dateKey);

        if (dateKey === todayStr) div.classList.add('today');

        // Debug: Log specific dates that should have bookings
        if (dateKey === '2026-05-19' || dateKey === '2026-05-26') {
            console.log(`Debug for ${dateKey}:`, {
                dayBookings: dayBookings,
                bookingsLength: dayBookings.length,
                isUnavailable: isUnavailable,
                hasBookingClass: div.classList.contains('has-booking')
            });
        }

        // logic: High priority (Unavailable) -> Bookings -> Empty
        if (isUnavailable) {
            div.classList.add('unavailable');
            div.style.backgroundColor = 'red';
            div.innerHTML = `<div class="day-number">${d}</div>`;
        } 
        else if (dayBookings.length > 0) {
            div.classList.add('has-booking');
            div.style.backgroundColor = 'green';
            div.innerHTML = `<div class="day-number">${d}</div>`;
            console.log(`Applied has-booking class to ${dateKey}`);
        } 
        else {
            div.textContent = d;
        }

        fragment.appendChild(div);
    }

    adminCalendarGrid.appendChild(fragment);
}
    // Function to add payment
    async function addPayment(bookingId) {
      const paymentAmount = parseFloat(document.getElementById('paymentAmount').value);
      
      if (!paymentAmount || paymentAmount <= 0) {
        alert('Please enter a valid payment amount');
        return;
      }
      
      if (!confirm(`Add payment of ₱${paymentAmount.toFixed(2)} to this booking?`)) {
        return;
      }
      
      try {
        const response = await fetch('/landscape/USER_API/BookingsController.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            action: 'add_payment',
            booking_id: bookingId,
            payment_amount: paymentAmount
          })
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
          alert(`Payment of ₱${paymentAmount.toFixed(2)} added successfully!`);
          closeBookingManagementModal();
          // Refresh bookings to show updated payment status
          if (typeof fetchData === 'function') {
            fetchData();
          }
        } else {
          alert('Failed to add payment: ' + (result.message || 'Unknown error'));
        }
      } catch (error) {
        console.error('Error adding payment:', error);
        alert('Failed to add payment. Please try again.');
      }
    }
/**
 * Closes the booking management modal.
 * Uses optional chaining for a safer removal process.
 */
function closeBookingManagementModal() {
    document.getElementById('bookingManagementModal')?.remove();
}

/**
 * Saves admin feedback to the server.
 * Optimized with modern async/await patterns and unified error handling.
 */
async function saveAdminFeedback(bookingId) {
    const feedbackInput = document.getElementById('adminFeedbackInput');
    
    // Safety check to ensure the element exists before accessing value
    if (!feedbackInput) return;
    
    const feedback = feedbackInput.value;
    
    try {
        const response = await fetch('/landscape/USER_API/BookingsController.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'update_admin_feedback',
                booking_id: bookingId,
                admin_feedback: feedback
            })
        });
        
        if (!response.ok) throw new Error(`Server error: ${response.status}`);
        
        const result = await response.json();
        
        if (result.status === 'success') {
            alert('Admin feedback saved successfully!');
            closeBookingManagementModal();
            
            // Re-sync data if global fetch function exists
            if (typeof fetchData === 'function') {
                fetchData();
            }
        } else {
            throw new Error(result.message || 'Unknown error');
        }
    } catch (error) {
        console.error('Error saving admin feedback:', error);
        alert(`Failed to save admin feedback: ${error.message}`);
    }
}

    /**
 * Fetches unavailable dates from the server and updates the global unavailableDates array.
 */
async function fetchUnavailableDates() {
    try {
        const response = await fetch(`/landscape/USER_API/AppointmentController.php?action=get_unavailable_dates`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const text = await response.text();
        
        // Check if response is valid JSON
        try {
            const result = JSON.parse(text);
            
            if (result.status === 'success') {
                window.unavailableDates = result.unavailable_dates || [];
                console.log('Fetched unavailable dates:', window.unavailableDates);
                CalendarState.unavailableMapArray = result.unavailable_dates || [];
            } else {
                window.unavailableDates = [];
                console.error('Failed to fetch unavailable dates:', result.message);
            }
        } catch (jsonError) {
            console.error('Invalid JSON response from AppointmentController:', text);
            window.unavailableDates = [];
        }
        
    } catch (error) {
        console.error('Error fetching unavailable dates:', error);
        window.unavailableDates = [];
    }
}

/**
 * Optimized removal of unavailable dates using async/await and 
 * consolidated UI refresh.
 */
async function removeUnavailableDate(date) {
    if (!confirm(`Are you sure you want to make ${date} available?`)) return;

    try {
        const response = await fetch('/landscape/USER_API/AppointmentController.php?action=remove_unavailable', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ date, admin_id: 2 })
        });

        // Basic check for non-JSON responses (prevents crash on 500 errors)
        if (!response.ok) throw new Error(`Server responded with ${response.status}`);
        
        const result = await response.json();

        if (result.status === 'success') {
            alert(`Date ${date} is now available!`);
            
            // Refresh data and UI sequentially
            await fetchUnavailableDates();
            updateAdminCalendar();
        } else {
            throw new Error(result.message || 'Unknown error');
        }
    } catch (error) {
        console.error('Error removing unavailable date:', error);
        alert(`Failed to make date available: ${error.message}`);
    }
}

/**
 * Lifecycle management
 */
document.addEventListener('DOMContentLoaded', () => {
    // Only init if the required UI elements exist
    if (adminMonthSelect && adminYearSelect) {
        // Small delay to ensure admin_booking.js has time to start fetching data
        setTimeout(() => {
            initAdminCalendar();
        }, 100);
    }

    const bookingContainer = document.getElementById('bookingContainer');
    if (bookingContainer) {
        // Optimized Observer: Debounce or simplify checks to prevent recursion
        const observer = new MutationObserver(() => {
            // Check existence of bookings before triggering a full re-render
            if (Array.isArray(window.allBookings) && window.allBookings.length > 0) {
                updateAdminCalendar();
            }
        });

        observer.observe(bookingContainer, { 
            childList: true, 
            subtree: true 
        });
    }
});
  </script>

  <!-- Availability Management Modal -->
  <div id="availabilityModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div class="modal-content" style="background: white; padding: 2rem; border-radius: 15px; max-width: 500px; width: 90%; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
      <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h3 style="margin: 0; color: var(--primary);">Manage Date Availability</h3>
        <button onclick="closeAvailabilityModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #666;">&times;</button>
      </div>
      
      <form id="availabilityForm">
        <div class="form-group" style="margin-bottom: 1rem;">
          <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Date:</label>
          <input type="date" id="availabilityDate" name="availaconsultedbilityDate" required style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px;">
        </div>
        
        <div class="form-group" style="margin-bottom: 1rem;">
          <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Status:</label>
          <select id="availabilityStatus" name="availabilityStatus" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px;">
            <option value="unavailable">Unavailable</option>
            <option value="available">Available</option>
          </select>
        </div>
        
        <div class="form-group" style="margin-bottom: 1.5rem;">
          <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Reason (optional):</label>
          <textarea id="availabilityReason" name="availabilityReason" rows="3" placeholder="Enter reason for unavailability..." style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; resize: vertical;"></textarea>
        </div>
        
        <div class="form-actions" style="display: flex; gap: 1rem; justify-content: flex-end;">
          <button type="button" onclick="closeAvailabilityModal()" style="padding: 0.75rem 1.5rem; border: 1px solid #ddd; background: #f8f9fa; border-radius: 8px; cursor: pointer;">Cancel</button>
          <button type="submit" style="padding: 0.75rem 1.5rem; border: 1px solid #ddd; background: #f8f9fa; border-radius: 8px; cursor: pointer;">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Availability Management Functions
    

    function closeAvailabilityModal() {
      const modal = document.getElementById('availabilityModal');
      modal.style.display = 'none';
      document.getElementById('availabilityForm').reset();
    }

    // Handle form submission
    document.getElementById('availabilityForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      
      const date = document.getElementById('availabilityDate').value;
      const status = document.getElementById('availabilityStatus').value;
      const reason = document.getElementById('availabilityReason').value;
      
      // Show loading state
      const submitBtn = e.target.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerText;
      submitBtn.disabled = true;
      submitBtn.innerText = 'Saving...';
      
      try {
        let response;
        
        if (status === 'unavailable') {
          // Add unavailable date
          response = await fetch('/landscape/USER_API/AppointmentController.php?action=add_unavailable', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              date: date,
              reason: reason || null,
              admin_id: 2 // Admin ID - you may want to get this dynamically
            })
          });
        } else {
          // Remove/make available date
          response = await fetch('/landscape/USER_API/AppointmentController.php?action=remove_unavailable', {
            method: 'DELETE',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              date: date,
              admin_id: 2 // Admin ID - you may want to get this dynamically
            })
          });
        }
        
        const result = await response.json();
        
        if (result.status === 'success') {
          // Show success message
          alert(`Date ${date} marked as ${status}${reason ? ' with reason: ' + reason : ''}`);
          
          // Refresh calendar
          if (typeof fetchData === 'function') {
            fetchData().then(() => {
              if (typeof updateAdminCalendar === 'function') updateAdminCalendar();
            });
          }
          
          closeAvailabilityModal();
        } else {
          throw new Error(result.message || 'Failed to update availability');
        }
      } catch (error) {
        console.error('Error updating availability:', error);
        alert('Failed to update availability: ' + error.message);
      } finally {
        // Restore button state
        submitBtn.disabled = false;
        submitBtn.innerText = originalText;
      }
    });
  </script>
  </body>
</html>
