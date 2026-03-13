/**
 * GreenScape Admin - Dashboard Logic
 * Handles fetching statistics, recent users, activity, and transactions.
 */

document.addEventListener('DOMContentLoaded', () => {
    fetchDashboardStats();
    fetchRecentUsers();
    fetchRecentActivity();
    fetchRecentTransactions();

    // Handle Export Report
    const exportBtn = document.querySelector('.admin-page-title .btn-primary');
    if (exportBtn) {
        exportBtn.addEventListener('click', () => {
            exportReport();
        });
    }
});

/**
 * Fetches overall statistics for the dashboard
 */
async function fetchDashboardStats() {
    try {
        const response = await fetch('/api/admin/stats.php');
        if (!response.ok) throw new Error('Failed to fetch stats');
        
        const data = await response.json();
        
        // Update UI elements (assuming IDs or specific selectors)
        // For this demo, we'll map them to the existing structure
        const statsContainers = document.querySelectorAll('.stat-card h3');
        if (statsContainers.length >= 4) {
            statsContainers[0].textContent = data.totalUsers.toLocaleString();
            statsContainers[1].textContent = `$${data.revenue.toLocaleString()}`;
            statsContainers[2].textContent = data.activeBookings.toString();
            statsContainers[3].textContent = data.pendingRequests.toString();
        }
    } catch (error) {
        console.error('Error fetching stats:', error);
    }
}

/**
 * Fetches recent users and populates the table
 */
async function fetchRecentUsers() {
    try {
        const response = await fetch('/api/admin/users.php?limit=4');
        if (!response.ok) throw new Error('Failed to fetch users');
        
        const users = await response.json();
        const tbody = document.querySelector('.dashboard-card:nth-child(1) .data-table tbody');
        
        if (tbody && users.length > 0) {
            tbody.innerHTML = users.map(user => `
                <tr>
                    <td>
                        <div class="table-user">
                            <img src="${user.avatar || 'https://via.placeholder.com/100'}" alt="${user.name}">
                            <div class="table-user-info">
                                <h4>${user.name}</h4>
                                <p>${user.email}</p>
                            </div>
                        </div>
                    </td>
                    <td>${user.joinedDate}</td>
                    <td><span class="status-badge ${user.status.toLowerCase()}">${user.status}</span></td>
                </tr>
            `).join('');
        }
    } catch (error) {
        console.error('Error fetching users:', error);
    }
}

/**
 * Fetches recent activity for the timeline
 */
async function fetchRecentActivity() {
    try {
        const response = await fetch('/api/admin/activity.php');
        if (!response.ok) throw new Error('Failed to fetch activity');
        
        const activities = await response.json();
        const timeline = document.querySelector('.timeline');
        
        if (timeline && activities.length > 0) {
            timeline.innerHTML = activities.map(activity => `
                <div class="timeline-item">
                    <div class="timeline-content">
                        <h4>${activity.title}</h4>
                        <p>${activity.description}</p>
                        <p class="timeline-time">${activity.timeAgo}</p>
                    </div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Error fetching activity:', error);
    }
}

/**
 * Fetches recent transactions
 */
async function fetchRecentTransactions() {
    try {
        const response = await fetch('/api/admin/transactions.php?limit=4');
        if (!response.ok) throw new Error('Failed to fetch transactions');
        
        const transactions = await response.json();
        const container = document.querySelector('.transaction-list');
        
        if (container && transactions.length > 0) {
            container.innerHTML = transactions.map(tx => `
                <div class="transaction-item">
                    <div class="transaction-icon ${tx.type === 'income' ? 'income' : 'expense'}">
                        <i class="fas ${tx.type === 'income' ? 'fa-arrow-down' : 'fa-arrow-up'}"></i>
                    </div>
                    <div class="transaction-info">
                        <h4>${tx.title}</h4>
                        <p>${tx.category}</p>
                    </div>
                    <div class="transaction-amount ${tx.type === 'income' ? 'positive' : 'negative'}">
                        <h4>${tx.type === 'income' ? '+' : '-'}$${tx.amount.toLocaleString()}</h4>
                        <p>${tx.date}</p>
                    </div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Error fetching transactions:', error);
    }
}

/**
 * Triggers a report export
 */
async function exportReport() {
    try {
        const response = await fetch('/api/admin/export.php', { method: 'POST' });
        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `report_${new Date().toISOString().split('T')[0]}.pdf`;
            document.body.appendChild(a);
            a.click();
            a.remove();
        } else {
            alert('Failed to export report');
        }
    } catch (error) {
        console.error('Export error:', error);
        alert('An error occurred during export.');
    }
}
