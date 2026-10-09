<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Surat Panggilan Kerja II</title>
    <style>
        @page {
            margin: 132px 32px 40px;
        }

        body {
            color: #111;
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.38;
        }

        .page-header {
            position: fixed;
            top: -114px;
            left: 0;
            right: 0;
        }

        .letterhead {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2.5px solid #111;
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
            font-size: 20pt;
            font-weight: bold;
        }

        .company-address {
            font-size: 12pt;
            line-height: 1.25;
        }

        .content {
            padding: 0 52px 0 72px;
        }

        .title {
            font-size: 16pt;
            font-weight: bold;
            text-align: center;
            text-decoration: underline;
        }

        .subtitle {
            margin: 0 0 8px;
            text-align: center;
        }

        .letter-date {
            margin-bottom: 12px;
            text-align: right;
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
            width: 80px;
        }

        .fields td.colon {
            width: 8px;
        }

        .metadata {
            margin-bottom: 14px;
        }

        .recipient-label {
            margin-bottom: 14px;
        }

        p {
            margin: 12px 0 0;
            text-align: justify;
        }

        .action-list {
            margin: 12px 0 0 18px;
            padding-left: 18px;
        }

        .action-list li {
            padding-left: 4px;
            text-align: justify;
        }

        .action-list li + li {
            margin-top: 8px;
        }

        .signature {
            margin-top: 28px;
            font-size: 14pt;
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
            font-size: 12pt;
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
        $formatDate = static function (?string $date, bool $includeDay = false, bool $padDay = false) use ($monthNames, $dayNames): string {
            if (! $date) {
                return '-';
            }

            $parsedDate = \Illuminate\Support\Carbon::parse($date);
            $formatted = $parsedDate->format($padDay ? 'd' : 'j').' '.$monthNames[$parsedDate->month - 1].' '.$parsedDate->year;

            return $includeDay ? $dayNames[$parsedDate->dayOfWeek].', '.$formatted : $formatted;
        };
        $employeeName = trim((string) ($employee->fullName ?? '')) ?: '-';
        $employeePosition = trim((string) ($employee->jobPositionLocation ?: $employee->jobPosition)) ?: '-';
        $employeeNik = ltrim(trim((string) ($employee->nikNpwp ?? '')), "'");
        $employeeNik = preg_match('/^\d{16}$/', $employeeNik) ? implode(' ', str_split($employeeNik, 4)) : ($employeeNik ?: '-');
        $employeeAddress = trim((string) ($employee->residentialAddress ?: $employee->citizenIdAddress)) ?: '-';
        $letterNumber = trim((string) ($extraData['sk_number'] ?? '')) ?: '-';
        $attachmentCount = (int) ($extraData['attachment_count'] ?? 0);
        $firstSummonsNumber = trim((string) ($extraData['first_summons_number'] ?? '')) ?: '-';
        $firstSummonsDate = $formatDate($extraData['first_summons_date'] ?? null, false, true);
        $workingDays = max(1, (int) ($extraData['working_days'] ?? 1));
        $companyName = (string) ($company['name'] ?? 'PT MAHAKARYA SUKSES INDONESIA');
        $companyTitle = preg_replace('/^Pt\b\.?/', 'PT', ucwords(strtolower($companyName)));
        $meetingTime = str_replace(':', '.', (string) ($extraData['meeting_time'] ?? '-'));
        $meetingLocation = trim((string) ($extraData['meeting_location'] ?? '')) ?: '-';
    @endphp

    @if (! empty($extraData['draft']))
        <div class="draft-watermark">DRAFT</div>
    @endif

    <div class="page-header">
        @include('pdf.components.kop-surat-mangkir')
    </div>

    <div class="content">
        <div class="title">SURAT PANGGILAN KERJA II</div>
        <div class="subtitle">(Panggilan Kedua/Terakhir karena Ketidakhadiran Tanpa Keterangan)</div>
        <div class="letter-date">{{ $company['city'] ?? 'Jakarta' }}, {{ $formatDate($extraData['doc_date'] ?? null, false, true) }}</div>

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
                <td class="colon"><strong>:</strong></td>
                <td><strong>Panggilan Kerja II (Terakhir)</strong></td>
            </tr>
        </table>

        <div class="recipient-label">Kepada Yth.</div>
        <table class="fields">
            <tr>
                <td class="field">Nama</td>
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
        <p>di tempat</p>

        <p>Dengan hormat,</p>
        <p>
            Merujuk Surat Panggilan Kerja I Nomor {{ $firstSummonsNumber }} tanggal {{ $firstSummonsDate }}
            perihal ketidakhadiran Saudara/i, sampai dengan diterbitkannya surat ini Saudara/i belum juga hadir bekerja
            dan/atau menyampaikan keterangan/klarifikasi tertulis yang sah. Dengan demikian, terhitung sejak tanggal
            {{ $formatDate($extraData['absence_start_date'] ?? null) }} sampai dengan tanggal surat ini, Saudara/i
            tercatat tidak masuk kerja selama {{ $workingDays }} hari kerja berturut-turut tanpa keterangan tertulis yang sah.
        </p>
        <p>
            Melalui surat ini, sebagai PANGGILAN KEDUA yang disampaikan secara patut dan tertulis, kami meminta agar
            Saudara/i:
        </p>
        <ol class="action-list">
            <li>
                Segera hadir bekerja dan/atau melapor ke bagian Sumber Daya Manusia (HRD) {{ $companyTitle }} paling
                lambat pada hari/tanggal {{ $formatDate($extraData['meeting_date'] ?? null, true) }}, pukul
                {{ $meetingTime }} WIB, bertempat di {{ $meetingLocation }}; dan
            </li>
            <li>Menyampaikan keterangan/klarifikasi tertulis disertai bukti yang sah atas ketidakhadiran dimaksud.</li>
        </ol>
        <p>
            Perlu menjadi perhatian Saudara/i bahwa berdasarkan <strong>Pasal 154A ayat (1) huruf j Undang-Undang Nomor 13
            Tahun 2003 tentang Ketenagakerjaan sebagaimana telah diubah dengan Undang-Undang Nomor 6 Tahun 2023</strong>,
            juncto <strong>Pasal 36 huruf j dan Pasal 51 Peraturan Pemerintah Nomor 35 Tahun 2021</strong>, pekerja/buruh
            yang mangkir selama <strong>5 (lima) hari kerja atau lebih secara berturut-turut tanpa keterangan tertulis yang
            dilengkapi dengan bukti yang sah dan telah dipanggil oleh pengusaha 2 (dua) kali secara patut dan
            tertulis</strong>, dapat dilakukan Pemutusan Hubungan Kerja (PHK) dengan kualifikasi mengundurkan diri.
        </p>
        <p>
            Dengan diterbitkannya surat ini, Perusahaan memberikan kesempatan kepada Saudara/i untuk memenuhi panggilan dan
            memberikan klarifikasi atas ketidakhadiran tersebut.
        </p>
        <p>
            Apabila sampai dengan batas waktu yang telah ditentukan Saudara/i tetap tidak hadir dan/atau tidak memberikan
            keterangan tertulis yang sah, Perusahaan akan menindaklanjuti sesuai dengan ketentuan peraturan
            perundang-undangan dan <strong>Peraturan Perusahaan yang berlaku</strong>, termasuk proses Pemutusan Hubungan
            Kerja karena mangkir sesuai ketentuan yang berlaku.
        </p>
        <p>
            <strong>Dalam hal hubungan kerja berakhir berdasarkan ketentuan tersebut, penyelesaian hak dan kewajiban
            Saudara/i akan dilakukan sesuai dengan ketentuan peraturan perundang-undangan dan Peraturan Perusahaan yang
            berlaku.</strong>
        </p>
        <p>Demikian surat panggilan ini disampaikan untuk menjadi perhatian dan dilaksanakan sebagaimana mestinya.</p>

        <div class="signature">
            <div>Hormat kami,</div>
            <div>{{ $companyTitle }}</div>
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

    @include('pdf.components.lampiran-gambar', ['attachments' => $extraData['attachments'] ?? [], 'withLetterhead' => false])
</body>

</html>
