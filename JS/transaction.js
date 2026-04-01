import {renderPagination} from "./utils/utils.js"

const state = {
  currentPage: 1,
  status: 'all',
  category: 'all',
  transactionType: 'all',
  order: 'newest',
  dateFrom: '',
  dateTo: '',
  allTransactions: [], // Stores the current page of users
  limit: 10,
  total_pages: 1,
  selectAllActive: false,
}

window.fetchData = async (page = state.currentPage) => {
  state.currentPage = page

  // Use URLSearchParams for clean URL building
  const params = new URLSearchParams({
    page: state.currentPage,
    status: state.status,
    order: state.order,
    from: state.dateFrom,
    to: state.dateTo,
    category: state.category,
    transactionType: state.transactionType
  })

  const apiURL = `/landscape/USER_API/transactions.php?${params.toString()}`

  try {
    const response = await fetch(apiURL)
    if (!response.ok)
      throw new Error(`HTTP error! status: ${response.status}`)

    const data = await response.json()
    console.log('Fetched data:', data)   

    // Update State & UI
    state.allTransactions = data.transactions || [];
    dataSummaryload(data.summary)
    displayData(data.transactions.transactions || [])

    renderPagination(fetchData, data.summary.total, state, () => fetchData());
  } catch (error) {
    console.error('Fetch error:', error)
  }
}

function dataSummaryload(data){
  updateStatCard('monthRevenue', 'monthRevenueColor', `$${parseFloat(data.revenue_this_month || 0).toLocaleString()}`, data.revenue_growth)
  updateStatCard('totalExpenses', 'totalExpensesColor', `$${parseFloat(data.expenses_this_month || 0).toLocaleString()}`, data.expense_growth)
  updateStatCard('lastMonthProfit', 'lastMonthProfitColor', `$${parseFloat(data.net_profit_this_month || 0).toLocaleString()}`, data.profit_growth)
  updateStatCard('totalTransactions', 'totalTransactionsColor', `${data.transactions_this_month}`, data.transactions_growth)
}

function updateStatCard(valueId, colorId, txt, growth) {
  const valueElem = document.getElementById(valueId)
  const colorElem = document.getElementById(colorId)

  // 1. Update the Main Amount
  if (valueElem) {
    valueElem.textContent = `${txt}`
  }

  // 2. Update the Growth Indicator
  if (colorElem) {
    const isPositive = parseFloat(growth || 0) >= 0
    const absGrowth = Math.abs(growth || 0)

    // Reset and apply the correct class
    colorElem.classList.remove('positive', 'negative')
    colorElem.classList.add(isPositive ? 'positive' : 'negative')

    // Inject the arrow and percentage text
    colorElem.innerHTML = `
            <i class="fas fa-arrow-${isPositive ? 'up' : 'down'}"></i> 
            ${isPositive ? '+' : '-'}${absGrowth}% from last month
        `
  }
}

//

/**
 * Create Transaction Function
 * Role: Senior JS Software Engineer
 * 
 * Creates a new transaction with thorough validation and sanitization
 * before sending to the API endpoint.
 */
async function createTransaction() {
  try {
    // 1. Get form elements
    const elements = {
      description: document.getElementById('addedTransaction_description'),
      category: document.getElementById('addedTransaction_category'),
      date: document.getElementById('addedTransaction_date'),
      notes: document.getElementById('addedTransaction_notes'),
      amount: document.getElementById('addedTransaction_amount'),
      type: document.getElementById('addedTransaction_type')
    }

    // 2. Validate all elements exist
    for (const [key, element] of Object.entries(elements)) {
      if (!element) {
        throw new Error(`Form element not found: addedTransaction_${key}`)
      }
    }

    // 3. Extract and sanitize raw values
    const rawValues = {
      description: elements.description.value.trim(),
      category: elements.category.value,
      date: elements.date.value,
      notes: elements.notes.value.trim(),
      amount: elements.amount.value,
      type: elements.type.value
    }

    // 4. Comprehensive validation
    const validationErrors = []

    // Description validation
    if (!rawValues.description) {
      validationErrors.push('Description is required')
    } else if (rawValues.description.length < 3) {
      validationErrors.push('Description must be at least 3 characters long')
    } else if (rawValues.description.length > 255) {
      validationErrors.push('Description must not exceed 255 characters')
    }

    // Category validation
    if (!rawValues.category) {
      validationErrors.push('Category is required')
    }

    // Date validation
    if (!rawValues.date) {
      validationErrors.push('Date is required')
    } else {
      const dateObj = new Date(rawValues.date)
      const today = new Date()
      today.setHours(0, 0, 0, 0)
      
      if (isNaN(dateObj.getTime())) {
        validationErrors.push('Invalid date format')
      } else if (dateObj > today) {
        validationErrors.push('Date cannot be in the future')
      }
    }

    // Amount validation
    if (!rawValues.amount) {
      validationErrors.push('Amount is required')
    } else {
      const amount = parseFloat(rawValues.amount)
      if (isNaN(amount)) {
        validationErrors.push('Amount must be a valid number')
      } else if (amount <= 0) {
        validationErrors.push('Amount must be greater than 0')
      } else if (amount > 999999999.99) {
        validationErrors.push('Amount exceeds maximum limit')
      }
    }

    // Type validation
    if (!rawValues.type) {
      validationErrors.push('Transaction type is required')
    }

    // Notes validation (optional but with constraints)
    if (rawValues.notes && rawValues.notes.length > 1000) {
      validationErrors.push('Notes must not exceed 1000 characters')
    }

    // 5. If validation errors exist, throw error
    if (validationErrors.length > 0) {
      throw new Error(`Validation failed: ${validationErrors.join(', ')}`)
    }

    // 6. Sanitize and prepare data for API
    const sanitizedData = {
      description: sanitizeString(rawValues.description),
      category: sanitizeString(rawValues.category),
      date: rawValues.date, // Date format is already validated
      notes: sanitizeString(rawValues.notes),
      amount: parseFloat(rawValues.amount).toFixed(2), // Ensure 2 decimal places
      type: sanitizeString(rawValues.type)
    }

    // 7. Additional data integrity checks
    const allowedCategories = ['service', 'equipment', 'materials', 'labor', 'other']
    const allowedTypes = ['income', 'expense', 'refund']

    if (!allowedCategories.includes(sanitizedData.category)) {
      throw new Error('Invalid category selected')
    }

    if (!allowedTypes.includes(sanitizedData.type)) {
      throw new Error('Invalid transaction type selected')
    }

    // 8. Send to API
    const response = await fetch('/landscape/USER_API/transactions.php?action=create', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(sanitizedData)
    })

    if (!response.ok) {
      const errorText = await response.text()
      throw new Error(`API Error: ${response.status} - ${errorText}`)
    }

    const result = await response.json()

    if (!result.success) {
      throw new Error(result.message || 'Failed to create transaction')
    }

    // 9. Success handling
    console.log('Transaction created successfully:', result)
    
    // Clear form
    Object.values(elements).forEach(element => {
      if (element.type === 'number') {
        element.value = ''
      } else if (element.tagName === 'SELECT') {
        element.selectedIndex = 0
      } else {
        element.value = ''
      }
    })

    // Refresh data
    await fetchData(1)
    
    return result

  } catch (error) {
    console.error('Create transaction error:', error)
    throw error // Re-throw for caller to handle
  }
}

