<?php
require_once __DIR__ . '/config/auth_middleware.php';

// Initialize standard session
$sessionData = initStandardSession();
$user = $sessionData['user'];
$isLoggedIn = $sessionData['isLoggedIn']; // Testing - hardcoded to true
$successMessage = "";
$errorMessage = "";


// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if user is logged in before processing
    if (!$isLoggedIn) {
        header("Location: login.php?error=You must be logged in to request a quote.");
        exit();
    }

    // Sanitize and capture inputs
    $userId = $_SESSION['user_id']; // Use actual session user ID
    $name = htmlspecialchars($_POST['name']);
    $phone = htmlspecialchars($_POST['phone']);
    
    // Validate if the user ID is valid
    if (!filter_var($userId, FILTER_VALIDATE_INT) || $userId <= 0) {
        $errorMessage = "Invalid user session. Please log in again.";
        error_log("Invalid user ID: " . $userId);
        // Don't exit here, let the form show with error
    } else {
        // Enhanced service data capture
        $serviceId = htmlspecialchars($_POST['service_dropdown']);//has a value tag that should the one this would get
        $sqm = floatval($_POST['sqm']); // make a fallback here making sure that less than that of 15sqm or such would be not eligible
        
        // Validate minimum SQM requirement
        if ($sqm < 15) {
            $errorMessage = "Service area must be at least 15 square meters to be eligible for a quote.";
        } else {
            // Fetch service details from database
            $serviceStmt = $pdo->prepare("SELECT service_name, base_price FROM services WHERE id = ? AND status = 'active'");
            $serviceStmt->execute([$serviceId]);
            $serviceData = $serviceStmt->fetch();
            
            if (!$serviceData) {
                $errorMessage = "Invalid service selected. Please choose a valid service.";
            } else {
                $serviceName = $serviceData['service_name'];
                $basePrice = floatval($serviceData['base_price']);
                
                // Calculate total cost with fallback
                $totalCost = floatval($basePrice * $sqm);
                
                // Validate calculated cost
                if ($totalCost <= 0 || !is_finite($totalCost)) {
                    $errorMessage = "Unable to calculate cost. Please verify service and area details.";
                } else {
                    $message = htmlspecialchars($_POST['message']);
                    $address = htmlspecialchars($_POST['address']); // should not be null and should be a valid one
                    
                    // Validate address
                    if (empty(trim($address))) {
                        $errorMessage = "Address is required and cannot be empty.";
                    } else {
                        try {
                            // Generate unique booking code with fallback mechanism
                            $maxAttempts = 5;
                            $bookingCode = '';
                            $codeGenerated = false;
                            
                            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                                $bookingCode = 'Q' . date('Y') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
                                
                                // Check if booking code already exists
                                $checkStmt = $pdo->prepare("SELECT id FROM bookings WHERE booking_code = ?");
                                $checkStmt->execute([$bookingCode]);
                                
                                if (!$checkStmt->fetch()) {
                                    // Code is unique, proceed with insertion
                                    $codeGenerated = true;
                                    break;
                                }
                                
                                // Log duplicate attempt (for debugging)
                                error_log("Duplicate booking code detected (attempt $attempt/$maxAttempts): $bookingCode");
                            }
                            
                            if (!$codeGenerated) {
                                throw new Exception("Failed to generate unique booking code after $maxAttempts attempts");
                            }
                            
                            // Insert quote request as a pending booking with consultation status
                            // Note: base_price column removed as mentioned in plan
                            // For consultation/quote stage, use a placeholder appointment date
                            $appointmentDate = '1970-01-01 00:00:00'; // Placeholder date for quote requests
                            $stmt = $pdo->prepare("INSERT INTO bookings (booking_code, user_id, service_id, address, status, notes, sqm, total_cost, base_price, appointment_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            if ($stmt->execute([$bookingCode, $userId, $serviceId, $address, 'Consultation', $message, $sqm, $totalCost, $basePrice, $appointmentDate])) {
                                $bookingId = $pdo->lastInsertId();
                                
                                // Generate unique transaction code
                                $transactionCode = 'QTX' . date('Y') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
                                
                                // Create transaction record for consultation request (zero amount, client message in notes)
                                $transactionStmt = $pdo->prepare("INSERT INTO transactions (transaction_code, booking_id, description, type, status, amount, notes, transaction_date) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                                $transactionDescription = "Consultation request for {$serviceName} - {$sqm}sqm at ₱" . number_format($basePrice, 2) . "/sqm";
                                $transactionNotes = "Client message: " . $message;
                                $transactionStmt->execute([$transactionCode, $bookingId, $transactionDescription, 'Consultations', 'Quote', 0, $transactionNotes]);
                                
                                $successMessage = "Your consultation request has been sent successfully! We will contact you soon.";
                            } else {
                                $errorMessage = "Failed to create quote request. Please try again.";
                            }
                        } catch (PDOException $e) {
                            $errorMessage = "Error sending request. Please try again later.";
                            error_log("Quote submission error: " . $e->getMessage());
                        }
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Us - EldinGarden Landscaping</title>
  <link rel="stylesheet" href="client-style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="/landscape/assets/tailwind.js"></script>
  <style>
    .area-input-wrapper {
      flex-grow: 1;
      position: relative;
    }
    .area-input-wrapper input {
      text-align: center;
      padding-right: 45px !important;
    }
    .area-unit {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #666;
      pointer-events: none;
    }
  </style>
</head>
<body>
  <!-- Navigation -->
  <nav class="navbar">
    <a href="index.php" class="logo">
      <div class="logo-icon">
        <img src="assets/img/LOGO.png" alt="EldinGarden Logo" style="height: 24px; width: auto; vertical-align: middle;">
      </div>
      EldinGarden
    </a>
    <div class="menu-toggle" onclick="toggleMenu()">
      <span></span>
      <span></span>
      <span></span>
    </div>
    <ul class="nav-links" id="navLinks">
      <li><a href="index.php">Home</a></li>
      <li><a href="about.php">About</a></li>
      <li><a href="services.php">Services</a></li>
      <li><a href="gallery.php">Gallery</a></li>
      <li><a href="contact.php" class="active">Contact</a></li>
      <?php if ($isLoggedIn): ?>
        <li><a href="profile.php" class="btn-login">My Profile</a></li>
      <?php else: ?>
        <li><a href="login.php" class="btn-login">Log in</a></li>
      <?php endif; ?>
    </ul>
  </nav>

  <!-- Contact Hero -->
  <section class="contact-hero">
    <div class="hero-content">
      <h1>Contact Us</h1>
      <p>Get in touch for a free consultation</p>
    </div>
  </section>

  <!-- Contact Content -->
  <section class="section">
    <div class="contact-content">
      <div class="contact-info">
        <h3>Get In Touch</h3>
        <p style="color: var(--text-gray); margin-bottom: 2rem;">Have questions about our services? Ready to start your landscaping project? We'd love to hear from you!</p>
        
        <!-- Success/Error Alerts -->
        <?php if ($successMessage): ?>
          <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid #bbf7d0;">
            <i class="fas fa-check-circle"></i> <?= $successMessage ?>
          </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
          <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid #fecaca;">
            <i class="fas fa-exclamation-circle"></i> <?= $errorMessage ?>
          </div>
        <?php endif; ?>

        <div class="contact-item">
          <div class="contact-icon">
            <i class="fas fa-phone"></i>
          </div>
          <div>
            <h4>Phone</h4>
            <p>0945 547 5152</p>
          </div>
        </div>

        <div class="contact-item">
          <div class="contact-icon">
            <i class="fas fa-envelope"></i>
          </div>
          <div>
            <h4>Email</h4>
            <p>info@EldinGarden.com</p>
          </div>
        </div>

        <div class="contact-item">
          <div class="contact-icon">
            <i class="fas fa-map-marker-alt"></i>
          </div>
          <div>
            <h4>Address</h4>
            <p>Bautista St., Brgy. Sampaloc IV, Dasmariñas City, Cavite</p>
          </div>
        </div>
      </div>

      <div class="contact-form">
        <h3>Request a Free Quote</h3>
        
        <?php if (!$isLoggedIn): ?>
          <div style="background: #fffbeb; color: #92400e; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid #fef3c7; font-size: 0.9rem;">
            <i class="fas fa-info-circle"></i> You must <a href="login.php" style="font-weight: bold; text-decoration: underline;">Login</a> to submit this form.
          </div>
        <?php endif; ?>

        <form action="contact.php" method="POST">
          <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" placeholder="Enter your full name" value="<?= $isLoggedIn ? htmlspecialchars($user['name']) : '' ?>" required <?= !$isLoggedIn ? 'disabled' : '' ?>>
          </div>
          <!-- <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" placeholder="Enter your email" required <?= !$isLoggedIn ? 'disabled' : '' ?>>
          </div> -->
          <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" value="<?= $isLoggedIn ? htmlspecialchars($user['phone_number'] ?? '') : '' ?>" <?= !$isLoggedIn ? 'disabled' : '' ?>>
          </div>
          <div class="form-group">
            <label for="address">Service Address</label>
            <input type="text" id="address" name="address" placeholder="Enter the service address" required <?= !$isLoggedIn ? 'disabled' : '' ?>>
          </div>
          <div class="form-group">
            <label for="service_dropdown">Service Interested In</label>
            <select id="service_dropdown" name="service_dropdown" onchange="toggleCustomInput(this.value)" required <?= !$isLoggedIn ? 'disabled' : '' ?> style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 8px; font-family: inherit;">
            </select>
          </div>

          <!-- AREA SPINNER -->
          <div class="form-group">
            <label for="sqm" class="block text-sm font-medium text-slate-600 mb-1.5">Area Size (sqm)</label>
            <div class="flex items-center gap-4">
              <button id="sub-estimate" class="p-3 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 active:scale-95 transition-all">
                <svg class="w-5 h-5 text-emerald-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
              </button>
              <div class="flex-1 bg-white border border-gray-200 rounded-xl text-center text-xl font-semibold text-emerald-900 area-input-wrapper">
                <input class="text-sm font-normal text-slate-400" id="sqm" name="sqm" min="0" step="0.01" type="number" required/>
                <span class="area-unit" style="font-size:12px;">sqm</span>
              </div>
              <button id="add-estimate" class="p-3 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 active:scale-95 transition-all">
                <svg class="w-5 h-5 text-emerald-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
              </button>
            </div>
          </div>
          <!-- AUTO-CALCULATING DYNAMIC OUTPUT -->
          <div class="form-group bg-emerald-50 p-4 rounded-2xl border border-emerald-100 flex justify-between items-center">
            <div>
              <label for="approx-value"></label> <p class="text-xs uppercase tracking-wider text-emerald-700 font-bold">Estimated Total</p>
              <p class="text-2xl font-black text-emerald-900" id="estimated-cost"></p>
            </div>
            <div class="text-right">
              <p class="text-[12px] text-emerald-600">Approx. <span id="baseprice">₱150</span>/sqm</p>
              <input type="hidden" name="approx-value" id="approx-value" />
              <input type="hidden" name="estimated-cost-input" id="estimated-cost-input" value="0" />
            </div>
          </div>

          <div class="form-group">
            <label for="message">Message</label>
            <textarea id="message" name="message" placeholder="Tell us about your project..." <?= !$isLoggedIn ? 'disabled' : '' ?>></textarea>
          </div>
          <button type="submit" class="btn btn-primary" style="width: 100%;">Send Message</button>
        </form>
      </div>
    </div>
  </section>

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
  <script type="module" src="JS/Client-Contact.js"></script>
  <script>
    function toggleMenu() {
      document.getElementById('navLinks').classList.toggle('active');
    }

    function toggleCustomInput(value) {
        const container = document.getElementById('custom_service_container');
        const customInput = document.getElementById('custom_service');
        if (value === 'Custom') {
            container.style.display = 'block';
            customInput.setAttribute('required', 'required');
        } else {
            container.style.display = 'none';
            customInput.removeAttribute('required');
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
              calculatedEstimate: updateResult.data.calculated_estimate
            });
            return updateResult.data.calculated_estimate;
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

    // Add event listeners for service and area changes
    document.addEventListener('DOMContentLoaded', function() {
      const serviceField = document.getElementById('service');
      const areaField = document.getElementById('area');
      
      if (serviceField && areaField) {
        const calculateEstimate = async () => {
          const serviceId = serviceField.value;
          const area = areaField.value;
          
          if (serviceId && area && parseFloat(area) > 0) {
            // For new bookings, we don't have a booking ID yet
            // This would be used after booking creation
            console.log('Service:', serviceId, 'Area:', area, 'Estimate calculation ready');
          }
        };
        
        // Add change event listeners
        serviceField.addEventListener('change', calculateEstimate);
        areaField.addEventListener('input', calculateEstimate);
      }
    });
  </script>
</body>
</html>