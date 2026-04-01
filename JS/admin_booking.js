import { switchTab, formatToCalendar, renderPagination, capitalize, log } from './utils/utils.js'
// --- 1. Centralized State Object ---
const state = {
  currentPage: 1,
  status: 'all',
  category: 0,
  order: 'newest',
  dateFrom: '',
  dateTo: '',
  allBookings: [], // Stores the current page of users
  limit: 6,
  total_pages: 1,
  selectAllActive: false,
}

async function fetchData(page = state.currentPage) {
  state.currentPage = page

  // Use URLSearchParams for clean URL building
  const params = new URLSearchParams({
    page: state.currentPage,
    status: state.status,
    order: state.order,
    from: state.dateFrom,
    category: state.category
  })

  const apiURL = `/landscape/USER_API/BookingsController.php?action=list&${params.toString()}`
  

  try {
    const response = await fetch(apiURL)
    if (!response.ok)
      throw new Error(`HTTP error! status: ${response.status}`)

    const data = await response.json()
    console.log('Fetched data:', data)

    // Update State & UI
    state.allBookings = data.bookings
    displayStats(data.summary)
    displayBookings(data.bookings)
    renderPagination(fetchData, data.summary.filtered, state, () => fetchData())
  } catch (error) {
    console.error('Fetch error:', error)
  }
}

function displayStats(data) {
  const stateMap = {
    activeBooking: data.active || 0,
    pendingBooking: data.pending || 0,
    bannedBooking: data.banned || 0,
    completedBooking: data.completed || 0,
    cancelledBooking: data.cancelled || 0,
  }
  for (const [id, value] of Object.entries(stateMap)) {
    const el = document.getElementById(id)
    if (el) el.textContent = value
  }
}

function displayBookings(bookings) {
  const container = document.getElementById('bookingContainer')
  container.innerHTML = ''

  if (bookings.length === 0) {
    container.innerHTML = '<p>No bookings found.</p>'
    return
  }

  let doc = ''
  bookings.forEach((booking) => {
    const statusLower = booking.status.toLowerCase()

    // Start of Card
    doc += `
      <div class="booking-card">
        <div class="booking-card-header">
          <span style="font-weight: 600; color: var(--text-dark)">#BK-2026-${booking.id.toString().padStart(3, '0')}</span>
          <span class="status-badge ${statusLower}">${booking.status}</span>
        </div>
        <div class="booking-card-body">
          <div class="table-user" style="margin-bottom: 1rem">
            <img src="${booking.avatar_url || 'default-avatar.png'}" alt="User" />
            <div class="table-user-info">
              <h4>${booking.name}</h4>
              <p>${booking.email}</p>
            </div>
          </div>
          <div class="booking-info-row">
            <i class="fas fa-tools"></i>
            <span>${capitalize(booking.category)} Service</span>
          </div>
          <div class="booking-info-row">
            <i class="fas fa-calendar"></i>
            <span>${formatToCalendar(booking.appointment_date)}</span>
          </div>
          <div class="booking-info-row">
            <i class="fas fa-map-marker-alt"></i>
            <span>${booking.address}</span>
          </div>
          <div class="booking-info-row">
            <i class="fas fa-dollar-sign"></i>
            <span style="font-weight: 600; color: var(--primary-green)">$${parseFloat(booking.total_amount).toFixed(2)}</span>
          </div>
        </div>`

    // Footer Logic (Appended inside the card)
    doc += `<div class="booking-card-footer">
              <button class="btn btn-small" style="background-color: #f1f5f9; color: var(--text-dark)" onclick="handleModalAction('view','${booking.id}')">
                <i class="fas fa-eye"></i> View
              </button>`

    if (statusLower === 'pending') {
      doc += `
        <button class="btn btn-primary btn-small" onclick="handleModalAction('confirm','${booking.id}')" id="confirmBtn-${booking.id}">
          <i class="fas fa-check"></i> Confirm
        </button>
        <button class="btn btn-danger btn-small" onclick="handleModalAction('cancel','${booking.id}')" id="cancelBtn-${booking.id}">
          <i class="fas fa-times"></i> Cancel
        </button>`
    } else if (statusLower === 'active') {
      doc += `
        <button class="btn btn-success btn-small" id="completeBtn-${booking.id}" onclick="handleModalAction('complete','${booking.id}')">
          <i class="fas fa-check-double"></i> Complete
        </button>`
    } else if (statusLower === 'completed') {
      doc += `
        <button class="btn btn-secondary btn-small" onclick="handleModalAction('invoice','${booking.id}' )">
          <i class="fas fa-file-invoice"></i> Invoice
        </button>`
    } 

    doc += '</div></div>' // Close footer and card
  })

  container.innerHTML = doc
}

