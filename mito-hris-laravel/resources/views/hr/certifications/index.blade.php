@extends('layouts.portal')

@section('title', 'Certification Management - MITO HRIS')
@section('page-title', 'Certification Management')
@section('page-subtitle', 'Kelola klasifikasi SNI, ISO, K3, Food Safety, dan Product Safety')

@section('content')
    <section class="page-section active" id="pageCertification">
        @include('hr.certifications.partials.stats')

        <div class="panel" id="certificationPanel">
            <div class="panel-header">
                <div>
                    <h6>Daftar Sertifikasi</h6>
                    <div class="panel-subtitle">SNI untuk produk, ISO untuk sistem perusahaan, K3 untuk keselamatan kerja, serta klasifikasi food dan product safety.</div>
                </div>
                <div class="export-btns d-flex flex-wrap gap-2">
                    @can('manage_certification')
                        <button class="btn btn-sm text-white fw-semibold"
                            style="background:#166534;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                            type="button" data-bs-toggle="modal" data-bs-target="#addCertificationModal"
                            id="btnAddCertification">
                            <i class="bi bi-plus-lg me-1"></i>Add Certification
                        </button>
                    @endcan
                </div>
            </div>

            {{-- FILTER BAR --}}
            <form action="{{ $certIndexPath ?? route('certificates.portal.index') }}" method="GET"
                id="certificationFilterForm">
                <input type="hidden" name="page" value="1">
                <div class="filter-bar employee-filter-bar">
                    <div class="table-search">
                        <i class="bi bi-search"></i>
                        <input type="text" name="search" id="certSearchInput"
                            placeholder="Cari kode, nama, karyawan, penerbit, nomor... "
                            value="{{ request('search', '') }}" />
                    </div>
                    <select class="filter-select" name="type" id="certTypeFilter" data-auto-submit="true">
                        <option value="">Semua Klasifikasi</option>
                        @foreach (\App\Enums\CertType::cases() as $type)
                            <option value="{{ $type->value }}" {{ request('type') === $type->value ? 'selected' : '' }}>
                                {{ $type->label() }} - {{ $type->description() }}
                            </option>
                        @endforeach
                    </select>
                    <select class="filter-select" name="status" id="certStatusFilter" data-auto-submit="true">
                        <option value="">Semua Status</option>
                        @foreach (\App\Enums\CertStatus::cases() as $st)
                            <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>
                                {{ $st->label() }}
                            </option>
                        @endforeach
                    </select>
                    <div class="employee-filter-actions">
                        <a href="{{ $certIndexPath ?? route('certificates.portal.index') }}"
                            class="btn-reset-filter text-decoration-none" title="Reset filter">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </a>
                        <button class="btn-refresh" type="button" title="Muat ulang" data-refresh="page">
                            <i class="bi bi-arrow-repeat"></i>
                        </button>
                    </div>
                </div>
            </form>

            @include('hr.certifications.partials.table')
        </div>

        @include('hr.certifications.partials.modals')
    </section>
@endsection

@section('styles')
    @vite(['resources/scss/modules/_certification.scss'])
@endsection

@section('scripts')
    <script>
        window.MITO_CERT_BASE = '{{ $certBasePath ?? '/certifications' }}';
    </script>
    @vite(['resources/js/certification.js'])
@endsection
