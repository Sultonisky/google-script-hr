@extends('layouts.hr')

@section('title', 'Pengajuan Manpower Request (MPR) - MITO HRIS')
@section('page-title', 'Pengajuan Manpower Request')
@section('page-subtitle', 'Isi kebutuhan tenaga kerja untuk diproses HR')

@section('styles')
    <style>
        .mpr-form-shell {
            max-width: 1100px;
        }

        .mpr-section-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(11, 37, 64, 0.05);
            overflow: hidden;
        }

        .mpr-section-header {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
            color: #fff;
            border-bottom: 1px solid rgba(255,255,255,0.12);
            padding: 1rem 1.25rem;
        }

        .mpr-section-header h5 {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
        }

        .mpr-form-body {
            padding: 1.25rem;
        }

        .mpr-form-body .row {
            --bs-gutter-y: 1rem;
        }

        .form-label {
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 0.45rem;
        }

        .form-control,
        .form-select,
        .form-select-sm,
        .form-control-sm,
        textarea.form-control {
            border-radius: 10px;
            border: 1.5px solid var(--color-border);
            min-height: 42px;
            background: var(--color-surface);
            color: var(--color-text);
        }

        .form-control:focus,
        .form-select:focus,
        textarea.form-control:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 0.2rem rgba(0, 91, 172, 0.15);
        }

        .mpr-identity-box {
            background: rgba(0, 91, 172, 0.03);
            border: 1px solid var(--color-border);
            border-radius: 14px;
            padding: 1rem;
        }

        .mpr-badge {
            font-size: 0.72rem;
            border-radius: 999px;
            padding: 0.38rem 0.7rem;
        }

        @media (max-width: 767.98px) {
            .mpr-form-body {
                padding: 1rem;
            }

            .d-flex.justify-content-end.gap-2 {
                flex-direction: column-reverse;
            }

            .d-flex.justify-content-end.gap-2 .btn {
                width: 100%;
            }
        }
    </style>
