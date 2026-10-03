<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Surat Panggilan Kerja II</title>
    <style>
        @page {
            margin: 28px 58px 34px;
        }

        body {
            color: #111;
            font-family: 'Times New Roman', Times, serif;
            font-size: 9pt;
            line-height: 1.22;
        }

        .letterhead {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1.5px solid #111;
            margin-bottom: 9px;
            padding-bottom: 5px;
        }

        .letterhead td {
            vertical-align: middle;
        }

        .logo-cell {
            width: 34%;
        }

        .logo {
            width: 145px;
            height: auto;
        }

        .company {
            text-align: center;
        }

        .company-name {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            font-weight: bold;
        }

        .company-address {
            font-family: Arial, sans-serif;
            font-size: 7pt;
            line-height: 1.2;
        }

        .title {
            margin: 8px 0 0;
            font-size: 11pt;
            font-weight: bold;
            text-align: center;
        }

        .subtitle {
            margin: 0 0 14px;
            text-align: center;
        }

        .letter-date {
            margin-bottom: 9px;
            text-align: right;
        }

        .metadata,
        .recipient-table,
        .meeting {
            border-collapse: collapse;
            table-layout: fixed;
        }

        .metadata {
            width: 100%;
            margin: 0 0 12px;
        }

        .recipient-table {
            width: 100%;
            margin: 0 0 14px;
        }

        .metadata td,
        .recipient-table td,
        .meeting td {
            padding: 0;
            vertical-align: top;
        }

        .field,
        .name-field {
            width: 70px;
        }

        .recipient {
            margin: 0 0 2px;
        }

        p {
            margin: 0 0 7px;
            text-align: justify;
        }

        .action-list {
            margin: 0 0 7px 20px;
            padding-left: 13px;
        }

        .action-list li {
            margin-bottom: 2px;
            padding-left: 2px;
            text-align: justify;
        }

        .meeting {
            width: 88%;
            margin: 1px 0 8px 12%;
        }

        .signature {
            margin: 20px 0 0;
            text-align: left;
            page-break-inside: avoid;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }

        .copies {
            margin: 8px 0 0;
            text-align: left;
            page-break-inside: avoid;
        }

        .copies ol {
            margin: 1px 0 0 16px;
            padding-left: 12px;
        }
    </style>
</head>

