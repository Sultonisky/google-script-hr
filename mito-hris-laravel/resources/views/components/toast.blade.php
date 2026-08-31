@php
    $flashToasts = collect(['success', 'error', 'warning', 'info'])
        ->filter(fn ($type) => session()->has($type))
        ->map(fn ($type) => ['type' => $type, 'message' => session($type)])
        ->values();
@endphp

@if ($flashToasts->isNotEmpty())
    <script>
        window.__flashToasts = @json($flashToasts);
    </script>
@endif