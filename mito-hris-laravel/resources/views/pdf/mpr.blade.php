<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Manpower Request — {{ $mpr->mprNumber }}</title>
    <style>
        @page {
            margin: 25px 35px 30px 35px;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 9.5pt;
            color: #000;
            line-height: 1.45;
        }

        /* HEADER */
        .company-header {
            border-bottom: 2px solid #eb1c24;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }

        .company-name {
            font-size: 13pt;
            font-weight: bold;
            color: #eb1c24;
            text-transform: uppercase;
            margin: 0;
        }

        .company-address {
            font-size: 8pt;
            color: #000;
            margin-top: 3px;
            line-height: 1.3;
        }

        /* TITLE */
        .doc-title-box {
            text-align: center;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 6px 10px;
            margin-bottom: 14px;
            border-radius: 4px;
        }

        .doc-title {
            font-size: 11.5pt;
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .doc-meta {
            font-size: 8.5pt;
            color: #000;
            margin-top: 3px;
        }

        /* SECTION */
        .section-title {
            font-size: 9.5pt;
            font-weight: bold;
            color: #000;
            background: #e6f0fa;
            padding: 4px 8px;
            margin-top: 10px;
            margin-bottom: 6px;
            border-left: 3px solid #000;
            text-transform: uppercase;
        }

        /* TABLE */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        table.data-table td {
            padding: 2px 5px;
            vertical-align: top;
            font-size: 9pt;
            line-height: 1.28;
        }

        .label-col {
            width: 28%;
            color: #000;
            font-weight: bold;
            white-space: nowrap;
        }

        .colon-col {
            width: 3%;
            text-align: center;
        }

        .value-col {
            width: 69%;
            color: #0f172a;
        }

        .value-col p,
        .value-col ul,
        .value-col ol,
        .value-col li {
            margin: 0;
            padding-left: 16px;
        }

        .value-col ul,
        .value-col ol {
            margin-top: 2px;
            margin-bottom: 2px;
        }

        .grid-2 {
            width: 100%;
            border-collapse: collapse;
        }

        .grid-2 td {
            width: 50%;
            vertical-align: top;
            padding: 0 4px;
        }

        .content-box {
            border: 1px solid #cbd5e1;
            background: #ffffff;
            padding: 6px 8px;
            font-size: 8.5pt;
            min-height: 40px;
            border-radius: 3px;
            line-height: 1.4;
        }

        /* Markdown-rendered HTML inside content-box (DomPDF compatible) */
        .content-box p {
            margin: 0 0 5px 0;
        }

        .content-box p:last-child {
            margin-bottom: 0;
        }

        .content-box h1,
        .content-box h2,
        .content-box h3,
        .content-box h4,
        .content-box h5,
        .content-box h6 {
            margin: 4px 0 5px 0;
            font-weight: bold;
            line-height: 1.3;
        }

        .content-box h1 {
            font-size: 11pt;
        }

        .content-box h2 {
            font-size: 10.5pt;
        }

        .content-box h3 {
            font-size: 10pt;
        }

        .content-box h4,
        .content-box h5,
        .content-box h6 {
            font-size: 9pt;
        }

        .content-box ul,
        .content-box ol {
            margin-top: 4px;
            margin-bottom: 5px;
            padding-left: 18px;
        }

        .content-box li {
            margin-bottom: 2px;
        }

        .content-box strong {
            font-weight: bold;
        }

        .content-box em {
            font-style: italic;
        }

        /* SIGNATURES */
        .sign-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
            page-break-inside: avoid;
        }

        .sign-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 4px 6px;
        }

        /* Baris kedua: 2 box (COO & CEO) center terhadap 3 box baris pertama */
        .sign-table-second {
            width: 66.66%;
            border-collapse: collapse;
            margin: 18px auto 0 auto;
            page-break-inside: avoid;
        }

        .sign-table-second td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 4px 6px;
        }

        .sign-title {
            font-size: 8.5pt;
            font-weight: bold;
            color: #000;
            margin-bottom: 45px;
        }

        .sign-name {
            font-size: 9pt;
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 4px;
            display: inline-block;
            min-width: 140px;
        }

        .sign-role {
            font-size: 8pt;
            color: #000;
            margin-top: 2px;
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <div class="company-header">
        <table style="width:100%;">
            <tr>
                <td>
                    <div class="company-name">{{ $company['name'] ?? 'PT MAHAKARYA SUKSES INDONESIA' }}</div>
                    <div class="company-address">
                        {{ $company['address'] ?? 'Jl. Gajah Tunggal, Kp. Gembor, Pasir Jaya, Jatiuwung, Tangerang' }}
                    </div>
                </td>
                <td style="text-align:right; vertical-align:middle;">
                    <span
                        style="font-size: 16pt; font-weight: bold; color: #000;">{{ $company['brand'] ?? 'MITO' }}</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- TITLE -->
    <div class="doc-title-box">
        <div class="doc-title">Form Permintaan Tenaga Kerja (Manpower Request)</div>
        <div class="doc-meta">
            <strong>No. Request:</strong> {{ $mpr->mprNumber }} &nbsp;|&nbsp;
            <strong>Tanggal:</strong>
            {{ $mpr->requestDate ? date('d F Y', strtotime($mpr->requestDate)) : date('d F Y') }} &nbsp;|&nbsp;
            <strong>Status: {{ strtoupper($mpr->status ?? 'SUBMITTED') }}</strong>
        </div>
    </div>

    <!-- SECTION 1: INFORMASI PEMOHON -->
    <div class="section-title">I. Informasi Pemohon & Organisasi</div>
    <table class="grid-2">
        <tr>
            <td>
                <table class="data-table">
                    <tr>
                        <td class="label-col">Nama Pemohon</td>
                        <td class="colon-col">:</td>
                        <td class="value-col"><strong>{{ $mpr->requestorName ?: '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label-col">Jabatan Pemohon</td>
                        <td class="colon-col">:</td>
                        <td class="value-col">{{ $mpr->requestorPosition ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Entitas / Perusahaan</td>
                        <td class="colon-col">:</td>
                        <td class="value-col">{{ $company['name'] ?? ($mpr->entity ?: '-') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- SECTION 2: DETAIL KEBUTUHAN POSISI -->
    <div class="section-title">II. Detail Posisi yang Dibutuhkan</div>
    <table class="grid-2">
        <tr>
            <td>
                <table class="data-table">
                    <tr>
                        <td class="label-col">Posisi / Nama Jabatan</td>
                        <td class="colon-col">:</td>
                        <td class="value-col"><strong>{{ $mpr->position ?: '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label-col">Departemen</td>
                        <td class="colon-col">:</td>
                        <td class="value-col">{{ $mpr->department ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Divisi</td>
                        <td class="colon-col">:</td>
                        <td class="value-col">{{ $mpr->division ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Level Jabatan</td>
                        <td class="colon-col">:</td>
                        <td class="value-col">{{ $mpr->jobLevel ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Lokasi Penempatan</td>
                        <td class="colon-col">:</td>
                        <td class="value-col">{{ $mpr->workLocation ?: '-' }}</td>
                    </tr>
                </table>
            </td>
            <td>
                <table class="data-table">
                    <tr>
                        <td class="label-col">Status Kepegawaian</td>
                        <td class="colon-col">:</td>
                        <td class="value-col">{{ $mpr->employmentType ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Jumlah Kebutuhan</td>
                        <td class="colon-col">:</td>
                        <td class="value-col"><strong style="color:#000; font-size:10pt;">{{ $mpr->quantity ?: 1 }}
                                Orang</strong></td>
                    </tr>
                    <tr>
                        <td class="label-col">Target Join Date</td>
                        <td class="colon-col">:</td>
                        <td class="value-col">
                            {{ $mpr->expectedJoinDate ? date('d F Y', strtotime($mpr->expectedJoinDate)) : '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- JADWAL KERJA & FASILITAS -->
    <table class="data-table" style="margin-top:2px;">
        <tr>
            <td class="label-col" style="width:20%;">Hari Kerja</td>
            <td class="colon-col">:</td>
            <td class="value-col">{{ $mpr->workingDays ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label-col" style="width:20%;">Jam Kerja</td>
            <td class="colon-col">:</td>
            <td class="value-col">{{ $mpr->workingHours ?: '-' }}</td>
        </tr>
        @if (!empty($mpr->shiftDetail))
            <tr>
                <td class="label-col" style="width:20%;">Detail Shift</td>
                <td class="colon-col">:</td>
                <td class="value-col">{{ $mpr->shiftDetail }}</td>
            </tr>
        @endif
        <tr>
            <td class="label-col" style="width:20%;">Benefits / Tunjangan</td>
            <td class="colon-col">:</td>
            <td class="value-col">{{ $mpr->benefits ?: '-' }}</td>
        </tr>
    </table>

    <!-- SECTION 3: ALASAN PERMINTAAN -->
    <div class="section-title">III. Alasan Permintaan Karyawan</div>
    <table class="data-table">
        <tr>
            <td class="label-col" style="width:20%;">Alasan Kebutuhan</td>
            <td class="colon-col">:</td>
            <td class="value-col"><strong>{{ $mpr->reason ?: '-' }}</strong></td>
        </tr>
        @if (!empty($mpr->replacementFor))
            <tr>
                <td class="label-col" style="width:20%;">Menggantikan Karyawan</td>
                <td class="colon-col">:</td>
                <td class="value-col">{{ $mpr->replacementFor }}</td>
            </tr>
        @endif
    </table>

    <!-- SECTION 4: KUALIFIKASI KANDIDAT -->
    <div class="section-title">IV. Kualifikasi Kandidat</div>
    <table class="data-table" style="margin-top:0;">
        <tr>
            <td class="label-col" style="width:22%;">Latar Belakang Pendidikan</td>
            <td class="colon-col">:</td>
            <td class="value-col">{{ $mpr->educationBackground ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label-col" style="width:22%;">Pengalaman Kerja</td>
            <td class="colon-col">:</td>
            <td class="value-col">{{ $mpr->workExperience ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label-col" style="width:22%;">Skills &amp; Kompetensi</td>
            <td class="colon-col">:</td>
            <td class="value-col">
                @if (!empty($skillsHtml))
                    {!! $skillsHtml !!}
                @else
                    -
                @endif
            </td>
        </tr>
        <tr>
            <td class="label-col" style="width:22%;">Bahasa yang Dikuasai</td>
            <td class="colon-col">:</td>
            <td class="value-col">
                @if (!empty($languagesHtml))
                    {!! $languagesHtml !!}
                @else
                    -
                @endif
            </td>
        </tr>
        <tr>
            <td class="label-col" style="width:22%;">Referensi Industri Sejenis</td>
            <td class="colon-col">:</td>
            <td class="value-col">
                @if (!empty($industryHtml))
                    {!! $industryHtml !!}
                @else
                    -
                @endif
            </td>
        </tr>
    </table>

    <!-- SECTION 5: KUALIFIKASI & URAIAN PEKERJAAN -->
    <div class="section-title">V. Kualifikasi & Uraian Pekerjaan</div>
    <table class="grid-2">
        <tr>
            <td>
                <strong>Kualifikasi & Persyaratan:</strong>
                <div class="content-box">
                    @if (!empty($requirementsHtml))
                        {!! $requirementsHtml !!}
                    @else
                        Tidak ada kualifikasi khusus yang dilampirkan.
                    @endif
                </div>
            </td>
            <td>
                <strong>Uraian Tugas / Tanggung Jawab Utama:</strong>
                <div class="content-box">
                    @if (!empty($jobDescriptionHtml))
                        {!! $jobDescriptionHtml !!}
                    @else
                        Tidak ada uraian pekerjaan khusus yang dilampirkan.
                    @endif
                </div>
            </td>
        </tr>
    </table>
    <div style="margin-top:6px;">
        <strong>Key Results / Target Posisi Ini:</strong>
        <div class="content-box" style="margin-top:3px;">
            @if (!empty($keyResultsHtml))
                {!! $keyResultsHtml !!}
            @else
                Tidak ada target khusus yang dilampirkan.
            @endif
        </div>
    </div>

    <!-- SECTION 6: CATATAN -->
    @if (!empty($mpr->specialNotes))
        <div style="margin-bottom:5px;"><strong>Catatan Khusus MPR:</strong>
            <div class="content-box" style="min-height: 25px; margin-top:3px; margin-bottom: 8px;">
                {!! $specialNotesHtml !!}</div>
        </div>
    @endif

    <!-- SECTION 7: TANDA TANGAN -->
    <!-- Baris 1: 3 signature box -->
    <table class="sign-table">
        <tr>
            <td>
                <div class="sign-title">Diajukan oleh (Pemohon)</div>
                <div class="sign-name">{{ $mpr->requestorName ?: 'Manager Pemohon' }}</div>
                <div class="sign-role">{{ $mpr->requestorPosition ?: 'Manager / User Dept' }}</div>
            </td>
            <td>
                <div class="sign-title">Disetujui oleh (Divisi)</div>
                <div class="sign-name">( {{ $mpr->approvalDivision ?: '........................................' }} )</div>
                <div class="sign-role">Pimpinan Divisi</div>
            </td>
            <td>
                <div class="sign-title">Diperiksa oleh (HRD)</div>
                <div class="sign-name">Hisar Hesti</div>
                <div class="sign-role">HR Manager</div>
            </td>
        </tr>
    </table>

    <!-- Baris 2: 2 signature box (COO & CEO) horizontal-center terhadap baris 1 -->
    <table class="sign-table-second">
        <tr>
            <td>
                <div class="sign-title">Disetujui oleh (COO)</div>
                <div class="sign-name">Frans Arsianto</div>
                <div class="sign-role">COO</div>
            </td>
            <td>
                <div class="sign-title">Disetujui oleh (CEO)</div>
                <div class="sign-name">Jacksen Lie</div>
                <div class="sign-role">CEO</div>
            </td>
        </tr>
    </table>

</body>

</html>
