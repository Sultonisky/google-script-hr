<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Surat Peringatan Tertulis - {{ $employee->fullName }}</title>
    <style>
        @page {
            margin: 38px 54px 42px;
        }

        body {
            color: #111;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            line-height: 1.28;
        }

        .letterhead {
            margin: 0 0 12px;
            padding: 0 0 8px;
            border-bottom: 1.5px solid #d87943;
            text-align: center;
        }

        .company-name {
            color: #eb1c24;
            font-size: 17pt;
            font-weight: bold;
            line-height: 1.2;
        }

        .company-address {
            margin-top: 2px;
            font-size: 8pt;
            line-height: 1.35;
        }

        .title-block {
            margin-bottom: 15px;
            text-align: center;
        }

        .title {
            font-size: 10pt;
            font-weight: bold;
            text-decoration: underline;
        }

        .number {
            font-size: 9pt;
            font-weight: bold;
        }

        .company-intro {
            margin: 0 0 8px;
            font-weight: bold;
        }

        .intro {
            margin: 0 0 2px;
        }

        .employee-table {
            margin: 0 0 10px 36px;
            border-collapse: collapse;
        }

        .employee-table td {
            padding: 0;
            vertical-align: top;
        }

        .employee-label {
            width: 76px;
        }

        .employee-colon {
            width: 10px;
        }

        p {
            margin: 0 0 8px;
            text-align: justify;
        }

        .violation-list {
            margin: 0 0 10px 20px;
            padding-left: 14px;
        }

        .violation-list li {
            margin-bottom: 3px;
            padding-left: 2px;
            text-align: justify;
        }

        .signatures {
            width: 100%;
            margin-top: 18px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }

        .signatures td {
            width: 50%;
            padding: 0;
            vertical-align: top;
        }

        .signatures .employee-sign,
        .signatures .company-sign {
            text-align: center;
        }

        .signature-date {
            padding-bottom: 8px !important;
            text-align: center;
        }

        .sign-space {
            height: 72px;
        }

        .sign-name {
            font-weight: bold;
        }

        .acknowledgement {
            margin-top: 12px;
            text-align: center;
            page-break-inside: avoid;
        }

        .acknowledgement-space {
            height: 34px;
        }
    </style>
</head>

