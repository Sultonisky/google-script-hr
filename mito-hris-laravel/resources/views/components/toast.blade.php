@php
    $flashToasts = collect(['success', 'error', 'warning', 'info'])
        ->filter(fn ($type) => session()->has($type))
        ->map(fn ($type) => ['type' => $type, 'message' => session($type)])
        ->values();
@endphp

@if ($flashToasts->isNotEmpty())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const flashToasts = @json($flashToasts);
            flashToasts.forEach(function(toast) {
                if (typeof window.showToast === 'function') window.showToast(toast);
            });
        });
    </script>
@endif