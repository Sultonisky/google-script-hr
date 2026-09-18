@extends('layouts.hr')

@section('title', 'Contract Tracking - MITO HRIS')
@section('page-title', 'Contract Tracking')
@section('page-subtitle', 'Monitoring kontrak karyawan (Contract / PKWT / Outsource) yang akan berakhir dalam 21 hari')

@section('content')
    <section class="page-section active" id="pageContractTracking">

        <div class="row g-3 mb-3 mt-2" id="contractStats">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-cyan"><i class="bi bi-file-earmark-text"></i></div>
                    <div>
                        <div class="stat-label">Total ≤21 Hari</div>
                        <div class="stat-value text-navy">{{ $stats['total'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-red"><i class="bi bi-exclamation-triangle-fill"></i></div>
                    <div>
                        <div class="stat-label">Kritis ≤7 Hari</div>
                        <div class="stat-value text-navy">{{ $stats['critical'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-gold"><i class="bi bi-clock-history"></i></div>
                    <div>
                        <div class="stat-label">Segera 8–14 Hari</div>
                        <div class="stat-value text-navy">{{ $stats['urgent'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-purple"><i class="bi bi-calendar2-week"></i></div>
                    <div>
                        <div class="stat-label">Mendatang 15–21 Hari</div>
                        <div class="stat-value text-navy">{{ $stats['upcoming'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel mt-2" id="contractPanel">
            <div class="panel-header">
                <div>
                    <h6>Kontrak Akan Berakhir</h6>
                    <div class="panel-subtitle">
                        @if ($total > 0)
                            Menampilkan {{ ($currentPage - 1) * $perPage + 1 }}–{{ min($currentPage * $perPage, $total) }}
                            dari {{ $total }} data
                        @else
                            Tidak ada kontrak yang berakhir dalam 21 hari
                        @endif
                    </div>
                </div>
                <div class="export-btns">
                    <button class="btn-refresh" type="button" title="Muat ulang"
                        aria-label="Muat ulang data" data-refresh="page">
                        <i class="bi bi-arrow-repeat"></i>
                    </button>
                </div>
            </div>

            <form action="{{ route('hr.contracts.index') }}" method="GET" id="ctFilterForm">
                <input type="hidden" name="page" value="1">
                <div class="filter-bar">
                    <div class="table-search">
                        <i class="bi bi-search"></i>
                        <input type="text" name="search" id="ctSearchInput"
                            placeholder="Cari nama, ID, posisi, departemen..."
                            value="{{ $searchFilter ?? '' }}" />
                    </div>
                    <select class="filter-select" name="window" id="ctWindowFilter" data-auto-submit="true">
                        <option value="" {{ ($windowFilter ?? '') === '' ? 'selected' : '' }}>Semua ≤21 hari</option>
                        <option value="7" {{ ($windowFilter ?? '') === '7' ? 'selected' : '' }}>≤7 hari</option>
                        <option value="14" {{ ($windowFilter ?? '') === '14' ? 'selected' : '' }}>≤14 hari</option>
                        <option value="21" {{ ($windowFilter ?? '') === '21' ? 'selected' : '' }}>≤21 hari</option>
                    </select>
                    <select class="filter-select" name="sort" id="ctSortSelect" data-auto-submit="true">
                        <option value="days_asc" {{ ($sortFilter ?? 'days_asc') === 'days_asc' ? 'selected' : '' }}>
                            Sisa hari (terdekat)
                        </option>
                        <option value="days_desc" {{ ($sortFilter ?? '') === 'days_desc' ? 'selected' : '' }}>
                            Sisa hari (terjauh)
                        </option>
                        <option value="end_asc" {{ ($sortFilter ?? '') === 'end_asc' ? 'selected' : '' }}>
                            End Date (terdekat)
                        </option>
                        <option value="end_desc" {{ ($sortFilter ?? '') === 'end_desc' ? 'selected' : '' }}>
                            End Date (terjauh)
                        </option>
                        <option value="name_asc" {{ ($sortFilter ?? '') === 'name_asc' ? 'selected' : '' }}>Nama A-Z</option>
                        <option value="name_desc" {{ ($sortFilter ?? '') === 'name_desc' ? 'selected' : '' }}>Nama Z-A</option>
                    </select>
                    <a href="{{ route('hr.contracts.index') }}" class="btn-reset-filter text-decoration-none">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table hr-table">
                    <thead>
                        <tr>
                            <th>Avatar</th>
                            <th>Employee ID</th>
                            <th>Nama</th>
                            <th>Posisi / Dept</th>
                            <th>Status</th>
                            <th>Join Date</th>
                            <th>End Date</th>
                            <th>Durasi Kontrak</th>
                            <th>Sisa Hari</th>
                        </tr>
                    </thead>
                    <tbody id="ctTableBody">
                        @forelse($contracts as $row)
                            @php
                                $days = (int) ($row->daysRemaining ?? 0);
                                $daysBadgeClass = match (true) {
                                    $days <= 7  => 'blacklist',
                                    $days <= 14 => 'pending',
                                    default     => 'hold',
                                };
                                $daysLabel = $days === 0 ? 'Hari ini' : $days . ' hari';
                            @endphp
                            <tr>
                                <td>
                                    <div class="avatar-sm">
                                        {{ strtoupper(substr($row->fullName ?? 'C', 0, 2)) }}
                                    </div>
                                </td>
                                <td class="id-mono fw-bold">{{ $row->employeeId }}</td>
                                <td>
                                    <div class="cand-name fw-bold">{{ $row->fullName }}</div>
                                    <div class="cand-sub">{{ $row->workingEmail ?? $row->personalEmail }}</div>
                                </td>
                                <td>
                                    {{ $row->jobPosition }}
                                    <small class="text-muted d-block">({{ $row->department }})</small>
                                </td>
                                <td><x-badge-status :status="$row->statusEmployee ?? 'Contract'" /></td>
                                <td class="id-mono"><small>{{ $row->joinDate ?? '-' }}</small></td>
                                <td class="id-mono"><small>{{ $row->endDateContract ?? '-' }}</small></td>
                                <td><small>{{ $row->contractDuration ?? '-' }}</small></td>
                                <td>
                                    <span class="badge-status {{ $daysBadgeClass }}">{{ $daysLabel }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="table-empty">
                                        <i class="bi bi-inbox"></i>
                                        <p>Tidak ada karyawan dengan kontrak yang berakhir dalam 21 hari.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="panel-footer">
                <span>
                    @if ($total > 0)
                        Menampilkan {{ ($currentPage - 1) * $perPage + 1 }}–{{ min($currentPage * $perPage, $total) }}
                        dari {{ $total }} data
                    @else
                        Tidak ada data
                    @endif
                </span>
                @if ($total > $perPage)
                    <x-pagination :currentPage="$currentPage" :total="$total" :perPage="$perPage"
                        :route="'hr.contracts.index'"
                        :queryParams="[
                            'search' => $searchFilter,
                            'sort' => $sortFilter,
                            'window' => $windowFilter,
                            'per_page' => $perPage,
                        ]" />
                @endif
            </div>
        </div>

    </section>
@endsection

@section('scripts')
    <script>
        var ctSearchInput = document.getElementById('ctSearchInput');
        if (ctSearchInput) {
            ctSearchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    document.getElementById('ctFilterForm').submit();
                }
            });
        }
    </script>
@endsection