const modalActions = {
  confirm: (id) => {
    const booking = state.allBookings.find(b => b.id === Number(id));
    const docID = document.getElementById('confirmModal')
    docID.innerHTML = `
              <div class="w-full max-w-2xl bg-white dark:bg-slate-900 rounded-xl shadow-2xl overflow-hidden border border-slate-200 dark:border-slate-800 max-h-[95vh] flex flex-col">
            
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/20 shrink-0">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg md:text-xl font-bold text-primary dark:text-emerald-500">Confirm & Activate</h2>
                        <span class="px-2 py-0.5 bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 text-[9px] font-bold rounded-full uppercase border border-orange-200 dark:border-orange-800">Pending</span>
                    </div>
                    <p class="text-slate-500 dark:text-slate-400 text-xs">Project Blueprint & Service Agreements</p>
                </div>
                <button class="text-slate-400 hover:text-slate-600 p-1" onclick="closeModal('confirmModal')">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <div class="p-5 space-y-6 overflow-y-auto">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="relative group dropzone-pattern">
                        <label for="upload-blueprint" class="py-4 md:py-6 px-2 rounded-lg flex flex-row md:flex-col items-center justify-center md:text-center cursor-pointer border border-dashed border-slate-300 dark:border-slate-700 hover:border-primary hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all gap-3 md:gap-1">
                            <span class="material-symbols-outlined text-primary text-xl">architecture</span>
                            <div class="text-left md:text-center">
                                <p class="text-[10px] font-bold uppercase text-slate-600 dark:text-slate-300">Blueprint</p>
                                <p id="name-blueprint" class="text-[9px] text-slate-400 truncate max-w-[150px] md:max-w-full">PDF/DWG/JPG</p>
                            </div>
                            <input accept=".pdf,.dwg,.jpg,.jpeg,.png" id="upload-blueprint" type="file" class="hidden" onchange="handleMultiFile(this, 'name-blueprint', 'clear-blueprint')" />
                        </label>
                        <button id="clear-blueprint" type="button" onclick="resetSingleInput(event, 'upload-blueprint', 'name-blueprint', 'clear-blueprint', 'PDF/DWG/JPG')" class="hidden absolute top-1 right-1 p-1.5 rounded-md text-slate-400 hover:text-red-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all duration-200 leading-none"><span class="material-symbols-outlined text-[18px]">close</span></button>                    
                    </div>

                    <div class="relative group dropzone-pattern">
                        <label for="upload-quote" class="py-4 md:py-6 px-2 rounded-lg flex flex-row md:flex-col items-center justify-center md:text-center cursor-pointer border border-dashed border-slate-300 dark:border-slate-700 hover:border-primary hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all gap-3 md:gap-1">
                            <span class="material-symbols-outlined text-primary text-xl">request_quote</span>
                            <div class="text-left md:text-center">
                                <p class="text-[10px] font-bold uppercase text-slate-600 dark:text-slate-300">Quotation</p>
                                <p id="name-quote" class="text-[9px] text-slate-400 truncate max-w-[150px] md:max-w-full">Signed PDF</p>
                            </div>
                            <input accept=".pdf,.xlsx,.docx,.txt,.csv" id="upload-quote" type="file" class="hidden" onchange="handleMultiFile(this, 'name-quote', 'clear-quote')" />
                        </label>
                    <button id="clear-quote" type="button" onclick="resetSingleInput(event, 'upload-quote', 'name-quote', 'clear-quote', 'Signed PDF')" class="hidden absolute top-1 right-1 p-1.5 rounded-md text-slate-400 hover:text-red-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all duration-200 leading-none"><span class="material-symbols-outlined text-[18px]">close</span></button>                    </div>

                    <div class="relative group dropzone-pattern">
                        <label for="upload-agreement" class="py-4 md:py-6 px-2 rounded-lg flex flex-row md:flex-col items-center justify-center md:text-center cursor-pointer border border-dashed border-slate-300 dark:border-slate-700 hover:border-primary hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all gap-3 md:gap-1">
                            <span class="material-symbols-outlined text-primary text-xl">draw</span>
                            <div class="text-left md:text-center">
                                <p class="text-[10px] font-bold uppercase text-slate-600 dark:text-slate-300">Agreement</p>
                                <p id="name-agreement" class="text-[9px] text-slate-400 truncate max-w-[150px] md:max-w-full">Digital Signed Agreement PDF</p>
                            </div>
                            <input accept=".pdf,.jpg,.jpeg" id="upload-agreement" type="file" class="hidden" onchange="handleMultiFile(this, 'name-agreement', 'clear-agreement')" />
                        </label>
                    <button id="clear-agreement" type="button" onclick="resetSingleInput(event, 'upload-agreement', 'name-agreement', 'clear-agreement', 'Digital Signed Agreement PDF')" class="hidden absolute top-1 right-1 p-1.5 rounded-md text-slate-400 hover:text-red-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all duration-200 leading-none"><span class="material-symbols-outlined text-[18px]">close</span></button>                    </div>
                </div>

                <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                        <div class="space-y-1.5">
                            <label for="downpayment-amount" class="text-[10px] font-bold text-slate-500 uppercase px-1">Downpayment</label>
                            <div class="relative flex items-center">
                                <span class="absolute left-3 text-slate-400 material-symbols-outlined text-base">payments</span>
                                <input id="downpayment-amount" type="number" placeholder="0.00" class="w-full pl-9 pr-3 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" />
                            </div>
                        </div>

                        <div class="flex flex-col items-end gap-1">
                            <label class="flex flex-row-reverse items-center gap-3 cursor-pointer group">
                                <input id="initial-checkbox" type="checkbox" class="rounded border-slate-300 text-primary h-5 w-5 md:h-4 md:w-4 transition-all cursor-pointer" />
                                
                                <div class="text-right">
                                    <span class="block text-xs md:text-[11px] font-bold text-slate-700 dark:text-slate-200 uppercase tracking-tight">Verified & Received</span>
                                    <span class="block text-[10px] text-slate-400 leading-none">Confirm payment arrival</span>
                                </div>
                            </label>
                        </div>
                    </div>
                    <button data-type="confirm" onclick="confirmBooking(this, ${booking.id})" class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-3 md:py-2.5 rounded-lg transition-all shadow-md active:scale-95 text-sm uppercase tracking-wide">
                        Confirm & Activate Project
                    </button>
                </div>
            </div>
        </div>
            `
    docID.style.display = 'flex'
  },
  complete: (id) => {
    const booking = state.allBookings.find(b => b.id === Number(id));
    console.log(booking)
    const docID = document.getElementById('completeModal')
    docID.innerHTML = `
            <div class="w-full max-w-2xl bg-white dark:bg-slate-900 rounded-xl shadow-2xl overflow-hidden border border-slate-200 dark:border-slate-800">
                <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/20">
                    <div class="flex items-center gap-4">
                        <div>
                            <div class="flex items-center gap-3">
                                <h2 class="text-2xl font-bold text-primary dark:text-emerald-500">Finalize Project</h2>
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 text-[10px] font-bold rounded-full tracking-wider uppercase border border-blue-200 dark:border-blue-800">Active</span>
                            </div>
                            <p class="text-slate-500 dark:text-slate-400 text-sm">Project Handover & Documentation</p>
                        </div>
                    </div>
                    <button class=text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-1 ml-auto">
                        <span class="material-symbols-outlined" onclick="closeModal('completeModal')">close</span>
                    </button>
                </div>
                <div class="p-6 space-y-6 max-h-[500px] overflow-y-auto pr-2 custom-scrollbar">
                    <div class="space-y-3">
                        <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                            Project Documentation (PDF) consisting Images and Client Sign-off Agreement
                        </label>
                        
                        <div class="group relative p-6 rounded-xl flex items-center gap-4 border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-primary/50 transition-all cursor-pointer"
                            onclick="document.getElementById('upload-pdf-doc').click()">
                            
                            <div class="w-12 h-12 rounded-lg bg-red-50 dark:bg-red-900/20 flex items-center justify-center border border-red-100 dark:border-red-800">
                                <span class="material-symbols-outlined text-red-500">picture_as_pdf</span>
                            </div>

                            <div class="flex-1 text-left">
                                <p id="pdf-file-name" class="text-sm font-medium text-slate-600 dark:text-slate-300">
                                    Select specific PDF document
                                </p>
                                <p class="text-xs text-slate-400">Single file only (Max 10MB)</p>
                            </div>

                            <input id="upload-pdf-doc" type="file" class="hidden" accept=".zip,.pdf,.docx,.png" 
                                onchange="handleFileChange(this, 'pdf-file-name', 'clear-pdf-btn')" />

                            <button id="clear-pdf-btn" type="button" 
                                onclick="event.stopPropagation(); clearFileInput(this, 'upload-pdf-doc', 'pdf-file-name', 'Select specific PDF document')" 
                                class="hidden p-2 text-slate-400 hover:text-red-500 transition-colors">
                                <span class="material-symbols-outlined">delete_forever</span>
                            </button>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                            Portfolio Images
                        </label>
                        
                        <div class="group relative p-6 rounded-xl flex items-center gap-4 border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-primary/50 transition-all cursor-pointer"
                            onclick="document.getElementById('upload-portfolio').click()">
                            
                            <div class="w-12 h-12 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center border border-blue-100 dark:border-blue-800">
                                <span class="material-symbols-outlined text-blue-500">image</span>
                            </div>

                            <div class="flex-1 text-left">
                                <p id="portfolio-file-name" class="text-sm font-medium text-slate-600 dark:text-slate-300">
                                    Select portfolio images
                                </p>
                                <p class="text-xs text-slate-400">Multiple files allowed (Max 10MB each)</p>
                            </div>

                            <input id="upload-portfolio" type="file" class="hidden" accept=".png,.jpg,.jpeg" multiple
                                onchange="handleFileChange(this, 'portfolio-file-name', 'clear-portfolio-btn')" />

                            <button id="clear-portfolio-btn" type="button" 
                                onclick="event.stopPropagation(); clearFileInput(this, 'upload-portfolio', 'portfolio-file-name', 'Select portfolio images')" 
                                class="hidden p-2 text-slate-400 hover:text-red-500 transition-colors">
                                <span class="material-symbols-outlined">delete_forever</span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="bg-primary/5 dark:bg-primary/10 p-4 rounded-xl flex justify-between items-center border border-primary/10">
                        <div class="flex flex-col">
                            <span class="text-xs text-slate-500 uppercase font-bold tracking-tight">Initial Estimate</span>
                            <span class="text-lg font-bold text-slate-800 dark:text-slate-100">$${parseFloat(booking.total_amount).toFixed(2)}</span>
                        </div>
                        <div class="flex flex-col items-end">
                            <span class="text-xs text-slate-500 uppercase font-bold tracking-tight">Final Project Cost</span>
                            <div class="relative mt-1">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                                <input id="project-cost" class="pl-7 pr-3 py-1 w-32 rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm font-bold text-primary" type="number"/>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <button data-type="complete" onclick="confirmBooking(this, ${booking.id})" class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-3 rounded-xl transition-all">
                        Finalize & Close Project
                    </button>
                </div>
            </div>`
    docID.style.display = 'flex'
  },
  cancel: (id) => {
    const booking = state.allBookings.find(b => b.id === Number(id));
    const docID = document.getElementById('cancelModal')
    console.log(booking)
    docID.innerHTML = `
            <div class="w-full max-w-2xl bg-white dark:bg-slate-900 rounded-xl shadow-2xl overflow-hidden border border-slate-200 dark:border-slate-800">
                <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-red-50/30 dark:bg-red-900/10">
                    <div class="flex items-center gap-4">
                        <div>
                            <div class="flex items-center gap-3">
                                <h2 class="text-2xl font-bold text-red-600 dark:text-red-500">Project Cancellation</h2>
                                <span class="px-3 py-1 bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 text-[10px] font-bold rounded-full tracking-wider uppercase border border-red-200 dark:border-red-800">Cancellation</span>
                            </div>
                            <p class="text-slate-500 dark:text-slate-400 text-sm">Settlement & Asset Recovery</p>
                        </div>
                    </div>
                    <button class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-1 ml-auto">
                        <span class="material-symbols-outlined" onclick="closeModal('cancelModal')">close</span>
                    </button>
                </div>
                <div class="p-6 space-y-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-500 uppercase">Service Charges ($)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                                <input class="w-full pl-7 rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 focus:ring-red-500 focus:border-red-500" placeholder="0.00" type="number" value="${parseFloat(booking.total_amount).toFixed(2)}" />
                            </div>
                        </div>
                        <div class="space-y-2">
                            <span class="text-xs font-bold text-slate-500 uppercase">Valuation Report</span>
                            
                            <div class="dropzone-pattern rounded-lg border border-slate-200 dark:border-slate-700 flex items-center justify-between gap-2 px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                
                                <label for="file-upload" class="flex items-center gap-2 cursor-pointer flex-1 min-w-0">
                                    <span class="material-symbols-outlined text-red-500 shrink-0">upload_file</span>
                                    <span id="file-name-display" class="text-xs text-slate-400 truncate">Click to upload</span>
                                    <input 
                                        id="file-upload" 
                                        type="file" 
                                        class="hidden" 
                                        onchange="handleFileChange(this, 'file-name-display', 'clear-file')"
                                    />
                                </label>

                                <button 
                                    id="clear-file" 
                                    type="button" 
                                    onclick="clearFileInput(this, 'file-upload', 'file-name-display', 'Click to upload')" 
                                    class="hidden shrink-0 p-1 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-full transition-colors leading-none"
                                >
                                    <span class="material-symbols-outlined text-sm text-slate-400 hover:text-red-500">close</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 uppercase">Reason for Cancellation</label>
                        <textarea id="cancellationReason" class="w-full rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 focus:ring-red-500 focus:border-red-500" placeholder="Please provide detailed reason..." rows="4"></textarea>
                    </div>
                </div>
                <div class="p-6 flex gap-3">
                    <button class="flex-1 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold py-3 rounded-xl hover:bg-slate-200 transition-all">
                        Go Back
                    </button>
                    <button id="cancelBTN" onclick="cancelBooking('${id}')" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-red-600/20">
                        Confirm Cancellation
                    </button>
                </div>
            </div>
            `
    docID.style.display = 'flex'
  },
  invoice: (id) => {
    const booking = state.allBookings.find(b => b.id === Number(id));
    const docID = document.getElementById('invoiceModal')
    docID.innerHTML = `<div id="modal-content" class="bg-white dark:bg-stone-900 w-full max-w-[960px] max-h-[90vh] rounded-2xl shadow-2xl overflow-hidden flex flex-col scale-95 transition-transform duration-300">
            
            <!-- Header Section (Fixed at top) -->
            <header class="shrink-0 flex items-center justify-between border-b border-slate-100 dark:border-stone-800 px-6 py-5 bg-white dark:bg-stone-900">
                <div class="flex items-center gap-4">
                    <div class="flex flex-col md:flex-row md:items-center gap-2 md:gap-4">
                        <h2 class="text-slate-900 dark:text-slate-100 text-xl font-bold leading-tight tracking-tight">Invoice &amp; Transaction History</h2>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-500 dark:text-stone-400 text-sm font-medium">#BK-2026-001</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-primary dark:bg-blue-900/30 uppercase tracking-wider">
                                COMPLETED
                            </span>
                        </div>
                    </div>
                </div>
                <button onclick="closeModal('invoiceModal')" class="p-2 hover:bg-slate-100 dark:hover:bg-stone-800 rounded-lg transition-colors text-slate-400 dark:text-stone-500 group">
                    <span class="material-symbols-outlined block group-hover:rotate-90 transition-transform" data-icon="close">close</span>
                </button>
            </header>

            <!-- Main Content Scrollable Area -->
            <!-- flex-1 and overflow-y-auto allow this middle section to grow and scroll if content overflows -->
            <div class="flex-1 p-6 space-y-8 overflow-y-auto custom-scrollbar">
                
                <!-- Financial Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex flex-col gap-2 rounded-xl p-6 border border-slate-100 dark:border-stone-800 bg-slate-50/50 dark:bg-stone-800/50">
                        <p class="text-slate-500 dark:text-stone-400 text-xs font-bold uppercase tracking-widest">TOTAL PROJECT VALUE</p>
                        <p class="text-slate-900 dark:text-white text-3xl font-bold tracking-tight">$12,450.00</p>
                        <p class="text-slate-400 text-sm font-medium">Baseline estimate</p>
                    </div>
                    
                </div>

                <!-- Transaction History Section -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-slate-900 dark:text-white text-lg font-bold">Transaction History</h3>
                    </div>
                    <div class="overflow-hidden rounded-xl border border-slate-100 dark:border-stone-800">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-stone-800/80 border-b border-slate-100 dark:border-stone-700">
                                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-stone-400 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-stone-400 uppercase tracking-wider">Transaction ID</th>
                                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-stone-400 uppercase tracking-wider">Payment Method</th>
                                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-stone-400 uppercase tracking-wider text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-stone-800" id="transactionTableBody">
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">
                                            <i class="fas fa-spinner fa-spin"></i> Loading transactions...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <!-- Footer Actions (Fixed at bottom) -->
            <footer class="shrink-0 p-6 border-t border-slate-100 dark:border-stone-800 bg-slate-50/30 dark:bg-stone-900/50">
                <div class="flex flex-col sm:flex-row items-center justify-end gap-3" id="invoiceActions">
                    <button class="w-full sm:w-auto flex items-center justify-center gap-2 px-6 h-12 rounded-xl bg-primary text-white font-bold text-sm hover:bg-primary/90 transition-all shadow-lg shadow-primary/20" onclick="window.open('/landscape/USER_API/generate_booking_report.php?id=${booking.id}', '_blank')">
                        <span class="material-symbols-outlined text-lg">download</span>
                        Download PDF Invoice
                    </button>
                </div>
            </footer>
        </div>`

    docID.style.display = 'flex'
    
    // Load invoice and transaction information for this booking
    loadTransactionInformation(booking.id)
  },
  view: (id) => {
    const booking = state.allBookings.find(b => b.id === Number(id));
    console.log(booking)
    const docID = document.getElementById('viewModal')
    docID.setAttribute('data-booking-id', id);
    docID.innerHTML = `<div class="relative w-full max-w-4xl max-h-[90vh] bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden flex flex-col">
      
      <!-- BEGIN: Fixed Header Section -->
      <header class="p-5 md:p-6 border-b border-slate-200 dark:border-slate-700 flex-shrink-0">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <div class="flex items-center gap-2 text-slate-500 dark:text-slate-400 mb-1">
              <span class="material-symbols-outlined text-sm">history</span>
              <span class="text-[10px] font-bold uppercase tracking-widest">Project History</span>
            </div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 dark:text-white">${booking.address || "No Address!!!!"}</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">ID: ${booking.booking_code} • ${capitalize(booking.category)}</p>
          </div>
          <div class="flex items-center gap-3">
            <span class="status-badge ${booking.status.toLowerCase()}">${booking.status}</span>
            <button onclick="closeModal('viewModal')" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-full transition-colors text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
              <span class="material-symbols-outlined">close</span>
            </button>
          </div>
        </div>
      </header>
      <!-- END: Header Section -->

      <!-- BEGIN: Scrollable Content Area -->
      <div class="flex-grow overflow-y-auto no-scrollbar">
        
        <!-- Progress Stepper Section -->
        <section class="p-6 bg-slate-50/50 dark:bg-slate-900/20 border-b border-slate-100 dark:border-slate-700/50">
          <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 relative" id="stepper-root">
            <!-- Step 1 -->
            <div class="step-item flex md:flex-col items-center gap-4 md:gap-2 z-10 flex-1 w-full" data-step="1">
              <div class="step-circle w-9 h-9 rounded-full flex items-center justify-center border-2 stepper-transition" data-purpose="status-indicator">
                <span class="material-symbols-outlined text-lg">mail</span>
              </div>
              <p class="text-xs font-semibold step-label">Quotation</p>
            </div>
            <!-- Step 2 -->
            <div class="step-item flex md:flex-col items-center gap-4 md:gap-2 z-10 flex-1 w-full" data-step="2">
              <div class="step-circle w-9 h-9 rounded-full flex items-center justify-center border-2 stepper-transition" data-purpose="status-indicator">
                <span class="material-symbols-outlined text-lg">check_circle</span>
              </div>
              <p class="text-xs font-semibold step-label">In Progress</p>
            </div>
            <!-- Step 3 -->
            <div class="step-item flex md:flex-col items-center gap-4 md:gap-2 z-10 flex-1 w-full" data-step="3">
              <div class="step-circle w-9 h-9 rounded-full flex items-center justify-center border-2 stepper-transition" data-purpose="status-indicator">
                <span class="material-symbols-outlined text-lg">pending_actions</span>
              </div>
              <p class="text-xs font-semibold step-label">Completed</p>
            </div>
            <!-- Step 4 -->
            <!--<div class="step-item flex md:flex-col items-center gap-4 md:gap-2 z-10 flex-1 w-full" data-step="4">
              <div class="step-circle w-9 h-9 rounded-full flex items-center justify-center border-2 stepper-transition" data-purpose="status-indicator">
                <span class="material-symbols-outlined text-lg">celebration</span>
              </div>
              <p class="text-xs font-semibold step-label">Completed</p>
            </div>-->
            <!-- Background Progress Line (Desktop) -->
            <div class="hidden md:block absolute top-[18px] left-0 w-full h-0.5 bg-slate-200 dark:bg-slate-700 -z-0">
              <div class="h-full bg-primary stepper-transition w-0" id="progress-bar-horizontal"></div>
            </div>
          </div>
        </section>

        <!-- Details Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-0">
          <!-- Documentation Section -->
          <section class="p-6 border-b lg:border-b-0 lg:border-r border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-2 mb-6">
              <span class="material-symbols-outlined text-primary">folder_open</span>
              <h2 class="font-bold text-base">Documentation</h2>
            </div>
            <div class="space-y-3" id="booking-files-container">
              <!-- Files will be dynamically inserted here -->
            </div>
          </section>

          <!-- Portfolio Section -->
          <section class="p-6">
            <div class="flex items-center gap-2 mb-6">
              <span class="material-symbols-outlined text-primary">photo_library</span>
              <h2 class="font-bold text-base">Project Portfolio</h2>
            </div>
            <div class="grid grid-cols-2 gap-3" id="portfolio-images-container">
              <!-- Portfolio images will be dynamically inserted here -->
            </div>
          </section>
        </div>
      </div>
      <!-- END: Scrollable Content Area -->

      <!-- BEGIN: Fixed Footer Section -->
      <footer class="p-5 md:p-6 bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 flex-shrink-0 flex flex-col sm:flex-row justify-between items-center gap-4">
        <p class="text-[10px] text-slate-500 font-medium"><!--Last modified by Admin • Jan 06, 2024--></p>
        <div class="flex gap-2 w-full sm:w-auto">
            <button onclick="closeModal('viewModal')" class="flex-1 sm:flex-none px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl transition-all">
                Cancel
            </button>
            <button onclick="exportBookingReport(this)" class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-6 py-2 bg-primary text-white rounded-xl text-sm font-bold shadow-lg shadow-primary/20 hover:shadow-primary/40 hover:scale-[1.02] transition-all">
                <span class="material-symbols-outlined text-sm">picture_as_pdf</span>
                Export Report
            </button>
        </div>
      </footer>
      <!-- END: Footer Section -->

    </div>
            `
    switch (booking.status) {
      case 'Pending':
        updateProgress(1)
        break;
      case 'Active':
        updateProgress(2)
        break;
      case 'Completed':
        updateProgress(3)
        break;
      case 'Cancelled':
        updateProgress(4)
        break;
    }
    docID.style.display = 'flex'
    
    // Display booking files if available
    if (booking.files && booking.files.length > 0) {
      displayBookingFiles(booking.files);
      displayPortfolioImages(booking.files);
    } else {
      displayBookingFiles([]);
      displayPortfolioImages([]);
    }
  }
}



