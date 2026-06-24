// NeoAlert Custom Component
// Designed specifically for Neo Brutalism aesthetic

function NeoToast(message, type = 'success') {
    // Container for toasts
    let container = document.getElementById('neo-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'neo-toast-container';
        document.body.appendChild(container);
    }

    // Create toast element
    const toast = document.createElement('div');
    toast.className = `neo-toast neo-toast-${type}`;
    
    // Icon based on type
    let icon = '';
    if (type === 'success') icon = "<i class='bx bxs-check-circle'></i>";
    else if (type === 'error') icon = "<i class='bx bxs-error-circle'></i>";
    else if (type === 'warning') icon = "<i class='bx bxs-error'></i>";

    toast.innerHTML = `
        <div class="neo-toast-icon">${icon}</div>
        <div class="neo-toast-content">${message}</div>
    `;

    // Append to container
    container.appendChild(toast);

    // Trigger reflow to play entrance animation
    void toast.offsetWidth;
    toast.classList.add('show');

    // Auto remove after 3.5 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        toast.classList.add('hide');
        
        // Wait for exit animation to finish before removing from DOM
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 400); // Matches CSS transition duration
    }, 3500);
}

function NeoConfirm(title, text, confirmCallback) {
    // Create overlay
    const overlay = document.createElement('div');
    overlay.className = 'neo-modal-overlay';
    
    // Create modal box
    const modal = document.createElement('div');
    modal.className = 'neo-modal-box neo-box';
    
    modal.innerHTML = `
        <div class="neo-modal-header">
            <i class='bx bx-error-circle neo-modal-icon'></i>
            <h3 class="neo-modal-title">${title}</h3>
        </div>
        <div class="neo-modal-body">
            <p>${text}</p>
        </div>
        <div class="neo-modal-footer">
            <button class="neo-btn neo-btn-cancel" id="neo-modal-cancel">Batal</button>
            <button class="neo-btn neo-btn-confirm" id="neo-modal-confirm">Ya</button>
        </div>
    `;
    
    overlay.appendChild(modal);
    document.body.appendChild(overlay);
    
    // Trigger entrance animation
    setTimeout(() => {
        overlay.classList.add('show');
        modal.classList.add('show');
    }, 10);
    
    // Setup event listeners
    const cancelBtn = modal.querySelector('#neo-modal-cancel');
    const confirmBtn = modal.querySelector('#neo-modal-confirm');
    
    const closeModal = () => {
        overlay.classList.remove('show');
        modal.classList.remove('show');
        setTimeout(() => {
            if (overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
        }, 300);
    };
    
    cancelBtn.addEventListener('click', closeModal);
    
    confirmBtn.addEventListener('click', () => {
        closeModal();
        if (typeof confirmCallback === 'function') {
            confirmCallback();
        } else if (typeof confirmCallback === 'string') {
            // Assume it's a URL to redirect to
            window.location.href = confirmCallback;
        }
    });
}
