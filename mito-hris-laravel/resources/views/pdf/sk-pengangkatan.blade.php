<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>SK Pengangkatan Karyawan Tetap - {{ $employee->fullName }}</title>
  <style>
    @page { margin: 30px 40px; }
    body {
      font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
      font-size: 11px;
      color: #1f2937;
      line-height: 1.55;
      text-align: justify;
    }
    .doc-title  { font-size: 14pt; font-weight: bold; text-align: center; text-transform: uppercase; color: #0b2540; margin-top: 10px; }
    .doc-no     { font-size: 10pt; text-align: center; color: #6b7280; margin-bottom: 16px; }
    .memutuskan { font-size: 12pt; font-weight: bold; text-align: center; margin: 12px 0 8px; letter-spacing: 0.5px; }
    .data-table { width: 100%; border-collapse: collapse; margin: 6px 0; }
    .data-table td { padding: 3px 4px; vertical-align: top; }
    .label  { width: 22%; font-weight: bold; }
    .colon  { width: 3%; }
    .value  { width: 75%; }
    .sign-table { width: 100%; margin-top: 30px; page-break-inside: avoid; }
    .sign-table td { text-align: right; padding-right: 20px; vertical-align: top; }
    .sign-space { height: 55px; }
  </style>
</head>
<body>

  @include('pdf.components.kop-surat')

  @php
    // 1:1 dengan GAS exportSKTetapPDF()
    $romanMonth  = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
    $skNumber    = $extraData['sk_number'] ?? $extraData['skNumber'] ?? $extraData['evalId']
                    ?? ('001/HRD-PK/' . ($company['brand'] ?? 'MITO') . '/' . $romanMonth[date('n')-1] . '/' . date('Y'));

    $newPos      = $extraData['job_position'] ?? $extraData['jobPosition'] ?? $employee->jobPosition ?? '-';
    $newDept     = $extraData['department']   ?? $employee->department                               ?? '-';
    $newDiv      = $extraData['division']     ?? $employee->division                                 ?? 'Operasional';

    $effectiveDate = $extraData['effective_date'] ?? $extraData['effectiveDate'] ?? null;
    $effectiveFmt  = $effectiveDate ? \Carbon\Carbon::parse($effectiveDate)->translatedFormat('d F Y') : date('d F Y');

    $companyName = $company['name'] ?? 'PT MAHAKARYA SUKSES INDONESIA';
    $companyCity = $company['city'] ?? 'Jakarta';
    $todayFmt    = date('d F Y');
    $nik         = ltrim($employee->nikNpwp ?? '-', "'");
  @endphp

  <div class="doc-title">SURAT KEPUTUSAN</div>
  <div class="doc-no">Nomor: {{ $skNumber }}</div>

  {{-- Menimbang, Mengingat (1:1 GAS) --}}
  <table class="data-table">
    <tr>
      <td class="label">Menimbang</td>
      <td class="colon">:</td>
      <td class="value">Bahwa berdasarkan hasil evaluasi masa percobaan serta kebutuhan Perusahaan, dipandang perlu mengangkat karyawan sebagai <strong>Karyawan Tetap (PKWTT)</strong> sesuai dengan Peraturan Perusahaan dan ketentuan peraturan perundang-undangan yang berlaku.</td>
    </tr>
    <tr>
      <td class="label">Mengingat</td>
      <td class="colon">:</td>
      <td class="value">
        1. Undang-Undang Ketenagakerjaan beserta peraturan pelaksanaannya.<br>
        2. Peraturan Perusahaan {{ $companyName }}.<br>
        3. Hasil evaluasi kinerja selama masa percobaan.
      </td>
    </tr>
  </table>

  <div class="memutuskan">MEMUTUSKAN</div>

  <table class="data-table">
    <tr>
      <td class="label">Menetapkan</td>
      <td class="colon">:</td>
      <td class="value">
        Mengangkat <strong>{{ $employee->fullName }}</strong> sebagai <strong>Karyawan Tetap</strong> dengan jabatan <strong>{{ $newPos }}</strong>, Departemen <strong>{{ $newDept }}</strong>, Divisi <strong>{{ $newDiv }}</strong>, terhitung sejak surat keputusan ini ditetapkan dan ditandatangani.
      </td>
    </tr>
  </table>

  <p>Demikian Surat Keputusan ini dibuat, untuk dilaksanakan sesuai Peraturan Perusahaan yang berlaku.</p>

  <table class="sign-table">
    <tr>
      <td>
        {{ $companyCity }}, {{ $todayFmt }}<br>
        Hormat Kami,<br>
        <strong>{{ $companyName }}</strong><br>
        @include('pdf.components.hr-sign')
        <strong><u>Hisar Hesti</u></strong><br>
        Human Resources (HR) &amp; Legal Manager
      </td>
    </tr>
  </table>

</body>
</html>
