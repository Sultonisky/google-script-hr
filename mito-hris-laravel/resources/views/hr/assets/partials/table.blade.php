{{-- Asset Table --}}
<div class="table-responsive">
    <table class="table hr-table">
        <thead>
            <tr>
                <th>Kode Aset</th>
                <th>Nama Aset</th>
                <th>Kategori</th>
                <th>Brand / Model</th>
                <th>Serial Number</th>
                <th>Lokasi</th>
                <th>Status</th>
                <th>Kondisi</th>
                <th>Assigned To</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody id="assetTableBody">
            @forelse($assets as $asset)
                <tr data-asset-id="{{ $asset->id }}" id="asset-row-{{ $asset->id }}">
                    <td class="id-mono">
                        @if(!empty($asset->asset_code))
                            <span class="fw-semibold">{{ $asset->asset_code }}</span>
                        @else
                            <span class="text-muted fst-italic">—</span>
                        @endif
                    </td>
                    <td>
                        <div class="cand-name fw-bold text-navy">{{ $asset->name }}</div>
                        @if($asset->description)
                            <div class="cand-sub">{{ Str::limit($asset->description, 40) }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="badge-status {{ $asset->category->badgeClass() }}">{{ $asset->category->label() }}</span>
                    </td>
                    <td>
                        {{ $asset->brand ?? '-' }}
                        @if($asset->model) <span class="text-muted">/ {{ $asset->model }}</span> @endif
                    </td>
                    <td class="id-mono">{{ $asset->serial_number ?? '-' }}</td>
                    <td>{{ $asset->location ?? '-' }}</td>
                    <td>
                        <span class="badge-status {{ $asset->status->badgeClass() }}">{{ $asset->status->label() }}</span>
                    </td>
                    <td>
                        <span class="badge-status {{ $asset->condition_status->badgeClass() }}">{{ $asset->condition_status->label() }}</span>
                    </td>
                    <td>
                        @if($asset->activeAssignment)
                            <span class="fw-semibold">{{ $asset->activeAssignment->employee_name ?? $asset->activeAssignment->employee_id }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1 flex-nowrap">
                            <button class="btn btn-sm btn-outline-primary asset-view-btn"
                                data-asset-id="{{ $asset->id }}" title="View">
                                <i class="bi bi-eye"></i>
                            </button>
                            @can('edit_asset')
                                <button class="btn btn-sm btn-outline-secondary asset-edit-btn"
                                    data-asset-id="{{ $asset->id }}" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                @if($asset->status->isAssignable())
                                    <button class="btn btn-sm btn-outline-success asset-assign-btn"
                                        data-asset-id="{{ $asset->id }}" title="Assign">
                                        <i class="bi bi-person-plus"></i>
                                    </button>
                                @endif
                                @if($asset->status->isReturnable())
                                    <button class="btn btn-sm btn-outline-warning asset-return-btn"
                                        data-asset-id="{{ $asset->id }}" title="Return">
                                        <i class="bi bi-arrow-return-left"></i>
                                    </button>
                                @endif
                                @if(!empty(trim($asset->asset_code ?? '')) === false)
                                    <button class="btn btn-sm btn-outline-info asset-gencode-btn"
                                        data-asset-id="{{ $asset->id }}" title="Generate Code">
                                        <i class="bi bi-magic"></i>
                                    </button>
                                @endif
                                @if($asset->status->value !== \App\Enums\AssetStatus::DISPOSED->value)
                                    <button class="btn btn-sm btn-outline-danger asset-dispose-btn"
                                        data-asset-id="{{ $asset->id }}"
                                        data-asset-code="{{ $asset->asset_code ?? '—' }}"
                                        data-name="{{ $asset->name }}"
                                        data-category="{{ $asset->category->label() }}"
                                        title="Dispose">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endif
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10">
                        <div class="table-empty">
                            <i class="bi bi-inbox"></i>
                            <p>Belum ada data aset.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="panel-footer">
    <span id="assetFooterCount">
        @if ($total > 0)
            Menampilkan {{ ($currentPage - 1) * $perPage + 1 }}–{{ min($currentPage * $perPage, $total) }}
            dari {{ $total }} data
        @else
            Tidak ada data
        @endif
    </span>
    @if ($total > $perPage)
        <x-pagination :currentPage="$currentPage" :total="$total" :perPage="$perPage" :route="'hr.assets.index'"
            :queryParams="[
                'search' => request('search'),
                'category' => request('category'),
                'status' => request('status'),
                'condition' => request('condition'),
                'location' => request('location'),
                'assignment' => request('assignment'),
                'per_page' => $perPage,
            ]" />
    @endif
</div>