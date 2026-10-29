<!-- SURAT PERINGATAN MODAL — search karyawan Contract & Permanent, lalu isi detail SP -->
<style>
    #warningLetterModal .modal-dialog {
        max-width: min(1200px, 92vw);
    }

    #warningLetterModal .modal-content {
        border-radius: 18px;
        box-shadow: 0 1.5rem 2.5rem rgba(15, 23, 42, 0.18);
    }

    #warningLetterModal .modal-body {
        padding: 1.5rem 1.5rem 1rem;
    }

    #warningLetterModal .form-label {
        margin-bottom: 0.5rem;
    }

    #warningLetterModal .form-control-sm,
    #warningLetterModal .form-select-sm {
        min-height: calc(2.75rem + 2px);
        font-size: 0.92rem;
    }

    #warningLetterModal textarea.form-control,
    #warningLetterModal textarea.form-control-sm {
        min-height: 120px;
        line-height: 1.6;
    }

    #warningLetterModal .wl-violation-row,
    #warningLetterModal .wl-regulation-row,
    #warningLetterModal .wl-violation-ref {
        background: #fff;
        border: 1px solid #e5e7eb;
    }

    #warningLetterModal .wl-violation-row textarea,
    #warningLetterModal .wl-violation-ref textarea,
    #warningLetterModal .wl-regulation-row textarea {
        min-height: 120px;
    }

    @media (max-width: 767.98px) {
        #warningLetterModal .modal-body {
            padding: 1rem;
        }
    }
