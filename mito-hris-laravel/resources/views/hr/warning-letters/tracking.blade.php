@extends('layouts.hr')

@section('title', 'SP Tracking - MITO HRIS')
@section('page-title', 'SP Tracking')
@section('page-subtitle', 'Riwayat Surat Peringatan karyawan yang telah diterbitkan')

@section('content')
    <section class="page-section active" id="pageWarningLetterTracking">
        <div class="row g-3 mb-3 mt-2">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-cyan"><i class="bi bi-files"></i></div>
                    <div>
                        <div class="stat-label">Total Surat Peringatan</div>
                        <div class="stat-value text-navy">{{ $stats['total'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-gold"><i class="bi bi-calendar-check"></i></div>
                    <div>
                        <div class="stat-label">Terbit Bulan Ini</div>
                        <div class="stat-value text-navy">{{ $stats['this_month'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-purple"><i class="bi bi-people"></i></div>
                    <div>
                        <div class="stat-label">Karyawan Tercatat</div>
                        <div class="stat-value text-navy">{{ $stats['employees'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-red"><i class="bi bi-exclamation-triangle"></i></div>
                    <div>
                        <div class="stat-label">Sanksi Aktif</div>
                        <div class="stat-value text-navy" id="spStatActive">{{ $stats['active'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel mt-2">
            <div class="panel-header">
                <div>
                    <h6>Riwayat Surat Peringatan</h6>
                    <div class="panel-subtitle">
                        @if ($total > 0)
                            Menampilkan {{ ($currentPage - 1) * $perPage + 1 }}–{{ min($currentPage * $perPage, $total) }}
                            dari {{ $total }} surat
                        @else
                            Belum ada Surat Peringatan yang sesuai
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

            <form action="{{ route('hr.sp-tracking') }}" method="GET" id="spTrackingFilterForm">
                <input type="hidden" name="page" value="1">
                <input type="hidden" name="per_page" value="{{ $perPage }}">
                <div class="filter-bar">
                    <div class="table-search">
                        <i class="bi bi-search"></i>
                        <input type="text" name="search" id="spTrackingSearch" aria-label="Cari Surat Peringatan"
                            placeholder="Cari nomor, nama, atau ID karyawan..."
                            value="{{ $searchFilter }}">
                    </div>
                    <select class="filter-select" name="status" id="spTrackingStatus" data-auto-submit="true"
                        aria-label="Filter status sanksi">
                        <option value="" {{ $statusFilter === '' ? 'selected' : '' }}>Semua Status ({{ $stats['total'] }})</option>
                        <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Aktif ({{ $statusCounts['active'] }})</option>
                        <option value="superseded" {{ $statusFilter === 'superseded' ? 'selected' : '' }}>Hangus ({{ $statusCounts['superseded'] }})</option>
                        <option value="ended" {{ $statusFilter === 'ended' ? 'selected' : '' }}>Berakhir ({{ $statusCounts['ended'] }})</option>
                    </select>
                    <select class="filter-select" name="level" id="spTrackingLevel" data-auto-submit="true"
                        aria-label="Filter tingkat Surat Peringatan">
                        <option value="" {{ $levelFilter === '' ? 'selected' : '' }}>Semua Tingkat ({{ $stats['total'] }})</option>
                        @foreach ($levelOptions as $option)
                            <option value="{{ $option['value'] }}" {{ $levelFilter === $option['value'] ? 'selected' : '' }}>
                                {{ $option['label'] }} ({{ $option['count'] }})
                            </option>
                        @endforeach
                    </select>
                    <select class="filter-select" name="sort" id="spTrackingSort" data-auto-submit="true"
                        aria-label="Urutkan Surat Peringatan">
                        <option value="issued_desc" {{ $sortFilter === 'issued_desc' ? 'selected' : '' }}>Terbaru</option>
                        <option value="issued_asc" {{ $sortFilter === 'issued_asc' ? 'selected' : '' }}>Terlama</option>
                        <option value="nomor_asc" {{ $sortFilter === 'nomor_asc' ? 'selected' : '' }}>Nomor A-Z</option>
                        <option value="nomor_desc" {{ $sortFilter === 'nomor_desc' ? 'selected' : '' }}>Nomor Z-A</option>
                        <option value="name_asc" {{ $sortFilter === 'name_asc' ? 'selected' : '' }}>Nama A-Z</option>
                        <option value="name_desc" {{ $sortFilter === 'name_desc' ? 'selected' : '' }}>Nama Z-A</option>
                    </select>
                    <a href="{{ route('hr.sp-tracking') }}" class="btn-reset-filter text-decoration-none">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset Filter
                    </a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table hr-table">
                    <thead>
                        <tr>
                            <th>Tanggal Terbit</th>
                            <th>Nomor SP</th>
                            <th>Tingkat</th>
                            <th>Masa Berlaku</th>
                            <th>Karyawan</th>
                            <th>Posisi / Dept</th>
                            <th>Entitas</th>
                            <th>Diterbitkan Oleh</th>
                            <th>Referensi</th>
                            <th>Dokumen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td class="id-mono">
                                    <small>{{ $row->issuedAtParsed?->format('d M Y') ?? ($row->issuedAt ?: '-') }}</small>
                                    @if ($row->issuedAtParsed && $row->issuedAtHasTime)
                                        <small class="text-muted d-block">{{ $row->issuedAtParsed->format('H:i') }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="id-mono fw-bold">{{ $row->nomor ?: '-' }}</div>
                                </td>
                                <td>
                                    <span class="badge-status blacklist">{{ $row->docType ?: 'Surat Peringatan' }}</span>
                                </td>
                                <td>
                                    @if (($row->validUntilLabel ?? '') !== '' || ($row->sanctionSuperseded ?? false))
                                        @if (($row->validUntilLabel ?? '') !== '')
                                            <div class="id-mono"><small>s.d. {{ $row->validUntilLabel }}</small></div>
                                        @endif
                                        @if ($row->sanctionSuperseded ?? false)
                                            <span class="badge-status hold">Hangus</span>
                                        @elseif ($row->sanctionActive)
                                            <span class="badge-status blacklist">Aktif</span>
                                        @else
                                            <span class="badge-status hold">Berakhir</span>
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($row->employeeId !== '')
                                        <a href="{{ route('hr.employees.index', ['search' => $row->employeeId]) }}"
                                            class="cand-name fw-bold text-decoration-none">
                                            {{ $row->employeeName ?: $row->employeeId }}
                                        </a>
                                        <div class="cand-sub id-mono">{{ $row->employeeId }}</div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    {{ $row->jobPosition ?: '-' }}
                                    @if ($row->department !== '')
                                        <small class="text-muted d-block">({{ $row->department }})</small>
                                    @endif
                                </td>
                                <td class="id-mono"><small>{{ $row->entity ?: '-' }}</small></td>
                                <td><small>{{ $row->issuedBy ?: '-' }}</small></td>
                                <td>
                                    <small>{{ $row->reference ?: '-' }}</small>
                                    @if ($row->notes !== '')
                                        <small class="text-muted d-block text-truncate" style="max-width: 220px;"
                                            title="{{ $row->notes }}">{{ $row->notes }}</small>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    @if ($row->documentId !== '' && $row->employeeId !== '')
                                        <a href="{{ route('hr.employees.warning-letter.download', ['id' => $row->employeeId, 'documentId' => $row->documentId]) }}"
                                            class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-file-earmark-pdf me-1"></i>Unduh
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10">
                                    <div class="table-empty">
                                        <i class="bi bi-inbox"></i>
                                        <p>Belum ada Surat Peringatan karyawan yang sesuai.</p>
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
                        dari {{ $total }} surat
                    @else
                        Tidak ada data
                    @endif
                </span>
                @if ($total > $perPage)
                    <x-pagination :currentPage="$currentPage" :total="$total" :perPage="$perPage"
                        :route="'hr.sp-tracking'"
                        :queryParams="[
                            'search' => $searchFilter,
                            'status' => $statusFilter,
                            'level' => $levelFilter,
                            'sort' => $sortFilter,
                            'per_page' => $perPage,
                        ]" />
                @endif
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('spTrackingFilterForm');
            var search = document.getElementById('spTrackingSearch');
            var sort = document.getElementById('spTrackingSort');
            var searchTimeout;

            if (sort && form) {
                sort.addEventListener('change', function () {
                    form.submit();
                });
            }

            if (search && form) {
                search.addEventListener('input', function () {
                    window.clearTimeout(searchTimeout);
                    searchTimeout = window.setTimeout(function () {
                        form.submit();
                    }, 400);
                });
            }
        });
    </script>
@endsection
