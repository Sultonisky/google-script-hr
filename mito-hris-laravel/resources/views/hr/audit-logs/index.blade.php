@extends('layouts.hr')

@section('title', 'Audit Log - MITO HRIS')
@section('page-title', 'Audit Log')
@section('page-subtitle', 'Riwayat aktivitas semua modul sistem')

@section('content')
<!-- partials/AuditLogPage.html — AUDIT LOG PAGE SECTION (1:1 from GAS) -->
<section class="page-section active" id="pageAuditLog">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h5 class="fw-bold mb-0">
        <i class="bi bi-clock-history me-2"></i>Audit Log
      </h5>
      <small class="text-muted">Riwayat aktivitas seluruh modul sistem</small>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-primary btn-sm" id="btnAuditRefresh" type="button" onclick="location.reload()">
        <i class="bi bi-arrow-clockwise"></i> Refresh
      </button>
    </div>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="stat-icon bg-blue"><i class="bi bi-list-ul"></i></div>
        <div>
          <div class="stat-label">Total Log</div>
          <div class="stat-value" id="auditStatTotal">{{ $logs->count() }}</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="stat-icon bg-green">
          <i class="bi bi-check-circle-fill"></i>
        </div>
        <div>
          <div class="stat-label">Created</div>
          <div class="stat-value" id="auditStatCreated">{{ $stats['created'] ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="stat-icon bg-gold"><i class="bi bi-arrow-repeat"></i></div>
        <div>
          <div class="stat-label">Update Status</div>
          <div class="stat-value" id="auditStatUpdate">{{ $stats['update'] ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="stat-icon bg-navy">
          <i class="bi bi-pause-circle-fill"></i>
        </div>
        <div>
          <div class="stat-label">Hold/Blacklist</div>
          <div class="stat-value" id="auditStatHoldBl">{{ $stats['hold_bl'] ?? 0 }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filter bar -->
  <div class="panel mb-4">
    <div class="panel-header">
      <form action="{{ route('hr.audit-logs.index') }}" method="GET" class="d-flex flex-wrap gap-2 align-items-center w-100">
        <div class="table-search" style="flex:1;">
          <i class="bi bi-search"></i>
          <input type="text" name="search" id="auditSearchInput" class="form-control" placeholder="Cari entity, ID, user, action..." value="{{ request('search') }}" />
        </div>
        <select class="filter-select" name="entity_type" onchange="this.form.submit()" style="width: 140px; font-size: 13px">
          <option value="">Semua Entity</option>
          @foreach(['Candidate', 'Employee', 'Probation', 'Outsource', 'MPR', 'User', 'Settings', 'MasterData', 'Applicant', 'Authentication', 'System'] as $entity)
            <option value="{{ $entity }}" {{ request('entity_type') === $entity ? 'selected' : '' }}>{{ $entity }}</option>
          @endforeach
        </select>
        <select class="filter-select" name="source" onchange="this.form.submit()" style="width: 130px; font-size: 13px">
          <option value="">Semua Source</option>
          @foreach(['Dashboard', 'Public', 'Artisan', 'Google Sheets', 'System', 'Legacy'] as $source)
            <option value="{{ $source }}" {{ request('source') === $source ? 'selected' : '' }}>{{ $source }}</option>
          @endforeach
        </select>
        <input type="text" name="entity_id" class="form-control" placeholder="Entity ID" value="{{ request('entity_id') }}" style="width: 140px; font-size: 13px" />
        <select class="filter-select" name="action" id="auditActionFilter" onchange="this.form.submit()" style="width: 160px; font-size: 13px">
          <option value="">Semua Action</option>
          <option value="APPLY" {{ request('action') === 'APPLY' ? 'selected' : '' }}>APPLY</option>
          <option value="UPDATE_STATUS" {{ request('action') === 'UPDATE_STATUS' ? 'selected' : '' }}>UPDATE_STATUS</option>
          <option value="HIRED_TO_EMPLOYEE" {{ request('action') === 'HIRED_TO_EMPLOYEE' ? 'selected' : '' }}>HIRED_TO_EMPLOYEE</option>
          <option value="HOLD" {{ request('action') === 'HOLD' ? 'selected' : '' }}>HOLD</option>
          <option value="BLACKLIST" {{ request('action') === 'BLACKLIST' ? 'selected' : '' }}>BLACKLIST</option>
        </select>
        <a href="{{ route('hr.audit-logs.index') }}" class="btn btn-outline-secondary btn-sm" id="btnAuditReset">
          <i class="bi bi-arrow-counterclockwise"></i> Reset
        </a>
      </form>
    </div>
  </div>

  <!-- Table -->
  <div class="panel">
    <div class="table-responsive">
      <table class="table hr-table">
        <thead>
          <tr>
            <th>Timestamp</th>
            <th>User</th>
            <th>Entity</th>
            <th>Entity ID</th>
            <th>Source</th>
            <th>Action</th>
            <th>Field</th>
            <th>Old Value</th>
            <th>New Value</th>
          </tr>
        </thead>
        <tbody id="auditTableBody">
          @forelse($logs as $log)
            <tr>
              <td class="id-mono">{{ $log['Timestamp'] ?? '-' }}</td>
              <td class="fw-semibold text-navy">{{ $log['User'] ?? '-' }}</td>
              <td>{{ $log['Entity Type'] ?? 'Candidate' }}</td>
              <td class="id-mono fw-bold text-primary">{{ $log['Entity ID'] ?? $log['Recruitment ID'] ?? '-' }}</td>
              <td>{{ $log['Source'] ?? 'Legacy' }}</td>
              <td><span class="badge bg-light text-dark border">{{ $log['Action'] ?? '-' }}</span></td>
              <td>{{ $log['Field'] ?? '-' }}</td>
              <td class="text-danger text-decoration-line-through">{{ $log['Old Value'] ?? '-' }}</td>
              <td class="text-success fw-bold">{{ $log['New Value'] ?? '-' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="text-center text-muted py-4">
                Belum ada data riwayat audit log.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="panel-footer">
      <span id="auditFooterCount">Menampilkan {{ $logs->count() }} data</span>
    </div>
  </div>
</section>
@endsection
