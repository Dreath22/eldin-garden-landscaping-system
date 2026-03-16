import { switchTab, renderPagination } from './utils/utils.js'
// --- 1. Centralized State Object ---
const state = {
  currentPage: 1,
  currentTab: 'all',
  role: 'all',
  order: 'newest',
  dateFrom: '',
  dateTo: '',
  allUsers: [], // Stores the current page of users
  limit: 9,
  total_pages: 1,
  selectAllActive: false,
}

async function exportUsers(exportBtn) {
  const originalText = exportBtn.innerHTML

  // 1. UI Feedback: Show the user something is happening
  exportBtn.disabled = true
  exportBtn.innerHTML = 'Generating CSV...'

  // 2. Build params: We set a very high limit to bypass pagination
  const params = new URLSearchParams({
    status: state.currentTab,
    role: state.role,
    order: state.order,
    from: state.dateFrom,
    to: state.dateTo,
    limit: 10000, // High limit to get all filtered users
    offset: 0, // Always start from the first record for export
  })

  try {
    const response = await fetch(
      `/landscape/USER_API/users.php?${params.toString()}`,
    )
    if (!response.ok) throw new Error('Failed to fetch data for export')

    const data = await response.json()
    const usersToExport = data.users

    if (!usersToExport || usersToExport.length === 0) {
      alert('No users found matching the current filters.')
      return
    }

    // 3. Define CSV Headers
    const headers = [
      'ID',
      'Full Name',
      'Email',
      'Role',
      'Status',
      'Joined Date',
    ]

    // 4. Map data to CSV rows
    // We wrap names in quotes to prevent commas in names from breaking columns
    const csvRows = usersToExport.map((u) =>
      [
        u.id,
        `"${u.name}"`,
        u.email,
        u.role,
        u.status,
        u.joined_date,
      ].join(','),
    )

    // 5. Combine and create the File
    const csvContent = [headers.join(','), ...csvRows].join('\n')
    const blob = new Blob([csvContent], {
      type: 'text/csv;charset=utf-8;',
    })
    const url = URL.createObjectURL(blob)

    // 6. Trigger Download
    const link = document.createElement('a')
    const dateStamp = new Date().toISOString().split('T')[0]

    link.setAttribute('href', url)
    link.setAttribute(
      'download',
      `User_Export_${state.currentTab}_${dateStamp}.csv`,
    )
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (error) {
    console.error('Export Error:', error)
    alert('There was an error generating the export.')
  } finally {
    // Restore button state
    exportBtn.disabled = false
    exportBtn.innerHTML = originalText
  }
}

async function confirmBan(userId) {
  const userName = document.getElementById('banUserName').textContent
  const reasonElement = document.querySelector(
    'input[name="banReason"]:checked',
  )
  const note = document.getElementById('banNote').value

  // 1. Validation: Ensure a reason is selected
  if (!reasonElement) {
    alert('Please select a reason for the ban.')
    return
  }

  // 2. Prepare Data (Matching your DB schema: id and status)
  const banData = {
    id: userId,
    status: 'banned', // Explicitly setting status for the DB
    reason: reasonElement.value,
    notes: note,
  }

  // Optional: UI Feedback (Disable button during request)
  const banBtn = document.querySelector(
    '.btn-danger[onclick^="confirmBan"]',
  )
  if (banBtn) {
    banBtn.disabled = true
    banBtn.textContent = 'Processing...'
  }

  try {
    // 3. Sending data using async/await to your API directory
    const response = await fetch('/landscape/USER_API/ban_user.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(banData),
    })

    const result = await response.json()

    if (response.ok && result.status === 'success') {
      alert(`User ${userName} has been banned.`)

      closeBanModal()

      // 4. Refresh the UI list if the function exists
      if (typeof fetchUsers === 'function') {
        fetchUsers()
      }
    } else {
      alert('Error: ' + (result.message || 'Failed to ban user'))
    }
  } catch (error) {
    console.error('Ban failed:', error)
    alert('Connection error. Could not reach the server.')
  } finally {
    // Re-enable button
    if (banBtn) {
      banBtn.disabled = false
      banBtn.textContent = 'Ban User'
    }
  }
}

