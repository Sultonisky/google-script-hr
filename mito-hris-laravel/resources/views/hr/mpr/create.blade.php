@extends('layouts.hr')

@section('title', 'Formulir Pengajuan Manpower Request (MPR) - MITO HRIS')
@section('page-title', 'Formulir Pengajuan Manpower Request')
@section('page-subtitle', 'Isi kebutuhan tenaga kerja untuk diproses HR')

@section('content')
    <div class="container-fluid px-0">
        <div class="mpr-form-shell mx-auto">
            <div id="mprSuccessAlert" class="alert alert-success alert-dismissible fade d-none shadow-sm" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill fs-4 me-3 text-success"></i>
                    <div>
                        <h6 class="alert-heading mb-1 fw-bold" id="mprSuccessTitle">Pengajuan MPR Berhasil Dibuat!</h6>
                        <p class="mb-0" id="mprSuccessBody">Dokumen PDF resmi telah digenerate secara otomatis.</p>
                    </div>
                    <div class="ms-auto d-flex gap-2">
                        <a id="btnAlertOpenPdf" href="#" target="_blank" class="btn btn-sm btn-success fw-semibold">
                            <i class="bi bi-file-earmark-pdf-fill me-1"></i> Buka PDF MPR
                        </a>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill fs-4 me-3 text-success"></i>
                        <div>
                            <h6 class="alert-heading mb-1 fw-bold">Berhasil</h6>
                            <p class="mb-0">{{ session('success') }}</p>
                        </div>
                        @if (session('mpr_pdf_url'))
                            <div class="ms-auto">
                                <a href="{{ session('mpr_pdf_url') }}" target="_blank"
                                    class="btn btn-sm btn-success fw-semibold">
                                    <i class="bi bi-file-earmark-pdf-fill me-1"></i> Unduh PDF
                                </a>
                            </div>
                        @endif
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div
                    class="mpr-section-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                    <div>
                        <h5 class="mb-0"><i class="bi bi-file-earmark-plus-fill me-2"></i>Formulir Pengajuan Manpower Request</h5>
                    </div>
                    <span class="badge bg-white text-primary fw-bold mpr-badge">Role: Manpower</span>
                </div>
                <div class="card-body p-0">
                    <div class="mpr-form-body">
                        <form id="formManagerMpr"
                            action="{{ request()->routeIs('mpr.auth.*') ? route('mpr.auth.request.store') : route('hr.mpr.store') }}"
                            method="POST">
                            @csrf

                            @php
                                $mprOptions = config('hris.mpr_form_options', []);
                                $oldWorkingDays = (array) old('working_days', []);
                                $oldWorkingHours = (array) old('working_hours', []);
                                $oldBenefits = (array) old('benefits', []);
                            @endphp

                            {{-- ===================== IDENTITAS PEMOHON ===================== --}}
                            <div class="mpr-identity-box mb-4">
                                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                                    <span class="text-primary fw-bold small"><i
                                            class="bi bi-shield-lock-fill me-1"></i>Identitas Pemohon (Terverifikasi
                                        Sistem)</span>
                                    <span class="badge bg-secondary-subtle text-secondary border mpr-badge"><i
                                            class="bi bi-lock-fill me-1"></i> Terkunci Permanen</span>
                                </div>
                                @php
                                    // Identity resolution — strict portal isolation.
                                    // DomainMiddleware sets request attribute 'portal' = 'hris' | 'mpr'.
                                    // On the MPR portal (/mpr/*): identity comes from mpr_requestor_auth ONLY.
                                    // On the HRIS portal (/hr/*): identity comes from $user (hr_user via View
                                    // composer) ONLY. We never read one portal's session for the other.
                                    if (request()->attributes->get('portal') === 'mpr') {
                                        $mprIdentKey      = config('mpr.session_key', 'mpr_requestor_auth');
                                        $mprIdentSess     = session($mprIdentKey, []);
                                        $identityName     = $mprIdentSess['fullName'] ?? 'Manpower';
                                        $identityPosition = $mprIdentSess['jobPosition'] ?? '';
                                    } else {
                                        // HRIS portal — $user is set by the View composer from hr_user.
                                        $identityName     = $user['fullName'] ?? $user['name'] ?? 'Manpower';
                                        $identityPosition = $user['jobPosition'] ?? '';
                                    }
                                @endphp
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small fw-semibold mb-1">Nama Pemohon</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                                            <input type="text" class="form-control"
                                                value="{{ $identityName }}" readonly
                                                disabled>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small fw-semibold mb-1">Jabatan Pemohon</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="bi bi-briefcase-fill"></i></span>
                                            <input type="text" class="form-control"
                                                value="{{ $identityPosition ?: '-' }}" readonly disabled>
                                        </div>
                                        <input type="hidden" name="requestor_position"
                                            value="{{ $identityPosition }}">
                                    </div>
                                </div>
                                <div class="mt-3 text-muted small">
                                    <i class="bi bi-shield-check-fill text-success me-1"></i>
                                    Identitas dan jabatan pemohon diambil otomatis dari sesi akun login Anda.
                                </div>
                            </div>



                            {{-- ===================== DETAIL POSISI ===================== --}}
                            <div class="mb-4">
                                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-briefcase-fill me-2"></i>Detail
                                    Posisi yang Dibutuhkan</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Entitas / Perusahaan yang dituju <span
                                                class="text-danger">*</span></label>
                                        <select name="entity" class="form-select" required>
                                            <option value="">-- Pilih Entitas --</option>
                                            @foreach ($entityOptions as $code => $label)
                                                <option value="{{ $code }}"
                                                    {{ old('entity') === $code ? 'selected' : '' }}>
                                                    {{ $code }} - {{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Posisi / Nama Jabatan yang Diminta <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="position" class="form-control"
                                            value="{{ old('position') }}"
                                            placeholder="Contoh: Frontend Developer, Sales Executive" required
                                            maxlength="255" data-sanitize-position="true">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Departemen <span class="text-danger">*</span></label>
                                        <select name="department" class="form-select" required>
                                            <option value="">-- Pilih Departemen --</option>
                                            @foreach ($departments as $d)
                                                <option value="{{ $d }}"
                                                    {{ old('department') === $d ? 'selected' : '' }}>{{ $d }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Divisi <span class="text-danger">*</span></label>
                                        <select name="division" class="form-select"
                                            data-selected-division="{{ old('division') }}" required>
                                            <option value="">-- Pilih Departemen terlebih dahulu --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Disetujui oleh (Divisi) <span class="text-danger">*</span></label>
                                        <select name="approval_division" class="form-select" required>
                                            <option value="">-- Pilih Divisi --</option>
                                            @foreach (config('hris.mpr.approval_divisions', []) as $division)
                                                <option value="{{ $division }}" {{ old('approval_division') === $division ? 'selected' : '' }}>
                                                    {{ $division }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Level Jabatan <span class="text-danger">*</span></label>
                                        <select name="job_level" class="form-select" required>
                                            <option value="">-- Pilih Level Jabatan --</option>
                                            @foreach ($jobLevels as $jl)
                                                <option value="{{ $jl }}"
                                                    {{ old('job_level') === $jl ? 'selected' : '' }}>
                                                    {{ $jl }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Lokasi Kerja Penempatan <span
                                                class="text-danger">*</span></label>
                                        <select name="work_location" class="form-select" required>
                                            <option value="">-- Pilih Lokasi Kerja --</option>
                                            @foreach ($workLocations as $wl)
                                                <option value="{{ $wl }}"
                                                    {{ old('work_location') === $wl ? 'selected' : '' }}>
                                                    {{ $wl }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Status Kepegawaian <span
                                                class="text-danger">*</span></label>
                                        <select name="employment_type" class="form-select" required>
                                            <option value="">-- Pilih Status Kepegawaian --</option>
                                            @foreach ($employmentTypes as $et)
                                                <option value="{{ $et }}"
                                                    {{ old('employment_type') === $et ? 'selected' : '' }}>
                                                    {{ $et }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Jumlah Kebutuhan (Orang) <span
                                                class="text-danger">*</span></label>
                                        <input type="number" name="quantity" class="form-control"
                                            value="{{ old('quantity', 1) }}" min="1" max="100" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Target Tanggal Bergabung (Join Date) <span
                                                class="text-danger">*</span></label>
                                        <input type="date" name="expected_join_date" class="form-control"
                                            value="{{ old('expected_join_date') }}" min="{{ date('Y-m-d') }}" required>
                                    </div>
                                </div>
                            </div>

                            {{-- ===================== JADWAL KERJA & FASILITAS ===================== --}}
                            <div class="mb-4">
                                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-clock-fill me-2"></i>Jadwal
                                    Kerja
                                    &amp; Fasilitas</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Hari Kerja <span class="text-danger">*</span></label>
                                        <div class="border rounded p-2 d-flex flex-column gap-1">
                                            @foreach ($mprOptions['working_days'] ?? [] as $dayKey => $dayLabel)
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="working_days[]"
                                                        value="{{ $dayKey }}" id="wd_{{ $dayKey }}"
                                                        {{ in_array($dayKey, $oldWorkingDays) ? 'checked' : '' }}>
                                                    <label class="form-check-label"
                                                        for="wd_{{ $dayKey }}">{{ $dayLabel }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Jam Kerja <span class="text-danger">*</span></label>
                                        <div class="border rounded p-2 d-flex flex-column gap-1">
                                            @foreach ($mprOptions['working_hours'] ?? [] as $hourKey => $hourLabel)
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio"
                                                        name="working_hours[]" value="{{ $hourKey }}"
                                                        id="wh_{{ $hourKey }}"
                                                        {{ in_array($hourKey, $oldWorkingHours) ? 'checked' : '' }}>
                                                    <label class="form-check-label"
                                                        for="wh_{{ $hourKey }}">{{ $hourLabel }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" id="shiftDetailLabel">
                                            Detail Shift
                                            <span class="text-danger d-none" id="shiftDetailRequiredMark">*</span>
                                            <span class="text-muted fw-normal small" id="shiftDetailHint">(aktif &amp; wajib jika "Shifting" dipilih)</span>
                                        </label>
                                        <textarea name="shift_detail" class="form-control" rows="2"
                                            id="shiftDetailField"
                                            placeholder="Contoh: Shift pagi 07:00-15:00, shift siang 15:00-23:00, rotasi mingguan..."
                                            disabled>{{ old('shift_detail') }}</textarea>
                                        <div class="form-text text-muted small mt-1" id="shiftDetailNote">
                                            <i class="bi bi-info-circle me-1"></i>Pilih "Shifting" pada Hari Kerja untuk mengisi detail shift.
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Benefits / Tunjangan <span
                                                class="text-danger">*</span></label>
                                        <div class="border rounded p-2 row g-1">
                                            @foreach ($mprOptions['benefits'] ?? [] as $benefitKey => $benefitLabel)
                                                <div class="col-md-4 col-6">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="benefits[]"
                                                            value="{{ $benefitKey }}" id="bn_{{ $benefitKey }}"
                                                            {{ in_array($benefitKey, $oldBenefits) ? 'checked' : '' }}>
                                                        <label class="form-check-label"
                                                            for="bn_{{ $benefitKey }}">{{ $benefitLabel }}</label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                            </div>{{-- end Jadwal Kerja & Fasilitas --}}

                            {{-- ===================== ALASAN & KUALIFIKASI ===================== --}}
                            <div class="mb-4">
                                <h6 class="fw-bold text-primary mb-3"><i
                                        class="bi bi-question-circle-fill me-2"></i>Alasan &amp; Kualifikasi</h6>
                                <div class="row g-3">

                                    {{-- Baris 1: Alasan + Penggantian --}}
                                    <div class="col-md-6">
                                        <label class="form-label">Alasan Permintaan <span
                                                class="text-danger">*</span></label>
                                        <select name="reason" class="form-select" id="mprReasonSelect" required>
                                            <option value="">-- Pilih Alasan Permintaan --</option>
                                            @foreach ($reasons as $r)
                                                <option value="{{ $r }}"
                                                    {{ old('reason') === $r ? 'selected' : '' }}>
                                                    {{ $r }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" id="replacementForLabel">
                                            Nama Karyawan yang Digantikan
                                            <span class="text-danger d-none" id="replacementForRequiredMark">*</span>
                                            <span class="text-muted fw-normal small" id="replacementForHint">(aktif jika alasan "Penggantian Karyawan")</span>
                                        </label>
                                        <input type="text" name="replacement_for" class="form-control"
                                            id="replacementForField"
                                            value="{{ old('replacement_for') }}"
                                            placeholder="Nama karyawan yang akan digantikan"
                                            disabled>
                                        <div class="form-text text-muted small mt-1" id="replacementForNote">
                                            <i class="bi bi-info-circle me-1"></i>Pilih alasan "Penggantian Karyawan Resign / Mutasi / Demosi" untuk mengisi field ini.
                                        </div>
                                    </div>

                                    {{-- Baris 2: Pendidikan + Pengalaman --}}
                                    <div class="col-md-6">
                                        <label class="form-label">Latar Belakang Pendidikan <span
                                                class="text-danger">*</span></label>
                                        <select name="education_background" class="form-select" required>
                                            <option value="">-- Pilih Pendidikan --</option>
                                            @foreach ($mprOptions['education_background'] ?? [] as $eduKey => $eduLabel)
                                                <option value="{{ $eduKey }}"
                                                    {{ old('education_background') === $eduKey ? 'selected' : '' }}>
                                                    {{ $eduLabel }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Pengalaman Kerja <span
                                                class="text-danger">*</span></label>
                                        <select name="work_experience" class="form-select" required>
                                            <option value="">-- Pilih Pengalaman --</option>
                                            @foreach ($mprOptions['work_experience'] ?? [] as $expKey => $expLabel)
                                                <option value="{{ $expKey }}"
                                                    {{ old('work_experience') === $expKey ? 'selected' : '' }}>
                                                    {{ $expLabel }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Baris 3: Skills + Bahasa --}}
                                    <div class="col-md-6">
                                        <label class="form-label">Skills &amp; Kompetensi</label>
                                        <textarea name="skills_competencies" class="form-control" rows="3"
                                            placeholder="Contoh: Laravel, Excel lanjutan, leadership...">{{ old('skills_competencies') }}</textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Bahasa yang Dikuasai</label>
                                        <textarea name="languages" class="form-control" rows="3"
                                            placeholder="Contoh: Bahasa Indonesia (aktif), Bahasa Inggris (pasif)..."
                                            maxlength="1000" data-sanitize-languages="true"
                                            autocomplete="off">{{ old('languages') }}</textarea>
                                        <div class="form-text text-muted small">Hanya huruf, spasi, koma, titik, dan kurung.</div>
                                    </div>

                                    {{-- Baris 4: Referensi Industri (full width) --}}
                                    <div class="col-12">
                                        <label class="form-label">Referensi Industri Sejenis</label>
                                        <textarea name="industry_reference" class="form-control" rows="2"
                                            placeholder="Contoh: Pengalaman dari industri mining, logistik, atau FMCG...">{{ old('industry_reference') }}</textarea>
                                    </div>

                                    {{-- Baris 5: Kualifikasi (full width) --}}
                                    <div class="col-12">
                                        <label class="form-label">Kualifikasi &amp; Persyaratan Khusus Kandidat</label>
                                        <textarea name="requirements" class="form-control" rows="3"
                                            placeholder="Contoh: Pendidikan min. S1 Informatika, pengalaman min. 2 tahun di Laravel, komunikasi baik...">{{ old('requirements') }}</textarea>
                                    </div>

                                    {{-- Baris 6: Uraian Tugas + Key Results (2 kolom) --}}
                                    <div class="col-md-6">
                                        <label class="form-label">Uraian Tugas &amp; Tanggung Jawab Utama</label>
                                        <textarea name="job_description" class="form-control" rows="4"
                                            placeholder="Contoh: Mengembangkan fitur web HRIS, melakukan code review, memastikan performa database...">{{ old('job_description') }}</textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Key Results / Target Posisi Ini</label>
                                        <textarea name="key_results_targets" class="form-control" rows="4"
                                            placeholder="Contoh: Mencapai target penjualan 100 unit/bulan, menyelesaikan integrasi ERP dalam 6 bulan...">{{ old('key_results_targets') }}</textarea>
                                    </div>

                                    {{-- Baris 7: Catatan Khusus (full width) --}}
                                    <div class="col-12">
                                        <label class="form-label">Catatan Khusus MPR <span class="text-muted fw-normal small">(Opsional)</span></label>
                                        <textarea name="special_notes" class="form-control" rows="2"
                                            placeholder="Catatan khusus terkait kebutuhan ini...">{{ old('special_notes') }}</textarea>
                                    </div>

                                </div>{{-- end row Alasan & Kualifikasi --}}
                            </div>{{-- end Alasan & Kualifikasi --}}

                            <hr class="my-4">

                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ request()->routeIs('mpr.auth.*') ? route('mpr.auth.request.history') : route('hr.mpr.history') }}"
                                    class="btn btn-outline-secondary px-3">Batal</a>
                                <button type="submit" id="btnSubmitMprManager"
                                    class="btn btn-primary px-4 fw-semibold shadow-sm">
                                    <span class="spinner-border spinner-border-sm me-1 d-none"
                                        id="spinnerSubmitManager"></span>
                                    <i class="bi bi-send-fill me-1" id="iconSubmitManager"></i> Kirim
                                    Pengajuan &amp; Generate PDF
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const departmentDivisionMap = @json($departmentDivisionMap);

            function setupDepartmentDivision(form, selectedDivision = '') {
                const department = form.elements.department;
                const division = form.elements.division;
                if (!department || !division) return;

                const initialDivision = selectedDivision || division.dataset.selectedDivision || '';
                const updateDivisions = (preserveSelection = false) => {
                    const divisions = departmentDivisionMap[department.value] || [];
                    const currentDivision = preserveSelection ? initialDivision || division.value : '';
                    division.innerHTML = '';
                    division.append(new Option(
                        divisions.length ? '-- Pilih Divisi --' :
                        '-- Pilih Departemen terlebih dahulu --',
                        ''
                    ));
                    divisions.forEach(value => division.append(new Option(value, value)));
                    division.disabled = divisions.length === 0;
                    division.value = divisions.includes(currentDivision) ? currentDivision : '';
                };

                department.addEventListener('change', () => updateDivisions(false));
                updateDivisions(true);
            }

            document.querySelectorAll('form').forEach(form => setupDepartmentDivision(form));

            const formMpr = document.getElementById('formManagerMpr');

            function sanitizeLanguagesField(field) {
                if (!field) return;
                field.value = field.value
                    .replace(/[^\p{L} ,.\(\)\r\n]/gu, '')
                    .replace(/ {2,}/g, ' ');
            }

            if (formMpr) {
                const languagesField = formMpr.querySelector('textarea[name="languages"]');
                if (languagesField) {
                    languagesField.addEventListener('input', function () {
                        sanitizeLanguagesField(languagesField);
                    });
                    languagesField.addEventListener('blur', function () {
                        languagesField.value = languagesField.value.trim();
                    });
                }
            }

            /**
             * Logika Shifting:
             * - Jika "Shifting" dipilih (radio):
             *   → Radio hari kerja lain di-disable
             *   → Radio jam kerja di-uncheck & disabled
             *   → Textarea detail shift: enabled, required, border highlight
             * - Jika hari kerja lain dipilih:
             *   → Radio hari kerja lain kembali enabled
             *   → Radio jam kerja kembali enabled
             *   → Textarea detail shift: disabled, not required, clear value
             */
            function applyShiftingState(form) {
                if (!form) return;

                const shiftingCb   = form.querySelector('input[name="working_days[]"][value="shifting"]');
                const otherDayCbs  = Array.from(form.querySelectorAll('input[name="working_days[]"]'))
                                         .filter(cb => cb.value !== 'shifting');
                const hourCbs      = Array.from(form.querySelectorAll('input[name="working_hours[]"]'));
                const shiftField   = form.querySelector('#shiftDetailField');
                const requiredMark = form.querySelector('#shiftDetailRequiredMark');
                const hintNote     = form.querySelector('#shiftDetailNote');

                if (!shiftingCb) return;

                const isShifting = shiftingCb.checked;

                // --- Hari Kerja lain ---
                otherDayCbs.forEach(cb => {
                    if (isShifting) {
                        cb.checked  = false;
                        cb.disabled = true;
                        cb.closest('.form-check')?.classList.add('opacity-50');
                    } else {
                        cb.disabled = false;
                        cb.closest('.form-check')?.classList.remove('opacity-50');
                    }
                });

                // --- Jam Kerja ---
                hourCbs.forEach(cb => {
                    if (isShifting) {
                        cb.checked  = false;
                        cb.disabled = true;
                        cb.closest('.form-check')?.classList.add('opacity-50');
                    } else {
                        cb.disabled = false;
                        cb.closest('.form-check')?.classList.remove('opacity-50');
                    }
                });

                // --- Detail Shift textarea ---
                if (shiftField) {
                    shiftField.disabled = !isShifting;
                    shiftField.required = isShifting;
                    if (!isShifting) {
                        shiftField.value = '';
                        shiftField.classList.remove('border-primary');
                    } else {
                        shiftField.classList.add('border-primary');
                        shiftField.focus();
                    }
                }
                if (requiredMark) requiredMark.classList.toggle('d-none', !isShifting);
                if (hintNote)     hintNote.classList.toggle('d-none', isShifting);
            }

            if (formMpr) {
                formMpr.querySelectorAll('input[name="working_days[]"]').forEach(function (dayRadio) {
                    dayRadio.addEventListener('change', () => applyShiftingState(formMpr));
                });
                // Inisialisasi state saat halaman dimuat (handle old() value saat validation error)
                applyShiftingState(formMpr);
            }

            /**
             * Logika Replacement:
             * - Jika alasan = "Penggantian Karyawan Resign / Mutasi / Demosi":
             *   → Input nama karyawan yang digantikan: enabled, required
             * - Selain itu:
             *   → Input disabled, not required, value dikosongkan
             */
            const REPLACEMENT_REASON_VALUE = 'Penggantian Karyawan Resign / Mutasi / Demosi';

            function applyReplacementState(form) {
                if (!form) return;
                const reasonSelect     = form.querySelector('[name="reason"]');
                const replacementField = form.querySelector('#replacementForField');
                const requiredMark     = form.querySelector('#replacementForRequiredMark');
                const hintText         = form.querySelector('#replacementForHint');
                const noteText         = form.querySelector('#replacementForNote');
                if (!reasonSelect || !replacementField) return;

                const isReplacement = reasonSelect.value === REPLACEMENT_REASON_VALUE;

                replacementField.disabled = !isReplacement;
                replacementField.required = isReplacement;
                if (!isReplacement) {
                    replacementField.value = '';
                    replacementField.classList.remove('border-primary');
                } else {
                    replacementField.classList.add('border-primary');
                }
                if (requiredMark) requiredMark.classList.toggle('d-none', !isReplacement);
                if (hintText)     hintText.classList.toggle('d-none', isReplacement);
                if (noteText)     noteText.classList.toggle('d-none', isReplacement);
            }

            if (formMpr) {
                const reasonSelect = formMpr.querySelector('[name="reason"]');
                if (reasonSelect) {
                    reasonSelect.addEventListener('change', () => applyReplacementState(formMpr));
                }
                applyReplacementState(formMpr);
            }

            function getCsrfToken() {
                const meta = document.querySelector('meta[name="csrf-token"]');
                return meta ? meta.getAttribute('content') : '';
            }

            if (!formMpr) return;

            formMpr.addEventListener('submit', function(e) {
                e.preventDefault();

                const btn = document.getElementById('btnSubmitMprManager');
                const spinner = document.getElementById('spinnerSubmitManager');
                const icon = document.getElementById('iconSubmitManager');

                if (btn) btn.disabled = true;
                if (spinner) spinner.classList.remove('d-none');
                if (icon) icon.classList.add('d-none');

                fetch(formMpr.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': getCsrfToken(),
                            'Accept': 'application/json',
                        },
                        body: new FormData(formMpr),
                    })
                    .then(async response => {
                        const data = await response.json();
                        if (!response.ok) {
                            throw new Error(data.message || (data.errors ? Object.values(data
                                    .errors).flat().join('\n') :
                                'Terjadi kesalahan saat menyimpan MPR.'));
                        }
                        return data;
                    })
                    .then(data => {
                        formMpr.reset();
                        const alertEl = document.getElementById('mprSuccessAlert');
                        const alertTitle = document.getElementById('mprSuccessTitle');
                        const alertBody = document.getElementById('mprSuccessBody');
                        const btnPdf = document.getElementById('btnAlertOpenPdf');

                        if (alertEl) {
                            if (alertTitle) alertTitle.innerText =
                                `Pengajuan MPR Berhasil! (${data.mpr_number})`;
                            if (alertBody) alertBody.innerText =
                                `Dokumen Manpower Request nomor ${data.mpr_number} telah berhasil disimpan dan PDF otomatis digenerate.`;
                            if (btnPdf) btnPdf.href = data.pdf_url;
                            alertEl.classList.remove('d-none');
                            alertEl.classList.add('show');
                            window.scrollTo({
                                top: 0,
                                behavior: 'smooth'
                            });
                        }

                        if (data.pdf_url) {
                            window.open(data.pdf_url, '_blank');
                        }

                        setTimeout(() => {
                            window.location.href =
                                '{{ request()->routeIs('mpr.auth.*') ? route('mpr.auth.request.history') : route('hr.mpr.history') }}';
                        }, 1200);
                    })
                    .catch(error => {
                        if (typeof showToast === 'function') {
                            showToast('Gagal: ' + error.message, 'error');
                        } else {
                            alert('Gagal: ' + error.message);
                        }
                    })
                    .finally(() => {
                        if (btn) btn.disabled = false;
                        if (spinner) spinner.classList.add('d-none');
                        if (icon) icon.classList.remove('d-none');
                    });
            });
        });
    </script>
@endsection
