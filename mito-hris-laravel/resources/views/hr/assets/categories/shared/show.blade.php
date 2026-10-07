@extends('layouts.portal')

@section('title', $title . ' Asset Detail - MITO HRIS')
@section('page-title', 'Detail ' . $title . ' Asset')
@section('page-subtitle', $asset->asset_code ?: 'Tanpa kode aset')

@section('content')
    @php
        $status = $asset->status;
        $condition = $asset->condition_status;
    @endphp
    <section class="page-section active">
        <div class="panel mb-4">
            <div class="panel-header justify-content-between">
                <div>
                    <h6>{{ $asset->name }}</h6>
                    <div class="panel-subtitle">{{ $asset->asset_code ?: 'Kode aset belum tersedia' }} · {{ $title }}</div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-outline-secondary" href="{{ route($routeName . '.index') }}">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </a>
                    <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#asset-history-{{ $asset->id }}">
                        <i class="bi bi-clock-history me-1"></i>Riwayat
                    </button>
                    @can($permissionPrefix . '.update')
                        <a class="btn btn-primary" href="{{ route($routeName . '.edit', $asset->id) }}">
                            <i class="bi bi-pencil me-1"></i>Edit
                        </a>
                    @endcan
                    @can($permissionPrefix . '.assign')
                        @if ($status === \App\Enums\AssetStatus::AVAILABLE && !$asset->activeAssignment)
                            <button class="btn btn-success" type="button" data-bs-toggle="modal" data-bs-target="#asset-assign-{{ $asset->id }}">
                                <i class="bi bi-person-plus me-1"></i>Tugaskan
                            </button>
                        @endif
                    @endcan
                </div>
            </div>
            <div class="p-3 p-md-4">
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <span class="badge-status {{ $status?->badgeClass() ?? 'pending' }}">
                        {{ $status?->label() ?? $status }}
                    </span>
                    <span class="badge-status {{ $condition?->badgeClass() ?? 'pending' }}">
                        Kondisi: {{ $condition?->label() ?? $condition }}
                    </span>
                </div>

                <div class="row g-3">
                    @foreach ($fields as $field)
                        @php
                            $value = $asset->{$field['name']};
                            if ($value instanceof \BackedEnum) {
                                $value = method_exists($value, 'label') ? $value->label() : $value->value;
                            } elseif ($value instanceof \DateTimeInterface) {
                                $value = $value->format('d/m/Y');
                            } elseif ($field['name'] === 'purchase_price' && filled($value)) {
                                $value = 'Rp ' . number_format((float) $value, 0, ',', '.');
                            }
                            $wideField = in_array($field['name'], ['description', 'address', 'notes'], true);
                        @endphp
                        <div class="{{ $wideField ? 'col-12' : 'col-12 col-md-6 col-xl-4' }}">
                            <div class="border rounded-3 h-100 p-3">
                                <div class="small text-muted mb-1">{{ $field['label'] }}</div>
                                <div class="fw-semibold text-break" style="white-space: pre-line">{{ filled($value) ? $value : '—' }}</div>
                            </div>
                        </div>
                    @endforeach
                    @if ($asset->activeAssignment)
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="border rounded-3 h-100 p-3">
                                <div class="small text-muted mb-1">Penanggung Jawab</div>
                                <div class="fw-semibold">{{ $asset->activeAssignment->employee_name ?: $asset->activeAssignment->employee_id }}</div>
                                <div class="small text-muted">{{ $asset->activeAssignment->employee_id }}</div>
                            </div>
                        </div>
                    @endif
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="border rounded-3 h-100 p-3">
                            <div class="small text-muted mb-1">Terakhir Diperbarui</div>
                            <div class="fw-semibold">{{ $asset->updated_at?->format('d/m/Y H:i') ?: '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('hr.assets.categories.shared.modals')
    </section>
@endsection