async function confirmEditUser(userId) {
  // Helper to treat placeholder strings as empty/null
  const cleanValue = (id, placeholder) => {
    const val = document.getElementById(id).value.trim()
    return val === '' || val === placeholder ? null : val
  }

  // 1. Gather data with placeholder handling
  const userData = {
    id: userId,
    firstName: cleanValue('editFirstName', ''),
    lastName: cleanValue('editLastName', ''),
    email: cleanValue('editEmail', ''),
    // If the value is the default "Phone number not provided", send null
    phone_number: cleanValue('editPhone', 'Phone number not provided'),
    role: document.getElementById('editRole').value,
    status: document.getElementById('editStatus').value,
    // If the notes are the default "Regular customer...", treat as empty
    notes: cleanValue(
      'editNotes',
      'Regular customer, prefers weekend appointments.',
    ),
  }

  // 2. Strict Validation: Ensure essential data exists
  if (!userData.firstName || !userData.email) {
    alert('First Name and Email are required.')
    return
  }

  // Optional: UI feedback
  const saveBtn = document.querySelector(
    'button[onclick="confirmEditUser()"]',
  )
  if (saveBtn) saveBtn.disabled = true

  try {
    const response = await fetch('/landscape/USER_API/update_user.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(userData),
    })

    const result = await response.json()

    if (response.ok) {
      alert('User updated successfully!')
      
      if (typeof fetchUsers === 'function') fetchUsers()
    } else {
      alert('Error: ' + (result.message || 'Failed to update'))
    }
  } catch (error) {
    console.error('Update failed:', error)
    alert('Connection error. Please check if the server is reachable.')
  } finally {
    if (saveBtn) saveBtn.disabled = false
  }
}

async function confirmAddUser() {
  const form = document.getElementById('addUserForm')

  // Create FormData object from the form element
  const formData = new FormData(form)

  // Convert FormData to a clean JSON object
  const userData = Object.fromEntries(formData.entries())

  console.log('data', userData)
  // Basic Validation: Ensure required fields aren't empty
  if (
    !userData.firstName ||
          !userData.email ||
          !userData.temporaryPassword
  ) {
    alert(
      'Please fill in the required fields (First Name, Email, and Password).',
    )
    return
  }

  try {
    // Change this URL to your actual API endpoint
    const response = await fetch('/landscape/USER_API/add_user.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(userData),
    })

    if (response.ok) {
      const result = await response.json()
      console.log('Success:', result)

      alert('User added successfully!')

      // Cleanup: Reset form and close modal
      form.reset()
      //need to close the modal

      // Optional: Refresh your user list table here
      fetchUsers(1)
    } else {
      const errorData = await response.json()
      alert(`Failed to add user: ${errorData.message || 'Server error'}`)
    }
  } catch (error) {
    console.error('Network error:', error)
    alert(
      'Could not connect to the server. Please check your connection.',
    )
  }
}

async function fetchUsers(page = state.currentPage) {
  state.currentPage = page

  const params = new URLSearchParams({
    page: state.currentPage,
    status: state.currentTab,
    role: state.role,
    order: state.order,
    from: state.dateFrom,
    to: state.dateTo,
  })

  const apiURL = `/landscape/USER_API/users.php?${params.toString()}`

  try {
    const response = await fetch(apiURL)
    if (!response.ok)
      throw new Error(`HTTP error! status: ${response.status}`)

    const data = await response.json()
    console.log('Fetched data:', data)

    // Update State & UI
    state.allUsers = data.users
    displayStats(data.summary)
    displayUsers(data.users)

    renderPagination(fetchUsers, data.summary.roleFilterValue, state)
  } catch (error) {
    console.error('Fetch error:', error)
  }
}

      
// --- 5. Action Handler (Finds user in state) ---
window.handleAction = (userId, actionType) => {
  const user = state.allUsers.find((u) => u.id === userId)
  if (!user) return console.error(`User ${userId} not found.`)

  if (actionType === 'view') showViewUserModal(user)
  else if (actionType === 'edit') showEditUserModal(user)
  else if (actionType === 'ban') showBanModal(user)
}

     
      

function displayStats(data) {
  const stateMap = {
    totalUser: data.total_users,
    activeUser: data.active_users,
    pendingUser: data.pending_users,
    bannedUser: data.banned_users,
  }
  for (const [id, value] of Object.entries(stateMap)) {
    const el = document.getElementById(id)
    if (el) el.textContent = value
  }
}

