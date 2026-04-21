import { filesizeComputation, generateCsrfToken, emptyElement, clearElementError, moneySign, switchTab, putTextinElementById, buttonEventListener, renderPagination, capitalize, log } from './utils/utils.js'
import { fetchPorfolio } from './utils/apiUtils.js';
const state = {
    currentPage: 1,
    currentTab: 'all',
    service_id: 1,
    sort: 'new',
    limit: 3,
    uploads: {
        files: [],
        fileSize: 0,
    }
};
let csrfToken = ""

const fixedstate = {
    currentPage: 1,
    currentTab: 'all',
    service_id: 1,
    sort: 'new',
    limit: 6
}
const controllerPath = "/landscape/USER_API/PortfolioController.php";
const base = "http://localhost/landscape/";

// Field configuration for validation
const uploadFields = {
    title: {
        elementId: 'contentTitle',
        required: true,
        errorMessage: 'Title is required',
        validation: (value) => value.trim().length > 0
    },
    description: {
        elementId: 'contentDescription', 
        required: true,
        errorMessage: 'Description is required',
        validation: (value) => value.trim().length > 0
    },
    serviceId: {
        elementId: 'contentCategory',
        required: true,
        errorMessage: 'Please select a service category',
        validation: (value) => value !== ''
    },
    status: {
        elementId: 'contentStatus',
        required: true,
        errorMessage: 'Please select a status',
        validation: (value) => value !== ''
    }
};

// Validation function for upload fields
function validateUploadFields(fields) {
    const errors = [];
    const validFields = [];
    
    Object.entries(fields).forEach(([fieldName, fieldConfig]) => {
        const element = document.getElementById(fieldConfig.elementId);
        const value = element ? element.value.trim() : '';
        
        if (fieldConfig.required && !fieldConfig.validation(value)) {
            // Use emptyElement to highlight field
            emptyElement(element, fieldConfig.errorMessage);
            errors.push({
                field: fieldName,
                message: fieldConfig.errorMessage,
                element: element
            });
        } else {
            // Clear any previous error state
            clearElementError(element);
            validFields.push(fieldName);
        }
    });
    
    return { isValid: errors.length === 0, errors, validFields };
}



const filterTodaysData = (data) =>{
  if(!data) return
  console("datas", data.filter(d => d.date === new Date().toISOString().split('T')[0])) 
}
const uploads = {
  title: "",
  description: "",
  files: [],
  fileSize: 0,
}
buttonEventListener("#fileInput", (e, element) => {
    const fileInput = e.target;
  
    // Convert FileList to Array and add to our tracker
    const newFiles = Array.from(fileInput.files);
    const newFileSize = newFiles.reduce((acc, file) => acc + file.size, 0);
    if(newFileSize > 10485760) {
        alert("File size exceeds 10MB");
        uploads.files = []
        renderPreviews();
        return;
    }
    uploads.files = uploads.files.concat(newFiles);
    uploads.fileSize = newFileSize;
    renderPreviews();
    updateInputFiles();
}, 'change')
// A reusable function to handle the preview update logic
const updatePreview = (inputElement, targetid, placeholder) => {
  // Use the input value if it exists, otherwise fallback to placeholder
  const newValue = inputElement.value.trim() !== '' ? inputElement.value : placeholder;
  putTextinElementById(targetid, newValue);
  return newValue
};

// Title Listener
buttonEventListener("#contentTitle", (e, element) => {
  uploads.title = updatePreview(element, "#title-changes", "Example Title");
}, 'input');

// Description Listener
buttonEventListener("#contentDescription", (e, element) => {
  uploads.description = updatePreview(element, "#description-changes", "Lorem ipsum dolor sit amet, consectetur adipisicing elit. Neque molestias vitae dolor repellendus molestiae eveniet numquam odio, quas veniam magni in perspiciatis commodi iste sapiente quia asperiores earum provident sed.");
}, 'input');

