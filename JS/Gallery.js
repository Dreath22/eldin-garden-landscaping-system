import { toggleModal, filesizeComputation, generateCsrfToken, emptyElement, clearElementError, moneySign, switchTab, putTextinElementById, buttonEventListener, renderPagination, capitalize, log } from './utils/utils.js'
import { fetchPorfolio } from './utils/apiUtils.js';
import { ModalSystem, ToastSystem } from './utils/modal.js';
const state = {
    currentPage: 1,
    currentTab: 'all',
    service_id: 1,
    sort: 'new',
    limit: 12,
    uploads: {
        files: [],
        fileSize: 0,
    }
};
let csrfToken = ""
const controllerPath = "/landscape/USER_API/PortfolioController.php";
const base = "http://localhost/landscape/";
let data;
const urlGet = (data) =>{
  console.log("urlGet: ", data)
  const files_data = data.filenames.split(",")
  const urls = files_data.map(file => {
    const cleanRelativePath = (data.dir_path + file).replace(/^[\./]+/, "");
    return base + cleanRelativePath;
  });
  return urls;
}

const rowData = (data) =>{
  const url = urlGet(data)
    console.log("url: ", url)
    
    // Format date to human readable
    const formattedDate = new Date(data.created_at).toLocaleDateString('en-US', {
      year: 'numeric', 
      month: 'short', 
      day: 'numeric'
    });
    
    let status = data.status;
    // Determine status badge class and text
    let statusClass = '';
    let statusText = '';

    if (status === 'LIVE') {
      statusClass = 'active';
      statusText = 'Live';
    } else if (status === 'DRAFT') {
      statusClass = 'pending';
      statusText = 'Draft';
    } else {
      statusClass = 'pending';
      statusText = 'Draft';
    }

    const totalFileSize = filesizeComputation(data.total_file_size);
    
    // Return data as dictionary/object
    return {
      id: data.portfolio_id,
      url: url[0], // Use first URL for main display
      urls: url, // All URLs for preview
      title: data.title,
      date: formattedDate,
      status: status,
      statusClass: statusClass,
      statusText: statusText,
      description: data.description,
      fileCount: data.file_count,
      fileSize: data.total_file_size,
      dirPath: data.dir_path,
      portfolioId: data.portfolio_id,
      filesize: totalFileSize,
      service_name: capitalize(data.service_name),
    };
}

// Generic portfolio renderer - DRY principle
const renderPortfolios = (dataArray, htmlTemplate, elementselector, maxItems = 3) => {
  let accumulatedHtml = '';
  if (!dataArray || !dataArray.length){
    accumulatedHtml = "<p>No Recent Data</p>";
    putTextinElementById(elementselector, accumulatedHtml, 'innerHTML');
    return;
  };
  console.log(`Rendering ${maxItems} portfolios to ${elementselector}`);
  
  // Loop through items and accumulate HTML
  const itemsToProcess = Math.min(maxItems, dataArray.length);
  
  for (let i = 0; i < itemsToProcess; i++) {
    const portfolioHtml = htmlTemplate(dataArray[i]);
    accumulatedHtml += portfolioHtml;
  }
  
  // Display the accumulated HTML
  putTextinElementById(elementselector, accumulatedHtml, 'innerHTML');
  console.log(`Rendered ${itemsToProcess} portfolio items`);
};
const galleryItemTemplate = (data) =>{
  const rowInfo = rowData(data);
  
  return `
    <div class="gallery-item-admin" data-id="${rowInfo.id}">
        <img src="${rowInfo.url}" alt="${rowInfo.service_name}" title=="${rowInfo.service_name}">
        <div class="gallery-item-overlay">
        <button class="table-btn view" data-id="${rowInfo.id}" title="View" ><i class="fas fa-eye"></i></button>
        <button class="table-btn edit" data-id="${rowInfo.id}" title="Edit"><i class="fas fa-edit"></i></button>
        <button class="table-btn delete" data-id="${rowInfo.id}" title="Delete"><i class="fas fa-trash"></i></button>
        </div>
        <div class="gallery-item-info">
        <h5>${rowInfo.title}</h5>
        <p>${rowInfo.service_name} * ${rowInfo.date}</p>
        </div>
        <div style="position: absolute; top: 0.5rem; left: 0.5rem;">
        <input type="checkbox" class="checkbox" data-id="${rowInfo.id}" style="width: 20px; height: 20px;">
        </div>
        <div style="position: absolute; top: 0.5rem; right: 0.5rem;">
        <span class="status-badge ${rowInfo.statusClass}" style="font-size: 0.65rem;">${rowInfo.statusText}</span>
        </div>
    </div>
  `;
}