function displayUsers(users) {
  const userTable = document.getElementById('userTableBody')
  if (!userTable) return

  // Efficiently render table rows
  userTable.innerHTML = users
    .map(
      (user) => `
      <tr>
        <td><input type="checkbox" class="checkbox" ${state.selectAllActive ? 'checked' : ''}/></td>
        <td>
          <div class="table-user">
            <img src="${user.avatar_url}" alt="${user.name}" />
            <div class="table-user-info">
              <h4>${user.name}</h4>
              <p>${user.email}</p>
            </div>
          </div>
        </td>
        <td>${user.role}</td>
        <td title="${new Date(user.joined_date).toLocaleString()}">${timeAgo(user.joined_date) || 'Never'}</td>
        <td title="${new Date(user.last_active).toLocaleString()}">${timeAgo(user.last_active) || 'Never'}</td>
        <td>
          <span class="status-badge ${user.status.toLowerCase()}">${user.status}</span>
        </td>
        <td>
          <div class="table-actions">
            <button class="table-btn view" onclick="handleAction(${user.id}, 'view')"><i class="fas fa-eye"></i></button>
            <button class="table-btn edit" onclick="handleAction(${user.id}, 'edit')"><i class="fas fa-edit"></i></button>
            <button class="table-btn ban" onclick="handleAction(${user.id}, 'ban')"><i class="fas fa-ban"></i></button>
          </div>
        </td>
      </tr>
    `,
    )
    .join('')
}

// --- 7. Pagination Logic (Updated to use state) ---
      

// --- 8. Modal Display Logic ---
// (Using your existing template structure, referencing state)

function showViewUserModal(user) {
  const doc = `<div class="modal modal-large">
      <div class="modal-header">
        <h3><i class="fas fa-user" style="color: var(--primary-green);"></i> User Details</h3>
        <button class="modal-close" onclick="closeViewUserModal()">&times;</button>
      </div>
      <div class="modal-body">
        <div style="display: flex; gap: 2rem; margin-bottom: 1.5rem;">
          <img src="${user.avatar_url}" id="viewUserProfile" alt="User" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;">
          <div>
            <h3 id="viewUserName">${user.name}</h3>
            <p style="color: var(--text-gray);" id="viewUserEmail">${user.email}</p>
            <span class="status-badge ${user.status.toLowerCase()}" style="margin-top: 0.5rem; display: inline-block;">${user.status}</span>
          </div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
          <div>
            <h4 style="margin-bottom: 0.75rem; color: var(--text-dark);">Contact Information</h4>
            <p style="margin-bottom: 0.5rem;"><i class="fas fa-phone" style="width: 20px; color: var(--text-gray);"></i> ${user.phone_number || 'Phone Number not provided'}</p>
            <p style="margin-bottom: 0.5rem;"><i class="fas fa-map-marker-alt" style="width: 20px; color: var(--text-gray);"></i> ${user.address || 'Address not provided'}</p>
            <p><i class="fas fa-calendar" style="width: 20px; color: var(--text-gray);"></i> Joined ${timeAgo(user.joined_date)}</p>
          </div>
          <div>
            <h4 style="margin-bottom: 0.75rem; color: var(--text-dark);">Activity Summary</h4>
            <p style="margin-bottom: 0.5rem;"><i class="fas fa-shopping-cart" style="width: 20px; color: var(--text-gray);"></i> ${user.booking_count || 0} Bookings</p>
            <p style="margin-bottom: 0.5rem;"><i class="fas fa-dollar-sign" style="width: 20px; color: var(--text-gray);"></i> $${user.total_spent || 0} Total Spent</p>
            <p><i class="fas fa-clock" style="width: 20px; color: var(--text-gray);"></i> Last active ${timeAgo(user.last_active) || 'Never'}</p>
          </div>
        </div>
        <div style="margin-top: 1.5rem;">
          <h4 style="margin-bottom: 0.75rem; color: var(--text-dark);">Recent Bookings</h4>
          <div class="transaction-list">
            <div class="transaction-item">
              <div class="transaction-icon income">
                <i class="fas fa-calendar-check"></i>
              </div>
              <div class="transaction-info">
                <h4>Lawn Maintenance Service</h4>
                <p>Booked on Feb 18, 2026</p>
              </div>
              <span class="status-badge completed">Completed</span>
            </div>
            <div class="transaction-item">
              <div class="transaction-icon income">
                <i class="fas fa-calendar-check"></i>
              </div>
              <div class="transaction-info">
                <h4>Garden Design Consultation</h4>
                <p>Booked on Feb 10, 2026</p>
              </div>
              <span class="status-badge completed">Completed</span>
            </div>
          </div>
        </div>
       <div class="modal-footer">
        <button class="btn btn-small" style="background-color: #f1f5f9; color: var(--text-dark);" onclick="closeViewUserModal()">Close</button>
        <button class="btn btn-primary btn-small" onclick="closeViewUserModal(); showEditUserModal('${user}')">Edit User</button>
      </div>
    </div>`
  document.getElementById('viewUserModal').innerHTML = doc
  document.getElementById('viewUserModal').style.display = 'flex'
}

