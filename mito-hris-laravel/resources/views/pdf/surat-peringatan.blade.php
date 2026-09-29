<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Surat Peringatan - {{ $employee->fullName }}</title>
    <style>
        @page {
            margin: 24px 40px;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 9pt;
            color: #1f2937;
            line-height: 1.38;
            text-align: justify;
        }

        p {
            margin: 0 0 5px;
        }

        .confidential-row {
            text-align: right;
            margin-top: -2px;
        }

        .confidential {
            display: inline-block;
            border: 1px solid #b91c1c;
            color: #b91c1c;
            font-size: 7.5pt;
            font-weight: bold;
            letter-spacing: 1.5px;
            padding: 1px 8px;
        }

        .doc-title {
            font-size: 13pt;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            text-decoration: underline;
            color: #000000;
            margin-top: 4px;
        }

        .doc-level {
            font-size: 10pt;
            font-weight: bold;
            text-align: center;
            color: #000000;
        }

        .doc-no {
            font-size: 9pt;
            text-align: center;
            color: #374151;
            margin-bottom: 8px;
        }

        .kv-table {
            width: 100%;
            border-collapse: collapse;
            margin: 2px 0 8px;
        }

        .kv-table td {
            padding: 1px 3px;
            vertical-align: top;
        }

        .kv-label {
            width: 135px;
            font-weight: bold;
        }

        .kv-colon {
            width: 10px;
        }

        .section-title {
            font-size: 9.5pt;
            font-weight: bold;
            color: #0b2540;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-bottom: 0.75px solid #0b2540;
            padding-bottom: 1px;
            margin: 8px 0 4px;
        }

        .violation-table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0 8px;
        }

        .violation-table td {
            border: 0.75px solid #9ca3af;
            padding: 3px 6px;
            vertical-align: top;
        }

        .violation-table .v-label {
            width: 135px;
            font-weight: bold;
            background: #f3f4f6;
        }

        ol {
            margin: 2px 0 8px;
            padding-left: 18px;
        }

        ol li {
            margin-bottom: 2px;
        }

        .sign-table {
            width: 100%;
            margin-top: 8px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }

        .sign-table td {
            width: 50%;
            vertical-align: top;
        }

        .sign-table td.sign-hr {
            text-align: right;
        }

        .sign-space {
            height: 50px;
        }

        .sign-image-area {
            height: 50px;
            margin: 2px 0 2px auto;
        }

        .sign-image-area .hr-sign-img {
            height: 48px !important;
            width: auto;
            max-width: 125px;
            margin: 0 0 4px auto;
        }

        .cc {
            margin-top: 8px;
            font-size: 8pt;
            color: #374151;
            page-break-inside: avoid;
        }

        .cc ol {
            margin: 1px 0 0;
        }
    </style>
</head>

