// ============================================================
// Dashboard.js — HR Recruitment Dashboard
// PT Mahakarya Sukses Indonesia
// ============================================================

// ============================================================
// GLOBAL STATE
// ============================================================
var allCandidates = [];
var filteredCandidates = [];
var currentCandidate = null;

// Status options for dropdown
var STATUS_OPTIONS = [
  { value: "Applied", label: "Applied", color: "#005BAC" },
  { value: "Screening", label: "Screening", color: "#7c3aed" },
  { value: "HR Interview", label: "HR Interview", color: "#8a6100" },
  { value: "User Interview", label: "User Interview", color: "#0891b2" },
  { value: "Offering", label: "Offering", color: "#92400e" },
  { value: "Hired", label: "Hired", color: "#166534" },
  { value: "Rejected", label: "Rejected", color: "#991b1b" },
];

// ============================================================
// DOM REFERENCES
// ============================================================
var sidebar = document.getElementById("sidebar");
var sidebarOverlay = document.getElementById("sidebarOverlay");
var btnBurger = document.getElementById("btnBurger");
var searchInput = document.getElementById("searchInput");
var filterStatus = document.getElementById("filterStatus");
var filterPosition = document.getElementById("filterPosition");
var filterEducation = document.getElementById("filterEducation");
var filterExperience = document.getElementById("filterExperience");
var filterDate = document.getElementById("filterDate");
var tableBody = document.getElementById("tableBody");
var tableLoading = document.getElementById("tableLoading");
var tableEmpty = document.getElementById("tableEmpty");

// Modal references
var cvModal = document.getElementById("cvModal");
var deleteModal = document.getElementById("deleteModal");

// ============================================================
// SIDEBAR TOGGLE (MOBILE)
// ============================================================
function openSidebar() {
  sidebar.classList.add("show");
  sidebarOverlay.classList.add("show");
}

function closeSidebar() {
  sidebar.classList.remove("show");
  sidebarOverlay.classList.remove("show");
}

if (btnBurger) {
  btnBurger.addEventListener("click", openSidebar);
}
if (sidebarOverlay) {
  sidebarOverlay.addEventListener("click", closeSidebar);
}

// ============================================================
// NAVIGATION — PAGE SWITCHING
// ============================================================
document.querySelectorAll(".nav-item").forEach(function (item) {
  item.addEventListener("click", function () {
    var page = this.getAttribute("data-page");
    if (!page) return;

    // Update active nav
    document.querySelectorAll(".nav-item").forEach(function (i) {
      i.classList.remove("active");
    });
    this.classList.add("active");

    // Show page
    document.querySelectorAll(".page").forEach(function (p) {
      p.classList.remove("active");
    });
    var targetPage = document.getElementById("page-" + page);
    if (targetPage) {
      targetPage.classList.add("active");
    }

    // Update topbar title
    var titleEl = document.getElementById("topbarTitle");
    if (titleEl) {
      titleEl.textContent = this.querySelector("span").textContent;
    }

    // Close sidebar on mobile
    if (window.innerWidth < 992) {
      closeSidebar();
    }

    // Load data if recruitment page
    if (page === "recruitment" && allCandidates.length === 0) {
      loadCandidates();
    }
  });
});

// ============================================================
// CLOCK — UPDATE DATE & TIME
// ============================================================
function updateClock() {
  var now = new Date();
  var days = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
  var months = [
    "Januari",
    "Februari",
    "Maret",
    "April",
    "Mei",
    "Juni",
    "Juli",
    "Agustus",
    "September",
    "Oktober",
    "November",
    "Desember",
  ];

  var dayName = days[now.getDay()];
  var dateStr =
    now.getDate() + " " + months[now.getMonth()] + " " + now.getFullYear();
  var timeStr =
    String(now.getHours()).padStart(2, "0") +
    ":" +
    String(now.getMinutes()).padStart(2, "0") +
    ":" +
    String(now.getSeconds()).padStart(2, "0");

  var dateEl = document.getElementById("currentDate");
  var timeEl = document.getElementById("currentTime");
  if (dateEl) dateEl.textContent = dayName + ", " + dateStr;
  if (timeEl) timeEl.textContent = timeStr;
}

