css = """
/* ========================================
   NEO ALERT COMPONENT (Custom)
   ======================================== */
#neo-toast-container {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 15px;
    pointer-events: none;
}

.neo-toast {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 1.2rem 1.8rem;
    border: 3px solid #000;
    box-shadow: 6px 6px 0px #000;
    font-weight: 800;
    font-size: 1.1rem;
    text-transform: uppercase;
    pointer-events: auto;
    
    transform: translateY(150%);
    opacity: 0;
    transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.4s ease;
}

.neo-toast.show {
    transform: translateY(0);
    opacity: 1;
}

.neo-toast.hide {
    transform: translateY(150%);
    opacity: 0;
}

.neo-toast-success {
    background-color: #a8e6cf;
    color: #000;
}
.neo-toast-error {
    background-color: #ff6b6b;
    color: #000;
}
.neo-toast-warning {
    background-color: #ffd900;
    color: #000;
}

.neo-toast-icon {
    font-size: 1.8rem;
    display: flex;
    align-items: center;
}

/* Modal Overlay */
.neo-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(3px);
    z-index: 10000;
    display: flex;
    justify-content: center;
    align-items: center;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease, visibility 0.3s ease;
}

.neo-modal-overlay.show {
    opacity: 1;
    visibility: visible;
}

/* Modal Box */
.neo-modal-box {
    background-color: var(--card);
    width: 90%;
    max-width: 450px;
    padding: 2rem;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    
    transform: scale(0.8) translateY(20px);
    opacity: 0;
    transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.3s ease;
}

.neo-modal-box.show {
    transform: scale(1) translateY(0);
    opacity: 1;
}

.neo-modal-header {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 1rem;
}

.neo-modal-icon {
    font-size: 4rem;
    color: var(--primary);
}

.neo-modal-title {
    font-size: 1.8rem;
    font-weight: 900;
    text-transform: uppercase;
    color: var(--foreground);
    line-height: 1.2;
}

.neo-modal-body p {
    text-align: center;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--muted-foreground);
    margin: 0;
}

.neo-modal-footer {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
}

.neo-btn-cancel {
    background-color: var(--muted);
    color: var(--muted-foreground);
}

.neo-btn-confirm {
    background-color: var(--primary);
    color: var(--primary-foreground);
}

/* Dark mode overrides if necessary */
.dark-mode .neo-toast-success {
    background-color: #2e8b57;
    color: #fff;
}
.dark-mode .neo-toast-error {
    background-color: #cc0000;
    color: #fff;
}
"""
with open('style.css', 'a', encoding='utf-8') as f:
    f.write("\n" + css)
