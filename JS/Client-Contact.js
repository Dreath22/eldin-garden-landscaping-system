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
  let value;
  const sqm = document.querySelector("#sqm");
  
  // Helper to get a clean number
  const getVal = (el) => parseFloat(el.value) || 0;

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
    
    // Calculate total cost
    const totalCost = value * currentNum;
    putTextinElementById("#estimated-cost", moneySign + totalCost.toFixed(2))
    putTextinElementById("#approx-value", value)
    putTextinElementById("#baseprice", moneySign + value)
    
    // Update hidden input for form submission
    const hiddenInput = document.querySelector("#estimated-cost-input");
    if (hiddenInput) {
      hiddenInput.value = totalCost.toFixed(2);
    }
  }, 'change');

  buttonEventListener("#service_dropdown", (e, el)=>{
    const selectedOption = el.options[el.selectedIndex];
    value = selectedOption.dataset.value;
    console.log("Val: ", value)
    
    putTextinElementById("#baseprice", moneySign + "" + value)
    
    // Update cost calculation when service changes
    const currentSqm = getVal(sqm);
    updateCostCalculation(currentSqm);
  }, 'change')
}

// Helper function to update cost calculations
function updateCostCalculation(sqmValue) {
  const totalCost = value * sqmValue;
  putTextinElementById("#estimated-cost", moneySign + totalCost.toFixed(2))
  putTextinElementById("#approx-value", value)
  
  // Update hidden input for form submission
  const hiddenInput = document.querySelector("#estimated-cost-input");
  if (hiddenInput) {
    hiddenInput.value = totalCost.toFixed(2);
  }
}

document.addEventListener("DOMContentLoaded", () => {
  loader();
  buttonLoader();

    // loader();
})