const state = {
        currentPage: 1,
        status: "all",
        category: "all",
        transactionType: 'all',
        order: "newest",
        dateFrom: "",
        dateTo: "",
        allTransactions: [], // Stores the current page of users
        limit: 6,
        total_pages: 1,
        selectAllActive: false,
};

async function fetchData(page = state.currentPage) {
        state.currentPage = page;

        // Use URLSearchParams for clean URL building
        const params = new URLSearchParams({
          page: state.currentPage,
          status: state.status,
          order: state.order,
          from: state.dateFrom,
          to: state.dateTo,
          category: state.category,
          transactionType: state.transactionType
        });

        const apiURL = `/landscape/USER_API/transactions.php?${params.toString()}`;

        try {
          const response = await fetch(apiURL);
          if (!response.ok)
            throw new Error(`HTTP error! status: ${response.status}`);

          const data = await response.json();
          console.log("Fetched data:", data);   

        //   // Update State & UI
        //   state.allBookings = data.bookings;
        //   displayStats(data.summary);
        //   displayBookings(data.bookings);
         dataSummaryload(data)

        //   renderPagination(data.summary.filtered, state, state.limit);
        } catch (error) {
          console.error("Fetch error:", error);
        }
      }

      function dataSummaryload(data){
        updateStatCard("monthRevenue", "monthRevenueColor", `$${parseFloat(data.revenue_this_month || 0).toLocaleString()}`, data.revenue_growth);
        updateStatCard("totalExpenses", "totalExpensesColor", `$${parseFloat(data.expenses_this_month || 0).toLocaleString()}`, data.expense_growth);
        updateStatCard("lastMonthProfit", "lastMonthProfitColor", `$${parseFloat(data.net_profit_this_month || 0).toLocaleString()}`, data.profit_growth);
        updateStatCard("totalTransactions", "totalTransactionsColor", `${data.transactions_this_month}`, data.transactions_growth);
      }

      /**
 * Updates a dashboard stat card with value and growth indicators
 * @param {string} valueId - The ID of the element displaying the main amount
 * @param {string} colorId - The ID of the element displaying the growth percentage
 * @param {number} amount - The numeric value to display
 * @param {number} growth - The growth percentage (positive or negative)
 */
function updateStatCard(valueId, colorId, txt, growth) {
    const valueElem = document.getElementById(valueId);
    const colorElem = document.getElementById(colorId);

    // 1. Update the Main Amount
    if (valueElem) {
        valueElem.textContent = `${txt}`;
    }

    // 2. Update the Growth Indicator
    if (colorElem) {
        const isPositive = parseFloat(growth || 0) >= 0;
        const absGrowth = Math.abs(growth || 0);

        // Reset and apply the correct class
        colorElem.classList.remove('positive', 'negative');
        colorElem.classList.add(isPositive ? 'positive' : 'negative');

        // Inject the arrow and percentage text
        colorElem.innerHTML = `
            <i class="fas fa-arrow-${isPositive ? 'up' : 'down'}"></i> 
            ${isPositive ? '+' : '-'}${absGrowth}% from last month
        `;
    }
}

      document.addEventListener("DOMContentLoaded", () => {
        fetchData(1)
      })
