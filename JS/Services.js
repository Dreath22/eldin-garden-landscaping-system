import { moneySign, switchTab, putTextinElementById, ButtonsEventListener, renderPagination, capitalize, log } from './utils/utils.js'
const state = {
    currentPage: 1,
    currentTab: 'all',
    status: "all",
    order: 'newest',
    limit: 6,
    total_pages: 1,
    allService_name_basePrice: []
}

const serviceStatus = [
    {value: 'active', label: 'Active'},
    {value: 'inactive', label: 'Inactive'},
    {value: 'cancelled', label: 'Cancelled'}
]

/*  REUSABLE FUNCTION  */
const toggleModal = (selector, show, hide="none") => {
    try{
        element = document.querySelector(selector)
        if(element){
            if(element.style.display == show){
                element.style.display = hide;
                return;
            }
            element.style.display = show;
        }
    }catch(error){
        console.error("Error in toggling modal:", error);
    }
    
}


const getServiceRecord = (id) => {
    return fetch(`USER_API/ServicesController.php?action=list&id=${id}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            console.log("Service Record:", data.data);
            data.data.service_name = capitalize(data.data.service_name)
            return data.data;
        })
        .catch(error => {
            console.error("Error in fetching:", error);
        });
}



const fetchData = (params) => {
    const queryString = new URLSearchParams(params).toString();
    return fetch(`USER_API/ServicesController.php?action=list&${queryString}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            console.log("Success:", data.data);
            displayData(data.data.uploads)
            stats(data.data.summary)
            renderPagination(fetchData, data.data.pagination.totalRecords, state, () => fetchData())
        })
        .catch(error => {
            console.error("Error in fetching:", error);
        });
}

const stats = (data) => {
    if(data){
        putTextinElementById('total_services', data.total_services);
        putTextinElementById('active_services', data.live_services);
        putTextinElementById('inactive_services', data.cancelled_services);
        putTextinElementById('avg_order_value', `${moneySign}${data.total_baseprice}`);
    }
}

const displayData = (data)=>{
    console.log("Displaying data:", data);
    let html = ''
    data.forEach(item => {
        html += `<div class="service-item">
                    <img src="https://images.unsplash.com/photo-1585320806297-9794b3e4eeae?w=200" alt="Service" class="service-item-image">
                    <div class="service-item-info">
                      <h4>${capitalize(item.service_name)} Services</h4>
                      <p>${item.description}</p>
                      <div style="margin-top: 0.5rem;">
                        <span class="status-badge active">${item.status}</span>
                        <span style="color: var(--text-gray); font-size: 0.85rem; margin-left: 0.5rem;"><i class="fas fa-star" style="color: var(--star-orange);"></i> ${item.rating ?? 0.0} (${item.review_count ?? 0} reviews)</span>
                      </div>
                    </div>
                    <div class="service-item-price">${moneySign + (item.base_price || 0)}<span style="font-size: 0.8rem; color: var(--text-gray);">/base-price</span></div>
                    <div class="table-actions">
                      <button class="table-btn view click-views" data-id="${item.id}" title="View"><i class="fas fa-eye"></i></button>
                      <button class="table-btn edit click-edits" title="Edit" data-id="${item.id}"><i class="fas fa-edit"></i></button>
                      <button class="table-btn delete click-deletes" title="Delete" data-id="${item.id}"><i class="fas fa-trash"></i></button>
                    </div>
                  </div>`;
    });
    putTextinElementById('services_list', html, 'innerHTML');
    setupButtonListeners();
}

const viewService = (id) => {
      toggleModal('#viewServiceModal', 'flex');
      getServiceRecord(id).then(data => { 
        console.log(data);
        if (data) {
          putTextinElementById('viewServiceModalName', (data.service_name || 'Unknown') + " Services");
          putTextinElementById('viewServiceModalPrice', moneySign + (data.base_price || 0));
          putTextinElementById('viewServiceModalStatus', data.status || 'Inactive');
          putTextinElementById('viewServiceModalRating', (data.rating ?? 0.0));
          putTextinElementById('viewServiceModalDescription', data.description || 'No description available');
          putTextinElementById('viewServiceModalCount', data.count || 0);
          putTextinElementById('viewServiceModalDuration', data.duration || 'N/A');
          // Handle features list
          const featuresList = document.querySelector('#viewServiceModal ul');
          if (data.features && Array.isArray(data.features)) {
            featuresList.innerHTML = data.features.map(feature => 
              `<li style="padding: 0.3rem 0;"><i class="fas fa-check" style="color: var(--primary-green); margin-right: 0.5rem;"></i> ${feature}</li>`
            ).join('');
          } else {
            featuresList.innerHTML = '<li style="padding: 0.3rem 0;"><i class="fas fa-check" style="color: var(--primary-green); margin-right: 0.5rem;"></i> No features listed</li>';
          }
          ButtonsEventListener("#viewServiceModalEditButton", ()=>{
            toggleModal('#viewServiceModal', 'none');
            editService(id, data.data);
          });
      }
    })
}

// Edit Service Modal
const editService = async (id, data=null) => {
    toggleModal('#editServiceModal', 'flex');
    if (!data){ 
        data = await getServiceRecord(id);
    }
    putTextinElementById('editServiceName', data.service_name, 'value');
    putTextinElementById('editServiceDescription', data.description || 'No description available', 'value');
    putTextinElementById('editServicePrice',  (data.base_price || 0), 'value');
    putTextinElementById('editServiceDuration', data.duration || 'N/A', 'value');
     
    serviceStatus.forEach((status)=>{
        const option = document.createElement('option');
        option.value = status.value;
        option.textContent = status.label;
        if(status.value === data.status){
            option.selected = true;
        }
        document.getElementById('editServiceStatus').appendChild(option);
    })
    
}

// Set up button event listeners
function setupButtonListeners() {
    ButtonsEventListener('.click-views', (clickedBtn)=>{
        console.log("Btn Listener 1 Clicked")
        const id = clickedBtn.getAttribute('data-id');
        viewService(id);
    })
    ButtonsEventListener('.click-edits', (clickedBtn)=>{
        const id = clickedBtn.getAttribute('data-id');
        editService(id);
    })
    ButtonsEventListener('.click-deletes', (clickedBtn)=>{
        const id = clickedBtn.getAttribute('data-id');
        deleteService(id);
    })
}

document.addEventListener('DOMContentLoaded', ()=> {
    fetchData(state);
    document.querySelectorAll('.moneySign').forEach(element => element.textContent = moneySign);
    document.querySelectorAll('#userTabsContainer .tab').forEach((tabElement) => {
        tabElement.addEventListener('click', (event) => {
            state.currentTab = tabElement.getAttribute('data-tab')
            switchTab(state.currentTab, event, fetchData)
        })
    })
});