function multifileHandling(input, textId, clearBtnId, previewId) {
  const textElement = document.getElementById(textId)
  const clearBtn = document.getElementById(clearBtnId)
  const previewContainer = document.getElementById(previewId)
  const container = input.closest('.dropzone-pattern')
  const resetUI = () => {
    input.value = ''
    textElement.innerText = 'Drag and drop or click to upload'
    if (previewContainer) previewContainer.innerHTML = ''
    if (container) container.classList.replace('border-solid', 'border-dashed')
    clearBtn.classList.add('hidden')
  }

  if (input.files && input.files.length > 0) {
    const files = Array.from(input.files)
    const count = input.files.length
    const MAX_FILES = 10
    const ALLOWED_TYPES = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp']

    if (files.length > MAX_FILES) {
      log(`Limit exceeded. Max ${MAX_FILES} images.`, 'warn')
      return resetUI()// Exit function
    }

    const isValidType = files.every(file => ALLOWED_TYPES.includes(file.type))
    if (!isValidType) {
      log('Invalid file format. Use PNG, JPG, or WEBP.', 'error')
      return resetUI()
    }

    textElement.innerText = count === 1 ? input.files[0].name : `${count} files selected`
    clearBtn.classList.remove('hidden')
    if (container) container.classList.replace('border-dashed', 'border-solid')
    log('Files validated successfully', 'success')
  } else {
    resetUI()
    console.log('ℹ️ [INFO]: File input cleared or empty.')
  }
}
window.multifileHandling = multifileHandling

