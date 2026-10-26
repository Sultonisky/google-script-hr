<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Exit Clearance - {{ $employee->fullName }}</title>
    <style>
        @page {
            margin: 10mm 11mm 10mm 11mm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 8.5pt;
            color: #1f2937;
            line-height: 1.3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header {
            border: 1.5px solid #111827;
            margin-bottom: 6px;
        }

        .header td {
            padding: 4px 8px;
            vertical-align: middle;
        }

        .doc-title {
            font-size: 12pt;
            font-weight: bold;
            letter-spacing: 0.4px;
            color: #111827;
        }

        .brand {
            width: 34%;
            text-align: right;
        }

        .brand-logo {
            height: 36px;
            width: auto;
        }

        .section {
            margin-top: 8px;
        }

        .section-title {
            font-size: 9pt;
            font-weight: bold;
            color: #eb1c24;
            margin: 0 0 3px;
            padding-bottom: 2px;
            border-bottom: 1px solid #f3c3c5;
        }

        .split td.col {
            width: 50%;
            vertical-align: top;
            border: none;
            padding: 0;
        }

        .split td.col-left {
            padding-right: 5px;
        }

        .split td.col-right {
            padding-left: 5px;
        }

        .sheet td,
        .sheet th {
            border: 1px solid #d1d5db;
            padding: 4px 6px;
            vertical-align: middle;
        }

        .sheet th {
            background: #f9fafb;
            font-size: 8.5pt;
            text-align: center;
            font-weight: bold;
        }

        .label {
            width: 42%;
            font-weight: bold;
            background: #f9fafb;
            white-space: nowrap;
        }

        .tick {
            font-family: 'DejaVu Sans', sans-serif;
        }

        .box {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #111827;
            text-align: center;
            line-height: 11px;
            font-size: 9pt;
            margin-right: 6px;
        }

        .item {
            width: 78%;
            white-space: nowrap;
        }

        .check {
            width: 22%;
            text-align: center;
            font-size: 10pt;
        }

        .money-label {
            width: 28%;
            font-weight: bold;
            background: #f9fafb;
        }

        .sign td {
            border: 1px solid #d1d5db;
            width: 33.33%;
            padding: 6px 6px 7px;
            vertical-align: top;
        }

        .sign-title {
            text-align: center;
            font-weight: bold;
            font-size: 8pt;
        }

        .sign-hint {
            text-align: center;
            color: #6b7280;
            font-size: 7pt;
            margin-bottom: 2px;
        }

        .sign-space {
            height: 50px;
            border-bottom: 1px dashed #9ca3af;
            margin: 6px 0 6px;
        }

        .sign-meta {
            width: 100%;
            margin-top: 2px;
        }

        .sign-meta td {
            border: none;
            padding: 1px 0 0;
            vertical-align: baseline;
            font-size: 7.5pt;
        }

        .sign-key {
            width: 48px;
            color: #4b5563;
            padding-right: 6px;
        }

        .sign-date {
            white-space: nowrap;
        }
    </style>
</head>
<body>
    @php
        $position = $employee->jobPosition ?: ($employee->jobPositionLocation ?: '');
        $division = $employee->division ?: ($employee->department ?: '');
        $moneyStatuses = \App\Support\ExitClearanceForm::MONEY_STATUSES;
        $mitoLogoPath = public_path('assets/mito-group.png');
        $mitoLogo = is_file($mitoLogoPath)
            ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($mitoLogoPath))
            : '';
    @endphp

    <table class="header">
        <tr>
            <td class="doc-title">EXIT CLEARANCE FORM</td>
            <td class="brand">
                @if ($mitoLogo !== '')
                    <img src="{{ $mitoLogo }}" alt="MITO group" class="brand-logo">
                @endif
            </td>
        </tr>
    </table>

    <table class="split">
        <tr>
            <td class="col col-left">
                <div class="section">
                    <div class="section-title">Data Karyawan</div>
                    <table class="sheet">
                        <tr>
                            <td class="label">Nama</td>
                            <td>{{ $employee->fullName ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="label">NIK</td>
                            <td>{{ $employee->nikNpwp ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Employee ID</td>
                            <td>{{ $employee->employeeId ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Posisi</td>
                            <td>{{ $position !== '' ? $position : '—' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Divisi</td>
                            <td>{{ $division !== '' ? $division : '—' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Perusahaan</td>
                            <td>{{ $extraData['company_name'] ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Berakhir bekerja</td>
                            <td>{{ $extraData['last_working_date'] ?: '—' }}</td>
                        </tr>
                    </table>
                </div>

                <div class="section">
                    <div class="section-title">Serah Terima Berkas/Dokumen</div>
                    <table class="sheet">
                        <tr>
                            <td class="label">Hardcopy</td>
                            <td class="check">@if ($extraData['hardcopy'])<span class="tick">✓</span>@else-@endif</td>
                        </tr>
                        <tr>
                            <td class="label">Softcopy</td>
                            <td class="check">@if ($extraData['softcopy'])<span class="tick">✓</span>@else-@endif</td>
                        </tr>
                        <tr>
                            <td class="label">Didelegasi kepada</td>
                            <td>{{ $extraData['delegated_to'] ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Tanggal penyerahan</td>
                            <td>{{ $extraData['handover_date'] ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Lainnya</td>
                            <td>{{ ($extraData['handover_other'] ?? '') !== '' ? $extraData['handover_other'] : '-' }}</td>
                        </tr>
                    </table>
                </div>

                <div class="section">
                    <div class="section-title">Aset Divisi</div>
                    <table class="sheet">
                        @foreach ($extraData['division'] as $row)
                            <tr>
                                <td class="label">{{ $row['label'] }}</td>
                                <td>{{ $row['value'] !== '' ? $row['value'] : '-' }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </td>
            <td class="col col-right">
                <div class="section">
                    <div class="section-title">Aset HR &amp; GA</div>
                    <table class="sheet">
                        @foreach ($extraData['ga'] as $row)
                            <tr>
                                <td class="item">{{ $row['label'] }}</td>
                                <td class="check">@if ($row['checked'])<span class="tick">✓</span>@else-@endif</td>
                            </tr>
                        @endforeach
                    </table>
                </div>

                <div class="section">
                    <div class="section-title">IT</div>
                    <table class="sheet">
                        @foreach ($extraData['it'] as $row)
                            <tr>
                                <td class="label">{{ $row['label'] }}</td>
                                <td>{{ $row['value'] !== '' ? $row['value'] : '-' }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Accounting</div>
        <table class="sheet">
            <tr>
                <th class="money-label">Item</th>
                <th>Status</th>
                <th>Nominal</th>
                <th>Telah diserahkan kepada</th>
            </tr>
            @foreach (['petty' => 'Petty Cash', 'loan' => 'Pinjaman'] as $key => $label)
                <tr>
                    <td class="money-label">{{ $label }}</td>
                    <td>
                        @foreach ($moneyStatuses as $statusKey => $statusLabel)
                            <span class="box">@if (($extraData[$key]['status'] ?? '') === $statusKey)<span class="tick">✓</span>@endif</span>{{ $statusLabel }}
                            &nbsp;
                        @endforeach
                    </td>
                    <td>{{ $extraData[$key]['amount'] ?: '—' }}</td>
                    <td>{{ $extraData[$key]['to'] ?: '—' }}</td>
                </tr>
            @endforeach
            <tr>
                <td class="money-label">Lainnya</td>
                <td colspan="3">{{ $extraData['accounting_other'] ?: '—' }}</td>
            </tr>
        </table>
    </div>

    <div class="section approval">
        <div class="section-title">Approval</div>
        <table class="sign">
            @foreach (array_chunk($extraData['approvers'], 3) as $row)
                <tr>
                    @foreach ($row as $approver)
                        <td>
                            <div class="sign-title">{{ $approver['title'] }}</div>
                            <div class="sign-hint">({{ $approver['hint'] }})</div>
                            <div class="sign-space"></div>
                            <table class="sign-meta">
                                <tr>
                                    <td class="sign-key">Nama</td>
                                    <td>{{ $approver['name'] !== '' ? $approver['name'] : '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="sign-key">Tanggal</td>
                                    <td class="sign-date">{{ $approver['date'] !== '' ? $approver['date'] : '—' }}</td>
                                </tr>
                            </table>
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    </div>
</body>
</html>
