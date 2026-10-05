<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Surat Panggilan Kerja I</title>
    <style>
        @page {
            margin: 22px 32px 30px;
        }

        body {
            color: #111;
            font-family: 'Times New Roman', Times, serif;
            font-size: 10pt;
            line-height: 1.28;
        }

        .letterhead {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2.5px solid #111;
            margin-bottom: 12px;
        }

        .letterhead td {
            padding: 0 0 4px;
            vertical-align: middle;
        }

        .logo-cell {
            width: 24%;
        }

        .logo {
            width: 150px;
            height: auto;
        }

        .company {
            text-align: center;
        }

        .company-name {
            font-family: Arial, sans-serif;
            font-size: 13pt;
            font-weight: bold;
        }

        .company-address {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            line-height: 1.25;
        }

        .content {
            padding: 0 52px 0 72px;
        }

        .title {
            margin: 2px 0 0;
            font-size: 12pt;
            font-weight: bold;
            text-align: center;
            text-decoration: underline;
        }

        .subtitle {
            margin: 0 0 8px;
            text-align: center;
        }

        .letter-date {
            margin-bottom: 14px;
            text-align: right;
            font-size: 11.5pt;
        }

        .fields {
            width: 100%;
            border-collapse: collapse;
        }

        .fields td {
            padding: 0;
            vertical-align: top;
        }

        .fields td.field {
            width: 72px;
        }

        .fields td.colon {
            width: 8px;
        }

        .metadata {
            margin-bottom: 14px;
        }

        p {
            margin: 0;
            text-align: justify;
        }

        .gap {
            margin-top: 14px;
        }

        .action-list {
            margin: 0 0 0 20px;
            padding-left: 18px;
        }

        .action-list li {
            padding-left: 2px;
            text-align: justify;
        }

        .meeting-wrap {
            padding-left: 38px;
        }

        .meeting td.field {
            width: 84px;
            font-weight: bold;
        }

        .signature {
            margin-top: 30px;
            page-break-inside: avoid;
        }

        .signature-space {
            height: 62px;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }

        .copies {
            margin-top: 12px;
            font-size: 8pt;
            line-height: 1.25;
            page-break-inside: avoid;
        }

        .draft-watermark {
            position: fixed;
            top: 38%;
            left: 0;
            right: 0;
            color: rgba(235, 28, 36, 0.16);
            font-size: 96pt;
            font-weight: bold;
            letter-spacing: 12px;
            text-align: center;
            transform: rotate(-35deg);
        }
    </style>
</head>

