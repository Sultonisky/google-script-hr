{{-- Certification Stats Cards --}}
<div class="row g-3 mb-4" id="certificationStatsPanel">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-blue"><i class="bi bi-award-fill"></i></div>
            <div>
                <div class="stat-label">Total Sertifikasi</div>
                <div class="stat-value text-navy">{{ $stats['total'] ?? 0 }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-green"><i class="bi bi-check-circle-fill"></i></div>
            <div>
                <div class="stat-label">Active</div>
                <div class="stat-value text-navy">{{ $stats['active'] ?? 0 }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-gold"><i class="bi bi-hourglass-split"></i></div>
            <div>
                <div class="stat-label">Expiring Soon (30 hari)</div>
                <div class="stat-value text-navy">{{ $stats['expiring_soon'] ?? 0 }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fef2f2;"><i class="bi bi-exclamation-triangle-fill" style="color:#991b1b;"></i></div>
            <div>
                <div class="stat-label">Expired</div>
                <div class="stat-value text-navy">{{ $stats['expired'] ?? 0 }}</div>
            </div>
        </div>
    </div>
</div>