function showEditUserModal(user) {
  document.getElementById('editUserModal').style.display = 'flex'
  document.getElementById('editUserModal').innerHTML =
    `<div class="modal modal-large">
      <div class="modal-header">
        <h3><i class="fas fa-edit" style="color: var(--primary-green);"></i> Edit User</h3>
        <button class="modal-close" onclick="closeEditUserModal()">&times;</button>
      </div>
      <div class="modal-body">
        <form id="editUserForm">
          <div class="form-row">
            <div class="form-group">
              <label for="editFirstName">First Name</label>
              <input type="text" id="editFirstName" value="${user.name.split(' ')[0] || ''}">
            </div>
            <div class="form-group">
              <label for="editLastName">Last Name</label>
              <input type="text" id="editLastName" value="${user.name.split(' ')[1] || ''}">
            </div>
          </div>
          <div class="form-group">
            <label for="editEmail">Email Address</label>
            <input type="email" id="editEmail" value="${user.email || ''}">
          </div>
          <div class="form-group">
            <label for="editPhone">Phone Number</label>
            <input type="tel" id="editPhone" value="${user.phone_number || 'Phone number not provided'}">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="editRole">Role</label>
              <select id="editRole">
                <option value="customer" ${user.role.toLowerCase() === 'customer' ? 'selected' : ''}>Customer</option>
                <option value="staff" ${user.role.toLowerCase() === 'staff' ? 'selected' : ''}>Staff</option>
                <option value="admin" ${user.role.toLowerCase() === 'admin' ? 'selected' : ''}>Admin</option>
              </select>
            </div>
            <div class="form-group">
              <label for="editStatus">Status</label>
              <select id="editStatus">
                <option value="active" ${user.status.toLowerCase() === 'active' ? 'selected' : ''}>Active</option>
                <option value="pending" ${user.status.toLowerCase() === 'pending' ? 'selected' : ''}>Pending</option>
                <option value="banned" ${user.status.toLowerCase() === 'banned' ? 'selected' : ''}>Banned</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label for="editNotes">Notes</label>
            <textarea id="editNotes" rows="2">Regular customer, prefers weekend appointments.</textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-small" style="background-color: #f1f5f9; color: var(--text-dark);" onclick="closeEditUserModal()">Cancel</button>
        <button class="btn btn-primary btn-small" onclick="confirmEditUser(${user.id})">Save Changes</button>
      </div>
    </div>`
}
      

      

      

function showBanModal(user) {
  document.getElementById('banModal').style.display = 'flex'
  document.getElementById('banModal').innerHTML = `
        <div class="modal">
        <div class="modal-header">
          <h3>
            <i class="fas fa-ban" style="color: var(--danger-red)"></i> Ban User
          </h3>
          <button class="modal-close" onclick="closeBanModal()">&times;</button>
        </div>
        <div class="modal-body">
          <p style="margin-bottom: 1rem; color: var(--text-gray)">
            You are about to ban <strong id="banUserName">${user.name}</strong>. Please
            select a reason:
          </p>
          <div class="ban-reasons">
            <label class="ban-reason">
              <input type="radio" name="banReason" value="spam" />
              <span>Spam or inappropriate content</span>
            </label>
            <label class="ban-reason">
              <input type="radio" name="banReason" value="payment" />
              <span>Payment fraud or chargebacks</span>
            </label>
            <label class="ban-reason">
              <input type="radio" name="banReason" value="harassment" />
              <span>Harassment or abusive behavior</span>
            </label>
            <label class="ban-reason">
              <input type="radio" name="banReason" value="fake" />
              <span>Fake account or impersonation</span>
            </label>
            <label class="ban-reason">
              <input type="radio" name="banReason" value="other" />
              <span>Other reason</span>
            </label>
          </div>
          <div class="form-group" style="margin-top: 1rem">
            <label for="banNote">Additional Notes (Optional)</label>
            <textarea
              id="banNote"
              rows="2"
              placeholder="Enter any additional details..."
            ></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button
            class="btn btn-small"
            style="background-color: #f1f5f9; color: var(--text-dark)"
            onclick="closeBanModal()"
          >
            Cancel
          </button>
          <button class="btn btn-danger btn-small" onclick="confirmBan(${user.id})">
            Ban User
          </button>
        </div>
      </div>` // Default to first reason
}

