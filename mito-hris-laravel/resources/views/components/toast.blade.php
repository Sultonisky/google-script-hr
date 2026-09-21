@php
    $flashToasts = collect(['success', 'error', 'warning', 'info'])
        ->filter(fn ($type) => session()->has($type))
        ->map(fn ($type) => ['type' => $type, 'message' => session($type)])
        ->values();
@endphp

@if ($flashToasts->isNotEmpty())
    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        window.__flashToasts = @json($flashToasts);
    </script>
@endif