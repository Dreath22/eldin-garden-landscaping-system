/**
 * GreenScape Admin - Upload Logic
 * Handles file selection, preview, and form submission to PHP API.
 */

document.addEventListener('DOMContentLoaded', () => {
    const uploadForm = document.getElementById('uploadForm');
    const fileInput = document.getElementById('fileInput');
    const uploadZone = document.querySelector('.upload-zone');
    const uploadPreview = document.getElementById('uploadPreview');

    // File Input Change
    if (fileInput) {
        fileInput.addEventListener('change', (e) => {
            handleFiles(e.target.files);
        });
    }

    // Drag and Drop
    if (uploadZone) {
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
            handleFiles(files);
        });
    }

    // Form Submission
    if (uploadForm) {
        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            await submitUploadForm(uploadForm);
        });
    }
});

/**
 * Handles file selection and generates previews
 * @param {FileList} files 
 */
function handleFiles(files) {
    const preview = document.getElementById('uploadPreview');
    // preview.innerHTML = ''; // Uncomment if you want to clear previous previews

    Array.from(files).forEach(file => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const div = document.createElement('div');
            div.className = 'upload-preview-item';
            
            // Handle different file types for preview
            if (file.type.startsWith('image/')) {
                div.innerHTML = `
                    <img src="${e.target.result}" alt="Preview">
                    <button type="button" class="upload-preview-remove" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                `;
            } else if (file.type === 'application/pdf') {
                div.innerHTML = `
                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #f1f5f9;">
                        <i class="fas fa-file-pdf" style="font-size: 2rem; color: #ef4444;"></i>
                    </div>
                    <button type="button" class="upload-preview-remove" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                `;
            } else if (file.type.startsWith('video/')) {
                div.innerHTML = `
                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #f1f5f9;">
                        <i class="fas fa-video" style="font-size: 2rem; color: var(--primary-green);"></i>
                    </div>
                    <button type="button" class="upload-preview-remove" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                `;
            }
            
            preview.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}

/**
 * Submits the upload form to the PHP API
 * @param {HTMLFormElement} form 
 */
async function submitUploadForm(form) {
    const formData = new FormData(form);
    
    // Add files from the input (or from a stored array if you implement removal logic properly)
    const fileInput = document.getElementById('fileInput');
    if (fileInput.files.length === 0) {
        alert('Please select at least one file to upload.');
        return;
    }

    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

    try {
        const response = await fetch('/api/admin/upload.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (response.ok && result.success) {
            alert('Content uploaded successfully!');
            form.reset();
            document.getElementById('uploadPreview').innerHTML = '';
        } else {
            alert('Upload failed: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Upload error:', error);
        alert('An error occurred while uploading. Please try again.');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}