<body>
    @php
        $monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $formatDate = static function (?string $value) use ($monthNames): string {
            if (! $value) {
                return '-';
            }

            $date = \Illuminate\Support\Carbon::parse($value);

            return $date->day.' '.$monthNames[$date->month - 1].' '.$date->year;
        };

        $companyName = $company['name'] ?? 'PT MAHAKARYA SUKSES INDONESIA';
        $companyAddress = $company['address'] ?? '';
        $companyCity = $company['city'] ?? 'Tangerang';
        $letterNumber = trim((string) ($extraData['sk_number'] ?? ''));
        if (! preg_match('#^\d+/SP/#i', $letterNumber)) {
            $letterNumber = '';
        }

        $employeeName = trim((string) ($employee->fullName ?? '')) ?: '-';
        $employeeNik = ltrim(trim((string) ($employee->nikNpwp ?? '')), "'") ?: '-';
        $employeePosition = trim((string) ($employee->jobPosition ?? $employee->jobPositionLocation ?? '')) ?: '-';
        $employeeLocation = trim((string) ($employee->lokasiKerja ?? $employee->areaKerja ?? '')) ?: '-';
        $directSuperior = trim((string) ($employee->directSuperior ?? '')) ?: '-';
        $superiorPosition = trim((string) ($extraData['superior_position'] ?? 'Atasan Langsung')) ?: 'Atasan Langsung';

        $level = \App\Enums\WarningLetterLevel::tryFrom((string) ($extraData['level'] ?? 'SP1'))
            ?? \App\Enums\WarningLetterLevel::SP1;
        $isFinal = $level === \App\Enums\WarningLetterLevel::SP1_FINAL;
        $letterName = match ($level) {
            \App\Enums\WarningLetterLevel::SP1 => 'Surat Peringatan Tertulis ke 1',
            \App\Enums\WarningLetterLevel::SP1_FINAL => 'Surat Peringatan Tertulis Pertama dan Terakhir (SP1 dan Terakhir)',
            \App\Enums\WarningLetterLevel::SP2 => 'Surat Peringatan Tertulis ke 2',
            \App\Enums\WarningLetterLevel::SP3 => 'Surat Peringatan Tertulis ke 3',
        };
        $validMonths = max(1, (int) ($extraData['validity_months'] ?? $level->validityMonths()));
        $numberWords = [1 => 'satu', 2 => 'dua', 3 => 'tiga', 4 => 'empat', 5 => 'lima', 6 => 'enam'];
        $validityText = $validMonths % 12 === 0
            ? ($validMonths / 12).' ('.($numberWords[$validMonths / 12] ?? $validMonths / 12).') tahun'
            : $validMonths.' ('.($numberWords[$validMonths] ?? $validMonths).') bulan';
        $regulation = trim((string) ($extraData['regulation_reference'] ?? ''))
            ?: trim((string) ($extraData['violation_category'] ?? 'Pelanggaran Peraturan Perusahaan'));
        $violationLines = collect(preg_split('/\r\n|\r|\n/', trim((string) ($extraData['violation_description'] ?? ''))))
            ->map(fn ($line) => trim(preg_replace('/^\s*(?:[-*\x{2022}]|\d+[.)])\s*/u', '', $line)))
            ->filter()
            ->values();
        $docDate = (string) ($extraData['doc_date'] ?? '');
        $docDateFormatted = $formatDate($docDate);
    @endphp

    <div class="letterhead">
        <div class="company-name">{{ $companyName }}</div>
        <div class="company-address">{{ $companyAddress }}</div>
    </div>

    <div class="title-block">
        <div class="title">SURAT PERINGATAN TERTULIS</div>
        @if ($letterNumber !== '')
            <div class="number">Nomor: {{ $letterNumber }}</div>
        @endif
    </div>

    @unless ($isFinal)
        <div class="company-intro">{{ $companyName }}</div>
    @endunless
    <p class="intro">Dengan ini diberikan {{ $letterName }} {{ $isFinal ? 'kepada:' : 'Kepada:' }}</p>

    <table class="employee-table">
        <tr>
            <td class="employee-label">Nama</td>
            <td class="employee-colon">:</td>
            <td>{{ $employeeName }}</td>
        </tr>
        <tr>
            <td class="employee-label">NIK</td>
            <td class="employee-colon">:</td>
            <td>{{ $employeeNik }}</td>
        </tr>
        <tr>
            <td class="employee-label">Jabatan</td>
            <td class="employee-colon">:</td>
            <td>{{ $employeePosition }}</td>
        </tr>
        <tr>
            <td class="employee-label">Lokasi</td>
            <td class="employee-colon">:</td>
            <td>{{ $employeeLocation }}</td>
        </tr>
    </table>

    <p>
        Karena telah melakukan tindakan pelanggaran terhadap Tata Tertib dan Peraturan Perusahaan berupa:
    </p>
    <ol class="violation-list">
        @forelse ($violationLines as $violationLine)
            <li>
                @if ($regulation !== '')
                    <strong>{{ $regulation }}:</strong>
                @endif
                {{ $violationLine }}
                @if ($loop->first && ! empty($extraData['incident_date']))
                    (Tanggal kejadian: {{ $formatDate($extraData['incident_date']) }})
                @endif
            </li>
        @empty
            <li>{{ $regulation }}</li>
        @endforelse
    </ol>

    @if ($isFinal)
        <p>
            Berdasarkan pelanggaran-pelanggaran tersebut, Perusahaan memberikan Surat Peringatan Tertulis Pertama dan
            Terakhir (SP1 dan Terakhir) kepada Saudara sebagai bentuk pembinaan dan penegakan disiplin kerja.
        </p>
        <p>
            Surat Peringatan Tertulis Pertama dan Terakhir ini berlaku selama 1 (satu) tahun sesuai dengan ketentuan
            Pasal 47 ayat (1) Peraturan Perusahaan.
        </p>
        <p>
            Selama masa berlaku Surat Peringatan ini, Saudara wajib memperbaiki kedisiplinan, mematuhi waktu kerja yang
            telah ditentukan, melaksanakan seluruh tugas dan tanggung jawab sesuai dengan ketentuan yang berlaku, serta
            menaati setiap perintah dan/atau instruksi yang diberikan oleh Atasan maupun Pimpinan Perusahaan.
        </p>
        <p>
            Apabila Saudara kembali melakukan pelanggaran terhadap Tata Tertib dan/atau Peraturan Perusahaan selama masa
            berlaku Surat Peringatan Pertama dan Terakhir ini, maka Perusahaan dapat mengambil tindakan lebih lanjut
            termasuk Pemutusan Hubungan Kerja (PHK) sesuai dengan ketentuan Peraturan Perusahaan dan perundang-undangan
            ketenagakerjaan yang berlaku.
        </p>
        <p>
            Demikian Surat Peringatan Tertulis Pertama dan Terakhir ini diberikan untuk menjadi perhatian dan
            dilaksanakan dengan penuh tanggung jawab.
        </p>
    @else
        <p>
            {{ $letterName }} ini berlaku {{ $validityText }}, apabila setelah mendapat Surat
            Peringatan Tertulis ini Saudara melakukan kembali tindakan pelanggaran disiplin maupun pelanggaran di dalam
            Peraturan Perusahaan, maka Perusahaan dapat memberikan sanksi sesuai dengan Peraturan Perusahaan /
            Undang-Undang Ketenagakerjaan yang berlaku.
        </p>
        <p>
            Demikian Surat Peringatan Tertulis ini diberikan, dan agar dapatnya Saudara memperbaiki tindakan pelanggaran
            tersebut.
        </p>
    @endif

    <table class="signatures">
        <tr>
            <td colspan="2" class="signature-date">{{ $companyCity }}, {{ $docDateFormatted }}</td>
        </tr>
        <tr>
            <td class="employee-sign">
                Yang Bersangkutan
                <div class="sign-space"></div>
                <span class="sign-name">{{ $employeeName }}</span><br>
                {{ $employeePosition }}
            </td>
            <td class="company-sign">
                {{ $companyName }}<br>
                Atasan
                <div class="sign-space"></div>
                <span class="sign-name">{{ $directSuperior }}</span><br>
                {{ $superiorPosition }}
            </td>
        </tr>
    </table>

    <div class="acknowledgement">
        Mengetahui
        <div class="acknowledgement-space"></div>
        <span class="sign-name">Hisar Hesti</span><br>
        {{ $isFinal ? 'Human Resources Manager' : 'Human Resources & Legal Manager' }}
    </div>
</body>

</html>