function handleFileChange(input, txtID, btnID) {
  const display = document.getElementById(txtID)
  console.log('input: ', input)
  if (input.files && input.files.length > 0) {
    // Show appropriate text based on number of files
    if (input.files.length === 1) {
      display.textContent = input.files[0].name
    } else {
      display.textContent = `${input.files.length} files selected`
    }
    display.classList.remove('text-slate-400')
    display.classList.add('text-slate-700', 'dark:text-slate-200', 'font-medium')
    document.getElementById(btnID).classList.remove('hidden')
    console.log('done', input.files.length, 'files')
  }
}

window.handleFileChange = handleFileChange

function handleMultiFile(input, textId, btnId) {
  const displayElement = document.getElementById(textId)
  const clearBtn = document.getElementById(btnId)
  const container = input.closest('.dropzone-pattern')
  console.log(container)
  if (input.files && input.files.length > 0) {
    displayElement.textContent = `Selected: ${input.files[0].name}`
    displayElement.classList.replace('text-slate-400', 'text-blue-600')
    displayElement.classList.add('font-bold')

    container.classList.replace('border-dashed', 'border-solid')
    container.classList.replace('border-slate-300', 'border-blue-500')

    clearBtn.classList.remove('hidden')
  }
}
window.handleMultiFile = handleMultiFile



