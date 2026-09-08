@extends('layouts.hr')

@section('title', 'Audit Log - MITO HRIS')
@section('page-title', 'Audit Log')
@section('page-subtitle', 'Riwayat aktivitas semua modul sistem')

@section('content')
    <style>
        #pageAuditLog .audit-page-header {
            gap: 1rem;
        }

        #pageAuditLog .audit-page-header h5 {
            letter-spacing: 0;
        }

        #pageAuditLog .audit-filter-panel {
            padding: 1rem 1.25rem;
        }

        #pageAuditLog .audit-filter-grid {
            display: grid;
            grid-template-columns: minmax(220px, 1.75fr) repeat(3, minmax(140px, 1fr)) auto;
            gap: .75rem;
            align-items: end;
        }

        #pageAuditLog .audit-filter-field {
            min-width: 0;
        }

        #pageAuditLog .audit-filter-field label {
            display: block;
            margin-bottom: .35rem;
            color: var(--text-muted, #64748b);
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        #pageAuditLog .audit-filter-field .form-control,
        #pageAuditLog .audit-filter-field .form-select {
            min-height: 40px;
            height: 40px;
            font-size: .82rem;
            border-radius: 10px;
            border: 1.5px solid rgba(148, 163, 184, 0.38);
            background: rgba(255, 255, 255, 0.65);
            padding: 0.55rem 0.8rem;
            width: 100%;
            box-sizing: border-box;
        }

        #pageAuditLog .audit-filter-field .form-control:focus,
        #pageAuditLog .audit-filter-field .form-select:focus {
            border-color: rgba(0, 91, 172, 0.7);
            box-shadow: 0 0 0 0.2rem rgba(0, 91, 172, 0.12);
            outline: none;
        }

        #pageAuditLog .audit-filter-actions {
            display: flex;
            align-items: end;
            justify-content: flex-end;
            gap: .5rem;
            white-space: nowrap;
        }

        #pageAuditLog .audit-filter-actions .btn {
            min-height: 40px;
            border-radius: 10px;
            font-size: .8rem;
            font-weight: 600;
            padding: 0.55rem 0.9rem;
        }

        #pageAuditLog .audit-table-wrap {
            overflow-x: auto;
        }

        #pageAuditLog .audit-table {
            min-width: 1120px;
            margin-bottom: 0;
        }

        #pageAuditLog .audit-table th {
            white-space: nowrap;
        }

        #pageAuditLog .audit-table td {
            vertical-align: middle;
        }

        #pageAuditLog .audit-id,
        #pageAuditLog .audit-entity-id,
        #pageAuditLog .audit-time {
            white-space: nowrap;
        }

        #pageAuditLog .audit-user {
            max-width: 190px;
        }

        #pageAuditLog .audit-value {
            max-width: 190px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        #pageAuditLog .audit-value.is-empty {
            color: #94a3b8;
        }

        #pageAuditLog .audit-badge {
            border: 1px solid transparent;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .02em;
        }

        #pageAuditLog .audit-table .badge-status.audit-badge {
            padding: 4px 9px;
            white-space: nowrap;
        }

        #pageAuditLog .audit-badge-entity {
            background: #eef5fb;
            border-color: #d7e6f4;
            color: #14527d;
        }

        #pageAuditLog .audit-badge-source {
            background: #f1f5f9;
            border-color: #e2e8f0;
            color: #475569;
        }

        #pageAuditLog .audit-action-created,
        #pageAuditLog .audit-action-approved,
        #pageAuditLog .audit-action-promoted,
        #pageAuditLog .audit-action-login-success {
            background: #e8f5ed;
            color: #166534;
        }

        #pageAuditLog .audit-action-updated,
        #pageAuditLog .audit-action-generated,
        #pageAuditLog .audit-action-exported,
        #pageAuditLog .audit-action-imported,
        #pageAuditLog .audit-action-logged-in {
            background: #eaf2fb;
            color: #15538a;
        }

        #pageAuditLog .audit-action-status-changed,
        #pageAuditLog .audit-action-hold,
        #pageAuditLog .audit-action-extended,
        #pageAuditLog .audit-action-demoted {
            background: #fff6dd;
            color: #8a6100;
        }

        #pageAuditLog .audit-action-deleted,
        #pageAuditLog .audit-action-blacklist,
        #pageAuditLog .audit-action-offboarded,
        #pageAuditLog .audit-action-login-failed,
        #pageAuditLog .audit-action-rejected {
            background: #fcebea;
            color: #991b1b;
        }

        #pageAuditLog .audit-action-default {
            background: #f8fafc;
            border-color: #e2e8f0;
            color: #334155;
        }

        #pageAuditLog .audit-detail-btn {
            min-width: 34px;
        }

        #pageAuditLog .audit-empty {
            padding: 3.5rem 1rem;
        }

        #pageAuditLog .audit-empty-icon {
            color: #94a3b8;
            font-size: 2rem;
        }

        @media (max-width: 1100px) {
            #pageAuditLog .audit-filter-grid {
                grid-template-columns: repeat(2, minmax(180px, 1fr));
            }

            #pageAuditLog .audit-filter-actions {
                grid-column: 1 / -1;
                justify-content: flex-end;
                width: 100%;
            }
        }

        @media (max-width: 600px) {
            #pageAuditLog .audit-page-header {
                align-items: flex-start !important;
                flex-direction: column;
            }

            #pageAuditLog .audit-page-header .btn {
                width: 100%;
            }

            #pageAuditLog .audit-filter-grid {
                grid-template-columns: 1fr;
            }

            #pageAuditLog .audit-filter-actions {
                display: grid;
                grid-template-columns: 1fr;
                width: 100%;
            }

            #pageAuditLog .audit-filter-actions .btn {
                width: 100%;
            }
        }
    </style>

    <section class="page-section active" id="pageAuditLog">
        <!-- Header -->
        <div class="audit-page-header d-flex justify-content-between align-items-center mb-4">
            <div></div>
            <div></div>
        </div>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-blue"><i class="bi bi-list-ul"></i></div>
                    <div>
                        <div class="stat-label">Total Log</div>
                        <div class="stat-value" id="auditStatTotal">{{ $total }}</div>
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
        <div class="panel audit-filter-panel mb-4">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-funnel text-primary" aria-hidden="true"></i>
                    <h6 class="mb-0 fw-bold">Filter Audit Log</h6>
                </div>

                <button class="btn btn-primary btn-sm" id="btnAuditRefresh" type="button" data-refresh="page" aria-label="Refresh data" title="Muat ulang data">
                    <i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>Refresh
                </button>
            </div>
            <form action="{{ route('hr.audit-logs.index') }}" method="GET">
                <div class="audit-filter-grid">
                    <div class="audit-filter-field">
                        <label for="auditSearchInput">Pencarian</label>
                        <input type="text" name="search" id="auditSearchInput" class="form-control"
                            placeholder="Entity, ID, user, action" value="{{ request('search') }}" data-submit-on-enter="true" />
                    </div>
                    <div class="audit-filter-field">
                        <label for="auditEntityFilter">Entity</label>
                        <select class="form-select" name="entity_type" id="auditEntityFilter" data-auto-submit="true">
                            <option value="">Semua Entity</option>
                            @foreach (['Candidate', 'Employee', 'Probation', 'Outsource', 'MPR', 'User', 'Setting', 'MasterData', 'Applicant', 'Authentication', 'System'] as $entity)
                                <option value="{{ $entity }}"
                                    {{ request('entity_type') === $entity ? 'selected' : '' }}>{{ $entity }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="audit-filter-field">
                        <label for="auditActionFilter">Action</label>
                        <select class="form-select" name="action" id="auditActionFilter" data-auto-submit="true">
                            <option value="">Semua Action</option>
                            @foreach (['created', 'updated', 'status_changed', 'submitted', 'generated', 'imported', 'exported', 'logged_in', 'logged_out', 'login_failed', 'hold', 'blacklist', 'offboarded'] as $action)
                                <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>
                                    {{ strtoupper($action) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="audit-filter-field">
                        <label for="auditSourceFilter">Source</label>
                        <select class="form-select" name="source" id="auditSourceFilter" data-auto-submit="true">
                            <option value="">Semua Source</option>
                            @foreach (['Dashboard', 'Public', 'Command', 'Import', 'Export', 'Authentication', 'System', 'Legacy'] as $source)
                                <option value="{{ $source }}" {{ request('source') === $source ? 'selected' : '' }}>
                                    {{ $source }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="audit-filter-actions">
                        <a href="{{ route('hr.audit-logs.index') }}" class="btn btn-outline-secondary btn-sm"
                            id="btnAuditReset">
                            <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="panel">
            <div class="audit-table-wrap">
                <table class="table hr-table audit-table">
                    <thead>
                        <tr>
                            <th>Audit ID</th>
                            <th>Entity</th>
                            <th>Entity ID</th>
                            <th>Action</th>
                            <th>Field</th>
                            <th>Old Value</th>
                            <th>New Value</th>
                            <th>User</th>
                            <th>Source</th>
                            <th>Timestamp</th>
                            <th aria-label="Detail"></th>
                        </tr>
                    </thead>
                    <tbody id="auditTableBody">
                        @forelse($paginatedLogs as $log)
                            <tr>
                                @php
                                    $action = strtolower(trim($log['Action'] ?? ''));
                                    $actionSlug = preg_replace('/[^a-z0-9]+/', '-', $action) ?: 'default';
                                    $knownActionSlugs = [
                                        'created', 'approved', 'promoted', 'login-success',
                                        'updated', 'generated', 'exported', 'imported', 'logged-in',
                                        'status-changed', 'hold', 'extended', 'demoted',
                                        'deleted', 'blacklist', 'offboarded', 'login-failed', 'rejected',
                                    ];
                                    $actionFallbackClass = in_array($actionSlug, $knownActionSlugs, true)
                                        ? ''
                                        : 'audit-action-default';
                                    $oldValue = (string) ($log['Old Value'] ?? '');
                                    $newValue = (string) ($log['New Value'] ?? '');
                                    $detail = [
                                        'Audit ID' => $log['Audit ID'] ?? '-',
                                        'Entity' => $log['Entity Type'] ?? 'Candidate',
                                        'Entity ID' => $log['Entity ID'] ?? ($log['Recruitment ID'] ?? '-'),
                                        'Action' => $log['Action'] ?? '-',
                                        'Field' => $log['Field'] ?? '-',
                                        'Old Value' => $oldValue ?: '-',
                                        'New Value' => $newValue ?: '-',
                                        'User' => $log['User'] ?? '-',
                                        'Source' => $log['Source'] ?? 'Legacy',
                                        'Timestamp' => $log['Timestamp'] ?? '-',
                                    ];
                                @endphp
                                <td class="audit-id id-mono fw-semibold">{{ $detail['Audit ID'] }}</td>
                                <td><span
                                        class="badge rounded-pill audit-badge audit-badge-entity">{{ $detail['Entity'] }}</span>
                                </td>
                                <td class="audit-entity-id id-mono fw-bold text-primary">{{ $detail['Entity ID'] }}</td>
                                <td><span
                                    class="badge-status audit-badge audit-action-{{ $actionSlug }} {{ $actionFallbackClass }}">{{ $detail['Action'] }}</span>
                                </td>
                                <td>{{ $detail['Field'] }}</td>
                                <td class="audit-value text-danger" title="{{ $oldValue }}">{{ $oldValue ?: '-' }}
                                </td>
                                <td class="audit-value text-success fw-bold" title="{{ $newValue }}">
                                    {{ $newValue ?: '-' }}</td>
                                <td class="audit-user fw-semibold text-navy">{{ $detail['User'] }}</td>
                                <td><span
                                        class="badge rounded-pill audit-badge audit-badge-source">{{ $detail['Source'] }}</span>
                                </td>
                                <td class="audit-time id-mono">{{ $detail['Timestamp'] }}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-light border audit-detail-btn"
                                        data-audit-detail='@json($detail)' data-bs-toggle="modal"
                                        data-bs-target="#auditDetailModal" aria-label="Lihat detail audit">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="audit-empty text-center text-muted">
                                    <div class="audit-empty-icon mb-2"><i class="bi bi-inbox" aria-hidden="true"></i>
                                    </div>
                                    <div class="fw-semibold text-dark">Belum ada aktivitas audit</div>
                                    <div class="small">Belum ada aktivitas yang sesuai dengan filter.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="panel-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span id="auditFooterCount">
                    @if ($total > 0)
                        Menampilkan {{ ($currentPage - 1) * $perPage + 1 }}–{{ min($currentPage * $perPage, $total) }}
                        dari {{ $total }} data
                    @else
                        Tidak ada data
                    @endif
                </span>
                @if ($total > $perPage)
                    <x-pagination :currentPage="$currentPage" :total="$total" :perPage="$perPage" :route="'hr.audit-logs.index'"
                        :queryParams="[
                            'search' => request('search'),
                            'entity_type' => request('entity_type'),
                            'action' => request('action'),
                            'user' => request('user'),
                            'source' => request('source'),
                            'entity_id' => request('entity_id'),
                            'per_page' => $perPage,
                        ]" />
                @endif
            </div>
        </div>
    </section>

    <div class="modal fade" id="auditDetailModal" tabindex="-1" aria-labelledby="auditDetailModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="auditDetailModalLabel"><i class="bi bi-clock-history text-primary me-2"
                            aria-hidden="true"></i>Detail Audit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-0 small" id="auditDetailContent"></dl>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('#pageAuditLog .audit-detail-btn').forEach(function(button) {
            button.addEventListener('click', function() {
                var detail = JSON.parse(button.dataset.auditDetail);
                var content = document.getElementById('auditDetailContent');
                content.innerHTML = Object.entries(detail).map(function(entry) {
                    var label = entry[0];
                    var value = entry[1] || '-';
                    return '<dt class="col-sm-3 text-muted fw-semibold mb-2">' + label +
                        '</dt><dd class="col-sm-9 mb-2 text-break">' + value.replace(/[&<>"']/g,
                            function(character) {
                                return {
                                    '&': '&amp;',
                                    '<': '&lt;',
                                    '>': '&gt;',
                                    '"': '&quot;',
                                    "'": '&#039;'
                                } [character];
                            }) + '</dd>';
                }).join('');
            });
        });
    </script>
@endsection
