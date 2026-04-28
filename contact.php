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
    $email = htmlspecialchars($_POST['email']);
    $phone = htmlspecialchars($_POST['phone']);
    
    // Enhanced service data capture
    $serviceId = htmlspecialchars($_POST['service_dropdown']);
    $sqm = floatval($_POST['sqm']);
    $basePrice = floatval($_POST['approx-value']);
    $totalCost = floatval($_POST['estimated-cost-input'] ?? 0);
    
    // Determine service name and category
    $serviceName = '';
    $serviceCategory = '';
    
        // Fetch service details from database
        $serviceStmt = $pdo->prepare("SELECT service_name, category FROM services WHERE id = ?");
        $serviceStmt->execute([$serviceId]);
        $serviceData = $serviceStmt->fetch();
        
        if ($serviceData) {
            $serviceName = $serviceData['service_name'];
            $serviceCategory = $serviceData['category'];
        }

    $message = htmlspecialchars($_POST['message']);

    // Capture address from form
    $address = htmlspecialchars($_POST['address'] ?? '');
    
    try {
        // Generate unique booking code
        $bookingCode = 'Q' . date('Y') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        
        // Insert quote request as a pending booking with quote details
        $stmt = $pdo->prepare("INSERT INTO bookings (booking_code, user_id, service_id, appointment_date, address, status, notes, sqm, base_price, total_cost) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$bookingCode, $userId, $serviceId, date('Y-m-d H:i:s'), $address, 'Pending', $message, $sqm, $basePrice, $totalCost])) {
            $bookingId = $pdo->lastInsertId();
            
            // Create transaction record for quote request
            $transactionStmt = $pdo->prepare("INSERT INTO transactions (booking_id, description, type, status, amount, transaction_date) VALUES (?, ?, ?, ?, ?, NOW())");
            $transactionDescription = "Quote request for {$serviceName} - {$sqm}sqm at $" . number_format($basePrice, 2) . "/sqm";
            $transactionStmt->execute([$bookingId, $transactionDescription, 'Status Change', 'Completed', $totalCost]);
            
            $successMessage = "Your quote request has been sent successfully! We will contact you soon.";
        }
    } catch (PDOException $e) {
        $errorMessage = "Error sending request. Please try again later.";
        error_log("Quote submission error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Us - GreenScape Landscaping</title>
  <link rel="stylesheet" href="client-style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="/landscape/assets/tailwind.js"></script>
</head>
<body>
  <!-- Navigation -->
  <nav class="navbar">
    <a href="index.php" class="logo">
      <div class="logo-icon">
        <i class="fas fa-leaf"></i>
      </div>
      GreenScape
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
            <p>(555) 123-4567</p>
          </div>
        </div>

        <div class="contact-item">
          <div class="contact-icon">
            <i class="fas fa-envelope"></i>
          </div>
          <div>
            <h4>Email</h4>
            <p>info@greenscape.com</p>
          </div>
        </div>

        <div class="contact-item">
          <div class="contact-icon">
            <i class="fas fa-map-marker-alt"></i>
          </div>
          <div>
            <h4>Address</h4>
            <p>123 Garden Lane, Green City</p>
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
            <input type="text" id="name" name="name" placeholder="Enter your full name" required <?= !$isLoggedIn ? 'disabled' : '' ?>>
          </div>
          <!-- <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" placeholder="Enter your email" required <?= !$isLoggedIn ? 'disabled' : '' ?>>
          </div> -->
          <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" <?= !$isLoggedIn ? 'disabled' : '' ?>>
          </div>
          <div class="form-group">
            <label for="address">Service Address</label>
            <input type="text" id="address" name="address" placeholder="Enter the service address" required <?= !$isLoggedIn ? 'disabled' : '' ?>>
          </div>
          <div class="form-group">
            <label for="service_dropdown">Service Interested In</label>
            <select id="service_dropdown" name="service_dropdown" onchange="toggleCustomInput(this.value)" required <?= !$isLoggedIn ? 'disabled' : '' ?> style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 8px; font-family: inherit;">
                <option value="" disabled selected>Select a service</option>
                <option value="Lawn Maintenance">Lawn Maintenance</option>
                <option value="Other">Other</option>
                <option value="Custom">Custom input</option>
            </select>
          </div>
          
          <div id="custom_service_container" class="form-group" style="display: none; margin-top: 1rem;">
            <label for="custom_service">Specify Service</label>
            <input type="text" id="custom_service" name="custom_service" placeholder="Enter manual service name" <?= !$isLoggedIn ? 'disabled' : '' ?>>
          </div>

          <!-- AREA SPINNER -->
          <div class="form-group">
            <label for="sqm" class="block text-sm font-medium text-slate-600 mb-1.5">Area Size (sqm)</label>
            <div class="flex items-center gap-4">
              <button id="sub-estimate" class="p-3 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 active:scale-95 transition-all">
                <svg class="w-5 h-5 text-emerald-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
              </button>
              <div class="flex-1 bg-white border border-gray-200 rounded-xl text-center text-xl font-semibold text-emerald-900">
                <input class="text-sm font-normal text-slate-400" id="sqm" name="sqm" min="0" step="0.01" type="number" required/>
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
              <p class="text-[10px] text-emerald-600">Approx. <span id="baseprice">$150</span>/sqm</p>
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
        <h3>GreenScape</h3>
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
        <p><i class="fas fa-phone"></i> (555) 123-4567</p>
        <p><i class="fas fa-envelope"></i> info@greenscape.com</p>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; 2026 GreenScape Landscaping. All rights reserved.</p>
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
  </script>
</body>
</html>