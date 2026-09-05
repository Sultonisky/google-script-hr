{{-- Asset Stats Cards --}}
<div class="row g-3 mb-4" id="assetStatsPanel">
    <div class="col-6 col-md-2">
        <div class="stat-card">
            <div class="stat-icon bg-blue"><i class="bi bi-box-seam-fill"></i></div>
            <div>
                <div class="stat-label">Total Aset</div>
                <div class="stat-value text-navy">{{ $stats['total'] ?? 0 }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card">
            <div class="stat-icon bg-green"><i class="bi bi-check-circle-fill"></i></div>
            <div>
                <div class="stat-label">Available</div>
                <div class="stat-value text-navy">{{ $stats['available'] ?? 0 }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card">
            <div class="stat-icon bg-blue"><i class="bi bi-person-fill"></i></div>
            <div>
                <div class="stat-label">Assigned</div>
                <div class="stat-value text-navy">{{ $stats['assigned'] ?? 0 }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card">
            <div class="stat-icon bg-gold"><i class="bi bi-wrench-fill"></i></div>
            <div>
                <div class="stat-label">Maintenance</div>
                <div class="stat-value text-navy">{{ $stats['maintenance'] ?? 0 }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fef2f2;"><i class="bi bi-exclamation-triangle-fill" style="color:#991b1b;"></i></div>
            <div>
                <div class="stat-label">Damaged</div>
                <div class="stat-value text-navy">{{ $stats['damaged'] ?? 0 }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stat-card">
            <div class="stat-icon" style="background:#f3f4f6;"><i class="bi bi-trash3-fill" style="color:#6b7280;"></i></div>
            <div>
                <div class="stat-label">Lost / Disposed</div>
                <div class="stat-value text-navy">{{ ($stats['lost'] ?? 0) + ($stats['disposed'] ?? 0) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Category Summary --}}
<div class="row g-3 mb-4">
    @php
        $catIcons = [
            'Building' => ['bi-building', '#6b7280'],
            'Vehicle'  => ['bi-truck', '#005BAC'],
            'Office'   => ['bi-laptop', '#0284c7'],
            'Elektronik' => ['bi-cpu', '#166534'],
        ];
    @endphp
    @foreach($stats['categories'] ?? [] as $catName => $catCount)
        <div class="col-6 col-md-3">
            <div class="stat-card" style="cursor:pointer;"
                onclick="document.querySelector('#assetCategoryFilter').value='{{ $catName }}';document.getElementById('assetFilterForm').submit();">
                <div class="stat-icon" style="background:{{ $catIcons[$catName][1] ?? '#6b7280' }}22;">
                    <i class="bi {{ $catIcons[$catName][0] ?? 'bi-box' }}" style="color:{{ $catIcons[$catName][1] ?? '#6b7280' }};"></i>
                </div>
                <div>
                    <div class="stat-label">{{ $catName }}</div>
                    <div class="stat-value text-navy">{{ $catCount }}</div>
                </div>
            </div>
        </div>
    @endforeach
    @if(($stats['without_code'] ?? 0) > 0)
        <div class="col-6 col-md-3">
            <div class="stat-card" style="border:2px dashed #d97706;">
                <div class="stat-icon" style="background:#fffbeb;"><i class="bi bi-question-circle-fill" style="color:#d97706;"></i></div>
                <div>
                    <div class="stat-label">Belum Ada Kode</div>
                    <div class="stat-value text-navy">{{ $stats['without_code'] }}</div>
                </div>
            </div>
        </div>
    @endif
</div>