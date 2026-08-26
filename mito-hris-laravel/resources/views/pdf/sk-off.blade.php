<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Surat Keputusan Pemberhentian - {{ $employee->fullName }}</title>
  <style>
    @page { margin: 30px 40px; }
    body {
      font-family: 'Times New Roman', Times, serif;
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
    // 1:1 dengan GAS exportOffboardingLetterPDF
    $romanMonth  = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
    $skNumber    = $extraData['sk_number'] ?? $extraData['skNumber']
                    ?? ('001/HRD-SKK/' . ($company['code'] ?? 'MSI') . '/' . $romanMonth[date('n')-1] . '/' . date('Y'));

    $resignDate  = $extraData['effective_date'] ?? $extraData['last_working_date'] ?? $employee->resignDate ?? null;
    $resignFmt   = $resignDate ? \Carbon\Carbon::parse($resignDate)->translatedFormat('d F Y') : date('d F Y');
    $joinFmt     = $employee->joinDate ? \Carbon\Carbon::parse($employee->joinDate)->translatedFormat('d F Y') : '-';

    $jobPosition = $employee->jobPosition   ?? '-';
    $statusEmp   = $employee->statusEmployee ?? 'Karyawan';
    $companyName = $company['name']          ?? 'PT MAHAKARYA SUKSES INDONESIA';
    $companyCity = $company['city']          ?? 'Jakarta';
    $todayFmt    = date('d F Y');

    // Deteksi apakah status Permanent untuk kalimat "Karyawan Tetap"
    $statusLabel = in_array(strtoupper($statusEmp), ['PKWTT', 'PERMANENT'])
        ? 'Karyawan Tetap' : 'Karyawan';
  @endphp

  <div class="doc-title">SURAT KEPUTUSAN</div>
  <div class="doc-no">Nomor: {{ $skNumber }}</div>

  {{-- Menimbang & Mengingat (1:1 GAS) --}}
  <table class="data-table">
    <tr>
      <td class="label">Menimbang</td>
      <td class="colon">:</td>
      <td class="value">Bahwa Perusahaan perlu menetapkan keputusan atas pengunduran diri karyawan sesuai dengan ketentuan yang berlaku.</td>
    </tr>
    <tr>
      <td class="label">Mengingat</td>
      <td class="colon">:</td>
      <td class="value">
        1. Undang-Undang Tenaga Kerja No. 13 Tahun 2003.<br>
        2. Peraturan Perusahaan {{ $companyName }}.
      </td>
    </tr>
  </table>

  <div class="memutuskan">MEMUTUSKAN</div>

  <p style="margin-bottom:4px;"><strong>Menetapkan :</strong></p>

  {{-- Pertama, Kedua, Ketiga (1:1 GAS) --}}
  <table class="data-table">
    <tr>
      <td class="label">Pertama</td>
      <td class="colon">:</td>
      <td class="value">Menerima pengunduran diri <strong>{{ $employee->fullName }}</strong>, efektif per <strong>{{ $resignFmt }}</strong>.</td>
    </tr>
    <tr>
      <td class="label">Kedua</td>
      <td class="colon">:</td>
      <td class="value">Menyampaikan apresiasi dan terima kasih atas kontribusi yang telah diberikan selama bekerja di <strong>{{ $companyName }}</strong>.</td>
    </tr>
    <tr>
      <td class="label">Ketiga</td>
      <td class="colon">:</td>
      <td class="value">Menerangkan bahwa <strong>{{ $employee->fullName }}</strong> telah bekerja di <strong>{{ $companyName }}</strong> sebagai <strong>{{ $statusLabel }}</strong>, terhitung mulai <strong>{{ $joinFmt }}</strong> dengan jabatan terakhir sebagai <strong>{{ $jobPosition }}</strong>.</td>
    </tr>
  </table>

  {{-- Tanda Tangan — kanan (1:1 GAS) --}}
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
