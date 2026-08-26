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
  @include('hr.partials.employee-form-modal')

  <!-- 1:1 Drawer (Candidate/Employee/Outsource Profile) -->
  @include('components.hr-drawer')

  <!-- Core Scripts for Burger, Theme & FAB -->
  <script>
    // Drawer Functions (1:1 from GAS js/drawer.html)
    var activeCandidateId = null;
    var _activeDrawerCandidate = null;
    var drawerOverlay = null;
    var drawerPanel = null;
    var drawerJustOpened = false;

    // Render badge status utama + badge respons offering di header drawer (1:1 GAS)
    function statusBadgeClass(status) {
      var s = (status || '').toLowerCase();
      if (s === 'accepted') return 'accepted';
      if (s === 'hold') return 'hold';
      if (s === 'blacklist') return 'blacklist';
      return 'pending';
    }
    function escapeHtmlDrawer(str) {
      return String(str == null ? '' : str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
    function renderDrawerCandidateBadge(statusLabel, offeringResponse) {
      var wrap = document.getElementById('drawerStatusBadgeWrap');
      if (!wrap) return;
      var mainBadge = '<span class="badge-status lg ' + statusBadgeClass(statusLabel) + '">' +
        escapeHtmlDrawer(statusLabel) + '</span>';
      var respBadge = '';
      if (offeringResponse) {
        var respClass = 'hold', respIcon = 'bi-hourglass-split';
        if (offeringResponse === 'Diterima') { respClass = 'accepted'; respIcon = 'bi-check-circle-fill'; }
        if (offeringResponse === 'Ditolak')  { respClass = 'blacklist'; respIcon = 'bi-x-circle-fill'; }
        respBadge = ' <span class="badge-status lg ' + respClass + '" style="font-size:10px">' +
          '<i class="bi ' + respIcon + ' me-1"></i>' + escapeHtmlDrawer(offeringResponse) + '</span>';
      }
      wrap.innerHTML = mainBadge + respBadge;
    }

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

      // Reset badge & tombol khusus kandidat saat pindah mode (hindari state basi)
      var badgeWrap = document.getElementById('drawerStatusBadgeWrap');
      if (badgeWrap && mode !== 'candidate') badgeWrap.innerHTML = '';
      var respWrap = document.getElementById('drawerOfferingRespWrap');
      if (respWrap && mode !== 'candidate') respWrap.style.display = 'none';
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

        // Badge status utama + badge offering response (1:1 GAS renderCandidateDetail)
        var isAccepted = (currentStatus || '').toLowerCase() === 'accepted';
        var hasOffering = c.offeringCreated && c.offeringCreated !== '-' && c.offeringCreated !== '';
        renderDrawerCandidateBadge(currentStatus, isAccepted ? (c.offeringResponse || '') : '');

        // Tombol Update Respons Offering — hanya untuk Accepted + sudah ada offering letter (1:1 GAS)
        var respWrap = document.getElementById('drawerOfferingRespWrap');
        var respBtn = document.getElementById('btnUpdateOfferResp');
        if (respWrap) respWrap.style.display = (isAccepted && hasOffering) ? 'block' : 'none';
        if (respBtn) respBtn.onclick = function() { window.openOfferingResponseModal(c); };
        // Helper live-update badge respons dipakai oleh modal respons offering
        window.renderOfferingRespBadge = function(response) {
          if (_activeDrawerCandidate) _activeDrawerCandidate.offeringResponse = response;
          renderDrawerCandidateBadge(currentStatus, response);
        };
        _activeDrawerCandidate = c;

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

        // Simpan data employee aktif untuk keperluan Edit & Probation
        window._activeDrawerEmployee = e;

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
        setDrawerText('empDrPosition', e.jobPositionLocation || e.jobPosition);
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

        // Tombol Edit di footer — wire ke empOpenEdit()
        var btnEdit = document.getElementById('btnDrawerEntityEdit');
        if (btnEdit) btnEdit.onclick = function() { empOpenEdit(e); };

        // Tombol Edit di header drawer
        var btnEditHeader = document.getElementById('btnDrawerEdit');
        if (btnEditHeader) btnEditHeader.onclick = function() { empOpenEdit(e); };

        // Contract actions — tampilkan tombol "Ajukan Onboarding Probation" hanya untuk Contract
        var contractWrap = document.getElementById('drawerContractActionsWrap');
        var statusLower = (e.statusEmployee || '').toLowerCase();
        var isContract = statusLower === 'contract' || statusLower === 'pkwt' || statusLower.indexOf('contract') !== -1;
        if (contractWrap) {
          contractWrap.style.display = isContract ? 'block' : 'none';
          var btnPromote = document.getElementById('btnDrawerPromoteProbation');
          if (btnPromote) {
            btnPromote.onclick = function() { openPromoteToProbationModal(e); };
          }
        }
      })
      .catch(function() { document.getElementById('drawerCandidateName').innerText = 'Gagal memuat data.'; });
    };

    // ===========================================================
    // EDIT EMPLOYEE — populate modal dari data drawer aktif
    // 1:1 dengan GAS empOpenEdit() di js/employee.html
    // ===========================================================
    function empOpenEdit(emp) {
      if (!emp) emp = window._activeDrawerEmployee;
      if (!emp) return;

      // Update judul modal
      var titleEl = document.getElementById('empFormTitle');
      if (titleEl) titleEl.textContent = 'Edit Karyawan';

      // Simpan ID aktif untuk submit
      var efId = document.getElementById('efId');
      if (efId) efId.value = emp.employeeId || '';

      // Banner preview
      var banner = document.getElementById('efPreviewBanner');
      var bannerAv = document.getElementById('efBannerAvatar');
      var bannerNm = document.getElementById('efBannerName');
      var bannerMt = document.getElementById('efBannerMeta');
      if (banner) banner.style.display = 'flex';
      if (bannerAv) bannerAv.innerText = initials(emp.fullName || 'K');
      if (bannerNm) bannerNm.innerText = emp.fullName || '-';
      if (bannerMt) bannerMt.innerText = (emp.jobPosition || emp.jobPositionLocation || '-') + (emp.department ? ' · ' + emp.department : '') + (emp.employeeId ? ' · ' + emp.employeeId : '');

      // Helper: set value pada input/select
      function setVal(id, val) {
        var el = document.getElementById(id);
        if (!el) return;
        el.value = (val === null || val === undefined) ? '' : String(val).replace(/^'/, '');
      }

      // Helper: set value pada date input (handle berbagai format tanggal)
      function setDate(id, val) {
        var el = document.getElementById(id);
        if (!el) return;
        if (!val) { el.value = ''; return; }
        var s = String(val).trim();
        // Jika sudah format YYYY-MM-DD
        if (/^\d{4}-\d{2}-\d{2}/.test(s)) { el.value = s.substring(0, 10); return; }
        // Coba parse DD/MM/YYYY
        var m = s.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})/);
        if (m) { el.value = m[3] + '-' + m[2].padStart(2, '0') + '-' + m[1].padStart(2, '0'); return; }
        // Fallback
        var d = new Date(s);
        el.value = isNaN(d.getTime()) ? '' : d.toISOString().substring(0, 10);
      }

      // Seksi 1: Identitas
      setVal('efName', emp.fullName);
      setVal('efNik', emp.nikNpwp);
      setVal('efNpwp', emp.npwp);
      setVal('efBirthPlace', emp.birthPlace);
      setDate('efBirthDate', emp.birthDate);
      setVal('efBloodType', emp.bloodType);
      setVal('efGender', emp.gender);
      setVal('efReligion', emp.religion);
      setVal('efMarital', emp.maritalStatus);
      setVal('efPtkp', emp.ptkpStatus);
      setVal('efEmail', emp.personalEmail);
      setVal('efPhone', emp.mobilePhone);
      setVal('efAddress', emp.citizenIdAddress);
      setVal('efResidentialAddress', emp.residentialAddress);

      // Seksi 2: Bank & BPJS
      setVal('efWorkingEmail', emp.workingEmail);
      setVal('efBankAccount', emp.bankAccount);
      setVal('efBankHolder', emp.bankAccountHolder || emp.fullName);
      setVal('efBpjsTk', emp.bpjsKetenagakerjaan);
      setVal('efBpjsKes', emp.bpjsKesehatan);

      // Seksi 3: Organisasi & Pekerjaan
      setVal('efStatusEmployee', emp.statusEmployee || 'Contract');
      setVal('efBranch', emp.branchName);
      setVal('efDivision', emp.division);
      setVal('efDept', emp.department);
      setVal('efCostCenter', emp.costCenter);
      setVal('efPos', emp.jobPositionLocation || emp.jobPosition);
      setVal('efPosNoLoc', emp.jobPosition);
      setVal('efJobLevel', emp.jobLevel);
      setVal('efGrade', emp.grade);
      setVal('efDistrict', emp.areaKerja);
      setVal('efCity', emp.lokasiKerja);
      setDate('efJoin', emp.joinDate);
      setVal('efDirectSup', emp.directSuperior);
      setVal('efIndirectSup', emp.indirectSuperior);
      setVal('efOutsourceVendor', emp.outsourceVendor);

      // Seksi 4: Kontrak
      setDate('efContractStart', emp.contractStart || emp.startDateContract);
      setDate('efContractEnd', emp.endDateContract);
      setVal('efContractDuration', emp.contractDuration);
      setVal('efContractNumber', emp.contractNumber);

      // Seksi 5: Mutasi
      var hasMutasi = !!(emp.jobPositionFormer || emp.typeOfRotation);
      var efInfo = document.getElementById('efNoMutasiInfo');
      if (efInfo) efInfo.style.display = hasMutasi ? 'none' : 'flex';
      setVal('efFormerPos', emp.jobPositionFormer);
      setVal('efRotationType', emp.typeOfRotation);
      setDate('efMutasiDate', emp.rotationDate);
      setVal('efNoSk', emp.nomorSk);
      setDate('efResignDate', emp.resignDate);

      // Tampilkan resign date hanya jika status offboard
      var resignWrap = document.getElementById('efResignDateWrap');
      if (resignWrap) {
        var s = (emp.statusEmployee || '').toLowerCase();
        resignWrap.style.display = (s === 'resigned' || s === 'terminated' || s === 'retired') ? '' : 'none';
      }

      // Seksi 6: Catatan
      setVal('efHrNotes', emp.hrNotes);
      setVal('efNotes', emp.notes);

      // Tampilkan modal
      var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('empFormModal'));
      modal.show();
    }

    // Submit edit employee via Fetch API — PUT /hr/employees/{id}
    function empSave() {
      var empId = (document.getElementById('efId') || {}).value || '';
      var name  = ((document.getElementById('efName') || {}).value || '').trim();
      if (!empId) return;
      if (!name) {
        showToast('Nama lengkap harus diisi.', 'warning');
        return;
      }

      var payload = {
        fullName:            name,
        nikNpwp:             (document.getElementById('efNik') || {}).value || '',
        npwp:                (document.getElementById('efNpwp') || {}).value || '',
        birthPlace:          (document.getElementById('efBirthPlace') || {}).value || '',
        birthDate:           (document.getElementById('efBirthDate') || {}).value || '',
        bloodType:           (document.getElementById('efBloodType') || {}).value || '',
        gender:              (document.getElementById('efGender') || {}).value || '',
        religion:            (document.getElementById('efReligion') || {}).value || '',
        maritalStatus:       (document.getElementById('efMarital') || {}).value || '',
        ptkpStatus:          (document.getElementById('efPtkp') || {}).value || '',
        personalEmail:       (document.getElementById('efEmail') || {}).value || '',
        mobilePhone:         (document.getElementById('efPhone') || {}).value || '',
        citizenIdAddress:    (document.getElementById('efAddress') || {}).value || '',
        residentialAddress:  (document.getElementById('efResidentialAddress') || {}).value || '',
        workingEmail:        (document.getElementById('efWorkingEmail') || {}).value || '',
        bankAccount:         (document.getElementById('efBankAccount') || {}).value || '',
        bankAccountHolder:   (document.getElementById('efBankHolder') || {}).value || '',
        bpjsKetenagakerjaan: (document.getElementById('efBpjsTk') || {}).value || '',
        bpjsKesehatan:       (document.getElementById('efBpjsKes') || {}).value || '',
        statusEmployee:      (document.getElementById('efStatusEmployee') || {}).value || '',
        branchName:          (document.getElementById('efBranch') || {}).value || '',
        division:            (document.getElementById('efDivision') || {}).value || '',
        department:          (document.getElementById('efDept') || {}).value || '',
        costCenter:          (document.getElementById('efCostCenter') || {}).value || '',
        jobPositionLocation: (document.getElementById('efPos') || {}).value || '',
        jobPosition:         (document.getElementById('efPosNoLoc') || {}).value || '',
        jobLevel:            (document.getElementById('efJobLevel') || {}).value || '',
        grade:               (document.getElementById('efGrade') || {}).value || '',
        areaKerja:           (document.getElementById('efDistrict') || {}).value || '',
        lokasiKerja:         (document.getElementById('efCity') || {}).value || '',
        joinDate:            (document.getElementById('efJoin') || {}).value || '',
        directSuperior:      (document.getElementById('efDirectSup') || {}).value || '',
        indirectSuperior:    (document.getElementById('efIndirectSup') || {}).value || '',
        outsourceVendor:     (document.getElementById('efOutsourceVendor') || {}).value || '',
        contractStart:       (document.getElementById('efContractStart') || {}).value || '',
        endDateContract:     (document.getElementById('efContractEnd') || {}).value || '',
        contractDuration:    (document.getElementById('efContractDuration') || {}).value || '',
        contractNumber:      (document.getElementById('efContractNumber') || {}).value || '',
        jobPositionFormer:   (document.getElementById('efFormerPos') || {}).value || '',
        typeOfRotation:      (document.getElementById('efRotationType') || {}).value || '',
        rotationDate:        (document.getElementById('efMutasiDate') || {}).value || '',
        nomorSk:             (document.getElementById('efNoSk') || {}).value || '',
        resignDate:          (document.getElementById('efResignDate') || {}).value || '',
        hrNotes:             (document.getElementById('efHrNotes') || {}).value || '',
        _method: 'PUT',
      };

      var btn = document.getElementById('btnEmpSave');
      if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...'; }

      fetch('/hr/employees/' + empId, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      })
      .then(function(res) {
        if (!res.ok) return res.json().then(function(d) { throw d; });
        return res.json();
      })
      .then(function(result) {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Simpan'; }
        if (result.success) {
          // Tutup modal
          var modal = bootstrap.Modal.getInstance(document.getElementById('empFormModal'));
          if (modal) modal.hide();
          // Refresh drawer dengan data terbaru dari response
          if (result.employee) {
            window._activeDrawerEmployee = result.employee;
            window.openEmployeeDrawer(empId);
          }
          showToast(result.message || 'Data karyawan berhasil diperbarui.', 'success');
        } else {
          showToast(result.message || 'Gagal menyimpan data.', 'error');
        }
      })
      .catch(function(err) {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Simpan'; }
        var msg = (err && err.message) ? err.message : 'Terjadi kesalahan. Coba lagi.';
        showToast(msg, 'error');
      });
    }

    // ===========================================================
    // PROMOTE TO PROBATION — submit via Fetch API
    // POST /hr/employees/{id}/promote-probation
    // 1:1 dengan GAS promoteEmployeeToProbation()
    // ===========================================================
    function openPromoteToProbationModal(emp) {
      if (!emp) return;
      window._promoteProbTargetEmp = emp;

      var empIdEl  = document.getElementById('promoteProbEmpId');
      var nameEl   = document.getElementById('promoteProbEmpName');
      var posEl    = document.getElementById('promoteProbPosition');
      var avEl     = document.getElementById('promoteProbAvatar');
      var badgeEl  = document.getElementById('promoteProbBadge');
      var startEl  = document.getElementById('promoteProbStart');
      var durEl    = document.getElementById('promoteProbDuration');
      var notesEl  = document.getElementById('promoteProbNotes');

      if (empIdEl) empIdEl.value    = emp.employeeId || '';
      if (nameEl)  nameEl.innerText = emp.fullName || '-';
      if (posEl)   posEl.innerText  = (emp.jobPositionLocation || emp.jobPosition || '-') + (emp.department ? ' · ' + emp.department : '');
      if (avEl)    avEl.innerText   = initials(emp.fullName || 'K');
      if (badgeEl) badgeEl.innerText = emp.statusEmployee || 'Contract';
      if (startEl) startEl.value   = new Date().toISOString().substring(0, 10);
      if (durEl)   durEl.value     = '3 Bulan';
      if (notesEl) notesEl.value   = '';

      // Override form action agar submit tidak trigger default POST
      var formEl = document.getElementById('formPromoteProbation');
      if (formEl) {
        formEl.onsubmit = function(e) {
          e.preventDefault();
          submitPromoteProbation();
          return false;
        };
      }

      var modalEl = document.getElementById('promoteToProbationModal');
      if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    function submitPromoteProbation() {
      var emp   = window._promoteProbTargetEmp;
      var empId = (document.getElementById('promoteProbEmpId') || {}).value || '';
      if (!empId) return;

      var probStart = (document.getElementById('promoteProbStart') || {}).value || '';
      var probDur   = (document.getElementById('promoteProbDuration') || {}).value || '3 Bulan';
      var notes     = (document.getElementById('promoteProbNotes') || {}).value || '';

      if (!probStart) {
        showToast('Tanggal mulai probation wajib diisi.', 'warning');
        return;
      }

      // Hitung probation end dari start + durasi
      var probEnd = '';
      try {
        var months = parseInt((probDur.match(/^(\d+)/) || [0, 3])[1], 10) || 3;
        var d = new Date(probStart);
        d.setMonth(d.getMonth() + months);
        d.setDate(d.getDate() - 1);
        probEnd = d.toISOString().substring(0, 10);
      } catch (e) {}

      var payload = {
        probation_start:    probStart,
        probation_duration: probDur,
        probation_end:      probEnd,
        notes:              notes,
      };

      var submitBtn = document.querySelector('#promoteToProbationModal button[type="submit"]');
      if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...'; }

      fetch('/hr/employees/' + empId + '/promote-probation', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      })
      .then(function(res) {
        if (!res.ok) return res.json().then(function(d) { throw d; });
        return res.json();
      })
      .then(function(result) {
        if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="bi bi-person-check-fill me-1"></i>Daftarkan ke Probation'; }

        var modalEl = document.getElementById('promoteToProbationModal');
        if (modalEl) { var m = bootstrap.Modal.getInstance(modalEl); if (m) m.hide(); }

        if (result.success) {
          showToast(result.message || 'Karyawan berhasil didaftarkan ke Onboarding Probation!', 'success', 5000);
          closeDrawer();
          // Reload halaman agar tabel ter-refresh
          window.location.reload();
        } else {
          showToast('Gagal: ' + (result.message || 'Error tidak diketahui'), 'error');
        }
      })
      .catch(function(err) {
        if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="bi bi-person-check-fill me-1"></i>Daftarkan ke Probation'; }
        showToast((err && err.message) ? err.message : 'Terjadi kesalahan. Coba lagi.', 'error');
      });
    }

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

        // Simpan data outsource aktif untuk keperluan Edit
        window._activeDrawerOutsource = e;

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
        setDrawerText('osDrPosition', e.jobPositionLocation || e.jobPosition);
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

        // Tombol Edit di footer — reuse modal edit employee karena outsource disimpan di sheet yang sama
        var btnEdit = document.getElementById('btnDrawerEntityEdit');
        if (btnEdit) btnEdit.onclick = function() { empOpenEdit(e); };

        // Tombol Edit di header
        var btnEditHeader = document.getElementById('btnDrawerEdit');
        if (btnEditHeader) btnEditHeader.onclick = function() { empOpenEdit(e); };
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

      // ── btnEmpSave — tombol simpan pada modal edit employee/outsource ──
      var btnEmpSave = document.getElementById('btnEmpSave');
      if (btnEmpSave) btnEmpSave.addEventListener('click', empSave);

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

      // Mobile Burger Menu & Sidebar Overlay
      const btnBurger = document.getElementById('btnBurger');
      const sidebar = document.querySelector('.sidebar');
      const sidebarOverlay = document.getElementById('sidebarOverlay');
      const btnSidebarClose = document.getElementById('btnSidebarClose');

      function closeSidebar() {
        if (sidebar) sidebar.classList.remove('show');
        if (sidebarOverlay) sidebarOverlay.classList.remove('show');
        document.body.classList.remove('sidebar-open');
      }

      function openSidebar() {
        if (sidebar) sidebar.classList.add('show');
        if (sidebarOverlay) sidebarOverlay.classList.add('show');
        document.body.classList.add('sidebar-open');
      }

      if (btnBurger && sidebar) {
        btnBurger.addEventListener('click', () => {
          if (sidebar.classList.contains('show')) {
            closeSidebar();
          } else {
            openSidebar();
          }
        });
        if (sidebarOverlay) {
          sidebarOverlay.addEventListener('click', closeSidebar);
        }
        if (btnSidebarClose) {
          btnSidebarClose.addEventListener('click', closeSidebar);
        }
      }

      // Close sidebar & drawer on Escape key
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          if (sidebar && sidebar.classList.contains('show')) {
            closeSidebar();
          }
          if (drawerPanel && drawerPanel.classList.contains('show')) {
            closeDrawer();
          }
        }
      });

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