@endsection

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
                                <a href="{{ session('mpr_pdf_url') }}" target="_blank" class="btn btn-sm btn-success fw-semibold">
                                    <i class="bi bi-file-earmark-pdf-fill me-1"></i> Unduh PDF
                                </a>
                            </div>
                        @endif
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="mpr-section-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                    <div>
                        <h5 class="mb-0"><i class="bi bi-file-earmark-plus-fill me-2"></i>Formulir Pengajuan Manpower Request</h5>
                    </div>
                    <span class="badge bg-white text-primary fw-bold mpr-badge">Role: Manpower</span>
                </div>
                <div class="card-body p-0">
                    <div class="mpr-form-body">
                        <form id="formManagerMpr" action="{{ route('hr.mpr.store') }}" method="POST">
                            @csrf

                            <div class="mpr-identity-box mb-4">
                                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                                    <span class="text-primary fw-bold small"><i class="bi bi-shield-lock-fill me-1"></i>Identitas Pemohon (Terverifikasi Sistem)</span>
                                    <span class="badge bg-secondary-subtle text-secondary border mpr-badge"><i class="bi bi-lock-fill me-1"></i> Terkunci Permanen</span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label text-muted small fw-semibold mb-1">Nama Pemohon (Manager)</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                                            <input type="text" class="form-control" value="{{ $user['fullName'] ?? ($user['name'] ?? 'Manager') }}" readonly disabled>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-muted small fw-semibold mb-1">Email Pemohon</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                                            <input type="text" class="form-control" value="{{ $user['email'] ?? '-' }}" readonly disabled>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-muted small fw-semibold mb-1">Branch / Lokasi Pemohon</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="bi bi-geo-alt-fill"></i></span>
                                            <input type="text" class="form-control" value="{{ !empty($user['branch']) ? $user['branch'] : '-' }}" readonly disabled title="Branch diambil otomatis dari profil akun Anda">
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3 mt-1">
                                    <div class="col-12">
                                        <label class="form-label text-muted small fw-semibold mb-1">
                                            Entitas yang Dituju <span class="text-danger">*</span>
                                            <span class="ms-1 text-muted fw-normal" style="font-size:10px;">(pilih dari daftar entitas Anda)</span>
                                        </label>
                                        @if (count($allowedEntities) === 0)
                                            <div class="alert alert-warning py-2 px-3 mb-0 small">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                                Akun Anda belum memiliki entitas yang di-assign. Hubungi administrator untuk menambahkan entitas.
                                            </div>
                                            <input type="hidden" name="entity" value="">
                                        @elseif(count($allowedEntities) === 1)
                                            @php
                                                $singleEntityCode = array_key_first($allowedEntities);
                                            @endphp
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text"><i class="bi bi-building-fill"></i></span>
                                                <input type="text" class="form-control" value="{{ $singleEntityCode }} — {{ $allowedEntities[$singleEntityCode] }}" readonly disabled>
                                            </div>
                                            <input type="hidden" name="entity" value="{{ $singleEntityCode }}">
                                        @else
                                            <select name="entity" class="form-select" required>
                                                <option value="">-- Pilih Entitas --</option>
                                                @foreach ($allowedEntities as $code => $label)
                                                    <option value="{{ $code }}">{{ $code }} — {{ $label }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-3 text-muted small">
                                    <i class="bi bi-shield-check-fill text-success me-1"></i>
                                    Identitas, branch, dan daftar entitas diambil dari sesi akun login Anda. Pilih entitas yang sesuai untuk pengajuan ini.
                                </div>
                            </div>

                            <div class="mb-4">
                                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-briefcase-fill me-2"></i>Detail Posisi yang Dibutuhkan</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Posisi / Jabatan yang Diminta <span class="text-danger">*</span></label>
                                        <input type="text" name="position" class="form-control" placeholder="Contoh: Frontend Developer, Sales Executive" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Departemen <span class="text-danger">*</span></label>
                                        <select name="department" class="form-select" required>
                                            <option value="">-- Pilih Departemen --</option>
                                            @foreach ($departments as $d)
                                                <option value="{{ $d }}" {{ old('department') === $d ? 'selected' : '' }}>{{ $d }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Divisi <span class="text-danger">*</span></label>
                                        <select name="division" class="form-select" data-selected-division="{{ old('division') }}" required>
                                            <option value="">-- Pilih Departemen terlebih dahulu --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Level Jabatan <span class="text-danger">*</span></label>
                                        <select name="job_level" class="form-select" required>
                                            <option value="">-- Pilih Level Jabatan --</option>
                                            @foreach ($jobLevels as $jl)
                                                <option value="{{ $jl }}">{{ $jl }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Lokasi Kerja Penempatan <span class="text-danger">*</span></label>
                                        <select name="work_location" class="form-select" required>
                                            <option value="">-- Pilih Lokasi Kerja --</option>
                                            @foreach ($workLocations as $wl)
                                                <option value="{{ $wl }}">{{ $wl }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Status Kepegawaian <span class="text-danger">*</span></label>
                                        <select name="employment_type" class="form-select" required>
                                            <option value="">-- Pilih Status Kepegawaian --</option>
                                            @foreach ($employmentTypes as $et)
                                                <option value="{{ $et }}">{{ $et }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Jumlah Kebutuhan (Orang) <span class="text-danger">*</span></label>
                                        <input type="number" name="quantity" class="form-control" value="1" min="1" max="100" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Target Tanggal Bergabung (Join Date) <span class="text-danger">*</span></label>
                                        <input type="date" name="expected_join_date" class="form-control" min="{{ date('Y-m-d') }}" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-question-circle-fill me-2"></i>Alasan & Kualifikasi</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Alasan Permintaan <span class="text-danger">*</span></label>
                                        <select name="reason" class="form-select" id="mprReasonSelect" required>
                                            <option value="">-- Pilih Alasan Permintaan --</option>
                                            @foreach ($reasons as $r)
                                                <option value="{{ $r }}">{{ $r }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nama Karyawan yang Digantikan (Opsional)</label>
                                        <input type="text" name="replacement_for" class="form-control" placeholder="Diisi jika alasan adalah penggantian">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Kualifikasi & Persyaratan Khusus Kandidat</label>
                                        <textarea name="requirements" class="form-control" rows="3" placeholder="Contoh: Pendidikan min. S1 Informatika, Pengalaman min. 2 tahun di Laravel, memiliki komunikasi baik..."></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Uraian Tugas & Tanggung Jawab Utama</label>
                                        <textarea name="job_description" class="form-control" rows="3" placeholder="Contoh: Mengembangkan fitur web HRIS, melakukan code review, memastikan performa database..."></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Catatan Tambahan (Opsional)</label>
                                        <textarea name="notes" class="form-control" rows="2" placeholder="Catatan atau instruksi tambahan untuk tim rekrutmen..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('hr.mpr.history') }}" class="btn btn-outline-secondary px-3">Batal</a>
                                <button type="submit" id="btnSubmitMprManager" class="btn btn-primary px-4 fw-semibold shadow-sm">
                                    <span class="spinner-border spinner-border-sm me-1 d-none" id="spinnerSubmitManager"></span>
                                    <i class="bi bi-send-fill me-1" id="iconSubmitManager"></i> Kirim Pengajuan & Generate PDF
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
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
                        divisions.length ? '-- Pilih Divisi --' : '-- Pilih Departemen terlebih dahulu --',
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

            function getCsrfToken() {
                const meta = document.querySelector('meta[name="csrf-token"]');
                return meta ? meta.getAttribute('content') : '';
            }

            const form = document.getElementById('formManagerMpr');
            if (!form) return;

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const btn = document.getElementById('btnSubmitMprManager');
                const spinner = document.getElementById('spinnerSubmitManager');
                const icon = document.getElementById('iconSubmitManager');

                if (btn) btn.disabled = true;
                if (spinner) spinner.classList.remove('d-none');
                if (icon) icon.classList.add('d-none');

                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'Accept': 'application/json',
                    },
                    body: new FormData(form),
                })
                    .then(async response => {
                        const data = await response.json();
                        if (!response.ok) {
                            throw new Error(data.message || (data.errors ? Object.values(data.errors).flat().join('\n') : 'Terjadi kesalahan saat menyimpan MPR.'));
                        }
                        return data;
                    })
                    .then(data => {
                        form.reset();
                        const alertEl = document.getElementById('mprSuccessAlert');
                        const alertTitle = document.getElementById('mprSuccessTitle');
                        const alertBody = document.getElementById('mprSuccessBody');
                        const btnPdf = document.getElementById('btnAlertOpenPdf');

                        if (alertEl) {
                            if (alertTitle) alertTitle.innerText = `Pengajuan MPR Berhasil! (${data.mpr_number})`;
                            if (alertBody) alertBody.innerText = `Dokumen Manpower Request nomor ${data.mpr_number} telah berhasil disimpan dan PDF otomatis digenerate.`;
                            if (btnPdf) btnPdf.href = data.pdf_url;
                            alertEl.classList.remove('d-none');
                            alertEl.classList.add('show');
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                        }

                        if (data.pdf_url) {
                            window.open(data.pdf_url, '_blank');
                        }

                        setTimeout(() => {
                            window.location.href = '{{ route('hr.mpr.history') }}';
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
