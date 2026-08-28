<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('components.seo', [
        'title' => 'Halaman Tidak Ditemukan - MITO Career Portal',
        'description' => 'Halaman yang Anda cari tidak tersedia di MITO HRIS Career Portal.',
        'robots' => 'noindex,nofollow,noarchive',
    ])
    <link rel="icon" type="image/png" href="{{ asset('assets/logo-favicon.png') }}">
    @vite(['resources/scss/app.scss', 'resources/scss/public.scss', 'resources/js/app.js'])
</head>

<body class="bg-light">
    <main class="container py-5 text-center">
        <h1 class="h2">Halaman Tidak Ditemukan</h1>
        <p class="text-muted">Posisi atau halaman yang Anda cari mungkin sudah tidak tersedia.</p>
        <a class="btn btn-danger" href="{{ route('public.career.index') }}">Kembali ke Portal Karir</a>
    </main>
</body>

</html>
