export function switchTab(tabs, state, actionEvent, fetchItems) {
  tabs.forEach((tab) => {
    tab.addEventListener(actionEvent, (event)=>{
    tabs.forEach((t) => t.classList.remove('active'))
    if (event && event.target) {
      event.target.classList.add('active')
      state.currentTab = tab.getAttribute('data-tab')
      fetchItems(1)
      
    } 
  })
  return null
})
}

export const moneySign = '₱';

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


export const renderPagination = (fetchData, totalData, state, fetchDataCallback) => {
  const container = document.getElementById('pagination')
  if (!container) return

  // Calculate Total Pages
  const totalPages = (state.total_pages = Math.ceil(totalData / state.limit))

  container.innerHTML = '<div class="pagination-controls"></div>'

  if(state.total_pages <= 1){
    return;
  }

  const controls = container.querySelector('.pagination-controls')
  // Fix: If current page is now out of bounds after filtering
  if (state.currentPage > totalPages) {
    state.currentPage = totalPages
  }


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

  // 2. UI Notification Logic removed - this is a utility file
  // UI notifications should be handled by calling code using ModalSystem/ToastSystem
  switch (type) {
    case 'error':
      console.error('UI notification should be handled by calling code')
      break
    case 'warn':
      console.warn('UI notification should be handled by calling code')
      break
    case 'success':
      console.info('Operation Successful')
      break
    default:
      console.log('System notification updated.')
  }
}



const getElement = (el) => (typeof el === 'string') ? document.querySelector(el) : el;

const withElement = (fn) => (el, ...args) =>{
  let element = getElement(el);
  if (!element) {
    return console.error("Element not found:", el);
  }
  return fn(element, ...args);
}

export const buttonEventListener = withElement((el, callback = null, onAction = 'click') => {
  el.addEventListener(onAction, (e) => {
    callback?.(e, el); 
  });
})


export const putTextinElementById = withElement((el, text, property = null) => {
    if (property === 'style') {
        el.style.cssText = text; // This handles the string correctly
    } else if (property) {
        el[property] = text;
    } else {
        el.textContent = text;
    }
})


export const emptyElement = withElement((element, errorMessage = null) => {
  if (element.value.trim() === ""){
    element.style.borderColor = "red";
    element.style.backgroundColor = "#fff5f5";
    
    // Add error message if provided
    if (errorMessage) {
      // Remove existing error message
      const existingError = element.parentNode.querySelector('.field-error');
      if (existingError) existingError.remove();
      
      // Add new error message
      const errorDiv = document.createElement('div');
      errorDiv.className = 'field-error';
      errorDiv.style.color = 'red';
      errorDiv.style.fontSize = '12px';
      errorDiv.style.marginTop = '4px';
      errorDiv.textContent = errorMessage;
      element.parentNode.appendChild(errorDiv);
    }
    return true; // Field is empty
  }
  return false; // Field is not empty
})

export const clearElementError = withElement((element) => {
  element.style.borderColor = "";
  element.style.backgroundColor = "";

  // Remove error message
  const errorDiv = element.parentNode.querySelector('.field-error');
  if (errorDiv) errorDiv.remove();
})
export const  generateCsrfToken = async() => {
  try{
    const response =  await fetch('/landscape/USER_API/utils/csrf_token.php')
    const data = await response.json()
      
    if (data.token) {
      // Add CSRF token to form
      let form = document.getElementById('uploadForm');
      if (!form) {
        // Create form if it doesn't exist
        form = document.createElement('form');
        form.id = 'uploadForm';
        form.method = 'POST';
        form.enctype = 'multipart/form-data';
        document.body.appendChild(form);
      }
      
      let csrfInput = document.querySelector('input[name="csrf_token"]');
      if (!csrfInput) {
        csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        form.appendChild(csrfInput);
      }
      csrfInput.value = data.token;
    
    }
  } catch (error) {
    console.error('Failed to get CSRF token:', error);
  }
}

const UNITS = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

export const filesizeComputation = (bytes) => {
  // Convert string to number if necessary, handle zero/null
  const byteCount = parseFloat(bytes);
  if (!byteCount || byteCount === 0) return '0 B';
  
  // Use 1024 for standard binary file size (IEC)
  const k = 1024; 
  let i = 0;
  let val = byteCount;
  
  while (val >= k && i < UNITS.length - 1) {
    val /= k;
    i++;
  }

  // Use Intl.NumberFormat for cleaner local-aware rounding
  return `${parseFloat(val.toFixed(2))} ${UNITS[i]}`;
};

export const toggleModal = withElement((selector, show, hide="none") => {
  if(selector.style.display == show){
      selector.style.display = hide;
      return;
  }
  selector.style.display = show;
})

const base = 'http://localhost/landscape/'


const urlGet = (data) =>{
  console.log('urlGet: ', data)
  const files_data = data.filenames.split(',')
  const urls = files_data.map(file => {
    const cleanRelativePath = (data.dir_path + file).replace(/^[./]+/, '')
    return base + cleanRelativePath
  })
  return urls
}


export const rowData = (data) =>{
  const urls = urlGet(data)
  console.log('url: ', urls)
    
  // Format date to human readable
  const formattedDate = new Date(data.created_at).toLocaleDateString('en-US', {
    year: 'numeric', 
    month: 'short', 
    day: 'numeric'
  })
    
  const status = data.status
  // Determine status badge class and text
  const statusMap = {
    'LIVE': { class: 'active', text: 'Live' },
    'DRAFT': { class: 'pending', text: 'Draft' }
  };
  // Fallback to 'DRAFT' logic if status is missing or unknown
  const currentStatus = statusMap[status] || statusMap['DRAFT'];

  

  const totalFileSize = filesizeComputation(data.total_file_size)
    
  // Return data as dictionary/object
  return {
    id: data.portfolio_id,
    url: urls[0],
    urls: urls,
    title: data.title,
    date: formattedDate,
    status: status,
    statusClass: currentStatus.class,
    statusText: currentStatus.text,
    description: data.description,
    fileCount: data.file_count,
    fileSize: data.total_file_size,
    dirPath: data.dir_path,
    portfolioId: data.portfolio_id,
    filesize: totalFileSize,
    service_name: capitalize(data.service_name),
  }
}
