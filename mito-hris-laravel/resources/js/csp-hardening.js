(function () {
    function applySavedTheme() {
        const theme = localStorage.getItem("mito_theme") || "light";
        document.documentElement.setAttribute("data-theme", theme);
        document.documentElement.setAttribute("data-bs-theme", theme);
    }

    function enableConsentToggle() {
        const checkbox = document.getElementById("consentCheckbox");
        const button = document.getElementById("btnProceedApply");
        if (!checkbox || !button) {
            return;
        }

        const syncButton = function () {
            button.disabled = !checkbox.checked;
        };

        ["change", "input", "click"].forEach(function (eventName) {
            checkbox.addEventListener(eventName, syncButton);
        });
        syncButton();
    }

    function enablePagination() {
        document.addEventListener("click", function (event) {
            const button = event.target.closest("[data-page-url]");
            if (!button) {
                return;
            }

            const url = button.getAttribute("data-page-url");
            if (url) {
                window.location.href = url;
            }
        });
    }

    function sanitizeFullNameField(field) {
        if (!(field instanceof HTMLInputElement)) {
            return;
        }

        field.value = field.value
            .replace(/[^\p{L} ]/gu, "")
            .replace(/ {2,}/g, " ");
    }

    function bindPublicCareerFormHelpers() {
        const fullNameField = document.getElementById("full_name");
        if (!fullNameField) {
            return;
        }

        fullNameField.addEventListener("input", function () {
            sanitizeFullNameField(fullNameField);
        });

        fullNameField.addEventListener("blur", function () {
            fullNameField.value = fullNameField.value.trim();
        });
    }

    function bindMasterDataHelpers() {
        const searchInput = document.getElementById("mdSearch");
        const tableBody = document.getElementById("mdTableBody");
        const countInfo = document.getElementById("mdCountInfo");
        if (!searchInput || !tableBody || !countInfo) {
            return;
        }

        const filterMasterTable = function () {
            const query = searchInput.value.trim().toLowerCase();
            const rows = tableBody.querySelectorAll("tr");
            let visible = 0;

            rows.forEach(function (row) {
                const name = (
                    row.querySelector(".item-name")?.textContent || ""
                ).toLowerCase();
                const description = (
                    row.querySelector(".item-desc")?.textContent || ""
                ).toLowerCase();
                const matches =
                    name.includes(query) || description.includes(query);
                row.style.display = matches ? "" : "none";
                if (matches) {
                    visible++;
                }
            });

            countInfo.textContent = `${visible} item`;
        };

        searchInput.addEventListener("keyup", filterMasterTable);
        filterMasterTable();
    }

    function bindMasterDataWarnings() {
        document.addEventListener("click", function (event) {
            const trigger = event.target.closest(
                "[data-master-delete-warning]",
            );
            if (!trigger) {
                return;
            }

            if (typeof window.showToast === "function") {
                window.showToast(
                    "Item referensi master terproteksi dari spreadsheet.",
                    "warning",
                );
            }
        });
    }

    function bindOutsourceFieldSanitization() {
        document
            .querySelectorAll("[data-sanitize-name]")
            .forEach(function (field) {
                const sanitize = function () {
                    field.value = field.value
                        .replace(/[^\p{L} ]/gu, "")
                        .replace(/ {2,}/g, " ");
                };

                field.addEventListener("input", sanitize);
                field.addEventListener("blur", function () {
                    field.value = field.value.trim();
                });
            });

        document
            .querySelectorAll("[data-sanitize-digits]")
            .forEach(function (field) {
                const max = parseInt(field.getAttribute("maxlength") || "32", 10);
                const sanitize = function () {
                    field.value = field.value.replace(/\D+/g, "").substring(0, max);
                };

                field.addEventListener("input", sanitize);
                field.addEventListener("blur", sanitize);
            });

        document
            .querySelectorAll("[data-sanitize-npwp]")
            .forEach(function (field) {
                const sanitize = function () {
                    field.value = field.value.replace(/\D+/g, "").substring(0, 16);
                };

                field.addEventListener("input", sanitize);
                field.addEventListener("blur", sanitize);
                sanitize();
            });

        document
            .querySelectorAll("[data-sanitize-moderate]")
            .forEach(function (field) {
                const sanitize = function () {
                    field.value = field.value
                        .replace(/[^\p{L}0-9 .,&\-\/]/gu, "")
                        .replace(/ {2,}/g, " ");
                };

                field.addEventListener("input", sanitize);
                field.addEventListener("blur", function () {
                    field.value = field.value.trim();
                });
            });

        document
            .querySelectorAll("[data-sanitize-position]")
            .forEach(function (field) {
                const sanitize = function () {
                    field.value = field.value
                        .replace(/[^\p{L} \-]/gu, "")
                        .replace(/ {2,}/g, " ");
                };

                field.addEventListener("input", sanitize);
                field.addEventListener("blur", function () {
                    field.value = field.value.trim();
                });
            });

        document
            .querySelectorAll("[data-sanitize-languages]")
            .forEach(function (field) {
                const sanitize = function () {
                    field.value = field.value
                        .replace(/[^\p{L} ,.\(\)\r\n]/gu, "")
                        .replace(/ {2,}/g, " ");
                };

                field.addEventListener("input", sanitize);
                field.addEventListener("blur", function () {
                    field.value = field.value.trim();
                });
            });

        document
            .querySelectorAll("[data-sanitize-org-title]")
            .forEach(function (field) {
                const sanitize = function () {
                    field.value = field.value
                        .replace(/[^\p{L} \-\/]/gu, "")
                        .replace(/ {2,}/g, " ");
                };

                field.addEventListener("input", sanitize);
                field.addEventListener("blur", function () {
                    field.value = field.value.trim();
                });
            });
    }

    function bindOutsourceAddressCopy() {
        const checkbox = document.getElementById("same_address");
        const address = document.getElementById("address");
        const residential = document.getElementById("address_residential");
        if (!checkbox || !address || !residential) {
            return;
        }

        const syncAddress = function () {
            residential.value = checkbox.checked ? address.value : "";
            if (typeof window.updateProgress === "function") {
                window.updateProgress();
            }
        };

        checkbox.addEventListener("change", syncAddress);
    }

    function bindHrAutoSubmitFilters() {
        document
            .querySelectorAll("[data-auto-submit='true']")
            .forEach(function (field) {
                field.addEventListener("change", function () {
                    const form = field.closest("form");
                    if (form) {
                        form.submit();
                    }
                });
            });

        document
            .querySelectorAll("[data-submit-on-enter='true']")
            .forEach(function (field) {
                field.addEventListener("keydown", function (event) {
                    if (event.key === "Enter") {
                        const form = field.closest("form");
                        if (form) {
                            form.submit();
                        }
                    }
                });
            });
    }

    function bindHrActionButtons() {
        document.addEventListener("click", function (event) {
            const button = event.target.closest("[data-action]");
            if (!button) {
                return;
            }

            const action = button.dataset.action;
            if (action === "open-move-status-modal") {
                if (typeof window.openMoveStatusModal === "function") {
                    window.openMoveStatusModal(
                        button.dataset.recruitmentId,
                        button.dataset.fromStatus || "Accepted",
                    );
                }
                return;
            }

            if (action === "open-offering-preview-modal") {
                if (typeof window.openOfferingPreviewModal === "function") {
                    window.openOfferingPreviewModal(
                        button.dataset.recruitmentId,
                    );
                }
            }
        });
    }

    function bindHrPerPageRedirects() {
        document
            .querySelectorAll("[data-per-page-url]")
            .forEach(function (select) {
                select.addEventListener("change", function () {
                    const baseUrl = select.dataset.perPageUrl;
                    if (!baseUrl) {
                        return;
                    }

                    const value = encodeURIComponent(select.value);
                    window.location.href = `${baseUrl}?per_page=${value}`;
                });
            });
    }

    function bindHrStopRowPropagation() {
        document.addEventListener("click", function (event) {
            const target = event.target.closest("[data-stop-row-propagation]");
            if (!target) {
                return;
            }

            event.stopPropagation();
        });
    }

    function bindProbationResetModal() {
        const button = document.getElementById("btnOpenEvalModal");
        if (!button) {
            return;
        }

        button.addEventListener("click", function () {
            if (typeof window.resetProbationEvalModal === "function") {
                window.resetProbationEvalModal();
            }
        });
    }

    function bindProbationIndexHandlers() {
        if (window.__mitoProbationIndexHandlersBound) {
            return;
        }

        window.__mitoProbationIndexHandlersBound = true;

        document.addEventListener("click", function (event) {
            const button = event.target.closest(".prob-btn-eval");
            if (!button) {
                return;
            }

            const employeeId = button.dataset.employeeId;
            if (!employeeId) {
                return;
            }

            if (typeof window.prefillEvalEmployee === "function") {
                window.prefillEvalEmployee(employeeId);
            }
        });

        document.addEventListener("click", function (event) {
            const button = event.target.closest(".prob-btn-history");
            if (!button) {
                return;
            }

            const employeeId = button.dataset.employeeId;
            if (!employeeId) {
                return;
            }

            const emp = (window.__allProbationEmployees || []).find(
                function (entry) {
                    return (
                        (entry.employeeId || "").replace(/^'+/, "") ===
                        String(employeeId).replace(/^'+/, "")
                    );
                },
            );

            if (typeof window.openEvalHistoryModal === "function") {
                window.openEvalHistoryModal(
                    employeeId,
                    emp ? emp.fullName || "-" : "-",
                );
            }
        });
    }

    function bindStatusPageModalHandlers() {
        if (window.__mitoStatusPageModalHandlersBound) {
            return;
        }

        window.__mitoStatusPageModalHandlersBound = true;

        const onboardingSearch = document.getElementById(
            "onboardingCandSearch",
        );
        if (onboardingSearch) {
            onboardingSearch.addEventListener("input", function (event) {
                handleOnboardingSearch(event.target.value);
            });
        }

        const onboardingClear = document.getElementById(
            "onboardingSearchClear",
        );
        if (onboardingClear) {
            onboardingClear.addEventListener("click", function () {
                clearOnboardingSearch();
            });
        }

        const offeringSearch = document.getElementById("offeringCandSearch");
        if (offeringSearch) {
            offeringSearch.addEventListener("input", function (event) {
                handleOfferingSearch(event.target.value);
            });
        }

        const offeringClear = document.getElementById("offeringSearchClear");
        if (offeringClear) {
            offeringClear.addEventListener("click", function () {
                clearOfferingSearch();
            });
        }

        const workingHoursPreset = document.getElementById(
            "offerWorkingHoursPreset",
        );
        if (workingHoursPreset) {
            workingHoursPreset.addEventListener("change", function () {
                applyWorkingHoursPreset(this);
            });
        }
    }

    function bindEntityModalSearchHandlers() {
        if (window.__mitoEntityModalSearchHandlersBound) {
            return;
        }

        window.__mitoEntityModalSearchHandlersBound = true;

        const offSearch = document.getElementById("offEmpSearch");
        if (offSearch) {
            offSearch.addEventListener("input", function (event) {
                handleOffEmpSearch(event.target.value);
            });
        }

        const offClear = document.getElementById("offEmpSearchClear");
        if (offClear) {
            offClear.addEventListener("click", function () {
                clearOffEmpSearch();
            });
        }

        const pdfSearch = document.getElementById("pdfEmpSearch");
        if (pdfSearch) {
            pdfSearch.addEventListener("input", function (event) {
                handlePdfEmpSearch(event.target.value);
            });
        }

        const pdfClear = document.getElementById("pdfEmpSearchClear");
        if (pdfClear) {
            pdfClear.addEventListener("click", function () {
                clearPdfEmpSearch();
            });
        }

        const exportPdfBtn = document.getElementById("btnExportPdf");
        if (exportPdfBtn) {
            exportPdfBtn.addEventListener("click", function () {
                generatePdfFromModal();
            });
        }
    }

    function bindOffContractAndRotationSearchHandlers() {
        if (window.__mitoOffContractRotationHandlersBound) {
            return;
        }

        window.__mitoOffContractRotationHandlersBound = true;

        const ocSearch = document.getElementById("ocEmpSearch");
        if (ocSearch) {
            ocSearch.addEventListener("input", function (event) {
                handleOcEmpSearch(event.target.value);
            });
        }

        const ocClear = document.getElementById("ocEmpSearchClear");
        if (ocClear) {
            ocClear.addEventListener("click", function () {
                clearOcEmpSearch();
            });
        }

        const rotSearch = document.getElementById("rotEmpSearch");
        if (rotSearch) {
            rotSearch.addEventListener("input", function (event) {
                handleRotEmpSearch(event.target.value);
            });
        }

        const rotClear = document.getElementById("rotEmpSearchClear");
        if (rotClear) {
            rotClear.addEventListener("click", function () {
                clearRotEmpSearch();
            });
        }
    }

    function displayFlashToasts() {
        const flashToasts = window.__flashToasts || [];
        flashToasts.forEach(function (toast) {
            if (typeof window.showToast === "function") {
                window.showToast(toast);
            }
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", function () {
            applySavedTheme();
            enableConsentToggle();
            enablePagination();
            displayFlashToasts();
            bindPublicCareerFormHelpers();
            bindMasterDataHelpers();
            bindMasterDataWarnings();
            bindOutsourceFieldSanitization();
            bindOutsourceAddressCopy();
            bindHrAutoSubmitFilters();
            bindHrActionButtons();
            bindHrPerPageRedirects();
            bindHrStopRowPropagation();
            bindProbationResetModal();
            bindProbationIndexHandlers();
            bindEntityModalSearchHandlers();
            bindStatusPageModalHandlers();
            bindOffContractAndRotationSearchHandlers();
        });
        return;
    }

    applySavedTheme();
    enableConsentToggle();
    enablePagination();
    displayFlashToasts();
    bindPublicCareerFormHelpers();
    bindMasterDataHelpers();
    bindMasterDataWarnings();
    bindOutsourceFieldSanitization();
    bindOutsourceAddressCopy();
    bindHrAutoSubmitFilters();
    bindHrActionButtons();
    bindHrPerPageRedirects();
    bindHrStopRowPropagation();
    bindProbationResetModal();
    bindProbationIndexHandlers();
    bindEntityModalSearchHandlers();
    bindStatusPageModalHandlers();
    bindOffContractAndRotationSearchHandlers();
})();
