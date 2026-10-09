@php
    $categoryCards = [
        'Building' => ['route' => 'building', 'icon' => 'bi-building-gear', 'color' => '#6b7280'],
        'Vehicle' => ['route' => 'vehicle', 'icon' => 'bi-truck', 'color' => '#005BAC'],
        'Office' => ['route' => 'office', 'icon' => 'bi-columns-gap', 'color' => '#0284c7'],
        'Electronics' => ['route' => 'electronics', 'icon' => 'bi-laptop', 'color' => '#166534'],
    ];
    $statusBadgeClasses = [
        'Available' => 'accepted',
        'Assigned' => 'hold',
        'Maintenance' => 'pending',
        'Damaged' => 'blacklist',
        'Lost' => 'blacklist',
        'Disposed' => 'blacklist',
    ];
    $statusLabels = [
        'Available' => 'Tersedia',
        'Assigned' => 'Digunakan',
        'Maintenance' => 'Pemeliharaan',
        'Damaged' => 'Rusak',
        'Lost' => 'Hilang',
        'Disposed' => 'Disposisi',
    ];
@endphp

<div class="row g-3 mb-4">
    @foreach ([
        ['label' => 'Total Aset', 'value' => number_format($stats['total']), 'icon' => 'bi-box-seam-fill', 'color' => 'bg-blue'],
        ['label' => 'Tersedia', 'value' => number_format($stats['statuses']['Available'] ?? 0), 'icon' => 'bi-check-circle-fill', 'color' => 'bg-green'],
        ['label' => 'Digunakan', 'value' => number_format($stats['statuses']['Assigned'] ?? 0), 'icon' => 'bi-person-fill', 'color' => 'bg-blue'],
        ['label' => 'Pemeliharaan', 'value' => number_format($stats['statuses']['Maintenance'] ?? 0), 'icon' => 'bi-tools', 'color' => 'bg-gold'],
    ] as $stat)
        <div class="col-6 col-xl-3">
            <div class="stat-card h-100">
                <div class="stat-icon {{ $stat['color'] }}"><i class="bi {{ $stat['icon'] }}"></i></div>
                <div>
                    <div class="stat-label">{{ $stat['label'] }}</div>
                    <div class="stat-value text-navy">{{ $stat['value'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="panel asset-condition-panel mb-4">
    <div class="panel-header asset-condition-header">
        <div class="asset-condition-heading">
            <h6>Kondisi Seluruh Aset</h6>
            <div class="panel-subtitle">Ringkasan status pada kategori yang dapat diakses.</div>
        </div>
        <div class="asset-condition-value">
            <div class="stat-label">Nilai Pembelian Tercatat</div>
            <div class="asset-condition-amount">Rp {{ number_format($stats['total_value'], 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="asset-condition-status-grid">
        @foreach ($stats['statuses'] as $status => $count)
            <div class="asset-condition-status">
                <span class="badge-status {{ $statusBadgeClasses[$status] ?? 'pending' }}">
                    {{ $statusLabels[$status] ?? $status }}
                </span>
                <span class="asset-condition-count">{{ number_format($count) }}</span>
            </div>
        @endforeach
    </div>
</div>

<div class="panel mb-4">
    <div class="panel-header">
        <div>
            <h6>Ringkasan per Kategori</h6>
            <div class="panel-subtitle">Jumlah aset dan distribusi status di setiap kategori.</div>
        </div>
    </div>
    <div class="row g-3">
        @foreach ($categoryCards as $label => $category)
            @if (in_array($label, $visibleCategories, true))
            @php
                $summary = $stats['categories'][$label];
            @endphp
            <div class="col-12 col-sm-6 col-xxl-3">
                <a href="{{ route('assets.portal.' . $category['route'] . '.index') }}"
                    class="stat-card h-100 text-decoration-none text-reset d-flex align-items-start">
                    <div class="stat-icon flex-shrink-0" style="background:{{ $category['color'] }}22;">
                        <i class="bi {{ $category['icon'] }}" style="color:{{ $category['color'] }};"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <div class="stat-label">{{ $label }}</div>
                            <i class="bi bi-arrow-up-right text-muted"></i>
                        </div>
                        <div class="stat-value text-navy mb-2">{{ number_format($summary['total']) }}</div>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach ($statusLabels as $status => $statusLabel)
                                @if (($summary['statuses'][$status] ?? 0) > 0)
                                    <span class="badge-status {{ $statusBadgeClasses[$status] }}">
                                        {{ $statusLabel }} <span>{{ number_format($summary['statuses'][$status]) }}</span>
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </a>
            </div>
            @endif
        @endforeach
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <h6>Aset Terakhir Diperbarui</h6>
            <div class="panel-subtitle">Maksimal 8 aset yang baru dibuat atau diperbarui.</div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table hr-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Kode / Nama Aset</th>
                    <th>Kategori</th>
                    <th>Status</th>
                    <th>Penanggung Jawab</th>
                    <th>Lokasi</th>
                    <th>Nilai Pembelian</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentAssets as $asset)
                    @php
                        $statusValue = $asset->status instanceof \App\Enums\AssetStatus
                            ? $asset->status->value
                            : (string) $asset->status;
                    @endphp
                    <tr>
                        <td>
                            <div class="id-mono">{{ $asset->asset_code ?: '—' }}</div>
                            <div class="fw-semibold text-navy">{{ $asset->name }}</div>
                        </td>
                        <td>{{ $asset->category_label }}</td>
                        <td>
                            <span class="badge-status {{ $statusBadgeClasses[$statusValue] ?? 'pending' }}">
                                {{ $statusLabels[$statusValue] ?? $statusValue }}
                            </span>
                        </td>
                        <td>{{ $asset->activeAssignment?->employee_name ?: '—' }}</td>
                        <td>{{ $asset->location ?: '—' }}</td>
                        <td>{{ $asset->purchase_price ? 'Rp ' . number_format((float) $asset->purchase_price, 0, ',', '.') : '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            Belum ada data aset pada kategori yang dapat diakses.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
