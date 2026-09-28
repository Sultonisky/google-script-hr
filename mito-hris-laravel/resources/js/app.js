import "./bootstrap";
import * as bootstrap from "bootstrap";
import "./toast";
import "./csp-hardening";

window.bootstrap = bootstrap;

(function () {
    const REFRESH_SELECTOR = ["[data-refresh]", "[data-mito-refresh]"].join(
        ", ",
    );

    function setRefreshLoading(button, isLoading) {
        if (!(button instanceof HTMLElement)) {
            return;
        }

        const icon = button.querySelector("i, svg") || button;
        const iconLabel = button.querySelector(".refresh-label, .btn-text");

        button.disabled = isLoading;
        button.setAttribute("aria-busy", String(isLoading));
        button.classList.toggle("is-refreshing", isLoading);
        button.classList.toggle("is-loading", isLoading);
        icon.classList.toggle("refresh-icon", true);
        icon.classList.toggle("is-refreshing", isLoading);

        if (iconLabel) {
            iconLabel.setAttribute(
                "data-loading-label",
                iconLabel.textContent.trim(),
            );
        }

        if (isLoading) {
            button.dataset.mitoRefreshLocked = "1";
            button.setAttribute(
                "title",
                button.dataset.refreshLoadingTitle || "Memuat ulang data...",
            );
            button.setAttribute(
                "aria-label",
                button.dataset.refreshLoadingLabel || "Memuat ulang data",
            );
        } else {
            delete button.dataset.mitoRefreshLocked;
            button.setAttribute(
                "title",
                button.dataset.refreshIdleTitle ||
                    button.getAttribute("title") ||
                    "Muat ulang data",
            );
            button.setAttribute(
                "aria-label",
                button.dataset.refreshIdleLabel ||
                    button.getAttribute("aria-label") ||
                    "Muat ulang data",
            );
        }
    }

    async function handleRefreshButton(button) {
        if (
            !button ||
            button.dataset.mitoRefreshLocked === "1" ||
            button.disabled
        ) {
            return;
        }

        setRefreshLoading(button, true);
        sessionStorage.setItem("mito_refresh_requested", "1");

        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute("content") : "";

        try {
            const response = await fetch("/hr/refresh-data", {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                    "X-Requested-With": "XMLHttpRequest",
                },
                credentials: "same-origin",
                body: JSON.stringify({}),
            });

            const payload = await response.json().catch(function () {
                return {};
            });

            if (!response.ok || !payload.success) {
                throw new Error(
                    payload.message || "Gagal memperbarui data.",
                );
            }

            if (window.showToast) {
                window.showToast(
                    payload.message || "Data berhasil di-refresh.",
                    "success",
                );
            }

            window.setTimeout(function () {
                window.location.reload();
            }, 250);
        } catch (error) {
            console.error("Refresh failed:", error);

            if (window.showToast) {
                window.showToast(
                    error.message || "Gagal memperbarui data.",
                    "error",
                );
            }

            setRefreshLoading(button, false);
        }
    }

    document.addEventListener(
        "click",
        function (event) {
            const button = event.target.closest(REFRESH_SELECTOR);
            if (!button) {
                return;
            }

            const isAlreadyLoading =
                button.disabled ||
                button.classList.contains("is-refreshing") ||
                button.dataset.mitoRefreshLocked === "1";
            if (isAlreadyLoading) {
                event.preventDefault();
                event.stopPropagation();
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            handleRefreshButton(button);
        },
        true,
    );

    const SYNC_CONFIRM_IDLE_HTML =
        '<i class="bi bi-cloud-upload me-1"></i>Sinkron Sekarang';
    const SYNC_CONFIRM_BUSY_HTML =
        '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Menyinkronkan...';

    function setSyncModalBusy(modalEl, isBusy) {
        const confirmBtn = modalEl.querySelector("#btnSyncSheetsConfirm");
        if (confirmBtn) {
            confirmBtn.disabled = isBusy;
            confirmBtn.innerHTML = isBusy
                ? SYNC_CONFIRM_BUSY_HTML
                : SYNC_CONFIRM_IDLE_HTML;
        }

        modalEl
            .querySelectorAll('[data-bs-dismiss="modal"]')
            .forEach(function (el) {
                el.disabled = isBusy;
            });

        modalEl.dataset.syncBusy = isBusy ? "1" : "";
    }

    function setSyncModalError(modalEl, message) {
        const errorEl = modalEl.querySelector("#syncSheetsError");
        if (!errorEl) {
            return;
        }

        errorEl.textContent = message || "";
        errorEl.classList.toggle("d-none", !message);
    }

    function openSyncSheetsModal(fabButton) {
        const modalEl = document.getElementById("syncSheetsModal");
        if (!modalEl || fabButton.dataset.mitoRefreshLocked === "1") {
            return;
        }

        setSyncModalError(modalEl, "");
        setSyncModalBusy(modalEl, false);
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    async function runSyncSheets(modalEl) {
        if (modalEl.dataset.syncBusy === "1") {
            return;
        }

        const fabButton = document.querySelector("[data-sync-sheets]");
        setSyncModalError(modalEl, "");
        setSyncModalBusy(modalEl, true);
        setRefreshLoading(fabButton, true);

        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute("content") : "";

        try {
            const response = await fetch("/hr/sync-to-sheets", {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                    "X-Requested-With": "XMLHttpRequest",
                },
                credentials: "same-origin",
                body: JSON.stringify({}),
            });

            const payload = await response.json().catch(function () {
                return {};
            });

            if (!response.ok || !payload.success) {
                throw new Error(
                    payload.message ||
                        payload.error ||
                        "Gagal menyinkronkan data ke Spreadsheet.",
                );
            }

            setSyncModalBusy(modalEl, false);
            bootstrap.Modal.getOrCreateInstance(modalEl).hide();

            if (window.showToast) {
                window.showToast(
                    payload.message ||
                        "Spreadsheet berhasil disinkronkan dari database.",
                    "success",
                );
            }
        } catch (error) {
            console.error("Sync to Sheets failed:", error);
            setSyncModalBusy(modalEl, false);
            setSyncModalError(
                modalEl,
                error.message || "Gagal menyinkronkan data ke Spreadsheet.",
            );
        } finally {
            setRefreshLoading(fabButton, false);
        }
    }

    document.addEventListener("click", function (event) {
        const fabButton = event.target.closest("[data-sync-sheets]");
        if (fabButton) {
            event.preventDefault();
            openSyncSheetsModal(fabButton);
            return;
        }

        const confirmBtn = event.target.closest("#btnSyncSheetsConfirm");
        if (confirmBtn) {
            const modalEl = confirmBtn.closest("#syncSheetsModal");
            if (modalEl) {
                event.preventDefault();
                runSyncSheets(modalEl);
            }
        }
    });

    document.addEventListener("hide.bs.modal", function (event) {
        if (
            event.target instanceof HTMLElement &&
            event.target.id === "syncSheetsModal" &&
            event.target.dataset.syncBusy === "1"
        ) {
            event.preventDefault();
        }
    });

    window.mitoRefreshRequest = function (
        button,
        requestPromise,
        options = {},
    ) {
        const config = {
            successMessage: "Data berhasil diperbarui.",
            errorMessage: "Gagal memperbarui data.",
            warningMessage: "Data diperbarui dengan peringatan.",
            ...options,
        };

        if (!(button instanceof HTMLElement)) {
            return Promise.resolve(requestPromise);
        }

        setRefreshLoading(button, true);

        return Promise.resolve(requestPromise())
            .then(function (result) {
                if (result && typeof result === "object" && result.warning) {
                    if (window.showToast) {
                        window.showToast(config.warningMessage, "warning");
                    }
                    return result;
                }

                if (window.showToast) {
                    window.showToast(config.successMessage, "success");
                }

                return result;
            })
            .catch(function (error) {
                if (window.showToast) {
                    window.showToast(config.errorMessage, "error");
                }
                throw error;
            })
            .finally(function () {
                setRefreshLoading(button, false);
            });
    };

    window.mitoRefreshPage = function (button, options = {}) {
        const delay = options.delay ?? 150;
        if (button instanceof HTMLElement) {
            handleRefreshButton(button);
            return;
        }

        sessionStorage.setItem("mito_refresh_requested", "1");
        window.setTimeout(function () {
            window.location.reload();
        }, delay);
    };
})();
