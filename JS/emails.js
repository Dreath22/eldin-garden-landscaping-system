import { moneySign, switchTab, putTextinElementById, buttonEventListener, renderPagination, capitalize, log } from './utils/utils.js'
const state = {
    currentPage: 1,
    currentTab: 'all',
    order: 'DESC',
    limit: 6,
    total_pages: 1,
    emails: []
}

const controllerPath = 'USER_API/EmailControllerSimple.php'

// Authentication state
let currentUser = null;

// Check authentication on load
// const checkAuth = async () => {
//     try {
//         const response = await fetch(`${controllerPath}?action=list&currentPage=1`);
//         if (response.status === 401) {
//             // Redirect to login or show auth modal
//             console.log('Authentication required');
//             return false;
//         }
//         const data = await response.json();
//         if (data.success && data.data.user_info) {
//             currentUser = data.data.user_info;
//             return true;
//         }
//     } catch (error) {
//         console.error('Auth check failed:', error);
//     }
//     return false;
// }

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

const stats = (data) => {
    console.log("logger: ", data)
    if(data) {
        putTextinElementById("##################total_email", data.total_emails || "0");
        putTextinElementById("##total_unread", data.total_opened || "0");
        putTextinElementById("#total_reed", data.avg_open_rate ? `${data.avg_open_rate}%` : "0%");
    }
}

const deleteEmail = async (id)=>{
    const queryString = new URLSearchParams({
        action: 'delete',
        id: id,
    });
    try {
        const response = await fetch(`${controllerPath}?${queryString}`);
        if (!response.ok) throw new Error('Network response was not ok');
        const data = await response.json();
        if (data.success) {
            console.log("Email deleted successfully");
            fetchEmails(state.currentTab);
        } else {
            console.error("Failed to delete email:", data.message);
        }
    } catch (error) {
        console.error("Error deleting email:", error);
    }
}

const saveEmail = async (id)=>{
    const queryString = new URLSearchParams({
        action: 'save',
        id: id,
    });
    try {
        const response = await fetch(`${controllerPath}?${queryString}`);
        if (!response.ok) throw new Error('Network response was not ok');
        const data = await response.json();
        if (data.success) {
            console.log("Email saved successfully");
            fetchEmails(state.currentTab);
        } else {
            console.error("Failed to save email:", data.message);
        }
    } catch (error) {
        console.error("Error saving email:", error);
    }
}

const fetchEmails = async (tab = state.currentTab) => {
    const queryString = new URLSearchParams({
        action: 'list',
        currentPage: state.currentPage,
        currentTab: tab,
        order: state.order,
        limit: state.limit
    });

    try {
        const response = await fetch(`${controllerPath}?${queryString}`);
        if (!response.ok) throw new Error('Network response was not ok');
        const data = await response.json();
        
        if (data.success) {
            state.emails = data.data.uploads;
            displayList();
            stats(data.data.summary);
            console.log(data)
            // Update pagination if available
            if (data.data.pagination) {
                renderPagination(fetchEmails, data.data.summary.total_emails, state, () => fetchEmails(state.currentTab));
            }
        } else {
            console.error("Error fetching emails:", data.error);
        }
    } catch (error) {
        console.error("Error in fetching emails:", error);
    }
}

