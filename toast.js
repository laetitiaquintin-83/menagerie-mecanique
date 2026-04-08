/**
 * ======================================================================
 * toast.js - SYSTÈME DE NOTIFICATIONS TOAST
 * ======================================================================
 * Affiche des notifications temporaires (Toast) dans le coin de l'écran
 * 
 * Utilisation:
 * - showToast('Message', 'success')  // vert
 * - showToast('Erreur', 'error')     // rouge  
 * - showToast('Info', 'info')        // bleu
 */

// Crée le conteneur de toasts s'il n'existe pas
function initToastContainer() {
    if (!document.getElementById('toast-container')) {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        `;
        document.body.appendChild(container);
    }
}

/**
 * Affiche une notification toast
 * @param {string} message - Le message à afficher
 * @param {string} type - 'success', 'error', 'info', 'warning'
 * @param {number} duration - Durée d'affichage en ms (0 = pas d'auto-fermeture)
 */
function showToast(message, type = 'info', duration = 3000) {
    initToastContainer();
    
    const colors = {
        success: { bg: '#4caf50', icon: '✅' },
        error:   { bg: '#ff6b6b', icon: '❌' },
        warning: { bg: '#ff9800', icon: '⚠️' },
        info:    { bg: '#2196f3', icon: 'ℹ️' }
    };
    
    const config = colors[type] || colors.info;
    
    const toast = document.createElement('div');
    toast.style.cssText = `
        background: ${config.bg};
        color: white;
        padding: 15px 20px;
        border-radius: 5px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideIn 0.3s ease-out;
        max-width: 300px;
        font-weight: bold;
        cursor: pointer;
    `;
    
    toast.innerHTML = `
        <span>${config.icon}</span>
        <span>${message}</span>
        <span style="margin-left: auto; cursor: pointer; opacity: 0.8; hover: opacity 1;">×</span>
    `;
    
    const container = document.getElementById('toast-container');
    container.appendChild(toast);
    
    // Bouton fermer
    toast.querySelector('span:last-child').onclick = () => removeToast(toast);
    
    // Auto-fermeture
    if (duration > 0) {
        setTimeout(() => removeToast(toast), duration);
    }
}

/**
 * Supprime un toast avec animation
 */
function removeToast(element) {
    element.style.animation = 'slideOut 0.3s ease-out forwards';
    setTimeout(() => {
        element.remove();
        // Supprimer le conteneur si vide
        const container = document.getElementById('toast-container');
        if (container && container.children.length === 0) {
            container.remove();
        }
    }, 300);
}

// Styles CSS pour les animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Export pour utilisation en modules (optionnel)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { showToast, removeToast };
}
