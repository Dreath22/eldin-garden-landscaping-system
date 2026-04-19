// apiUtils.js
export const baseRequest = async (action, state, params = {}) => {
    // Merge default state params with custom params
    const queryString = new URLSearchParams({
        action: action,
        page: state.currentPage,
        limit: state.limit || 5,
        ...params // Overwrites defaults if provided
    }).toString();

    const formData = new FormData();
    formData.append('csrf_token', state.csrfToken);

    try {
        const response = await fetch(`${controllerPath}?${queryString}`, {
            method: 'POST',
            body: formData
        });

        if (!response.ok) throw new Error(`HTTP Error: ${response.status}`);
        
        return await response.json();
    } catch (error) {
        console.error(`API Error (${action}):`, error);
        throw error; // Re-throw so the caller can handle it if needed
    }
};

export const fetchPorfolio = async (path, state, tab = state.currentTab, limit=null) => {
    // 1. GET Parameters (Filters/Pagination)
    const queryString = new URLSearchParams({
        action: 'list',
        page: state.currentPage,
        tab: tab,
        sort: state.sort,
        category: state.service_id,
        limit: limit ? limit : state.limit
    }).toString();

    // 2. POST Data (Security/Sensitive Info)
    const formData = new FormData();
    formData.append('csrf_token', state.csrfToken);

    const response = await fetch(`${path}?${queryString}`, {
        method: 'POST', // Changing to POST makes $_POST available
        body: formData   // This goes into $_POST
    });
    if (!response.ok) throw new Error(`HTTP Error: ${response.status}`);
    const data = await response.json();
    return data;
}