(function () {
    "use strict";
    const CSRF =
        document.querySelector('meta[name="csrf-token"]')?.content || "";
    const HEADERS = {
        "X-CSRF-TOKEN": CSRF,
        Accept: "application/json",
    };

    const MITO_CERT_BASE = window.MITO_CERT_BASE || "/certifications";
    const certTypeInput = document.getElementById("certificationFormType");
    const productFields = document.getElementById("certificationProductFields");
    const productFieldsRow = document.getElementById(
        "certificationProductFieldsRow",
    );
    const companyFields = document.getElementById("certificationCompanyFields");
    const companyFieldsRow = document.getElementById(
        "certificationCompanyFieldsRow",
    );
    const productScopeInput = document.getElementById("certificationFormScope");
    const brandInput = document.getElementById("certificationFormBrand");
    const companyScopeInput = document.getElementById(
        "certificationFormCompanyScope",
    );
    const attachmentInput = document.getElementById(
        "certificationFormAttachment",
    );
    const currentAttachment = document.getElementById(
        "certificationCurrentAttachment",
    );
    const removeAttachmentWrap = document.getElementById(
        "certificationRemoveAttachmentWrap",
    );
    const removeAttachmentInput = document.getElementById(
        "certificationRemoveAttachment",
    );

    function updateClassificationFields() {
        const type = certTypeInput?.value || "";
        const productType = ["SNI", "Food Safety", "Product Safety"].includes(
            type,
        );
        const companyType = ["ISO", "K3"].includes(type);
        productFields?.classList.toggle("d-none", !productType);
        productFieldsRow?.classList.toggle("d-none", !productType);
        companyFields?.classList.toggle("d-none", !companyType);
        companyFieldsRow?.classList.toggle("d-none", !companyType);
        if (productScopeInput) productScopeInput.disabled = !productType;
        if (brandInput) brandInput.disabled = !productType;
        if (companyScopeInput) companyScopeInput.disabled = !companyType;
        if (productType && productScopeInput) {
            productScopeInput.placeholder =
                type === "SNI"
                    ? "Contoh: Rice Cooker, Air Fryer"
                    : "Contoh: Food Contact / Cookware atau Consumer Products";
        }
        if (companyType && companyScopeInput) {
            companyScopeInput.placeholder =
                type === "ISO"
                    ? "Contoh: Quality Management System"
                    : "Contoh: Keselamatan & Kesehatan Kerja";
        }
    }

    certTypeInput?.addEventListener("change", updateClassificationFields);
    updateClassificationFields();

    function showToast(msg, type) {
        if (window.showToast) showToast(msg, type);
    }

    function setLoading(btn, loading) {
        btn.disabled = loading;
        if (loading) btn.dataset.origText = btn.innerHTML;
        btn.innerHTML = loading
            ? '<i class="bi bi-arrow-repeat fa-spin"></i>'
            : btn.dataset.origText || btn.innerHTML;
    }

    // API serializes dates as UTC ISO. Convert to browser local yyyy-mm-dd so
    // <input type="date"> prefills and date-only fields are preserved on save.
    function toLocalDateValue(iso) {
        if (!iso) return "";
        const d = new Date(iso);
        if (isNaN(d.getTime())) return "";
        return (
            d.getFullYear() +
            "-" +
            String(d.getMonth() + 1).padStart(2, "0") +
            "-" +
            String(d.getDate()).padStart(2, "0")
        );
    }

    function formatDisplayDate(iso) {
        if (!iso) return "";
        const match = String(iso).match(/^(\d{4})-(\d{2})-(\d{2})/);
        const d = match
            ? new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]))
            : new Date(iso);
        if (isNaN(d.getTime())) return "";
        const months = [
            "Jan",
            "Feb",
            "Mar",
            "Apr",
            "Mei",
            "Jun",
            "Jul",
            "Agu",
            "Sep",
            "Okt",
            "Nov",
            "Des",
        ];
        return (
            String(d.getDate()).padStart(2, "0") +
            " " +
            months[d.getMonth()] +
            " " +
            d.getFullYear()
        );
    }

    function escHtml(v) {
        return String(v === null || v === undefined ? "" : v).replace(
            /[&<>"']/g,
            function (c) {
                return {
                    "&": "&amp;",
                    "<": "&lt;",
                    ">": "&gt;",
                    '"': "&quot;",
                    "'": "&#39;",
                }[c];
            },
        );
    }

    document.querySelectorAll('[data-auto-submit="true"]').forEach((sel) => {
        sel.addEventListener("change", () => sel.closest("form")?.submit());
    });

    // ---- Employee picker DOM refs ----
    const empSearch = document.getElementById("certificationEmployeeSearch");
    const empResults = document.getElementById("certificationEmployeeResults");
    const empError = document.getElementById("certificationEmployeeError");
    const selectedBox = document.getElementById(
        "certificationSelectedEmployee",
    );
    const empIdInput = document.getElementById("certificationFormEmployeeId");
    const empNameInput = document.getElementById(
        "certificationFormEmployeeName",
    );
    const empDivisionInput = document.getElementById(
        "certificationFormDivision",
    );
    const empDepartmentInput = document.getElementById(
        "certificationFormDepartment",
    );
    let searchTimeout = null;
    let activeOption = -1;

    function hideEmpResults() {
        if (empResults) empResults.classList.add("d-none");
    }

    function showEmpError(msg) {
        if (!empError) return;
        empError.textContent = msg || "";
        empError.classList.toggle("d-none", !msg);
    }

    function clearEmpError() {
        showEmpError("");
    }

    function clearEmpPicked() {
        if (empIdInput) empIdInput.value = "";
        if (empNameInput) empNameInput.value = "";
        if (empDivisionInput) empDivisionInput.value = "";
        if (empDepartmentInput) empDepartmentInput.value = "";
        if (selectedBox) {
            selectedBox.innerHTML = "";
            selectedBox.classList.add("d-none");
        }
    }

    function resetEmpPicker() {
        clearEmpPicked();
        clearEmpError();
        hideEmpResults();
        activeOption = -1;
    }

    function renderEmpSelected(name, id) {
        if (!selectedBox) return;
        selectedBox.innerHTML = "";
        const note = document.createElement("div");
        note.className = "text-muted small mb-1";
        note.textContent = "Karyawan terpilih:";
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "btn btn-sm btn-outline-success";
        btn.title = "Ganti / hapus pilihan karyawan";
        btn.innerHTML =
            '<i class="bi bi-person-check me-1"></i>' +
            escHtml(name) +
            " (" +
            escHtml(id) +
            ') <i class="bi bi-x-lg ms-2"></i>';
        btn.addEventListener("click", function () {
            if (empSearch) empSearch.value = "";
            clearEmpPicked();
            clearEmpError();
            if (empSearch) empSearch.focus();
        });
        selectedBox.appendChild(note);
        selectedBox.appendChild(btn);
        selectedBox.classList.remove("d-none");
    }

    function selectEmployee(opt) {
        empIdInput.value = opt.dataset.empId || "";
        empNameInput.value = opt.dataset.empName || "";
        empDivisionInput.value = opt.dataset.empDivision || "";
        empDepartmentInput.value = opt.dataset.empDepartment || "";
        empSearch.value = opt.dataset.empName || "";
        clearEmpError();
        hideEmpResults();
        renderEmpSelected(opt.dataset.empName || "", opt.dataset.empId || "");
        empSearch.blur();
    }

    document.addEventListener("click", function (e) {
        if (
            empResults &&
            !empResults.contains(e.target) &&
            e.target.id !== "certificationEmployeeSearch"
        ) {
            hideEmpResults();
        }
    });

    empSearch?.addEventListener("input", function () {
        clearTimeout(searchTimeout);
        clearEmpError();
        clearEmpPicked(); // typing a new query always starts a fresh selection
        const q = this.value.trim();
        hideEmpResults();
        activeOption = -1;
        if (q.length < 2) return;
        if (empResults) {
            empResults.innerHTML =
                '<div class="list-group-item"><span class="spinner-border spinner-border-sm me-2"></span>Mencari karyawan...</div>';
            empResults.classList.remove("d-none");
        }
        searchTimeout = setTimeout(async function () {
            try {
                const res = await fetch(
                    MITO_CERT_BASE +
                        "/employees/lookup?q=" +
                        encodeURIComponent(q) +
                        "&limit=8",
                    { headers: HEADERS },
                );
                if (res.status === 403) {
                    hideEmpResults();
                    showEmpError(
                        "Anda tidak memiliki izin untuk mencari karyawan.",
                    );
                    return;
                }
                const data = await res.json();
                if (!data.data || data.data.length === 0) {
                    if (empResults) {
                        empResults.innerHTML =
                            '<div class="list-group-item text-muted">Tidak ada karyawan yang cocok dengan "' +
                            escHtml(q) +
                            '".</div>';
                        empResults.classList.remove("d-none");
                    }
                    return;
                }
                renderEmpOptions(data.data);
            } catch (_) {
                hideEmpResults();
                showEmpError("Kesalahan jaringan saat mencari karyawan.");
            }
        }, 300);
    });

    empResults?.addEventListener("click", function (e) {
        const opt = e.target.closest(".employee-option");
        if (opt) selectEmployee(opt);
    });

    empResults?.addEventListener("mousemove", function (e) {
        const opt = e.target.closest(".employee-option");
        if (!opt) return;
        Array.prototype.forEach.call(
            empResults.querySelectorAll(".employee-option"),
            function (el) {
                const isActive = el === opt;
                el.classList.toggle("active", isActive);
                if (isActive) activeOption = Number(el.dataset.index);
            },
        );
    });

    empSearch?.addEventListener("keydown", function (e) {
        if (!empResults) return;
        const opts = Array.prototype.slice.call(
            empResults.querySelectorAll(".employee-option"),
        );
        if (!opts.length) return;
        if (e.key === "ArrowDown" || e.key === "ArrowUp") {
            e.preventDefault();
            const dir = e.key === "ArrowDown" ? 1 : -1;
            activeOption = (activeOption + dir + opts.length) % opts.length;
            opts.forEach(function (el, i) {
                el.classList.toggle("active", i === activeOption);
            });
            if (opts[activeOption])
                opts[activeOption].scrollIntoView({ block: "nearest" });
        } else if (e.key === "Enter") {
            const current = opts[activeOption] || opts[0];
            if (current) {
                e.preventDefault();
                selectEmployee(current);
            }
        } else if (e.key === "Escape") {
            hideEmpResults();
        }
    });

    // ---- Add / Edit modal open ----
    document
        .getElementById("addCertificationModal")
        ?.addEventListener("show.bs.modal", function () {
            const form = this.querySelector("#certificationForm");
            const isEdit = !!form.querySelector("#certificationFormId").value;
            if (!isEdit) {
                form.reset();
                form.querySelectorAll(".is-invalid").forEach((el) =>
                    el.classList.remove("is-invalid"),
                );
                form.querySelector("#certificationFormId").value = "";
                form.querySelector("#certificationFormMethod").value = "POST";
                form.querySelector("#certificationFormCode").removeAttribute(
                    "readonly",
                );
                if (empSearch) empSearch.value = "";
                resetEmpPicker();
                if (certTypeInput) certTypeInput.value = "";
                if (productScopeInput) productScopeInput.value = "";
                if (brandInput) brandInput.value = "";
                if (companyScopeInput) companyScopeInput.value = "";
                updateClassificationFields();
                if (attachmentInput) attachmentInput.value = "";
                if (currentAttachment) {
                    currentAttachment.innerHTML = "";
                    currentAttachment.classList.add("d-none");
                }
                removeAttachmentWrap?.classList.add("d-none");
                if (removeAttachmentInput)
                    removeAttachmentInput.checked = false;
            }
            document.getElementById("addCertificationModalLabel").textContent =
                isEdit ? "Edit Sertifikasi" : "Tambah Sertifikasi Baru";
        });

    // ---- Code generation preview ----
    document
        .getElementById("btnCertFormGenerateCode")
        ?.addEventListener("click", async function () {
            const input = document.getElementById("certificationFormCode");
            setLoading(this, true);
            try {
                const res = await fetch(MITO_CERT_BASE + "/preview-next-code", {
                    method: "GET",
                    headers: HEADERS,
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    input.value = data.code;
                } else
                    showToast(data.message || "Gagal generate kode.", "error");
            } catch (_) {
                showToast("Kesalahan jaringan.", "error");
            } finally {
                setLoading(this, false);
            }
        });

    // ---- Form Submit (Add / Edit) ----
    document
        .getElementById("certificationForm")
        ?.addEventListener("submit", async function (e) {
            e.preventDefault();
            const form = this;
            const id = form.querySelector("#certificationFormId").value;
            const isEdit = !!id;
            const btn = form.querySelector("#btnCertificationFormSubmit");
            const body = new FormData(form);
            body.delete("certification_id");
            if (!body.get("cert_code")) body.delete("cert_code");
            if (!body.get("status")) body.delete("status");
            const url = isEdit ? MITO_CERT_BASE + "/" + id : MITO_CERT_BASE;
            setLoading(btn, true);
            clearEmpError();
            try {
                const res = await fetch(url, {
                    method: "POST",
                    headers: HEADERS,
                    body,
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    showToast(data.message, "success");
                    bootstrap.Modal.getInstance(form.closest(".modal"))?.hide();
                    setTimeout(function () {
                        location.reload();
                    }, 600);
                } else {
                    showToast(data.message || "Gagal menyimpan.", "error");
                    form.querySelectorAll(".is-invalid").forEach(function (el) {
                        el.classList.remove("is-invalid");
                    });
                    empSearch?.classList.remove("is-invalid");
                    if (data.errors) {
                        Object.entries(data.errors).forEach(function (entry) {
                            const input = form.querySelector(
                                '[name="' + entry[0] + '"]',
                            );
                            if (input) {
                                input.classList.add("is-invalid");
                                // Map invisible hidden employee_id errors back to the visible picker.
                                if (entry[0] === "employee_id" && empSearch) {
                                    empSearch.classList.add("is-invalid");
                                    showEmpError(
                                        Array.isArray(entry[1])
                                            ? entry[1][0]
                                            : entry[1],
                                    );
                                }
                            }
                        });
                    }
                }
            } catch (_) {
                showToast("Kesalahan jaringan.", "error");
            } finally {
                setLoading(btn, false);
            }
        });

    // ---- Edit modal fill (from already-fetched JSON payload) ----
    function fillEditFormData(c) {
        if (!c) return;
        const form = document.getElementById("certificationForm");
        form.querySelector("#certificationFormId").value = c.id;
        form.querySelector("#certificationFormMethod").value = "PUT";
        form.querySelector("#certificationFormCode").value = c.cert_code || "";
        form.querySelector("#certificationFormType").value = c.cert_type || "";
        form.querySelector("#certificationFormName").value = c.name || "";
        form.querySelector("#certificationFormScope").value =
            c.product_scope || "";
        form.querySelector("#certificationFormBrand").value = c.brand || "";
        form.querySelector("#certificationFormCompanyScope").value =
            c.company_scope || "";
        updateClassificationFields();
        form.querySelector("#certificationFormDesc").value =
            c.description || "";
        form.querySelector("#certificationFormOrg").value =
            c.issuing_organization || "";
        form.querySelector("#certificationFormNumber").value =
            c.certificate_number || "";
        form.querySelector("#certificationFormIssueDate").value =
            toLocalDateValue(c.issue_date);
        form.querySelector("#certificationFormExpiryDate").value =
            toLocalDateValue(c.expiry_date);
        form.querySelector("#certificationFormStatus").value = c.status || "";
        form.querySelector("#certificationFormNotes").value = c.notes || "";
        if (attachmentInput) attachmentInput.value = "";
        if (currentAttachment) {
            if (c.attachment_path) {
                const fileName = c.attachment_path.split("/").pop();
                currentAttachment.innerHTML =
                    '<i class="bi bi-paperclip me-1"></i>Attachment saat ini: <a href="' +
                    MITO_CERT_BASE +
                    "/" +
                    c.id +
                    '/attachment" target="_blank" rel="noopener">' +
                    escHtml(fileName) +
                    "</a>";
                currentAttachment.classList.remove("d-none");
                removeAttachmentWrap?.classList.remove("d-none");
            } else {
                currentAttachment.innerHTML = "";
                currentAttachment.classList.add("d-none");
                removeAttachmentWrap?.classList.add("d-none");
            }
        }
        if (removeAttachmentInput) removeAttachmentInput.checked = false;
        clearEmpError();
        hideEmpResults();
        if (empSearch) {
            empSearch.value = c.employee_name || "";
            if (c.employee_id)
                renderEmpSelected(
                    c.employee_name || c.employee_id,
                    c.employee_id,
                );
        }
    }

    // ---- Edit button ----
    document
        .querySelectorAll(".certification-edit-btn")
        .forEach(function (btn) {
            btn.addEventListener("click", async function () {
                const id = this.dataset.certificationId;
                if (!id) return;
                const modalEl = document.getElementById(
                    "addCertificationModal",
                );
                const form = document.getElementById("certificationForm");
                const submitBtn = form.querySelector(
                    "#btnCertificationFormSubmit",
                );
                form.querySelectorAll(".is-invalid").forEach(function (el) {
                    el.classList.remove("is-invalid");
                });
                resetEmpPicker();
                // Flag as edit BEFORE show() so the show.bs.modal handler treats it
                // as an edit and does not reset the form back to a blank Add state.
                form.querySelector("#certificationFormId").value = id;
                form.querySelector("#certificationFormMethod").value = "PUT";
                form.querySelector("#certificationFormCode").setAttribute(
                    "readonly",
                    "readonly",
                );
                const prevBtnHtml = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-1"></span>Memuat data...';
                new bootstrap.Modal(modalEl).show();
                try {
                    const res = await fetch(
                        MITO_CERT_BASE + "/" + id + "/json",
                        { headers: HEADERS },
                    );
                    const data = await res.json();
                    if (!data.success) {
                        showToast(
                            data.message || "Gagal memuat data.",
                            "error",
                        );
                        bootstrap.Modal.getInstance(modalEl)?.hide();
                        return;
                    }
                    if (!modalEl.classList.contains("show")) return; // closed while loading
                    fillEditFormData(data.certification);
                } catch (_) {
                    showToast("Gagal memuat data.", "error");
                    bootstrap.Modal.getInstance(modalEl)?.hide();
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = prevBtnHtml;
                }
            });
        });

    // ---- View detail (shows employee + attachment) ----
    document
        .querySelectorAll(".certification-view-btn")
        .forEach(function (btn) {
            btn.addEventListener("click", async function () {
                const body = document.getElementById("viewCertificationBody");
                body.innerHTML =
                    '<div class="text-center text-muted py-4">Memuat...</div>';
                const modal = new bootstrap.Modal(
                    document.getElementById("viewCertificationModal"),
                );
                modal.show();
                try {
                    const res = await fetch(
                        MITO_CERT_BASE +
                            "/" +
                            this.dataset.certificationId +
                            "/json",
                        { headers: HEADERS },
                    );
                    const data = await res.json();
                    if (!data.success) {
                        body.innerHTML =
                            '<div class="alert alert-danger mb-0">' +
                            (data.message || "Gagal memuat.") +
                            "</div>";
                        return;
                    }
                    const c = data.certification;
                    body.innerHTML =
                        '<div class="row"><div class="col-md-6"><div class="asset-detail-item"><div class="asset-detail-label">Nama Sertifikasi</div><div class="asset-detail-value fw-semibold">' +
                        escHtml(c.name || "-") +
                        "</div></div></div>" +
                        '<div class="col-md-6"><div class="asset-detail-item"><div class="asset-detail-label">Kode</div><div class="asset-detail-value id-mono">' +
                        escHtml(c.cert_code || "-") +
                        "</div></div></div></div>" +
                        "<hr>" +
                        '<div class="row g-3"><div class="col-md-6"><div class="asset-detail-item"><div class="asset-detail-label">Klasifikasi</div><div class="asset-detail-value">' +
                        escHtml(c.cert_type || "-") +
                        "</div></div></div>" +
                        '<div class="col-md-6"><div class="asset-detail-item"><div class="asset-detail-label">Status</div><div class="asset-detail-value"><span class="badge-status">' +
                        escHtml(c.status || "-") +
                        "</span></div></div></div>" +
                        '<div class="col-md-6"><div class="asset-detail-item"><div class="asset-detail-label">Karyawan</div><div class="asset-detail-value">' +
                        escHtml(c.employee_name || "-") +
                        " (" +
                        escHtml(c.employee_id || "-") +
                        ")</div></div></div>" +
                        '<div class="col-md-6"><div class="asset-detail-item"><div class="asset-detail-label">Division / Department</div><div class="asset-detail-value">' +
                        escHtml(c.division || "-") +
                        " / " +
                        escHtml(c.department || "-") +
                        "</div></div></div>" +
                        '<div class="col-md-6"><div class="asset-detail-item"><div class="asset-detail-label">Produk / Scope</div><div class="asset-detail-value">' +
                        escHtml(c.product_scope || "-") +
                        "</div></div></div>" +
                        '<div class="col-md-6"><div class="asset-detail-item"><div class="asset-detail-label">Brand</div><div class="asset-detail-value">' +
                        escHtml(c.brand || "-") +
                        "</div></div></div>" +
                        '<div class="col-md-6"><div class="asset-detail-item"><div class="asset-detail-label">Scope Perusahaan</div><div class="asset-detail-value">' +
                        escHtml(c.company_scope || "-") +
                        "</div></div></div>" +
                        '<div class="col-md-6"><div class="asset-detail-item"><div class="asset-detail-label">Lembaga Penerbit</div><div class="asset-detail-value">' +
                        escHtml(c.issuing_organization || "-") +
                        "</div></div></div>" +
                        '<div class="col-md-6"><div class="asset-detail-item"><div class="asset-detail-label">Nomor Sertifikat</div><div class="asset-detail-value">' +
                        escHtml(c.certificate_number || "-") +
                        "</div></div></div>" +
                        '<div class="col-md-6"><div class="asset-detail-item"><div class="asset-detail-label">Tanggal Terbit</div><div class="asset-detail-value">' +
                        escHtml(formatDisplayDate(c.issue_date) || "-") +
                        "</div></div></div>" +
                        '<div class="col-md-6"><div class="asset-detail-item"><div class="asset-detail-label">Tanggal Kedaluwarsa</div><div class="asset-detail-value">' +
                        escHtml(formatDisplayDate(c.expiry_date) || "-") +
                        "</div></div></div>" +
                        (c.description
                            ? '<div class="col-12"><div class="asset-detail-item"><div class="asset-detail-label">Deskripsi</div><div class="asset-detail-value">' +
                              escHtml(c.description) +
                              "</div></div></div>"
                            : "") +
                        (c.notes
                            ? '<div class="col-12"><div class="asset-detail-item"><div class="asset-detail-label">Catatan</div><div class="asset-detail-value">' +
                              escHtml(c.notes) +
                              "</div></div></div>"
                            : "") +
                        (c.attachment_path
                            ? '<div class="col-12 mt-2"><div class="asset-detail-label mb-2">Attachment</div>' +
                              (/\.(jpe?g|png|webp)$/i.test(c.attachment_path)
                                  ? '<img class="img-fluid rounded border" style="max-height:360px" src="' +
                                    MITO_CERT_BASE +
                                    "/" +
                                    c.id +
                                    '/attachment" alt="Attachment sertifikasi">'
                                  : '<a class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" href="' +
                                    MITO_CERT_BASE +
                                    "/" +
                                    c.id +
                                    '/attachment"><i class="bi bi-file-earmark-pdf me-1"></i>Buka Dokumen PDF</a>') +
                              "</div>"
                            : '<div class="col-12 mt-2 text-muted small">Tidak ada attachment.</div>') +
                        "</div>";
                } catch (_) {
                    body.innerHTML =
                        '<div class="alert alert-danger mb-0">Kesalahan jaringan.</div>';
                }
            });
        });

    // Full clean-up when the modal closes (Add Cancel AND Edit Cancel/close) so
    // stale employee A can never bleed into the next Add.
    document
        .getElementById("addCertificationModal")
        ?.addEventListener("hidden.bs.modal", function () {
            const form = this.querySelector("#certificationForm");
            form.reset();
            form.querySelectorAll(".is-invalid").forEach((el) =>
                el.classList.remove("is-invalid"),
            );
            form.querySelector("#certificationFormId").value = "";
            form.querySelector("#certificationFormMethod").value = "POST";
            form.querySelector("#certificationFormCode").removeAttribute(
                "readonly",
            );
            if (attachmentInput) attachmentInput.value = "";
            currentAttachment?.classList.add("d-none");
            if (currentAttachment) currentAttachment.innerHTML = "";
            removeAttachmentWrap?.classList.add("d-none");
            if (removeAttachmentInput) removeAttachmentInput.checked = false;
            if (certTypeInput) certTypeInput.value = "";
            if (productScopeInput) productScopeInput.value = "";
            if (brandInput) brandInput.value = "";
            if (companyScopeInput) companyScopeInput.value = "";
            updateClassificationFields();
            if (empSearch) empSearch.value = "";
            resetEmpPicker();
        });

    // ---- Delete ----
    document
        .querySelectorAll(".certification-delete-btn")
        .forEach(function (btn) {
            btn.addEventListener("click", function () {
                const id = this.dataset.certificationId;
                const code = this.dataset.certCode || "-";
                const name = this.dataset.name || "sertifikasi ini";
                const emp = this.dataset.employee || "-";
                document.getElementById("deleteCertId").value = id;
                document.getElementById("deleteCertCode").textContent = code;
                document.getElementById("deleteCertName").textContent = name;
                document.getElementById("deleteCertEmployee").textContent = emp;
                const err = document.getElementById("deleteCertError");
                err.style.display = "none";
                err.textContent = "";
                new bootstrap.Modal(
                    document.getElementById("deleteCertificationModal"),
                ).show();
            });
        });

    document
        .getElementById("btnDeleteCertConfirm")
        ?.addEventListener("click", async function () {
            const btn = this;
            const id = document.getElementById("deleteCertId").value;
            const err = document.getElementById("deleteCertError");
            err.style.display = "none";
            setLoading(btn, true);
            try {
                const res = await fetch(MITO_CERT_BASE + "/" + id, {
                    method: "DELETE",
                    headers: HEADERS,
                });
                const data = await res.json().catch(function () {
                    return { success: false, message: "Respons tidak valid." };
                });
                if (res.ok && data.success) {
                    showToast(data.message || "Berhasil dihapus.", "success");
                    bootstrap.Modal.getInstance(
                        document.getElementById("deleteCertificationModal"),
                    )?.hide();
                    setTimeout(function () {
                        location.reload();
                    }, 600);
                } else {
                    err.textContent =
                        data.message || "Gagal (HTTP " + res.status + ").";
                    err.style.display = "block";
                    showToast(err.textContent, "error");
                }
            } catch (_) {
                err.textContent = "Kesalahan jaringan.";
                err.style.display = "block";
                showToast(err.textContent, "error");
            } finally {
                setLoading(btn, false);
            }
        });

    // ---- Row code generation ----
    document
        .querySelectorAll(".certification-gencode-btn")
        .forEach(function (btn) {
            btn.addEventListener("click", async function () {
                const id = this.dataset.certificationId;
                this.disabled = true;
                try {
                    const res = await fetch(
                        MITO_CERT_BASE + "/" + id + "/generate-code",
                        { method: "POST", headers: HEADERS },
                    );
                    const data = await res.json();
                    if (res.ok && data.success) {
                        showToast(data.message, "success");
                        setTimeout(function () {
                            location.reload();
                        }, 600);
                    } else
                        showToast(
                            data.message || "Gagal generate kode.",
                            "error",
                        );
                } catch (_) {
                    showToast("Kesalahan jaringan.", "error");
                } finally {
                    this.disabled = false;
                }
            });
        });
    // Hoisted helper: renders the typeahead option list (name / id / position / dept).
    function renderEmpOptions(list) {
        empResults.innerHTML = list
            .map(function (e, i) {
                return (
                    '<button type="button" class="list-group-item list-group-item-action employee-option" data-index="' +
                    i +
                    '"' +
                    ' data-emp-id="' +
                    escHtml(e.employeeId) +
                    '"' +
                    ' data-emp-name="' +
                    escHtml(e.fullName || "") +
                    '"' +
                    ' data-emp-division="' +
                    escHtml(e.division || "") +
                    '"' +
                    ' data-emp-department="' +
                    escHtml(e.department || "") +
                    '">' +
                    '<div class="fw-semibold">' +
                    escHtml(e.fullName || "") +
                    "</div>" +
                    '<small class="text-muted">' +
                    escHtml(e.employeeId || "") +
                    (e.jobPosition ? " - " + escHtml(e.jobPosition) : "") +
                    "</small>" +
                    (e.division
                        ? '<small class="text-muted d-block">' +
                          escHtml(e.division) +
                          (e.department ? " / " + escHtml(e.department) : "") +
                          "</small>"
                        : "") +
                    "</button>"
                );
            })
            .join("");
        empResults.classList.remove("d-none");
    }
})();
