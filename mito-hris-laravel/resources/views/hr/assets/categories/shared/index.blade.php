@extends('layouts.portal')

@section('title', $title . ' Assets - MITO HRIS')
@section('page-title', $title . ' Assets')
@section('page-subtitle', 'Kelola data aset kategori ' . $title)

@section('content')
    <section class="page-section active">
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h6>Daftar {{ $title }}</h6>
                    <div class="panel-subtitle">{{ $assets->total() }} aset terdaftar.</div>
                </div>
                <div class="export-btns">
                    @can($permissionPrefix . '.create')
                        <a class="btn btn-primary" href="{{ route($routeName . '.create') }}">
                            <i class="bi bi-plus-lg me-1"></i>Tambah {{ $title }}
                        </a>
                    @endcan
                    @can($permissionPrefix . '.generate_code')
                        @if ($missingCodeCount > 0)
                            <form method="POST" action="{{ route($routeName . '.generate-bulk-codes') }}">
                                @csrf
                                <button class="btn btn-outline-info" type="submit">
                                    <i class="bi bi-magic me-1"></i>Generate {{ $missingCodeCount }} Kode
                                </button>
                            </form>
                        @endif
                    @endcan
                </div>
            </div>

            <form method="GET" action="{{ route($routeName . '.index') }}" class="filter-bar employee-filter-bar asset-category-filter mb-3">
                <div class="table-search">
                    <i class="bi bi-search"></i>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari kode, nama, atau detail aset...">
                </div>
                <select class="filter-select" name="status" data-auto-submit="true">
                    <option value="">Semua Status</option>
                    @foreach (\App\Enums\AssetStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
                <select class="filter-select" name="condition_status" data-auto-submit="true">
                    <option value="">Semua Kondisi</option>
                    @foreach (\App\Enums\AssetCondition::cases() as $condition)
                        <option value="{{ $condition->value }}" @selected(request('condition_status') === $condition->value)>{{ $condition->label() }}</option>
                    @endforeach
                </select>
                <div class="asset-filter-actions">
                    <button class="btn btn-outline-secondary asset-filter-icon" type="submit" aria-label="Cari aset" title="Cari aset">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </button>
                    <a class="btn btn-outline-danger asset-filter-icon" href="{{ route($routeName . '.index') }}" aria-label="Reset filter" title="Reset filter">
                        <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                    </a>
                </div>
            </form>

            @include('hr.assets.categories.shared.table')

            <div class="panel-footer">
                <span>
                    @if ($assets->total() > 0)
                        Menampilkan {{ $assets->firstItem() }}–{{ $assets->lastItem() }} dari {{ $assets->total() }} aset
                    @else
                        Tidak ada aset
                    @endif
                </span>
                @if ($assets->total() > $assets->perPage())
                    <x-pagination
                        :currentPage="$assets->currentPage()"
                        :total="$assets->total()"
                        :perPage="$assets->perPage()"
                        :route="$routeName . '.index'"
                        :queryParams="[
                            'search' => request('search'),
                            'status' => request('status'),
                            'condition_status' => request('condition_status'),
                        ]"
                    />
                @endif
            </div>
        </div>
        @include('hr.assets.categories.shared.modals')
    </section>
@endsection
