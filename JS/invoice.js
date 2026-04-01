class InvoiceManager {
    constructor() {
        this.apiBase = '/landscape/USER_API/invoices.php';
    }

    /**
     * Create invoice from booking
     */
    async createFromBooking(bookingId) {
        try {
            const response = await fetch(`${this.apiBase}?action=create_from_booking`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    booking_id: bookingId
                })
            });

            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.message || 'Failed to create invoice');
            }

            return result;
        } catch (error) {
            console.error('Create invoice error:', error);
            throw error;
        }
    }

    /**
     * Get invoice details with transaction history
     */
    async getInvoice(invoiceId) {
        try {
            const response = await fetch(`${this.apiBase}?action=get`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: invoiceId
                })
            });

            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.message || 'Failed to get invoice');
            }

            return result;
        } catch (error) {
            console.error('Get invoice error:', error);
            throw error;
        }
    }

    /**
     * Get all invoices for a booking
     */
    async getBookingInvoices(bookingId) {
        try {
            const response = await fetch(`${this.apiBase}?action=booking_invoices`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    booking_id: bookingId
                })
            });

            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.message || 'Failed to get booking invoices');
            }

            return result;
        } catch (error) {
            console.error('Get booking invoices error:', error);
            throw error;
        }
    }

    /**
     * Update invoice status
     */
    async updateStatus(invoiceId, status) {
        try {
            const response = await fetch(`${this.apiBase}?action=update_status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    invoice_id: invoiceId,
                    status: status
                })
            });

            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.message || 'Failed to update invoice status');
            }

            return result;
        } catch (error) {
            console.error('Update invoice status error:', error);
            throw error;
        }
    }

    /**
     * List all invoices with pagination
     */
    async listInvoices(page = 1, status = 'all', userId = null) {
        try {
            const params = new URLSearchParams({
                action: 'list',
                page: page,
                status: status
            });

            if (userId) {
                params.append('user_id', userId);
            }

            const response = await fetch(`${this.apiBase}?${params.toString()}`);
            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.message || 'Failed to list invoices');
            }

            return result;
        } catch (error) {
            console.error('List invoices error:', error);
            throw error;
        }
    }

    /**
     * Display invoice details in a modal
     */
    async showInvoiceDetails(invoiceId) {
        try {
            const result = await this.getInvoice(invoiceId);
            const invoice = result.invoice;
            const transactions = result.transactions;

            // Create modal HTML
            const modalHtml = `
                <div class="modal-overlay" id="invoiceModal" style="display: flex;">
                    <div class="modal modal-large">
                        <div class="modal-header">
                            <h3><i class="fas fa-file-invoice" style="color: var(--primary-green);"></i> Invoice Details</h3>
                            <button class="modal-close" onclick="closeInvoiceModal()">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="invoice-header">
                                <div class="invoice-info">
                                    <h2>${invoice.invoice_number}</h2>
                                    <p><strong>Status:</strong> <span class="status-badge ${invoice.status.toLowerCase()}">${invoice.status}</span></p>
                                    <p><strong>Invoice Date:</strong> ${new Date(invoice.invoice_date).toLocaleDateString()}</p>
                                    <p><strong>Due Date:</strong> ${new Date(invoice.due_date).toLocaleDateString()}</p>
                                </div>
                                <div class="invoice-amounts">
                                    <p><strong>Subtotal:</strong> $${parseFloat(invoice.subtotal).toFixed(2)}</p>
                                    <p><strong>Tax (12%):</strong> $${parseFloat(invoice.tax_amount).toFixed(2)}</p>
                                    <p><strong>Total:</strong> $${parseFloat(invoice.total_amount).toFixed(2)}</p>
                                </div>
                            </div>
                            
                            <div class="invoice-customer">
                                <h4>Customer Information</h4>
                                <p><strong>Name:</strong> ${invoice.customer_name}</p>
                                <p><strong>Email:</strong> ${invoice.customer_email}</p>
                                <p><strong>Service:</strong> ${invoice.service_name}</p>
                                <p><strong>Booking Date:</strong> ${new Date(invoice.booking_date).toLocaleDateString()}</p>
                                <p><strong>Address:</strong> ${invoice.address}</p>
                            </div>
                            
                            <div class="invoice-transactions">
                                <h4>Transaction History</h4>
                                <div class="transaction-list">
                                    ${transactions.map(transaction => `
                                        <div class="transaction-item">
                                            <div class="transaction-info">
                                                <p><strong>${transaction.transaction_code}</strong></p>
                                                <p>${transaction.description}</p>
                                                <p><small>${new Date(transaction.transaction_date).toLocaleDateString()}</small></p>
                                            </div>
                                            <div class="transaction-amount">
                                                <p><strong>$${parseFloat(transaction.amount).toFixed(2)}</strong></p>
                                                <span class="status-badge ${transaction.status.toLowerCase()}">${transaction.status}</span>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                            
                            ${invoice.notes ? `
                                <div class="invoice-notes">
                                    <h4>Notes</h4>
                                    <p>${invoice.notes}</p>
                                </div>
                            ` : ''}
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-small" style="background-color: #f1f5f9; color: var(--text-dark);" onclick="closeInvoiceModal()">Close</button>
                            ${invoice.status === 'draft' ? `
                                <button class="btn btn-secondary btn-small" onclick="invoiceManager.updateStatus(${invoice.id}, 'sent').then(() => location.reload())">
                                    <i class="fas fa-paper-plane"></i> Mark as Sent
                                </button>
                            ` : ''}
                            ${invoice.status === 'sent' ? `
                                <button class="btn btn-primary btn-small" onclick="invoiceManager.updateStatus(${invoice.id}, 'paid').then(() => location.reload())">
                                    <i class="fas fa-check"></i> Mark as Paid
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;

            // Add modal to page
            const modalContainer = document.createElement('div');
            modalContainer.innerHTML = modalHtml;
            document.body.appendChild(modalContainer.firstElementChild);

        } catch (error) {
            console.error('Show invoice details error:', error);
            alert('Failed to load invoice details: ' + error.message);
        }
    }

    /**
     * Generate invoice PDF (using existing booking report)
     */
    async generateInvoicePDF(invoiceId) {
        try {
            const result = await this.getInvoice(invoiceId);
            const invoice = result.invoice;
            
            // Use existing booking report generator
            const pdfUrl = `/landscape/USER_API/generate_booking_report.php?id=${invoice.booking_id}`;
            window.open(pdfUrl, '_blank');
        } catch (error) {
            console.error('Generate invoice PDF error:', error);
            alert('Failed to generate invoice PDF: ' + error.message);
        }
    }
}

// Global instance
const invoiceManager = new InvoiceManager();

// Helper function to close modal
function closeInvoiceModal() {
    const modal = document.getElementById('invoiceModal');
    if (modal) {
        modal.remove();
    }
}

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = InvoiceManager;
}
