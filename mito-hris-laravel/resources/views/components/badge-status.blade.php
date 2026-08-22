@props(['status'])

@php
    $statusLower = strtolower(trim($status ?? ''));
    $bgClass = 'bg-secondary';

    switch ($statusLower) {
        case 'pending':
            $bgClass = 'bg-warning text-dark';
            break;
        case 'new':
        case 'baru':
            $bgClass = 'bg-info text-white';
            break;
        case 'screening':
            $bgClass = 'bg-primary text-white';
            break;
        case 'interview hr':
        case 'interview user':
        case 'interview':
            $bgClass = 'bg-warning text-dark';
            break;
        case 'offering':
        case 'offered':
            $bgClass = 'bg-info text-white';
            break;
        case 'hired':
        case 'accepted':
        case 'aktif':
        case 'active':
        case 'tetap':
            $bgClass = 'bg-success text-white';
            break;
        case 'rejected':
        case 'ditolak':
        case 'resigned':
        case 'non-aktif':
        case 'phk':
            $bgClass = 'bg-danger text-white';
            break;
        case 'hold':
            $bgClass = 'bg-secondary text-white';
            break;
        case 'blacklist':
            $bgClass = 'bg-dark text-white';
            break;
        case 'probation':
            $bgClass = 'bg-warning text-dark';
            break;
        default:
            $bgClass = 'bg-light text-dark border';
            break;
    }
@endphp

<span class="badge {{ $bgClass }} px-2 py-1 fs-7 fw-semibold">
    {{ $status ?? '-' }}
</span>
