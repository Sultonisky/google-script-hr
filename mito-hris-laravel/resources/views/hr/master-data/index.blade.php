@extends('layouts.hr')

@section('title', 'Master Data - MITO HRIS')
@section('page-title', 'Master Data')
@section('page-subtitle', 'Kelola data referensi posisi, cabang, divisi, departemen, dan opsi sistem')

@section('content')
<!-- partials/MasterDataContent.html — MASTER DATA PAGE CONTENT (1:1 from GAS) -->
<section class="page-section active" id="pageMasterData">

  <!-- Summary Cards (1:1 from GAS) -->
  <div class="md-card" id="mdSummaryCard">
    <div class="md-card-body">
      <div class="md-summary" id="mdSummaryGrid">
        <div class="md-summary-card">
          <i class="bi bi-briefcase"></i>
          <div class="count">{{ count($positions ?? []) }}</div>
          <div class="label">Job Positions</div>
        </div>
        <div class="md-summary-card">
          <i class="bi bi-building"></i>
          <div class="count">{{ count($branches ?? []) }}</div>
          <div class="label">Branches</div>
        </div>
        <div class="md-summary-card">
          <i class="bi bi-diagram-3"></i>
          <div class="count">{{ count($departments ?? []) }}</div>
          <div class="label">Departments</div>
        </div>
        <div class="md-summary-card">
          <i class="bi bi-geo-alt"></i>
          <div class="count">{{ count($cities ?? []) }}</div>
          <div class="label">Work Locations</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Table Card (1:1 from GAS) -->
  <div class="md-card">
    <div class="md-card-header d-flex justify-content-between align-items-center">
      <h6><i class="bi bi-list-ul"></i> <span id="mdCurrentCategoryLabel">Data Master {{ ucfirst(request('cat', 'positions')) }}</span></h6>
      <div class="d-flex gap-2">
        <button class="btn btn-sm text-white fw-semibold" style="background:#166534;border-radius:8px;padding:6px 14px;font-size:13px" type="button" data-bs-toggle="modal" data-bs-target="#empImportModal">
          <i class="bi bi-upload me-1"></i> Import CSV/Excel
        </button>
        <button class="btn btn-sm text-white fw-semibold" style="background:var(--color-primary);border-radius:8px;padding:6px 14px;font-size:13px" type="button" data-bs-toggle="modal" data-bs-target="#addMasterModal">
          <i class="bi bi-plus-lg me-1"></i> Tambah Item
        </button>
      </div>
    </div>

    <!-- Category Nav (1:1 from GAS) -->
    <div class="md-cat-nav" id="mdCatNav">
      <a href="{{ route('hr.master-data.index', ['cat' => 'positions']) }}" class="md-cat-btn {{ request('cat', 'positions') === 'positions' ? 'active' : '' }}">
        <i class="bi bi-briefcase"></i> Posisi Jabatan
      </a>
      <a href="{{ route('hr.master-data.index', ['cat' => 'branches']) }}" class="md-cat-btn {{ request('cat') === 'branches' ? 'active' : '' }}">
        <i class="bi bi-building"></i> Cabang
      </a>
      <a href="{{ route('hr.master-data.index', ['cat' => 'departments']) }}" class="md-cat-btn {{ request('cat') === 'departments' ? 'active' : '' }}">
        <i class="bi bi-diagram-3"></i> Departemen
      </a>
    </div>

    <!-- Toolbar -->
    <div class="md-toolbar">
      <div class="md-search">
        <i class="bi bi-search"></i>
        <input type="text" id="mdSearch" placeholder="Cari item..." autocomplete="off" />
      </div>
      <div class="text-muted small" id="mdCountInfo">{{ count($items ?? []) }} item</div>
    </div>

    <!-- Table (1:1 from GAS: #, Nama, Deskripsi, Kategori, Aksi) -->
    <div class="md-card-body">
      <div style="overflow-x:auto;">
        <table class="md-table" id="mdTable">
          <thead>
            <tr>
              <th style="width:50px;">#</th>
              <th>Nama</th>
              <th>Deskripsi</th>
              <th>Kategori</th>
              <th style="width:80px;" class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody id="mdTableBody">
            @forelse($items ?? [] as $index => $item)
              @php
                $itemName = is_array($item) ? ($item['name'] ?? '-') : $item;
                $itemDesc = is_array($item) ? ($item['description'] ?? '-') : 'Referensi Master ' . ucfirst(request('cat', 'positions'));
              @endphp
              <tr>
                <td><span class="order-num">{{ $index + 1 }}</span></td>
                <td class="fw-semibold text-navy item-name">{{ $itemName }}</td>
                <td class="text-muted item-desc" style="font-size:12.5px">{{ $itemDesc }}</td>
                <td><span class="cat-badge">{{ strtoupper(request('cat', 'positions')) }}</span></td>
                <td class="text-end">
                  <button class="btn btn-sm btn-light border text-danger" title="Hapus Item" data-master-delete-warning="true">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5">
                  <div class="md-empty">
                    <i class="bi bi-inbox"></i>
                    <div>Belum ada data ditemukan.</div>
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

</section>

<!-- Modal Add Master Data (1:1 from GAS) -->
<div class="modal fade" id="addMasterModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px">
      <div class="modal-header" style="background:#0B2540;color:#fff;border-radius:16px 16px 0 0">
        <h6 class="modal-title fw-bold">Tambah Item Master Data</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('hr.master-data.store') }}" method="POST">
        @csrf
        <div class="modal-body p-4">
          <input type="hidden" name="category" value="{{ request('cat', 'positions') }}" />
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:13px">Kategori</label>
            <input type="text" class="form-control form-control-sm" value="{{ strtoupper(request('cat', 'positions')) }}" readonly disabled />
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:13px">Nama Item <span class="text-danger">*</span></label>
            <input type="text" class="form-control form-control-sm" name="name" placeholder="Contoh: Senior Software Engineer / Cabang Bali" required />
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:13px">Deskripsi / Keterangan</label>
            <textarea class="form-control form-control-sm" name="description" rows="2" placeholder="Keterangan opsional untuk opsi master..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm text-white" style="background:var(--color-primary)">
            <i class="bi bi-check2 me-1"></i> Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
