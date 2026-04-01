import { switchTab, formatToCalendar, renderPagination, capitalize, log } from './utils/utils.js'
const state = {
    currentPage: 1,
    currentTab: 'all',
    status: "all",
    order: 'newest',
    limit: 6,
    total_pages: 1,
}


const fetchData = (params) => {
    const queryString = new URLSearchParams(params).toString();
    return fetch(`USER_API/UploadsController.php?action=list&${queryString}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            console.log("Success:", data);
            displayData(data.data.uploads)
            renderPagination(fetchData, data.data.pagination.totalRecords, state, () => fetchData())
        })
        .catch(error => {
            console.error("Error in fetching:", error);
        });
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
                    <div class="service-item-price">$${item.base_price}<span style="font-size: 0.8rem; color: var(--text-gray);">/base-price</span></div>
                    <div class="table-actions">
                      <button class="table-btn view" title="View" onclick="viewService('${item.id}')"><i class="fas fa-eye"></i></button>
                      <button class="table-btn edit" title="Edit" onclick="editService('${item.id}')"><i class="fas fa-edit"></i></button>
                      <button class="table-btn delete" title="Delete" onclick="deleteService('${item.id}')"><i class="fas fa-trash"></i></button>
                    </div>
                  </div>`;
    });
    document.getElementById('services_list').innerHTML = html;
}

window.getServiceRecord = (id) => {
    return fetch(`USER_API/UploadsController.php?action=list&id=${id}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            console.log("Service Record:", data);
            data.data.service_name = capitalize(data.data.service_name)
            return data;
        })
        .catch(error => {
            console.error("Error in fetching:", error);
        });
}

document.addEventListener('DOMContentLoaded', ()=> {
    fetchData(state);
});
