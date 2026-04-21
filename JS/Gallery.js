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
const controllerPath = "/landscape/USER_API/PortfolioController.php";
const base = "http://localhost/landscape/";
let data;
const urlGet = (data) =>{
  console.log("urlGet: ", data)
  const files_data = JSON.parse(data.files).files
  const urls = files_data.map(file => {
    const cleanRelativePath = (data.dir_path + file.stored_name).replace(/^[\./]+/, "");
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
    <div class="gallery-item-admin">
        <img src="${rowInfo.url}" alt="${rowInfo.service_name}" title=="${rowInfo.service_name}">
        <div class="gallery-item-overlay">
        <button class="table-btn view" data-id="${rowInfo.id}" title="View" onclick="viewImage('${rowInfo.title}')"><i class="fas fa-eye"></i></button>
        <button class="table-btn edit" data-id="${rowInfo.id}" title="Edit" onclick="editImage('${rowInfo.title}')"><i class="fas fa-edit"></i></button>
        <button class="table-btn delete" data-id="${rowInfo.id}" title="Delete" onclick="deleteImage('${rowInfo.title}')"><i class="fas fa-trash"></i></button>
        </div>
        <div class="gallery-item-info">
        <h5>${rowInfo.title}</h5>
        <p>${rowInfo.service_name} * ${rowInfo.date}</p>
        </div>
        <div style="position: absolute; top: 0.5rem; left: 0.5rem;">
        <input type="checkbox" class="checkbox" style="width: 20px; height: 20px;">
        </div>
        <div style="position: absolute; top: 0.5rem; right: 0.5rem;">
        <span class="status-badge ${rowInfo.statusClass}" style="font-size: 0.65rem;">${rowInfo.statusText}</span>
        </div>
    </div>
  `;
}

const buttonsLoader = () => {
  document.querySelectorAll(".view").forEach(element => {
  buttonEventListener(element, (el, element)=>{
    const portfolioId = element.dataset.id;
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
});
}

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
    loader()
    updates();
    const tabs = document.querySelectorAll('.tabs .tab')
    switchTab(tabs, state, 'click', updates)
})