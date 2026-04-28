/**
 * GreenScape Modal System
 * A comprehensive modal and notification system for GreenScape Landscaping
 */

// Modal System
export const ModalSystem = {
    // Create modal overlay and container if not exists
    init() {
        if (!document.getElementById('modal-overlay')) {
            const overlay = document.createElement('div');
            overlay.id = 'modal-overlay';
            overlay.className = 'modal-overlay';
            overlay.innerHTML = '<div id="modal-container" class="modal"></div>';
            document.body.appendChild(overlay);

            // Close on overlay click
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    this.close();
                }
            });

            // Close on Escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.close();
                }
            });
        }

        // Create toast container if not exists
        if (!document.getElementById('toast-container')) {
            const toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.className = 'toast-container';
            document.body.appendChild(toastContainer);
        }
    },

    // Open modal with content
    open(content, size = '') {
        this.init();
        const overlay = document.getElementById('modal-overlay');
        const container = document.getElementById('modal-container');
        
        container.className = 'modal ' + size;
        container.innerHTML = content;
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    },

    // Close modal
    close() {
        const overlay = document.getElementById('modal-overlay');
        if (overlay) {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    },

    // Success Modal
    success(title, message, buttonText = 'OK', callback = null) {
        const content = `
            <div class="modal-header">
                <div class="modal-icon success"><i class="fas fa-check-circle"></i></div>
                <div class="modal-title">
                    <h3>${title}</h3>
                    <p>Success</p>
                </div>
                <button class="modal-close" onclick="ModalSystem.close()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <p>${message}</p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn modal-btn-success" onclick="ModalSystem.close(); ${callback ? callback + '()' : ''}">
                    <i class="fas fa-check"></i> ${buttonText}
                </button>
            </div>
        `;
        this.open(content);
    },

    // Error Modal
    error(title, message, buttonText = 'OK', callback = null) {
        const content = `
            <div class="modal-header">
                <div class="modal-icon error"><i class="fas fa-times-circle"></i></div>
                <div class="modal-title">
                    <h3>${title}</h3>
                    <p>Error</p>
                </div>
                <button class="modal-close" onclick="ModalSystem.close()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <p>${message}</p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn modal-btn-danger" onclick="ModalSystem.close(); ${callback ? callback + '()' : ''}">
                    <i class="fas fa-times"></i> ${buttonText}
                </button>
            </div>
        `;
        this.open(content);
    },

    // Warning Modal
    warning(title, message, buttonText = 'Understood', callback = null) {
        const content = `
            <div class="modal-header">
                <div class="modal-icon warning"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="modal-title">
                    <h3>${title}</h3>
                    <p>Warning</p>
                </div>
                <button class="modal-close" onclick="ModalSystem.close()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <p>${message}</p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn modal-btn-warning" onclick="ModalSystem.close(); ${callback ? callback + '()' : ''}">
                    <i class="fas fa-check"></i> ${buttonText}
                </button>
            </div>
        `;
        this.open(content);
    },

    // Info Modal
    info(title, message, buttonText = 'OK', callback = null) {
        const content = `
            <div class="modal-header">
                <div class="modal-icon info"><i class="fas fa-info-circle"></i></div>
                <div class="modal-title">
                    <h3>${title}</h3>
                    <p>Information</p>
                </div>
                <button class="modal-close" onclick="ModalSystem.close()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <p>${message}</p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn modal-btn-primary" onclick="ModalSystem.close(); ${callback ? callback + '()' : ''}">
                    <i class="fas fa-check"></i> ${buttonText}
                </button>
            </div>
        `;
        this.open(content);
    },

    // Confirmation Modal
    confirm(title, message, onConfirm, onCancel = null, confirmText = 'Yes', cancelText = 'No') {
        const content = `
            <div class="modal-header">
                <div class="modal-icon confirm"><i class="fas fa-question-circle"></i></div>
                <div class="modal-title">
                    <h3>${title}</h3>
                    <p>Please confirm</p>
                </div>
                <button class="modal-close" onclick="ModalSystem.close(); ${onCancel ? onCancel + '()' : ''}"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <p>${message}</p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn modal-btn-secondary" onclick="ModalSystem.close(); ${onCancel ? onCancel + '()' : ''}">
                    <i class="fas fa-times"></i> ${cancelText}
                </button>
                <button class="modal-btn modal-btn-primary" onclick="ModalSystem.close(); ${onConfirm}();">
                    <i class="fas fa-check"></i> ${confirmText}
                </button>
            </div>
        `;
        this.open(content);
    },

    // Delete Confirmation Modal
    confirmDelete(itemName, onConfirm, onCancel = null) {
        const content = `
            <div class="modal-header">
                <div class="modal-icon error"><i class="fas fa-trash-alt"></i></div>
                <div class="modal-title">
                    <h3>Delete Confirmation</h3>
                    <p>This action cannot be undone</p>
                </div>
                <button class="modal-close" onclick="ModalSystem.close(); ${onCancel ? onCancel + '()' : ''}"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong>${itemName}</strong>?</p>
                <p style="color: var(--danger-red); margin-top: 0.5rem;"><i class="fas fa-exclamation-circle"></i> This action is permanent and cannot be recovered.</p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn modal-btn-secondary" onclick="ModalSystem.close(); ${onCancel ? onCancel + '()' : ''}">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="modal-btn modal-btn-danger" onclick="ModalSystem.close(); ${onConfirm}();">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            </div>
        `;
        this.open(content);
    },

    // Logout Confirmation Modal
    confirmLogout(onConfirm, onCancel = null) {
        const content = `
            <div class="modal-header">
                <div class="modal-icon warning"><i class="fas fa-sign-out-alt"></i></div>
                <div class="modal-title">
                    <h3>Logout Confirmation</h3>
                    <p>Are you leaving?</p>
                </div>
                <button class="modal-close" onclick="ModalSystem.close(); ${onCancel ? onCancel + '()' : ''}"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to log out of your account?</p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn modal-btn-secondary" onclick="ModalSystem.close(); ${onCancel ? onCancel + '()' : ''}">
                    <i class="fas fa-times"></i> Stay Logged In
                </button>
                <button class="modal-btn modal-btn-danger" onclick="ModalSystem.close(); ${onConfirm}();">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        `;
        this.open(content);
    },

    // Form Modal
    form(title, formContent, onSubmit, submitText = 'Submit', cancelText = 'Cancel') {
        const content = `
            <div class="modal-header">
                <div class="modal-icon info"><i class="fas fa-edit"></i></div>
                <div class="modal-title">
                    <h3>${title}</h3>
                    <p>Please fill in the details</p>
                </div>
                <button class="modal-close" onclick="ModalSystem.close()"><i class="fas fa-times"></i></button>
            </div>
            <form onsubmit="event.preventDefault(); ModalSystem.close(); ${onSubmit}(this);">
                <div class="modal-body">
                    ${formContent}
                </div>
                <div class="modal-footer">
                    <button type="button" class="modal-btn modal-btn-secondary" onclick="ModalSystem.close()">
                        <i class="fas fa-times"></i> ${cancelText}
                    </button>
                    <button type="submit" class="modal-btn modal-btn-primary">
                        <i class="fas fa-check"></i> ${submitText}
                    </button>
                </div>
            </form>
        `;
        this.open(content, 'modal-medium');
    },

    // Custom Modal with full HTML content
    custom(htmlContent, size = '') {
        this.open(htmlContent, size);
    }
};

