@extends('layouts.portal')

@section('title', ($asset ? 'Edit ' : 'Tambah ') . $title . ' Asset - MITO HRIS')
@section('page-title', ($asset ? 'Edit ' : 'Tambah ') . $title . ' Asset')
@section('page-subtitle', 'Lengkapi informasi aset ' . $title)

@section('content')
    @php
        $identityNames = ['asset_code', 'name', 'description'];
        $inventoryNames = ['purchase_date', 'purchase_price', 'condition_status', 'status', 'location', 'notes'];
        $groups = [
            [
                'title' => 'Identitas Aset',
                'description' => 'Informasi dasar dan identitas aset.',
                'icon' => 'bi-box-seam',
                'fields' => array_values(array_filter($fields, fn (array $field): bool => in_array($field['name'], $identityNames, true))),
            ],
            [
                'title' => 'Detail ' . $title,
                'description' => 'Informasi khusus sesuai kategori aset.',
                'icon' => 'bi-card-list',
                'fields' => array_values(array_filter($fields, fn (array $field): bool => !in_array($field['name'], [...$identityNames, ...$inventoryNames], true))),
            ],
            [
                'title' => 'Kondisi & Pengadaan',
                'description' => 'Status, lokasi, dan informasi pembelian aset.',
                'icon' => 'bi-clipboard-data',
                'fields' => array_values(array_filter($fields, fn (array $field): bool => in_array($field['name'], $inventoryNames, true))),
            ],
        ];
    @endphp

    <section class="page-section active">
        <div class="panel">
            <div class="panel-header d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h6 class="mb-1">{{ $asset ? 'Edit' : 'Tambah' }} Aset {{ $title }}</h6>
                    <div class="panel-subtitle">Kolom bertanda <span class="text-danger">*</span> wajib diisi.</div>
                </div>
                <a class="btn btn-outline-secondary" href="{{ route($routeName . '.index') }}">
                    <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar
                </a>
            </div>

            <form method="POST" action="{{ $asset ? route($routeName . '.update', $asset->id) : route($routeName . '.store') }}">
                @csrf
                @if ($asset)
                    @method('PUT')
                @endif

                <div class="p-3 p-md-4">
                    @foreach ($groups as $group)
                        @if (count($group['fields']) > 0)
                            <section class="border rounded-3 p-3 p-md-4 mb-3" aria-labelledby="form-section-{{ $loop->index }}">
                                <div class="d-flex align-items-start gap-3 border-bottom pb-3 mb-4">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light text-primary flex-shrink-0 p-2">
                                        <i class="bi {{ $group['icon'] }}"></i>
                                    </span>
                                    <div>
                                        <h6 class="mb-1" id="form-section-{{ $loop->index }}">{{ $group['title'] }}</h6>
                                        <p class="text-muted small mb-0">{{ $group['description'] }}</p>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    @foreach ($group['fields'] as $field)
                                        @php
                                            $fieldValue = old($field['name'], $asset?->getRawOriginal($field['name']));
                                            if ($fieldValue instanceof \BackedEnum) {
                                                $fieldValue = $fieldValue->value;
                                            }
                                            $isWideField = in_array($field['type'], ['textarea'], true) || $field['name'] === 'address';
                                            $columnClass = $isWideField ? 'col-12' : 'col-12 col-md-6 col-xl-4';
                                            if ($field['name'] === 'name') {
                                                $columnClass = 'col-12 col-md-8';
                                            } elseif ($field['name'] === 'asset_code') {
                                                $columnClass = 'col-12 col-md-4';
                                            }
                                        @endphp

                                        <div class="{{ $columnClass }}">
                                            <label class="form-label" for="field-{{ $field['name'] }}">
                                                {{ $field['label'] }}@if (!empty($field['required'])) <span class="text-danger">*</span>@endif
                                            </label>

                                            @if ($field['type'] === 'textarea')
                                                <textarea
                                                    @class(['form-control', 'is-invalid' => $errors->has($field['name'])])
                                                    id="field-{{ $field['name'] }}"
                                                    name="{{ $field['name'] }}"
                                                    rows="3"
                                                    @if ($errors->has($field['name'])) aria-invalid="true" aria-describedby="field-{{ $field['name'] }}-error" @endif
                                                    @required(!empty($field['required']))>{{ $fieldValue }}</textarea>
                                            @elseif ($field['type'] === 'select')
                                                <select
                                                    @class(['form-select', 'is-invalid' => $errors->has($field['name'])])
                                                    id="field-{{ $field['name'] }}"
                                                    name="{{ $field['name'] }}"
                                                    @if ($errors->has($field['name'])) aria-invalid="true" aria-describedby="field-{{ $field['name'] }}-error" @endif
                                                    @required(!empty($field['required']))>
                                                    <option value="">Pilih {{ $field['label'] }}</option>
                                                    @foreach ($field['options'] as $option)
                                                        <option value="{{ $option }}" @selected((string) $fieldValue === (string) $option)>{{ $option }}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input
                                                    @class(['form-control', 'is-invalid' => $errors->has($field['name'])])
                                                    id="field-{{ $field['name'] }}"
                                                    name="{{ $field['name'] }}"
                                                    type="{{ $field['type'] }}"
                                                    value="{{ $fieldValue }}"
                                                    @if (isset($field['step'])) step="{{ $field['step'] }}" @endif
                                                    @if ($errors->has($field['name'])) aria-invalid="true" aria-describedby="field-{{ $field['name'] }}-error" @endif
                                                    @required(!empty($field['required']))>
                                            @endif

                                            @if ($field['name'] === 'asset_code' && !$asset)
                                                <div class="form-text">Kosongkan untuk membuat kode aset otomatis saat disimpan.</div>
                                            @endif
                                            @error($field['name'])
                                                <div class="invalid-feedback d-block" id="field-{{ $field['name'] }}-error">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    @endforeach

                    <div class="d-flex flex-column-reverse flex-sm-row justify-content-end gap-2 pt-2">
                        <a class="btn btn-outline-secondary" href="{{ route($routeName . '.index') }}">Batal</a>
                        <button class="btn btn-primary px-4" type="submit">
                            <i class="bi bi-check-lg me-1"></i>{{ $asset ? 'Simpan Perubahan' : 'Simpan Aset' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection
