(function() {
    'use strict';

    const TOAST_CONFIG = {
        defaultDuration: 5000,
        durations: {
            success: 5000,
            error: 7000,
            warning: 7000,
            info: 5000
        }
    };

    function getDuration(type) {
        return TOAST_CONFIG.durations[type] || TOAST_CONFIG.defaultDuration;
    }

    function createContainer() {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '2200';
            document.body.appendChild(container);
        }
        return container;
    }

    function showToast(message, type = 'info', duration = null) {
        if (!message) return;
        type = type || 'info';
        const container = createContainer();
        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-bg-${type === 'warning' ? 'warning' : type} border-0 show`;
        toastEl.role = 'alert';
        toastEl.ariaLive = type === 'error' ? 'assertive' : 'polite';
        toastEl.ariaAtomic = 'true';

        const iconMap = {
            success: 'bi-check-circle-fill',
            error: 'bi-exclamation-triangle-fill',
            warning: 'bi-exclamation-circle-fill',
            info: 'bi-info-circle-fill'
        };
        const icon = iconMap[type] || iconMap.info;

        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2">
                    <i class="bi ${icon} fs-5"></i>
                    <span>${escapeHtml(message)}</span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

        container.appendChild(toastEl);
        const bsToast = new bootstrap.Toast(toastEl, {
            autohide: true,
            delay: duration || getDuration(type)
        });
        bsToast.show();

        toastEl.addEventListener('hidden.bs.toast', function() {
            if (toastEl.parentNode) toastEl.parentNode.removeChild(toastEl);
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    window.showToast = showToast;
})();