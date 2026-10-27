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

        .pair {
            margin-top: 6px;
            table-layout: fixed;
        }

        .pair col.col-label,
        .pair col.col-value {
            width: 24.8%;
        }

        .pair col.col-gap {
            width: 0.8%;
        }

        .pair td.gap {
            width: 0.8%;
            border: none;
            padding: 0;
            background: #fff;
            font-size: 0;
            line-height: 0;
        }

        .pair td.pair-title {
            border: none;
            border-bottom: 1px solid #f3c3c5;
            color: #eb1c24;
            font-size: 9pt;
            font-weight: bold;
            padding: 2px 0 2px;
            background: #fff;
        }

        .pair td.data {
            height: 16px;
        }

        .pair .label,
        .pair .item {
            width: auto;
            white-space: normal;
        }

        .pair td.blank {
            border: none;
            background: #fff;
            padding: 0;
        }

        .pair td.pair-title-next {
            padding-top: 8px;
        }

        .pair td.head {
            background: #f9fafb;
            font-weight: bold;
            text-align: center;
        }

        .pair td.status {
            white-space: nowrap;
            font-size: 8pt;
        }

        .pair td.status .box {
            margin-right: 3px;
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

        .sign-line {
            font-size: 7.5pt;
            line-height: 1.2;
            white-space: nowrap;
        }

        .sign-line .sign-key,
        .sign-line .sign-sep {
            display: inline-block;
            vertical-align: bottom;
            line-height: 1.2;
        }

        .sign-line .sign-key {
            width: 46px;
            color: #4b5563;
            text-align: left;
        }

        .sign-line .sign-sep {
            width: 10px;
            text-align: center;
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
        $employeeRows = [
            ['label' => 'Nama', 'value' => $employee->fullName ?: '-'],
            ['label' => 'NIK', 'value' => $employee->nikNpwp ?: '-'],
            ['label' => 'Employee ID', 'value' => $employee->employeeId ?: '-'],
            ['label' => 'Posisi', 'value' => $position !== '' ? $position : '-'],
            ['label' => 'Divisi', 'value' => $division !== '' ? $division : '-'],
            ['label' => 'Perusahaan', 'value' => $extraData['company_name'] ?: '-'],
            ['label' => 'Berakhir bekerja', 'value' => $extraData['last_working_date'] ?: '-'],
        ];
        $gaRows = array_values($extraData['ga']);
        $handoverRows = [
            ['label' => 'Hardcopy', 'check' => (bool) $extraData['hardcopy']],
            ['label' => 'Softcopy', 'check' => (bool) $extraData['softcopy']],
            ['label' => 'Didelegasi kepada', 'value' => $extraData['delegated_to'] ?: '-'],
            ['label' => 'Tanggal penyerahan', 'value' => $extraData['handover_date'] ?: '-'],
            ['label' => 'Lainnya', 'value' => ($extraData['handover_other'] ?? '') !== '' ? $extraData['handover_other'] : '-'],
        ];
        $itRows = array_values($extraData['it']);
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

    <table class="sheet pair">
        <colgroup>
            <col class="col-label">
            <col class="col-value">
            <col class="col-gap">
            <col class="col-label">
            <col class="col-value">
        </colgroup>
        <tr>
            <td class="pair-title" colspan="2">Data Karyawan</td>
            <td class="gap"></td>
            <td class="pair-title" colspan="2">Aset HR &amp; GA</td>
        </tr>
        @foreach ($employeeRows as $index => $row)
            <tr>
                <td class="data label">{{ $row['label'] }}</td>
                <td class="data">{{ $row['value'] }}</td>
                <td class="gap"></td>
                <td class="data label">{{ $gaRows[$index]['label'] ?? '' }}</td>
                <td class="data">
                    @if ($gaRows[$index]['checked'] ?? false)
                        <span class="tick">✓</span>
                    @else
                        -
                    @endif
                </td>
            </tr>
        @endforeach
        <tr>
            <td class="pair-title pair-title-next" colspan="2">Serah Terima Berkas/Dokumen</td>
            <td class="gap"></td>
            <td class="pair-title pair-title-next" colspan="2">IT</td>
        </tr>
        @foreach ($handoverRows as $index => $row)
            <tr>
                <td class="data label">{{ $row['label'] }}</td>
                <td class="data">
                    @if (array_key_exists('check', $row))
                        @if ($row['check'])<span class="tick">✓</span>@else-@endif
                    @else
                        {{ $row['value'] }}
                    @endif
                </td>
                <td class="gap"></td>
                <td class="data label">{{ $itRows[$index]['label'] ?? '' }}</td>
                <td class="data">{{ ($itRows[$index]['value'] ?? '') !== '' ? $itRows[$index]['value'] : '-' }}</td>
            </tr>
        @endforeach
        <tr>
            <td class="pair-title pair-title-next" colspan="2">Aset Divisi</td>
            <td class="gap"></td>
            <td class="blank" colspan="2"></td>
        </tr>
        @foreach ($extraData['division'] as $row)
            <tr>
                <td class="data label">{{ $row['label'] }}</td>
                <td class="data">{{ $row['value'] !== '' ? $row['value'] : '-' }}</td>
                <td class="gap"></td>
                <td class="blank" colspan="2"></td>
            </tr>
        @endforeach
        <tr>
            <td class="pair-title pair-title-next" colspan="5">Accounting</td>
        </tr>
        <tr>
            <td class="data head">Item</td>
            <td class="data head" colspan="2">Status</td>
            <td class="data head">Nominal</td>
            <td class="data head">Telah diserahkan kepada</td>
        </tr>
        @foreach (['petty' => 'Petty Cash', 'loan' => 'Pinjaman'] as $key => $label)
            <tr>
                <td class="data label">{{ $label }}</td>
                <td class="data status" colspan="2">
                    @foreach ($moneyStatuses as $statusKey => $statusLabel)
                        <span class="box">@if (($extraData[$key]['status'] ?? '') === $statusKey)<span class="tick">✓</span>@endif</span>{{ $statusLabel }}@if (! $loop->last) &nbsp; @endif
                    @endforeach
                </td>
                <td class="data">{{ $extraData[$key]['amount'] ?: '-' }}</td>
                <td class="data">{{ $extraData[$key]['to'] ?: '-' }}</td>
            </tr>
        @endforeach
        <tr>
            <td class="data label">Lainnya</td>
            <td class="data" colspan="4">{{ $extraData['accounting_other'] ?: '-' }}</td>
        </tr>
    </table>

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
                            <div class="sign-line"><span class="sign-key">Nama</span><span class="sign-sep">:</span>&nbsp;{{ $approver['name'] !== '' ? $approver['name'] : '-' }}</div>
                            <div class="sign-line"><span class="sign-key">Tanggal</span><span class="sign-sep">:</span>&nbsp;<span class="sign-date">{{ $approver['date'] !== '' ? $approver['date'] : '-' }}</span></div>
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    </div>
</body>
</html>
