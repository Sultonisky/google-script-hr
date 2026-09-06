@extends('layouts.hr')

@section('title', 'Asset Management - MITO HRIS')
@section('page-title', 'Asset Management')
@section('page-subtitle', 'InVENTORY dan pelacakan aset perusahaan')

@section('content')
<section class="page-section active" id="pageAsset">
    @include('hr.assets.partials.stats')

    <div class="panel" id="assetPanel">
        <div class="panel-header">
            <div>
                <h6>Daftar Aset</h6>
                <div class="panel-subtitle">Kelola seluruh aset perusahaan beserta penugasan dan riwayatnya.</div>
            </div>
            <div class="export-btns d-flex flex-wrap gap-2">
                @can('edit_asset')
                    <button class="btn btn-sm text-white fw-semibold"
                        style="background:#0B2540;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                        type="button" data-bs-toggle="modal" data-bs-target="#bulkGenerateModal"
                        id="btnBulkGenerate">
                        <i class="bi bi-magic me-1"></i>Generate Asset Codes
                    </button>
                    <button class="btn btn-sm text-white fw-semibold"
                        style="background:#166534;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                        type="button" data-bs-toggle="modal" data-bs-target="#addAssetModal"
                        id="btnAddAsset">
                        <i class="bi bi-plus-lg me-1"></i>Add Asset
                    </button>
                @endcan
            </div>
        </div>

        {{-- FILTER BAR --}}
        <form action="{{ $assetIndexPath ?? route('hr.assets.index') }}" method="GET" id="assetFilterForm">
            <input type="hidden" name="page" value="1">
            <div class="filter-bar employee-filter-bar">
                <div class="table-search">
                    <i class="bi bi-search"></i>
                    <input type="text" name="search" id="assetSearchInput"
                        placeholder="Cari kode, nama, serial number, brand, model..."
                        value="{{ request('search', '') }}" />
                </div>
                <select class="filter-select" name="category" id="assetCategoryFilter" data-auto-submit="true">
                    <option value="">Semua Kategori</option>
                    @foreach(\App\Enums\AssetCategory::cases() as $cat)
                        <option value="{{ $cat->value }}" {{ request('category') === $cat->value ? 'selected' : '' }}>
                            {{ $cat->label() }} ({{ $stats['categories'][$cat->label()] ?? 0 }})
                        </option>
                    @endforeach
                </select>
                <select class="filter-select" name="status" id="assetStatusFilter" data-auto-submit="true">
                    <option value="">Semua Status ({{ $stats['total'] ?? 0 }})</option>
                    @foreach(\App\Enums\AssetStatus::cases() as $st)
                        @php($countKey = strtolower($st->value))
                        <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>
                            {{ $st->label() }} ({{ $stats[$countKey] ?? 0 }})
                        </option>
                    @endforeach
                </select>
                <select class="filter-select" name="condition" id="assetConditionFilter" data-auto-submit="true">
                    <option value="">Semua Kondisi</option>
                    @foreach(\App\Enums\AssetCondition::cases() as $cond)
                        <option value="{{ $cond->value }}" {{ request('condition') === $cond->value ? 'selected' : '' }}>
                            {{ $cond->label() }}
                        </option>
                    @endforeach
                </select>
                <select class="filter-select" name="location" id="assetLocationFilter" data-auto-submit="true">
                    <option value="">Semua Lokasi</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc }}" {{ request('location') === $loc ? 'selected' : '' }}>{{ $loc }}</option>
                    @endforeach
                </select>
                <select class="filter-select" name="assignment" id="assetAssignmentFilter" data-auto-submit="true">
                    <option value="">Semua Penugasan</option>
                    <option value="assigned" {{ request('assignment') === 'assigned' ? 'selected' : '' }}>Assigned</option>
                    <option value="unassigned" {{ request('assignment') === 'unassigned' ? 'selected' : '' }}>Unassigned</option>
                </select>
                <div class="employee-filter-actions">
                    <a href="{{ $assetIndexPath ?? route('hr.assets.index') }}" class="btn-reset-filter text-decoration-none" title="Reset filter">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </a>
                    <button class="btn-refresh" type="button" title="Muat ulang" data-refresh="page">
                        <i class="bi bi-arrow-repeat"></i>
                    </button>
                </div>
            </div>
        </form>

        @include('hr.assets.partials.table')
    </div>

    @include('hr.assets.partials.modals')
</section>
@endsection

@section('styles')
@vite(['resources/scss/modules/_asset.scss'])
@endsection

@section('scripts')
<script>window.MITO_ASSET_BASE = '{{ $assetBasePath ?? '/hr/assets' }}';</script>
@vite(['resources/js/asset.js'])
@endsection
