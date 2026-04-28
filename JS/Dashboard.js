import { toggleModal, filesizeComputation, generateCsrfToken, emptyElement, clearElementError, moneySign, switchTab, putTextinElementById, buttonEventListener, renderPagination, capitalize, log } from './utils/utils.js'

// Dashboard state and configuration
const state = {
    refreshInterval: 30000, // 30 seconds auto-refresh
};

const listData = {
    data: [
        [1, 2, 3, 4, 5],     // Day/Month index
      [120, 185, 150, 250, 210]
    ],
    labelY: "Price",
    labelX: "Weeks"
}
let csrfToken = "";
const controllerPath = "/landscape/USER_API/DashboardController.php";
let dashboardData;

// Main dashboard data fetching function
const fetchDashboardStats = async () => {
    try {
        // Generate CSRF token if not available
        if (!csrfToken) {
            await generateCsrfToken();
            csrfToken = document.querySelector('input[name="csrf_token"]')?.value;
        }
        console.log("scrf: ", csrfToken)
        const response = await fetch(`${controllerPath}?action=stats`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log('Dashboard data:', data);

        if (data.status === 'success' && data.data) {
            dashboardData = data.data;
            updateDashboardUI(dashboardData);
            return dashboardData;
        } else {
            console.error('API error:', data.message);
            return null;
        }
    } catch (error) {
        console.error('Failed to fetch dashboard stats:', error);
        return null;
    }
};

// Update UI with dashboard data
const updateDashboardUI = (data) => {
    try {
        // Update total users
        const totalUsersElement = document.getElementById('total-user');
        if (totalUsersElement && data.total_users !== undefined) {
            putTextinElementById('#total-user', data.total_users, 'textContent');
        }

        // Update monthly revenue
        const monthlyRevenueElement = document.getElementById('monthly-revenue');
        if (monthlyRevenueElement && data.monthly_revenue !== undefined) {
            putTextinElementById('#monthly-revenue', moneySign + data.monthly_revenue, 'textContent');
        }

        // Update active bookings
        const activeBookingsElement = document.getElementById('active-bookings');
        if (activeBookingsElement && data.active_bookings !== undefined) {
            putTextinElementById('#active-bookings', data.active_bookings, 'textContent');
        }

        // Update pending bookings
        const pendingBookingsElement = document.getElementById('pending-bookings');
        if (pendingBookingsElement && data.pending_bookings !== undefined) {
            putTextinElementById('#pending-bookings', data.pending_bookings, 'textContent');
        }

        // Update recent users table
        updateRecentUsersTable(data.recent_users || []);

        // Update recent transactions table
        updateRecentTransactionsTable(data.recent_transactions || []);

        // Update recent portfolios
        updateRecentPortfolios(data.recent_portfolios || []);

        // Update recent services (admin only)
        if (data.recent_services) {
            updateRecentServices(data.recent_services);
        }

    } catch (error) {
        console.error('Error updating dashboard UI:', error);
    }
};

// Update recent users table
const updateRecentUsersTable = (users) => {
    const tableBody = document.getElementById('recent-user-tbody');
    if (!tableBody) return;

    if (users.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="4" class="text-center">No recent users found</td></tr>';
        return;
    }
    console.log("USers: ",users)
    const limitedData = users.slice(0, 5); // Limit to 5
    const rows = limitedData.map(user => `
        <tr>
            <td>
                <div class="table-user">
                <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100" alt="User">
                <div class="table-user-info">
                    <h4>${user.name}</h4>
                    <p>${user.email}</p>
                </div>
                </div>
            </td>
            <td>${new Date(user.joined_date).toLocaleDateString()}</td>
            <td><span class="status-badge ${user.status === 'active' ? 'active' : '" style="background-color: rgb(200 34 34 / 10%); color: red;"'}">${user.status}</span></td>
        </tr>
    `).join('');
    putTextinElementById('#recent-user-tbody', rows, 'innerHTML');
};

// Update recent transactions table
const updateRecentTransactionsTable = (transactions) => {
    const tableBody = document.getElementById('recent-transactions-body');
    if (!tableBody) return;

    if (transactions.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="5" class="text-center">No recent transactions found</td></tr>';
        return;
    }
    const limitedData = transactions.slice(0, 5); // Limit to 5
    const rows = limitedData.map(transaction => `
        <div class="transaction-item">
            <div class="transaction-icon income">
                <i class="fas fa-arrow-down"></i>
            </div>
            <div class="transaction-info">
                <h4>Payment for Booking #${transaction.booking_id} ${transaction.user_name || 'Unknown'} ${transaction.user_email ? "("+transaction.user_email+")" : ""}</h4>
                <p>Status: ${capitalize(transaction.status)}</p>
            </div>
            <div class="transaction-amount positive">
                <h4>${moneySign + transaction.amount}</h4>
                <p>${new Date(transaction.transaction_date).toLocaleDateString()}</p>
            </div>
        </div>
    `).join('');
    putTextinElementById('#recent-transactions-body', rows, 'innerHTML');
};

// Update recent portfolios
const updateRecentPortfolios = (portfolios) => {
    const container = document.getElementById('recent-portfolios');
    if (!container) return;

    if (portfolios.length === 0) {
        container.innerHTML = '<p class="text-center">No recent portfolios found</p>';
        return;
    }

    const portfolioItems = portfolios.map(portfolio => `
        <div class="portfolio-item">
            <h4>${portfolio.title}</h4>
            <p><span class="status-badge ${portfolio.status === 'LIVE' ? 'success' : 'draft'}">${capitalize(portfolio.status)}</span></p>
            <small>${new Date(portfolio.created_at).toLocaleDateString()}</small>
        </div>
    `).join('');

    putTextinElementById('#recent-portfolios', portfolioItems, 'innerHTML');
};

// Update recent services
const updateRecentServices = (services) => {
    const container = document.getElementById('recent-services');
    if (!container) return;

    if (services.length === 0) {
        container.innerHTML = '<p class="text-center">No recent services found</p>';
        return;
    }

    const serviceItems = services.map(service => `
        <div class="service-item">
            <h4>${service.service_name}</h4>
            <small>${new Date(service.created_at).toLocaleDateString()}</small>
        </div>
    `).join('');

    putTextinElementById('#recent-services', serviceItems, 'innerHTML');
};

// Auto-refresh functionality
let refreshInterval;

const startAutoRefresh = () => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
    
    refreshInterval = setInterval(() => {
        console.log('Auto-refreshing dashboard...');
        fetchDashboardStats();
    }, state.refreshInterval);
};