// Toast Notification System
export const ToastSystem = {
    // Create toast container
    init() {
        if (!document.getElementById('toast-container')) {
            const toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.className = 'toast-container';
            document.body.appendChild(toastContainer);
        }
    },

    // Show toast notification
    show(message, type = 'info', title = '', duration = 5000) {
        this.init();
        const container = document.getElementById('toast-container');
        
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-times-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        const titles = {
            success: title || 'Success',
            error: title || 'Error',
            warning: title || 'Warning',
            info: title || 'Information'
        };

        const toastId = 'toast-' + Date.now();
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <div class="toast-icon"><i class="fas ${icons[type]}"></i></div>
            <div class="toast-content">
                <h4>${titles[type]}</h4>
                <p>${message}</p>
            </div>
            <button class="toast-close" onclick="ToastSystem.dismiss('${toastId}')"><i class="fas fa-times"></i></button>
            <div class="toast-progress" style="width: 100%;"></div>
        `;

        container.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => {
            toast.classList.add('show');
        });

        // Progress bar animation
        const progress = toast.querySelector('.toast-progress');
        progress.style.transition = `width ${duration}ms linear`;
        requestAnimationFrame(() => {
            progress.style.width = '0%';
        });

        // Auto dismiss
        setTimeout(() => {
            this.dismiss(toastId);
        }, duration);

        return toastId;
    },

    // Success toast
    success(message, title = '', duration = 5000) {
        return this.show(message, 'success', title, duration);
    },

    // Error toast
    error(message, title = '', duration = 5000) {
        return this.show(message, 'error', title, duration);
    },

    // Warning toast
    warning(message, title = '', duration = 5000) {
        return this.show(message, 'warning', title, duration);
    },

    // Info toast
    info(message, title = '', duration = 5000) {
        return this.show(message, 'info', title, duration);
    },

    // Dismiss toast
    dismiss(toastId) {
        const toast = document.getElementById(toastId);
        if (toast) {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 400);
        }
    },

    // Dismiss all toasts
    dismissAll() {
        const toasts = document.querySelectorAll('.toast');
        toasts.forEach(toast => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 400);
        });
    }
};

// // Auto-initialize on DOM ready
// document.addEventListener('DOMContentLoaded', () => {
//     ModalSystem.init();
//     ToastSystem.init();
// });

// // Handle PHP session messages
// function handleSessionMessages() {
//     // Check for success message in URL
//     const urlParams = new URLSearchParams(window.location.search);
//     const successMsg = urlParams.get('success');
//     const errorMsg = urlParams.get('error');
//     const warningMsg = urlParams.get('warning');
//     const infoMsg = urlParams.get('info');

//     if (successMsg) {
//         ToastSystem.success(decodeURIComponent(successMsg), 'Success', 6000);
//     }
//     if (errorMsg) {
//         ToastSystem.error(decodeURIComponent(errorMsg), 'Error', 8000);
//     }
//     if (warningMsg) {
//         ToastSystem.warning(decodeURIComponent(warningMsg), 'Warning', 7000);
//     }
//     if (infoMsg) {
//         ToastSystem.info(decodeURIComponent(infoMsg), 'Information', 5000);
//     }
// }

// Run on page load
//document.addEventListener('DOMContentLoaded', handleSessionMessages);
