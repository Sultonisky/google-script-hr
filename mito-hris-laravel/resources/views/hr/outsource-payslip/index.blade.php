@extends('layouts.hr')

@section('title', 'Payslip Outsource - MITO HRIS')
@section('page-title', 'Payslip Outsource')
@section('page-subtitle', 'Rekap payslip karyawan outsource per periode')

@php
    $rupiah = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    $days = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
@endphp

@section('content')
    <section class="page-section active" id="pageOutsourcePayslip">

        <div class="panel mt-2" id="outsourcePayslipPanel">
            <div class="panel-header">
                <div>
                    <h6>Payslip Outsource &middot; {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $periodFilter)->locale('id')->translatedFormat('F Y') }}</h6>
                    <div class="panel-subtitle">
                        @if ($total > 0)
                            Menampilkan {{ ($currentPage - 1) * $perPage + 1 }}–{{ min($currentPage * $perPage, $total) }}
                            dari {{ $total }} data
                        @else
                            Belum ada payslip untuk periode ini
                        @endif
                    </div>
                </div>
                <div class="export-btns d-flex flex-wrap gap-2">
                    <a class="btn btn-sm fw-semibold text-white"
                        style="background:#005bac;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                        href="{{ route('hr.outsource-payslips.export', ['period' => $periodFilter, 'search' => $searchFilter]) }}">
                        <i class="bi bi-download me-1"></i>Export Excel
                    </a>
                    @can('manage_outsource_payslip')
                        <button class="btn btn-sm fw-semibold text-white"
                            style="background:#198754;border:none;border-radius:8px;padding:6px 14px;font-size:13px"
                            type="button"
                            data-bs-toggle="modal"
                            data-bs-target="#outsourcePayslipImportModal"
                            id="btnImportOutsourcePayslip">
                            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Import Excel
                        </button>
                    @endcan
                </div>
            </div>

            <form action="{{ route('hr.outsource-payslips.index') }}" method="GET" id="opsFilterForm">
                <div class="filter-bar">
                    <div class="table-search">
                        <i class="bi bi-search"></i>
                        <input type="text" name="search" id="opsSearchInput"
                            placeholder="Cari Outsource ID atau nama..." value="{{ $searchFilter }}" />
                    </div>
                    <select class="filter-select" name="period" id="opsPeriodSelect" data-auto-submit="true">
                        @foreach ($periods->contains($periodFilter) ? $periods : $periods->prepend($periodFilter) as $period)
                            <option value="{{ $period }}" {{ $period === $periodFilter ? 'selected' : '' }}>
                                {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $period)->locale('id')->translatedFormat('F Y') }}
                            </option>
                        @endforeach
                    </select>
                    <button class="btn-reset-filter" type="submit"><i class="bi bi-search"></i> Cari</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table hr-table">
                    <thead>
                        <tr>
                            <th>Outsource ID</th>
                            <th>Nama</th>
                            <th>Vendor</th>
                            <th class="text-end">Total HKE</th>
                            <th class="text-end">Gaji Pokok</th>
                            <th class="text-end">Pot. BPJS Kesehatan</th>
                            <th class="text-end">Pot. Pinjaman</th>
                            <th class="text-end">THP</th>
                        </tr>
                    </thead>
                    <tbody id="opsTableBody">
                        @forelse ($payslips as $payslip)
                            @php
                                $detail = [
                                    'period' => \Illuminate\Support\Carbon::createFromFormat('Y-m', $payslip->period)->locale('id')->translatedFormat('F Y'),
                                    'outsourceId' => $payslip->outsource_id,
                                    'fullName' => $payslip->full_name ?? '-',
                                    'vendor' => $payslip->vendor ?? '-',
                                    'hke' => $days($payslip->hke),
                                    'basicSalary' => $rupiah($payslip->basic_salary),
                                    'bpjsKesehatan' => $rupiah($payslip->bpjs_kesehatan_deduction),
                                    'loan' => $rupiah($payslip->loan_deduction),
                                    'totalDeduction' => $rupiah((float) $payslip->bpjs_kesehatan_deduction + (float) $payslip->loan_deduction),
                                    'takeHomePay' => $rupiah($payslip->take_home_pay),
                                    'sourceFile' => $payslip->source_file ?? '-',
                                    'importedBy' => $payslip->imported_by ?? '-',
                                    'createdAt' => $payslip->created_at?->timezone('Asia/Jakarta')->locale('id')->translatedFormat('j F Y') ?? '-',
                                ];
                            @endphp
                            <tr>
                                <td class="id-mono fw-bold">{{ $payslip->outsource_id }}</td>
                                <td>
                                    <button type="button" class="btn btn-link p-0 cand-name fw-bold text-primary text-decoration-underline text-start"
                                        data-payslip-detail="{{ json_encode($detail) }}">{{ $payslip->full_name ?? '-' }}</button>
                                </td>
                                <td>{{ $payslip->vendor ?? '-' }}</td>
                                <td class="text-end id-mono">{{ $days($payslip->hke) }}</td>
                                <td class="text-end id-mono">{{ $rupiah($payslip->basic_salary) }}</td>
                                <td class="text-end id-mono">{{ $rupiah($payslip->bpjs_kesehatan_deduction) }}</td>
                                <td class="text-end id-mono">{{ (float) $payslip->loan_deduction > 0 ? $rupiah($payslip->loan_deduction) : '-' }}</td>
                                <td class="text-end id-mono fw-bold">{{ $rupiah($payslip->take_home_pay) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="table-empty">
                                        <i class="bi bi-inbox"></i>
                                        <p>Belum ada payslip untuk periode ini.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="panel-footer">
                <span>
                    @if ($total > 0)
                        Menampilkan {{ ($currentPage - 1) * $perPage + 1 }}–{{ min($currentPage * $perPage, $total) }}
                        dari {{ $total }} data
                    @else
                        Tidak ada data
                    @endif
                </span>
                @if ($total > $perPage)
                    <x-pagination :currentPage="$currentPage" :total="$total" :perPage="$perPage" :route="'hr.outsource-payslips.index'"
                        :queryParams="['search' => $searchFilter, 'period' => $periodFilter, 'per_page' => $perPage]" />
                @endif
            </div>
        </div>

    </section>

    @include('hr.partials.outsource-payslip-detail-modal')

    @can('manage_outsource_payslip')
        @include('hr.partials.outsource-payslip-import-modal', ['defaultPeriod' => $periodFilter])
    @endcan
@endsection

@section('scripts')
    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        window.addEventListener('load', function() {
            var message = null;
            try {
                message = sessionStorage.getItem('opsImportToast');
                sessionStorage.removeItem('opsImportToast');
            } catch (e) {}
            if (message && typeof window.showToast === 'function') window.showToast(message, 'success');
        });

        var opsSearchInput = document.getElementById('opsSearchInput');
        if (opsSearchInput) {
            opsSearchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    document.getElementById('opsFilterForm').submit();
                }
            });
        }
    </script>
@endsection
