<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Manpower Request — {{ $mpr->mprNumber }}</title>
    <style>
        @page {
            margin: 20px 30px 25px 30px;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 9pt;
            color: #000;
            line-height: 1.35;
        }

        /* HEADER */
        .company-header {
            border-bottom: 2px solid #eb1c24;
            padding-bottom: 6px;
            margin-bottom: 8px;
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
            padding: 4px 10px;
            margin-bottom: 8px;
            border-radius: 4px;
        }

        .doc-title {
            font-size: 10.5pt;
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .doc-meta {
            font-size: 8pt;
            color: #000;
            margin-top: 2px;
        }

        /* SECTION */
        .section-title {
            font-size: 9pt;
            font-weight: bold;
            color: #000;
            background: #e6f0fa;
            padding: 3px 8px;
            margin-top: 7px;
            margin-bottom: 4px;
            border-left: 3px solid #000;
            text-transform: uppercase;
        }

        /* TABLE */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }

        table.data-table td {
            padding: 1.5px 5px;
            vertical-align: top;
            font-size: 8.5pt;
            line-height: 1.25;
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

        /* Di dalam grid-2 (50% lebar halaman), label-col perlu lebih lebar
           agar tidak overflow dan ':' tetap sejajar antar baris */
        .grid-2 td .data-table .label-col {
            width: 44%;
            white-space: normal;
        }

        .grid-2 td .data-table .colon-col {
            width: 4%;
        }

        .grid-2 td .data-table .value-col {
            width: 52%;
        }

        /* Class eksplisit untuk data-table di dalam grid-2 — DomPDF safe */
        .label-col-half {
            width: 44% !important;
            white-space: normal !important;
        }

        .value-col-half {
            width: 49% !important;
        }

        .value-col p,
        .value-col ul,
        .value-col ol,
        .value-col li {
            margin: 0;
            padding-left: 0;
        }

        .value-col ul,
        .value-col ol {
            margin-top: 2px;
            margin-bottom: 2px;
            padding-left: 14px;
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
            padding: 5px 7px;
            font-size: 8pt;
            min-height: 30px;
            border-radius: 3px;
            line-height: 1.35;
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
            margin-top: 12px;
            page-break-inside: avoid;
        }

        .sign-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 3px 6px;
        }

        /* Baris kedua: 2 box (COO & CEO) center terhadap 3 box baris pertama */
        .sign-table-second {
            width: 66.66%;
            border-collapse: collapse;
            margin: 12px auto 0 auto;
            page-break-inside: avoid;
        }

        .sign-table-second td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 3px 6px;
        }

        .sign-title {
            font-size: 8pt;
            font-weight: bold;
            color: #000;
            margin-bottom: 38px;
        }

        .sign-name {
            font-size: 8.5pt;
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 3px;
            display: inline-block;
            min-width: 120px;
        }

        .sign-role {
            font-size: 7.5pt;
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
                    @php
                        $entityCode = strtoupper(trim($mpr->entity ?? ''));
                        // MITO Group: MSI, MEP, PII → logo mito-pdf.png
                        // Stein Group: SPI atau lainnya → logo stein.jpg
                        $isMitoGroup = in_array($entityCode, ['MSI', 'MEP', 'PII']);
                        $logoPath = $isMitoGroup ? public_path('assets/mito-red.png') : public_path('assets/stein-pdf.png');
                        $logoBase64 = file_exists($logoPath)
                            ? 'data:image/' .
                                ($isMitoGroup ? 'png' : 'jpeg') .
                                ';base64,' .
                                base64_encode(file_get_contents($logoPath))
                            : null;
                    @endphp
                    @if ($logoBase64)
                        <img src="{{ $logoBase64 }}" style="max-height: 38px; max-width: 90px; object-fit: contain;"
                            alt="{{ $company['brand'] ?? 'LOGO' }}">
                    @else
                        <span
                            style="font-size: 16pt; font-weight: bold; color: #000;">{{ $company['brand'] ?? 'MITO' }}</span>
                    @endif
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
            <strong>Status:</strong> {{ strtoupper($mpr->status ?? 'SUBMITTED') }}
        </div>
    </div>

    <!-- SECTION 1: INFORMASI PEMOHON -->
    <div class="section-title">I. Informasi Pemohon & Organisasi</div>
    <table class="grid-2">
        <tr>
            <td>
                <table class="data-table">
                    <tr>
                        <td class="label-col label-col-half">Nama Pemohon</td>
                        <td class="colon-col">:</td>
                        <td class="value-col value-col-half"><strong>{{ $mpr->requestorName ?: '-' }}</strong></td>
                    </tr>
                </table>
            </td>
            <td>
                <table class="data-table">
                    <tr>
                        <td class="label-col label-col-half">Jabatan Pemohon</td>
                        <td class="colon-col">:</td>
                        <td class="value-col value-col-half">{{ $mpr->requestorPosition ?: '-' }}</td>
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
                <table class="data-table data-table-half">
                    <tr>
                        <td class="label-col label-col-half">Entitas / Perusahaan</td>
                        <td class="colon-col">:</td>
                        <td class="value-col value-col-half">{{ $company['name'] ?? ($mpr->entity ?: '-') }}</td>
                    </tr>
                    <tr>
                        <td class="label-col label-col-half">Posisi / Nama Jabatan</td>
                        <td class="colon-col">:</td>
                        <td class="value-col value-col-half"><strong>{{ $mpr->position ?: '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label-col label-col-half">Departemen</td>
                        <td class="colon-col">:</td>
                        <td class="value-col value-col-half">{{ $mpr->department ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label-col label-col-half">Divisi</td>
                        <td class="colon-col">:</td>
                        <td class="value-col value-col-half">{{ $mpr->division ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label-col label-col-half">Level Jabatan</td>
                        <td class="colon-col">:</td>
                        <td class="value-col value-col-half">{{ $mpr->jobLevel ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label-col label-col-half">Lokasi Penempatan</td>
                        <td class="colon-col">:</td>
                        <td class="value-col value-col-half">{{ $mpr->workLocation ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label-col label-col-half">Benefits / Tunjangan</td>
                        <td class="colon-col">:</td>
                        <td class="value-col value-col-half">{{ $mpr->benefits ?: '-' }}</td>
                    </tr>
                </table>
            </td>
            <td>
                <table class="data-table data-table-half">
                    <tr>
                        <td class="label-col label-col-half">Status Kepegawaian</td>
                        <td class="colon-col">:</td>
                        <td class="value-col value-col-half">{{ $mpr->employmentType ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label-col label-col-half">Jumlah Kebutuhan</td>
                        <td class="colon-col">:</td>
                        <td class="value-col value-col-half"><strong>{{ $mpr->quantity ?: 1 }} Orang</strong></td>
                    </tr>
                    <tr>
                        <td class="label-col label-col-half">Target Join Date</td>
                        <td class="colon-col">:</td>
                        <td class="value-col value-col-half">
                            {{ $mpr->expectedJoinDate ? date('d F Y', strtotime($mpr->expectedJoinDate)) : '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="label-col label-col-half">Hari Kerja</td>
                        <td class="colon-col">:</td>
                        <td class="value-col value-col-half">{{ $mpr->workingDays ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label-col label-col-half">Jam Kerja</td>
                        <td class="colon-col">:</td>
                        <td class="value-col value-col-half">{{ $mpr->workingHours ?: '-' }}</td>
                    </tr>
                     @if (!empty($mpr->shiftDetail))
                     <tr>
                        <td class="label-col label-col-half">Detail Shift</td>
                        <td class="colon-col">:</td>
                        <td class="value-col value-col-half">{{ $mpr->shiftDetail }}</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <!-- Detail Shift & Benefits — full width agar tidak overflow -->
    <!-- <table class="data-table" style="margin-top:2px;">
       
    </table> -->

    <!-- SECTION 3: ALASAN PERMINTAAN -->
    <div class="section-title">III. Alasan Permintaan Karyawan</div>
    <table class="grid-2">
        <tr>
            <td>
                <table class="data-table">
                    <tr>
                        <td class="label-col label-col-half">Alasan Kebutuhan</td>
                        <td class="colon-col">:</td>
                        <td class="value-col value-col-half"><strong>{{ $mpr->reason ?: '-' }}</strong></td>
                    </tr>
                </table>
            </td>
            @if (!empty($mpr->replacementFor))
                <td>
                    <table class="data-table">
                        <tr>
                            <td class="label-col label-col-half">Menggantikan Karyawan</td>
                            <td class="colon-col">:</td>
                            <td class="value-col value-col-half">{{ $mpr->replacementFor }}</td>
                        </tr>
                    </table>
                </td>
            @else
                <td></td>
            @endif
        </tr>
    </table>

    <!-- SECTION 4: KUALIFIKASI KANDIDAT -->
    <div class="section-title">IV. Kualifikasi Kandidat</div>
    <table class="grid-2" style="margin-top:0;">
        <tr>
            <td>
                <table class="data-table">
                    <tr>
                        <td class="label-col label-col-half">Latar Belakang Pendidikan</td>
                        <td class="colon-col">:</td>
                        <td class="value-col value-col-half">{{ $mpr->educationBackground ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label-col label-col-half">Pengalaman Kerja</td>
                        <td class="colon-col">:</td>
                        <td class="value-col value-col-half">{{ $mpr->workExperience ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label-col label-col-half">Skills &amp; Kompetensi</td>
                        <td class="colon-col">:</td>
                        <td class="value-col value-col-half">
                            @if (!empty($skillsHtml))
                                {!! $skillsHtml !!}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
            <td>
                <table class="data-table">
                    <tr>
                        <td class="label-col label-col-half">Bahasa yang Dikuasai</td>
                        <td class="colon-col">:</td>
                        <td class="value-col value-col-half">
                            @if (!empty($languagesHtml))
                                {!! $languagesHtml !!}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="label-col label-col-half">Referensi Industri Sejenis</td>
                        <td class="colon-col">:</td>
                        <td class="value-col value-col-half">
                            @if (!empty($industryHtml))
                                {!! $industryHtml !!}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- SECTION 5: KUALIFIKASI & URAIAN PEKERJAAN -->
    <div class="section-title">V. Kualifikasi & Uraian Pekerjaan</div>
    <table class="grid-2">
        <tr>
            <td style="width:33.33%; vertical-align:top; padding: 0 3px;">
                <strong style="font-size:8pt;">Kualifikasi &amp; Persyaratan:</strong>
                <div class="content-box" style="margin-top:2px;">
                    @if (!empty($requirementsHtml))
                        {!! $requirementsHtml !!}
                    @else
                        Tidak ada kualifikasi khusus yang dilampirkan.
                    @endif
                </div>
            </td>
            <td style="width:33.33%; vertical-align:top; padding: 0 3px;">
                <strong style="font-size:8pt;">Uraian Tugas / Tanggung Jawab:</strong>
                <div class="content-box" style="margin-top:2px;">
                    @if (!empty($jobDescriptionHtml))
                        {!! $jobDescriptionHtml !!}
                    @else
                        Tidak ada uraian pekerjaan khusus yang dilampirkan.
                    @endif
                </div>
            </td>
            <td style="width:33.33%; vertical-align:top; padding: 0 3px;">
                <strong style="font-size:8pt;">Key Results / Target Posisi:</strong>
                <div class="content-box" style="margin-top:2px;">
                    @if (!empty($keyResultsHtml))
                        {!! $keyResultsHtml !!}
                    @else
                        Tidak ada target khusus yang dilampirkan.
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- SECTION 6: CATATAN -->
    @if (!empty($mpr->specialNotes))
        <div style="margin-bottom:5px;"><strong>Catatan Khusus MPR:</strong>
            <div class="content-box" style="min-height: 25px; margin-top:3px; margin-bottom: 8px;">
                {!! $specialNotesHtml !!}</div>
        </div>
    @endif

    <!-- SECTION 7: TANDA TANGAN -->
    <!-- Baris 1: Pemohon | HRD | Management -->
    <table class="sign-table">
        <tr>
            <td>
                <div class="sign-title">Diajukan oleh (Pemohon)</div>
                <div class="sign-name">{{ $mpr->requestorName ?: 'Manager Pemohon' }}</div>
                <div class="sign-role">{{ $mpr->requestorPosition ?: 'Manager / User Dept' }}</div>
            </td>
            <td>
                <div class="sign-title">Disetujui oleh (Divisi)</div>
                <div class="sign-name">( {{ $mpr->approvalDivision ?: '........................................' }} )
                </div>
                <div class="sign-role">Pimpinan Divisi</div>
            </td>
            <td>
                <div class="sign-title">Diperiksa oleh (HRD)</div>
                <div class="sign-name">Hisar Hesti</div>
                <div class="sign-role">HR Manager</div>
            </td>
        </tr>
    </table>

    <!-- Baris 2: COO & CEO — center di bawah baris 1 -->
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