const buttonsLoader = () => {
  document.querySelectorAll(".view").forEach(element => {
  buttonEventListener(element, (el, elementr)=>{
    const portfolioId = elementr.dataset.id;
    const portfolioItem = data.data.data.find(item => item.portfolio_id == portfolioId);
    
    if (portfolioItem) {
      const rowInfo = rowData(portfolioItem);
      
      putTextinElementById("#previewTitle", rowInfo.title)
      putTextinElementById("#viewImageModal", "flex", "style")
      putTextinElementById("#previewCategory", rowInfo.service_name)
      putTextinElementById("#previewDate", rowInfo.date)
      putTextinElementById("#previewFilesize", rowInfo.filesize)
      
      // Clear existing preview content
      document.getElementById('upload-preview').innerHTML = '';
      
      // Render all images
      rowInfo.urls.forEach((imageUrl, index) => {
        const div = document.createElement('div');
        div.className = 'upload-preview-item';
        div.innerHTML = `
          <img src="${imageUrl}" alt="Preview ${index + 1}">
          <button class="upload-preview-remove" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
          </button>
        `;
        document.getElementById('upload-preview').appendChild(div);
      });
    }
  })
})
  // Add event listeners for edit buttons
  document.querySelectorAll(".edit").forEach(element => {
    buttonEventListener(element, (el, elementr)=>{
      console.log("Edit Element: ", elementr)
      const portfolioId = elementr.dataset.id;
      const portfolioItem = data.data.data.find(item => item.portfolio_id == portfolioId);
      
      if (portfolioItem) {
        const rowInfo = rowData(portfolioItem);
        
        // Store portfolio ID in modal dataset
        document.getElementById('editImageModal').dataset.portfolioId = portfolioId;
        document.getElementById('hidden_data_id').dataset.id = portfolioId;
        
        // Populate edit form fields
        putTextinElementById("#editImageTitle", rowInfo.title, "value")
        putTextinElementById("#editImageDescription", rowInfo.description, "value")
        putTextinElementById("#editImageStatus", rowInfo.status, "value")
        
        // Populate and set category dropdown
        const categorySelect = document.getElementById('editImageCategory');
        if (categorySelect) {
          // Clear existing options
          categorySelect.innerHTML = '';
          
          // Add default option
          const defaultOption = document.createElement('option');
          defaultOption.value = '';
          defaultOption.textContent = 'Select Category';
          categorySelect.appendChild(defaultOption);
          
          // Fetch and populate services
          fetch('/landscape/USER_API/ServicesController.php?action=getServices')
            .then(response => response.json())
            .then(servicesData => {
              if (servicesData.services) {
                servicesData.services.forEach(service => {
                  const option = document.createElement('option');
                  option.value = service.id;
                  option.textContent = service.service_name;
                  categorySelect.appendChild(option);
                });
              }
            });
        }
        
        // Set status dropdown
        const statusSelect = document.getElementById('editImageStatus');
        if (statusSelect) {
          // Compare status values (both lowercase)
          Array.from(statusSelect.options).forEach(option => {
            if (option.value.toLowerCase() === rowInfo.status.toLowerCase()) {
              statusSelect.value = option.value;
            }
          });
        }
        
        // Set featured checkbox
        const featuredCheckbox = document.getElementById('isFeatured');
        if (featuredCheckbox) {
          featuredCheckbox.checked = rowInfo.featured == 1;
        }
        
        // Show edit modal
        toggleModal("#editImageModal", "flex");
      }
    })
  })

  // Add event listeners for delete buttons
  document.querySelectorAll(".delete").forEach(element => {
    buttonEventListener(element, (el, elementr)=>{
      const portfolioId = elementr.dataset.id;
      const portfolioItem = data.data.data.find(item => item.portfolio_id == portfolioId);
      
      console.log(" Delete element: ", portfolioItem.portfolio_id)
    })
  })
  
  // Add batch delete functionality
  const batchDeleteBtn = document.createElement('button');
  batchDeleteBtn.className = 'btn btn-danger btn-small';
  batchDeleteBtn.innerHTML = '<i class="fas fa-trash"></i> Delete Selected';
  batchDeleteBtn.style.marginLeft = '10px';
  batchDeleteBtn.style.display = 'none';
  
  // Insert button after gallery container
  const galleryContainer = document.querySelector('.gallery-container');
  if (galleryContainer) {
    galleryContainer.parentNode.insertBefore(batchDeleteBtn, galleryContainer.nextSibling);
  }
  
  // Add event listener for batch delete button
  buttonEventListener('#batchdelete', (el, elementr) => {
    // Collect all checked checkboxes
    const checkboxes = document.querySelectorAll('.checkbox:checked');
    
    if (checkboxes.length === 0) {
      ToastSystem.warning('Please select at least one item to delete', 'Selection Required');
      return;
    }
    
    // Confirm before deletion
    ModalSystem.confirm(
      "Delete Confirmation", 
      `Are you sure you want to delete ${checkboxes.length} portfolio item(s)?`, 
      () => {
        // Continue with deletion logic
        performBatchDeletion(checkboxes);
      }
    );
    return;
  });
}

