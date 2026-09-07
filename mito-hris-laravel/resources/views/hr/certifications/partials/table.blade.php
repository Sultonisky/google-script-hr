{{-- Certification Table --}}
<div class="table-responsive">
    <table class="table hr-table">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Sertifikasi</th>
                <th>Klasifikasi</th>
                <th>Karyawan</th>
                <th>Penerbit</th>
                <th>Terbit</th>
                <th>Kedaluwarsa</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody id="certificationTableBody">
            @forelse($certifications as $cert)
                <tr data-certification-id="{{ $cert->id }}" id="certification-row-{{ $cert->id }}">
                    <td class="id-mono">
                        @if(!empty($cert->cert_code))
                            <span class="fw-semibold">{{ $cert->cert_code }}</span>
                        @else
                            <span class="text-muted fst-italic">—</span>
                        @endif
                    </td>
                    <td>
                        <div class="cand-name fw-bold text-navy">{{ $cert->name }}</div>
                        @if($cert->product_scope || $cert->brand)
                            <div class="cand-sub">{{ $cert->product_scope ?: '-' }}{{ $cert->brand ? ' · ' . $cert->brand : '' }}</div>
                        @endif
                        @if($cert->description)
                            <div class="cand-sub">{{ Str::limit($cert->description, 40) }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="badge-status {{ $cert->cert_type->badgeClass() }}">{{ $cert->cert_type->label() }}</span>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $cert->employee_name }}</div>
                        <div class="cand-sub">{{ $cert->employee_id }}</div>
                    </td>
                    <td>{{ $cert->issuing_organization }}</td>
                    <td>{{ $cert->issue_date ? $cert->issue_date->format('d M Y') : '-' }}</td>
                    <td>{{ $cert->expiry_date ? $cert->expiry_date->format('d M Y') : '-' }}</td>
                    <td>
                        <span class="badge-status {{ $cert->status->badgeClass() }}">{{ $cert->status->label() }}</span>
                    </td>
                    <td>
                        <div class="d-flex gap-1 flex-nowrap">
                            <button class="btn btn-sm btn-outline-primary certification-view-btn"
                                data-certification-id="{{ $cert->id }}" title="View">
                                <i class="bi bi-eye"></i>
                            </button>
                            @can('manage_certification')
                                <button class="btn btn-sm btn-outline-secondary certification-edit-btn"
                                    data-certification-id="{{ $cert->id }}" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                @if(empty(trim($cert->cert_code ?? '')))
                                    <button class="btn btn-sm btn-outline-info certification-gencode-btn"
                                        data-certification-id="{{ $cert->id }}" title="Generate Code">
                                        <i class="bi bi-magic"></i>
                                    </button>
                                @endif
                                <button class="btn btn-sm btn-outline-danger certification-delete-btn"
                                    data-certification-id="{{ $cert->id }}" data-name="{{ $cert->name }}"
                                    data-cert-code="{{ $cert->cert_code ?? '—' }}"
                                    data-employee="{{ ($cert->employee_name ?? '') ? $cert->employee_name . ' (' . ($cert->employee_id ?? '') . ')' : '—' }}"
                                    title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">
                        <div class="table-empty">
                            <i class="bi bi-inbox"></i>
                            <p>Belum ada data sertifikasi.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="panel-footer">
    <span id="certificationFooterCount">
        @if ($total > 0)
            Menampilkan {{ ($currentPage - 1) * $perPage + 1 }}–{{ min($currentPage * $perPage, $total) }}
            dari {{ $total }} data
        @else
            Tidak ada data
        @endif
    </span>
    @if ($total > $perPage)
        <x-pagination :currentPage="$currentPage" :total="$total" :perPage="$perPage" :route="'certificates.portal.index'"
            :queryParams="[
                'search' => request('search'),
                'type' => request('type'),
                'status' => request('status'),
                'employee' => request('employee'),
                'per_page' => $perPage,
            ]" />
    @endif
</div>