function clearFileInput(btn, inputID, txtID, txtOriginal) {
  const display = document.getElementById(txtID)

  document.getElementById(inputID).value = ''
  display.textContent = txtOriginal
  display.classList.add('text-slate-400')
  display.classList.remove('text-slate-700', 'dark:text-slate-200', 'font-medium')
  btn.classList.add('hidden')
}

window.clearFileInput = clearFileInput


function resetSingleInput(event, inputId, textId, btnId, originalText) {
  // IMPORTANT: Stop the click from bubbling up to the <label>
  event.preventDefault()
  event.stopPropagation()

  const input = document.getElementById(inputId)
  const displayElement = document.getElementById(textId)
  const clearBtn = document.getElementById(btnId)
  const container = input.closest('.dropzone-pattern')

  // Reset Input
  input.value = ''

  // Reset UI
  displayElement.textContent = originalText
  displayElement.classList.replace('text-blue-600', 'text-slate-400')
  displayElement.classList.remove('font-bold')

  container.classList.replace('border-solid', 'border-dashed')
  container.classList.replace('border-blue-500', 'border-slate-300')

  clearBtn.classList.add('hidden')
}


window.resetSingleInput = resetSingleInput


// const reportfileinput = document.getElementById('file-upload');
// reportfileinput.addEventListener('change', (event)=>{
//     const files = event.target.files;
//     if (files.length > 0) {
//         console.log("Selected file:", files[0].name);
//         document.getElementById('file-name-display');
//     }
// })


window.handleModalAction = (type, data) => {
  const action = modalActions[type]
  console.log('clicked  ')
  if (action) {
    action(data)
  } else {
    console.error(`Action type "${type}" is not defined in the list.`)
  }
}

window.confirmAddBooking = async () => {
  try {
    const limitsResponse = await fetch('/landscape/USER_API/test_post_limits.php');
    const limits = await limitsResponse.json();
    console.log('PHP Limits:', limits);
    
    // Warn if limits seem too low
    const postMaxSize = limits.post_max_size;
    if (postMaxSize && parseInt(postMaxSize) < 8) { // Less than 8M
      console.warn('PHP post_max_size is low:', postMaxSize);
    }
  } catch (error) {
    console.warn('Could not check PHP limits:', error);
  }

  // Clear any potential file inputs that might interfere
  const allFileInputs = document.querySelectorAll('input[type="file"]');
  allFileInputs.forEach(input => {
    if (input.form && input.form.id === 'addBookingForm') {
      input.value = ''; // Clear any file selections
    }
  });

  // 1. Gather Data
  const date = document.getElementById('bookingDate').value
  const time = document.getElementById('bookingTime').value
  
  const formData = {
    user_id: document.getElementById('selectedCustomerId').value,
    service_id: document.getElementById('selectedServiceId').value,
    appointment_date: `${date} ${time}:00`, // Send as combined datetime
    address: document.getElementById('address').value.trim(),
    cost: document.getElementById('cost').value.trim(),
    notes: document.getElementById('notes').value.trim()
  }
  console.log('Form Data:', formData)
  console.log('Form Data JSON size:', JSON.stringify(formData).length, 'bytes')

  // 2. Validation Logic
  if (!formData.user_id) return alert('Please select a valid customer from the list.')
  if (!formData.service_id) return alert('Please select a valid service from the list.')
  if (!date || !time) return alert('Please select both a date and a time.')
  if (!formData.address) return alert('Please enter a complete service address.')

  // 3. UI Feedback
  // Since this is a standalone function, we target the button by ID or specific selector
  const submitBtn = document.querySelector('#addBookingForm button[type="button"]') ||
    document.querySelector('#saveBookingBtn')

  if (submitBtn) {
    submitBtn.disabled = true
    submitBtn.textContent = 'Saving...'
  }

  try {
    // 4. Fetch Request
    const jsonBody = JSON.stringify(formData);
    console.log('Request body size:', jsonBody.length, 'bytes');
    console.log('Request URL:', '/landscape/USER_API/BookingsController.php?action=create');
    
    const response = await fetch('/landscape/USER_API/BookingsController.php?action=create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: jsonBody
    })

    console.log('Response status:', response.status);
    console.log('Response headers:', response.headers);

    const result = await response.json()

    if (result.status === 'success') {
      alert('Booking created successfully!')

      // Reset form manually since 'this' no longer refers to the form
      document.getElementById('addBookingForm').reset()

      // Refresh the list
      if (typeof fetchData === 'function') fetchData(1)

      // Close modal if you have a closeModal function
    } else {
      alert('Error: ' + result.message)
    }
  } catch (error) {
    console.error('Submission error:', error)
    alert('Network error. Failed to save booking.')
  } finally {
    if (submitBtn) {
      submitBtn.disabled = false
      submitBtn.textContent = 'Save Booking'
    }
  }
}

window.closeModal = (id) => { document.getElementById(id).style.display = 'none' }

// Add Booking Modal
window.handleAction = (bookingid, actionType) => {
  console.log(`Action: ${actionType} on booking ${bookingid}`)
  console.log('Current state of bookings:', state.allBookings)
  const booking = state.allBookings.find((b) => b.id === bookingid)
  if (!booking) return console.error(`booking ${bookingid} not found.`)
}

function showAddBookingModal() {
  document.getElementById('addBookingModal').style.display = 'flex'
}

window.closeAddBookingModal = () => {
  document.getElementById('addBookingModal').style.display = 'none'
}

window.closeBookingDetailsModal = () => {
  document.getElementById('bookingDetailsModal').style.display = 'none'
}

// Logger utility for future modal integration
const Logger = {
  log: function(message, type = 'info', data = null) {
    console.log(`[${type.toUpperCase()}] ${message}`, data || '');
  },
  
  error: function(message, data = null) {
    this.log(message, 'error', data);
  },
  
  success: function(message, data = null) {
    this.log(message, 'success', data);
  },
  
  warn: function(message, data = null) {
    this.log(message, 'warn', data);
  }
};

// File type validation rules matching backend validation
const FILE_TYPE_RULES = {
  blueprint: {
    extensions: ['pdf', 'dwg', 'jpg', 'png'],
    mimeTypes: ['application/pdf', 'image/vnd.dwg', 'image/jpeg', 'image/png'],
    maxSize: 15 * 1024 * 1024 // 15MB
  },
  quotation: {
    extensions: ['pdf', 'xlsx', 'docx', 'txt', 'csv'],
    mimeTypes: [
      'application/pdf',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'text/plain',
      'text/csv',
      'application/csv'
    ],
    maxSize: 5 * 1024 * 1024 // 5MB
  },
  agreement: {
    extensions: ['pdf', 'jpg'],
    mimeTypes: ['application/pdf', 'image/jpeg'],
    maxSize: 5 * 1024 * 1024 // 5MB
  },
  projectDocumentation: {
    extensions: ['zip', 'pdf', 'docx', 'png'],
    mimeTypes: [
      'application/zip',
      'application/pdf',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'image/png'
    ],
    maxSize: 25 * 1024 * 1024 // 25MB
  },
  portfolio: {
    extensions: ['png', 'jpg', 'jpeg'],
    mimeTypes: ['image/png', 'image/jpeg'],
    maxSize: 10 * 1024 * 1024 // 10MB
  }
};

