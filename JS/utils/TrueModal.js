const modalStyles = {
    info: {
        icon: 'ℹ️',
        bg: '#ffffff',
        text: '#1e3a8a',
        accent: '#3b82f6',
        border: 'transparent'
    },
    success: {
        icon: '✅',
        bg: '#f0fdf4',
        text: '#166534',
        accent: '#22c55e',
        border: '#bbf7d0'
    },
    warning: {
        icon: '⚠️',
        bg: '#fffbeb',
        text: '#92400e',
        accent: '#f59e0b',
        border: '#fcd34d'
    },
    error: {
        icon: '❌',
        bg: '#fef2f2',
        text: '#991b1b',
        accent: '#ef4444',
        border: '#fecaca'
    },
    database: {
        icon: '🔌',
        bg: '#fff7ed',
        text: '#9a3412',
        accent: '#f97316',
        border: '#ffedd5'
    }
};
/**
 * @param {string} type - Key from modalTemplates (info, success, warning, error, database or 'custom')
 * @param {string} title - Header text
 * @param {string} message - Body text
 * @param {object} custom - Optional: { icon, bg, text, accent, border }
 */
export const showModal = (type, title, message, custom = {}) => {
    // Merge template with any custom overrides
    const theme = { ...(modalStyles[type] || modalStyles.info), ...custom };

    // Create the element purely in memory
    const container = document.createElement('div');
    
    // Wrapper styles (centering logic)
    Object.assign(container.style, {
        position: 'fixed',
        top: '20px',
        width: '100%',
        display: 'flex',
        justifyContent: 'center',
        zIndex: '10000',
        pointerEvents: 'none',
        fontFamily: 'sans-serif'
    });

    // The Modal Card
    container.innerHTML = `
        <div style="
            pointer-events: auto; 
            background: ${theme.bg}; 
            color: ${theme.text}; 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            padding: 16px 24px; 
            border-radius: 12px; 
            box-shadow: 0 8px 30px rgba(0,0,0,0.12); 
            border: 1px solid ${theme.border}; 
            border-left: 6px solid ${theme.accent}; 
            min-width: 320px;
            cursor: pointer;
        " onclick="this.parentElement.remove()">
            <span style="font-size: 20px;">${theme.icon}</span>
            <div>
                <div style="font-weight: bold; font-size: 14px; margin-bottom: 2px;">${title}</div>
                <div style="font-size: 13px; opacity: 0.9; line-height: 1.4;">${message}</div>
            </div>
        </div>
    `;

    document.body.appendChild(container);

    // Auto-remove after 4 seconds if not clicked
    setTimeout(() => {
        if (container.parentElement) container.remove();
    }, 4000);
}