const stopAutoRefresh = () => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
};

// Manual refresh button
const setupRefreshButton = () => {
    const refreshBtn = document.getElementById('refresh-dashboard');
    if (refreshBtn) {
        buttonEventListener(refreshBtn, async (e, element) => {
            e.target.disabled = true;
            e.target.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
            
            try {
                await fetchDashboardStats();
                e.target.innerHTML = '<i class="fas fa-sync"></i> Refresh';
            } catch (error) {
                console.error('Manual refresh failed:', error);
                e.target.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Refresh Failed';
                setTimeout(() => {
                    e.target.innerHTML = '<i class="fas fa-sync"></i> Refresh';
                }, 2000);
            } finally {
                e.target.disabled = false;
            }
        });
    }
};

// Initialize dashboard
const initDashboard = async () => {
    try {
        console.log('Initializing dashboard...');
        
        // Generate CSRF token
        await generateCsrfToken();
        csrfToken = document.querySelector('input[name="csrf_token"]')?.value;
        
        // Fetch initial data
        await fetchDashboardStats();
        
        // Setup refresh button
        setupRefreshButton();
        
        // Start auto-refresh
        startAutoRefresh();
        chartings()
        console.log('Dashboard initialized successfully');
        
    } catch (error) {
        console.error('Failed to initialize dashboard:', error);
        
        // Show error message
        const errorElement = document.getElementById('dashboard-error');
        if (errorElement) {
            errorElement.style.display = 'block';
            errorElement.textContent = 'Failed to load dashboard data. Please refresh the page.';
        }
    }
};

// Clean up on page unload
window.addEventListener('beforeunload', () => {
    stopAutoRefresh();
});

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', initDashboard);

// Export functions for external use if needed
export {
    fetchDashboardStats,
    updateDashboardUI,
    startAutoRefresh,
    stopAutoRefresh
};

const chartings = () =>{
    buttonEventListener("#charts-filter", (el, element)=>{
        console.log(element.value)
    }, "change")
    const container = document.getElementById("chart-wrapper");
    if (!container) return;

    const data = listData.data

    const opts = {
      title: "Revenue Overview",
      width: container.offsetWidth || 800,
      height: container.offsetHeight || 400,
      series: [
        {}, // X Series
        {
          label: "Price",
          stroke: "#2ecc71", // Green border
          fill: "#2ecc7133",  // Light green transparent fill
          // paths: uPlot.paths.bars({
          //   size: [0.6, 100], // Slightly slimmer bars for a cleaner look
          //   align: 0
          // }),
        }
      ],
      axes: [
        { 
          label: listData.labelX, 
          grid: {show: false} // Optional: cleaner look for X axis
        },
        { 
          label: listData.labelY,
          // Formats the numbers on the axis to include '$'
          values: (self, ticks) => ticks.map(v => moneySign + v.toLocaleString())
        }
      ]
    };

    let plot = new uPlot(opts, data, container);

    window.addEventListener("resize", () => {
        plot.setSize({
            width: container.offsetWidth,
            height: container.offsetHeight,
        });
    });
}