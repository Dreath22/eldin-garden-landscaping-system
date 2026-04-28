import { generateCsrfToken, putTextinElementById, renderPagination, rowData } from './utils/utils.js'
import { fetchPorfolio } from './utils/apiUtils.js'
// import { ModalSystem, ToastSystem } from './utils/modal.js'
// { filesizeComputation, generateCsrfToken, emptyElement, clearElementError, moneySign, switchTab, putTextinElementById, buttonEventListener, capitalize, log } 
const controllerPath = '/landscape/USER_API/PortfolioController.php'

let csrfToken = ''
let data;

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
}

// Fetch data function for reloading
const fetchData = async (tab = state.currentTab) => {
  state.currentTab = tab
  data = await fetchPorfolio(controllerPath, state, csrfToken, undefined, 5)
  console.log('Reloaded data: ', data)
  console.log('data pagination: ', data.data.pagination)
  galleryBody(data.data.data)
   if (data.data.pagination) {
    renderPagination(fetchPorfolio, data.data.pagination.totalPages, state, () => fetchData(state.currentTab))
  }
}

    
// HTML template for table rows
const tableRowTemplate = (data) => {
  const rowInfo = rowData(data)
  
  return `
    <div class="gallery-item">
        <img src="${rowInfo.url}" alt="${rowInfo.title}" title="${rowInfo.title}">
        <div class="gallery-overlay">
          <h3>${rowInfo.title}</h3>
          <p>${rowInfo.description}</p>
        </div>
      </div>
  `
}


// Generic portfolio renderer - DRY principle
const renderPortfolios = (dataArray, htmlTemplate, elementId, maxItems = 3) => {
  let accumulatedHtml = ''
  if (!dataArray || !dataArray.length){
    accumulatedHtml = '<p>No Recent Data</p>'
    putTextinElementById(elementId, accumulatedHtml, 'innerHTML')
    return
  };
  
  console.log(`Rendering ${maxItems} portfolios to ${elementId}`)
  
  // Loop through items and accumulate HTML
  
  const itemsToProcess = Math.min(maxItems, dataArray.length)
  
  for (let i = 0; i < itemsToProcess; i++) {
    const portfolioHtml = htmlTemplate(dataArray[i])
    accumulatedHtml += portfolioHtml
  }
  
  // Display the accumulated HTML
  putTextinElementById(elementId, accumulatedHtml, 'innerHTML')
  console.log(`Rendered ${itemsToProcess} portfolio items`)
}

const buttonsLoader = () => {
    //
}

// Specific function using the generic renderer
const galleryBody = (dataArray) => {
  renderPortfolios(dataArray, tableRowTemplate, '.gallery-grid', dataArray.length)
  buttonsLoader(dataArray)
}

// Add real-time validation to clear errors when users start typing
document.addEventListener('DOMContentLoaded', async() => {
  // Generate CSRF token for form
  await generateCsrfToken()
  csrfToken = document.querySelector('input[name="csrf_token"]')?.value
  
  // Use fetchData for initial load
  await fetchData(state.currentTab)
})