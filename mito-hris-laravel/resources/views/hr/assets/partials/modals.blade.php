{{-- ═══ ADD / EDIT ASSET MODAL ═══ --}}
<div class="modal fade" id="addAssetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="addAssetModalLabel">Tambah Aset Baru</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="assetForm">
                @csrf
                <input type="hidden" name="_method" id="assetFormMethod" value="POST">
                <input type="hidden" name="asset_id" id="assetFormId" value="">
                <div class="modal-body">

                    <div class="asset-section-title"><i class="bi bi-info-circle"></i> Informasi Umum</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Kode Aset</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="asset_code" id="assetFormCode"
                                    placeholder="Kosongkan untuk auto-generate" maxlength="50">
                                <button class="btn btn-outline-info" type="button" id="btnFormGenerateCode">
                                    <i class="bi bi-magic"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Kategori *</label>
                            <select class="form-select" name="category" id="assetFormCategory" required>
                                <option value="">-- Pilih Kategori --</option>
                                @foreach(\App\Enums\AssetCategory::cases() as $cat)
                                    <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nama Aset *</label>
                            <input type="text" class="form-control" name="name" id="assetFormName" required maxlength="255">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Deskripsi</label>
                            <textarea class="form-control" name="description" id="assetFormDesc" rows="2" maxlength="1000"></textarea>
                        </div>
                    </div>

                    <div class="asset-section-title mt-3"><i class="bi bi-receipt"></i> Informasi Pembelian</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6"><label class="form-label fw-semibold">Tanggal Pembelian</label>
                            <input type="date" class="form-control" name="purchase_date" id="assetFormPurchDate"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Harga Pembelian (Rp)</label>
                            <input type="number" class="form-control" name="purchase_price" id="assetFormPurchPrice" min="0" step="0.01"></div>
                    </div>

                    <div class="asset-section-title mt-3"><i class="bi bi-clipboard-check"></i> Kondisi & Status</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4"><label class="form-label fw-semibold">Kondisi</label>
                            <select class="form-select" name="condition_status" id="assetFormCondition">
                                @foreach(\App\Enums\AssetCondition::cases() as $c)
                                    <option value="{{ $c->value }}">{{ $c->label() }}</option>
                                @endforeach
                            </select></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Status</label>
                            <select class="form-select" name="status" id="assetFormStatus">
                                @foreach(\App\Enums\AssetStatus::cases() as $s)
                                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                                @endforeach
                            </select></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Lokasi</label>
                            <input type="text" class="form-control" name="location" id="assetFormLocation" maxlength="255">
                        </div>
                    </div>

                    {{-- ═══ CATEGORY-SPECIFIC FIELDS ═══ --}}
                    <div class="asset-section-title mt-3"><i class="bi bi-tags"></i> Detail Kategori</div>
                    <div class="text-muted small mb-2" id="assetCatHint">Pilih kategori terlebih dahulu untuk menampilkan kolom detail.</div>

                    {{-- Building / Property --}}
                    <div class="category-fields" data-category="Building" style="display:none;">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4"><label class="form-label fw-semibold">Tipe Properti</label>
                                <select class="form-select" name="property_type">
                                    <option value="">-- Pilih --</option>
                                    @foreach(\App\Enums\AssetCategory::fieldOptions('property_type') as $opt)
                                        <option value="{{ $opt }}">{{ $opt }}</option>
                                    @endforeach
                                </select></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Status Kepemilikan <span class="text-danger">*</span></label>
                                <select class="form-select" name="ownership_status" required>
                                    <option value="">-- Pilih --</option>
                                    @foreach(\App\Enums\AssetCategory::fieldOptions('ownership_status') as $opt)
                                        <option value="{{ $opt }}">{{ $opt }}</option>
                                    @endforeach
                                </select></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Jumlah Lantai</label>
                                <input type="number" class="form-control" name="floors" min="1" max="500"></div>
                            <div class="col-md-8"><label class="form-label fw-semibold">Alamat</label>
                                <input type="text" class="form-control" name="address" maxlength="500"
                                    placeholder="Alamat lengkap gedung / properti"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Luas Bangunan (m²)</label>
                                <input type="number" class="form-control" name="area_sqm" min="0" step="0.01"></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Luas Tanah (m²)</label>
                                <input type="number" class="form-control" name="land_area_sqm" min="0" step="0.01"></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Nomor Sertifikat</label>
                                <input type="text" class="form-control" name="certificate_number" maxlength="120"></div>
                            <div class="col-12"><label class="form-label fw-semibold">Jadwal Pemeliharaan</label>
                                <input type="text" class="form-control" name="maintenance_schedule" maxlength="255"
                                    placeholder="Cth: AC service tiap 6 bulan"></div>
                        </div>
                    </div>
                    {{-- Vehicle --}}
                    <div class="category-fields" data-category="Vehicle" style="display:none;">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4"><label class="form-label fw-semibold">Brand / Merek</label>
                                <input type="text" class="form-control" name="brand" maxlength="255" placeholder="Cth: Toyota"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Model</label>
                                <input type="text" class="form-control" name="model" maxlength="255" placeholder="Cth: Hilux Double Cabin"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Jenis Kendaraan</label>
                                <select class="form-select" name="vehicle_type">
                                    <option value="">-- Pilih --</option>
                                    @foreach(\App\Enums\AssetCategory::fieldOptions('vehicle_type') as $opt)
                                        <option value="{{ $opt }}">{{ $opt }}</option>
                                    @endforeach
                                </select></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Plat Nomor</label>
                                <input type="text" class="form-control" name="license_plate" maxlength="20"
                                    placeholder="Cth: B 1234 XYZ"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Tahun</label>
                                <input type="number" class="form-control" name="year" min="1900" max="{{ date('Y') + 1 }}"></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">VIN / No. Rangka</label>
                                <input type="text" class="form-control" name="vin" maxlength="100"></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">No. Mesin</label>
                                <input type="text" class="form-control" name="engine_number" maxlength="100"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Warna</label>
                                <input type="text" class="form-control" name="color" maxlength="50"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Bahan Bakar</label>
                                <select class="form-select" name="fuel_type">
                                    <option value="">-- Pilih --</option>
                                    @foreach(\App\Enums\AssetCategory::fieldOptions('fuel_type') as $opt)
                                        <option value="{{ $opt }}">{{ $opt }}</option>
                                    @endforeach
                                </select></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Transmisi</label>
                                <select class="form-select" name="transmission">
                                    <option value="">-- Pilih --</option>
                                    @foreach(\App\Enums\AssetCategory::fieldOptions('transmission') as $opt)
                                        <option value="{{ $opt }}">{{ $opt }}</option>
                                    @endforeach
                                </select></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">No. STNK</label>
                                <input type="text" class="form-control" name="stnk_number" maxlength="50"></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">No. BPKB</label>
                                <input type="text" class="form-control" name="bpkb_number" maxlength="50"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Masa Berlaku STNK</label>
                                <input type="date" class="form-control" name="stnk_expiry"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Service Terakhir</label>
                                <input type="date" class="form-control" name="last_service_date"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Service Berikutnya</label>
                                <input type="date" class="form-control" name="next_service_date"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Kilometer (km)</label>
                                <input type="number" class="form-control" name="mileage" min="0" max="10000000"></div>
                        </div>
                    </div>

                    {{-- Office Equipment --}}
                    <div class="category-fields" data-category="Office" style="display:none;">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4"><label class="form-label fw-semibold">Brand / Merek</label>
                                <input type="text" class="form-control" name="brand" maxlength="255" placeholder="Opsional"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Model</label>
                                <input type="text" class="form-control" name="model" maxlength="255" placeholder="Opsional"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Serial Number</label>
                                <input type="text" class="form-control" name="serial_number" maxlength="255" placeholder="Opsional"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Jenis Peralatan</label>
                                <select class="form-select" name="equipment_type">
                                    <option value="">-- Pilih --</option>
                                    @foreach(\App\Enums\AssetCategory::fieldOptions('equipment_type') as $opt)
                                        <option value="{{ $opt }}">{{ $opt }}</option>
                                    @endforeach
                                </select></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Supplier / Vendor</label>
                                <input type="text" class="form-control" name="supplier" maxlength="255"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Garansi Pembelian</label>
                                <input type="text" class="form-control" name="purchase_warranty" maxlength="120"
                                    placeholder="Cth: 1 Year"></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Garansi Mulai</label>
                                <input type="date" class="form-control" name="warranty_start"></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Garansi Berakhir</label>
                                <input type="date" class="form-control" name="warranty_expiry"></div>
                            <div class="col-12"><label class="form-label fw-semibold">Jadwal Pemeliharaan</label>
                                <input type="text" class="form-control" name="maintenance_schedule" maxlength="255"></div>
                        </div>
                    </div>

                    {{-- Elektronik / IT --}}
                    <div class="category-fields" data-category="Elektronik" style="display:none;">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4"><label class="form-label fw-semibold">Brand / Merek</label>
                                <input type="text" class="form-control" name="brand" maxlength="255" placeholder="Cth: Dell"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Model</label>
                                <input type="text" class="form-control" name="model" maxlength="255" placeholder="Cth: Latitude 5440"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Serial Number</label>
                                <input type="text" class="form-control" name="serial_number" maxlength="255" placeholder="Cth: SN-2024-000123"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Jenis Perangkat</label>
                                <select class="form-select" name="device_type">
                                    <option value="">-- Pilih --</option>
                                    @foreach(\App\Enums\AssetCategory::fieldOptions('device_type') as $opt)
                                        <option value="{{ $opt }}">{{ $opt }}</option>
                                    @endforeach
                                </select></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Prosesor</label>
                                <input type="text" class="form-control" name="processor" maxlength="120"
                                    placeholder="Cth: Intel Core i5-1240P"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Sistem Operasi</label>
                                <input type="text" class="form-control" name="operating_system" maxlength="120"
                                    placeholder="Cth: Windows 11 Pro"></div>
                            <div class="col-md-3"><label class="form-label fw-semibold">RAM</label>
                                <input type="text" class="form-control" name="ram" maxlength="50" placeholder="Cth: 16 GB"></div>
                            <div class="col-md-3"><label class="form-label fw-semibold">Penyimpanan</label>
                                <input type="text" class="form-control" name="storage" maxlength="50" placeholder="Cth: 512 GB"></div>
                            <div class="col-md-3"><label class="form-label fw-semibold">Tipe Penyimpanan</label>
                                <select class="form-select" name="storage_type">
                                    <option value="">-- Pilih --</option>
                                    @foreach(\App\Enums\AssetCategory::fieldOptions('storage_type') as $opt)
                                        <option value="{{ $opt }}">{{ $opt }}</option>
                                    @endforeach
                                </select></div>
                            <div class="col-md-3"><label class="form-label fw-semibold">Hostname</label>
                                <input type="text" class="form-control" name="hostname" maxlength="120"></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">MAC Address</label>
                                <input type="text" class="form-control" name="mac_address" maxlength="30"
                                    placeholder="AA:BB:CC:DD:EE:FF"></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">IP Address</label>
                                <input type="text" class="form-control" name="ip_address" maxlength="45"
                                    placeholder="Cth: 192.168.1.10"></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Garansi Mulai</label>
                                <input type="date" class="form-control" name="warranty_start"></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Garansi Berakhir</label>
                                <input type="date" class="form-control" name="warranty_expiry"></div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3 mt-2">
                        <div class="col-12"><label class="form-label fw-semibold">Catatan</label>
                            <textarea class="form-control" name="notes" id="assetFormNotes" rows="2" maxlength="2000"></textarea></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn text-white fw-semibold" style="background:var(--color-primary,#eb1c24);" id="btnAssetFormSubmit">
                        <i class="bi bi-check2-circle me-1"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


{{-- ═══ ASSIGN ASSET MODAL ═══ --}}
<div class="modal fade" id="assignAssetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-person-plus me-1"></i>Assign Aset</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="assignAssetForm" novalidate>
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Asset</label>
                        <input type="text" class="form-control" id="assignAssetDisplay" readonly>
                        <input type="hidden" name="asset_id" id="assignAssetId">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cari Karyawan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="assignEmployeeSearch" autocomplete="off"
                            placeholder="Ketik nama / Employee ID karyawan...">
                        <input type="hidden" name="employee_id" id="assignEmpId">
                        <input type="hidden" name="employee_name" id="assignEmpName">
                        <div class="list-group mt-2 d-none" id="assignEmployeeResults"
                            style="max-height:200px;overflow:auto;position:absolute;z-index:1050;width:calc(100% - 2rem);"></div>
                        <div id="assignEmployeeError" class="text-danger small mt-1 d-none"></div>
                        <div id="assignEmployeeSelected" class="d-none mt-2"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tanggal Penugasan <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="assigned_date" id="assignDate" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Catatan</label>
                        <textarea class="form-control" name="notes" id="assignNotes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn text-white fw-semibold" style="background:#166534;">
                        <i class="bi bi-check2-circle me-1"></i>Assign
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══ RETURN ASSET MODAL ═══ --}}
<div class="modal fade" id="returnAssetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-arrow-return-left me-1"></i>Kembalikan Aset</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="returnAssetForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Asset</label>
                        <input type="text" class="form-control" id="returnAssetDisplay" readonly>
                        <input type="hidden" name="asset_id" id="returnAssetId">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ditugaskan Kepada</label>
                        <input type="text" class="form-control" id="returnAssignedTo" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tanggal Pengembalian <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="return_date" id="returnDate" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Catatan</label>
                        <textarea class="form-control" name="return_notes" id="returnNotes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn text-white fw-semibold" style="background:#d97706;">
                        <i class="bi bi-arrow-return-left me-1"></i>Kembalikan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