setInterval(updateClock, 1000);
updateClock();

// ============================================================
// LOAD CANDIDATES FROM APPS SCRIPT
// ============================================================
function loadCandidates() {
  if (tableLoading) tableLoading.style.display = "flex";
  if (tableEmpty) tableEmpty.style.display = "none";
  if (tableBody) tableBody.innerHTML = "";

  google.script.run
    .withSuccessHandler(function (data) {
      allCandidates = data || [];
      filteredCandidates = allCandidates.slice();
      populateFilters();
      renderTable();
      updateSummaryCards();
      if (tableLoading) tableLoading.style.display = "none";
    })
    .withFailureHandler(function (error) {
      if (tableLoading) tableLoading.style.display = "none";
      if (tableBody) {
        tableBody.innerHTML =
          '<tr><td colspan="8" class="text-center text-danger py-4">' +
          '<i class="bi bi-exclamation-triangle-fill"></i> Gagal memuat data: ' +
          error.message +
          "</td></tr>";
      }
    })
    .getRecruitmentList();
}

// ============================================================
// POPULATE FILTER DROPDOWNS
// ============================================================
function populateFilters() {
  // Position filter
  var positions = [];
  allCandidates.forEach(function (c) {
    if (c.positionApplied && positions.indexOf(c.positionApplied) === -1) {
      positions.push(c.positionApplied);
    }
  });
  positions.sort();
  if (filterPosition) {
    filterPosition.innerHTML = '<option value="">Semua Posisi</option>';
    positions.forEach(function (p) {
      filterPosition.innerHTML +=
        '<option value="' + p + '">' + p + "</option>";
    });
  }

  // Education filter
  var educations = [];
  allCandidates.forEach(function (c) {
    if (c.education && educations.indexOf(c.education) === -1) {
      educations.push(c.education);
    }
  });
  educations.sort();
  if (filterEducation) {
    filterEducation.innerHTML = '<option value="">Semua Pendidikan</option>';
    educations.forEach(function (e) {
      filterEducation.innerHTML +=
        '<option value="' + e + '">' + e + "</option>";
    });
  }

  // Experience filter
  var experiences = [];
  allCandidates.forEach(function (c) {
    if (c.workExperience && experiences.indexOf(c.workExperience) === -1) {
      experiences.push(c.workExperience);
    }
  });
  experiences.sort();
  if (filterExperience) {
    filterExperience.innerHTML = '<option value="">Semua Pengalaman</option>';
    experiences.forEach(function (e) {
      filterExperience.innerHTML +=
        '<option value="' + e + '">' + e + "</option>";
    });
  }
}

// ============================================================
// FILTER & SEARCH
// ============================================================
function applyFilters() {
  var search = searchInput ? searchInput.value.toLowerCase().trim() : "";
  var status = filterStatus ? filterStatus.value : "";
  var position = filterPosition ? filterPosition.value : "";
  var education = filterEducation ? filterEducation.value : "";
  var experience = filterExperience ? filterExperience.value : "";
  var dateFilter = filterDate ? filterDate.value : "";

  filteredCandidates = allCandidates.filter(function (c) {
    // Search
    if (search) {
      var matchSearch =
        (c.fullName && c.fullName.toLowerCase().indexOf(search) !== -1) ||
        (c.nik && c.nik.toLowerCase().indexOf(search) !== -1) ||
        (c.email && c.email.toLowerCase().indexOf(search) !== -1) ||
        (c.phone && c.phone.toLowerCase().indexOf(search) !== -1);
      if (!matchSearch) return false;
    }

    // Status filter
    if (status && c.status !== status) return false;

    // Position filter
    if (position && c.positionApplied !== position) return false;

    // Education filter
    if (education && c.education !== education) return false;

    // Experience filter
    if (experience && c.workExperience !== experience) return false;

    // Date filter
    if (dateFilter) {
      var today = new Date();
      var createdDate = parseDate(c.createdDate);
      if (createdDate) {
        if (dateFilter === "today") {
          if (createdDate.toDateString() !== today.toDateString()) return false;
        } else if (dateFilter === "week") {
          var weekAgo = new Date(today);
          weekAgo.setDate(weekAgo.getDate() - 7);
          if (createdDate < weekAgo) return false;
        } else if (dateFilter === "month") {
          if (
            createdDate.getMonth() !== today.getMonth() ||
            createdDate.getFullYear() !== today.getFullYear()
          )
            return false;
        }
      }
    }

    return true;
  });

  renderTable();
}

