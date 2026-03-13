/**
 * GreenScape Admin - Gallery Manager Logic
 * Handles fetching, editing, and deleting gallery items via PHP API.
 */

document.addEventListener('DOMContentLoaded', () => {
    fetchGalleryItems();

    // Handle Edit Form Submission
    const editForm = document.getElementById('editImageForm');
    if (editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            await saveImageChanges();
        });
    }

    // Close modals when clicking outside
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    });
});

/**
 * Fetches gallery items from the API
 * @param {string} filter - 'all', 'published', 'drafts', 'featured'
 */
async function fetchGalleryItems(filter = 'all') {
    try {
        const response = await fetch(`/api/admin/gallery.php?filter=${filter}`);
        if (!response.ok) throw new Error('Failed to fetch gallery');
        
        const items = await response.json();
        renderGallery(items);
    } catch (error) {
        console.error('Error fetching gallery:', error);
        // Fallback or error message in UI
    }
}

/**
 * Renders gallery items into the grid
 * @param {Array} items 
 */
function renderGallery(items) {
    const grid = document.querySelector('.gallery-grid-admin');
    if (!grid) return;

    if (items.length === 0) {
        grid.innerHTML = '<div class="empty-state"><i class="fas fa-images"></i><h3>No images found</h3></div>';
        return;
    }

    grid.innerHTML = items.map(item => `
        <div class="gallery-item-admin" data-id="${item.id}">
            <img src="${item.url}" alt="${item.title}">
            <div class="gallery-item-overlay">
                <button class="table-btn view" title="View" onclick="viewImage(${JSON.stringify(item).replace(/"/g, '&quot;')})"><i class="fas fa-eye"></i></button>
                <button class="table-btn edit" title="Edit" onclick="editImage(${JSON.stringify(item).replace(/"/g, '&quot;')})"><i class="fas fa-edit"></i></button>
                <button class="table-btn delete" title="Delete" onclick="deleteImage(${item.id}, '${item.title}')"><i class="fas fa-trash"></i></button>
            </div>
            <div class="gallery-item-info">
                <h5>${item.title}</h5>
                <p>${item.category} • ${item.date}</p>
            </div>
            <div style="position: absolute; top: 0.5rem; left: 0.5rem;">
                <input type="checkbox" class="checkbox" value="${item.id}" style="width: 20px; height: 20px;">
            </div>
            <div style="position: absolute; top: 0.5rem; right: 0.5rem;">
                <span class="status-badge ${item.status.toLowerCase()}" style="font-size: 0.65rem;">${item.status}</span>
            </div>
        </div>
    `).join('');
}

/**
 * Opens the view modal with item details
 * @param {Object} item 
 */
function viewImage(item) {
    document.getElementById('previewImage').src = item.url;
    document.getElementById('previewTitle').textContent = item.title;
    document.getElementById('previewCategory').textContent = item.category;
    document.getElementById('previewDate').textContent = item.date;
    document.getElementById('viewImageModal').style.display = 'flex';
}

function closeViewImageModal() {
    document.getElementById('viewImageModal').style.display = 'none';
}

/**
 * Opens the edit modal with item data
 * @param {Object} item 
 */
let currentEditingId = null;
function editImage(item) {
    currentEditingId = item.id;
    document.getElementById('editImageTitle').value = item.title;
    document.getElementById('editImageDescription').value = item.description || '';
    document.getElementById('editImageCategory').value = item.category.toLowerCase();
    document.getElementById('editImageStatus').value = item.status.toLowerCase();
    document.getElementById('editImageModal').style.display = 'flex';
}

function closeEditImageModal() {
    document.getElementById('editImageModal').style.display = 'none';
    currentEditingId = null;
}

/**
 * Saves changes to an image via API
 */
async function saveImageChanges() {
    const title = document.getElementById('editImageTitle').value;
    const description = document.getElementById('editImageDescription').value;
    const category = document.getElementById('editImageCategory').value;
    const status = document.getElementById('editImageStatus').value;

    try {
        const response = await fetch(`/api/admin/gallery.php?id=${currentEditingId}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ title, description, category, status })
        });

        const result = await response.json();
        if (response.ok && result.success) {
            alert('Image details updated successfully!');
            closeEditImageModal();
            fetchGalleryItems(); // Refresh list
        } else {
            alert('Update failed: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Update error:', error);
        alert('An error occurred while saving changes.');
    }
}

/**
 * Deletes an image via API
 * @param {number} id 
 * @param {string} title 
 */
async function deleteImage(id, title) {
    if (!confirm(`Are you sure you want to delete "${title}"?`)) return;

    try {
        const response = await fetch(`/api/admin/gallery.php?id=${id}`, {
            method: 'DELETE'
        });

        const result = await response.json();
        if (response.ok && result.success) {
            alert('Image deleted successfully!');
            fetchGalleryItems(); // Refresh list
        } else {
            alert('Delete failed: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Delete error:', error);
        alert('An error occurred while deleting.');
    }
}

/**
 * Deletes multiple selected images
 */
async function deleteSelected() {
    const checked = document.querySelectorAll('.gallery-item-admin input[type="checkbox"]:checked');
    if (checked.length === 0) {
        alert('Please select at least one image to delete.');
        return;
    }

    if (!confirm(`Are you sure you want to delete ${checked.length} selected images?`)) return;

    const ids = Array.from(checked).map(cb => cb.value);

    try {
        const response = await fetch('/api/admin/gallery.php?bulk=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids })
        });

        const result = await response.json();
        if (response.ok && result.success) {
            alert('Selected images deleted successfully!');
            fetchGalleryItems();
        } else {
            alert('Bulk delete failed.');
        }
    } catch (error) {
        console.error('Bulk delete error:', error);
    }
}

/**
 * Switches between gallery tabs
 * @param {string} tab 
 */
function switchTab(tab) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    event.target.classList.add('active');
    fetchGalleryItems(tab);
}