/**
 * Sanitize string input to prevent XSS and remove unwanted characters
 */
function sanitizeString(input) {
  if (typeof input !== 'string') {
    return ''
  }
  
  return input
    .replace(/[<>]/g, '') // Remove potential HTML tags
    .replace(/['"]/g, '') // Remove quotes
    .replace(/[\x00-\x1F\x7F]/g, '') // Remove control characters
    .replace(/[\s]{2,}/g, ' ') // Replace multiple spaces with single space
    .trim()
}

/**
 * Utility function to validate numeric input
 */
function isValidNumber(value, min = 0, max = 999999999.99) {
  const num = parseFloat(value)
  return !isNaN(num) && num >= min && num <= max
}

/**
 * Utility function to validate date format and range
 */
function isValidDate(dateString, maxDate = new Date()) {
  const date = new Date(dateString)
  return !isNaN(date.getTime()) && date <= maxDate
}

// Make the function globally accessible
window.createTransaction = createTransaction

window.displayData = (datas) => {
  let body = document.getElementById('tablebody')
  if (!body) {
    console.error('tablebody element not found!')
    return
  }
  
  let html = ""
  
  if (!datas || datas.length === 0) {
    body.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 2rem;">No transactions found</td></tr>'
    return
  }
  console.log(datas)
  
  datas.forEach((data) => {
    html += `<tr>
              <td style="font-family: monospace;">#${data.transaction_code}</td>
              <td>${data.transaction_date}</td>
              <td>
                <div class="table-user">
                  <img src="${data.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(data.full_name || 'User')}&background=6366f1&color=fff&size=100`}" alt="${data.full_name}" title="${data.full_name}" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(data.full_name || 'User')}&background=6366f1&color=fff&size=100'">
                  <div class="table-user-info">
                    <h4>${data.full_name}</h4>
                    <p>${data.user_email}</p>
                  </div>
                </div>
              </td>
              <td><span class="status-badge active">${data.type || 'Payment'}</span></td>
              <td><span class="status-badge ${data.status?.toLowerCase()}">${data.status || 'Unknown'}</span></td>
              <td style="color: #22c55e; font-weight: 600;">$${parseFloat(data.amount || 0).toFixed(2)}</td>
              <td>
                <div class="table-actions">
                  <button class="table-btn view" title="View" onclick="showTransactionDetails('${data.transaction_code}')"><i class="fas fa-eye"></i></button>
                  <!--<button class="table-btn edit" title="Edit"><i class="fas fa-edit"></i></button>
                  <button class="table-btn delete" title="Delete"><i class="fas fa-trash"></i></button>-->
                </div>
              </td>
            </tr>`
  })
  
  body.innerHTML = html
}

 window.getTransactionDetails = async(transactionid) => {
    try {
        const response = await fetch(`/landscape/USER_API/transactions.php?transaction_id=${transactionid}`)
        const data = await response.json()
        console.log(data)
        return data
    } catch (error) {
        console.error('Error fetching transaction details', error)
        return null
    }
}


document.addEventListener('DOMContentLoaded', () => {
  fetchData(1)
})


