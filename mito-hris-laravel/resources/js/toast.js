(function() {
    'use strict';

    const TOAST_CONFIG = {
        defaultDuration: 4500,
        durations: {
            success: 4500,
            error: 9000,
            warning: 6500,
            info: 4500
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
            container.className = 'toast-container mito-toast-container position-fixed top-0 end-0 p-3';
            document.body.appendChild(container);
        }
        return container;
    }

    function showToast(messageOrOptions, type = 'info', duration = null) {
        const options = typeof messageOrOptions === 'object' ? messageOrOptions : {
            message: messageOrOptions,
            type: type,
            duration: duration
        };
        const message = options.message || '';
        type = normalizeType(options.type);
        duration = options.duration || null;
        if (!message) return;
        const container = createContainer();
        const toastEl = document.createElement('div');
        toastEl.className = `toast mito-toast mito-toast-${type}`;
        toastEl.role = type === 'error' ? 'alert' : 'status';
        toastEl.ariaLive = type === 'error' ? 'assertive' : 'polite';
        toastEl.ariaAtomic = 'true';

        const iconMap = {
            success: 'bi-check-circle-fill',
            error: 'bi-x-circle-fill',
            warning: 'bi-exclamation-triangle-fill',
            info: 'bi-info-circle-fill'
        };
        const icon = iconMap[type] || iconMap.info;
        const title = options.title || ({
            success: 'Berhasil',
            error: 'Terjadi kesalahan',
            warning: 'Perhatian',
            info: 'Informasi'
        }[type]);

        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="mito-toast-icon" aria-hidden="true"><i class="bi ${icon}"></i></div>
                <div class="toast-body">
                    <strong class="mito-toast-title">${escapeHtml(title)}</strong>
                    <span class="mito-toast-message">${escapeHtml(message)}</span>
                </div>
                <button type="button" class="btn-close me-3 mt-3" data-bs-dismiss="toast" aria-label="Tutup notifikasi"></button>
            </div>
            <div class="mito-toast-progress" aria-hidden="true"></div>
        `;

        container.appendChild(toastEl);
        const bsToast = new bootstrap.Toast(toastEl, {
            autohide: true,
            delay: duration || getDuration(type)
        });
        toastEl.style.setProperty('--mito-toast-duration', `${duration || getDuration(type)}ms`);
        bsToast.show();

        toastEl.addEventListener('hidden.bs.toast', function() {
            if (toastEl.parentNode) toastEl.parentNode.removeChild(toastEl);
        });
    }

    function normalizeType(type) {
        return type === 'danger' ? 'error' : (['success', 'error', 'warning', 'info'].includes(type) ? type : 'info');
    }

    function setupRefreshToast() {
        const refreshFlag = 'mito_refresh_requested';
        const refreshSelector = '#fabRefresh, #btnRefresh, #btnAccRefresh, #btnHoldRefresh, #btnBlRefresh, #btnProbRefresh, #btnAuditRefresh, .btn-refresh';

        document.addEventListener('click', function(event) {
            if (event.target.closest(refreshSelector)) {
                sessionStorage.setItem(refreshFlag, '1');
            }
        }, true);

        if (sessionStorage.getItem(refreshFlag) === '1') {
            sessionStorage.removeItem(refreshFlag);
            window.setTimeout(function() {
                showToast({
                    type: 'success',
                    title: 'Data diperbarui',
                    message: 'Data berhasil diambil dari Sheet.'
                });
            }, 150);
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    window.showToast = showToast;
    document.addEventListener('DOMContentLoaded', setupRefreshToast);
})();