function closeBanModal() {
  document.getElementById('banModal').style.display = 'none'
}

// function selectAll() {
//   document.addEventListener('change', function (e) {
//     if (e.target && e.target.id === 'deleteAll') {
//       state.selectAllActive = e.target.checked
//       const checkboxes = document.querySelectorAll('.checkbox')
//       checkboxes.forEach((cb) => (cb.checked = state.selectAllActive))
//     }

//     if (
//       e.target &&
//             e.target.classList.contains('checkbox') &&
//             !e.target.checked
//     ) {
//       state.selectAllActive = false
//       const master = document.getElementById('deleteAll')
//       if (master) master.checked = false
//     }
//   })
// }

      

     

// --- 9. Utilities ---
function timeAgo(dateString) {
  // 1. Handle Null or MySQL zero dates
  if (!dateString || dateString === '0000-00-00 00:00:00') return 'Never'

  const now = new Date()
  const past = new Date(dateString)

  // 2. Safety check for invalid dates
  if (isNaN(past.getTime())) return 'Never'

  // 3. Calculate difference in seconds
  const seconds = Math.floor((now - past) / 1000)

  // 4. Handle edge cases (like server clock being 1-2 seconds ahead of client)
  if (seconds < 60) return 'Just now'

  const minutes = Math.floor(seconds / 60)
  if (minutes < 60) return `${minutes}m ago`

  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours}h ago`

  const days = Math.floor(hours / 24)
  if (days < 30) return `${days}d ago`

  // 5. For older dates, show the actual date
  return past.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    // Only show year if it's not the current year
    year:
            now.getFullYear() !== past.getFullYear() ? 'numeric' : undefined,
  })
}
   

window.goToPage = (page) => fetchUsers(page)
window.confirmBan = (userId) => confirmBan(userId)
window.confirmEditUser = (userId) => confirmEditUser(userId)
window.confirmAddUser = () => confirmAddUser()
window.closeViewUserModal = () => document.getElementById('viewUserModal').style.display = 'none'
window.closeEditUserModal = () => document.getElementById('editUserModal').style.display = 'none'
window.closeBanModal = () => document.getElementById('banModal').style.display = 'none'
window.closeAddUserModal = () => document.getElementById('addUserModal').style.display = 'none'
window.document.querySelector('[data-action=\'showadd-user\']').onclick = () => document.getElementById('addUserModal').style.display = 'flex'

document.querySelector('[data-action=\'showadd-user\']').onclick = function() {
  document.getElementById('addUserModal').style.display = 'flex'
}
// --- 3. Event Listeners (Initialized on Load) ---
document.addEventListener('DOMContentLoaded', () => {
  // Role Filter Listener
  const roleSelect = document.getElementById('roleFilter')
  roleSelect?.addEventListener('change', (e) => {
    state.role = e.target.value.toLowerCase()
    fetchUsers(1) // Reset to page 1 on filter change
  })

  // Order Filter Listener
  const orderSelect = document.getElementById('orderFilter')
  orderSelect?.addEventListener('change', (e) => {
    state.order = e.target.value
    fetchUsers(1)
  })

  // Date Filter Listeners
  const fromInput = document.getElementById('dateFrom')
  const toInput = document.getElementById('dateTo')

  const onDateChange = () => {
    state.dateFrom = fromInput.value
    state.dateTo = toInput.value

    // Sync min/max to prevent invalid ranges in the calendar UI
    if (fromInput.value) toInput.min = fromInput.value
    if (toInput.value) fromInput.max = toInput.value

    // Update the export button's functionality based on the selected date range
    document.getElementById('exportBtn').onclick = function() {
      exportUsers(this)
    }
    fetchUsers(1)
  }

  fromInput?.addEventListener('change', onDateChange)
  toInput?.addEventListener('change', onDateChange)

  document.querySelectorAll('#userTabsContainer .tab').forEach((tabElement) => {
    tabElement.addEventListener('click', (event) => {
      const tabName = tabElement.getAttribute('data-tab')
      // Call the private function
      state.currentTab = tabName
      switchTab(tabName, event, fetchUsers)
    })
  })
  // Initial Data Fetch
  fetchUsers(1)
})