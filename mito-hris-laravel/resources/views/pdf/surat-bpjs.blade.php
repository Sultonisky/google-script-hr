<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Surat Keterangan BPJS - {{ $employee->fullName }}</title>
    <style>
        @page {
            margin: 30px 40px;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11px;
            color: #1f2937;
            line-height: 1.55;
            text-align: justify;
        }

        .doc-title {
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            color: #0b2540;
            margin-top: 10px;
        }

        .doc-no {
            font-size: 10pt;
            text-align: center;
            color: #6b7280;
            margin-bottom: 16px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
        }

        .data-table td {
            padding: 3px 4px;
            vertical-align: top;
        }

        .label {
            width: 25%;
            font-weight: bold;
        }

        .colon {
            width: 3%;
        }

        .value {
            width: 72%;
        }

        .sign-table {
            width: 100%;
            margin-top: 26px;
            page-break-inside: avoid;
        }

        .sign-table td {
            text-align: right;
            padding-right: 20px;
            vertical-align: top;
        }

        .sign-image-area {
            height: 58px;
            margin: 2px 0 2px auto;
        }

        .sign-image-area .hr-sign-img {
            height: 48px !important;
            width: auto;
            max-width: 125px;
            margin: 0 0 4px auto;
        }
    </style>
</head>

<body>

    @include('pdf.components.kop-surat')

    @php
        // Nomor surat BPJS dinonaktifkan (kode generate tetap ada di bawah untuk referensi).
        // $romanMonth = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        // $letterNumber =
        //     $extraData['letter_number'] ??
        //     ($extraData['sk_number'] ??
        //         '001/HRD-SKK/' . ($company['code'] ?? 'MSI') . '/' . $romanMonth[date('n') - 1] . '/' . date('Y'));
        $letterNumber = trim((string) (
            $extraData['letter_number'] ?? $extraData['sk_number'] ?? $extraData['skNumber'] ?? ''
        ));

        $endDate =
            $extraData['effective_date'] ??
            ($extraData['last_working_date'] ?? ($employee->resignDate ?? ($employee->endDateContract ?? null)));
        $endFmt = $endDate ? \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y') : date('d F Y');
        $joinFmt = $employee->joinDate ? \Carbon\Carbon::parse($employee->joinDate)->translatedFormat('d F Y') : '-';

        $bpjsTk = ltrim($employee->bpjsKetenagakerjaan ?? '-', "'");
        $empAddress = trim((string) ($employee->residentialAddress ?? ''));
        if ($empAddress === '') {
            $empAddress = trim((string) ($employee->citizenIdAddress ?? ''));
        }
        $empAddress = $empAddress !== '' ? $empAddress : '-';
        $companyName = $company['name'] ?? 'PT MAHAKARYA SUKSES INDONESIA';
        $companyAddr = $company['address'] ?? '-';
        $companyCity = $company['city'] ?? 'Jakarta';
        $todayFmt = date('d F Y');
    @endphp

    <div class="doc-title">SURAT KETERANGAN</div>
    @if ($letterNumber !== '')
        <div class="doc-no">Nomor: {{ $letterNumber }}</div>
    @endif

    <p>Yang bertandatangan di bawah ini:</p>

    <table class="data-table">
        <tr>
            <td class="label">Nama</td>
            <td class="colon">:</td>
            <td class="value"><strong>Hisar Hesti</strong></td>
        </tr>
        <tr>
            <td class="label">Jabatan</td>
            <td class="colon">:</td>
            <td class="value">Human Resources (HR) &amp; Legal Manager</td>
        </tr>
        <tr>
            <td class="label">Alamat</td>
            <td class="colon">:</td>
            <td class="value">{{ $companyAddr }}</td>
        </tr>
    </table>

    <p>Dengan ini menerangkan bahwa:</p>

    <table class="data-table">
        <tr>
            <td class="label">Nama</td>
            <td class="colon">:</td>
            <td class="value"><strong>{{ $employee->fullName }}</strong></td>
        </tr>
        <tr>
            <td class="label">NIK</td>
            <td class="colon">:</td>
            <td class="value">{{ ltrim($employee->nikNpwp ?? '-', "'") }}</td>
        </tr>
        <tr>
            <td class="label">Alamat</td>
            <td class="colon">:</td>
            <td class="value">{{ $empAddress }}</td>
        </tr>
        <tr>
            <td class="label">Jabatan</td>
            <td class="colon">:</td>
            <td class="value">{{ $employee->jobPosition ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Periode Kerja</td>
            <td class="colon">:</td>
            <td class="value">{{ $joinFmt }} s.d {{ $endFmt }}</td>
        </tr>
    </table>

    <p>Benar telah bekerja pada <strong>{{ $companyName }}</strong> selama periode tersebut.</p>

    <p>Demikian Surat Keterangan ini dibuat untuk keperluan administrasi di BPJS Ketenagakerjaan.</p>

    <table class="sign-table">
        <tr>
            <td>
                {{ $companyCity }}, {{ $todayFmt }}<br>
                Hormat Kami,<br>
                <strong>{{ $companyName }}</strong><br>
                <div class="sign-image-area">
                    @include('pdf.components.hr-sign')
                </div>
                <strong><u>Hisar Hesti</u></strong><br>
                HR &amp; Legal Manager
            </td>
        </tr>
    </table>

</body>

</html>
