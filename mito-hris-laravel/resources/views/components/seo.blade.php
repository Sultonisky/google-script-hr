@php
    $seoTitle = $title ?? 'MITO Career Portal | Job Opportunities';
    $seoDescription =
        $description ?? 'Temukan informasi rekrutmen dan peluang karir yang tersedia melalui MITO HRIS Career Portal.';
    $seoRobots = $robots ?? 'index,follow';
    $seoType = $type ?? 'website';
    $seoImage = $image ?? asset(config('seo.default_image'));
    $seoCanonical = $canonical ?? rtrim(config('seo.canonical_base_url'), '/') . request()->getPathInfo();
    $seoStructuredData = $structuredData ?? null;
@endphp

<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDescription }}">
<meta name="robots" content="{{ $seoRobots }}">
<meta name="author" content="MITO HRIS">
<meta name="theme-color" content="#eb1c24">
<link rel="canonical" href="{{ $seoCanonical }}">
<meta property="og:type" content="{{ $seoType }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $seoCanonical }}">
<meta property="og:image" content="{{ $seoImage }}">
<meta property="og:site_name" content="{{ config('seo.site_name') }}">
<meta property="og:locale" content="id_ID">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<meta name="twitter:image" content="{{ $seoImage }}">

@if ($seoStructuredData)
    <script type="application/ld+json">{!! json_encode($seoStructuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endif