// File validation function
function validateFileType(file, fileType) {
  const rules = FILE_TYPE_RULES[fileType];
  if (!rules) {
    return { valid: false, error: `Unknown file type: ${fileType}` };
  }

  // Check file size
  if (file.size > rules.maxSize) {
    const maxSizeMB = Math.round(rules.maxSize / (1024 * 1024));
    return { 
      valid: false, 
      error: `${fileType} file too large (max ${maxSizeMB}MB)` 
    };
  }

  // Check file extension
  const fileExtension = file.name.split('.').pop().toLowerCase();
  if (!rules.extensions.includes(fileExtension)) {
    return { 
      valid: false, 
      error: `Invalid ${fileType} file type. Allowed: ${rules.extensions.join(', ')}` 
    };
  }

  // Check MIME type
  if (!rules.mimeTypes.includes(file.type)) {
    return { 
      valid: false, 
      error: `Invalid ${fileType} file format. File type: ${file.type}` 
    };
  }

  return { valid: true };
}

// Booking Actions
window.confirmBooking = async (btn, bookingId) => {
  try {
    Logger.log(`Starting booking confirmation for ID: ${bookingId}`, 'info', { bookingId });
    
    // Sanitize inputs
    const sanitizedBookingId = parseInt(bookingId);
    if (isNaN(sanitizedBookingId) || sanitizedBookingId <= 0) {
      throw new Error('Invalid booking ID');
    }

    const actionType = btn.getAttribute('data-type');
    Logger.log(`Action type: ${actionType}`, 'info', { actionType });
    
    // Create FormData instance for multipart upload
    const formData = new FormData();
    formData.append('id', sanitizedBookingId);

    // Handle different action types with proper sanitization and validation
    if (actionType === 'complete') {
      Logger.log('Processing complete action', 'info');
      
      const projectCost = document.getElementById('project-cost')?.value;
      const sanitizedCost = parseFloat(projectCost);
      
      if (isNaN(sanitizedCost) || sanitizedCost < 0) {
        throw new Error('Invalid project cost');
      }
      
      const projectDocsInput = document.getElementById('upload-pdf-doc');
      const projectDocs = projectDocsInput?.files || [];
      if (projectDocs.length === 0) {
        throw new Error('Project documentation is required');
      }
      
      // Get portfolio images
      const portfolioInput = document.getElementById('upload-portfolio');
      const portfolioFiles = portfolioInput?.files || [];
      
      // Validate project documentation file type
      const projectDocValidation = validateFileType(projectDocs[0], 'projectDocumentation');
      if (!projectDocValidation.valid) {
        throw new Error(projectDocValidation.error);
      }
      
      // Validate portfolio files if any are selected
      if (portfolioFiles.length > 0) {
        for (let i = 0; i < portfolioFiles.length; i++) {
          const portfolioValidation = validateFileType(portfolioFiles[i], 'portfolio');
          if (!portfolioValidation.valid) {
            throw new Error(`Portfolio file ${portfolioFiles[i].name}: ${portfolioValidation.error}`);
          }
        }
      }
      
      // Append data for complete action
      formData.append('amount', sanitizedCost);
      formData.append('projectDocumentation', projectDocs[0]); // Get actual File object
      
      // Append portfolio files
      for (let i = 0; i < portfolioFiles.length; i++) {
        formData.append('portfolioFiles[]', portfolioFiles[i]);
      }
      
      Logger.log('Complete action FormData prepared', 'info', { 
        amount: sanitizedCost,
        docFileName: projectDocs[0]?.name,
        portfolioFileCount: portfolioFiles.length
      });

      closeModal('completeModal');
      
    } else if (actionType === 'confirm') {
      Logger.log('Processing confirm action', 'info');
      
      const checkbox = document.getElementById('initial-checkbox');
      if (!checkbox || !checkbox.checked) {
        throw new Error('Please verify and confirm initial payment.');
      }
      
      // Get file inputs safely and validate
      const blueprintInput = document.getElementById('upload-blueprint');
      const quotationInput = document.getElementById('upload-quote');
      const agreementInput = document.getElementById('upload-agreement');
      
      const blueprint = blueprintInput?.files || [];
      const quotation = quotationInput?.files || [];
      const agreement = agreementInput?.files || [];
      
      // Validate all required files are present
      const missingFiles = [];
      if (blueprint.length === 0) missingFiles.push('Blueprint');
      if (quotation.length === 0) missingFiles.push('Quotation');
      if (agreement.length === 0) missingFiles.push('Agreement');
      
      if (missingFiles.length > 0) {
        throw new Error(`Required files missing: ${missingFiles.join(', ')}`);
      }
      
      // Validate file types
      const blueprintValidation = validateFileType(blueprint[0], 'blueprint');
      if (!blueprintValidation.valid) {
        throw new Error(blueprintValidation.error);
      }
      
      const quotationValidation = validateFileType(quotation[0], 'quotation');
      if (!quotationValidation.valid) {
        throw new Error(quotationValidation.error);
      }
      
      const agreementValidation = validateFileType(agreement[0], 'agreement');
      if (!agreementValidation.valid) {
        throw new Error(agreementValidation.error);
      }
      
      // Append data for confirm action
      formData.append('blueprint', blueprint[0]); // Get actual File object
      formData.append('quotation', quotation[0]); // Get actual File object
      formData.append('agreement', agreement[0]); // Get actual File object
      
      const initialPayment = document.getElementById('downpayment-amount')?.value;
      if (!initialPayment) {
        throw new Error('Initial payment amount is required');
      }
      
      const sanitizedPayment = parseFloat(initialPayment);
      if (isNaN(sanitizedPayment) || sanitizedPayment < 0) {
        throw new Error('Invalid initial payment amount');
      }
      
      formData.append('amount', sanitizedPayment); // Always append amount for confirm action
      
      Logger.log('Confirm action FormData prepared', 'info', { 
        files: [blueprint[0]?.name, quotation[0]?.name, agreement[0]?.name],
        amount: initialPayment 
      });
      closeModal('confirmModal');
      
    } else {
      throw new Error('Invalid action type');
    }

    // Disable button and show loading state
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Processing...';
    
    Logger.log('Sending FormData request to backend', 'info', { 
      url: '/landscape/USER_API/BookingsController.php?action=update',
      actionType 
    });

    // Send FormData to backend
    const url = '/landscape/USER_API/BookingsController.php?action=update';
    
    const response = await fetch(url, {
      method: 'POST',
      // Remove Content-Type header to let browser set multipart boundary automatically
      body: formData
    });

    Logger.log('Response received', 'info', { status: response.status, ok: response.ok });

    if (!response.ok) {
      const errorData = await response.json();
      Logger.error('Backend error response', errorData);
      throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
    }

    const result = await response.json();
    Logger.success('Booking operation completed successfully', result);
    
    // Success handling
    const successMessage = `Booking ID ${sanitizedBookingId} has been successfully ${actionType === 'complete' ? 'completed' : 'confirmed'}.`;
    alert(successMessage);
    Logger.success(successMessage);
    
    // Refresh data if function exists
    if (typeof fetchData === 'function') {
      Logger.log('Refreshing booking data');
      fetchData();
    }

  } catch (error) {
    Logger.error('Booking confirmation failed', { error: error.message, stack: error.stack });
    alert('Error: ' + error.message);
  } finally {
    // Reset button state
    if (btn) {
      btn.disabled = false;
      const actionType = btn.getAttribute('data-type');
      btn.textContent = actionType === 'complete' ? 'Finalize & Close Project' : 'Confirm & Activate Project';
      Logger.log('Button state reset', 'info', { actionType });
    }
  }
}

window.completeBooking = (bookingId) => {
  alert('Booking ' + bookingId + ' has been marked as completed.')
}

window.cancelBooking = (bookingId) => {
  if (confirm('Are you sure you want to cancel this booking?')) {
    confirmCancel(bookingId)
    alert('Booking ' + bookingId + ' has been cancelled.')
  }
}
/**
 * Generic function to send status updates to the API
 */