function renderPreviews(num=null) {
  const preview = document.getElementById('uploadPreview');
  preview.innerHTML = '';
  if(num){
    uploads.files = []
    uploads.fileSize = 0
    preview.innerHTML = "";
    return
  } 
  if(uploads.files.length<=0){
    preview.innerHTML = '<p>No files selected</p>';
    return;
  }
  uploads.files.forEach((file, index) => {
    const reader = new FileReader();
    reader.onload = function(e) {
      const div = document.createElement('div');
      div.className = 'upload-preview-item';
      div.innerHTML = `
        <img src="${e.target.result}" alt="Preview">
        <button class="upload-preview-remove">
          <i class="fas fa-times"></i>
        </button>
      `;

      // True removal logic
      div.querySelector('.upload-preview-remove').onclick = () => {
        removeFile(index);
      };

      preview.appendChild(div);
    };
    reader.readAsDataURL(file);
  });
}

function removeFile(index) {
  uploads.files.splice(index, 1); // Remove from our array
  renderPreviews();              // Refresh UI
  updateInputFiles();           // Sync back to the input
}

function updateInputFiles() {
    const fileInput = document.getElementById('fileInput');
    const dataTransfer = new DataTransfer();
    uploads.files.forEach(file => {
    dataTransfer.items.add(file);
    });

    // This is the magic line that actually updates the input
    fileInput.files = dataTransfer.files;
    if(fileInput.files[0]){
        const tempURL = URL.createObjectURL(fileInput.files[0]);
        putTextinElementById("#preview-card-image", `background-image: url('${tempURL}');`, 'style')
    }
}

