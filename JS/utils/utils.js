export function switchTab(tab, event, fetchItems) {
  document
    .querySelectorAll('.tab')
    .forEach((t) => t.classList.remove('active'))
  if (event && event.target) {
    event.target.classList.add('active')
  }

  fetchItems(1)
}

export function formatToCalendar(dateString) {
  if (!dateString) return 'N/A'

  const date = new Date(dateString.replace(/-/g, '/')) // Replace for cross-browser compatibility

  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  }).format(date)
}


export function renderPagination(fetchData, totalData, state, fetchDataCallback) {
  const container = document.getElementById('pagination')
  if (!container) return

  // Calculate Total Pages
  const totalPages = (state.total_pages = Math.ceil(totalData / state.limit))

  // Fix: If current page is now out of bounds after filtering
  if (state.currentPage > totalPages) {
    state.currentPage = totalPages
  }

  container.innerHTML = '<div class="pagination-controls"></div>'
  const controls = container.querySelector('.pagination-controls')

  // PREV Button
  const prevBtn = document.createElement('button')
  prevBtn.className = 'pagination-btn'
  prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>'
  prevBtn.disabled = state.currentPage <= 1 || totalData === 0
  prevBtn.onclick = () => goToPage(state.currentPage - 1, state, fetchDataCallback)
  controls.appendChild(prevBtn)

  // Logic for Numbered Buttons (Ellipsis)
  let pages = []
  if (totalPages <= 7) {
    for (let i = 1; i <= totalPages; i++) pages.push(i)
  } else {
    if (state.currentPage <= 3) {
      pages = [1, 2, 3, 4, '...', totalPages]
    } else if (state.currentPage >= totalPages - 2) {
      pages = [
        1,
        '...',
        totalPages - 3,
        totalPages - 2,
        totalPages - 1,
        totalPages,
      ]
    } else {
      pages = [
        1,
        '...',
        state.currentPage - 1,
        state.currentPage,
        state.currentPage + 1,
        '...',
        totalPages,
      ]
    }
  }

  pages.forEach((p) => {
    if (p === '...') {
      const span = document.createElement('span')
      span.className = 'pagination-ellipsis'
      span.textContent = '...'
      controls.appendChild(span)
    } else {
      const btn = document.createElement('button')
      const pageNum = Number(p);
      btn.className = `pagination-btn ${pageNum === Number(state.currentPage) ? 'active' : ''}`
      btn.textContent = p
      btn.disabled = totalData === 0

      // FIX: Added missing state and fetchDataCallback parameters
      btn.onclick = () => goToPage(pageNum, state, fetchDataCallback)

      controls.appendChild(btn)
    }
  })

  // NEXT Button
  const nextBtn = document.createElement('button')
  nextBtn.className = 'pagination-btn'
  nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>'
  nextBtn.disabled = state.currentPage >= totalPages || totalData === 0
  nextBtn.onclick = () => goToPage(state.currentPage + 1, state, fetchDataCallback)
  controls.appendChild(nextBtn)
}

function goToPage(pageNumber, state, fetchDataCallback) {
  if (!state) return;
  if (pageNumber < 1 || pageNumber > state.total_pages) return;

  state.currentPage = pageNumber;

  // FIX: Check if it's actually a function before calling it
  if (typeof fetchDataCallback === 'function') {
    fetchDataCallback();
  } else {
    console.error("Pagination Error: fetchDataCallback is not a function. Received:", fetchDataCallback);
  }
}


export function capitalize(str) {
  return str.split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
}

export const log = (message, type = 'info') => {
  const icons = {
    error: '❌',
    warn: '⚠️',
    success: '✅',
    info: 'ℹ️'
  }

  const styles = {
    error: 'color: #ff4d4d; font-weight: bold;',
    warn: 'color: #ffaa00; font-weight: bold;',
    success: 'color: #00ff88; font-weight: bold;',
    info: 'color: #00aaff; font-weight: bold;'
  }

  // 1. Terminal/Console Logger (With consistent formatting)
  console.log(
    `%c${icons[type] || ''} [${type.toUpperCase()}]: ${message}`,
    styles[type] || ''
  )

  // 2. UI Notification Logic
  // This is where you swap alert() for SweetAlert or Toastr
  switch (type) {
    case 'error':
      // Example: Swal.fire('Error', message, 'error')
      alert(`🛑 ERROR: ${message}`)
      break
    case 'warn':
      alert(`⚠️ WARNING: ${message}`)
      break
    case 'success':
      // Successes are often better as silent console logs or small toasts
      console.info('Operation Successful')
      break
    default:
      console.log('System notification updated.')
  }
}