async function updateBookingStatus(url, data) {
  const response = await fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(data),
  })

  // Parse the JSON response
  const result = await response.json()

  if (!response.ok || result.status !== 'success') {
    throw new Error(result.message || 'Failed to update booking')
  }

  return result
}

// Function to display booking files dynamically
function displayBookingFiles(files) {
  const container = document.getElementById('booking-files-container');
  if (!container) return;
  
  if (!files || files.length === 0) {
    container.innerHTML = `
      <div class="text-center py-8 text-slate-500 dark:text-slate-400">
        <span class="material-symbols-outlined text-2xl mb-2">folder_open</span>
        <p class="text-sm">No files uploaded yet</p>
      </div>
    `;
    return;
  }
  
  const fileIcons = {
    'blueprint': 'architecture',
    'quotation': 'request_quote', 
    'agreement': 'description',
    'projectDocumentation': 'folder_open',
    'portfolio': 'image'
  };
  
  const fileColors = {
    'blueprint': 'bg-red-50 dark:bg-red-900/20 text-red-600',
    'quotation': 'bg-blue-50 dark:bg-blue-900/20 text-blue-600',
    'agreement': 'bg-green-50 dark:bg-green-900/20 text-green-600',
    'projectDocumentation': 'bg-purple-50 dark:bg-purple-900/20 text-purple-600',
    'portfolio': 'bg-orange-50 dark:bg-orange-900/20 text-orange-600'
  };
  
  const fileLabels = {
    'blueprint': 'Blueprint',
    'quotation': 'Quotation',
    'agreement': 'Agreement', 
    'projectDocumentation': 'Documentation',
    'portfolio': 'Portfolio Images'
  };
  
  container.innerHTML = files.map(file => {
    const icon = fileIcons[file.file_type] || 'description';
    const colorClass = fileColors[file.file_type] || 'bg-slate-50 dark:bg-slate-900/20 text-slate-600';
    const label = fileLabels[file.file_type] || file.file_type;
    const fileSize = file.file_size ? `${(file.file_size / (1024 * 1024)).toFixed(1)} MB` : 'Unknown size';
    const uploadDate = file.uploaded_at ? formatToCalendar(file.uploaded_at) : 'Unknown date';
    
    return `
      <div class="flex items-start gap-4 p-3 rounded-xl border border-transparent hover:border-slate-200 dark:hover:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-all cursor-pointer group"
           onclick="downloadFile('${file.file_path}', '${file.original_name}', '${file.file_type}')">
        <div class="p-2 ${colorClass} rounded-lg">
          <span class="material-symbols-outlined">${icon}</span>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium truncate">${label}</p>
          <p class="text-[10px] text-slate-500">${uploadDate} • ${fileSize}</p>
          <p class="text-[9px] text-slate-400 mt-1">${label}</p>
        </div>
        <span class="material-symbols-outlined text-slate-400 text-lg group-hover:text-primary transition-colors">download</span>
      </div>
    `;
  }).join('');
}

// Function to download files
window.downloadFile = (filePath, originalName, fileType) => {
  const downloadName = `${fileType}.${originalName.split('.').pop()}`;
  const downloadUrl = `/landscape/USER_API/download.php?file=${encodeURIComponent(filePath)}&name=${encodeURIComponent(downloadName)}`;
  
  // Create a temporary link to trigger download
  const link = document.createElement('a');
  link.href = downloadUrl;
  link.download = downloadName;
  link.style.display = 'none';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  
  Logger.log(`Downloading file: ${downloadName}`, 'info', { filePath, fileType });
}

// Function to display portfolio images dynamically
function displayPortfolioImages(files) {
  const container = document.getElementById('portfolio-images-container');
  if (!container) return;
  
  if (!files || files.length === 0) {
    container.innerHTML = `
      <div class="col-span-2 text-center py-8 text-slate-500 dark:text-slate-400">
        <span class="material-symbols-outlined text-2xl mb-2">photo_library</span>
        <p class="text-sm">No portfolio images uploaded yet</p>
      </div>
    `;
    return;
  }
  
  // Filter only portfolio files
  const portfolioFiles = files.filter(file => file.file_type === 'portfolio');
  
  if (portfolioFiles.length === 0) {
    container.innerHTML = `
      <div class="col-span-2 text-center py-8 text-slate-500 dark:text-slate-400">
        <span class="material-symbols-outlined text-2xl mb-2">photo_library</span>
        <p class="text-sm">No portfolio images uploaded yet</p>
      </div>
    `;
    return;
  }
  
  container.innerHTML = portfolioFiles.map((file, index) => {
    const imageUrl = `/landscape/USER_API/download.php?file=${encodeURIComponent(file.file_path)}&view=1`;
    
    return `
      <div class="relative group aspect-square rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-700 shadow-inner">
        <img alt="Portfolio image ${index + 1}" class="w-full h-full object-cover" src="${imageUrl}" />
        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
          <button onclick="window.open('${imageUrl}', '_blank')" class="text-white bg-white/20 p-2 rounded-full backdrop-blur-sm hover:bg-white/30 transition-colors">
            <span class="material-symbols-outlined">zoom_in</span>
          </button>
        </div>
      </div>
    `;
  }).join('');
}

window.displayPortfolioImages = displayPortfolioImages;

