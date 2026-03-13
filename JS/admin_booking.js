 import { switchTab, formatToCalendar, renderPagination, capitalize} from './utils/utils.js';
      // --- 1. Centralized State Object ---
const state = {
        currentPage: 1,
        status: "all",
        category: "all",
        order: "newest",
        dateFrom: "",
        dateTo: "",
        allBookings: [], // Stores the current page of users
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
          // to: state.dateTo, --- IGNORE ---
          category: state.category
        });

        const apiURL = `/landscape/USER_API/bookings.php?${params.toString()}`;

        try {
          const response = await fetch(apiURL);
          if (!response.ok)
            throw new Error(`HTTP error! status: ${response.status}`);

          const data = await response.json();
          console.log("Fetched data:", data);

          // Update State & UI
          state.allBookings = data.bookings;
          displayStats(data.summary);
          displayBookings(data.bookings);

          renderPagination(data.summary.filtered, state, state.limit);
        } catch (error) {
          console.error("Fetch error:", error);
        }
      }

      function displayStats(data) {
        const stateMap = {
          activeBooking: data.active || 0,
          pendingBooking: data.pending || 0,
          bannedBooking: data.banned || 0,
          completedBooking: data.completed || 0,
          cancelledBooking: data.cancelled || 0,
        };
        for (const [id, value] of Object.entries(stateMap)) {
          const el = document.getElementById(id);
          if (el) el.textContent = value;
        }
      }

      

      function displayBookings(bookings) {
  const container = document.getElementById("bookingContainer");
  container.innerHTML = "";

  if (bookings.length === 0) {
    container.innerHTML = "<p>No bookings found.</p>";
    return;
  }

  let doc = "";
  bookings.forEach((booking) => {
    const statusLower = booking.status.toLowerCase();
    
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
            <span>${booking.category.split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')} Service</span>
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
        </div>`;

    // Footer Logic (Appended inside the card)
    doc += `<div class="booking-card-footer">
              <button class="btn btn-small" style="background-color: #f1f5f9; color: var(--text-dark)" onclick="handleModalAction('view','${booking.id}')">
                <i class="fas fa-eye"></i> View
              </button>`;

    if (statusLower === "pending") {
      doc += `
        <button class="btn btn-primary btn-small" onclick="handleModalAction('confirm','${booking.id}')" id="confirmBtn-${booking.id}">
          <i class="fas fa-check"></i> Confirm
        </button>
        <button class="btn btn-danger btn-small" onclick="handleModalAction('cancel','${booking.id}')" id="cancelBtn-${booking.id}">
          <i class="fas fa-times"></i> Cancel
        </button>`;
    } else if (statusLower === 'active') {
      doc += `
        <button class="btn btn-success btn-small" id="completeBtn-${booking.id}" onclick="handleModalAction('complete','${booking.id}')">
          <i class="fas fa-check-double"></i> Complete
        </button>`;
    } else if (statusLower === 'completed') {
      doc += `
        <button class="btn btn-secondary btn-small" onclick="handleModalAction('view','${booking.id}' )">
          <i class="fas fa-file-invoice"></i> Invoice
        </button>`;
    } else if (statusLower === 'cancelled') {
      doc += `
        <button class="btn btn-primary btn-small" onclick="handleModalAction('rebook', '${booking.id}')">
          <i class="fas fa-redo"></i> Rebook
        </button>`;
    }

    doc += `</div></div>`; // Close footer and card
  });

  container.innerHTML = doc;
}

      // Booking Details Modal
      function showBookingDetails(booking) {
        let modal = document.getElementById("bookingDetailsModal");
        modal.style.display = "flex";
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
                ${booking.phone_number || "Please Provide Phone Number"}
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
               ${(booking.category || "").split(' ').map(word => word ? word.charAt(0).toUpperCase() + word.slice(1).toLowerCase() : "").join(' ')} Service
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
              ${booking.notes || "Add notes here!"}
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
        `;

      }
      
  const modalActions = {
    confirm: (data) => {
        console.log("Activating Project:", data);
        let docID = document.getElementById('confirmModal');
        docID.innerHTML = `
        <div class="w-full max-w-2xl bg-white dark:bg-slate-900 rounded-xl shadow-2xl overflow-hidden border border-slate-200 dark:border-slate-800">
            <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/20">
                <div class="flex items-center gap-4">
                    <div>
                        <div class="flex items-center gap-3">
                            <h2 class="text-2xl font-bold text-primary dark:text-emerald-500">Confirm & Activate</h2>
                            <span class="px-3 py-1 bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 text-[10px] font-bold rounded-full tracking-wider uppercase border border-orange-200 dark:border-orange-800">Pending</span>
                        </div>
                        <p class="text-slate-500 dark:text-slate-400 text-sm">Project Blueprint & Service Agreements</p>
                    </div>
                </div>
                <button class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-1 ml-auto">
                    <span class="material-symbols-outlined" onclick="closeModal('confirmModal')">close</span>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 gap-4">
                    <div class="dropzone-pattern p-6 rounded-xl flex flex-col items-center justify-center text-center cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                        <span class="material-symbols-outlined text-primary mb-2">architecture</span>
                        <p class="text-sm font-medium">Upload Project Blueprint</p>
                        <p class="text-xs text-slate-400 mt-1">PDF, DWG or JPG up to 10MB</p>
                    </div>
                    <div class="dropzone-pattern p-6 rounded-xl flex flex-col items-center justify-center text-center cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                        <span class="material-symbols-outlined text-primary mb-2">request_quote</span>
                        <p class="text-sm font-medium">Official Quotation</p>
                        <p class="text-xs text-slate-400 mt-1">Signed PDF document</p>
                    </div>
                    <div class="dropzone-pattern p-6 rounded-xl flex flex-col items-center justify-center text-center cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                        <span class="material-symbols-outlined text-primary mb-2">draw</span>
                        <p class="text-sm font-medium">Signed Service Agreement</p>
                        <p class="text-xs text-slate-400 mt-1">Digital signature required</p>
                    </div>
                </div>
            </div>
            <div class="p-6 bg-slate-50 dark:bg-slate-800/50 flex flex-col gap-4">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input class="rounded border-slate-300 text-primary focus:ring-primary h-5 w-5" type="checkbox" />
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Downpayment Verified & Received</span>
                </label>
                <button class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-primary/20">
                    Confirm & Activate Project
                </button>
            </div>
        </div>
        `;
        docID.style.display = "flex"
      },
    complete: (data) => {
        console.log("Finalizing Handover for:", data);
        let docID = document.getElementById('completeModal');
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
                <button class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-1 ml-auto">
                    <span class="material-symbols-outlined" onclick="closeModal('completeModal')">close</span>
                </button>
            </div>
            <div class="p-6 space-y-6">
                <div class="space-y-3">
                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">Final Project Portfolio (Images)</label>
                    <div class="dropzone-pattern p-8 rounded-xl flex flex-col items-center justify-center text-center border-2 border-dashed border-primary/20">
                        <div class="flex gap-2 mb-4">
                            <div class="w-16 h-16 rounded-lg bg-slate-200 overflow-hidden">
                                <img class="w-full h-full object-cover" src="https://images.unsplash.com/photo-1558904541-efa8c19681ef?auto=format&fit=crop&w=150" />
                            </div>
                            <div class="w-16 h-16 rounded-lg bg-slate-200 overflow-hidden">
                                <img class="w-full h-full object-cover" src="https://images.unsplash.com/photo-1585320806297-9794b3e4eeae?auto=format&fit=crop&w=150" />
                            </div>
                            <div class="w-16 h-16 rounded-lg bg-primary/10 flex items-center justify-center border-2 border-primary/20">
                                <span class="material-symbols-outlined text-primary">add</span>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500">Drag and drop or click to upload final photos</p>
                    </div>
                </div>
                <div class="space-y-3">
                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">Client Sign-off Document</label>
                    <div class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
                        <span class="material-symbols-outlined text-primary">description</span>
                        <span class="text-sm flex-1 truncate">waiting_for_upload.pdf</span>
                        <button class="text-primary text-xs font-bold">BROWSE</button>
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
        </div>`;
        docID.style.display = "flex"
    },
    cancel: (data) => {
        console.log("Cancelling Project. Reason:", data);
        let docID = document.getElementById('cancelModal');
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
                            <input class="w-full pl-7 rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 focus:ring-red-500 focus:border-red-500" placeholder="0.00" type="number" />
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 uppercase">Valuation Report</label>
                        <div class="dropzone-pattern py-2 px-3 rounded-lg border border-slate-200 dark:border-slate-700 flex items-center gap-2 cursor-pointer hover:bg-slate-50 transition-colors">
                            <span class="material-symbols-outlined text-red-500">upload_file</span>
                            <span class="text-xs text-slate-400">Click to upload</span>
                        </div>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-500 uppercase">Reason for Cancellation</label>
                    <textarea class="w-full rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 focus:ring-red-500 focus:border-red-500" placeholder="Please provide detailed reason..." rows="4"></textarea>
                </div>
            </div>
            <div class="p-6 flex gap-3">
                <button class="flex-1 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold py-3 rounded-xl hover:bg-slate-200 transition-all">
                    Go Back
                </button>
                <button class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-red-600/20">
                    Confirm Cancellation
                </button>
            </div>
        </div>
        `;
        docID.style.display = "flex"
    },
    rebook: (data) => {
        console.log("Rescheduling to:", data);
        let docID = document.getElementById('rebookModal');
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
        `;
        docID.style.display = "flex"
    },
    view: (data) => {
        console.log("Opening History View for:", data);
        let docID = document.getElementById('viewModal');
        docID.innerHTML =`
        <div class="w-full max-w-4xl bg-white dark:bg-slate-900 rounded-xl shadow-2xl overflow-hidden border border-slate-200 dark:border-slate-800">
            <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-800/20">
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-primary flex items-center justify-center text-white">
                            <span class="material-symbols-outlined">history</span>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-primary dark:text-emerald-500">Project History: <span class="text-slate-800 dark:text-slate-100">Willow Creek Estate</span></h2>
                            <p class="text-slate-500 dark:text-slate-400 text-sm">ID: GS-2024-0089 • Residential Landscaping</p>
                        </div>
                    </div>
                    <span class="px-4 py-1.5 bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400 text-xs font-bold rounded-full tracking-wider uppercase border border-green-200 dark:border-green-800">Completed</span>
                </div>
                <button class="ml-auto text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-1">
                    <span class="material-symbols-outlined" onclick="closeModal('viewModal')">close</span>
                </button>
            </div>
            <div class="p-8 space-y-10">
                <div class="relative flex items-center justify-between">
                    <div class="absolute top-1/2 left-0 right-0 h-1 bg-slate-100 dark:bg-slate-800 -translate-y-1/2 z-0"></div>
                    <div class="absolute top-1/2 left-0 w-full h-1 bg-primary -translate-y-1/2 z-0 scale-x-100 origin-left"></div>
                    <div class="relative z-10 flex flex-col items-center gap-2 group">
                        <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center ring-4 ring-white dark:ring-slate-900">
                            <span class="material-symbols-outlined">mail</span>
                        </div>
                        <span class="text-xs font-bold text-primary">Inquiry</span>
                    </div>
                    <div class="relative z-10 flex flex-col items-center gap-2">
                        <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center ring-4 ring-white dark:ring-slate-900">
                            <span class="material-symbols-outlined">check_circle</span>
                        </div>
                        <span class="text-xs font-bold text-primary">Confirmed</span>
                    </div>
                    <div class="relative z-10 flex flex-col items-center gap-2">
                        <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center ring-4 ring-white dark:ring-slate-900">
                            <span class="material-symbols-outlined">pending_actions</span>
                        </div>
                        <span class="text-xs font-bold text-primary">In Progress</span>
                    </div>
                    <div class="relative z-10 flex flex-col items-center gap-2">
                        <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center ring-4 ring-white dark:ring-slate-900">
                            <span class="material-symbols-outlined">celebration</span>
                        </div>
                        <span class="text-xs font-bold text-primary">Completed</span>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div class="space-y-4">
                        <h3 class="text-sm font-bold uppercase tracking-widest text-slate-400 flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">folder_open</span> Documentation
                        </h3>
                        <div class="space-y-2">
                            <div class="group flex items-center gap-3 p-3 rounded-xl border border-slate-100 dark:border-slate-800 hover:border-primary/30 hover:bg-primary/5 transition-all cursor-pointer">
                                <div class="w-10 h-10 rounded-lg bg-red-100 dark:bg-red-900/20 text-red-600 flex items-center justify-center">
                                    <span class="material-symbols-outlined">picture_as_pdf</span>
                                </div>
                                <div class="flex-1 overflow-hidden">
                                    <p class="text-sm font-semibold truncate">Master_Blueprint_Final.pdf</p>
                                    <p class="text-[10px] text-slate-500 uppercase">Oct 12, 2023 • 4.2 MB</p>
                                </div>
                                <span class="material-symbols-outlined text-slate-300 group-hover:text-primary">download</span>
                            </div>
                            <div class="group flex items-center gap-3 p-3 rounded-xl border border-slate-100 dark:border-slate-800 hover:border-primary/30 hover:bg-primary/5 transition-all cursor-pointer">
                                <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/20 text-blue-600 flex items-center justify-center">
                                    <span class="material-symbols-outlined">request_quote</span>
                                </div>
                                <div class="flex-1 overflow-hidden">
                                    <p class="text-sm font-semibold truncate">Official_Quotation_v2.pdf</p>
                                    <p class="text-[10px] text-slate-500 uppercase">Oct 14, 2023 • 1.1 MB</p>
                                </div>
                                <span class="material-symbols-outlined text-slate-300 group-hover:text-primary">download</span>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <h3 class="text-sm font-bold uppercase tracking-widest text-slate-400 flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">photo_library</span> Project Portfolio
                        </h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="aspect-square rounded-xl bg-slate-100 overflow-hidden relative group">
                                <img class="w-full h-full object-cover" src="https://images.unsplash.com/photo-1590059392395-9799276c0e5a?auto=format&fit=crop&w=300" />
                                <div class="absolute inset-0 bg-primary/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <span class="material-symbols-outlined text-white">zoom_in</span>
                                </div>
                            </div>
                            <div class="aspect-square rounded-xl bg-slate-100 overflow-hidden relative group">
                                <img class="w-full h-full object-cover" src="https://images.unsplash.com/photo-1598902108854-10e335adac99?auto=format&fit=crop&w=300" />
                                <div class="absolute inset-0 bg-primary/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <span class="material-symbols-outlined text-white">zoom_in</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-6 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                <div class="text-xs text-slate-400 font-medium">
                    Last modified by Admin on Jan 06, 2024
                </div>
                <button class="flex items-center gap-2 bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 font-bold px-6 py-2.5 rounded-lg hover:opacity-90 transition-all">
                    <span class="material-symbols-outlined text-sm">picture_as_pdf</span>
                    Generate PDF Report
                </button>
            </div>
        </div>
        `;
        docID.style.display = "flex";
    }
  };

  export function handleModalAction(type, data) {
    const action = modalActions[type];
    console.log("clicked  ")
    if (action) {
        action(data);
    } else {
        console.error(`Action type "${type}" is not defined in the list.`);
    }
}
window.handleModalAction = handleModalAction;

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
    };
    console.log("Form Data:", formData);

    // 2. Validation Logic
    if (!formData.user_id) return alert("Please select a valid customer from the list.");
    if (!formData.service_id) return alert("Please select a valid service from the list.");
    if (!formData.date || !formData.time) return alert("Please select both a date and a time.");
    if (formData.address.length < 5) return alert("Please enter a complete service address.");

    // Combine for MySQL format
    formData.appointment_date = `${formData.date} ${formData.time}:00`;

    // 3. UI Feedback
    // Since this is a standalone function, we target the button by ID or specific selector
    const submitBtn = document.querySelector('#addBookingForm button[type="button"]') || 
                      document.querySelector('#saveBookingBtn');
    
    if (submitBtn) {
        submitBtn.disabled = true;
        const originalText = submitBtn.innerHTML;
        submitBtn.textContent = "Saving...";
    }

    try {
        // 4. Fetch Request
        const response = await fetch('/landscape/USER_API/create_booking.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });

        const result = await response.json();

        if (result.status === 'success') {
            alert("Booking created successfully!");
            
            // Reset form manually since 'this' no longer refers to the form
            document.getElementById('addBookingForm').reset();
            
            // Refresh the list
            if (typeof fetchData === 'function') fetchData(1); 
            
            // Close modal if you have a closeModal function
            if (typeof closeModal === 'function') closeModal();
        } else {
            alert("Error: " + result.message);
        }
    } catch (error) {
        console.error("Submission error:", error);
        alert("Network error. Failed to save booking.");
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = "Save Booking";
        }
    }
}


      

// Add Booking Modal
      window.handleAction = (bookingid, actionType) => {
        console.log(`Action: ${actionType} on booking ${bookingid}`);
        console.log("Current state of bookings:", state.allBookings);
        const booking = state.allBookings.find((b) => b.id == bookingid);
        if (!booking) return console.error(`booking ${bookingid} not found.`);

        if(actionType === "showDetails") showBookingDetails(booking); 

      }

      function showAddBookingModal() {
        document.getElementById("addBookingModal").style.display = "flex";
      }

      

      window.closeAddBookingModal = () => {
        document.getElementById("addBookingModal").style.display = "none";
      }
      window.closeBookingDetailsModal = () => {
        document.getElementById("bookingDetailsModal").style.display = "none";
      }

      window.confirmAddBooking = () => {
        alert("New booking has been created successfully.");
        finalAddBooking();
        closeAddBookingModal();
      }

      // Booking Actions
      window.confirmBooking = (bookingId) => {
        alert("Booking " + bookingId + " has been confirmed.");
        confirmedBooking(bookingId);
      };

      window.completeBooking = (bookingId) => {
        alert("Booking " + bookingId + " has been marked as completed.");
      }

      window.cancelBooking = (bookingId) => {
        if (confirm("Are you sure you want to cancel this booking?")) {
          alert("Booking " + bookingId + " has been cancelled.");
          confirmCancel(bookingId);
        }
      }
/**
 * Generic function to send status updates to the API
 */
async function updateBookingStatus(url, data) {
    const response = await fetch(url, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify(data),
    });

    // Parse the JSON response
    const result = await response.json();

    if (!response.ok || result.status !== "success") {
        throw new Error(result.message || "Failed to update booking");
    }

    return result;
}
      /**
 * Specific function to trigger a cancellation
 */
async function confirmedBooking(bookingId) {
    const btn = document.querySelector(`#cancelBtn-${bookingId}`);
    
    // 1. Prepare UI Feedback
    if (btn) {
        btn.disabled = true;
        btn.textContent = "Processing...";
    }

    try {
        // 2. Define API endpoint and payload
        const url = "/landscape/USER_API/cancelled_booking.php";
        const payload = {
            id: bookingId,
            status: "Cancelled"
        };

        // 3. Call the generic fetch function
        const result = await updateBookingStatus(url, payload);

        // 4. Success Handling
        alert(`Booking ID ${bookingId} has been cancelled.`);

        if (typeof fetchData === "function") {
            fetchData();
        }

    } catch (error) {
        console.error("Cancellation failed:", error);
        alert("Error: " + error.message);
    } finally {
        // 5. Reset UI
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = "<i class='fas fa-times'></i> Cancelled";
        }
    }
}

async function confirmCancel(bookingId) {
    const btn = document.querySelector(`#confirmBtn-${bookingId}`);
    
    // 1. Prepare UI Feedback
    if (btn) {
        btn.disabled = true;
        btn.textContent = "Processing...";
    }

    try {
        // 2. Define API endpoint and payload
        const url = "/landscape/USER_API/confirmed_booking.php";
        const payload = {
            id: bookingId,
        };

        // 3. Call the generic fetch function
        const result = await updateBookingStatus(url, payload);

        // 4. Success Handling
        alert(`Booking ID ${bookingId} has been confirmed.`);

        if (typeof fetchData === "function") {
            fetchData();
        }

    } catch (error) {
        console.error("Confirmation failed:", error);
        alert("Error: " + error.message);
    } finally {
        // 5. Reset UI
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = "<i class='fas fa-check'></i> Confirmed";
        }
    }
}

      window.generateInvoice = (bookingId) => {
        alert("Invoice for " + bookingId + " has been generated.");
      }

      window.rebook = (bookingId) => {
        alert("Rebooking process started for " + bookingId);
      }

      window.showBookingDetails = showBookingDetails; // Expose to global scope

      // Close modals when clicking outside
      document.querySelectorAll(".modal-overlay").forEach((modal) => {
        modal.addEventListener("click", function (e) {
          if (e.target === this) {
            this.style.display = "none";
          }
        });
      });




      window.goToPage = (page) => fetchUsers(page);
      window.showAddBookingModal = showAddBookingModal; // Expose to global scope



async function loadData() {
    const customerlist = document.getElementById('customerList');
    const serviceList = document.getElementById('serviceList');
    const selectedCustomerFilter = document.getElementById('selectedCustomerIdFilter');//<select id="selectedCustomerIdFilter"></select>

    try {
        const response = await fetch('/landscape/USER_API/get_customers.php');
        const data = await response.json();

        console.log("loaded data: ", data)
        // Clear and Populate the datalist
        customerlist.innerHTML = data.customers.map(user => 
            `<option value="${user.name} (${user.email})" data-id="${user.id}">`
        ).join('');
        serviceList.innerHTML = data.services.map(s => 
            `<option value="${s.service_name}" data-id="${s.id}">${s.service_name.split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')} - $${s.basePrice}</option>`
        ).join('');
        
        // Populate Filter Dropdown
        const filterOptions = [
            `<option value="all">All Services</option>`,
            ...data.services.map(s => `<option value="${s.service_name}">${capitalize(s.service_name)}</option>`)
        ].join('');
        selectedCustomerFilter.innerHTML = filterOptions;
    } catch (error) {
        console.error("Error loading customers:", error);
    }
}




// Logic to capture the ID when a user is selected
document.getElementById('customerSearch').addEventListener('input', function(e) {
    const options = document.querySelectorAll('#customerList option');
    const hiddenInput = document.getElementById('selectedCustomerId');
    
    // Find the option that matches the input text
    const selectedOption = Array.from(options).find(opt => opt.value === this.value);
    
    if (selectedOption) {
        hiddenInput.value = selectedOption.getAttribute('data-id');
        console.log("Selected User ID:", hiddenInput.value);
    } else {
        hiddenInput.value = ""; // Reset if text doesn't match an option
    }
});
document.getElementById('serviceSearch').addEventListener('input', function(e) {
    const options = document.querySelectorAll('#serviceList option');
    const hiddenInput = document.getElementById('selectedServiceId');
    
    // Find the option that matches the input text
    const selectedOption = Array.from(options).find(opt => opt.value === this.value);
    
    if (selectedOption) {
        hiddenInput.value = selectedOption.getAttribute('data-id');
        console.log("Selected Service ID:", hiddenInput.value);
    } else {
        hiddenInput.value = ""; // Reset if text doesn't match an option
    }
});

      document.addEventListener("DOMContentLoaded", () => {
        document.querySelectorAll("#userTabsContainer .tab").forEach((tabElement) => {
          tabElement.addEventListener("click", (event) => {
              const tabName = tabElement.getAttribute("data-tab");
              state.status = tabName;
              switchTab(tabName, event, fetchData);
            });
          });
        document.querySelector("#selectedCustomerIdFilter")
        .addEventListener("change", (event) => {
            const valueElem = event.target.value;
            state.category = valueElem;
            fetchData(1);
            console.log("Category changed to:", valueElem);
        });
        
        document.querySelector("#dateFilter")
        .addEventListener("change", (event) => {
            const valueElem = event.target.value;
            state.category = valueElem;
            fetchData(1);
            console.log("Category changed to:", valueElem);
        });

        const datefilter = document.getElementById("dateFilter");
        const onDateChange = () => {
          state.dateFrom = datefilter.value;
          fetchData(1);
        }
        datefilter.addEventListener("change", onDateChange);
        
        fetchData(1);
        loadData();
        });