</style>
<div class="modal fade" id="warningLetterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:18px">
            <div class="modal-header" style="background:#eb1c24;border-radius:16px 16px 0 0">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill text-white fs-5"></i>
                    <h6 class="modal-title mb-0 text-white fw-bold">Generate Surat Peringatan</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            {{-- Form membungkus body + footer: harus flex column agar modal-dialog-scrollable tetap menggulir body --}}
            <form id="formWarningLetter" class="d-flex flex-column" style="min-height:0;flex:1 1 auto;overflow:hidden" novalidate>
                <div class="modal-body p-4">
                    <input type="hidden" id="wlEmployeeId" />

                    <!-- STEP 1: Search karyawan -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:13px" for="wlEmpSearch">
                            <i class="bi bi-search me-1"></i>Cari Karyawan Contract / Permanent <span class="text-danger">*</span>
                        </label>
                        <div class="position-relative">
                            <input type="text" class="form-control" id="wlEmpSearch"
                                placeholder="Ketik nama, Employee ID, atau jabatan..." autocomplete="off"
                                style="font-size:13px;padding-right:36px" />
                            <i class="bi bi-x-circle-fill position-absolute" id="wlEmpSearchClear"
                                style="right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:#aaa;display:none"></i>
                        </div>
                        <div id="wlEmpDropdown" class="border rounded-3 mt-1 shadow-sm"
                            style="display:none;max-height:220px;overflow-y:auto;z-index:9999;position:relative"></div>
                        <div class="form-text" style="font-size:11px">Hanya karyawan berstatus Contract dan Permanent.</div>
                    </div>

                    <!-- STEP 2: Preview + form -->
                    <div id="wlEmpPreview" style="display:none">
                        <div class="p-3 rounded-3 mb-3" style="border:1px solid #eb1c24">
                            <div class="d-flex align-items-center gap-3">
                                <div id="wlEmpAvatar"
                                        style="width:44px;height:44px;font-size:16px;flex-shrink:0;background:#eb1c24;color:#fff;border-radius:12px;display:flex;align-items:center;justify-content:center;font-weight:800">
                                    ?</div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-primary" id="wlEmpName" style="font-size:15px">-</div>
                                    <div class="text-muted" style="font-size:12px">
                                        <span id="wlEmpPosition">-</span> <span class="mx-1">&bull;</span>
                                        <span id="wlEmpDept">-</span> <span class="mx-1">&bull;</span>
                                        <span id="wlEmpStatus">-</span>
                                    </div>
                                    <div class="text-muted" style="font-size:11.5px" id="wlEmpBranch">-</div>
                                    <div class="text-muted" style="font-size:11.5px">Lokasi kerja: <span id="wlEmpLocation">-</span></div>
                                </div>
                                <div class="text-end flex-shrink-0" style="font-size:11.5px">
                                    <div class="text-muted">Employee ID</div>
                                    <div class="fw-semibold text-primary" id="wlEmpIdDisp">-</div>
                                </div>
                            </div>
                        </div>

                        <div id="wlLastSp" class="alert alert-warning py-2 px-3 mb-3" style="display:none;font-size:12.5px">
                            <i class="bi bi-clock-history me-1"></i><span id="wlLastSpText"></span>
                        </div>

                        <div id="wlErrors" class="alert alert-danger py-2 px-3 mb-3" style="display:none;font-size:12.5px"></div>

                        <p class="fw-bold mb-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#eb1c24">
                            <i class="bi bi-file-earmark-text me-1"></i>Detail Surat
                        </p>
                        <div class="row g-3 mb-2">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="font-size:13px" for="wlLevel">Tingkat SP <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" name="level" id="wlLevel" required>
                                    @foreach (\App\Enums\WarningLetterLevel::cases() as $wlLevel)
                                        <option value="{{ $wlLevel->value }}">{{ $wlLevel->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="font-size:13px" for="wlDocDate">Tanggal Surat <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" name="doc_date" id="wlDocDate"
                                    value="{{ now()->timezone('Asia/Jakarta')->format('Y-m-d') }}" required />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="font-size:13px" for="wlValidity">Masa Berlaku</label>
                                <input type="text" class="form-control form-control-sm bg-light" id="wlValidity" value="6 bulan" readonly tabindex="-1" />
                                <div class="form-text" style="font-size:11px">Berlaku s.d. <strong id="wlValidUntil">-</strong></div>
                            </div>
                        </div>
                        <div class="row g-3 mb-4" id="wlSuperiorPositionField">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold" style="font-size:13px" for="wlSuperiorName">Nama Atasan <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm" name="superior_name" id="wlSuperiorName"
                                            maxlength="150" list="wlSuperiorOptions" autocomplete="off"
                                            placeholder="Ketik nama atasan..." />
                                        <datalist id="wlSuperiorOptions"></datalist>
                                        <div class="form-text" style="font-size:11px">Terisi otomatis dari Direct Superior karyawan, bisa diubah.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold" style="font-size:13px" for="wlSuperiorPosition">Jabatan Atasan <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm" name="superior_position" id="wlSuperiorPosition"
                                            maxlength="150" placeholder="Misal: Branch Manager Lampung" />
                                        <div class="form-text" style="font-size:11px" id="wlSuperiorPositionHelp">Terisi otomatis bila nama atasan ditemukan di data karyawan.</div>
                                    </div>
                        </div>

                        <p class="fw-bold mb-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#eb1c24">
                            <i class="bi bi-exclamation-octagon me-1"></i>Pelanggaran
                        </p>
                        <div class="row g-3 mb-4">
                            <div class="col-md-7" id="wlCategoryField">
                                <label class="form-label fw-semibold" style="font-size:13px" for="wlCategory">Kategori Pelanggaran <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" name="violation_category" id="wlCategory" required>
                                    <option value="">— Pilih kategori —</option>
                                    @foreach (\App\Services\WarningLetterService::VIOLATION_CATEGORIES as $wlCategory)
                                        <option value="{{ $wlCategory }}">{{ $wlCategory }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5" id="wlIncidentDateField">
                                <label class="form-label fw-semibold" style="font-size:13px" for="wlIncidentDate">Tanggal Kejadian <span class="text-muted fw-normal">(opsional)</span></label>
                                <input type="date" class="form-control form-control-sm" name="incident_date" id="wlIncidentDate" />
                            </div>
                            <div class="col-12" id="wlViolationSection" style="display:none">
                                <label class="form-label fw-semibold" style="font-size:13px">Rincian Pelanggaran <span class="text-danger">*</span></label>
                                <div id="wlViolationRows"></div>
                                <template id="wlViolationTemplate">
                                    <div class="border rounded-3 p-3 mb-3 wl-violation-row">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="small fw-semibold wl-violation-row-title"></span>
                                            <button type="button" class="btn btn-sm btn-outline-danger wl-remove-violation" aria-label="Hapus pelanggaran">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                        <label class="form-label mb-1" style="font-size:12px">Uraian pelanggaran</label>
                                        <textarea class="form-control form-control-sm" data-violation-field="description" rows="5" maxlength="1000"
                                            placeholder="Contoh: Tidak melaksanakan perintah dan/atau instruksi Atasan sesuai dengan arahan yang telah diberikan dalam penyelesaian permasalahan pekerjaan"></textarea>
                                        <div class="form-text mb-2" style="font-size:11px">Tulis tanpa menyebut pasal. Kalimat &ldquo;sebagaimana diatur dalam Pasal &hellip;&rdquo; ditambahkan otomatis di PDF.</div>
                                        <div class="wl-violation-refs"></div>
                                        <button type="button" class="btn btn-sm btn-outline-primary wl-add-violation-ref">
                                            <i class="bi bi-plus-lg me-1"></i>Tambah pasal
                                        </button>
                                    </div>
                                </template>
                                <template id="wlViolationRefTemplate">
                                    <div class="bg-light rounded-3 p-2 mb-2 wl-violation-ref">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="small fw-semibold wl-violation-ref-title"></span>
                                            <button type="button" class="btn btn-sm btn-outline-danger wl-remove-violation-ref" aria-label="Hapus pasal">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                        <input type="hidden" data-ref-field="regulation_type" value="Peraturan Perusahaan" />
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <select class="form-select form-select-sm" data-ref-field="chapter_number" data-pp-field="chapter" aria-label="Pilih bab">
                                                    <option value="">Pilih bab</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <input type="text" class="form-control form-control-sm" data-ref-field="chapter_title"
                                                    maxlength="200" placeholder="Judul bab terisi otomatis" aria-label="Judul bab" readonly />
                                            </div>
                                            <div class="col-md-6">
                                                <select class="form-select form-select-sm" data-ref-field="article_number" data-pp-field="article" aria-label="Pilih pasal" disabled>
                                                    <option value="">Pilih pasal</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <input type="text" class="form-control form-control-sm" data-ref-field="article_title"
                                                    maxlength="200" placeholder="Judul pasal terisi otomatis" aria-label="Judul pasal" readonly />
                                            </div>
                                            <div class="col-md-4">
                                                <select class="form-select form-select-sm" data-ref-field="paragraph_number" data-pp-field="paragraph" aria-label="Pilih ayat atau huruf" disabled>
                                                    <option value="">Pilih ayat / huruf (opsional)</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <select class="form-select form-select-sm" data-ref-field="article_letter" data-pp-field="subparagraph" aria-label="Pilih subhuruf (opsional)" disabled>
                                                    <option value="">Pilih subhuruf (opsional)</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <select class="form-select form-select-sm" data-ref-field="subparagraph_number" data-pp-field="nested-subparagraph" aria-label="Pilih nomor subbutir (opsional)" disabled>
                                                    <option value="">Pilih nomor subbutir (opsional)</option>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <textarea class="form-control form-control-sm" data-ref-field="article_text" rows="4" maxlength="10000"
                                                    placeholder="Kutipan terisi otomatis dari pilihan" aria-label="Bunyi pasal" readonly></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <div class="form-text" style="font-size:11px">Setiap pelanggaran wajib memiliki uraian dan minimal satu pasal. Bunyi pasal dicetak sebagai kutipan di bawah uraian.</div>
                                    <button type="button" class="btn btn-sm btn-outline-primary text-nowrap" id="wlAddViolation">
                                        <i class="bi bi-plus-lg me-1"></i>Tambah pelanggaran
                                    </button>
                                </div>
                            </div>
                            <div class="col-12" id="wlDescriptionField">
                                <label class="form-label fw-semibold" style="font-size:13px" for="wlDescription">Uraian Pelanggaran <span class="text-danger">*</span></label>
                                <textarea class="form-control form-control-sm" name="violation_description" id="wlDescription" rows="6"
                                    maxlength="2000" required
                                    placeholder="Jelaskan kronologi pelanggaran secara faktual: apa yang terjadi, kapan, di mana, dan dampaknya."></textarea>
                                <div class="form-text text-end" style="font-size:11px"><span id="wlDescCount">0</span> / 2000</div>
                            </div>
                            <div class="col-12" id="wlRegulationField">
                                <div id="wlStructuredRegulation">
                                    <label class="form-label fw-semibold" style="font-size:13px">
                                        <span id="wlRegulationTitle">Jenis Peraturan</span>
                                        <span class="text-danger" id="wlRegulationRequired" style="display:none">*</span>
                                        <span class="text-muted fw-normal" id="wlRegulationOptional">(opsional)</span>
                                    </label>
                                    <div id="wlRegulationRows">
                                        <div class="border rounded-3 p-3 mb-2 wl-regulation-row">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="small fw-semibold wl-regulation-row-title">Dasar ketentuan 1</span>
                                                <button type="button" class="btn btn-sm btn-outline-danger wl-remove-regulation" aria-label="Hapus dasar ketentuan" style="display:none">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-12 wl-regulation-type-col">
                                                    <label class="form-label mb-1" style="font-size:12px">Jenis peraturan</label>
                                                    <select class="form-select form-select-sm" data-regulation-field="regulation_type" name="regulation_references[0][regulation_type]">
                                                        <option value="">— Pilih jenis peraturan —</option>
                                                        @foreach (\App\Services\WarningLetterService::REGULATION_TYPES as $regulationType)
                                                            <option value="{{ $regulationType }}">{{ $regulationType }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label mb-1" style="font-size:12px">Bab <span class="text-muted fw-normal">(opsional)</span></label>
                                                    <select class="form-select form-select-sm" data-regulation-field="chapter_number" data-pp-field="chapter">
                                                        <option value="">Pilih bab</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label mb-1" style="font-size:12px">Judul bab <span class="text-muted fw-normal">(opsional)</span></label>
                                                    <input type="text" class="form-control form-control-sm" data-regulation-field="chapter_title"
                                                        maxlength="200" placeholder="Terisi otomatis dari pilihan bab" readonly />
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label mb-1" style="font-size:12px">Pasal</label>
                                                    <select class="form-select form-select-sm" data-regulation-field="article_number" data-pp-field="article">
                                                        <option value="">Pilih pasal</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label mb-1" style="font-size:12px">Judul pasal <span class="text-muted fw-normal">(opsional)</span></label>
                                                    <input type="text" class="form-control form-control-sm" data-regulation-field="article_title"
                                                        maxlength="200" placeholder="Terisi otomatis dari pilihan pasal" readonly />
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label mb-1" style="font-size:12px">Ayat / huruf <span class="text-muted fw-normal">(opsional)</span></label>
                                                    <select class="form-select form-select-sm" data-regulation-field="paragraph_number" data-pp-field="paragraph">
                                                        <option value="">Pilih ayat / huruf (opsional)</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label mb-1" style="font-size:12px">Subhuruf <span class="text-muted fw-normal">(opsional)</span></label>
                                                    <select class="form-select form-select-sm" data-regulation-field="article_letter" data-pp-field="subparagraph">
                                                        <option value="">Pilih subhuruf (opsional)</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label mb-1" style="font-size:12px">Nomor subbutir <span class="text-muted fw-normal">(opsional)</span></label>
                                                    <select class="form-select form-select-sm" data-regulation-field="subparagraph_number" data-pp-field="nested-subparagraph" disabled>
                                                        <option value="">Pilih nomor subbutir (opsional)</option>
                                                    </select>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label mb-1" style="font-size:12px">Bunyi pasal</label>
                                                    <textarea class="form-control form-control-sm" data-pp-field="clause-text" rows="5"
                                                        placeholder="Bunyi pasal akan tampil setelah ayat atau subbutir dipilih" readonly></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <template id="wlRegulationRowTemplate">
                                        <div class="border rounded-3 p-3 mb-2 wl-regulation-row">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="small fw-semibold wl-regulation-row-title"></span>
                                                <button type="button" class="btn btn-sm btn-outline-danger wl-remove-regulation" aria-label="Hapus dasar ketentuan">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-12 wl-regulation-type-col">
                                                    <label class="form-label mb-1" style="font-size:12px">Jenis peraturan</label>
                                                    <select class="form-select form-select-sm" data-regulation-field="regulation_type">
                                                        <option value="">— Pilih jenis peraturan —</option>
                                                        @foreach (\App\Services\WarningLetterService::REGULATION_TYPES as $regulationType)
                                                            <option value="{{ $regulationType }}">{{ $regulationType }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label mb-1" style="font-size:12px">Bab <span class="text-muted fw-normal">(opsional)</span></label>
                                                    <select class="form-select form-select-sm" data-regulation-field="chapter_number" data-pp-field="chapter">
                                                        <option value="">Pilih bab</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label mb-1" style="font-size:12px">Judul bab <span class="text-muted fw-normal">(opsional)</span></label>
                                                    <input type="text" class="form-control form-control-sm" data-regulation-field="chapter_title"
                                                        maxlength="200" placeholder="Terisi otomatis dari pilihan bab" readonly />
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label mb-1" style="font-size:12px">Pasal</label>
                                                    <select class="form-select form-select-sm" data-regulation-field="article_number" data-pp-field="article" disabled>
                                                        <option value="">Pilih pasal</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label mb-1" style="font-size:12px">Judul pasal <span class="text-muted fw-normal">(opsional)</span></label>
                                                    <input type="text" class="form-control form-control-sm" data-regulation-field="article_title"
                                                        maxlength="200" placeholder="Terisi otomatis dari pilihan pasal" readonly />
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label mb-1" style="font-size:12px">Ayat / huruf <span class="text-muted fw-normal">(opsional)</span></label>
                                                    <select class="form-select form-select-sm" data-regulation-field="paragraph_number" data-pp-field="paragraph" disabled>
                                                        <option value="">Pilih ayat / huruf (opsional)</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label mb-1" style="font-size:12px">Subhuruf <span class="text-muted fw-normal">(opsional)</span></label>
                                                    <select class="form-select form-select-sm" data-regulation-field="article_letter" data-pp-field="subparagraph" disabled>
                                                        <option value="">Pilih subhuruf (opsional)</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label mb-1" style="font-size:12px">Nomor subbutir <span class="text-muted fw-normal">(opsional)</span></label>
                                                    <select class="form-select form-select-sm" data-regulation-field="subparagraph_number" data-pp-field="nested-subparagraph" disabled>
                                                        <option value="">Pilih nomor subbutir (opsional)</option>
                                                    </select>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label mb-1" style="font-size:12px">Bunyi pasal</label>
                                                    <textarea class="form-control form-control-sm" data-pp-field="clause-text" rows="5"
                                                        placeholder="Bunyi pasal akan tampil setelah ayat atau huruf dipilih" readonly></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <div class="d-flex justify-content-between align-items-center gap-2">
                                        <div>
                                            <div class="form-text" id="wlRegulationHelp" style="font-size:11px">Tambahkan satu atau lebih pasal. Nomor pasal wajib diisi; ayat dan huruf opsional.</div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary text-nowrap" id="wlAddRegulation">
                                            <i class="bi bi-plus-lg me-1"></i>Tambah pasal
                                        </button>
                                    </div>
                                    <ol class="small mt-2 mb-0 ps-3 text-muted" id="wlRegulationPreview" aria-live="polite"></ol>
                                </div>
                            </div>
                        </div>

                        <div id="wlCorrectiveSection">
                            <p class="fw-bold mb-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#eb1c24">
                                <i class="bi bi-tools me-1"></i>Tindakan Perbaikan
                            </p>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold" style="font-size:13px" for="wlCorrective">Tindakan perbaikan yang diharapkan <span class="text-muted fw-normal">(opsional, satu per baris)</span></label>
                                    <textarea class="form-control form-control-sm" name="corrective_actions" id="wlCorrective" rows="5" maxlength="1500"
                                        placeholder="Misal:&#10;Hadir tepat waktu sesuai jadwal kerja&#10;Melapor ke atasan langsung apabila berhalangan hadir"></textarea>
                                    <div class="form-text" style="font-size:11px">Poin standar (mematuhi peraturan, tidak mengulangi pelanggaran, perbaikan kinerja) selalu dicantumkan.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-outline-danger btn-sm fw-semibold" id="btnPreviewWarningLetter" disabled
                        title="Lihat draft PDF tanpa menerbitkan nomor surat">
                        <i class="bi bi-eye me-1"></i>Preview PDF
                    </button>
                    <button type="submit" class="btn btn-sm text-white fw-semibold" style="background:#eb1c24"
                        id="btnGenerateWarningLetter" disabled>
                        <i class="bi bi-file-earmark-pdf me-1"></i>Buat Surat Peringatan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@php
    $__wlLatest = app(\App\Services\WarningLetterService::class)->latestByEmployee();
    $__wlEmployees = collect($all ?? [])
        ->filter(fn ($e) => \App\Services\WarningLetterService::isEligible($e))
        ->map(fn ($e) => [
            'employeeId' => $e->employeeId ?? null,
            'fullName' => $e->fullName ?? null,
            'statusEmployee' => $e->statusEmployee ?? null,
            'jobPosition' => $e->jobPosition ?? null,
            'jobPositionLocation' => $e->jobPositionLocation ?? null,
            'lokasiKerja' => $e->lokasiKerja ?? null,
            'areaKerja' => $e->areaKerja ?? null,
            'department' => $e->department ?? null,
            'branchName' => $e->branchName ?? null,
            'directSuperior' => trim((string) ($e->directSuperior ?? '')) ?: null,
            'lastSp' => $__wlLatest[$e->employeeId ?? ''] ?? null,
            'spCount' => (int) (($sanctionCounts ?? [])[$e->employeeId ?? ''] ?? 0),
        ])
        ->values()
        ->all();
    $__wlSuperiors = collect($all ?? [])
        ->reject(fn ($e) => in_array(strtolower(trim((string) ($e->statusEmployee ?? ''))), ['resigned', 'contract finished'], true))
        ->map(fn ($e) => [
            'name' => trim((string) ($e->fullName ?? '')),
            'position' => trim((string) ($e->jobPosition ?? '')) ?: trim((string) ($e->jobPositionLocation ?? '')),
        ])
        ->filter(fn ($s) => $s['name'] !== '')
        ->unique(fn ($s) => mb_strtolower($s['name']))
        ->sortBy('name')
        ->values()
        ->all();
    $__wlStoreUrl = route('hr.employees.warning-letter', ['id' => '__ID__']);
    $__wlPreviewUrl = route('hr.employees.warning-letter.preview', ['id' => '__ID__']);
    $__wlLevelLabels = collect(\App\Enums\WarningLetterLevel::cases())
        ->mapWithKeys(fn ($l) => [$l->value => $l->shortLabel()])
        ->all();
    $__wlLevelMeta = collect(\App\Enums\WarningLetterLevel::cases())
        ->mapWithKeys(fn ($l) => [$l->value => [
            'months' => $l->validityMonths(),
            'next' => ($l->next() ?? $l)->value,
            'final' => $l->usesViolationDetails(),
        ]])
        ->all();
@endphp
<script nonce="{{ request()->attributes->get('csp_nonce') }}">
    (function() {
        var employees = @json($__wlEmployees);
        var superiors = @json($__wlSuperiors);
        var storeUrlTemplate = @json($__wlStoreUrl);
        var previewUrlTemplate = @json($__wlPreviewUrl);
        var levelLabels = @json($__wlLevelLabels);
        var levelMeta = @json($__wlLevelMeta);
        var bulanId = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        var ppChapters = @json($regulationChapters ?? []);

        function el(id) { return document.getElementById(id); }

        function escapeHtml(value) {
            return String(value == null ? '' : value).replace(/[&<>"']/g, function(c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        function parseYmd(value) {
            var p = String(value || '').slice(0, 10).split('-');
            if (p.length !== 3) return null;
            var d = new Date(Number(p[0]), Number(p[1]) - 1, Number(p[2]));
            return isNaN(d.getTime()) ? null : d;
        }

        function fmtId(d) {
            return d ? d.getDate() + ' ' + bulanId[d.getMonth()] + ' ' + d.getFullYear() : '-';
        }

        function addMonthsNoOverflow(d, months) {
            var target = new Date(d.getFullYear(), d.getMonth() + months, 1);
            var lastDay = new Date(target.getFullYear(), target.getMonth() + 1, 0).getDate();
            target.setDate(Math.min(d.getDate(), lastDay));
            return target;
        }

        function levelMonths(level) {
            return (levelMeta[level] && levelMeta[level].months) || 12;
        }

        // Semua tingkat memakai rincian pelanggaran yang sama, dengan kutipan dari Bank PP.
        function usesFirstTemplate(level) {
            return !!levelMeta[level];
        }

        function isFinalLevel(level) {
            return !!(levelMeta[level] && levelMeta[level].final);
        }

        function initializePpCascade(row, fieldAttribute) {
            if (row.dataset.ppCascadeReady === '1') return;

            var field = function(name) {
                return row.querySelector('[' + fieldAttribute + '="' + name + '"]');
            };
            var chapter = field('chapter_number');
            var chapterTitle = field('chapter_title');
            var article = field('article_number');
            var articleTitle = field('article_title');
            var paragraph = field('paragraph_number');
            var subparagraph = field('article_letter');
            var nestedSubparagraph = field('subparagraph_number');
            var articleText = field('article_text') || row.querySelector('[data-pp-field="clause-text"]');
            if (!chapter || !chapter.hasAttribute('data-pp-field')) return;

            function setOptions(select, placeholder, options) {
                select.innerHTML = '';
                var first = document.createElement('option');
                first.value = '';
                first.textContent = placeholder;
                select.appendChild(first);
                options.forEach(function(option) {
                    var item = document.createElement('option');
                    item.value = option.value;
                    item.textContent = option.label;
                    select.appendChild(item);
                });
            }

            function selectedChapter() {
                return ppChapters.find(function(item) { return item.number === chapter.value; });
            }

            function selectedArticle() {
                var selected = selectedChapter();
                return selected && selected.articles.find(function(item) { return item.number === article.value; });
            }

            function selectedParagraph() {
                if (!paragraph.value) return null;
                var selected = selectedArticle();
                return selected && selected.paragraphs.find(function(item) { return item.number === paragraph.value; });
            }

            function selectedSubparagraph() {
                var selected = selectedParagraph();
                return selected && selected.sub.find(function(item) { return item.number === subparagraph.value; });
            }

            function updateText() {
                if (!articleText) return;
                var node = selectedParagraph();
                var sub = selectedSubparagraph();
                var nested = sub && sub.sub.find(function(item) { return item.number === nestedSubparagraph.value; });
                var selected = selectedArticle();
                articleText.value = nested ? nested.text : (sub ? sub.text : (node ? node.text : (selected ? selected.text : '')));
            }

            setOptions(chapter, 'Pilih bab', ppChapters.map(function(item) {
                return { value: item.number, label: item.number + ' — ' + item.title };
            }));
            setOptions(article, 'Pilih pasal', []);
            setOptions(paragraph, 'Pilih ayat / huruf (opsional)', []);
            setOptions(subparagraph, 'Pilih subhuruf (opsional)', []);
            setOptions(nestedSubparagraph, 'Pilih nomor subbutir (opsional)', []);
            article.disabled = true;
            paragraph.disabled = true;
            subparagraph.disabled = true;
            nestedSubparagraph.disabled = true;

            chapter.addEventListener('change', function() {
                var selected = selectedChapter();
                chapterTitle.value = selected ? selected.title : '';
                setOptions(article, 'Pilih pasal', selected ? selected.articles.map(function(item) {
                    return { value: item.number, label: 'Pasal ' + item.number };
                }) : []);
                article.disabled = !selected;
                article.value = '';
                articleTitle.value = '';
                setOptions(paragraph, 'Pilih ayat / huruf (opsional)', []);
                paragraph.disabled = true;
                setOptions(subparagraph, 'Pilih subhuruf (opsional)', []);
                subparagraph.disabled = true;
                setOptions(nestedSubparagraph, 'Pilih nomor subbutir (opsional)', []);
                nestedSubparagraph.disabled = true;
                updateText();
                refreshRegulationPreview();
            });

            article.addEventListener('change', function() {
                var selected = selectedArticle();
                articleTitle.value = selected ? selected.title : '';
                setOptions(paragraph, 'Pilih ayat / huruf (opsional)', selected ? selected.paragraphs
                    .filter(function(item) { return item.number !== ''; })
                    .map(function(item) {
                    return { value: item.number, label: /^[a-z]$/i.test(item.number) ? 'Huruf ' + item.number : 'Ayat (' + item.number + ')' };
                }) : []);
                paragraph.disabled = !selected;
                paragraph.value = '';
                setOptions(subparagraph, 'Pilih subhuruf (opsional)', []);
                subparagraph.disabled = true;
                setOptions(nestedSubparagraph, 'Pilih nomor subbutir (opsional)', []);
                nestedSubparagraph.disabled = true;
                updateText();
                refreshRegulationPreview();
            });

            paragraph.addEventListener('change', function() {
                var selected = selectedParagraph();
                var children = selected ? selected.sub : [];
                setOptions(subparagraph, 'Pilih subhuruf (opsional)', children.map(function(item) {
                    var label = /^[a-z]$/i.test(item.number) ? 'Huruf ' + item.number : 'Nomor ' + item.number;
                    return { value: item.number, label: label };
                }));
                subparagraph.disabled = children.length === 0;
                subparagraph.value = '';
                setOptions(nestedSubparagraph, 'Pilih nomor subbutir (opsional)', []);
                nestedSubparagraph.disabled = true;
                updateText();
                refreshRegulationPreview();
            });

            subparagraph.addEventListener('change', function() {
                var selected = selectedSubparagraph();
                var children = selected ? selected.sub : [];
                setOptions(nestedSubparagraph, 'Pilih nomor subbutir (opsional)', children.map(function(item) {
                    var label = /^[a-z]$/i.test(item.number) ? 'Huruf ' + item.number : 'Nomor ' + item.number;
                    return { value: item.number, label: label };
                }));
                nestedSubparagraph.disabled = children.length === 0;
                nestedSubparagraph.value = '';
                updateText();
                refreshRegulationPreview();
            });

            nestedSubparagraph.addEventListener('change', function() {
                updateText();
                refreshRegulationPreview();
            });

            row.dataset.ppCascadeReady = '1';
        }

        function setFieldsDisabled(container, disabled) {
            container.querySelectorAll('input, select, textarea').forEach(function(input) {
                input.disabled = disabled;
            });
        }

        function refreshValidUntil() {
            var d = parseYmd(el('wlDocDate').value);
            var months = levelMonths(el('wlLevel').value);
            el('wlValidity').value = months % 12 === 0 ? (months / 12) + ' tahun' : months + ' bulan';
            if (!d) { el('wlValidUntil').textContent = '-'; return; }
            var until = addMonthsNoOverflow(d, months);
            until.setDate(until.getDate() - 1);
            el('wlValidUntil').textContent = fmtId(until);
        }

        function updateLevelFields() {
            el('wlCorrectiveSection').style.display = 'none';
            el('wlCategoryField').style.display = 'none';
            el('wlIncidentDateField').style.display = 'none';
            el('wlCategory').required = false;
            el('wlViolationSection').style.display = '';
            el('wlDescriptionField').style.display = 'none';
            el('wlDescription').disabled = true;
            el('wlRegulationField').style.display = 'none';
            el('wlStructuredRegulation').style.display = '';
            applyRegulationTypeVisibility();
            setFieldsDisabled(el('wlRegulationField'), true);
            el('wlRegulationTitle').textContent = 'Rincian pelanggaran';
            el('wlRegulationRequired').style.display = 'none';
            el('wlRegulationOptional').style.display = 'none';
            el('wlRegulationHelp').textContent = 'Bab, judul, ayat/huruf dan kutipan opsional.';
            updateViolationRows();
            refreshRegulationPreview();
            el('wlSuperiorPositionField').style.display = '';
            el('wlSuperiorName').required = true;
            el('wlSuperiorPosition').required = true;
        }

        function isSp1Only() {
            return usesFirstTemplate(el('wlLevel').value) && !isFinalLevel(el('wlLevel').value);
        }

        // SP-1 hanya mencantumkan pasal/ayat/huruf, tanpa jenis peraturan.
        function applyRegulationTypeVisibility() {
            regulationRows().forEach(function(row) {
                row.querySelector('.wl-regulation-type-col').style.display = '';
            });
        }

        function findSuperior(name) {
            var key = String(name || '').trim().toLowerCase();
            if (!key) return null;
            return superiors.find(function(s) { return s.name.toLowerCase() === key; }) || null;
        }

        // Jabatan hanya ditimpa bila masih kosong atau sebelumnya hasil isi otomatis.
        function prefillSuperiorPosition() {
            var position = el('wlSuperiorPosition');
            var match = findSuperior(el('wlSuperiorName').value);
            var canOverwrite = position.value.trim() === '' || position.dataset.autofilled === '1';
            if (match && match.position && canOverwrite) {
                position.value = match.position;
                position.dataset.autofilled = '1';
            } else if (position.dataset.autofilled === '1') {
                position.value = '';
                position.dataset.autofilled = '';
            }
        }

        function renderSuperiorOptions() {
            el('wlSuperiorOptions').innerHTML = superiors.map(function(s) {
                return '<option value="' + escapeHtml(s.name) + '">' + escapeHtml(s.position || '') + '</option>';
            }).join('');
        }

        function violationRows() {
            return Array.prototype.slice.call(el('wlViolationRows').querySelectorAll('.wl-violation-row'));
        }

        function addViolationRef(violationRow) {
            violationRow.querySelector('.wl-violation-refs')
                .appendChild(el('wlViolationRefTemplate').content.firstElementChild.cloneNode(true));
        }

        function addViolation() {
            var row = el('wlViolationTemplate').content.firstElementChild.cloneNode(true);
            addViolationRef(row);
            el('wlViolationRows').appendChild(row);
        }

        function updateViolationRows() {
            var rows = violationRows();
            rows.forEach(function(row, index) {
                row.querySelector('.wl-violation-row-title').textContent = 'Pelanggaran ' + (index + 1);
                row.querySelector('.wl-remove-violation').style.display = rows.length > 1 ? '' : 'none';
                row.querySelector('[data-violation-field="description"]').name = 'violations[' + index + '][description]';
                var refs = row.querySelectorAll('.wl-violation-ref');
                refs.forEach(function(ref, refIndex) {
                    ref.querySelector('.wl-violation-ref-title').textContent = 'Pasal ' + (refIndex + 1);
                    ref.querySelector('.wl-remove-violation-ref').style.display = refs.length > 1 ? '' : 'none';
                    ref.querySelector('[data-ref-field="chapter_number"]').required = true;
                    ref.querySelector('[data-ref-field="article_number"]').required = true;
                    initializePpCascade(ref, 'data-ref-field');
                    ref.querySelectorAll('[data-ref-field]').forEach(function(input) {
                        input.name = 'violations[' + index + '][references][' + refIndex + '][' + input.getAttribute('data-ref-field') + ']';
                    });
                });
                row.querySelector('.wl-add-violation-ref').disabled = refs.length >= 5;
            });
            el('wlAddViolation').disabled = rows.length >= 10;
        }

        function resetViolationRows() {
            el('wlViolationRows').innerHTML = '';
            addViolation();
            updateViolationRows();
        }

        function refreshRegulationPreview() {
            var preview = el('wlRegulationPreview');
            preview.innerHTML = '';
            regulationRows().forEach(function(row) {
                var chapter = row.querySelector('[data-regulation-field="chapter_number"]').value.trim();
                var chapterTitle = row.querySelector('[data-regulation-field="chapter_title"]').value.trim();
                var article = row.querySelector('[data-regulation-field="article_number"]').value.trim();
                var articleTitle = row.querySelector('[data-regulation-field="article_title"]').value.trim();
                var paragraph = row.querySelector('[data-regulation-field="paragraph_number"]').value.trim();
                var letter = row.querySelector('[data-regulation-field="article_letter"]').value.trim().toLowerCase();
                var subparagraphNumber = row.querySelector('[data-regulation-field="subparagraph_number"]').value.trim();
                var type = row.querySelector('[data-regulation-field="regulation_type"]').value;
                var parts = [];
                if (chapter) parts.push('Bab ' + chapter + (chapterTitle ? ' (' + chapterTitle + ')' : ''));
                if (article) parts.push('Pasal ' + article + (articleTitle ? ' (' + articleTitle + ')' : ''));
                if (paragraph) parts.push(/^[a-z]$/i.test(paragraph) ? 'huruf ' + paragraph.toLowerCase() : 'ayat (' + paragraph + ')');
                if (letter) parts.push((/^[a-z]$/i.test(paragraph) ? 'subhuruf ' : 'huruf ') + letter);
                if (subparagraphNumber) parts.push('nomor ' + subparagraphNumber);
                if (type) parts.push(type);
                if (!parts.length) return;
                var item = document.createElement('li');
                item.textContent = parts.join(' ');
                preview.appendChild(item);
            });
        }

        function regulationRows() {
            return Array.prototype.slice.call(el('wlRegulationRows').querySelectorAll('.wl-regulation-row'));
        }

        function updateRegulationRows() {
            var rows = regulationRows();
            rows.forEach(function(row, index) {
                row.querySelector('.wl-regulation-row-title').textContent = 'Dasar ketentuan ' + (index + 1);
                var remove = row.querySelector('.wl-remove-regulation');
                remove.style.display = rows.length > 1 ? '' : 'none';
                initializePpCascade(row, 'data-regulation-field');
                row.querySelectorAll('[data-regulation-field]').forEach(function(input) {
                    input.name = 'regulation_references[' + index + '][' + input.getAttribute('data-regulation-field') + ']';
                });
            });
            el('wlAddRegulation').disabled = rows.length >= 10;
            applyRegulationTypeVisibility();
            refreshRegulationPreview();
        }

        function resetRegulationRows() {
            var rows = regulationRows();
            rows.slice(1).forEach(function(row) { row.remove(); });
            regulationRows()[0].querySelectorAll('[data-regulation-field]').forEach(function(input) {
                input.value = '';
            });
            regulationRows()[0].querySelector('[data-pp-field="chapter"]').dispatchEvent(new Event('change'));
            updateRegulationRows();
        }

        function renderDropdown(query) {
            var q = (query || '').toLowerCase().trim();
            var dropdown = el('wlEmpDropdown');
            el('wlEmpSearchClear').style.display = q ? 'block' : 'none';
            if (!q) { dropdown.style.display = 'none'; return; }

            var matched = employees.filter(function(e) {
                return (e.fullName || '').toLowerCase().includes(q)
                    || (e.employeeId || '').toLowerCase().includes(q)
                    || (e.jobPosition || '').toLowerCase().includes(q);
            }).slice(0, 8);

            if (matched.length === 0) {
                dropdown.innerHTML = '<div class="p-3 text-muted text-center" style="font-size:13px">Tidak ada karyawan Contract/Permanent yang cocok</div>';
                dropdown.style.display = 'block';
                return;
            }

            dropdown.innerHTML = matched.map(function(e) {
                return '<div class="p-2 border-bottom d-flex align-items-center gap-2 hover-item wl-search-item" style="cursor:pointer" data-emp-id="' + escapeHtml(e.employeeId) + '">' +
                    '<div style="width:32px;height:32px;background:#7c2d12;color:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">' +
                    escapeHtml((e.fullName || 'E').substring(0, 2).toUpperCase()) + '</div>' +
                    '<div class="flex-grow-1" style="font-size:12.5px">' +
                    '<div class="fw-semibold text-primary">' + escapeHtml(e.fullName || '-') + '</div>' +
                    '<div class="text-muted" style="font-size:11px">' + escapeHtml(e.employeeId || '') + ' &bull; ' +
                    escapeHtml(e.jobPosition || '-') + ' &bull; ' + escapeHtml(e.statusEmployee || '-') +
                    (e.lastSp ? ' &bull; <span class="text-danger">' + escapeHtml(levelLabels[e.lastSp.level] || e.lastSp.level) + '</span>' : '') +
                    '</div></div></div>';
            }).join('');

            dropdown.querySelectorAll('.wl-search-item').forEach(function(item) {
                item.addEventListener('click', function() {
                    var empId = item.getAttribute('data-emp-id');
                    var found = employees.find(function(e) { return e.employeeId === empId; });
                    if (found) selectEmployee(found);
                });
            });
            dropdown.style.display = 'block';
        }

        function selectEmployee(emp) {
            el('wlEmpDropdown').style.display = 'none';
            el('wlEmpSearch').value = emp.fullName + ' (' + emp.employeeId + ')';
            el('wlEmployeeId').value = emp.employeeId;
            el('wlEmpName').textContent = emp.fullName || '-';
            el('wlEmpPosition').textContent = emp.jobPosition || '-';
            el('wlEmpDept').textContent = emp.department || '-';
            el('wlEmpStatus').textContent = emp.statusEmployee || '-';
            el('wlEmpBranch').textContent = emp.branchName || '-';
            el('wlEmpLocation').textContent = emp.lokasiKerja || emp.areaKerja || '-';
            el('wlEmpIdDisp').textContent = emp.employeeId;
            el('wlEmpAvatar').textContent = (emp.fullName || 'E').substring(0, 2).toUpperCase();

            var lastSp = emp.lastSp;
            var level = 'SP1';
            if (lastSp && lastSp.level) {
                var issued = parseYmd(lastSp.issued_at);
                var stillActive = issued && addMonthsNoOverflow(issued, levelMonths(lastSp.level)) > new Date();
                el('wlLastSpText').textContent = 'SP terakhir: ' + (levelLabels[lastSp.level] || lastSp.level) +
                    (lastSp.nomor ? ' — ' + lastSp.nomor : '') + ' (' + fmtId(issued) + ')' +
                    (stillActive ? '. Tingkat berikutnya dipilih otomatis, sesuaikan bila perlu.' : '.');
                el('wlLastSp').style.display = 'block';
                if (stillActive && levelMeta[lastSp.level]) level = levelMeta[lastSp.level].next;
            } else {
                el('wlLastSp').style.display = 'none';
            }
            el('wlLevel').value = level;

            el('wlSuperiorName').value = emp.directSuperior || '';
            el('wlSuperiorPosition').value = '';
            el('wlSuperiorPosition').dataset.autofilled = '';
            prefillSuperiorPosition();

            refreshValidUntil();
            updateLevelFields();
            el('wlErrors').style.display = 'none';
            el('wlEmpPreview').style.display = 'block';
            el('btnGenerateWarningLetter').disabled = false;
            el('btnPreviewWarningLetter').disabled = false;
        }

        function resetModal() {
            var form = el('formWarningLetter');
            if (form) form.reset();
            resetRegulationRows();
            resetViolationRows();
            el('wlSuperiorPosition').dataset.autofilled = '';
            el('wlEmployeeId').value = '';
            el('wlEmpSearch').value = '';
            el('wlEmpDropdown').style.display = 'none';
            el('wlEmpSearchClear').style.display = 'none';
            el('wlEmpPreview').style.display = 'none';
            el('wlErrors').style.display = 'none';
            el('wlDescCount').textContent = '0';
            updateLevelFields();
            el('btnGenerateWarningLetter').disabled = true;
            el('btnPreviewWarningLetter').disabled = true;
        }

        function showErrors(messages) {
            var box = el('wlErrors');
            box.innerHTML = messages.map(function(m) { return '<div>' + escapeHtml(m) + '</div>'; }).join('');
            box.style.display = 'block';
            box.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function downloadPdf(url) {
            if (typeof window.mitoFinishPageLoader === 'function') window.mitoFinishPageLoader();
            var iframe = document.createElement('iframe');
            iframe.style.cssText = 'display:none;width:0;height:0;border:0';
            iframe.src = url;
            document.body.appendChild(iframe);
            var remove = function() { try { document.body.removeChild(iframe); } catch (_) {} };
            iframe.addEventListener('load', function() { setTimeout(remove, 1000); });
            setTimeout(remove, 4000);
        }

        function todayStamp() {
            var today = new Date();
            var month = String(today.getMonth() + 1).padStart(2, '0');
            var day = String(today.getDate()).padStart(2, '0');
            return today.getFullYear() + '-' + month + '-' + day;
        }

        function employeeRow(empId) {
            var row = null;
            document.querySelectorAll('#empTableBody tr[data-drawer-id]').forEach(function(tr) {
                if (tr.getAttribute('data-drawer-id') === empId) row = tr;
            });
            return row;
        }

        function bumpWarningLetterFilter() {
            var option = document.querySelector('#empStatusFilter option[value="warning_letter"]');
            if (!option) return;
            var match = option.textContent.match(/\((\d+)\)/);
            var count = match ? Number(match[1]) + 1 : 1;
            option.textContent = 'Surat Peringatan (' + count + ')';
        }

        function refreshEmployeeSanction(empId, data, letterCount, replaceLabel) {
            var row = employeeRow(empId);
            var nameCell = row ? row.children[2] : null;
            if (!nameCell) return;

            var extra = Math.max(0, letterCount - 1);
            var showNew = replaceLabel && String(data.valid_until || '') >= todayStamp();
            var badge = nameCell.querySelector('.sp-sanction');
            var wrap = badge ? badge.parentElement : null;
            if (!badge && !showNew) return;

            if (!wrap) {
                wrap = document.createElement('div');
                wrap.className = 'mt-1 d-inline-flex align-items-center gap-1';
                nameCell.appendChild(wrap);
            }
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'badge-status blacklist sp-sanction';
                badge.title = 'Sanksi Surat Peringatan masih berlaku';
                wrap.appendChild(badge);
            }
            if (showNew) {
                badge.textContent = (data.short_label || levelLabels[data.level] || data.level) +
                    ' · s.d. ' + (data.valid_until_label || '');
            }

            var more = wrap.querySelector('.sp-sanction-more');
            if (extra > 0) {
                if (!more) {
                    more = document.createElement('span');
                    more.className = 'sp-sanction-more fw-bold text-danger';
                    wrap.appendChild(more);
                }
                more.textContent = '+' + extra;
                more.title = 'Ada ' + extra + ' Surat Peringatan lain';
                more.setAttribute('aria-label', more.title);
            } else if (more) {
                more.remove();
            }
        }

        function rememberIssuedLetter(empId, data) {
            if (!data || !data.level) return;
            var found = employees.find(function(employee) { return employee.employeeId === empId; });
            var previousCount = found ? (Number(found.spCount) || 0) : 0;
            var previousAt = found && found.lastSp && found.lastSp.issued_at
                ? String(found.lastSp.issued_at).slice(0, 10) : '';
            var incomingAt = String(data.issued_at || '').slice(0, 10);
            if (found) {
                if (!found.lastSp || incomingAt >= previousAt) {
                    found.lastSp = {
                        level: data.level,
                        issued_at: incomingAt,
                        nomor: data.nomor || ''
                    };
                }
                found.spCount = previousCount + 1;
            }
            if (previousCount === 0) bumpWarningLetterFilter();
            refreshEmployeeSanction(empId, data, previousCount + 1, !previousAt || incomingAt >= previousAt);
        }

        document.addEventListener('DOMContentLoaded', function() {
            var form = el('formWarningLetter');
            var btn = el('btnGenerateWarningLetter');
            if (!form || !btn) return;
            var submitting = false;

            el('wlEmpSearch').addEventListener('input', function(e) { renderDropdown(e.target.value); });
            el('wlEmpSearchClear').addEventListener('click', resetModal);
            el('wlDocDate').addEventListener('change', refreshValidUntil);
            el('wlLevel').addEventListener('change', function() { updateLevelFields(); refreshValidUntil(); });
            el('wlDescription').addEventListener('input', function(e) { el('wlDescCount').textContent = e.target.value.length; });
            el('wlRegulationRows').addEventListener('input', refreshRegulationPreview);
            el('wlRegulationRows').addEventListener('change', refreshRegulationPreview);
            el('wlRegulationRows').addEventListener('click', function(e) {
                var remove = e.target.closest('.wl-remove-regulation');
                if (!remove || regulationRows().length < 2) return;
                remove.closest('.wl-regulation-row').remove();
                updateRegulationRows();
            });
            el('wlAddRegulation').addEventListener('click', function() {
                var rows = regulationRows();
                if (rows.length >= 10) return;
                el('wlRegulationRows').appendChild(el('wlRegulationRowTemplate').content.firstElementChild.cloneNode(true));
                updateRegulationRows();
            });
            el('wlAddViolation').addEventListener('click', function() {
                if (violationRows().length >= 10) return;
                addViolation();
                updateViolationRows();
            });
            el('wlViolationRows').addEventListener('click', function(e) {
                var removeViolation = e.target.closest('.wl-remove-violation');
                if (removeViolation && violationRows().length > 1) {
                    removeViolation.closest('.wl-violation-row').remove();
                    updateViolationRows();
                    return;
                }
                var removeRef = e.target.closest('.wl-remove-violation-ref');
                if (removeRef && removeRef.closest('.wl-violation-refs').children.length > 1) {
                    removeRef.closest('.wl-violation-ref').remove();
                    updateViolationRows();
                    return;
                }
                var addRef = e.target.closest('.wl-add-violation-ref');
                if (addRef && addRef.closest('.wl-violation-row').querySelectorAll('.wl-violation-ref').length < 5) {
                    addViolationRef(addRef.closest('.wl-violation-row'));
                    updateViolationRows();
                }
            });
            el('wlSuperiorName').addEventListener('input', prefillSuperiorPosition);
            el('wlSuperiorName').addEventListener('change', prefillSuperiorPosition);
            el('wlSuperiorPosition').addEventListener('input', function(e) { e.target.dataset.autofilled = ''; });
            el('warningLetterModal').addEventListener('hidden.bs.modal', resetModal);
            renderSuperiorOptions();
            resetViolationRows();
            updateLevelFields();
            updateRegulationRows();

            function requestHeaders() {
                return {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
                };
            }

            function responseErrors(data, fallback) {
                return data && data.errors
                    ? Object.values(data.errors).flat()
                    : [(data && data.message) || fallback];
            }

            el('btnPreviewWarningLetter').addEventListener('click', function() {
                if (submitting || !prepareSubmission()) return;
                var previewBtn = el('btnPreviewWarningLetter');
                // Jendela dibuka saat klik agar tidak diblokir popup blocker; URL diisi setelah draft siap.
                var previewWin = window.open('', '_blank');
                var origHtml = previewBtn.innerHTML;
                submitting = true;
                previewBtn.disabled = true;
                previewBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyiapkan...';

                fetch(previewUrlTemplate.replace('__ID__', encodeURIComponent(el('wlEmployeeId').value)), {
                    method: 'POST',
                    headers: requestHeaders(),
                    body: new FormData(form)
                })
                    .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
                    .then(function(res) {
                        if (res.ok && res.data.success && res.data.preview_url) {
                            el('wlErrors').style.display = 'none';
                            if (previewWin) previewWin.location.href = res.data.preview_url;
                            else window.location.assign(res.data.preview_url);
                            return;
                        }
                        if (previewWin) previewWin.close();
                        showErrors(responseErrors(res.data, 'Gagal membuat pratinjau Surat Peringatan.'));
                    })
                    .catch(function(err) {
                        if (previewWin) previewWin.close();
                        showErrors(['Error: ' + (err && err.message ? err.message : 'Network error')]);
                    })
                    .then(function() {
                        submitting = false;
                        previewBtn.disabled = false;
                        previewBtn.innerHTML = origHtml;
                    });
            });

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (submitting || !prepareSubmission()) return;
                var empId = el('wlEmployeeId').value;

                submitting = true;
                var origHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...';

                fetch(storeUrlTemplate.replace('__ID__', encodeURIComponent(empId)), {
                    method: 'POST',
                    headers: requestHeaders(),
                    body: new FormData(form)
                })
                    .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
                    .then(function(res) {
                        submitting = false;
                        btn.disabled = false;
                        btn.innerHTML = origHtml;

                        if (res.ok && res.data.success) {
                            rememberIssuedLetter(empId, res.data);
                            if (res.data.pdf_url) downloadPdf(res.data.pdf_url);
                            showToast(res.data.message || 'Surat Peringatan berhasil diterbitkan.', 'success', 5000);
                            var modal = bootstrap.Modal.getInstance(el('warningLetterModal'));
                            if (modal) modal.hide();
                            return;
                        }

                        showErrors(responseErrors(res.data, 'Gagal menerbitkan Surat Peringatan.'));
                    })
                    .catch(function(err) {
                        submitting = false;
                        btn.disabled = false;
                        btn.innerHTML = origHtml;
                        showErrors(['Error: ' + (err && err.message ? err.message : 'Network error')]);
                    });
            });

            function prepareSubmission() {
                var missing = [];
                if (!el('wlEmployeeId').value) missing.push('Pilih karyawan terlebih dahulu.');
                if (!el('wlDocDate').value) missing.push('Tanggal surat wajib diisi.');
                violationRows().forEach(function(row, index) {
                    var position = index + 1;
                    if (row.querySelector('[data-violation-field="description"]').value.trim().length < 10) {
                        missing.push('Uraian pelanggaran ke-' + position + ' minimal 10 karakter.');
                    }
                    row.querySelectorAll('.wl-violation-ref').forEach(function(ref, refIndex) {
                        var clause = refIndex + 1;
                        if (!ref.querySelector('[data-ref-field="chapter_number"]').value) {
                            missing.push('Pilih bab pada pelanggaran ke-' + position + ', pasal ke-' + clause + '.');
                        }
                        if (!ref.querySelector('[data-ref-field="article_number"]').value) {
                            missing.push('Pilih pasal pada pelanggaran ke-' + position + ', pasal ke-' + clause + '.');
                        }
                    });
                });
                if (!el('wlSuperiorName').value.trim()) missing.push('Nama atasan wajib diisi.');
                if (!el('wlSuperiorPosition').value.trim()) missing.push('Jabatan atasan wajib diisi.');
                if (missing.length) { showErrors(missing); return false; }

                return true;
            }
        });
    })();
</script>