<body>
    @php
        $isStein = strtoupper((string) ($company['code'] ?? '')) === 'SPI';
        $monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $formatDate = static function (?string $date, bool $includeDay = false) use ($monthNames, $dayNames): string {
            if (! $date) {
                return '-';
            }

            $parsedDate = \Illuminate\Support\Carbon::parse($date);
            $formatted = $parsedDate->day.' '.$monthNames[$parsedDate->month - 1].' '.$parsedDate->year;

            return $includeDay ? $dayNames[$parsedDate->dayOfWeek].', '.$formatted : $formatted;
        };
        $employeeName = trim((string) ($employee->fullName ?? '')) ?: '-';
        $employeePosition = trim((string) ($employee->jobPositionLocation ?: $employee->jobPosition)) ?: '-';
        $employeeNik = ltrim(trim((string) ($employee->nikNpwp ?? '')), "'") ?: '-';
        $employeeAddress = trim((string) ($employee->residentialAddress ?: $employee->citizenIdAddress)) ?: '-';
        $letterNumber = trim((string) ($extraData['sk_number'] ?? '')) ?: '-';
        $firstSummonsNumber = trim((string) ($extraData['first_summons_number'] ?? '')) ?: '-';
        $firstSummonsDate = $formatDate($extraData['first_summons_date'] ?? null);
        $workingDays = max(1, (int) ($extraData['working_days'] ?? 1));
        $companyName = $company['name'] ?? 'PT MAHAKARYA SUKSES INDONESIA';
    @endphp

    @if ($isStein)
        @php
            $steinLogoPath = public_path('assets/stein-pdf.png');
            $steinLogo = 'data:image/png;base64,' . base64_encode(file_get_contents($steinLogoPath));
        @endphp
        <table class="letterhead">
            <tr>
                <td class="logo-cell">
                    <img class="logo" src="{{ $steinLogo }}" alt="Stein Cookware">
                </td>
                <td class="company">
                    <div class="company-name">{{ $companyName }}</div>
                    <div class="company-address">{{ $company['address'] }}</div>
                </td>
            </tr>
        </table>
    @else
        @include('pdf.components.kop-surat')
    @endif

    <div class="title">SURAT PANGGILAN KERJA II</div>
    <div class="subtitle">(Panggilan Kedua/Terakhir karena Ketidakhadiran Tanpa Keterangan)</div>
    <div class="letter-date">{{ $company['city'] ?? 'Jakarta' }}, {{ $formatDate($extraData['doc_date'] ?? null) }}</div>

    <div>
        <table class="metadata">
            <colgroup>
                <col style="width:70px">
                <col>
            </colgroup>
            <tr>
                <td class="field">Nomor</td>
                <td>: {{ $letterNumber }}</td>
            </tr>
            <tr>
                <td class="field">Lampiran</td>
                <td>: 2</td>
            </tr>
            <tr>
                <td class="field">Perihal</td>
                <td>: <strong>Panggilan Kerja II (Terakhir)</strong></td>
            </tr>
        </table>

        <div class="recipient">Kepada Yth.</div>
        <table class="recipient-table">
            <colgroup>
                <col style="width:70px">
                <col>
            </colgroup>
            <tr>
                <td class="name-field">Nama</td>
                <td>: {{ $employeeName }}</td>
            </tr>
            <tr>
                <td class="field">Jabatan</td>
                <td>: {{ $employeePosition }}</td>
            </tr>
            <tr>
                <td class="field">NIK/NIP</td>
                <td>: {{ $employeeNik }}</td>
            </tr>
            <tr>
                <td class="field">Alamat</td>
                <td>: {{ $employeeAddress }}</td>
            </tr>
        </table>
        <div class="recipient">di tempat</div>
    </div>

    <p>Dengan hormat,</p>
    <p>
        Merujuk Surat Panggilan Kerja I Nomor <strong>{{ $firstSummonsNumber }}</strong> tanggal
        <strong>{{ $firstSummonsDate }}</strong>, perihal ketidakhadiran Saudara/i, sampai dengan diterbitkannya surat ini
        Saudara/i belum juga hadir bekerja dan/atau menyampaikan keterangan atau klarifikasi tertulis yang sah.
        Dengan demikian, terhitung sejak tanggal
        <strong>{{ $formatDate($extraData['absence_start_date'] ?? null) }}</strong> sampai dengan tanggal
        <strong>{{ $formatDate($extraData['absence_end_date'] ?? null) }}</strong>, Saudara/i tercatat tidak masuk kerja
        selama <strong>{{ $workingDays }} hari kerja berturut-turut</strong> tanpa keterangan tertulis yang sah.
    </p>
    <p>
        Melalui surat ini, sebagai <strong>PANGGILAN KEDUA</strong> yang disampaikan secara patut dan tertulis,
        kami meminta agar Saudara/i:
    </p>
    <ol class="action-list">
        <li>
            Segera hadir bekerja dan/atau melapor ke bagian Sumber Daya Manusia (HRD) {{ $companyName }} paling lambat
            pada hari/tanggal <strong>{{ $formatDate($extraData['meeting_date'] ?? null, true) }}</strong>,
            pukul <strong>{{ $extraData['meeting_time'] ?? '-' }} WIB</strong>, bertempat di
            <strong>{{ $extraData['meeting_location'] ?? '-' }}</strong>; dan
        </li>
        <li>
            Menyampaikan keterangan atau klarifikasi tertulis disertai bukti yang sah atas ketidakhadiran dimaksud
            terkait agenda <strong>{{ $extraData['meeting_agenda'] ?? '-' }}</strong>.
        </li>
    </ol>
    <p>
        Perlu menjadi perhatian Saudara/i bahwa berdasarkan Pasal 154A ayat (1) huruf j Undang-Undang Nomor 13 Tahun
        2003 tentang Ketenagakerjaan sebagaimana telah diubah dengan Undang-Undang Nomor 6 Tahun 2023, juncto Pasal 36
        huruf j dan Pasal 51 Peraturan Pemerintah Nomor 35 Tahun 2021, pekerja/buruh yang mangkir selama 5 (lima) hari
        kerja atau lebih secara berturut-turut tanpa keterangan tertulis yang dilengkapi dengan bukti yang sah dan
        telah dipanggil oleh pengusaha 2 (dua) kali secara patut dan tertulis, dapat dilakukan Pemutusan Hubungan Kerja
        (PHK) dengan kualifikasi mengundurkan diri.
    </p>
    <p>
        Dengan diterbitkannya surat ini, Perusahaan memberikan kesempatan kepada Saudara/i untuk memenuhi panggilan dan
        memberikan klarifikasi atas ketidakhadiran tersebut.
    </p>
    <p>
        Apabila sampai dengan batas waktu yang telah ditentukan Saudara/i tetap tidak hadir dan/atau tidak memberikan
        keterangan tertulis yang sah, Perusahaan akan menindaklanjuti sesuai dengan ketentuan yang berlaku.
    </p>
    <p>
        Demikian surat panggilan ini disampaikan untuk dapat dilaksanakan sebagaimana mestinya. Atas perhatian dan
        kerja sama Saudara/i, kami ucapkan terima kasih.
    </p>

    <div class="signature">
        Hormat kami,<br>
        {{ $companyName }}
        <br><br><br><br>
        <span class="signature-name">Hisar Hesti</span><br>
        HR &amp; Legal Manager
    </div>

    <div class="copies">
        Tembusan:
        <ol>
            <li>Arsip/Personal File</li>
            <li>Atasan langsung Karyawan</li>
        </ol>
    </div>
</body>

</html>
