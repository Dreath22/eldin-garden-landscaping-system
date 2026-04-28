import { putTextinElementById, capitalize, renderPagination, moneySign } from './utils/utils.js'
const state = {
    currentPage: 1,
    currentTab: 'all',
    order: 'DESC',
    limit: 6,
    total_pages: 1
}

const controllerPath = 'USER_API/ServicesController.php'


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


const displayData = (data)=>{
    console.log("Displaying data:", data.uploads.length);
    let html = ''
    if(data.uploads.length > 0){
    data.uploads.forEach(item => {
        const rawFeatures = item?.features ?? "";

        // 2. The Logic Chain
        const parsedFeatures = rawFeatures
        .split('\n')
        .filter(line => line.trim() !== '') // Removes empty lines
        .map(line => {
            const trimmed = line.trim();
            // 3. Capitalize the first letter of every line
            return `<li>${trimmed.charAt(0).toUpperCase() + trimmed.slice(1)}</li>`;
        })
        .join('');

        // Final Output
        const listOfServices = parsedFeatures ? `<ul class="service-features">${parsedFeatures}</ul>` : '<p>No features available.</p>';

        
        html += `
        <div class="service-detail">
            <div class="service-detail-image" style="background-image: url('https://images.unsplash.com/photo-1585320806297-9794b3e4eeae?w=600')"></div>
            <div class="service-detail-content">
            <h3>${capitalize(item.service_name)} Services</h3>
            <p>${item.description}</p>
            ${ listOfServices }
            <div class="service-item-price">${moneySign + (item.base_price || 0)}<span style="font-size: 0.8rem; color: var(--text-gray);">/base-price</span></div>
            </div>
        </div>`
        })
        renderPagination(fetchData, data.pagination.totalRecords, state, () => fetchData(state.currentTab))
        // document.getElementById('pagination').style.display = 'flex'
    }else{
        html = "<h4>No Content</h4>"
        putTextinElementById('#pagination', '', 'innerHTML')
        document.getElementById('pagination').style.display = 'none';
    }
    putTextinElementById('#service_list', html, 'innerHTML');
    //setupButtonListeners();
    
}

const stats = () => {
    //
} 

document.addEventListener('DOMContentLoaded', ()=> {
    fetchData(state.currentPage)
})