// Helper: parse date string "dd/MM/yyyy HH:mm"
function parseDate(dateStr) {
  if (!dateStr) return null;
  var parts = dateStr.split(" ");
  var dateParts = parts[0].split("/");
  if (dateParts.length !== 3) return null;
  return new Date(dateParts[2], dateParts[1] - 1, dateParts[0]);
}

// Attach filter listeners
if (searchInput) {
  searchInput.addEventListener("input", applyFilters);
}
if (filterStatus) {
  filterStatus.addEventListener("change", applyFilters);
}
if (filterPosition) {
  filterPosition.addEventListener("change", applyFilters);
}
if (filterEducation) {
  filterEducation.addEventListener("change", applyFilters);
}
if (filterExperience) {
  filterExperience.addEventListener("change", applyFilters);
}
if (filterDate) {
  filterDate.addEventListener("change", applyFilters);
}

// ============================================================
// RENDER TABLE
// ============================================================
function renderTable() {
  if (!tableBody) return;

  if (filteredCandidates.length === 0) {
    tableBody.innerHTML = "";
    if (tableEmpty) tableEmpty.style.display = "block";
    return;
  }

  if (tableEmpty) tableEmpty.style.display = "none";

  var html = "";
  filteredCandidates.forEach(function (c) {
    var statusClass = getStatusClass(c.status);
    var phoneClean = String(c.phone || "").replace(/[^0-9]/g, "");
    if (phoneClean.startsWith("0")) phoneClean = "62" + phoneClean.substring(1);

    html +=
      "<tr>" +
      "<td>" +
      escapeHtml(c.createdDate || "-") +
      "</td>" +
      "<td><strong>" +
      escapeHtml(c.fullName || "-") +
      "</strong></td>" +
      "<td>" +
      escapeHtml(c.positionApplied || "-") +
      "</td>" +
      "<td>" +
      escapeHtml(c.phone || "-") +
      "</td>" +
      "<td>" +
      escapeHtml(c.education || "-") +
      "</td>" +
      "<td>" +
      escapeHtml(c.workExperience || "-") +
      "</td>" +
      "<td>" +
      renderStatusDropdown(c) +
      "</td>" +
      "<td>" +
      renderActionButtons(c, phoneClean) +
      "</td>" +
      "</tr>";
  });

  tableBody.innerHTML = html;

  // Attach status dropdown listeners
  attachStatusDropdowns();

  // Attach action button listeners
  attachActionButtons();
}

// ============================================================
// STATUS HELPERS
// ============================================================
function getStatusClass(status) {
  var statusMap = {
    Applied: "Applied",
    Screening: "Screening",
    "HR Interview": "HR",
    "User Interview": "User",
    Offering: "Offering",
    Hired: "Hired",
    Rejected: "Rejected",
    Pending: "Pending",
    Accepted: "Hired",
    Hold: "Screening",
    Blacklist: "Rejected",
  };
  return statusMap[status] || "Pending";
}

function getStatusLabel(status) {
  var labelMap = {
    Applied: "Applied",
    Screening: "Screening",
    "HR Interview": "HR Interview",
    "User Interview": "User Interview",
    Offering: "Offering",
    Hired: "Hired",
    Rejected: "Rejected",
    Pending: "Pending",
    Accepted: "Hired",
    Hold: "On Hold",
    Blacklist: "Blacklisted",
  };
  return labelMap[status] || status || "Pending";
}

