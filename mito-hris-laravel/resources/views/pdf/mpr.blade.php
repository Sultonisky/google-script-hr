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
      font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
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
      color: #0b2540;
      text-transform: uppercase;
      margin: 0;
      letter-spacing: 0.5px;
    }
    .doc-meta {
      font-size: 8.5pt;
      color: #475569;
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
      margin-bottom: 6px;
    }
    table.data-table td {
      padding: 3.5px 6px;
      vertical-align: top;
      font-size: 9pt;
    }
    .label-col {
      width: 28%;
      color: #334155;
      font-weight: bold;
    }
    .colon-col {
      width: 3%;
      text-align: center;
    }
    .value-col {
      width: 69%;
      color: #0f172a;
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
      white-space: pre-wrap;
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
      padding: 4px 8px;
    }
    .sign-title {
      font-size: 8.5pt;
      font-weight: bold;
      color: #334155;
      margin-bottom: 45px;
    }
    .sign-name {
      font-size: 9pt;
      font-weight: bold;
      border-top: 1px solid #334155;
      padding-top: 4px;
      display: inline-block;
      min-width: 140px;
    }
    .sign-role {
      font-size: 8pt;
      color: #64748b;
      margin-top: 2px;
    }

    .badge-status {
      display: inline-block;
      padding: 2px 6px;
      font-size: 8pt;
      font-weight: bold;
      border-radius: 3px;
      background: #e2e8f0;
      color: #334155;
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
          <div class="company-address">{{ $company['address'] ?? 'Jl. Gajah Tunggal, Kp. Gembor, Pasir Jaya, Jatiuwung, Tangerang' }}</div>
        </td>
        <td style="text-align:right; vertical-align:middle;">
          <span style="font-size: 16pt; font-weight: bold; color: #000;">{{ $company['brand'] ?? 'MITO' }}</span>
        </td>
      </tr>
    </table>
  </div>

  <!-- TITLE -->
  <div class="doc-title-box">
    <div class="doc-title">Form Permintaan Tenaga Kerja (Manpower Request)</div>
    <div class="doc-meta">
      <strong>No. Request:</strong> {{ $mpr->mprNumber }} &nbsp;|&nbsp; 
      <strong>Tanggal:</strong> {{ $mpr->requestDate ? date('d F Y', strtotime($mpr->requestDate)) : date('d F Y') }} &nbsp;|&nbsp;
      <strong>Status:</strong> <span class="badge-status">{{ strtoupper($mpr->status ?? 'SUBMITTED') }}</span>
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
            <td class="label-col">Email Pemohon</td>
            <td class="colon-col">:</td>
            <td class="value-col">{{ $mpr->requestorEmail ?: '-' }}</td>
          </tr>
          <tr>
            <td class="label-col">Entitas / Perusahaan</td>
            <td class="colon-col">:</td>
            <td class="value-col">{{ $company['name'] ?? ($mpr->entity ?: '-') }}</td>
          </tr>
          @if(!empty($mpr->branch))
          <tr>
            <td class="label-col">Branch / Lokasi Pemohon</td>
            <td class="colon-col">:</td>
            <td class="value-col">{{ $mpr->branch }}</td>
          </tr>
          @endif
        </table>
      </td>
      <td>
        <table class="data-table">
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
            <td class="label-col">Diajukan Oleh</td>
            <td class="colon-col">:</td>
            <td class="value-col">{{ $mpr->createdBy ?: '-' }}</td>
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
            <td class="label-col">Posisi / Jabatan</td>
            <td class="colon-col">:</td>
            <td class="value-col"><strong>{{ $mpr->position ?: '-' }}</strong></td>
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
            <td class="value-col"><strong style="color:#000; font-size:10pt;">{{ $mpr->quantity ?: 1 }} Orang</strong></td>
          </tr>
          <tr>
            <td class="label-col">Target Join Date</td>
            <td class="colon-col">:</td>
            <td class="value-col">{{ $mpr->expectedJoinDate ? date('d F Y', strtotime($mpr->expectedJoinDate)) : '-' }}</td>
          </tr>
        </table>
      </td>
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
    @if(!empty($mpr->replacementFor))
    <tr>
      <td class="label-col" style="width:20%;">Menggantikan Karyawan</td>
      <td class="colon-col">:</td>
      <td class="value-col">{{ $mpr->replacementFor }}</td>
    </tr>
    @endif
  </table>

  <!-- SECTION 4: KUALIFIKASI & URAIAN PEKERJAAN -->
  <div class="section-title">IV. Kualifikasi & Uraian Pekerjaan</div>
  <table class="grid-2">
    <tr>
      <td>
        <strong>Kualifikasi & Persyaratan:</strong>
        <div class="content-box">{{ $mpr->requirements ?: 'Tidak ada kualifikasi khusus yang dilampirkan.' }}</div>
      </td>
      <td>
        <strong>Uraian Tugas / Tanggung Jawab Utama:</strong>
        <div class="content-box">{{ $mpr->jobDescription ?: 'Tidak ada uraian pekerjaan khusus yang dilampirkan.' }}</div>
      </td>
    </tr>
  </table>

  <!-- SECTION 5: CATATAN TAMBAHAN -->
  @if(!empty($mpr->notes))
  <div class="section-title">V. Catatan Tambahan</div>
  <div class="content-box" style="min-height: 25px; margin-bottom: 8px;">{{ $mpr->notes }}</div>
  @endif

  <!-- SECTION 6: TANDA TANGAN -->
  <table class="sign-table">
    <tr>
      <td>
        <div class="sign-title">Diajukan oleh (Pemohon)</div>
        <div class="sign-name">{{ $mpr->requestorName ?: 'Manager Pemohon' }}</div>
        <div class="sign-role">Manager / User Dept</div>
      </td>
      <td>
        <div class="sign-title">Diperiksa oleh (HRD)</div>
        <div class="sign-name">( ........................................ )</div>
        <div class="sign-role">HR Manager / Recruiter</div>
      </td>
      <td>
        <div class="sign-title">Disetujui oleh (Management)</div>
        <div class="sign-name">( ........................................ )</div>
        <div class="sign-role">Direksi / General Manager</div>
      </td>
    </tr>
  </table>

</body>
</html>