// Separate function for batch deletion logic
function performBatchDeletion(checkboxes) {
  // Collect all portfolio IDs to delete
  const portfolioIdsToDelete = [];
  checkboxes.forEach(checkbox => {
    const galleryItem = checkbox.closest('.gallery-item-admin');
    if (galleryItem && galleryItem.dataset.id) {
      portfolioIdsToDelete.push(galleryItem.dataset.id);
    }
  });
  
  // Get CSRF token
  const csrfToken = generateCsrfToken();
  
  // Create FormData for batch delete
  const formData = new FormData();
  formData.append('action', 'batchDelete');
  formData.append('ids', JSON.stringify(portfolioIdsToDelete));
  formData.append('csrf_token', csrfToken);
  
  // Send batch delete request
  fetch(controllerPath, {
    method: 'POST',
    body: formData
  })
  .then(response => {
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    return response.json();
  })
  .then(data => {
    console.log('Batch delete response:', data);
    
    // Process results
    const successful = data.results?.filter(r => r.success) || [];
    const failed = data.results?.filter(r => !r.success) || [];
    
    if (successful.length > 0) {
      ToastSystem.success(`Successfully deleted ${successful.length} portfolio item(s)`, 'Delete Success');
    }
    
    if (failed.length > 0) {
      console.error('Failed deletions:', failed);
      ToastSystem.error(`Failed to delete ${failed.length} portfolio item(s)`, 'Delete Error');
    }
    
    // Reload data if any deletions occurred
    if (successful.length > 0) {
      fetchData(state.currentTab);
    }
  })
  .catch(error => {
    console.error('Batch delete error:', error);
    ToastSystem.error('Batch delete failed: ' + error.message, 'Delete Error');
  });
  
  // Show/hide batch delete button based on checkbox selection
  const updateBatchDeleteButton = () => {
    const checkedBoxes = document.querySelectorAll('.checkbox:checked');
    if (batchDeleteBtn) {
      batchDeleteBtn.style.display = checkedBoxes.length > 0 ? 'inline-block' : 'none';
    }
  };
  
  // Add event listeners to all checkboxes for batch delete button visibility
  document.querySelectorAll('.checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateBatchDeleteButton);
  });
  
  // Initial check
  updateBatchDeleteButton();
}