function renderStatusDropdown(c) {
  var statusClass = getStatusClass(c.status);
  var html =
    '<div class="status-dropdown">' +
    '<button class="status-dropdown-btn status-badge ' +
    statusClass +
    '" ' +
    'data-recruitment-id="' +
    escapeHtml(c.recruitmentId) +
    '">' +
    getStatusLabel(c.status) +
    ' <i class="bi bi-chevron-down"></i></button>' +
    '<div class="status-dropdown-menu">';

  STATUS_OPTIONS.forEach(function (opt) {
    html +=
      '<div class="status-dropdown-item" ' +
      'data-recruitment-id="' +
      escapeHtml(c.recruitmentId) +
      '" ' +
      'data-new-status="' +
      opt.value +
      '">' +
      '<span class="dot" style="background:' +
      opt.color +
      '"></span>' +
      opt.label +
      "</div>";
  });

  html += "</div></div>";
  return html;
}

// ============================================================
// STATUS DROPDOWN — ATTACH LISTENERS
// ============================================================
function attachStatusDropdowns() {
  document.querySelectorAll(".status-dropdown-btn").forEach(function (btn) {
    btn.addEventListener("click", function (e) {
      e.stopPropagation();
      var menu = this.nextElementSibling;
      // Close all other menus
      document.querySelectorAll(".status-dropdown-menu").forEach(function (m) {
        if (m !== menu) m.classList.remove("show");
      });
      menu.classList.toggle("show");
    });
  });

  document.querySelectorAll(".status-dropdown-item").forEach(function (item) {
    item.addEventListener("click", function (e) {
      e.stopPropagation();
      var recruitmentId = this.getAttribute("data-recruitment-id");
      var newStatus = this.getAttribute("data-new-status");

      // Close menu
      document.querySelectorAll(".status-dropdown-menu").forEach(function (m) {
        m.classList.remove("show");
      });

      // Update status via Apps Script
      updateStatus(recruitmentId, newStatus);
    });
  });
}

// Close dropdowns when clicking outside
document.addEventListener("click", function () {
  document.querySelectorAll(".status-dropdown-menu").forEach(function (m) {
    m.classList.remove("show");
  });
});

// ============================================================
// UPDATE STATUS — APPS SCRIPT CALL
// ============================================================
function updateStatus(recruitmentId, newStatus) {
  // Optimistic UI update
  var candidate = allCandidates.find(function (c) {
    return c.recruitmentId === recruitmentId;
  });
  if (candidate) {
    candidate.status = newStatus;
  }

  // Update UI immediately
  renderTable();
  updateSummaryCards();

  // Call Apps Script to persist
  google.script.run
    .withSuccessHandler(function (response) {
      if (response && response.success) {
        // Success - data already updated in UI
      } else {
        // Revert on failure
        alert(
          "Gagal mengubah status: " +
            (response && response.message ? response.message : "Unknown error"),
        );
        loadCandidates(); // Reload to get correct state
      }
    })
    .withFailureHandler(function (error) {
      alert("Gagal mengubah status: " + error.message);
      loadCandidates();
    })
    .updateCandidateStatus(recruitmentId, newStatus, null);
}

// ============================================================
// ACTION BUTTONS
// ============================================================
function renderActionButtons(c, phoneClean) {
  var html =
    '<div class="action-btns">' +
    '<button class="action-btn view" title="Lihat CV" ' +
    'data-recruitment-id="' +
    escapeHtml(c.recruitmentId) +
    '">' +
    '<i class="bi bi-eye-fill"></i></button>' +
    '<button class="action-btn whatsapp" title="WhatsApp" ' +
    'data-phone="' +
    phoneClean +
    '">' +
    '<i class="bi bi-whatsapp"></i></button>' +
    '<button class="action-btn delete" title="Hapus" ' +
    'data-recruitment-id="' +
    escapeHtml(c.recruitmentId) +
    '" ' +
    'data-candidate-name="' +
    escapeHtml(c.fullName) +
    '">' +
    '<i class="bi bi-trash-fill"></i></button>' +
    "</div>";
  return html;
}

function attachActionButtons() {
  // View CV
  document.querySelectorAll(".action-btn.view").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var id = this.getAttribute("data-recruitment-id");
      openCvModal(id);
    });
  });

  // WhatsApp
  document.querySelectorAll(".action-btn.whatsapp").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var phone = this.getAttribute("data-phone");
      if (phone) {
        window.open("https://wa.me/" + phone, "_blank");
      }
    });
  });

  // Delete
  document.querySelectorAll(".action-btn.delete").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var id = this.getAttribute("data-recruitment-id");
      var name = this.getAttribute("data-candidate-name");
      openDeleteModal(id, name);
    });
  });
}

