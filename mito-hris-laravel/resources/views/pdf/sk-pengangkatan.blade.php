<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>SK Pengangkatan Karyawan Tetap - {{ $employee->fullName }}</title>
  <style>
    @page { margin: 30px 40px; }
    body {
      font-family: 'Times New Roman', Times, serif;
      font-size: 11px;
      color: #000000;
      line-height: 1.55;
      text-align: justify;
    }
    .doc-title  { font-size: 12pt; font-weight: bold; text-align: center; text-transform: uppercase; color: #000000; margin-top: 6px; }
    .doc-sub    { font-size: 10pt; font-weight: bold; text-align: center; color: #000000; margin-bottom: 4px; }
    .doc-no     { font-size: 8.5pt; text-align: center; color: #555555; margin-bottom: 4px; }
    .doc-co     { font-size: 10pt; font-weight: bold; text-align: center; color: #000000; margin-bottom: 12px; }
    .kv-table   { width: 100%; border-collapse: collapse; margin: 4px 0; }
    .kv-table td { padding: 2px 3px; vertical-align: top; font-size: 8.5pt; }
    .kv-label   { width: 120px; font-weight: bold; }
    .kv-colon   { width: 12px; }
    .kv-value   { }
    .sign-table { width: 100%; margin-top: 24px; page-break-inside: avoid; }
    .sign-table td { text-align: right; padding-right: 0; vertical-align: top; font-size: 8.5pt; }
    .sign-space { height: 50px; display: block; }
    .sign-image-area { height: 58px; margin: 2px 0 2px auto; }
    .sign-image-area .hr-sign-img { height: 48px !important; width: auto; max-width: 125px; margin: 0 0 4px auto; }
  </style>
</head>
<body>

  @include('pdf.components.kop-surat')

  @php
    // ── 1:1 GAS exportSKTetapPDF() ─────────────────────────────
    // SK number: from service result or fallback
    $skNumber = $extraData['sk_number'] ?? $extraData['skNumber'] ?? $extraData['evalId']
                ?? ('001/HRD-PK/' . ($company['code'] ?? 'MSI') . '/I/' . date('Y'));

    // Fields: 1:1 GAS emp.position || emp.positionCurrent || 'Jabatan'
    $position   = $extraData['job_position'] ?? $extraData['jobPosition']
                  ?? $employee->jobPositionLocation ?? $employee->jobPosition ?? 'Jabatan';

    // department: 1:1 GAS emp.department || 'Departemen'
    $department = $extraData['department'] ?? $employee->department ?? 'Departemen';

    // division: 1:1 GAS emp.division || 'Divisi'
    $division   = $extraData['division']   ?? $employee->division   ?? 'Divisi';

    // fullName: 1:1 GAS emp.fullName
    $fullName   = $employee->fullName ?? '-';

    // companyName: 1:1 GAS comp.name
    $companyName = $company['name'] ?? 'PT MAHAKARYA SUKSES INDONESIA';

    // city: 1:1 GAS getCityFromAddress(comp.address) — extract "Kota X" pattern
    $companyAddress = $company['address'] ?? '';
    preg_match('/Kota\s+([\w\s]+?)(?:,|$)/i', $companyAddress, $cityMatch);
    $city = isset($cityMatch[1]) ? trim($cityMatch[1]) : ($company['city'] ?? 'Jakarta');

    // todayStr: 1:1 GAS fmtDateId(today) = "d Bulan YYYY"
    $bulanId = ['Januari','Februari','Maret','April','Mei','Juni',
                'Juli','Agustus','September','Oktober','November','Desember'];
    $todayStr = date('j') . ' ' . $bulanId[(int)date('n') - 1] . ' ' . date('Y');
  @endphp

  {{-- ── JUDUL (1:1 GAS sequence) ──────────────────────────── --}}
  <div class="doc-title">SURAT KEPUTUSAN</div>
  <div class="doc-no">Nomor: {{ $skNumber }}</div>
  {{-- GAS line: 'Perusahaan ' + companyName --}}
  <div class="doc-co">Perusahaan {{ $companyName }}</div>

  {{-- ── MENIMBANG (1:1 GAS text — tanpa kata "(PKWTT)") ────── --}}
  <table class="kv-table">
    <tr>
      <td class="kv-label">Menimbang</td>
      <td class="kv-colon">:</td>
      <td class="kv-value">Bahwa berdasarkan hasil evaluasi masa percobaan serta kebutuhan Perusahaan, dipandang perlu mengangkat karyawan sebagai Karyawan Tetap sesuai dengan Peraturan Perusahaan dan ketentuan peraturan perundang-undangan yang berlaku.</td>
    </tr>
  </table>

  {{-- ── MENGINGAT (1:1 GAS — 3 items) ─────────────────────── --}}
  <table class="kv-table">
    <tr>
      <td class="kv-label">Mengingat</td>
      <td class="kv-colon">:</td>
      <td class="kv-value">1. Undang-Undang Ketenagakerjaan beserta peraturan pelaksanaannya.</td>
    </tr>
    <tr>
      <td class="kv-label"></td>
      <td class="kv-colon"></td>
      <td class="kv-value">2. Peraturan Perusahaan {{ $companyName }}.</td>
    </tr>
    <tr>
      <td class="kv-label"></td>
      <td class="kv-colon"></td>
      <td class="kv-value">3. Hasil evaluasi kinerja selama masa percobaan.</td>
    </tr>
  </table>

  <div style="text-align:center;font-size:10pt;font-weight:bold;margin:12px 0 6px;">MEMUTUSKAN</div>

  {{-- ── MENETAPKAN (1:1 GAS ketetapan text) ───────────────── --}}
  <table class="kv-table">
    <tr>
      <td class="kv-label">Menetapkan</td>
      <td class="kv-colon">:</td>
      <td class="kv-value"></td>
    </tr>
    <tr>
      <td class="kv-label"></td>
      <td class="kv-colon">:</td>
      <td class="kv-value">
        Mengangkat <strong>{{ $fullName }}</strong> sebagai Karyawan Tetap dengan jabatan
        {{ $position }}, Departemen {{ $department }}, Divisi {{ $division }},
        terhitung sejak surat keputusan ini ditetapkan dan ditandatangani.
      </td>
    </tr>
  </table>

  {{-- ── PENUTUP (1:1 GAS) ──────────────────────────────────── --}}
  <p style="font-size:8.5pt;margin-top:16px;">
    Demikian Surat Keputusan ini dibuat, untuk dilaksanakan sesuai Peraturan Perusahaan yang berlaku.
  </p>

  {{-- ── TANDA TANGAN (1:1 GAS: kanan, city+todayStr, Hormat Kami, companyName, Hisar Hesti, jabatan) --}}
  <table class="sign-table">
    <tr>
      <td>
        {{ $city }}, {{ $todayStr }}<br>
        Hormat Kami,<br>
        <strong>{{ $companyName }}</strong><br>
        <div class="sign-image-area">
          @include('pdf.components.hr-sign')
        </div>
        <strong><u>Hisar Hesti</u></strong><br>
        Human Resources (HR) &amp; Legal Manager
      </td>
    </tr>
  </table>

</body>
</html>
