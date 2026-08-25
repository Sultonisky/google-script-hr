@props(['status'])

@php
    $s = strtolower(trim($status ?? ''));

    // Mapping 1:1 dengan GAS js/helpers.html entityStatusBadgeClass()
    // Badge classes merujuk ke CSS definitions di css/table.html GAS:
    //   accepted  → background: #ecfdf3; color: #166534  (hijau)
    //   hold      → background: #eef6ff; color: #0b4a86  (biru)
    //   pending   → background: #fff8e6; color: #8a6100  (kuning/amber)
    //   blacklist → background: #fef2f2; color: #991b1b  (merah)
    $badgeClass = match(true) {
        in_array($s, ['permanent', 'pkwtt', 'contract', 'pkwt', 'active', 'aktif']) => 'accepted',
        in_array($s, ['probation'])                                                  => 'hold',
        in_array($s, ['outsource', 'on leave', 'magang', 'intern'])                 => 'pending',
        in_array($s, ['resigned', 'terminated', 'retired', 'deceased', 'inactive',
                      'non-aktif', 'phk', 'blacklist', 'contract finished',
                      'off contract'])                                               => 'blacklist',
        default                                                                      => 'pending',
    };
@endphp

<span class="badge-status {{ $badgeClass }}">{{ $status ?? '-' }}</span>