const displayList = () => {
    let emails = state.emails;
    if(!emails){
        return;
    }
    let html = '';
    
    if(emails && emails.length > 0) {
        emails.forEach(email => {
            console.log("Emails: ", email)
            const senderName = email.sender_name || 'Unknown User';
            const date = email.date || 'Feb 18, 2026';
            const time = email.time || '10:30 AM';
            const subject = email.subject || 'Email Subject';
            const preview = email.preview || 'Email preview text...';
            const content = email.full_content || 'No content available';
            const status = email.status;
            const senderEmail = email.sender_email || 'unknown';
            const emailID = email.id;
                
                // Status indicators
            let statusBadge = '';
            const saved = email.save ? "<i class='far fa-star' style='color: yellow;'></i>" : "<i class='fas fa-star'></i>";
            switch (status) {
                case 'read':
                const readersName = email.read_by_name
                    statusBadge = `<span class="status-badge read" >read</span> - <span>${readersName}</span>`;
                break;
            case 'unread':
                statusBadge = '<span class="status-badge unread">unread</span>';
                break;
            default:
                statusBadge = '<span class="status-badge">Unknown</span>';
            }
            html += `<div class="email-item unread">
                <div class="email-sender">
                  <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=${senderName}" alt="${senderName} - ${senderEmail}" title="${senderName} - ${senderEmail}">
                </div>
                <div class="email-content">
                  <div class="email-header">
                    <h4>${senderName} - ${senderEmail} 
                    <span class="save-btn" data-id="${emailID}">${saved}</span>${statusBadge}</h4> 
                    <span>${date} - ${time}</span>
                  </div>
                  <p class="email-subject">${subject}</p>
                  <p class="email-preview">${preview}</p>
                  <div class="email-actions">
                    <button class="btn btn-primary btn-small viewModal" data-id="${emailID}" ><i class="fas fa-eye"></i> View</button>
                    <!--<button class="btn btn-small" style="background-color: #f1f5f9; color: var(--text-dark);" onclick="duplicateEmail('${subject}')"><i class="fas fa-copy"></i> Duplicate</button>-->
                    <button class="btn btn-small clickDelete" style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444;" data-id="${emailID}"><i class="fas fa-trash"></i> Delete</button>
                  </div>
                </div>
              </div>`;
        });
    } else {
        html = '<div class="no-emails"><p>No emails found</p></div>';
    }
    
    document.querySelector('.email-system').innerHTML = html;
    console.log("done")
    


    document.querySelectorAll('.viewModal').forEach(el => {
        buttonEventListener(el, (e) => {
            e.preventDefault(); // Now 'e' is the event object!
            
            const emailID = e.currentTarget.getAttribute('data-id');
            
            // Using == is fine if data-id is a string and id is a number
            const email = state.emails.find(email => email.id == emailID);
            
            if (email) {
                toggleModal("#viewEmailModal", "flex");
                viewEmail(email);
                if (email.status === 'unread') {
                    readUpdate(email.id);
                }
            } else {
                console.error("No email found with ID:", emailID);
            }
        });
    });

    document.querySelectorAll('.clickDelete').forEach(el => {
        buttonEventListener(el, (e) => {
            e.preventDefault(); // Now 'e' is the event object!
            
            const emailID = e.currentTarget.getAttribute('data-id');
            
            // Using == is fine if data-id is a string and id is a number
            const email = state.emails.find(email => email.id == emailID);
            
            if (email) {
                console.log("Deleting email:", email);
                deleteEmail(email.id);
            } else {
                console.error("No email found with ID:", emailID);
            }
        });
    });
    document.querySelectorAll('.save-btn').forEach(el => {
        buttonEventListener(el, (e) => {
            e.preventDefault(); // Now 'e' is the event object!
            
            const emailID = e.currentTarget.getAttribute('data-id');
            
            // Using == is fine if data-id is a string and id is a number
            const email = state.emails.find(email => email.id == emailID);
            
            if (email) {
                console.log("Saving email:", email);
                saveEmail(email.id);
            } else {
                console.error("No email found with ID:", emailID);
            }
        });
    });

}

const readUpdate = (id) => {
    fetch(`${controllerPath}?action=updateread&id=${id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        fetchEmails()
        //console.log('Email marked as read:', data);
    })
    .catch(error => {
        console.error('Error marking email as read:', error);
    });
}



// Initialize page when DOM is loaded
document.addEventListener('DOMContentLoaded', async () => {
    // Check authentication first
    // const isAuthenticated = await checkAuth();
    // if (!isAuthenticated) {
    //     // Show authentication required message
    //     const emailList = document.querySelector('.email-system');
    //     if (emailList) {
    //         emailList.innerHTML = '<div class="auth-required"><p>Authentication required to access email management</p></div>';
    //     }
    //     return;
    // }
    
    // Load emails
    fetchEmails();
    
    
    // Add tab switching functionality
    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(tab => {
        buttonEventListener(tab, () => {
            // Remove active class from all tabs
            tabs.forEach(t => t.classList.remove('active'));
            // Add active class to clicked tab
            tab.classList.add('active');
            // Update current tab and fetch emails
            state.currentTab = tab.textContent.toLowerCase().replace(' ', '');
            console.log("clicked", state.currentTab);
            fetchEmails(state.currentTab);
        });
    });
    
    // Display user info if available
    if (currentUser) {
        console.log('Current user:', currentUser);
        // You can display user info in the UI if needed
    }
});

// Email action functions
const viewEmail = (email) => {
    putTextinElementById("#viewEmailSubject", email.subject);
    putTextinElementById("#email-content", email.full_content, "innerHTML");
}
