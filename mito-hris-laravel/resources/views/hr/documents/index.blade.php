@extends('layouts.hr')

@section('title', 'Document Tracking - MITO HRIS')
@section('page-title', 'Document Tracking')
@section('page-subtitle', 'Riwayat dokumen resmi karyawan (SK, kontrak, paklaring) yang telah diterbitkan')

@section('content')
    <section class="page-section active" id="pageDocumentTracking">

        <div class="row g-3 mb-3 mt-2" id="documentStats">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-cyan"><i class="bi bi-files"></i></div>
                    <div>
                        <div class="stat-label">Total Dokumen</div>
                        <div class="stat-value text-navy">{{ $stats['total'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-gold"><i class="bi bi-calendar-check"></i></div>
                    <div>
                        <div class="stat-label">Terbit Bulan Ini</div>
                        <div class="stat-value text-navy">{{ $stats['this_month'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-purple"><i class="bi bi-people"></i></div>
                    <div>
                        <div class="stat-label">Karyawan Tercatat</div>
                        <div class="stat-value text-navy">{{ $stats['employees'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-blue"><i class="bi bi-file-earmark-text"></i></div>
                    <div>
                        <div class="stat-label">Kontrak (PKWT / TAD)</div>
                        <div class="stat-value text-navy">{{ $stats['contracts'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel mt-2" id="documentPanel">
            <div class="panel-header">
                <div>
                    <h6>Dokumen Karyawan</h6>
                    <div class="panel-subtitle">
                        @if ($total > 0)
                            Menampilkan {{ ($currentPage - 1) * $perPage + 1 }}–{{ min($currentPage * $perPage, $total) }}
                            dari {{ $total }} dokumen
                        @else
                            Belum ada dokumen yang sesuai
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

            <form action="{{ route('hr.documents.index') }}" method="GET" id="docFilterForm">
                <input type="hidden" name="page" value="1">
                <input type="hidden" name="per_page" value="{{ $perPage }}">
                <div class="filter-bar">
                    <div class="table-search">
                        <i class="bi bi-search"></i>
                        <input type="text" name="search" id="docSearchInput"
                            placeholder="Cari nomor, nama, ID karyawan, referensi..."
                            value="{{ $searchFilter ?? '' }}" />
                    </div>
                    <select class="filter-select" name="type" id="docTypeFilter" data-auto-submit="true"
                        aria-label="Filter jenis dokumen">
                        <option value="" {{ ($typeFilter ?? '') === '' ? 'selected' : '' }}>Semua jenis</option>
                        @foreach ($documentTypes as $code => $label)
                            <option value="{{ $code }}" {{ ($typeFilter ?? '') === $code ? 'selected' : '' }}>
                                {{ $label }} ({{ $code }})
                            </option>
                        @endforeach
                    </select>
                    <select class="filter-select" name="entity" id="docEntityFilter" data-auto-submit="true"
                        aria-label="Filter entitas">
                        <option value="" {{ ($entityFilter ?? '') === '' ? 'selected' : '' }}>Semua entitas</option>
                        @foreach ($entities as $entity)
                            <option value="{{ $entity }}" {{ ($entityFilter ?? '') === $entity ? 'selected' : '' }}>
                                {{ $entity }}
                            </option>
                        @endforeach
                    </select>
                    <select class="filter-select" name="period" id="docPeriodFilter" data-auto-submit="true"
                        aria-label="Filter periode terbit">
                        <option value="" {{ ($periodFilter ?? '') === '' ? 'selected' : '' }}>Semua periode</option>
                        <option value="this_month" {{ ($periodFilter ?? '') === 'this_month' ? 'selected' : '' }}>Bulan ini</option>
                        <option value="last_30" {{ ($periodFilter ?? '') === 'last_30' ? 'selected' : '' }}>30 hari terakhir</option>
                        <option value="this_year" {{ ($periodFilter ?? '') === 'this_year' ? 'selected' : '' }}>Tahun ini</option>
                    </select>
                    <select class="filter-select" name="sort" id="docSortSelect" data-auto-submit="true"
                        aria-label="Urutkan dokumen">
                        <option value="issued_desc" {{ ($sortFilter ?? 'issued_desc') === 'issued_desc' ? 'selected' : '' }}>Terbaru</option>
                        <option value="issued_asc" {{ ($sortFilter ?? '') === 'issued_asc' ? 'selected' : '' }}>Terlama</option>
                        <option value="nomor_asc" {{ ($sortFilter ?? '') === 'nomor_asc' ? 'selected' : '' }}>Nomor A-Z</option>
                        <option value="nomor_desc" {{ ($sortFilter ?? '') === 'nomor_desc' ? 'selected' : '' }}>Nomor Z-A</option>
                        <option value="name_asc" {{ ($sortFilter ?? '') === 'name_asc' ? 'selected' : '' }}>Nama A-Z</option>
                        <option value="name_desc" {{ ($sortFilter ?? '') === 'name_desc' ? 'selected' : '' }}>Nama Z-A</option>
                    </select>
                    <a href="{{ route('hr.documents.index') }}" class="btn-reset-filter text-decoration-none">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </a>
                </div>
            </form>

            @if (session('document_download_error'))
                <div class="alert alert-danger mx-3 mt-3 mb-0" role="alert" id="docDownloadError">
                    <i class="bi bi-exclamation-triangle me-1"></i>{{ session('document_download_error') }}
                </div>
            @endif

            @php
                $canDownloadDocuments = Gate::allows('download_documents');
            @endphp

            <div class="table-responsive">
                <table class="table hr-table">
                    <thead>
                        <tr>
                            <th>Tanggal Terbit</th>
                            <th>Nomor Dokumen</th>
                            <th>Jenis</th>
                            <th>Karyawan</th>
                            <th>Posisi / Dept</th>
                            <th>Entitas</th>
                            <th>Diterbitkan Oleh</th>
                            <th>Referensi</th>
                            @if ($canDownloadDocuments)
                                <th>Dokumen</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody id="docTableBody">
                        @forelse ($rows as $row)
                            @php
                                $typeBadgeClass = match ($row->docCode) {
                                    'PKWT' => 'hold',
                                    'SKP' => 'accepted',
                                    'SKO', 'SPAK', 'BPJS' => 'blacklist',
                                    default => 'pending',
                                };
                            @endphp
                            <tr>
                                <td class="id-mono">
                                    <small>{{ $row->issuedAtParsed?->format('d M Y') ?? ($row->issuedAt ?: '-') }}</small>
                                    @if ($row->issuedAtParsed)
                                        <small class="text-muted d-block">{{ $row->issuedAtParsed->format('H:i') }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="id-mono fw-bold">{{ $row->nomor ?: '-' }}</div>
                                    <small class="text-muted">{{ $row->documentId }}</small>
                                </td>
                                <td>
                                    <span class="badge-status {{ $typeBadgeClass }}">{{ $row->docType ?: $row->docCode }}</span>
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
                                @if ($canDownloadDocuments)
                                    <td class="text-nowrap">
                                        @php
                                            $archiveSource = $archiveSources[$row->documentId] ?? null;
                                        @endphp
                                        @if ($row->documentId !== '')
                                            <a href="{{ route('hr.documents.download', ['documentId' => $row->documentId]) }}"
                                                class="btn btn-sm btn-outline-danger"
                                                title="{{ $archiveSource ? 'Unduh PDF arsip' : 'Belum ada arsip — PDF dibuat ulang dari nomor & tanggal terbit, lalu diarsipkan' }}">
                                                <i class="bi bi-file-earmark-pdf me-1"></i>Unduh
                                            </a>
                                            <small class="text-muted d-block mt-1">
                                                @if ($archiveSource === 'export')
                                                    <i class="bi bi-check-circle text-success"></i> Arsip asli
                                                @elseif ($archiveSource === 'regenerated')
                                                    <i class="bi bi-arrow-repeat"></i> Arsip generate ulang
                                                @else
                                                    <i class="bi bi-hourglass"></i> Belum diarsipkan
                                                @endif
                                            </small>
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canDownloadDocuments ? 9 : 8 }}">
                                    <div class="table-empty">
                                        <i class="bi bi-inbox"></i>
                                        <p>Belum ada dokumen karyawan yang sesuai filter.</p>
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
                        dari {{ $total }} dokumen
                    @else
                        Tidak ada data
                    @endif
                </span>
                @if ($total > $perPage)
                    <x-pagination :currentPage="$currentPage" :total="$total" :perPage="$perPage"
                        :route="'hr.documents.index'"
                        :queryParams="[
                            'search' => $searchFilter,
                            'type' => $typeFilter,
                            'entity' => $entityFilter,
                            'period' => $periodFilter,
                            'sort' => $sortFilter,
                            'per_page' => $perPage,
                        ]" />
                @endif
            </div>
        </div>

    </section>
@endsection

@section('scripts')
    <script>
        var docSearchInput = document.getElementById('docSearchInput');
        if (docSearchInput) {
            docSearchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    document.getElementById('docFilterForm').submit();
                }
            });
        }
    </script>
@endsection
