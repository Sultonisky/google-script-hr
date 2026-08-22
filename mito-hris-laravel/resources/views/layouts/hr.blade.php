<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="icon" type="image/png" href="{{ asset('assets/logo-favicon.png') }}">
  <title>@yield('title', 'MITO HRIS — Dashboard')</title>

  <script>
    // Immediate theme initializer to avoid light flicker
    (function() {
      const savedTheme = localStorage.getItem('mito_theme') || 'light';
      document.documentElement.setAttribute('data-theme', savedTheme);
      document.documentElement.setAttribute('data-bs-theme', savedTheme);
    })();
  </script>

  @vite(['resources/scss/app.scss', 'resources/scss/hr.scss', 'resources/js/app.js'])
  @yield('styles')
</head>
<body>
  <!-- Sidebar Overlay for Mobile -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- 1:1 Sidebar -->
  @include('components.hr-sidebar')

  <!-- 1:1 Main Content -->
  <div class="main-content">
    @include('components.hr-topbar')

    <div class="content-wrap">
      @include('components.alerts')
      @yield('content')
    </div>
  </div>

  <!-- 1:1 Floating Action Button (FAB) -->
  @include('hr.partials.floating-action-button')

  <!-- 1:1 Modals -->
  @include('hr.partials.rotation-modal')
  @include('hr.partials.entity-modals')
  @include('hr.partials.probation-modals')
  @include('hr.partials.off-contract-modal')
  @include('hr.partials.status-page-modals')
  @include('hr.recruitment.modals.action-modals')

  <!-- 1:1 Drawer (Candidate/Employee/Outsource Profile) -->
  @include('components.hr-drawer')

  <!-- Core Scripts for Burger, Theme & FAB -->
  <script>
    // Drawer Functions (1:1 from GAS js/drawer.html)
    var activeCandidateId = null;
    var drawerOverlay = null;
    var drawerPanel = null;
    var drawerJustOpened = false;

    function openDrawer() {
      drawerJustOpened = true;
      if (drawerOverlay) drawerOverlay.classList.add('show');
      if (drawerPanel) drawerPanel.classList.add('show');
      document.body.style.overflow = 'hidden';
      setTimeout(function() { drawerJustOpened = false; }, 300);
    }

    function closeDrawer() {
      if (drawerJustOpened) return;
      if (drawerOverlay) drawerOverlay.classList.remove('show');
      if (drawerPanel) drawerPanel.classList.remove('show');
      document.body.style.overflow = '';
    }

    function setDrawerMode(mode) {
      if (drawerPanel) drawerPanel.setAttribute('data-mode', mode);
      var sections = document.querySelectorAll('.drawer-mode-section');
      sections.forEach(function(sec) {
        sec.classList.remove('active');
      });
      var activeSec = document.getElementById('drawerSection' + mode.charAt(0).toUpperCase() + mode.slice(1));
      if (activeSec) activeSec.classList.add('active');

      var footerCand = document.getElementById('drawerFooterCandidate');
      var footerEntity = document.getElementById('drawerFooterEntity');
      if (footerCand) footerCand.style.display = (mode === 'candidate') ? '' : 'none';
      if (footerEntity) footerEntity.style.display = (mode !== 'candidate') ? '' : 'none';

      var btnPrint = document.getElementById('btnDrawerPrint');
      var btnEdit = document.getElementById('btnDrawerEdit');
      if (btnPrint) btnPrint.style.display = (mode === 'candidate') ? '' : 'none';
      if (btnEdit) btnEdit.style.display = (mode !== 'candidate') ? '' : 'none';
    }

    function drawerTextValue(value) {
      if (value === null || value === undefined) return '-';
      var text = String(value).trim();
      return text ? text : '-';
    }

    function setDrawerText(id, value) {
      var el = document.getElementById(id);
      if (el) el.innerText = drawerTextValue(value);
    }

    function initials(str) {
      if (!str) return '-';
      return str.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
    }

    function getCsrfToken() {
      var meta = document.querySelector('meta[name="csrf-token"]');
      return meta ? meta.getAttribute('content') : '';
    }

    window.openCandidateDrawer = function(id) {
      setDrawerMode('candidate');
      document.getElementById('drawerCandidateName').innerText = 'Memuat...';
      openDrawer();

      fetch('/hr/recruitment/' + id + '/json', {
        headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'Accept': 'application/json' }
      })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        if (!data.success) { closeDrawer(); return; }
        var c = data.candidate;
        document.getElementById('drawerCandidateName').innerText = c.fullName || '-';
        document.getElementById('drawerPosition').innerText = c.positionApplied || '-';
        document.getElementById('drawerAvatar').innerText = initials(c.fullName);
        setDrawerText('cvFullName', c.fullName);
        setDrawerText('cvBirthDate', c.birthDate);
        setDrawerText('cvAge', c.age ? c.age + ' Tahun' : '-');
        setDrawerText('cvGender', c.gender === 'Male' ? 'Laki-laki' : (c.gender === 'Female' ? 'Perempuan' : c.gender));
        setDrawerText('cvMaritalStatus', c.maritalStatus);
        setDrawerText('cvNik', c.nik);
        setDrawerText('cvPhone', c.phone);
        setDrawerText('cvEmail', c.email);
        setDrawerText('cvAddress', c.address);
        setDrawerText('cvCity', c.city);
        setDrawerText('cvPosition', c.positionApplied);
        setDrawerText('cvEducation', c.education);
        setDrawerText('cvExperience', c.workExperience);
        setDrawerText('cvLastCompany', c.lastCompany);
        setDrawerText('cvEmploymentStatus', c.currentEmploymentStatus);
        setDrawerText('cvAvailability', c.availableToJoin);
        setDrawerText('cvSalary', c.expectedSalary);
        setDrawerText('cvSource', c.recruitmentSource);
        var notesEl = document.getElementById('cvHrNotes');
        if (notesEl) notesEl.value = c.hrNotes || '';
        var metaEl = document.getElementById('drawerIdMeta');
        if (metaEl) metaEl.innerHTML = '<span>Recruitment ID<strong>' + (c.recruitmentId || '-') + '</strong></span><span>Tanggal Daftar<strong>' + (c.createdDate || '-') + '</strong></span>';
        activeCandidateId = id;

        // Update status button highlights (1:1 GAS updateStatusButtonsUI)
        var currentStatus = c.status || 'New';
        ['Accept','Hold','Blacklist'].forEach(function(key) {
          var statusMap = { 'Accept': 'accepted', 'Hold': 'hold', 'Blacklist': 'blacklist' };
          var btn = document.getElementById('btn' + key);
          if (!btn) return;
          btn.classList.toggle('current', statusMap[key].toLowerCase() === currentStatus.toLowerCase());
        });

        var tlWrap = document.getElementById('drawerTimeline');
        if (tlWrap) {
          var logs = data.auditLogs || [];
          if (!logs.length) {
            tlWrap.innerHTML = '<div class="timeline-empty">Belum ada riwayat aktivitas.</div>';
          } else {
            var iconMap = {'Created':'bi-plus-circle-fill','Update Status':'bi-arrow-repeat','Hold':'bi-pause-fill','Blacklist':'bi-slash-circle-fill','Accepted':'bi-check-lg','HR Notes Update':'bi-chat-square-text-fill'};
            var dotMap = {'Created':'created','Accepted':'accepted','Hold':'hold','Blacklist':'blacklist','HR Notes Update':'pending'};
            tlWrap.innerHTML = logs.map(function(ev) {
              var action = ev.Action || ev.action || '';
              var oldValue = ev['Old Value'] || ev.oldValue || '';
              var newValue = ev['New Value'] || ev.newValue || '';
              var timestamp = ev.Timestamp || ev.timestamp || '';
              var user = ev.User || ev.user || '';
              var dot = dotMap[action] || 'pending';
              var icon = iconMap[action] || 'bi-dot';
              return '<div class="timeline-item">' +
                '<div class="timeline-dot ' + dot + '"><i class="bi ' + icon + '"></i></div>' +
                '<div class="timeline-title">' + action + '</div>' +
                '<div class="timeline-desc">' + oldValue + ' → ' + newValue + '</div>' +
                '<div class="timeline-time"><i class="bi bi-clock"></i> ' + timestamp + ' · ' + user + '</div>' +
                '</div>';
            }).join('');
          }
        }
      })
      .catch(function() { document.getElementById('drawerCandidateName').innerText = 'Gagal memuat data.'; });
    };

    window.openEmployeeDrawer = function(id) {
      setDrawerMode('employee');
      document.getElementById('drawerCandidateName').innerText = 'Memuat...';
      openDrawer();

      fetch('/hr/employees/' + id + '/json', {
        headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'Accept': 'application/json' }
      })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        if (!data.success) { closeDrawer(); return; }
        var e = data.employee;
        document.getElementById('drawerCandidateName').innerText = e.fullName || '-';
        document.getElementById('drawerPosition').innerText = (e.jobPosition || '-') + (e.department ? ' · ' + e.department : '');
        document.getElementById('drawerAvatar').innerText = initials(e.fullName);
        setDrawerText('empDrEmployeeId', e.employeeId);
        setDrawerText('empDrFullName', e.fullName);
        setDrawerText('empDrNik', e.nikNpwp);
        setDrawerText('empDrNpwp', e.npwp);
        setDrawerText('empDrBirthPlace', e.birthPlace);
        setDrawerText('empDrBirthDate', e.birthDate);
        setDrawerText('empDrGender', e.gender === 'Male' ? 'Laki-laki' : (e.gender === 'Female' ? 'Perempuan' : e.gender));
        setDrawerText('empDrReligion', e.religion);
        setDrawerText('empDrMarital', e.maritalStatus);
        setDrawerText('empDrBloodType', e.bloodType);
        setDrawerText('empDrPtkp', e.ptkpStatus);
        setDrawerText('empDrStatusEmployee', e.statusEmployee);
        setDrawerText('empDrEmail', e.personalEmail);
        setDrawerText('empDrWorkingEmail', e.workingEmail);
        setDrawerText('empDrPhone', e.mobilePhone);
        setDrawerText('empDrAddress', e.citizenIdAddress);
        setDrawerText('empDrResidentialAddress', e.residentialAddress);
        setDrawerText('empDrBankName', e.bankName);
        setDrawerText('empDrBankHolder', e.bankAccountHolder);
        setDrawerText('empDrBankAccount', e.bankAccount);
        setDrawerText('empDrBpjsTk', e.bpjsKetenagakerjaan);
        setDrawerText('empDrBpjsKes', e.bpjsKesehatan);
        setDrawerText('empDrVendor', e.outsourceVendor);
        setDrawerText('empDrBranch', e.branchName);
        setDrawerText('empDrDivision', e.division);
        setDrawerText('empDrDept', e.department);
        setDrawerText('empDrCostCenter', e.costCenter);
        setDrawerText('empDrCity', e.lokasiKerja || e.areaKerja);
        setDrawerText('empDrDistrict', e.areaKerja);
        setDrawerText('empDrPosition', e.jobPosition);
        setDrawerText('empDrJobLevel', e.jobLevel);
        setDrawerText('empDrGrade', e.grade);
        setDrawerText('empDrJoinDate', e.joinDate);
        setDrawerText('empDrDirectSup', e.directSuperior);
        setDrawerText('empDrIndirectSup', e.indirectSuperior);
        setDrawerText('empDrContractEnd', e.endDateContract);
        setDrawerText('empDrFormerPos', e.jobPositionFormer);
        setDrawerText('empDrRotationType', e.typeOfRotation);
        setDrawerText('empDrMutasiDate', e.rotationDate);
        setDrawerText('empDrNoSk', e.nomorSk);
        setDrawerText('empDrResignDate', e.resignDate);
        setDrawerText('empDrOffbType', e.offboardingType);
        setDrawerText('empDrOffbReason', e.offboardingReason);
        setDrawerText('empDrCreatedBy', e.createdBy);
        setDrawerText('empDrCreated', e.createdAt);
        setDrawerText('empDrUpdated', e.updatedAt);
        setDrawerText('empDrNotes', e.hrNotes);
        var metaEl = document.getElementById('drawerIdMeta');
        if (metaEl) metaEl.innerHTML = '<span>Employee ID<strong>' + (e.employeeId || '-') + '</strong></span><span>Tanggal Masuk<strong>' + (e.joinDate || '-') + '</strong></span>';
      })
      .catch(function() { document.getElementById('drawerCandidateName').innerText = 'Gagal memuat data.'; });
    };

    window.openOutsourceDrawer = function(id) {
      setDrawerMode('outsource');
      document.getElementById('drawerCandidateName').innerText = 'Memuat...';
      openDrawer();

      fetch('/hr/employees/' + id + '/json', {
        headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'Accept': 'application/json' }
      })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        if (!data.success) { closeDrawer(); return; }
        var e = data.employee;
        document.getElementById('drawerCandidateName').innerText = e.fullName || '-';
        document.getElementById('drawerPosition').innerText = (e.jobPosition || '-') + (e.outsourceVendor ? ' · ' + e.outsourceVendor : '');
        document.getElementById('drawerAvatar').innerText = initials(e.fullName);
        setDrawerText('osDrEmployeeId', e.employeeId);
        setDrawerText('osDrNik', e.nikNpwp);
        setDrawerText('osDrFullName', e.fullName);
        setDrawerText('osDrBirthPlace', e.birthPlace);
        setDrawerText('osDrBirthDate', e.birthDate);
        setDrawerText('osDrGender', e.gender === 'Male' ? 'Laki-laki' : (e.gender === 'Female' ? 'Perempuan' : e.gender));
        setDrawerText('osDrMarital', e.maritalStatus);
        setDrawerText('osDrStatusEmployee', e.statusEmployee);
        setDrawerText('osDrEmail', e.personalEmail);
        setDrawerText('osDrPhone', e.mobilePhone);
        setDrawerText('osDrCity', e.lokasiKerja || e.areaKerja);
        setDrawerText('osDrAddress', e.citizenIdAddress);
        setDrawerText('osDrResidentialAddress', e.residentialAddress);
        setDrawerText('osDrBankName', e.bankName);
        setDrawerText('osDrBankAccount', e.bankAccount);
        setDrawerText('osDrBpjsTk', e.bpjsKetenagakerjaan);
        setDrawerText('osDrBpjsKes', e.bpjsKesehatan);
        setDrawerText('osDrVendor', e.outsourceVendor);
        setDrawerText('osDrBranch', e.branchName);
        setDrawerText('osDrDivision', e.division);
        setDrawerText('osDrDept', e.department);
        setDrawerText('osDrPosition', e.jobPosition);
        setDrawerText('osDrJobLevel', e.jobLevel);
        setDrawerText('osDrJoinDate', e.joinDate);
        setDrawerText('osDrDirectSup', e.directSuperior);
        setDrawerText('osDrIndirectSup', e.indirectSuperior);
        setDrawerText('osDrCreatedBy', e.createdBy);
        setDrawerText('osDrCreated', e.createdAt);
        setDrawerText('osDrUpdated', e.updatedAt);
        setDrawerText('osDrNotes', e.hrNotes);
        var metaEl = document.getElementById('drawerIdMeta');
        if (metaEl) metaEl.innerHTML = '<span>Employee ID<strong>' + (e.employeeId || '-') + '</strong></span><span>Tanggal Masuk<strong>' + (e.joinDate || '-') + '</strong></span>';
      })
      .catch(function() { document.getElementById('drawerCandidateName').innerText = 'Gagal memuat data.'; });
    };

    var notesAutoSaveTimer = null;
    function persistHrNotes() {
      if (!activeCandidateId) return;
      var notesEl = document.getElementById('cvHrNotes');
      var stateEl = document.getElementById('notesSaveState');
      var value = notesEl ? notesEl.value : '';
      fetch('/hr/recruitment/' + activeCandidateId + '/save-notes', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken(), 'Accept': 'application/json' },
        body: JSON.stringify({ notes: value })
      })
      .then(function(res) { return res.json(); })
      .then(function(result) {
        if (stateEl) {
          stateEl.innerText = result.success ? 'Tersimpan otomatis' : 'Gagal menyimpan';
          stateEl.className = 'notes-save-state' + (result.success ? ' saved' : '');
        }
      })
      .catch(function() {
        if (stateEl) { stateEl.innerText = 'Gagal menyimpan'; stateEl.className = 'notes-save-state'; }
      });
    }

    document.addEventListener('DOMContentLoaded', () => {
      drawerOverlay = document.getElementById('drawerOverlay');
      drawerPanel = document.getElementById('drawerPanel');
      if (drawerPanel) {
        document.getElementById('btnDrawerClose')?.addEventListener('click', closeDrawer);
        drawerOverlay?.addEventListener('click', function(e) {
          if (e.target === drawerOverlay) closeDrawer();
        });
      }

      var notesEl = document.getElementById('cvHrNotes');
      if (notesEl) {
        notesEl.addEventListener('input', function() {
          var charEl = document.getElementById('notesCharCounter');
          var stateEl = document.getElementById('notesSaveState');
          if (charEl) charEl.innerText = notesEl.value.length + ' / 2000 karakter';
          if (stateEl) { stateEl.innerText = 'Menyimpan...'; stateEl.className = 'notes-save-state saving'; }
          clearTimeout(notesAutoSaveTimer);
          notesAutoSaveTimer = setTimeout(persistHrNotes, 1200);
        });
      }
      var saveBtn = document.getElementById('btnSaveNotesManual');
      if (saveBtn) saveBtn.addEventListener('click', function() { clearTimeout(notesAutoSaveTimer); persistHrNotes(); });

      var printBtn = document.getElementById('btnDrawerPrint');
      if (printBtn) printBtn.addEventListener('click', function() {
        if (activeCandidateId) window.open('/hr/export/candidate-pdf/' + activeCandidateId, '_blank');
      });

      // Dark Mode Toggle
      const btnDark = document.getElementById('btnDarkModeToggle');
      if (btnDark) {
        const syncThemeIcon = (theme) => {
          const icon = btnDark.querySelector('i');
          if (icon) {
            icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
          }
        };

        const current = localStorage.getItem('mito_theme') || 'light';
        syncThemeIcon(current);

        btnDark.addEventListener('click', () => {
          const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
          const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
          document.documentElement.setAttribute('data-theme', newTheme);
          document.documentElement.setAttribute('data-bs-theme', newTheme);
          localStorage.setItem('mito_theme', newTheme);
          syncThemeIcon(newTheme);
        });
      }

      // Mobile Burger Menu
      const btnBurger = document.getElementById('btnBurger');
      const sidebar = document.querySelector('.sidebar');
      if (btnBurger && sidebar) {
        btnBurger.addEventListener('click', () => {
          sidebar.classList.toggle('show');
          drawerOverlay?.classList.toggle('show');
        });
        drawerOverlay?.addEventListener('click', () => {
          sidebar.classList.remove('show');
          drawerOverlay?.classList.remove('show');
        });
      }

      // FAB Toggle
      const fabMain = document.getElementById('fabMain');
      const fabActions = document.getElementById('fabActions');
      if (fabMain && fabActions) {
        fabMain.addEventListener('click', () => {
          fabMain.classList.toggle('open');
          fabActions.classList.toggle('d-none');
        });
      }

      // ================================================================
      // DRAWER ACTION BUTTONS — Accept / Hold / Blacklist (1:1 from GAS)
      // Wires the three footer buttons in candidate drawer mode to
      // their respective Bootstrap modals, pre-filling the form action
      // URL and candidate name preview before showing the modal.
      // ================================================================

      // Helper: inject candidate info banner into a modal body
      function injectCandidatePreviewBanner(modalId, candidateName, recruitmentId) {
        var modal = document.getElementById(modalId);
        if (!modal) return;
        // Remove any previously injected banner
        var existing = modal.querySelector('.drawer-cand-preview-banner');
        if (existing) existing.remove();

        var body = modal.querySelector('.modal-body');
        if (!body) return;
        var initStr = (candidateName || 'C').replace(/\s+/g, ' ').trim().split(' ')
          .map(function(w) { return w[0]; }).join('').substring(0, 2).toUpperCase();
        var banner = document.createElement('div');
        banner.className = 'drawer-cand-preview-banner d-flex align-items-center gap-3 p-3 rounded-3 mb-3';
        banner.style.cssText = 'background:#f0f7ff;border:1px solid #c7dff7;';
        banner.innerHTML =
          '<div style="width:40px;height:40px;border-radius:10px;background:var(--color-primary,#eb1c24);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;flex-shrink:0">' + initStr + '</div>' +
          '<div><div class="fw-bold text-navy" style="font-size:14px">' + (candidateName || '-') + '</div>' +
          '<div class="text-muted" style="font-size:11.5px">Recruitment ID: <strong>' + (recruitmentId || '-') + '</strong></div></div>';
        body.insertBefore(banner, body.firstChild);
      }

      // Helper: update status button highlight (same as GAS updateStatusButtonsUI)
      function updateStatusButtonsUI(currentStatus) {
        var map = { 'Accept': 'Accepted', 'Hold': 'Hold', 'Blacklist': 'Blacklist' };
        Object.keys(map).forEach(function(key) {
          var btn = document.getElementById('btn' + key);
          if (!btn) return;
          btn.classList.toggle('current', map[key].toLowerCase() === (currentStatus || '').toLowerCase());
        });
      }

      // btnAccept — opens #modalAccept, sets form action to /{id}/accept
      var btnAccept = document.getElementById('btnAccept');
      if (btnAccept) {
        btnAccept.addEventListener('click', function() {
          if (!activeCandidateId) return;
          var candName = document.getElementById('drawerCandidateName')?.innerText || '';
          var formAccept = document.getElementById('formAccept');
          formAccept.action = '/hr/recruitment/' + activeCandidateId + '/accept';
          // Reset to defaults so previous candidate data doesn't leak
          var joinDateEl = formAccept.querySelector('input[name="join_date"]');
          if (joinDateEl) joinDateEl.value = new Date().toISOString().substring(0, 10);
          var statusEl = formAccept.querySelector('select[name="status_employee"]');
          if (statusEl) statusEl.value = 'PKWT';
          injectCandidatePreviewBanner('modalAccept', candName, activeCandidateId);
          var modal = new bootstrap.Modal(document.getElementById('modalAccept'));
          modal.show();
        });
      }

      // btnHold — opens #modalHold, sets form action to /{id}/hold
      var btnHold = document.getElementById('btnHold');
      if (btnHold) {
        btnHold.addEventListener('click', function() {
          if (!activeCandidateId) return;
          var candName = document.getElementById('drawerCandidateName')?.innerText || '';
          document.getElementById('formHold').action = '/hr/recruitment/' + activeCandidateId + '/hold';
          // Reset textarea fields so previous candidate data doesn't leak
          var formHold = document.getElementById('formHold');
          formHold.querySelectorAll('textarea').forEach(function(el) { el.value = ''; });
          var fuDate = formHold.querySelector('input[name="hold_follow_up_date"]');
          if (fuDate) fuDate.value = '';
          injectCandidatePreviewBanner('modalHold', candName, activeCandidateId);
          var modal = new bootstrap.Modal(document.getElementById('modalHold'));
          modal.show();
        });
      }

      // btnBlacklist — opens #modalBlacklist, sets form action to /{id}/blacklist
      var btnBlacklist = document.getElementById('btnBlacklist');
      if (btnBlacklist) {
        btnBlacklist.addEventListener('click', function() {
          if (!activeCandidateId) return;
          var candName = document.getElementById('drawerCandidateName')?.innerText || '';
          document.getElementById('formBlacklist').action = '/hr/recruitment/' + activeCandidateId + '/blacklist';
          var formBl = document.getElementById('formBlacklist');
          formBl.querySelectorAll('textarea').forEach(function(el) { el.value = ''; });
          injectCandidatePreviewBanner('modalBlacklist', candName, activeCandidateId);
          var modal = new bootstrap.Modal(document.getElementById('modalBlacklist'));
          modal.show();
        });
      }

      // After modal form submission redirect, update status button highlight
      // by reading the current candidate status from the drawer meta
      ['formAccept', 'formHold', 'formBlacklist'].forEach(function(formId) {
        var form = document.getElementById(formId);
        if (form) {
          form.addEventListener('submit', function() {
            // Close drawer on submit so user sees the redirect result cleanly
            closeDrawer();
          });
        }
      });
    });
  </script>
  @yield('scripts')
</body>
</html>