buttonEventListener("#upload-submit", (e, element) => {
    e.preventDefault();
    e.target.disabled = true;
    
    // 1. Validate all required fields
    const validation = validateUploadFields(uploadFields);
    
    if (!validation.isValid) {
        // Show first error message to user
        alert(validation.errors[0].message);
        validation.errors[0].element.focus();
        return;
    }
    
    // 2. Validate file uploads
    if (!uploads.files || uploads.files.length === 0) {
        alert('Please select at least one file to upload');
        return;
    }
    
    // 3. Get CSRF token and prepare FormData for file upload
    
    // Create FormData for file upload
    const formData = new FormData();
    formData.append('title', document.getElementById('contentTitle').value.trim());
    formData.append('description', document.getElementById('contentDescription').value.trim());
    formData.append('serviceId', document.getElementById('contentCategory').value);
    formData.append('status', document.getElementById('contentStatus').value);
    formData.append('csrf_token', csrfToken);
    
    // Add files to FormData
    Array.from(uploads.files).forEach((file, index) => {
        formData.append(`files[${index}]`, file);
    });
    
    // 4. Submit to API with proper error handling
    fetch('/landscape/USER_API/PortfolioController.php?action=create', {
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
        console.log('Upload successful:', data);
        
        // Log the success message
        if (data.message) {
            console.log('Server message:', data.message);
        }
        
        // Show success feedback to user
        alert(data.message || 'Content uploaded successfully!');
        
        if(data.status !== "error"){
          // Reset form on success
        document.getElementById('uploadForm').reset();
        clearElementError('#contentTitle');
        clearElementError('#contentDescription');
        clearElementError('#contentCategory');
        clearElementError('#contentStatus');
        renderPreviews(1);
        }
        } else if (data.status === 'error') {
            // Handle validation errors
            if (data.errors) {
                // Show field-specific errors
                Object.entries(data.errors).forEach(([field, message]) => {
                    const element = document.getElementById(field === 'title' ? 'contentTitle' : 
                                                             field === 'description' ? 'contentDescription' :
                                                             field === 'serviceId' ? 'contentCategory' :
                                                             field === 'status' ? 'contentStatus' : field);
                    if (element) {
                        emptyElement(element, message);
                    }
                });
                alert('Please fix the validation errors.');
            } else {
                alert(data.message || 'Upload failed');
            }
        }
    })
    .catch(error => {
        console.error('Upload failed:', error);
        
        // Log error details
        console.error('Error details:', {
            message: error.message,
            formData: formData,
            timestamp: new Date().toISOString()
        });
        
        // Show user-friendly error message
        alert('Upload failed: ' + error.message);
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
     html = "<option value=''>Select service category</option>"
    services.forEach(service => {
      html += `<option value="${service.id}">${capitalize(service.service_name)}</option>`
    })
  }else{
    html = "<option value=''>No services found</option>"
  }
  putTextinElementById('#contentCategory', html, 'innerHTML')
}

const urlGet = (data) =>{
  console.log("urlGet: ", data)
  const files_data = JSON.parse(data.files).files
  const cleanRelativePath = (data.dir_path + files_data[0].stored_name).replace(/^[\./]+/, "");
  const absoluteUrl = base + cleanRelativePath;
  return absoluteUrl;
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
      url: url,
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
const renderPortfolios = (dataArray, htmlTemplate, elementId, maxItems = 3) => {
  let accumulatedHtml = '';
  if (!dataArray || !dataArray.length){
    accumulatedHtml = "<p>No Recent Data</p>";
    putTextinElementById(elementId, accumulatedHtml, 'innerHTML');
    return;
  };
  
  console.log(`Rendering ${maxItems} portfolios to ${elementId}`);
  
  // Loop through items and accumulate HTML
  
  const itemsToProcess = Math.min(maxItems, dataArray.length);
  
  for (let i = 0; i < itemsToProcess; i++) {
    const portfolioHtml = htmlTemplate(dataArray[i]);
    accumulatedHtml += portfolioHtml;
  }
  
  // Display the accumulated HTML
  putTextinElementById(elementId, accumulatedHtml, 'innerHTML');
  console.log(`Rendered ${itemsToProcess} portfolio items`);
};

// HTML template for recent uploads (card format)
const recentCardTemplate = (data) => {
  const rowInfo = rowData(data);
  
  return `
    <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background-color: #f8fafc; border-radius: 8px;">
      <img src="${rowInfo.url}" style="width: 50px; height: 50px; border-radius: 6px; object-fit: cover;">
      <div style="flex: 1;">
        <p style="font-weight: 600; font-size: 0.9rem;">${rowInfo.title}</p>
        <p style="font-size: 0.8rem; color: var(--text-gray);">${rowInfo.date}</p>
      </div>
      <span class="status-badge ${rowInfo.statusClass}">${rowInfo.statusText}</span>
    </div>
  `;
};

// Specific function using the generic renderer
const recentLoader = (dataArray) => {
  renderPortfolios(dataArray, recentCardTemplate, "#recentUploaded", 3);
};


// HTML template for table rows
const tableRowTemplate = (data) => {
  const rowInfo = rowData(data);
  
  return `
    <tr>
      <td><img src="${rowInfo.url}" style="width: 50px; height: 50px; border-radius: 6px; object-fit: cover;"></td>
      <td>${rowInfo.title}</td>
      <td>${rowInfo.service_name}</td>
      <td>${rowInfo.filesize}</td>
      <td>${rowInfo.date}</td>
      <td><span class="status-badge ${rowInfo.statusClass}">${rowInfo.statusText}</span></td>
      <td>
        <div class="table-actions">
          <button class="table-btn view" title="View"><i class="fas fa-eye"></i></button>
          <button class="table-btn edit" title="Edit"><i class="fas fa-edit"></i></button>
          <button class="table-btn delete" title="Delete"><i class="fas fa-trash"></i></button>
        </div>
      </td>
    </tr>
  `;
};

// Specific function using the generic renderer
const tableBody = (dataArray) => {
  renderPortfolios(dataArray, tableRowTemplate, "#portfolioTable", dataArray.length);
};

// Add real-time validation to clear errors when users start typing
document.addEventListener('DOMContentLoaded', async() => {
  // Generate CSRF token for form
  await generateCsrfToken();
  csrfToken = document.querySelector('input[name="csrf_token"]')?.value
  let data = await fetchPorfolio(controllerPath, state, csrfToken, undefined, 5);
  let data1 = await fetchPorfolio(controllerPath, fixedstate, csrfToken);
  console.log("data: ", data);
  console.log("data pagination: ", data.data.pagination)
  tableBody(data.data.data)
  if (data.data.pagination) {
    renderPagination(fetchPorfolio, data.data.pagination.totalRecords, state, () => fetchData(state.currentTab));
  }
  recentLoader(data1.data.data)
  loader();
  
  
  // Clear errors when user starts typing in any field
  Object.values(uploadFields).forEach(fieldConfig => {
    const element = document.getElementById(fieldConfig.elementId);
    if (element) {
      console.log("test: ",element)
      element.addEventListener('input', () => {
        clearElementError(element);
      });
      
      // Also clear errors on change for select elements
      if (element.tagName === 'SELECT') {
        element.addEventListener('change', () => {
          clearElementError(element);
        });
      }
    }
  });
});