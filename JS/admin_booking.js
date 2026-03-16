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

  const apiURL = `/landscape/USER_API/bookings.php?${params.toString()}`

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
    renderPagination(fetchData, data.summary.filtered, state)
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
        <button class="btn btn-secondary btn-small" onclick="handleModalAction('view','${booking.id}' )">
          <i class="fas fa-file-invoice"></i> Invoice
        </button>`
    } else if (statusLower === 'cancelled') {
      doc += `
        <button class="btn btn-primary btn-small" onclick="handleModalAction('rebook', '${booking.id}')">
          <i class="fas fa-redo"></i> Rebook
        </button>`
    }

    doc += '</div></div>' // Close footer and card
  })

  container.innerHTML = doc
}

// Booking Details Modal
function showBookingDetails(booking) {
  const modal = document.getElementById('bookingDetailsModal')
  modal.style.display = 'flex'
  modal.innerHTML = `
        <div class="modal modal-large">
        <div class="modal-header">
          <h3>
            <i
              class="fas fa-calendar-alt"
              style="color: var(--primary-green)"
            ></i>
            Booking Details
          </h3>
          <button class="modal-close" onclick="closeBookingDetailsModal()">
            &times;
          </button>
        </div>
        <div class="modal-body">
          <div
            style="
              display: flex;
              justify-content: space-between;
              align-items: center;
              margin-bottom: 1.5rem;
              padding: 1rem;
              background-color: #f8fafc;
              border-radius: 8px;
            "
          >
            <div>
              <p style="color: var(--text-gray); font-size: 0.85rem">
                Booking ID
              </p>
              <p style="font-weight: 600; color: var(--text-dark)">
                #${booking.booking_code}
              </p>
            </div>
            <span class="status-badge ${booking.status.toLowerCase()}">${booking.status}</span>
          </div>
          <div
            style="
              display: grid;
              grid-template-columns: 1fr 1fr;
              gap: 1.5rem;
              margin-bottom: 1.5rem;
            "
          >
            <div>
              <h4 style="margin-bottom: 0.75rem; color: var(--text-dark)">
                Customer Information
              </h4>
              <div class="table-user" style="margin-bottom: 0.75rem">
                <img
                  src="${booking.avatar_url}"
                  alt="${booking.name}"
                  title="${booking.name}"
                />
                <div class="table-user-info">
                  <h4>${booking.name}</h4>
                  <p>${booking.email}/p>
                </div>
              </div>
              <p style="margin-bottom: 0.5rem">
                <i
                  class="fas fa-phone"
                  style="width: 20px; color: var(--text-gray)"
                ></i>
                ${booking.phone_number || 'Please Provide Phone Number'}
              </p>
            </div>
            <div>
              <h4 style="margin-bottom: 0.75rem; color: var(--text-dark)">
                Service Details
              </h4>
              <p style="margin-bottom: 0.5rem">
                <i
                  class="fas fa-tools"
                  style="width: 20px; color: var(--text-gray)"
                ></i>
               ${capitalize(booking.category || '')} Service
              </p>
              <p style="margin-bottom: 0.5rem">
                <i
                  class="fas fa-calendar"
                  style="width: 20px; color: var(--text-gray)"
                ></i>
                ${formatToCalendar(booking.appointment_date)}
              </p>
              <p style="margin-bottom: 0.5rem">
                <i
                  class="fas fa-map-marker-alt"
                  style="width: 20px; color: var(--text-gray)"
                ></i>
                ${booking.address}
              </p>
              <p>
                <i
                  class="fas fa-dollar-sign"
                  style="width: 20px; color: var(--text-gray)"
                ></i>
                $${booking.total_amount.toFixed(2)}
              </p>
            </div>
          </div>
          <div>
            <h4 style="margin-bottom: 0.75rem; color: var(--text-dark)">
              Special Instructions
            </h4>
            <p
              style="
                padding: 1rem;
                background-color: #f8fafc;
                border-radius: 8px;
                color: var(--text-gray);
              "
            >
              ${booking.notes || 'Add notes here!'}
            </p>
          </div>
        </div>
        <div class="modal-footer">
          <button
            class="btn btn-small"
            style="background-color: #f1f5f9; color: var(--text-dark)"
            onclick="closeBookingDetailsModal()"
          >
            Close
          </button>
          <button
            class="btn btn-primary btn-small"
            onclick="confirmBooking('${booking.booking_code}')"
          >
            <i class="fas fa-check"></i> Confirm
          </button>
          <button
            class="btn btn-danger btn-small"
            onclick="open('${booking.booking_code}')"
          >
            <i class="fas fa-times"></i> Cancel
          </button>
        </div>
      </div>
        `

}

const modalActions = {
  confirm: (data) => {
    console.log('Activating Project:', data)
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
                            <input id="upload-blueprint" type="file" class="hidden" onchange="handleMultiFile(this, 'name-blueprint', 'clear-blueprint')" />
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
                            <input id="upload-quote" type="file" class="hidden" onchange="handleMultiFile(this, 'name-quote', 'clear-quote')" />
                        </label>
                    <button id="clear-quote" type="button" onclick="resetSingleInput(event, 'upload-quote', 'name-quote', 'clear-quote', 'Signed PDF')" class="hidden absolute top-1 right-1 p-1.5 rounded-md text-slate-400 hover:text-red-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all duration-200 leading-none"><span class="material-symbols-outlined text-[18px]">close</span></button>                    </div>

                    <div class="relative group dropzone-pattern">
                        <label for="upload-agreement" class="py-4 md:py-6 px-2 rounded-lg flex flex-row md:flex-col items-center justify-center md:text-center cursor-pointer border border-dashed border-slate-300 dark:border-slate-700 hover:border-primary hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all gap-3 md:gap-1">
                            <span class="material-symbols-outlined text-primary text-xl">draw</span>
                            <div class="text-left md:text-center">
                                <p class="text-[10px] font-bold uppercase text-slate-600 dark:text-slate-300">Agreement</p>
                                <p id="name-agreement" class="text-[9px] text-slate-400 truncate max-w-[150px] md:max-w-full">Digital Sign</p>
                            </div>
                            <input id="upload-agreement" type="file" class="hidden" onchange="handleMultiFile(this, 'name-agreement', 'clear-agreement')" />
                        </label>
                    <button id="clear-agreement" type="button" onclick="resetSingleInput(event, 'upload-agreement', 'name-agreement', 'clear-agreement', 'Digital Sign')" class="hidden absolute top-1 right-1 p-1.5 rounded-md text-slate-400 hover:text-red-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all duration-200 leading-none"><span class="material-symbols-outlined text-[18px]">close</span></button>                    </div>
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
                                <input type="checkbox" class="rounded border-slate-300 text-primary h-5 w-5 md:h-4 md:w-4 transition-all cursor-pointer" />
                                
                                <div class="text-right">
                                    <span class="block text-xs md:text-[11px] font-bold text-slate-700 dark:text-slate-200 uppercase tracking-tight">Verified & Received</span>
                                    <span class="block text-[10px] text-slate-400 leading-none">Confirm payment arrival</span>
                                </div>
                            </label>
                        </div>
                    </div>
                    <button class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-3 md:py-2.5 rounded-lg transition-all shadow-md active:scale-95 text-sm uppercase tracking-wide">
                        Confirm & Activate Project
                    </button>
                </div>
            </div>
        </div>
            `
    docID.style.display = 'flex'
  },
  complete: (data) => {
    console.log('Finalizing Handover for:', data)
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
                            Final Project Portfolio (Images)
                        </label>
                        
                        <div class="group relative dropzone-pattern p-8 rounded-xl flex flex-col items-center justify-center text-center border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-primary/50 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all duration-300 cursor-pointer"
                            onclick="document.getElementById('upload-final-project').click()">
                            
                            <div class="w-16 h-16 mb-4 rounded-full bg-primary/10 flex items-center justify-center border-2 border-primary/20 group-hover:scale-110 transition-transform duration-200">
                                <span class="material-symbols-outlined text-primary text-3xl">cloud_upload</span>
                            </div>

                            <div class="space-y-1">
                                <p id="final-project-text" class="text-sm font-medium text-slate-600 dark:text-slate-300">
                                    Click to upload or drag and drop
                                </p>
                                <p class="text-xs text-slate-400">PNG, JPG or WEBP (Max. 10 files)</p>
                            </div>

                            <input id="upload-final-project" type="file" class="hidden" multiple accept=".png, .jpg, .jpeg, .webp" onchange="multifileHandling(this, 'final-project-text', 'clear-project-images', 'preview-container')" />
                            <div id="preview-container" class="grid grid-cols-5 gap-2 mt-4"></div>
                            <button id="clear-project-images" type="button" onclick="event.stopPropagation(); resetSingleInput(event, 'upload-final-project', 'final-project-text', 'clear-project-images', 'Click to upload or drag and drop')" class="hidden absolute top-3 right-3 p-2 hover:text-red-500 transition-all duration-200">
                                <span class="material-symbols-outlined text-sm">close</span>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                            Project Documentation (PDF) w/ Client Sign-off Agreement
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

                            <input id="upload-pdf-doc" type="file" class="hidden" accept=".pdf" 
                                onchange="handleFileChange(this, 'pdf-file-name', 'clear-pdf-btn')" />

                            <button id="clear-pdf-btn" type="button" 
                                onclick="event.stopPropagation(); clearFileInput(this, 'upload-pdf-doc', 'pdf-file-name', 'Select specific PDF document')" 
                                class="hidden p-2 text-slate-400 hover:text-red-500 transition-colors">
                                <span class="material-symbols-outlined">delete_forever</span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="bg-primary/5 dark:bg-primary/10 p-4 rounded-xl flex justify-between items-center border border-primary/10">
                        <div class="flex flex-col">
                            <span class="text-xs text-slate-500 uppercase font-bold tracking-tight">Initial Estimate</span>
                            <span class="text-lg font-bold text-slate-800 dark:text-slate-100">$4,250.00</span>
                        </div>
                        <div class="flex flex-col items-end">
                            <span class="text-xs text-slate-500 uppercase font-bold tracking-tight">Final Project Cost</span>
                            <div class="relative mt-1">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                                <input class="pl-7 pr-3 py-1 w-32 rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm font-bold text-primary" type="number" value="4380.00" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <button class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-3 rounded-xl transition-all">
                        Finalize & Close Project
                    </button>
                </div>
            </div>`
    docID.style.display = 'flex'
  },
  cancel: (id) => {
    const booking = state.allBookings.find((b) => b.id === id)
    console.log(`Cancelling Project. Reason: ${parseFloat(booking.total_amount).toFixed(2)}`)
    const docID = document.getElementById('cancelModal')
    docID.innerHTML =`
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
  rebook: (data) => {
    console.log('Rescheduling to:', data)
    const docID = document.getElementById('rebookModal')
    docID.innerHTML =`
            <div class="w-full max-w-md bg-white dark:bg-slate-900 rounded-xl shadow-2xl overflow-hidden border border-slate-200 dark:border-slate-800">
                <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/20">
                    <div class="flex items-center gap-3">
                        <h2 class="text-xl font-bold text-primary dark:text-emerald-500">Schedule Rebooking</h2>
                        <span class="px-3 py-1 bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400 text-[10px] font-bold rounded-full tracking-wider uppercase border border-purple-200 dark:border-purple-800">Rescheduling</span>
                    </div>
                    <button class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-1 ml-auto">
                        <span class="material-symbols-outlined" onclick="closeModal('rebookModal')">close</span>
                    </button>
                </div>
                <div class="p-6 space-y-6">
                    <div class="space-y-4">
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-500 uppercase">New Date</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">calendar_month</span>
                                <input class="w-full pl-10 rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900" type="date" />
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-500 uppercase">Start Time</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">schedule</span>
                                <input class="w-full pl-10 rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900" type="time" />
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800 rounded-xl">
                        <div class="flex flex-col">
                            <span class="text-sm font-semibold">Carry Over Previous Blueprint</span>
                            <span class="text-xs text-slate-500">Use existing designs & quotes</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input checked="" class="sr-only peer" type="checkbox" value="" />
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
                        </label>
                    </div>
                </div>
                <div class="p-6 pt-0">
                    <button class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-3 rounded-xl transition-all">
                        Schedule Rebooking
                    </button>
                </div>
            </div>
            `
    docID.style.display = 'flex'
  },
  view: (data) => {
    console.log('Opening History View for:', data)
    const docID = document.getElementById('viewModal')
    docID.innerHTML =`<div class="relative w-full max-w-4xl max-h-[90vh] bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden flex flex-col">
      
      <!-- BEGIN: Fixed Header Section -->
      <header class="p-5 md:p-6 border-b border-slate-200 dark:border-slate-700 flex-shrink-0">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <div class="flex items-center gap-2 text-slate-500 dark:text-slate-400 mb-1">
              <span class="material-symbols-outlined text-sm">history</span>
              <span class="text-[10px] font-bold uppercase tracking-widest">Project History</span>
            </div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 dark:text-white">Willow Creek Estate</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">ID: GS-2024-0089 • Residential Landscaping</p>
          </div>
          <div class="flex items-center gap-3">
            <span class="px-3 py-1 bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 text-xs font-bold rounded-full uppercase">Completed</span>
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
              <p class="text-xs font-semibold step-label">Inquiry</p>
            </div>
            <!-- Step 2 -->
            <div class="step-item flex md:flex-col items-center gap-4 md:gap-2 z-10 flex-1 w-full" data-step="2">
              <div class="step-circle w-9 h-9 rounded-full flex items-center justify-center border-2 stepper-transition" data-purpose="status-indicator">
                <span class="material-symbols-outlined text-lg">check_circle</span>
              </div>
              <p class="text-xs font-semibold step-label">Confirmed</p>
            </div>
            <!-- Step 3 -->
            <div class="step-item flex md:flex-col items-center gap-4 md:gap-2 z-10 flex-1 w-full" data-step="3">
              <div class="step-circle w-9 h-9 rounded-full flex items-center justify-center border-2 stepper-transition" data-purpose="status-indicator">
                <span class="material-symbols-outlined text-lg">pending_actions</span>
              </div>
              <p class="text-xs font-semibold step-label">In Progress</p>
            </div>
            <!-- Step 4 -->
            <div class="step-item flex md:flex-col items-center gap-4 md:gap-2 z-10 flex-1 w-full" data-step="4">
              <div class="step-circle w-9 h-9 rounded-full flex items-center justify-center border-2 stepper-transition" data-purpose="status-indicator">
                <span class="material-symbols-outlined text-lg">celebration</span>
              </div>
              <p class="text-xs font-semibold step-label">Completed</p>
            </div>
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
            <div class="space-y-3">
              <div class="flex items-start gap-4 p-3 rounded-xl border border-transparent hover:border-slate-200 dark:hover:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-all cursor-pointer">
                <div class="p-2 bg-red-50 dark:bg-red-900/20 text-red-600 rounded-lg">
                  <span class="material-symbols-outlined">picture_as_pdf</span>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium truncate">Master_Blueprint_Final.pdf</p>
                  <p class="text-[10px] text-slate-500">Oct 12, 2023 • 4.2 MB</p>
                </div>
                <span class="material-symbols-outlined text-slate-400 text-lg">download</span>
              </div>
              <div class="flex items-start gap-4 p-3 rounded-xl border border-transparent hover:border-slate-200 dark:hover:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-all cursor-pointer">
                <div class="p-2 bg-blue-50 dark:bg-blue-900/20 text-blue-600 rounded-lg">
                  <span class="material-symbols-outlined">request_quote</span>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium truncate">Official_Quotation_v2.pdf</p>
                  <p class="text-[10px] text-slate-500">Oct 14, 2023 • 1.1 MB</p>
                </div>
                <span class="material-symbols-outlined text-slate-400 text-lg">download</span>
              </div>
              <!-- Added placeholder to demonstrate scrolling -->
              <div class="flex items-start gap-4 p-3 rounded-xl border border-transparent hover:border-slate-200 dark:hover:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-all cursor-pointer">
                <div class="p-2 bg-green-50 dark:bg-green-900/20 text-green-600 rounded-lg">
                  <span class="material-symbols-outlined">description</span>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium truncate">Site_Inspection_Report.pdf</p>
                  <p class="text-[10px] text-slate-500">Sep 28, 2023 • 0.8 MB</p>
                </div>
                <span class="material-symbols-outlined text-slate-400 text-lg">download</span>
              </div>
            </div>
          </section>

          <!-- Portfolio Section -->
          <section class="p-6">
            <div class="flex items-center gap-2 mb-6">
              <span class="material-symbols-outlined text-primary">photo_library</span>
              <h2 class="font-bold text-base">Project Portfolio</h2>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div class="relative group aspect-square rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-700 shadow-inner">
                <img alt="Project view 1" class="w-full h-full object-cover" src="https://images.unsplash.com/photo-1558905730-3446059e74d7?auto=format&fit=crop&q=80&w=400" />
                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                  <button class="text-white bg-white/20 p-2 rounded-full backdrop-blur-sm"><span class="material-symbols-outlined">zoom_in</span></button>
                </div>
              </div>
              <div class="relative group aspect-square rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-700 shadow-inner">
                <img alt="Project view 2" class="w-full h-full object-cover" src="https://images.unsplash.com/photo-1585320806297-9794b3e4eeae?auto=format&fit=crop&q=80&w=400" />
                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                  <button class="text-white bg-white/20 p-2 rounded-full backdrop-blur-sm"><span class="material-symbols-outlined">zoom_in</span></button>
                </div>
              </div>
              <div class="relative group aspect-square rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-700 shadow-inner">
                <img alt="Project view 3" class="w-full h-full object-cover" src="https://images.unsplash.com/photo-1598902108854-10e335adac99?auto=format&fit=crop&q=80&w=400" />
                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                  <button class="text-white bg-white/20 p-2 rounded-full backdrop-blur-sm"><span class="material-symbols-outlined">zoom_in</span></button>
                </div>
              </div>
            </div>
          </section>
        </div>

        <!-- Simulation Controls (Inside Scroll for Demo) -->
        <section class="p-6 bg-slate-50 dark:bg-slate-900/40 border-t border-slate-100 dark:border-slate-700">
           <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-4">Stage Simulation</p>
           <div class="flex flex-wrap gap-2">
            <button onclick="updateProgress(1)" class="px-4 py-2 text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:border-primary transition-all">Stage 1</button>
            <button onclick="updateProgress(2)" class="px-4 py-2 text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:border-primary transition-all">Stage 2</button>
            <button onclick="updateProgress(3)" class="px-4 py-2 text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:border-primary transition-all">Stage 3</button>
            <button onclick="updateProgress(4)" class="px-4 py-2 text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:border-primary transition-all">Stage 4</button>
           </div>
        </section>
      </div>
      <!-- END: Scrollable Content Area -->

      <!-- BEGIN: Fixed Footer Section -->
      <footer class="p-5 md:p-6 bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 flex-shrink-0 flex flex-col sm:flex-row justify-between items-center gap-4">
        <p class="text-[10px] text-slate-500 font-medium">Last modified by Admin • Jan 06, 2024</p>
        <div class="flex gap-2 w-full sm:w-auto">
            <button onclick="closeModal('viewModal')" class="flex-1 sm:flex-none px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl transition-all">
                Cancel
            </button>
            <button class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-6 py-2 bg-primary text-white rounded-xl text-sm font-bold shadow-lg shadow-primary/20 hover:shadow-primary/40 hover:scale-[1.02] transition-all">
                <span class="material-symbols-outlined text-sm">picture_as_pdf</span>
                Export Report
            </button>
        </div>
      </footer>
      <!-- END: Footer Section -->

    </div>
            `
    docID.style.display = 'flex'
  }
}


