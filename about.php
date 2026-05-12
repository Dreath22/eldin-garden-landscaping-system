<?php
require_once __DIR__ . '/config/auth_middleware.php';

// Initialize standard session
$sessionData = initStandardSession();
$user = $sessionData['user'];
$isLoggedIn = $sessionData['isLoggedIn'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About Us - EldinGarden Landscaping</title>
  <link rel="stylesheet" href="client-style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
      <li><a href="about.php" class="active">About</a></li>
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

  <!-- About Hero -->
  <section class="about-hero">
    <div class="hero-content">
      <h1>About EldinGarden</h1>
      <p>Creating beautiful outdoor spaces since 2005</p>
    </div>
  </section>

  <!-- About Content -->
  <section class="section">
    <div class="about-content">
      <div class="about-grid">
        <div class="about-image">
          <img src="https://images.unsplash.com/photo-1558904541-efa843a96f01?w=800" alt="EldinGarden Team">
        </div>
        <div class="about-text">
          <h3>Our Story</h3>
          <p>EldinGarden was founded in 2005 with a simple mission: to transform ordinary outdoor spaces into extraordinary landscapes. What started as a small family business has grown into one of the most trusted landscaping companies in the region.</p>
          <p>Our team of passionate horticulturists, designers, and craftsmen work together to create sustainable, beautiful outdoor environments that our clients love. We believe that every landscape has the potential to be a masterpiece.</p>
        </div>
      </div>

      <div class="about-grid">
        <div class="about-text">
          <h3>Our Mission</h3>
          <p>At EldinGarden, we're committed to excellence in every project we undertake. Our mission is to provide exceptional landscaping services that enhance the beauty and value of your property while promoting environmental sustainability.</p>
          <p>We use eco-friendly practices, native plants, and efficient irrigation systems to create landscapes that are not only beautiful but also sustainable for years to come.</p>
        </div>
        <div class="about-image">
          <img src="https://images.unsplash.com/photo-1585320806297-9794b3e4eeae?w=800" alt="Beautiful Garden">
        </div>
      </div>

      <div class="stats-grid">
        <div class="stat-item">
          <h4>15+</h4>
          <p>Years of Experience</p>
        </div>
        <div class="stat-item">
          <h4>500+</h4>
          <p>Projects Completed</p>
        </div>
        <div class="stat-item">
          <h4>100%</h4>
          <p>Client Satisfaction</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Team Section -->
  <section class="section" style="background-color: var(--light-cream);">
    <div class="section-header">
      <h2>Meet Our Team</h2>
      <p>The passionate experts behind EldinGarden</p>
    </div>
    <style>
      .asjfadsnlk-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        padding: 20px;
      }

      .asjfadsnlk-card {
        position: relative;
        height: 450px; 
        border-radius: 15px;
        background-size: cover;
        background-position: center;
        display: flex;
        flex-direction: column;
        justify-content: flex-end; 
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: 1px solid rgba(255, 255, 255, 0.1);
      }

      .asjfadsnlk-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3);
      }

      .asjfadsnlk-content {
        padding: 30px;
        color: #ffffff;
        text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.5);
      }

      .asjfadsnlk-content h3 {
        margin: 0;
        font-size: 1.8rem;
        font-weight: 700;
      }

      .asjfadsnlk-role {
        color: #a8cf45; 
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 1.2px;
        margin: 8px 0 15px 0;
      }

      .asjfadsnlk-content p:not(.asjfadsnlk-role) {
        font-size: 1rem;
        line-height: 1.5;
        opacity: 0.95;
        margin: 0;
      }
    </style>
    <div class="asjfadsnlk-grid">
      <div class="asjfadsnlk-card" style="background-image: linear-gradient(rgba(0,0,0,0.1), rgba(0,0,0,0.8)), url('assets/img/male.jpeg');">
        <div class="asjfadsnlk-content">
          <h3>[Husband Name]</h3>
          <p class="asjfadsnlk-role">Co-Owner & Director of Operations</p>
          <p>With over [Number] years of field expertise, [Husband Name] oversees every project from ground-break to completion, ensuring structural integrity and precision in every build.</p>
        </div>
      </div>

      <div class="asjfadsnlk-card" style="background-image: linear-gradient(rgba(0,0,0,0.1), rgba(0,0,0,0.8)), url('assets/img/female.jpeg');">
        <div class="asjfadsnlk-content">
          <h3>[Wife Name]</h3>
          <p class="asjfadsnlk-role">Co-Owner & Principal Designer</p>
          <p>[Wife Name] bridges the gap between imagination and reality, utilizing her keen eye for horticulture and aesthetics to create outdoor living spaces that feel like home.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="footer">
    <div class="footer-content">
      <div class="footer-section">
        <h3>EldinGarden</h3>
        <p>Professional landscaping services that bring your outdoor vision to life. We create beautiful, sustainable landscapes for homes and businesses.</p>
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
        <h3>Services</h3>
        <p><a href="services.php">Lawn Maintenance</a></p>
        <p><a href="services.php">Garden Design</a></p>
        <p><a href="services.php">Hardscaping</a></p>
        <p><a href="services.php">Irrigation Systems</a></p>
      </div>
      <div class="footer-section">
        <h3>Contact Us</h3>
        <p><i class="fas fa-phone"></i> 0945 547 5152</p>
        <p><i class="fas fa-envelope"></i> info@EldinGarden.com</p>
        <p><i class="fas fa-map-marker-alt"></i> Bautista St., Brgy. Sampaloc IV, Dasmariñas City, Cavite</p>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; 2005 EldinGarden Landscaping. All rights reserved.</p>
    </div>
  </footer>

  <script>
    function toggleMenu() {
      document.getElementById('navLinks').classList.toggle('active');
    }
  </script>
</body>
</html>