{{-- ═══ BULK GENERATE CODES MODAL ═══ --}}
<div class="modal fade" id="bulkGenerateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-magic me-1"></i>Generate Asset Codes</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Generate kode aset otomatis untuk semua aset yang belum memiliki kode.</p>
                <div id="bulkGenSummary" class="mb-3" style="display:none;">
                    <div class="alert alert-info mb-0">
                        <strong>Aset tanpa kode: <span id="bulkGenTotal">0</span></strong>
                        <ul class="mb-0 mt-1" id="bulkGenCategories"></ul>
                    </div>
                </div>
                <div id="bulkGenResult" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn text-white fw-semibold" style="background:#0B2540;" id="btnBulkGenExecute">
                    <i class="bi bi-magic me-1"></i>Generate Sekarang
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ═══ VIEW ASSET DETAIL MODAL ═══ --}}
<div class="modal fade" id="viewAssetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-eye me-1"></i>Detail Aset</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewAssetBody">
                <div class="text-center py-4"><i class="bi bi-arrow-repeat"></i> Memuat data...</div>
            </div>
        </div>
    </div>
</div>

{{-- DISPOSE ASSET CONFIRMATION MODAL --}}
<div class="modal fade" id="disposeAssetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title text-danger"><i class="bi bi-trash me-1"></i>Dispose Aset</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Tindakan ini mengubah status aset menjadi <strong>Disposed</strong>. Aset tidak akan dihapus permanen dari database, namun tidak lagi muncul sebagai aset aktif dan tidak dapat di-assign.</p>
                <div class="border rounded p-3 bg-light">
                    <div class="row g-2">
                        <div class="col-4 text-muted small">Kode Aset</div>
                        <div class="col-8 fw-semibold" id="disposeAssetCode">—</div>
                        <div class="col-4 text-muted small">Nama</div>
                        <div class="col-8 fw-semibold" id="disposeAssetName">—</div>
                        <div class="col-4 text-muted small">Kategori</div>
                        <div class="col-8" id="disposeAssetCategory">—</div>
                    </div>
                </div>
                <input type="hidden" id="disposeAssetId">
                <div id="disposeAssetError" class="alert alert-danger mt-3 mb-0" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="btnDisposeConfirm"><i class="bi bi-trash me-1"></i>Dispose Aset</button>
            </div>
        </div>
    </div>
</div>