<body>
    @php
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
        $formatPeriod = static fn (?string $start, ?string $end): string => $formatDate($start).' sampai dengan '.$formatDate($end);

        $gender = strtolower(trim((string) ($employee->gender ?? '')));
        $salutation = match (true) {
            str_contains($gender, 'perempuan') || str_contains($gender, 'wanita') || str_contains($gender, 'female') => 'Saudari',
            str_contains($gender, 'laki') || str_contains($gender, 'pria') || str_contains($gender, 'male') => 'Saudara',
            default => 'Saudara/Saudari',
        };

        $employeeName = trim((string) ($employee->fullName ?? '')) ?: '-';
        $employeePosition = trim((string) ($employee->jobPositionLocation ?: $employee->jobPosition)) ?: '-';
        $employeeNik = ltrim(trim((string) ($employee->nikNpwp ?? '')), "'");
        $employeeNik = preg_match('/^\d{16}$/', $employeeNik) ? implode(' ', str_split($employeeNik, 4)) : ($employeeNik ?: '-');
        $employeeAddress = trim((string) ($employee->residentialAddress ?: $employee->citizenIdAddress)) ?: '-';
        $letterNumber = trim((string) ($extraData['sk_number'] ?? '')) ?: '-';
        $attachmentCount = (int) ($extraData['attachment_count'] ?? 0);
        $docDate = filled($extraData['doc_date'] ?? null) ? \Illuminate\Support\Carbon::parse($extraData['doc_date']) : null;
        $letterDate = $docDate ? $docDate->format('d').' '.$monthNames[$docDate->month - 1].' '.$docDate->year : '-';
        $companyName = (string) ($company['name'] ?? 'PT MAHAKARYA SUKSES INDONESIA');
        $signatureCompany = preg_replace('/^Pt\b\.?/', 'PT', ucwords(strtolower($companyName)));
        $hasSecondPeriod = ! empty($extraData['absence_second_start_date']) && ! empty($extraData['absence_second_end_date']);
        $meetingTime = str_replace(':', '.', (string) ($extraData['meeting_time'] ?? '-'));
    @endphp

    @if (! empty($extraData['draft']))
        <div class="draft-watermark">DRAFT</div>
    @endif

    @include('pdf.components.kop-surat-mangkir')

    <div class="content">
        <div class="title">SURAT PANGGILAN KERJA I</div>
        <div class="subtitle">(Panggilan Pertama karena Ketidakhadiran Tanpa Keterangan)</div>
        <div class="letter-date">{{ $company['city'] ?? 'Jakarta' }}, {{ $letterDate }}</div>

        <table class="fields metadata">
            <tr>
                <td class="field">Nomor</td>
                <td class="colon">:</td>
                <td>{{ $letterNumber }}</td>
            </tr>
            <tr>
                <td class="field">Lampiran</td>
                <td class="colon">:</td>
                <td>{{ $attachmentCount > 0 ? $attachmentCount : '-' }}</td>
            </tr>
            <tr>
                <td class="field">Perihal</td>
                <td class="colon">:</td>
                <td><strong>Panggilan Kerja I</strong></td>
            </tr>
        </table>

        <div>Kepada Yth.</div>
        <table class="fields">
            <tr>
                <td class="field">Sdr/I</td>
                <td class="colon">:</td>
                <td>{{ $employeeName }}</td>
            </tr>
            <tr>
                <td class="field">Jabatan</td>
                <td class="colon">:</td>
                <td>{{ $employeePosition }}</td>
            </tr>
            <tr>
                <td class="field">NIK/NIP</td>
                <td class="colon">:</td>
                <td>{{ $employeeNik }}</td>
            </tr>
            <tr>
                <td class="field">Alamat</td>
                <td class="colon">:</td>
                <td>{{ $employeeAddress }}</td>
            </tr>
        </table>
        <div>di tempat</div>

        <p class="gap">Dengan hormat,</p>
        <p>
            Berdasarkan data kehadiran Perusahaan, Saudara/Saudari tercatat
            <strong>tidak hadir bekerja tanpa keterangan</strong> pada tanggal
            <strong>{{ $formatPeriod($extraData['absence_start_date'] ?? null, $extraData['absence_end_date'] ?? null) }}</strong>@if ($hasSecondPeriod), dan kembali tercatat tidak hadir pada tanggal
            <strong>{{ $formatPeriod($extraData['absence_second_start_date'], $extraData['absence_second_end_date']) }}</strong>@endif.
        </p>

        <p class="gap">
            Ketidakhadiran tersebut menjadi perhatian Perusahaan karena berdasarkan ketentuan
            <strong>Peraturan Perusahaan</strong>, karyawan yang tidak masuk kerja tanpa memperoleh izin dari atasan dan
            tidak diketahui oleh HRBP ditunjuk dapat dikategorikan sebagai <strong>mangkir</strong>.
        </p>
        <p>
            Sehubungan dengan hal tersebut, Perusahaan dengan ini memberikan <strong>Panggilan Kerja I</strong> kepada
            {{ $salutation }} untuk:
        </p>
        <ol class="action-list">
            <li><strong>Hadir dan melapor kepada HR Perusahaan</strong> untuk memberikan klarifikasi atas ketidakhadiran {{ $salutation }};</li>
            <li>Menyampaikan <strong>keterangan tertulis</strong> mengenai alasan ketidakhadiran {{ $salutation }}; dan</li>
            <li>
                Apabila terdapat alasan atau kondisi yang dapat dipertanggungjawabkan, agar disertai dengan
                <strong>bukti atau dokumen pendukung yang sah</strong>.
            </li>
        </ol>
        <p>
            Panggilan ini diberikan sebagai bagian dari proses klarifikasi dan pembinaan hubungan kerja sesuai dengan
            ketentuan <strong>Peraturan Perusahaan</strong>, khususnya ketentuan mengenai <strong>ketidakhadiran/mangkir</strong>.
        </p>
        <p>Saudara/Saudari diminta untuk <strong>hadir pada:</strong></p>
        <div class="meeting-wrap">
        <table class="fields meeting">
            <tr>
                <td class="field">Hari/Tanggal</td>
                <td class="colon"><strong>:</strong></td>
                <td><strong>{{ $formatDate($extraData['meeting_date'] ?? null, true) }}</strong></td>
            </tr>
            <tr>
                <td class="field">Waktu</td>
                <td class="colon"><strong>:</strong></td>
                <td><strong>{{ $meetingTime }} WIB</strong></td>
            </tr>
            <tr>
                <td class="field">Tempat</td>
                <td class="colon">:</td>
                <td>{!! nl2br(e($extraData['meeting_location'] ?? '-')) !!}</td>
            </tr>
            <tr>
                <td class="field">Agenda</td>
                <td class="colon">:</td>
                <td>{{ $extraData['meeting_agenda'] ?? '-' }}</td>
            </tr>
        </table>
        </div>

        <p class="gap">
            Apabila {{ $salutation }} tidak memenuhi panggilan ini dan tidak memberikan keterangan yang dapat
            dipertanggungjawabkan, Perusahaan dapat melakukan <strong>Panggilan Kerja II</strong> dan mengambil tindakan
            lebih lanjut sesuai dengan ketentuan Peraturan Perusahaan dan peraturan perundang-undangan yang berlaku.
        </p>
        <p>
            Demikian surat panggilan ini disampaikan untuk dapat dilaksanakan sebagaimana mestinya. Atas perhatian dan
            kerja sama {{ $salutation }}, kami ucapkan terima kasih.
        </p>

        <div class="signature">
            <div>Hormat kami,</div>
            <div>{{ $signatureCompany }}</div>
            <div class="signature-space"></div>
            <div class="signature-name">Hisar Hesti</div>
            <div>HR &amp; Legal Manager</div>
        </div>

        <div class="copies">
            <div>Tembusan:</div>
            <div>1. Arsip/Personal File</div>
            <div>2. Atasan langsung Karyawan</div>
        </div>
    </div>

    @include('pdf.components.lampiran-gambar', ['attachments' => $extraData['attachments'] ?? [], 'withLetterhead' => true])
</body>

</html>
