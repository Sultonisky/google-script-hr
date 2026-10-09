<div class="table-responsive">
    <table class="table hr-table">
        <thead>
            <tr>
                <th>Kode Aset</th>
                <th>Nama Aset</th>
                @foreach ($listFields as $field)
                    <th>{{ $field['label'] }}</th>
                @endforeach
                <th>Lokasi</th>
                <th>Status</th>
                <th>Kondisi</th>
                <th>Assigned To</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($assets as $asset)
                <tr>
                    <td class="id-mono">{{ $asset->asset_code ?: '—' }}</td>
                    <td>
                        <div class="cand-name fw-bold text-navy">{{ $asset->name }}</div>
                        @if ($asset->description)
                            <div class="cand-sub">{{ \Illuminate\Support\Str::limit($asset->description, 60) }}</div>
                        @endif
                    </td>
                    @foreach ($listFields as $field)
                        <td>{{ $asset->{$field['name']} ?: '—' }}</td>
                    @endforeach
                    <td>{{ $asset->location ?: '—' }}</td>
                    <td>
                        <span class="badge-status {{ $asset->status?->badgeClass() ?? 'pending' }}">
                            {{ $asset->status?->label() ?? $asset->status }}
                        </span>
                    </td>
                    <td>
                        <span class="badge-status {{ $asset->condition_status?->badgeClass() ?? 'pending' }}">
                            {{ $asset->condition_status?->label() ?? $asset->condition_status }}
                        </span>
                    </td>
                    <td>{{ $asset->activeAssignment?->employee_name ?: '—' }}</td>
                    <td>
                        <a class="btn btn-sm btn-outline-info" href="{{ route($routeName . '.show', $asset->id) }}" title="Lihat detail">
                            <i class="bi bi-eye"></i>
                        </a>
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#asset-history-{{ $asset->id }}" title="Riwayat penugasan">
                            <i class="bi bi-clock-history"></i>
                        </button>
                        @can($permissionPrefix . '.update')
                            <a class="btn btn-sm btn-outline-primary" href="{{ route($routeName . '.edit', $asset->id) }}" aria-label="Edit {{ $asset->name }}">
                                <i class="bi bi-pencil"></i>
                            </a>
                        @endcan
                        @can($permissionPrefix . '.assign')
                            @if ($asset->status === \App\Enums\AssetStatus::AVAILABLE)
                                <button class="btn btn-sm btn-outline-success" type="button" data-bs-toggle="modal" data-bs-target="#asset-assign-{{ $asset->id }}" title="Tugaskan">
                                    <i class="bi bi-person-plus"></i>
                                </button>
                            @endif
                        @endcan
                        @can($permissionPrefix . '.generate_code')
                            @if (blank($asset->asset_code))
                                <form method="POST" action="{{ route($routeName . '.generate-code', $asset->id) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-warning" type="submit" title="Buat kode aset">
                                        <i class="bi bi-magic"></i>
                                    </button>
                                </form>
                            @endif
                        @endcan
                        @can($permissionPrefix . '.delete')
                            @if ($asset->status !== \App\Enums\AssetStatus::DISPOSED)
                                <button
                                    class="btn btn-sm btn-outline-danger"
                                    type="button"
                                    data-bs-toggle="modal"
                                    data-bs-target="#asset-disposal-confirm-modal"
                                    data-disposal-url="{{ route($routeName . '.destroy', $asset->id) }}"
                                    data-asset-code="{{ $asset->asset_code ?: '—' }}"
                                    data-asset-name="{{ $asset->name }}"
                                    aria-label="Disposisi {{ $asset->name }}"
                                    title="Disposisi aset">
                                    <i class="bi bi-trash"></i>
                                </button>
                            @endif
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($listFields) + 7 }}">
                        <div class="table-empty">
                            <i class="bi bi-inbox"></i>
                            <p>Belum ada data {{ strtolower($title) }}.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
