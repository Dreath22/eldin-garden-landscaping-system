import { moneySign,capitalize, putTextinElementById, buttonEventListener } from './utils/utils.js'
const loader = async () => {
  const response = await fetch('/landscape/USER_API/ServicesController.php?action=getServices')
  const servicesData = await response.json()
  const services = servicesData.services
  console.log('hello', services)
  console.log('lenght: ', services.length)
  let html = ''
  if(services.length > 0){
    html = '<option value=\'\'>Select service category</option>'
    services.forEach(service => {
      html += `<option data-value="${service.basePrice}" value="${service.id}">${capitalize(service.service_name)}</option>`
    })
  }else{
    html = '<option value=\'\'>No services found</option>'
  }
  putTextinElementById('#service_dropdown', html, 'innerHTML')
}

const buttonLoader = () => {
  let currentBasePrice = 0;
  const sqm = document.querySelector("#sqm");
  
  // Helper to get a clean number
  const getVal = (el) => parseFloat(el.value) || 0;
  
  // Helper to get current base price from selected service
  const getCurrentBasePrice = () => {
    const serviceDropdown = document.querySelector("#service_dropdown");
    if (serviceDropdown && serviceDropdown.selectedIndex > 0) {
      const selectedOption = serviceDropdown.options[serviceDropdown.selectedIndex];
      return parseFloat(selectedOption.dataset.value) || 0;
    }
    return 0;
  };

  // Plus Button
  buttonEventListener("#add-estimate", (e) => {
    e.preventDefault();
    let currentNum = getVal(sqm);

    if (currentNum < 1) { // If it's 0 or empty, start at 1
      sqm.value = (1).toFixed(2);
      updateCostCalculation(1);
      return;
    }
    
    if (currentNum >= 1000000) return;

    const newNum = currentNum + 1;
    sqm.value = newNum.toFixed(2);
    updateCostCalculation(newNum);
  }, 'click');

  // Minus Button
  buttonEventListener("#sub-estimate", (e) => {
    e.preventDefault();
    let currentNum = getVal(sqm);

    if (currentNum <= 0) {
      sqm.value = (0).toFixed(2);
      updateCostCalculation(0);
      return;
    }

    // Ensure we don't go below zero
    const result = currentNum - 1;
    const finalNum = result < 0 ? 0 : result;
    sqm.value = finalNum.toFixed(2);
    updateCostCalculation(finalNum);
  }, 'click');

  // Manual Input Listener
  buttonEventListener(sqm, (e, el) => {
    // When the user finishes typing and clicks away (change event),
    // we format their manual entry to 2 decimal places.
    let currentNum = getVal(el);
    
    if (currentNum > 1000000) currentNum = 1000000;
    if (currentNum < 0) currentNum = 0;
    el.value = currentNum.toFixed(2);
    
    // Calculate total cost using current base price
    const basePrice = getCurrentBasePrice();
    const totalCost = basePrice * currentNum;
    putTextinElementById("#estimated-cost", moneySign + totalCost.toFixed(2))
    putTextinElementById("#approx-value", basePrice, 'value')
    putTextinElementById("#baseprice", moneySign + basePrice)
      
    // Update hidden input for form submission
    putTextinElementById("#estimated-cost-input", totalCost.toFixed(2), 'value');
  }, 'change');

  buttonEventListener("#service_dropdown", (e, el)=>{
    const selectedOption = el.options[el.selectedIndex];
    currentBasePrice = parseFloat(selectedOption.dataset.value) || 0;
    console.log("Base Price: ", currentBasePrice)
    
    putTextinElementById("#baseprice", moneySign + currentBasePrice)
    
    // Update cost calculation when service changes
    const currentSqm = getVal(sqm);
    updateCostCalculation(currentSqm);
  }, 'change')
}

// Helper function to update cost calculations
function updateCostCalculation(sqmValue) {
  let el = document.querySelector("#service_dropdown")
  if (!el || el.selectedIndex <= 0) return;
  
  const selectedOption = el.options[el.selectedIndex];
  const basePrice = parseFloat(selectedOption.dataset.value) || 0;
  const totalCost = basePrice * sqmValue;
  putTextinElementById("#estimated-cost", moneySign + totalCost.toFixed(2))
  putTextinElementById("#approx-value", basePrice, 'value')
  
  // Update hidden input for form submission
  putTextinElementById("#estimated-cost-input", totalCost.toFixed(2), 'value');
  
}

document.addEventListener("DOMContentLoaded", () => {
  loader();
  buttonLoader();

    // loader();
})