<body>

    @include('pdf.components.kop-surat')

    @php
        $level = \App\Enums\WarningLetterLevel::tryFrom((string) ($extraData['level'] ?? '')) ?? \App\Enums\WarningLetterLevel::SP1;
        $nextLevel = $level->next();

        $bulanId = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $fmtDateId = function (?string $value) use ($bulanId): string {
            $ts = $value ? strtotime($value) : false;
            if (!$ts) {
                return '-';
            }
            return date('j', $ts) . ' ' . $bulanId[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
        };
        $angka = [1 => 'satu', 2 => 'dua', 3 => 'tiga', 4 => 'empat', 5 => 'lima', 6 => 'enam'];

        // Nomor Surat Peringatan: {seq}/SP/{ENTITY}/{ROMAN}/{YEAR}; nomor dokumen lain tidak dicetak.
        $spNumber = trim((string) ($extraData['sk_number'] ?? ($extraData['letter_number'] ?? '')));
        if (!preg_match('#^\d+/SP/#i', $spNumber)) {
            $spNumber = '';
        }

        $companyName = $company['name'] ?? 'PT MAHAKARYA SUKSES INDONESIA';
        $companyCity = $company['city'] ?? 'Tangerang';

        $docDate = (string) ($extraData['doc_date'] ?? date('Y-m-d'));
        $docDateFmt = $fmtDateId($docDate);
        $validMonths = max(1, (int) ($extraData['validity_months'] ?? 6));
        $validMonthsText = $validMonths . ' (' . ($angka[$validMonths] ?? $validMonths) . ') bulan';
        $validUntilFmt = $fmtDateId($extraData['valid_until'] ?? null);

        $status = strtolower(trim((string) ($employee->statusEmployee ?? '')));
        $statusLabel = in_array($status, ['permanent', 'pkwtt'], true)
            ? 'Karyawan Tetap (PKWTT)'
            : 'Karyawan Kontrak (PKWT)';

        $position = $employee->jobPositionLocation ?: ($employee->jobPosition ?: '-');
        $department = collect([$employee->department ?? null, $employee->division ?? null])
            ->filter(fn ($v) => trim((string) $v) !== '')
            ->unique()
            ->implode(' / ') ?: '-';

        $incidentDate = trim((string) ($extraData['incident_date'] ?? ''));
        $regulation = trim((string) ($extraData['regulation_reference'] ?? ''))
            ?: 'Peraturan Perusahaan ' . $companyName . ' serta tata tertib dan prosedur kerja yang berlaku.';

        $correctiveActions = collect(preg_split('/\r\n|\r|\n/', (string) ($extraData['corrective_actions'] ?? '')))
            ->map(fn ($line) => trim(preg_replace('/^\s*(?:[-*\x{2022}]|\d+[.)])\s*/u', '', $line)))
            ->filter()
            ->values();

        $superior = trim((string) ($employee->directSuperior ?? ''));
    @endphp

    {{-- ── JUDUL ─────────────────────────────────────────────── --}}
    <div class="doc-title">Surat Peringatan {{ $level->ordinal() }}</div>
    <div class="doc-level">({{ $level->shortLabel() }})</div>
    @if ($spNumber !== '')
        <div class="doc-no">Nomor: {{ $spNumber }}</div>
    @endif

    <p>
        Manajemen <strong>{{ $companyName }}</strong>, dengan ini menerbitkan
        <strong>{{ $level->label() }}</strong> kepada karyawan berikut:
    </p>

    <table class="kv-table">
        <tr>
            <td class="kv-label">Nama</td>
            <td class="kv-colon">:</td>
            <td><strong>{{ $employee->fullName ?? '-' }}</strong></td>
        </tr>
        <tr>
            <td class="kv-label">Employee ID</td>
            <td class="kv-colon">:</td>
            <td>{{ $employee->employeeId ?? '-' }}</td>
        </tr>
        <tr>
            <td class="kv-label">Jabatan</td>
            <td class="kv-colon">:</td>
            <td>{{ $position }}</td>
        </tr>
        <tr>
            <td class="kv-label">Departemen / Divisi</td>
            <td class="kv-colon">:</td>
            <td>{{ $department }}</td>
        </tr>
        <tr>
            <td class="kv-label">Status Karyawan</td>
            <td class="kv-colon">:</td>
            <td>{{ $statusLabel }}</td>
        </tr>
        <tr>
            <td class="kv-label">Tanggal Bergabung</td>
            <td class="kv-colon">:</td>
            <td>{{ $fmtDateId($employee->joinDate ?? null) }}</td>
        </tr>
    </table>

    {{-- ── I. PELANGGARAN ────────────────────────────────────── --}}
    <div class="section-title">I. Pelanggaran</div>
    <p>
        Berdasarkan hasil evaluasi dan klarifikasi yang telah dilakukan oleh Perusahaan, Saudara/i dinyatakan telah
        melakukan pelanggaran dengan rincian sebagai berikut:
    </p>
    <table class="violation-table">
        <tr>
            <td class="v-label">Kategori Pelanggaran</td>
            <td>{{ $extraData['violation_category'] ?? '-' }}</td>
        </tr>
        @if ($incidentDate !== '')
            <tr>
                <td class="v-label">Tanggal Kejadian</td>
                <td>{{ $fmtDateId($incidentDate) }}</td>
            </tr>
        @endif
        <tr>
            <td class="v-label">Uraian Pelanggaran</td>
            <td>{!! nl2br(e(trim((string) ($extraData['violation_description'] ?? '-')))) !!}</td>
        </tr>
        <tr>
            <td class="v-label">Dasar Ketentuan</td>
            <td>{{ $regulation }}</td>
        </tr>
    </table>
    <p>
        Tindakan tersebut tidak sejalan dengan ketentuan yang berlaku di Perusahaan serta nilai profesionalisme,
        integritas, dan tanggung jawab yang dijunjung tinggi oleh <strong>{{ $companyName }}</strong>.
    </p>

    {{-- ── II. TINDAKAN PERBAIKAN ─────────────────────────────── --}}
    <div class="section-title">II. Tindakan Perbaikan</div>
    <p>Sehubungan dengan hal tersebut, Perusahaan meminta Saudara/i untuk:</p>
    <ol>
        @foreach ($correctiveActions as $action)
            <li>{{ $action }}</li>
        @endforeach
        <li>Mematuhi seluruh Peraturan Perusahaan, tata tertib, serta prosedur kerja yang berlaku.</li>
        <li>Tidak mengulangi pelanggaran yang sama maupun melakukan pelanggaran lainnya.</li>
        <li>Menunjukkan perbaikan sikap dan kinerja yang terukur, yang akan dievaluasi oleh atasan langsung.</li>
    </ol>

    {{-- ── III. MASA BERLAKU & KONSEKUENSI ────────────────────── --}}
    <div class="section-title">III. Masa Berlaku dan Konsekuensi</div>
    <ol>
        <li>
            Surat Peringatan ini berlaku selama <strong>{{ $validMonthsText }}</strong>, terhitung sejak tanggal
            <strong>{{ $docDateFmt }}</strong> sampai dengan <strong>{{ $validUntilFmt }}</strong>.
        </li>
        @if ($nextLevel)
            <li>
                Apabila dalam masa berlaku Surat Peringatan ini Saudara/i kembali melakukan pelanggaran, Perusahaan
                akan menerbitkan <strong>{{ $nextLevel->label() }}</strong> sesuai dengan ketentuan yang berlaku.
            </li>
        @else
            <li>
                Surat Peringatan Ketiga ini merupakan <strong>peringatan terakhir</strong>. Apabila dalam masa
                berlakunya Saudara/i kembali melakukan pelanggaran, Perusahaan dapat melakukan
                <strong>Pemutusan Hubungan Kerja (PHK)</strong> sesuai dengan Peraturan Perusahaan dan peraturan
                perundang-undangan ketenagakerjaan yang berlaku.
            </li>
        @endif
        <li>
            Surat Peringatan ini dicatat dalam arsip kepegawaian Saudara/i dan menjadi bahan pertimbangan dalam
            penilaian kinerja serta keputusan kepegawaian selanjutnya.
        </li>
    </ol>

    <p>
        Demikian Surat Peringatan ini disampaikan agar dapat diperhatikan dan dilaksanakan dengan penuh tanggung jawab.
    </p>

    {{-- ── TANDA TANGAN ───────────────────────────────────────── --}}
    <table class="sign-table">
        <tr>
            <td>
                &nbsp;<br>
                Diterima dan dipahami oleh,<br>
                Karyawan yang bersangkutan,
                <div class="sign-space"></div>
                <strong><u>{{ $employee->fullName ?? '-' }}</u></strong><br>
                Tanggal: {{ $docDateFmt }}
            </td>
            <td class="sign-hr">
                {{ $companyCity }}, {{ $docDateFmt }}<br>
                Hormat kami,<br>
                <strong>{{ $companyName }}</strong>
                <div class="sign-image-area">
                    @include('pdf.components.hr-sign')
                </div>
                <strong><u>Hisar Hesti</u></strong><br>
                Human Resources (HR) &amp; Legal Manager
            </td>
        </tr>
    </table>

    <div class="cc">
        <strong>Tembusan:</strong>
        <ol>
            <li>Atasan Langsung{{ $superior !== '' ? ' (' . $superior . ')' : '' }}</li>
            <li>Arsip Personalia (HRD)</li>
        </ol>
    </div>

</body>

</html>
