
function renderToWrapper(wrapperId, rawData) {
    const wrapper = document.getElementById(wrapperId);
    const svgNS = "http://www.w3.org/2000/svg";

    // 1. GET ACTUAL WRAPPER DIMENSIONS
    // This is where you manipulate the height/width for all next computations
    const width = wrapper.clientWidth; 
    const height = wrapper.clientHeight || 400; // Fallback if height isn't set in CSS

    // 2. Adjust margins based on total size
    // Logic: If the wrapper is small, shrink the margins automatically
    const margin = { 
        top: height * 0.1, 
        right: width * 0.05, 
        bottom: height * 0.2, 
        left: width * 0.12 
    };

    const chartW = width - margin.left - margin.right;
    const chartH = height - margin.top - margin.bottom;

    // 3. Initialize SVG with Dynamic ViewBox
    wrapper.innerHTML = ''; // Clear previous render
    const svg = document.createElementNS(svgNS, "svg");
    svg.setAttribute("viewBox", `0 0 ${width} ${height}`);
    svg.setAttribute("width", "100%");  // Fill wrapper width
    svg.setAttribute("height", "100%"); // Fill wrapper height
    
    // 4. Log Scale Calculations (Mapping Data to the Measured Height)
    const values = rawData.map(d => d.value);
    const logMin = Math.log10(Math.min(...values) || 1);
    const logMax = Math.log10(Math.max(...values));

    const getLogY = (val) => {
        const logVal = Math.log10(val || 1);
        const normalized = (logVal - logMin) / (logMax - logMin);
        return chartH - (normalized * chartH) + margin.top;
    };

    // 5. Render Items
    const barWidth = (chartW / rawData.length) * 0.7;
    const gap = (chartW / rawData.length) * 0.3;

    rawData.forEach((d, i) => {
        const xPos = margin.left + (i * (barWidth + gap)) + (gap / 2);
        const yPos = getLogY(d.value);
        const barH = (margin.top + chartH) - yPos;

        // Create Rectangle
        const rect = document.createElementNS(svgNS, "rect");
        rect.setAttribute("x", xPos);
        rect.setAttribute("y", yPos);
        rect.setAttribute("width", barWidth);
        rect.setAttribute("height", barH);
        rect.setAttribute("fill", "teal");
        svg.appendChild(rect);

        // Logic: Dynamically adjust font size based on wrapper width
        const label = document.createElementNS(svgNS, "text");
        label.textContent = d.label;
        label.setAttribute("x", xPos + barWidth / 2);
        label.setAttribute("y", height - (margin.bottom / 2));
        label.setAttribute("font-size", `${Math.max(10, width / 40)}px`); // Responsive font
        label.setAttribute("text-anchor", "middle");
        svg.appendChild(label);
    });

    wrapper.appendChild(svg);
}
// Example Usage:
const complexData = [
    { label: "0", value: 0 },
    { label: "1", value: 100 },
    { label: "2", value: 500 },
    { label: "3", value: 1000000 },
    { label: "4", value: 100 },
    { label: "5", value: 500 },
    { label: "6", value: 1000000 } 
]
// Re-render when the window resizes to ensure it always fits
window.addEventListener('DOMContentLoaded', () => {
    renderToWrapper('chart-wrapper', complexData);
});
window.addEventListener('resize', () => {
    renderToWrapper('chart-wrapper', complexData);
});