// ============================================================
// CV MODAL
// ============================================================
function openCvModal(recruitmentId) {
  var candidate = allCandidates.find(function (c) {
    return c.recruitmentId === recruitmentId;
  });
  if (!candidate) return;

  currentCandidate = candidate;

  // Populate modal
  document.getElementById("cvName").textContent = candidate.fullName || "-";
  document.getElementById("cvPosition").textContent =
    candidate.positionApplied || "-";
  document.getElementById("cvPhone").textContent = candidate.phone || "-";
  document.getElementById("cvEmail").textContent = candidate.email || "-";
  document.getElementById("cvEducation").textContent =
    candidate.education || "-";
  document.getElementById("cvExperience").textContent =
    candidate.workExperience || "-";
  document.getElementById("cvSalary").textContent = formatSalary(
    candidate.expectedSalary,
  );
  document.getElementById("cvStatus").textContent = getStatusLabel(
    candidate.status,
  );
  document.getElementById("cvStatus").className =
    "value status-badge " + getStatusClass(candidate.status);

  // CV Link
  var cvLink = candidate.cvLink || "";
  var btnViewCv = document.getElementById("btnViewCv");
  var btnDownloadCv = document.getElementById("btnDownloadCv");
  if (cvLink) {
    btnViewCv.style.display = "inline-flex";
    btnDownloadCv.style.display = "inline-flex";
    btnViewCv.onclick = function () {
      window.open(cvLink, "_blank");
    };
    btnDownloadCv.onclick = function () {
      window.open(cvLink, "_blank");
    };
  } else {
    btnViewCv.style.display = "none";
    btnDownloadCv.style.display = "none";
  }

  // Load and render Activity Timeline
  renderActivityTimeline(recruitmentId);

  // WhatsApp button
  var phoneClean = String(candidate.phone || "").replace(/[^0-9]/g, "");
  if (phoneClean.startsWith("0")) phoneClean = "62" + phoneClean.substring(1);
  var btnWa = document.getElementById("btnCvWhatsapp");
  btnWa.onclick = function () {
    window.open("https://wa.me/" + phoneClean, "_blank");
  };

  // Show modal
  cvModal.classList.add("show");
}

function closeCvModal() {
  cvModal.classList.remove("show");
  currentCandidate = null;
}

// ============================================================
// DELETE MODAL
// ============================================================
function openDeleteModal(recruitmentId, candidateName) {
  document.getElementById("deleteCandidateName").textContent =
    candidateName || "-";
  document
    .getElementById("btnConfirmDelete")
    .setAttribute("data-recruitment-id", recruitmentId);
  deleteModal.classList.add("show");
}

function closeDeleteModal() {
  deleteModal.classList.remove("show");
}

// Confirm delete
document
  .getElementById("btnConfirmDelete")
  .addEventListener("click", function () {
    var recruitmentId = this.getAttribute("data-recruitment-id");
    closeDeleteModal();

    // Call Apps Script to delete
    google.script.run
      .withSuccessHandler(function (response) {
        if (response && response.success) {
          // Remove from local array
          allCandidates = allCandidates.filter(function (c) {
            return c.recruitmentId !== recruitmentId;
          });
          applyFilters();
          updateSummaryCards();
        } else {
          alert(
            "Gagal menghapus kandidat: " +
              (response && response.message
                ? response.message
                : "Unknown error"),
          );
        }
      })
      .withFailureHandler(function (error) {
        alert("Gagal menghapus kandidat: " + error.message);
      })
      .deleteCandidate(recruitmentId);
  });

// ============================================================
// MODAL CLOSE LISTENERS
// ============================================================
document.querySelectorAll(".modal-close").forEach(function (btn) {
  btn.addEventListener("click", function () {
    cvModal.classList.remove("show");
    deleteModal.classList.remove("show");
  });
});

