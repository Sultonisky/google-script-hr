@props(['currentPage', 'total', 'perPage', 'route' => null, 'queryParams' => []])

@php
    $totalPages = max(1, ceil($total / $perPage));
    $maxButtons = 5;
    $startPage = max(1, $currentPage - floor($maxButtons / 2));
    $endPage = min($totalPages, $startPage + $maxButtons - 1);
    $startPage = max(1, $endPage - $maxButtons + 1);
    
    $showStart = $startPage > 1;
    $showEnd = $endPage < $totalPages;
    
    $prevDisabled = $currentPage <= 1;
    $nextDisabled = $currentPage >= $totalPages;
    
    $route = $route ?? request()->route()->getName();
@endphp

<nav class="pagination-ui" aria-label="Pagination">
    {{-- Previous Button --}}
    <button class="page-btn" type="button" @if($prevDisabled) disabled @else onclick="window.location.href='{{ route($route, array_merge($queryParams, ['page' => $currentPage - 1, 'per_page' => $perPage])) }}'" @endif>
        <i class="bi bi-chevron-left"></i>
    </button>

    {{-- First page --}}
    @if($showStart)
        <button class="page-btn" type="button" onclick="window.location.href='{{ route($route, array_merge($queryParams, ['page' => 1, 'per_page' => $perPage])) }}'">
            1
        </button>
        @if($startPage > 2)
            <span class="page-btn" style="border:none;background:transparent;cursor:default">&hellip;</span>
        @endif
    @endif

    {{-- Page numbers --}}
    @for($p = $startPage; $p <= $endPage; $p++)
        <button class="page-btn @if($p === $currentPage) active @endif" type="button" onclick="window.location.href='{{ route($route, array_merge($queryParams, ['page' => $p, 'per_page' => $perPage])) }}'">
            {{ $p }}
        </button>
    @endfor

    {{-- Last page --}}
    @if($showEnd)
        @if($endPage < $totalPages - 1)
            <span class="page-btn" style="border:none;background:transparent;cursor:default">&hellip;</span>
        @endif
        <button class="page-btn" type="button" onclick="window.location.href='{{ route($route, array_merge($queryParams, ['page' => $totalPages, 'per_page' => $perPage])) }}'">
            {{ $totalPages }}
        </button>
    @endif

    {{-- Next Button --}}
    <button class="page-btn" type="button" @if($nextDisabled) disabled @else onclick="window.location.href='{{ route($route, array_merge($queryParams, ['page' => $currentPage + 1, 'per_page' => $perPage])) }}'" @endif>
        <i class="bi bi-chevron-right"></i>
    </button>
</nav>