function multifileHandling(input, textId, clearBtnId, previewId) {
  const textElement = document.getElementById(textId)
  const clearBtn = document.getElementById(clearBtnId)
  const previewContainer = document.getElementById(previewId)
  const container = input.closest('.dropzone-pattern')
  const resetUI = ()=>{
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
    console.log('ℹ️ [INFO]: File input cleared or empty.')  }
}
window.multifileHandling = multifileHandling

function handleFileChange(input, txtID, btnID) {
  const display = document.getElementById(txtID)
  console.log('input: ',input)
  if (input.files && input.files.length > 0) {
    // Show the name of the selected file
    display.textContent = input.files[0].name
    display.classList.remove('text-slate-400')
    display.classList.add('text-slate-700', 'dark:text-slate-200', 'font-medium')
    document.getElementById(btnID).classList.remove('hidden')
    console.log('done', input.files[0].name)
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



function clearFileInput(btn, inputID, txtID, txtOriginal){
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


function handleModalAction(type, data) {
  const action = modalActions[type]
  console.log('clicked  ')
  if (action) {
    action(data)
  } else {
    console.error(`Action type "${type}" is not defined in the list.`)
  }
}
window.handleModalAction = handleModalAction

async function finalAddBooking() {
  // 1. Gather Data
  const formData = {
    user_id: document.getElementById('selectedCustomerId').value,
    service_id: document.getElementById('selectedServiceId').value,
    date: document.getElementById('bookingDate').value,
    time: document.getElementById('bookingTime').value,
    address: document.getElementById('address').value.trim(),
    cost: document.getElementById('cost').value.trim(),
    notes: document.getElementById('notes').value.trim()
  }
  console.log('Form Data:', formData)

  // 2. Validation Logic
  if (!formData.user_id) return alert('Please select a valid customer from the list.')
  if (!formData.service_id) return alert('Please select a valid service from the list.')
  if (!formData.date || !formData.time) return alert('Please select both a date and a time.')
  if (!formData.address) return alert('Please enter a complete service address.')

  // Combine for MySQL format
  formData.appointment_date = `${formData.date} ${formData.time}:00`

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
    const response = await fetch('/landscape/USER_API/add_booking.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(formData)
    })

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

window.closeModal = (id) => {document.getElementById(id).style.display = 'none'}

// Add Booking Modal
window.handleAction = (bookingid, actionType) => {
  console.log(`Action: ${actionType} on booking ${bookingid}`)
  console.log('Current state of bookings:', state.allBookings)
  const booking = state.allBookings.find((b) => b.id === bookingid)
  if (!booking) return console.error(`booking ${bookingid} not found.`)

  if(actionType === 'showDetails') showBookingDetails(booking)

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

window.confirmAddBooking = () => {
  alert('New booking has been created successfully.')
  finalAddBooking()
}

// Booking Actions
window.confirmBooking = (bookingId) => {
  alert('Booking ' + bookingId + ' has been confirmed.')
  confirmedBooking(bookingId)
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
/**
 * Specific function to trigger a cancellation
 */
async function confirmedBooking(bookingId) {
  const btn = document.querySelector(`#cancelBtn-${bookingId}`)

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
      status: 'Cancelled'
    }

    // 3. Call the generic fetch function
    await updateBookingStatus(url, payload)

    // 4. Success Handling
    alert(`Booking ID ${bookingId} has been cancelled.`)

    if (typeof fetchData === 'function') {
      fetchData()
    }

  } catch (error) {
    console.error('Cancellation failed:', error)
    alert('Error: ' + error.message)
  } finally {
    // 5. Reset UI
    if (btn) {
      btn.disabled = false
      btn.innerHTML = '<i class=\'fas fa-times\'></i> Cancelled'
    }
  }
}

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

window.generateInvoice = (bookingId) => {
  alert('Invoice for ' + bookingId + ' has been generated.')
}

window.rebook = (bookingId) => {
  alert('Rebooking process started for ' + bookingId)
}

window.showBookingDetails = showBookingDetails // Expose to global scope

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
  const steps = document.querySelectorAll('.step-item')
  const progressBar = document.getElementById('progress-bar-horizontal')
      
  steps.forEach((step, index) => {
    const stageNum = index + 1
    const circle = step.querySelector('.step-circle')
    const label = step.querySelector('.step-label')
        
    if (stageNum <= activeStage) {
      // Active or Completed State
      circle.classList.remove('bg-slate-100', 'dark:bg-slate-700', 'border-slate-300', 'dark:border-slate-600', 'text-slate-400')
      circle.classList.add('bg-primary', 'border-primary', 'text-white')
      label.classList.add('text-primary')
      label.classList.remove('text-slate-400')
    } else {
      // Pending State
      circle.classList.add('bg-slate-100', 'dark:bg-slate-700', 'border-slate-300', 'dark:border-slate-600', 'text-slate-400')
      circle.classList.remove('bg-primary', 'border-primary', 'text-white')
      label.classList.remove('text-primary')
      label.classList.add('text-slate-400')
    }
  })

  // Update progress bar width (for md screens and up)
  // Stages: 1=0%, 2=33%, 3=66%, 4=100%
  const percentage = ((activeStage - 1) / (steps.length - 1)) * 100
  if (progressBar) {
    progressBar.style.width = `${percentage}%`
  }
}




async function loadData() {
  const customerlist = document.getElementById('customerList')
  const serviceList = document.getElementById('serviceList')
  const selectedCustomerFilter = document.getElementById('selectedCustomerIdFilter')//<select id="selectedCustomerIdFilter"></select>

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

    // Populate Filter Dropdown
    const filterOptions = [
      '<option value="all" data-id="0">All Services</option>',
      ...data.services.map(s => `<option value="${s.service_name}" data-id="${s.id}">${capitalize(s.service_name)}</option>`)
    ].join('')
    selectedCustomerFilter.innerHTML = filterOptions
  } catch (error) {
    console.error('Error loading customers:', error)
  }
}




// Logic to capture the ID when a user is selected
document.getElementById('customerSearch').addEventListener('input', function() {
  const options = document.querySelectorAll('#customerList option')
  const hiddenInput = document.getElementById('selectedCustomerId')

  // Find the option that matches the input text
  const selectedOption = Array.from(options).find(opt => opt.value === this.value)

  if (selectedOption) {
    hiddenInput.value = selectedOption.getAttribute('data-id')
    console.log('Selected User ID:', hiddenInput.value)
  } else {
    hiddenInput.value = '' // Reset if text doesn't match an option
  }
})
document.getElementById('serviceSearch').addEventListener('input', function() {
  const options = document.querySelectorAll('#serviceList option')
  const hiddenInput = document.getElementById('selectedServiceId')

  // Find the option that matches the input text
  const selectedOption = Array.from(options).find(opt => opt.value === this.value)

  if (selectedOption) {
    hiddenInput.value = selectedOption.getAttribute('data-id')
    console.log('Selected Service ID:', hiddenInput.value)
  } else {
    hiddenInput.value = '' // Reset if text doesn't match an option
  }
})

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('#userTabsContainer .tab').forEach((tabElement) => {
    tabElement.addEventListener('click', (event) => {
      const tabName = tabElement.getAttribute('data-tab')
      state.status = tabName
      switchTab(tabName, event, fetchData)
    })
  })

  document.querySelector('#selectedCustomerIdFilter')
    .addEventListener('change', (event) => {
      const selectedOption = event.target.options[event.target.selectedIndex]
      const valueElem = selectedOption.getAttribute('data-id')
      if (valueElem !== null) {
        state.category = Number(valueElem)
        fetchData(1)
        console.log('Category changed to ID:', valueElem)
      }
    })

  document.querySelector('#dateFilter')
    .addEventListener('change', (event) => {
      const valueElem = event.target.value
      state.category = valueElem
      fetchData(1)
      console.log('Category changed to:', valueElem)
    })

  const datefilter = document.getElementById('dateFilter')
  const onDateChange = () => {
    state.dateFrom = datefilter.value
    fetchData(1)
  }
  datefilter.addEventListener('change', onDateChange)

  fetchData(1)
  loadData()
})