document.querySelectorAll(".modal-overlay").forEach(function (overlay) {
  overlay.addEventListener("click", function (e) {
    if (e.target === this) {
      this.classList.remove("show");
    }
  });
});

// ============================================================
// SUMMARY CARDS
// ============================================================
function updateSummaryCards() {
  var total = allCandidates.length;
  var today = new Date().toDateString();
  var todayCount = allCandidates.filter(function (c) {
    var d = parseDate(c.createdDate);
    return d && d.toDateString() === today;
  }).length;

  var thisMonth = new Date();
  var monthCount = allCandidates.filter(function (c) {
    var d = parseDate(c.createdDate);
    return (
      d &&
      d.getMonth() === thisMonth.getMonth() &&
      d.getFullYear() === thisMonth.getFullYear()
    );
  }).length;

  var appliedCount = allCandidates.filter(function (c) {
    return c.status === "Applied" || c.status === "Pending";
  }).length;

  var interviewCount = allCandidates.filter(function (c) {
    return (
      c.status === "HR Interview" ||
      c.status === "User Interview" ||
      c.status === "Screening"
    );
  }).length;

  var hiredCount = allCandidates.filter(function (c) {
    return c.status === "Hired" || c.status === "Accepted";
  }).length;

  var rejectedCount = allCandidates.filter(function (c) {
    return c.status === "Rejected" || c.status === "Blacklist";
  }).length;

  setText("statTotal", total);
  setText("statToday", todayCount);
  setText("statMonth", monthCount);
  setText("statApplied", appliedCount);
  setText("statInterview", interviewCount);
  setText("statHired", hiredCount);
  setText("statRejected", rejectedCount);
}

function setText(id, value) {
  var el = document.getElementById(id);
  if (el) el.textContent = value;
}

// ============================================================
// HELPERS
// ============================================================
function escapeHtml(str) {
  if (!str) return "";
  var amp = "\x26";
  return String(str)
    .replace(/&/g, amp + "amp;")
    .replace(/</g, amp + "lt;")
    .replace(/>/g, amp + "gt;")
    .replace(/"/g, amp + "quot;")
    .replace(/'/g, amp + "#039;");
}

function formatSalary(salary) {
  if (!salary || salary === 0) return "-";
  return "Rp " + Number(salary).toLocaleString("id-ID");
}

// ============================================================
// ACTIVITY TIMELINE — Load and render for modal
// ============================================================
function renderActivityTimeline(recruitmentId) {
  var container = document.getElementById("activityTimeline");
  if (!container) return;

  container.innerHTML =
    '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-secondary"></div></div>';

  google.script.run
    .withSuccessHandler(function (logs) {
      if (!logs || logs.length === 0) {
        container.innerHTML =
          '<div class="text-center py-3 text-muted" style="font-size:12.5px;">Belum ada riwayat aktivitas.</div>';
        return;
      }

      var html = '<div class="timeline-list">';
      logs.forEach(function (log) {
        html +=
          '<div class="timeline-item">' +
          '<div class="timeline-dot"></div>' +
          '<div class="timeline-content">' +
          '<div class="timeline-header">' +
          '<span class="timeline-action">' +
          escapeHtml(log.action) +
          "</span>" +
          '<span class="timeline-time">' +
          escapeHtml(log.timestamp) +
          "</span>" +
          "</div>" +
          '<div class="timeline-body">' +
          '<div class="timeline-user"><i class="bi bi-person-fill"></i> ' +
          escapeHtml(log.user) +
          "</div>" +
          (log.oldValue && log.oldValue !== "-"
            ? '<div class="timeline-change">' +
              escapeHtml(log.oldValue) +
              " \u2192 " +
              escapeHtml(log.newValue) +
              "</div>"
            : "") +
          "</div></div></div>";
      });
      html += "</div>";
      container.innerHTML = html;
    })
    .withFailureHandler(function (error) {
      container.innerHTML =
        '<div class="text-center py-3 text-danger" style="font-size:12.5px;">Gagal memuat riwayat.</div>';
    })
    .getAuditLogForCandidate(recruitmentId);
}

// ============================================================
// INIT — Load dashboard data on page load
// ============================================================
loadCandidates();
