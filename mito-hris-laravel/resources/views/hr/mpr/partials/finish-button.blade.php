@php
    $mprStatus = trim((string) ($mpr->status ?? 'Submitted'));
    $isSubmitted = strcasecmp($mprStatus, 'Submitted') === 0;
    $isFinish = strcasecmp($mprStatus, 'Finish') === 0;
@endphp
@if ($isSubmitted)
    <form method="POST" action="{{ $action }}" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-sm btn-info text-white" data-mpr-finish="{{ $mpr->mprNumber }}"
            title="Ubah status menjadi Finish">Finish</button>
    </form>
@elseif ($isFinish)
    <button type="button" class="btn btn-sm btn-outline-secondary" disabled data-mpr-finish="{{ $mpr->mprNumber }}" title="Status sudah Finish">Finish</button>
@endif
