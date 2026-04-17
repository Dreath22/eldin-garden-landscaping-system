import { moneySign, switchTab, putTextinElementById, buttonEventListener, renderPagination, capitalize, log } from './utils/utils.js'
const state = {
    currentPage: 1,
    currentTab: 'all',
    order: 'DESC',
    limit: 6,
    total_pages: 1,
    allService_name_basePrice: []
}

const serviceStatus = [
    {value: 'active', label: 'Active'},
    {value: 'inactive', label: 'Inactive'},
    {value: 'cancelled', label: 'Cancelled'}
]

const controllerPath = 'USER_API/ServicesController.php'

/*  REUSABLE FUNCTION  */
const toggleModal = (selector, show, hide="none") => {
    try{
        const element = document.querySelector(selector)
        if(element){
            if(element.style.display == show){
                element.style.display = hide;
                return;
            }
            element.style.display = show;
        }else{
            console.log("selector not found", selector);
        }
    }catch(error){
        console.error("Error in toggling modal:", error);
    }
}


const getServiceRecord = (id) => {
    return fetch(`${controllerPath}?action=list&id=${id}`)
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

const fetchData = (tab=state.currentTab) => {
    const queryString = new URLSearchParams({
    page: tab,
    currentTab: state.currentTab,
    order: state.order,
    } ).toString();
    return fetch(`${controllerPath}?action=list&${queryString}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            console.log("Success:", data.data);
            displayData(data.data)
            stats(data.data.summary)
        })
        .catch(error => {
            console.error("Error in fetching:", error);
        });
}

const stats = (data) => {
    if(data){
        putTextinElementById('total_services', data.total_services);
        putTextinElementById('active_services', data.live_services);
        putTextinElementById('inactive_services', data.inactive_services);
        putTextinElementById('avg_order_value', `${moneySign}${data.total_baseprice/data.total_services}`);
    }
}

const displayData = (data)=>{
    
    console.log("Displaying data:", data.uploads.length);
    let html = ''
    if(data.uploads.length > 0){
        data.uploads.forEach(item => {
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
        renderPagination(fetchData, data.pagination.totalRecords, state, () => fetchData(state.currentTab))
        document.getElementById('pagination').style.display = 'flex';
    }else{
        html = "<h4>No Content</h4>";
        putTextinElementById('pagination', '', 'innerHTML');
        document.getElementById('pagination').style.display = 'none';
    }

    putTextinElementById('services_list', html, 'innerHTML');
    setupButtonListeners();
    
}

const viewService = (id) => {
      toggleModal('#viewServiceModal', 'flex');
      getServiceRecord(id).then(data => { 
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
          } else if(data.features){
            featuresList.innerHTML = `<li style="padding: 0.3rem 0;"><i class="fas fa-check" style="color: var(--primary-green); margin-right: 0.5rem;"></i> ${data.features}</li>`;
          }else {
            featuresList.innerHTML = '<li style="padding: 0.3rem 0;"><i class="fas fa-check" style="color: var(--primary-green); margin-right: 0.5rem;"></i> No features listed</li>';
          }

          document.querySelectorAll('.viewCloseModal').forEach(button => {
            buttonEventListener(button, ()=>{
              console.log('Close button clicked');    
              toggleModal('#viewServiceModal', 'none');
            });
          });
          buttonEventListener("#viewServiceModalEditButton", ()=>{
            toggleModal('#viewServiceModal', 'none');
            editService(id, data.data);
          });
      }
    })
}

const editService = async (id, data=null) => {
    toggleModal('#editServiceModal', 'flex');
    if (!data){ 
        data = await getServiceRecord(id);
    }
    putTextinElementById('editServiceName', data.service_name, 'value');
    putTextinElementById('editServiceDescription', data.description || 'No description available', 'value');
    putTextinElementById('editServicePrice',  (data.base_price || 0), 'value');
    putTextinElementById('editServiceDuration', data.duration || 'N/A', 'value');
    putTextinElementById('editServiceFeatures', data.features || 'No features available', 'value');
    putTextinElementById('editServiceStatus', '', 'innerHTML');
    
    // Clear existing options and add new ones
    serviceStatus.forEach((status)=>{
        const option = document.createElement('option');
        option.value = status.value;
        option.textContent = status.label;
        // Set selected attribute if status matches current data
        if(status.value === data.status.toLowerCase()) {
            option.selected = true;
        }
        document.getElementById('editServiceStatus').appendChild(option);
    })
    buttonEventListener(document.querySelector("#saveServiceChanges"), ()=>{
        updateService(id, data);
    })

    // Iterate over the elements with the class 'md-close'
    // and add an event listener to each one. When the button is clicked,
    // toggle the visibility of the #editServiceModal modal to 'none'.
    document.querySelectorAll('.md-close').forEach(button => {
        buttonEventListener(button, () => {
            toggleModal('#editServiceModal', 'none');
        });
    });
}

/** 
 * Validates if a value has changed and returns the new value or null
 * @param {string} selector - The CSS selector for the input element
 * @param {any} original - The original value to compare against
 * @param {string} type - The type of the value ('string', 'number', 'float')
 * @returns {any} - The new value if it changed, otherwise null
 */
const valueValidator = (selector, original, type) => {
    const inputElement = document.querySelector(selector);
    if (!inputElement) return null;
    let value = inputElement.value;

    if (type === 'number') {
        value = parseFloat(value);
        original = parseFloat(original);
    }
    return value !== original ? value : null;
};

//Update Service Fetch
const updateService = async(id, data) => {
    const editServiceName = document.querySelector('#editServiceName').value;
    const editServiceDescription = document.querySelector('#editServiceDescription').value;
    const editServicePrice = document.querySelector('#editServicePrice').value;
    const editServiceDuration = document.querySelector('#editServiceDuration').value;
    const editServiceFeatures = document.querySelector('#editServiceFeatures').value;
    const editServiceStatus = document.querySelector('#editServiceStatus').value;
    
    console.log("Form values:", {
        name: editServiceName,
        description: editServiceDescription == 'No description available' ? '': editServiceDescription,
        price: editServicePrice,
        duration: editServiceDuration == 'N/A' ? '' : editServiceDuration,
        features: editServiceFeatures,
        status: editServiceStatus
    });
    
    // Build params with all values (since we're not using valueValidator anymore)
    const params = new URLSearchParams();
    
    // Add all fields (they'll be validated on the backend)
    params.append('name', editServiceName);
    params.append('description', editServiceDescription === 'No description available' ? '' : editServiceDescription);
    params.append('baseprice', editServicePrice);
    params.append('duration', editServiceDuration === 'N/A' ? '' : editServiceDuration);
    params.append('features', editServiceFeatures);
    params.append('status', editServiceStatus);

  fetch(`${controllerPath}?action=update&id=${id}`, {
    method: 'POST',
    body: params
  }).then((response)=>{
    return response.json();

  }).then((data)=>{
    if (data.success) {
        
      toggleModal('#editServiceModal', 'flex')
      fetchData(state.currentTab);
    } else {
      console.log("Update failed:", data.message, data.errors);
    }
  }).catch((e)=>{
    console.log("ERROR UPDATING USER ", id, " ", e);
  })
}

function setupButtonListeners() {
    document.querySelectorAll('.click-views').forEach((el)=>{
        buttonEventListener(el, (clickedBtn)=>{
            const id = clickedBtn.getAttribute('data-id');
            viewService(id);
        })
    })
    document.querySelectorAll('.click-edits').forEach((el)=>{
        buttonEventListener(el, (clickedBtn)=>{
            const id = clickedBtn.getAttribute('data-id');
            editService(id);
        })
    })
    document.querySelectorAll('.click-deletes').forEach((el)=>{
        buttonEventListener(el, (clickedBtn)=>{
            const id = clickedBtn.getAttribute('data-id');
            deleteService(id);
        })
    })
}
/**
 * a function  that would add a services, has create as start name for the variables
 * would send it via POST oR Create to the Service Controller with the action create
 * done using fetch and then function
 */
const createService = () =>{
    // Define field selectors as a list for cleaner code
    const fieldSelectors = {
        name: '#serviceName',
        description: '#serviceDescription', 
        features: '#serviceFeatures',
        price: '#servicePrice',
        duration: '#serviceDuration',
        status: '#serviceStatus'
    };
    
    // Loop through selectors to get values
    const formData = {};
    const requiredFields = ['name', 'price'];
    const emptyFields = [];
    
    Object.keys(fieldSelectors).forEach(field => {
        const element = document.querySelector(fieldSelectors[field]);
        if (element) {
            const value = element.value.trim();
            formData[field] = value;
            
            // Check for empty required fields
            if (requiredFields.includes(field) && !value) {
                emptyFields.push(field);
            }
        }
    });
    
    // Build params with all values
    const params = new URLSearchParams();
    
    // Add all fields using loop (they'll be validated on the backend)
    Object.keys(formData).forEach(field => {
        let value = formData[field];
        if (value !== undefined && value !== null) {
            // Convert price field to number and map to baseprice
            if (field === 'price') {
                value = Number(value);
                params.append('baseprice', value);
            } else {
                params.append(field, value);
            }
        }
    });

    fetch(`${controllerPath}?action=create`, {
        method: 'POST',
        body: params
    }).then((response)=>{
        return response.json();
    }).then((data)=>{
        if (data.success) {
            console.log("Service created successfully:", data.message);
            // Close modal and refresh data
            toggleModal('#addServiceModal', 'none');
            fetchData(state.currentTab);
        } else {
            console.log("Create failed:", data.message, data.errors);
        }
    }).catch((e)=>{
        console.log("ERROR CREATING SERVICE: ", e);
    })
}

const deleteService = (id) => {
    console.log("clicked 1")
    fetch(controllerPath+"?action=delete&id="+id)
    .then((response)=>{
        return response.json();
    })
    .then((data)=>{
        if (data.success) {
            console.log('clicked2')
            fetchData(state.currentTab);
        }else{
            console.log("Delete failed:", data);
        }
    }).catch((e)=>{
        console.log("error: ", e)
    })
}


buttonEventListener("#showAddServiceModal", () => {
    toggleModal('#addServiceModal', 'flex');
    buttonEventListener("#confirmAddService", ()=>{
        createService();
    })
    document.querySelectorAll('.md-close').forEach(button => {
        buttonEventListener(button, () => {
            toggleModal('#addServiceModal', 'none');
        });
    });
})

document.addEventListener('DOMContentLoaded', ()=> {
    fetchData(1);
    document.querySelectorAll('.moneySign').forEach(element => element.textContent = moneySign);

    const tabs = document.querySelectorAll('#userTabsContainer .tab');
    state.currentTab = switchTab(tabs, state, 'click', fetchData);
    setupButtonListeners();

const magicSort = document.getElementById('magic-sort');

buttonEventListener(magicSort, () => {
    if(state.order == "DESC") {
        fetchData(1)
        state.order = "ASC";
        magicSort.innerHTML = '<i class="fas fa-sort-asc"></i> Ascending';
    }else{
        fetchData(1)
        state.order = "DESC";
        magicSort.innerHTML = '<i class="fas fa-sort-desc"></i> Descending';
    }
});
});