buttonEventListener('#saveEdit', (e, element) => {
    e.preventDefault();
    e.target.disabled = true;
    
    // 1. Validate all required fields
    const title = document.getElementById('editImageTitle').value.trim();
    const description = document.getElementById('editImageDescription').value.trim();
    const categoryId = document.getElementById('editImageCategory').value;
    const status = document.getElementById('editImageStatus').value;
    const isFeatured = document.getElementById('isFeatured').checked;
    
    if (!title) {
        ModalSystem.warning("Required Field", "Title is required");
        document.getElementById('editImageTitle').focus();
        e.target.disabled = false;
        return;
    }
    
    if (!description) {
        ModalSystem.warning("Required Field", "Description is required");
        document.getElementById('editImageDescription').focus();
        e.target.disabled = false;
        return;
    }
    
    if (!categoryId) {
        ModalSystem.warning("Required Field", "Category is required");
        document.getElementById('editImageCategory').focus();
        e.target.disabled = false;
        return;
    }
    
    // 2. Get the portfolio ID from the modal (store it when opening the modal)
    const portfolioId = document.getElementById('editImageModal').dataset.portfolioId;
    if (!portfolioId) {
        ModalSystem.error("System Error", "Portfolio ID not found");
        e.target.disabled = false;
        return;
    }
    
    // 3. Create FormData for update
    const formData = new FormData();
    formData.append('id', portfolioId);
    formData.append('title', title);
    formData.append('description', description);
    formData.append('serviceId', categoryId);
    formData.append('status', status);
    formData.append('featured', isFeatured ? 1 : 0);
    formData.append('csrf_token', csrfToken);
    
    // 4. Submit to API with proper error handling
    fetch('/landscape/USER_API/PortfolioController.php?action=update', {
        method: 'POST',
        body: formData // Don't set Content-Type header - FormData sets it automatically
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            console.log('Update successful:', data);
            
            // Show success feedback to user
            ToastSystem.success(data.message || 'Portfolio updated successfully!', 'Update Success');
            
            // Close modal and refresh gallery
            toggleModal("#editImageModal", "none");
            
            // Refresh the gallery data
            updates();
            
        } else if (data.status === 'error') {
            // Handle validation errors
            if (data.errors) {
                // Show field-specific errors
                Object.entries(data.errors).forEach(([field, message]) => {
                    const element = document.getElementById(field === 'title' ? 'editImageTitle' : 
                                                             field === 'description' ? 'editImageDescription' :
                                                             field === 'serviceId' ? 'editImageCategory' :
                                                             field === 'status' ? 'editImageStatus' : field);
                    if (element) {

                        //dont empty it, do the empty in the successful one
                        emptyElement(element, message);
                    }
                });
                ToastSystem.error('Please fix the validation errors.', 'Validation Required');
            } else {
                ToastSystem.error(data.message || 'Update failed', 'Update Error');
            }
        }
        
        e.target.disabled = false;
    })
    .catch(error => {
        console.error('Update failed:', error);
        
        // Show user-friendly error message
        ToastSystem.error('Update failed: ' + error.message, 'Update Error');
        e.target.disabled = false;
    });
}, 'click')

const loader = async () => {
  const response = await fetch('/landscape/USER_API/ServicesController.php?action=getServices')
  const servicesData = await response.json()
  const services = servicesData.services
  console.log("hello", services)
  console.log("lenght: ", services.length)
  let html = ''
  if(services.length > 0){
     html = "<option value='1'>All Categories</option>"
    services.forEach(service => {
      html += `<option value="${service.id}">${capitalize(service.service_name)}</option>`
    })
  }else{
    html = "<option value=''>No services found</option>"
  }
  putTextinElementById('#categories', html, 'innerHTML')
}
const updates = async() => {
    data = await fetchPorfolio(controllerPath, state, csrfToken)
    galleryBody(data.data.data)
    if (data.data.pagination) {
        renderPagination(fetchPorfolio, data.data.pagination.totalRecords, state, () => fetchData(state.currentTab));
    }
}
buttonEventListener("#categories", async (el, element)=>{
    console.log("Category changed", element.value)
    state.service_id = element.value;
    updates();
},"change")
buttonEventListener("#sortings", async (el, element)=>{
    console.log("sorting changed", element.value)
    state.sort = element.value;
    updates();
},"change")

// Specific function using the generic renderer
const galleryBody = (dataArray) => {
  renderPortfolios(dataArray, galleryItemTemplate, ".gallery-grid-admin", dataArray.length);
  buttonsLoader();
};
document.addEventListener('DOMContentLoaded', async() => {
    // Generate CSRF token for form
    await generateCsrfToken();
    csrfToken = document.querySelector('input[name="csrf_token"]')?.value
    
    // Fetch and update portfolio statistics
    try {
        const statsResponse = await fetch(`${controllerPath}?action=stats`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            }
        });
        
        if (statsResponse.ok) {
            const statsData = await statsResponse.json();
            console.log('Stats data:', statsData);
            
            // Update HTML elements with stats data
            if (statsData.status === 'success' && statsData.data && statsData.data.overview) {
                const overview = statsData.data.overview;
                
                // Update total portfolios
                putTextinElementById('#total-portfolios', overview.total_portfolios, 'textContent');
                
                // Update live portfolios
                putTextinElementById('#total-live', overview.live_portfolios, 'textContent');
                
                // Update draft portfolios
                putTextinElementById('#total-draft', overview.draft_portfolios, 'textContent');
                
                // Update total file size
                if (overview.total_file_size !== undefined) {
                    putTextinElementById('#total-file-size', filesizeComputation(overview.total_file_size), 'textContent');
                }
            }
        }
    } catch (error) {
        console.error('Failed to fetch portfolio statistics:', error);
    }
    loader()
    updates();
    const tabs = document.querySelectorAll('.tabs .tab')
    switchTab(tabs, state, 'click', updates)
})