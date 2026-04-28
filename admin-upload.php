<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Content Upload - GreenScape Admin</title>
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
          <a href="admin-upload.php" class="admin-nav-item active">
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
          <input type="text" placeholder="Search content...">
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
            <h2>Content Upload Manager</h2>
            <p>Upload and manage content for your website.</p>
          </div>
          <a href="admin-gallery.php" class="btn btn-secondary">
            <i class="fas fa-images"></i> View Gallery
          </a>
        </div>

        <!-- Stats Cards -->
        <div class="dashboard-stats" style="grid-template-columns: repeat(4, 1fr);">
          <div class="stat-card">
            <div class="stat-card-header">
              <div>
                <h3>248</h3>
                <p>Total Files</p>
              </div>
              <div class="stat-card-icon blue">
                <i class="fas fa-file"></i>
              </div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-card-header">
              <div>
                <h3>156</h3>
                <p>Images</p>
              </div>
              <div class="stat-card-icon green">
                <i class="fas fa-image"></i>
              </div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-card-header">
              <div>
                <h3>12</h3>
                <p>Videos</p>
              </div>
              <div class="stat-card-icon orange">
                <i class="fas fa-video"></i>
              </div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-card-header">
              <div>
                <h3>80</h3>
                <p>Documents</p>
              </div>
              <div class="stat-card-icon purple">
                <i class="fas fa-file-alt"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Upload Section -->
        <div class="dashboard-grid" style="margin-top: 1.5rem;">
          <!-- Upload Form -->
          <div class="dashboard-card">
            <div class="dashboard-card-header">
              <h3><i class="fas fa-cloud-upload-alt"></i> Upload New Content</h3>
            </div>
            <div class="dashboard-card-body">
              <form id="uploadForm">
                <div class="form-group">
                  <label for="contentTitle">Title</label>
                  <input type="text" id="contentTitle" placeholder="Enter content title" required>
                </div>
                <div class="form-group">
                  <label for="contentDescription">Description</label>
                  <textarea id="contentDescription" rows="3" placeholder="Enter description" required></textarea>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label for="contentCategory"> Service Category</label>
                    <select id="contentCategory" required>
                      <option value="">Select service category</option>
                      <option value="gallery">Gallery</option>
                      <option value="services">Services</option>
                      <option value="testimonials">Testimonials</option>
                      <option value="promotions">Promotions</option>
                      <option value="banner">Banner</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="contentStatus">Status</label>
                    <select id="contentStatus" required>
                      <option value="live">Live</option>
                      <option value="draft">Draft</option>
                    </select>
                  </div>
                </div>
                <div class="upload-zone" onclick="document.getElementById('fileInput').click()">
                  <i class="fas fa-cloud-upload-alt"></i>
                  <h4>Drag & Drop or Click to Upload</h4>
                  <p>Supported formats: JPG, PNG, GIF, MP4, PDF (Max 10MB)</p>
                  <input type="file" id="fileInput" style="display: none;" multiple accept="image/*,video/*,.pdf" required>
                </div>
                <div class="upload-preview" id="uploadPreview">
                  <!-- Preview items will be added here -->
                </div>
                <button type="submit" id="upload-submit" class="btn btn-primary" style="margin-top: 1rem; width: 100%;">
                  <i class="fas fa-upload"></i> Upload Content
                </button>
              </form>
            </div>
          </div>

          <!-- Client Preview -->
          <div class="dashboard-card">
            <div class="dashboard-card-header">
              <h3><i class="fas fa-eye"></i> Client-Side Preview</h3>
            </div>
            <div class="dashboard-card-body">
              <p style="color: var(--text-gray); font-size: 0.9rem; margin-bottom: 1rem;">This is how the content will appear to clients:</p>
              
              <div class="preview-card">
                <div class="preview-card-image" id="preview-card-image" style="background-image: url('https://images.unsplash.com/photo-1558904541-efa843a96f01?w=400');"></div>
                <div class="preview-card-content">
                  <h5 id="title-changes">Example Title</h5>
                  <p id="description-changes">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Neque molestias vitae dolor repellendus molestiae eveniet numquam odio, quas veniam magni in perspiciatis commodi iste sapiente quia asperiores earum provident sed.</p>
                </div>
              </div>

              <div style="margin-top: 1.5rem;">
                <h5 style="margin-bottom: 0.75rem; color: var(--text-dark);">Recently Uploaded</h5>
                <div id="recentUploaded" style="display: flex; flex-direction: column; gap: 0.75rem;">
                  <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background-color: #f8fafc; border-radius: 8px;">
                    <img id="recent-image-1" src="https://images.unsplash.com/photo-1585320806297-9794b3e4eeae?w=100" style="width: 50px; height: 50px; border-radius: 6px; object-fit: cover;">
                    <div style="flex: 1;">
                      <p style="font-weight: 600; font-size: 0.9rem;" id="recent-title-1">Flower Garden Design</p>
                      <p style="font-size: 0.8rem; color: var(--text-gray);" id="recent-date-1">Gallery • Feb 18, 2026</p>
                    </div>
                    <span class="status-badge active" id="status-1">Live</span>
                  </div>
                  <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background-color: #f8fafc; border-radius: 8px;">
                    <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=100" style="width: 50px; height: 50px; border-radius: 6px; object-fit: cover;">
                    <div style="flex: 1;">
                      <p style="font-weight: 600; font-size: 0.9rem;">Lawn Care Service</p>
                      <p style="font-size: 0.8rem; color: var(--text-gray);">Services • Feb 17, 2026</p>
                    </div>
                    <span class="status-badge active">Live</span>
                  </div>
                  <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background-color: #f8fafc; border-radius: 8px;">
                    <img src="https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=100" style="width: 50px; height: 50px; border-radius: 6px; object-fit: cover;">
                    <div style="flex: 1;">
                      <p style="font-weight: 600; font-size: 0.9rem;">Irrigation Installation</p>
                      <p style="font-size: 0.8rem; color: var(--text-gray);">Gallery • Feb 15, 2026</p>
                    </div>
                    <span class="status-badge pending">Draft</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Uploads Table -->
        <div class="dashboard-card" style="margin-top: 1.5rem;">
          <div class="dashboard-card-header">
            <h3><i class="fas fa-history"></i> Recent Uploads</h3>
            <a href="admin-gallery.php" style="color: var(--primary-green); font-size: 0.9rem;">View All</a>
          </div>
          <div class="dashboard-card-body">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Preview</th>
                  <th>Title</th>
                  <th>Category</th>
                  <th>Size</th>
                  <th>Uploaded</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="portfolioTable">
                <tr>
                  <td><img src="https://images.unsplash.com/photo-1585320806297-9794b3e4eeae?w=100" style="width: 50px; height: 50px; border-radius: 6px; object-fit: cover;"></td>
                  <td>Flower Garden Design</td>
                  <td>Gallery</td>
                  <td><i class="fas fa-image" style="color: var(--primary-green);"></i> JPG</td>
                  <td>2.4 MB</td>
                  <td>Feb 18, 2026</td>
                  <td><span class="status-badge active">Live</span></td>
                  <td>
                    <div class="table-actions">
                      <button class="table-btn view" title="View"><i class="fas fa-eye"></i></button>
                      <button class="table-btn edit" title="Edit"><i class="fas fa-edit"></i></button>
                      <button class="table-btn delete" title="Delete"><i class="fas fa-trash"></i></button>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=100" style="width: 50px; height: 50px; border-radius: 6px; object-fit: cover;"></td>
                  <td>Lawn Care Service</td>
                  <td>Services</td>
                  <td><i class="fas fa-image" style="color: var(--primary-green);"></i> PNG</td>
                  <td>1.8 MB</td>
                  <td>Feb 17, 2026</td>
                  <td><span class="status-badge active">Live</span></td>
                  <td>
                    <div class="table-actions">
                      <button class="table-btn view" title="View"><i class="fas fa-eye"></i></button>
                      <button class="table-btn edit" title="Edit"><i class="fas fa-edit"></i></button>
                      <button class="table-btn delete" title="Delete"><i class="fas fa-trash"></i></button>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><img src="https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=100" style="width: 50px; height: 50px; border-radius: 6px; object-fit: cover;"></td>
                  <td>Irrigation Installation</td>
                  <td>Gallery</td>
                  <td><i class="fas fa-image" style="color: var(--primary-green);"></i> JPG</td>
                  <td>3.2 MB</td>
                  <td>Feb 15, 2026</td>
                  <td><span class="status-badge pending">Draft</span></td>
                  <td>
                    <div class="table-actions">
                      <button class="table-btn view" title="View"><i class="fas fa-eye"></i></button>
                      <button class="table-btn edit" title="Edit"><i class="fas fa-edit"></i></button>
                      <button class="table-btn delete" title="Delete"><i class="fas fa-trash"></i></button>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><div style="width: 50px; height: 50px; background-color: #f8fafc; border-radius: 6px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-video" style="color: var(--primary-green); font-size: 1.2rem;"></i></div></td>
                  <td>Garden Transformation Video</td>
                  <td>Promotions</td>
                  <td><i class="fas fa-video" style="color: var(--primary-green);"></i> MP4</td>
                  <td>45.6 MB</td>
                  <td>Feb 14, 2026</td>
                  <td><span class="status-badge active">Live</span></td>
                  <td>
                    <div class="table-actions">
                      <button class="table-btn view" title="View"><i class="fas fa-eye"></i></button>
                      <button class="table-btn edit" title="Edit"><i class="fas fa-edit"></i></button>
                      <button class="table-btn delete" title="Delete"><i class="fas fa-trash"></i></button>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td><div style="width: 50px; height: 50px; background-color: #f8fafc; border-radius: 6px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-file-pdf" style="color: #ef4444; font-size: 1.2rem;"></i></div></td>
                  <td>Service Price List 2026</td>
                  <td>Documents</td>
                  <td><i class="fas fa-file-pdf" style="color: #ef4444;"></i> PDF</td>
                  <td>856 KB</td>
                  <td>Feb 12, 2026</td>
                  <td><span class="status-badge active">Live</span></td>
                  <td>
                    <div class="table-actions">
                      <button class="table-btn view" title="View"><i class="fas fa-eye"></i></button>
                      <button class="table-btn edit" title="Edit"><i class="fas fa-edit"></i></button>
                      <button class="table-btn delete" title="Delete"><i class="fas fa-trash"></i></button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>

            <!-- Pagination -->
            <div class="pagination" id="pagination">
              
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
  <!-- View Image Modal -->
  <div class="modal-overlay" id="viewImageModal" style="display: none;">
    <div class="modal modal-large">
      <div class="modal-header">
        <h3><i class="fas fa-image" style="color: var(--primary-green);"></i> Image Preview</h3>
        <button class="modal-close close">&times;</button>
      </div>
      <div class="modal-body">
        <div class="upload-preview" id="upload-preview">
          <!-- <img id="previewImage" src="" alt="Preview" style="width: 100%; border-radius: 8px; margin-bottom: 1rem;"> -->
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div>
            <p style="color: var(--text-gray); font-size: 0.85rem;">Title</p>
            <p id="previewTitle" style="font-weight: 600;">Beautiful Backyard</p>
          </div>
          <div>
            <p style="color: var(--text-gray); font-size: 0.85rem;">Category</p>
            <p id="previewCategory">Landscaping</p>
          </div>
          <div>
            <p style="color: var(--text-gray); font-size: 0.85rem;">Uploaded</p>
            <p id="previewDate">Feb 18, 2026</p>
          </div>
          <div>
            <p style="color: var(--text-gray); font-size: 0.85rem;">File Size</p>
            <p id="previewFilesize">2.4 MB</p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-small close" style="background-color: #f1f5f9; color: var(--text-dark);">Close</button>
        <button class="table-btn edit close" title="Edit"><i class="fas fa-edit"></i></button>
        <button class="table-btn delete close" title="Delete" ><i class="fas fa-trash"></i></button>
      </div>
    </div>
  </div>

  <!-- Edit Image Modal -->
  <div class="modal-overlay" id="editImageModal" style="display: none;">
    <div class="modal">
      <div class="modal-header">
        <h3><i class="fas fa-edit" style="color: var(--primary-green);"></i> Edit Image</h3>
        <button class="modal-close close">&times;</button>
      </div>
      <div class="modal-body">
        <form id="editImageForm">
          <div class="form-group">
            <label for="editImageTitle">Title</label>
            <input type="text" id="editImageTitle" value="Beautiful Backyard">
          </div>
          <div class="form-group">
            <label for="editImageDescription">Description</label>
            <textarea id="editImageDescription" rows="2">A stunning backyard transformation with native plants.</textarea>
          </div>
          <div class="form-group">
            <label for="editImageCategory">Category</label>
            <select id="editImageCategory">
              <option value="landscaping" selected>Landscaping</option>
              <option value="garden">Garden Design</option>
              <option value="lawn">Lawn Care</option>
              <option value="hardscape">Hardscaping</option>
              <option value="beforeafter">Before & After</option>
            </select>
          </div>
          <div class="form-group">
            <label for="editImageStatus">Status</label>
            <select id="editImageStatus">
              <option value="live" selected>Live</option>
              <option value="draft">Draft</option>
            </select>
          </div>
          <input type="hidden" id="hidden_data_id" data-id=""/>
          <div class="form-group">
            <label for="isFeatured">
              <input type="checkbox" id="isFeatured"> Featured Image
            </label>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-small close" style="background-color: #f1f5f9; color: var(--text-dark);">Cancel</button>
        <button class="btn btn-primary btn-small" id="saveEdit">Save Changes</button>
      </div>
    </div>
  </div>
  <script type="module" src="JS/Portfolio.js"></script>
  <script>

    // Form Submit
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
      e.preventDefault();
      alert('Content uploaded successfully!');
    });

    // Drag and Drop
    const uploadZone = document.querySelector('.upload-zone');
    
    uploadZone.addEventListener('dragover', (e) => {
      e.preventDefault();
      uploadZone.style.borderColor = 'var(--primary-green)';
      uploadZone.style.backgroundColor = 'rgba(26, 77, 46, 0.05)';
    });
    
    uploadZone.addEventListener('dragleave', () => {
      uploadZone.style.borderColor = '#cbd5e1';
      uploadZone.style.backgroundColor = '#f8fafc';
    });
    
    uploadZone.addEventListener('drop', (e) => {
      e.preventDefault();
      uploadZone.style.borderColor = '#cbd5e1';
      uploadZone.style.backgroundColor = '#f8fafc';
      
      const files = e.dataTransfer.files;
      const preview = document.getElementById('uploadPreview');
      
      Array.from(files).forEach(file => {
        const reader = new FileReader();
        reader.onload = function(e) {
          const div = document.createElement('div');
          div.className = 'upload-preview-item';
          div.innerHTML = `
            <img src="${e.target.result}" alt="Preview">
            <button class="upload-preview-remove" onclick="this.parentElement.remove()">
              <i class="fas fa-times"></i>
            </button>
          `;
          preview.appendChild(div);
        };
        reader.readAsDataURL(file);
      });
    });

    // Close modals when clicking outside
    document.querySelectorAll('.modal-overlay').forEach(modal => {
      modal.addEventListener('click', function(e) {
        if (e.target === this) {
          this.style.display = 'none';
        }
      });
    });

    // Close modals when clicking close buttons
    document.querySelectorAll('.close').forEach(closeBtn => {
      closeBtn.addEventListener('click', function(e) {
        e.preventDefault();
        // Find the closest modal overlay and close it
        const modalOverlay = this.closest('.modal-overlay');
        if (modalOverlay) {
          modalOverlay.style.display = 'none';
        }
      });
    });
  </script>
</body>
</html>
