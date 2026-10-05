@php
    $footerPortal = request()->attributes->get('portal', 'hris');
    $footerLabel = match ($footerPortal) {
        'mpr' => 'Portal MPR',
        'assets' => 'Portal Aset',
        'certificates' => 'Portal Sertifikasi',
        default => 'MITO HRIS',
    };
    $footerYear = now('Asia/Jakarta')->year;
@endphp

<footer class="dashboard-footer">
    <div class="dashboard-footer__inner">
        <p class="dashboard-footer__copy">
            &copy; {{ $footerYear }} <strong>MITO Group</strong>. Hak cipta dilindungi.
        </p>
        <p class="dashboard-footer__meta">
            <span class="dashboard-footer__dot" aria-hidden="true"></span>
            {{ $footerLabel }}
        </p>
    </div>
</footer>
