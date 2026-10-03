<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Surat Panggilan Kerja I</title>
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

        .metadata {
            width: 100%;
            margin: 0 0 12px;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .metadata td {
            padding: 0;
            vertical-align: top;
        }

        .metadata .field {
            width: 70px;
        }

        .recipient-table {
            width: 100%;
            margin: 0 0 14px;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .recipient-table td {
            padding: 0;
            vertical-align: top;
        }

        .recipient-table .field {
            width: 70px;
        }

        .recipient-table .name-field {
            width: 70px;
        }

        .recipient {
            margin: 0 0 2px;
        }

        .recipient-block {
            width: 100%;
            margin-left: 0;
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
            border-collapse: collapse;
            table-layout: fixed;
        }

        .meeting td {
            padding: 0;
            vertical-align: top;
        }

        .meeting .field {
            width: 70px;
        }

        .signature {
            margin: 20px 0 0;
            text-align: left;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }

        .copies {
            margin: 8px 0 0;
            text-align: left;
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
        $formatAbsenceDate = static function (?string $date) use ($monthNames): string {
            if (! $date) {
                return '-';
            }

            $parsedDate = \Illuminate\Support\Carbon::parse($date);

            return $parsedDate->day.' '.$monthNames[$parsedDate->month - 1].' '.$parsedDate->year;
        };
        $formatMeetingDate = static function (?string $date) use ($monthNames): string {
            if (! $date) {
                return '-';
            }

            $parsedDate = \Illuminate\Support\Carbon::parse($date);
            $dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

            return $dayNames[$parsedDate->dayOfWeek].', '.$parsedDate->day.' '.$monthNames[$parsedDate->month - 1].' '.$parsedDate->year;
        };
        $employeeName = trim((string) ($employee->fullName ?? '')) ?: '-';
        $employeePosition = trim((string) ($employee->jobPositionLocation ?: $employee->jobPosition)) ?: '-';
        $employeeNik = ltrim(trim((string) ($employee->nikNpwp ?? '')), "'") ?: '-';
        $employeeAddress = trim((string) ($employee->residentialAddress ?: $employee->citizenIdAddress)) ?: '-';
        $letterNumber = trim((string) ($extraData['sk_number'] ?? '')) ?: '-';
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
                    <div class="company-name">{{ $company['name'] }}</div>
                    <div class="company-address">{{ $company['address'] }}</div>
                </td>
            </tr>
        </table>
    @else
        @include('pdf.components.kop-surat')
    @endif

    <div class="title">SURAT PANGGILAN KERJA I</div>
    <div class="subtitle">(Panggilan Pertama karena Ketidakhadiran Tanpa Keterangan)</div>
    <div class="letter-date">Jakarta, 09 September 2026</div>

    <div class="recipient-block">
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
                <td>: 1</td>
            </tr>
            <tr>
                <td class="field">Perihal</td>
                <td>: <strong>Panggilan Kerja I</strong></td>
            </tr>
        </table>

        <div class="recipient">Kepada Yth.</div>
        <table class="recipient-table">
            <colgroup>
                <col style="width:70px">
                <col>
            </colgroup>
            <tr>
                <td class="name-field">Sdr/i</td>
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
        Berdasarkan data kehadiran Perusahaan, Saudara/Saudari tercatat
        <strong>tidak hadir bekerja tanpa keterangan</strong> pada tanggal
        <strong>{{ $formatAbsenceDate($extraData['absence_start_date'] ?? null) }} sampai dengan {{ $formatAbsenceDate($extraData['absence_end_date'] ?? null) }}</strong>
        @if (! empty($extraData['absence_second_start_date']) && ! empty($extraData['absence_second_end_date']))
            , dan kembali tercatat tidak hadir pada tanggal
            <strong>{{ $formatAbsenceDate($extraData['absence_second_start_date']) }} sampai dengan {{ $formatAbsenceDate($extraData['absence_second_end_date']) }}</strong>
        @endif.
    </p>
    <p>
        Ketidakhadiran tersebut menjadi perhatian Perusahaan karena berdasarkan ketentuan
        <strong>Peraturan Perusahaan</strong>, karyawan yang tidak masuk kerja tanpa memperoleh izin dari atasan dan
        tidak diketahui oleh HRBP ditunjuk dapat dikategorikan sebagai mangkir.
    </p>
    <p>
        Sehubungan dengan hal tersebut, Perusahaan dengan ini memberikan <strong>Panggilan Kerja I</strong> kepada
        Saudari untuk:
    </p>
    <ol class="action-list">
        <li>Hadir dan melapor kepada HR Perusahaan untuk memberikan klarifikasi atas ketidakhadiran Saudari;</li>
        <li>Menyampaikan keterangan tertulis mengenai alasan ketidakhadiran Saudari; dan</li>
        <li>
            Apabila terdapat alasan atau kondisi yang dapat dipertanggungjawabkan, agar disertai dengan
            <strong>bukti atau dokumen pendukung yang sah.</strong>
        </li>
    </ol>
    <p>
        Panggilan ini diberikan sebagai bagian dari proses klarifikasi dan pembinaan hubungan kerja sesuai dengan
        ketentuan Peraturan Perusahaan, khususnya ketentuan mengenai ketidakhadiran/mangkir.
    </p>
    <p><strong>Saudara/Saudari diminta untuk hadir pada:</strong></p>
    <table class="meeting">
        <colgroup>
            <col style="width:70px">
            <col>
        </colgroup>
        <tr>
            <td class="field"><strong>Hari/Tanggal</strong></td>
            <td>: {{ $formatMeetingDate($extraData['meeting_date'] ?? null) }}</td>
        </tr>
        <tr>
            <td class="field"><strong>Waktu</strong></td>
            <td>: {{ $extraData['meeting_time'] ?? '-' }} WIB</td>
        </tr>
        <tr>
            <td class="field"><strong>Tempat</strong></td>
            <td>: {{ $extraData['meeting_location'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="field"><strong>Agenda</strong></td>
            <td>: {{ $extraData['meeting_agenda'] ?? '-' }}</td>
        </tr>
    </table>
    <p>
        Apabila Saudari tidak memenuhi panggilan ini dan tidak memberikan keterangan yang dapat dipertanggungjawabkan,
        Perusahaan dapat melakukan <strong>Panggilan Kerja II</strong> dan mengambil tindakan lebih lanjut sesuai
        dengan ketentuan Peraturan Perusahaan dan peraturan perundang-undangan yang berlaku.
    </p>
    <p>
        Demikian surat panggilan ini disampaikan untuk dapat dilaksanakan sebagaimana mestinya. Atas perhatian dan
        kerja sama Saudari, kami ucapkan terima kasih.
    </p>

    <div class="signature">
        Hormat kami,<br>
        {{ $company['name'] }}
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