// Function to export booking report as PDF
window.exportBookingReport = async(btn) => {
  const originalContent = btn.innerHTML;

  try {
    // Get current booking ID from the modal
    const bookingId = document.getElementById('viewModal').getAttribute('data-booking-id');
    
    if (!bookingId) {
      throw new Error('Booking ID not found');
    }
    
    // Show loading state
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined animate-spin">hourglass_empty</span> Generating...';
    
    // Fetch booking data including files and transactions
    const response = await fetch(`/landscape/USER_API/generate_booking_report.php?id=${bookingId}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json'
      }
    });
    
    if (!response.ok) {
      throw new Error('Failed to generate report');
    }
    
    // Create blob from response and download
    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `booking_report_${bookingId}.pdf`;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
    
    Logger.log(`Booking report generated for booking ${bookingId}`, 'info');
    
  } catch (error) {
    Logger.log('Error generating booking report: ' + error.message, 'error');
    alert('Failed to generate report. Please try again.');
  } finally {
    // Restore button state
    btn.disabled = false;
    btn.innerHTML = originalContent;
  }
}


/**
 * Specific function to trigger a cancellation
 */

async function confirmCancel(bookingId) {
  const btn = document.querySelector('#cancelBTN')

  // 1. Prepare UI Feedback
  if (btn) {
    btn.disabled = true
    btn.textContent = 'Processing...'
  }

  try {
    // 2. Define API endpoint and payload
    const url = '/landscape/USER_API/cancelled_booking.php'
    const payload = {
      id: bookingId,
      notes: document.getElementById('cancellationReason').value.trim(),
    }

    // 3. Call the generic fetch function
    await updateBookingStatus(url, payload)

    // 4. Success Handling
    alert(`Booking ID ${bookingId} has been confirmed.`)

    if (typeof fetchData === 'function') {
      fetchData()
    }

  } catch (error) {
    console.error('Confirmation failed:', error)
    alert('Error: ' + error.message)
  } finally {
    // 5. Reset UI
    if (btn) {
      btn.disabled = false
      btn.innerHTML = '<i class=\'fas fa-check\'></i> Confirmed'
    }
  }
}


// Close modals when clicking outside
document.querySelectorAll('.modal-overlay').forEach((modal) => {
  modal.addEventListener('click', function (e) {
    if (e.target === this) {
      this.style.display = 'none'
    }
  })
})

window.showAddBookingModal = showAddBookingModal // Expose to global scop

window.updateProgress = (activeStage) => {
  const steps = document.querySelectorAll('.step-item');
  const progressBar = document.getElementById('progress-bar-horizontal');
  const isCancelled = activeStage === 4;

  steps.forEach((step, index) => {
    const stageNum = index + 1;
    const circle = step.querySelector('.step-circle');
    const label = step.querySelector('.step-label');

    // 1. Reset classes to "Pending" as baseline
    circle.classList.remove('bg-primary', 'border-primary', 'bg-red-500', 'border-red-500', 'text-white');
    circle.classList.add('bg-slate-100', 'dark:bg-slate-700', 'text-slate-400');
    label.classList.remove('text-primary', 'text-red-500');
    label.classList.add('text-slate-400');

    // 2. Apply "Active/Completed" or "Cancelled" styles
    if (isCancelled && stageNum === activeStage) {
      // Only the 4th circle turns Red
      circle.classList.add('bg-red-500', 'border-red-500', 'text-white');
      label.classList.add('text-red-500');
      circle.classList.remove('bg-slate-100', 'text-slate-400');
    } else if (!isCancelled && stageNum <= activeStage) {
      // Normal Progress
      circle.classList.add('bg-primary', 'border-primary', 'text-white');
      label.classList.add('text-primary');
      circle.classList.remove('bg-slate-100', 'text-slate-400');
    }
  });

  // 3. Progress Bar Width Logic
  if (progressBar) {
    // If cancelled, maybe keep the bar at the previous step's width (66%) 
    // or keep it at 100% but red. 
    const displayStage = isCancelled ? 3 : activeStage;
    const percentage = ((displayStage - 1) / (steps.length - 1)) * 100;

    progressBar.style.width = `${percentage}%`;
    progressBar.classList.toggle('bg-red-500', isCancelled);
    progressBar.classList.toggle('bg-primary', !isCancelled);
  }
}




async function loadData() {
  const customerlist = document.getElementById('customerList')
  const serviceList = document.getElementById('serviceList')

  try {
    const response = await fetch('/landscape/USER_API/get_customers.php')
    const data = await response.json()

    console.log('loaded data: ', data)
    // Clear and Populate the datalist
    customerlist.innerHTML = data.customers.map(user =>
      `<option value="${user.name} (${user.email})" data-id="${user.id}">`
    ).join('')
    serviceList.innerHTML = data.services.map(s =>
      `<option value="${s.service_name}" data-id="${s.id}">${s.service_name.split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')} - $${s.basePrice}</option>`
    ).join('')

    const filterOptions = [
      '<option value="all" data-id="0">All Services</option>',
      ...data.services.map(s => `<option value="${s.id}" data-id="${s.id}">${capitalize(s.service_name)}</option>`)
    ].join('')
    document.getElementById('serviceFilter').innerHTML = filterOptions;
  } catch (error) {
    console.error('Error loading customers:', error)
  }
}




// Reusable helper function for dropdown selection handling
function setupDropdownListener(inputId, optionsId, hiddenInputId) {
  const input = document.getElementById(inputId)
  if (!input) return

  input.addEventListener('input', function () {
    const options = document.querySelectorAll(`#${optionsId} option`)
    const hiddenInput = document.getElementById(hiddenInputId)
    
    if (!hiddenInput) return

    // Find the option that matches the input text
    const selectedOption = Array.from(options).find(opt => opt.value === this.value)
    
    if (selectedOption) {
      hiddenInput.value = selectedOption.getAttribute('data-id')
      console.log(`Selected ID for ${inputId}:`, hiddenInput.value)
    } else {
      hiddenInput.value = '' // Reset if text doesn't match an option
    }
  })
}

// Initialize dropdown listeners
setupDropdownListener('customerSearch', 'customerList', 'selectedCustomerId')
setupDropdownListener('serviceSearch', 'serviceList', 'selectedServiceId')

// Load transaction information for a booking
async function loadTransactionInformation(bookingId) {
  try {
    const response = await fetch(`/landscape/USER_API/transactions.php?booking_id=${bookingId}`);
    const result = await response.json();
    
    if (result.status === 'success' && result.transactions) {
      const transactionTableBody = document.getElementById('transactionTableBody');
      
      if (result.transactions.length === 0) {
        transactionTableBody.innerHTML = `
          <tr>
            <td colspan="4" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">
              No transactions found for this booking.
            </td>
          </tr>
        `;
        return;
      }
      
      transactionTableBody.innerHTML = result.transactions.map(transaction => `
        <tr class="hover:bg-slate-50/50 dark:hover:bg-stone-800/30 transition-colors">
          <td class="px-6 py-4 text-sm text-slate-600 dark:text-stone-300">${new Date(transaction.transaction_date).toLocaleDateString()}</td>
          <td class="px-6 py-4 text-sm font-medium text-slate-900 dark:text-stone-100">${transaction.transaction_code}</td>
          <td class="px-6 py-4 text-sm text-slate-600 dark:text-stone-300">
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-slate-400">${getPaymentMethodIcon(transaction.description)}</span>
              ${getPaymentMethodText(transaction.description)}
            </div>
          </td>
          <td class="px-6 py-4 text-sm font-bold text-slate-900 dark:text-stone-100 text-right">$${parseFloat(transaction.amount).toFixed(2)}</td>
        </tr>
      `).join('');
      
    } else {
      const transactionTableBody = document.getElementById('transactionTableBody');
      transactionTableBody.innerHTML = `
        <tr>
          <td colspan="4" class="px-6 py-8 text-center text-red-500">
            Failed to load transactions: ${result.message || 'Unknown error'}
          </td>
        </tr>
      `;
    }
  } catch (error) {
    console.error('Load transaction information error:', error);
    const transactionTableBody = document.getElementById('transactionTableBody');
    transactionTableBody.innerHTML = `
      <tr>
        <td colspan="4" class="px-6 py-8 text-center text-red-500">
          Failed to load transactions. Please try again.
        </td>
      </tr>
    `;
  }
}

// Helper functions for transaction display
function getPaymentMethodIcon(description) {
  if (description.toLowerCase().includes('credit')) return 'credit_card';
  if (description.toLowerCase().includes('bank')) return 'account_balance';
  if (description.toLowerCase().includes('paypal')) return 'account_balance_wallet';
  if (description.toLowerCase().includes('cash')) return 'payments';
  return 'payments'; // default
}

function getPaymentMethodText(description) {
  if (description.toLowerCase().includes('credit')) return 'Credit Card';
  if (description.toLowerCase().includes('bank')) return 'Bank Transfer';
  if (description.toLowerCase().includes('paypal')) return 'PayPal';
  if (description.toLowerCase().includes('cash')) return 'Cash Deposit';
  return description; // default to original text
}


// Reusable helper function for filter setup
function setupFilter(elementId, changeHandler) {
  const element = document.getElementById(elementId)
  if (element) {
    element.addEventListener('change', changeHandler)
  }
}

// Reusable helper for select-based filters
function setupSelectFilter(elementId, stateProperty, getValueCallback) {
  setupFilter(elementId, () => {
    const element = document.getElementById(elementId)
    const selectedOption = element.options[element.selectedIndex]
    state[stateProperty] = getValueCallback(selectedOption)
    fetchData(1)
  })
}

// Reusable helper for input-based filters  
function setupInputFilter(elementId, stateProperty) {
  setupFilter(elementId, () => {
    const element = document.getElementById(elementId)
    state[stateProperty] = element.value
    fetchData(1)
  })
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('#userTabsContainer .tab').forEach((tabElement) => {
    tabElement.addEventListener('click', (event) => {
      const tabName = tabElement.getAttribute('data-tab')
      state.status = tabName
      switchTab(tabName, event, fetchData)
    })
  })

  // Setup filters using DRY helper functions
  setupInputFilter('dateFilter', 'dateFrom')
  
  setupSelectFilter('serviceFilter', 'category', (selectedOption) => 
    selectedOption.getAttribute('data-id')
  )
  
  setupSelectFilter('sortFilter', 'order', (selectedOption) => 
    selectedOption.value
  )

  fetchData(1)
  loadData()
})