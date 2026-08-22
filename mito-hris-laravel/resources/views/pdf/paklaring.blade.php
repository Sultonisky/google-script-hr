<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Surat Keterangan Kerja (Paklaring) - {{ $employee->fullName }}</title>
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
    .data-table { width: 100%; border-collapse: collapse; margin: 6px 0; }
    .data-table td { padding: 3px 4px; vertical-align: top; }
    .label  { width: 25%; font-weight: bold; }
    .colon  { width: 3%; }
    .value  { width: 72%; }
    .sign-table { width: 100%; margin-top: 35px; page-break-inside: avoid; }
    .sign-table td { text-align: right; padding-right: 20px; vertical-align: top; }
    .sign-space { height: 55px; }
  </style>
</head>
<body>

  @include('pdf.components.kop-surat')

  @php
    // 1:1 dengan GAS exportPaklaringPDF()
    $romanMonth  = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
    $letterNumber = $extraData['letter_number'] ?? $extraData['evalId'] ?? $extraData['sk_number']
                    ?? ('001/HRD-SKK/' . ($company['brand'] ?? 'MITO') . '/' . $romanMonth[date('n')-1] . '/' . date('Y'));

    $endDate = $extraData['effective_date'] ?? $extraData['last_working_date']
               ?? $employee->resignDate ?? $employee->endDateContract ?? null;
    $endFmt  = $endDate ? \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y') : date('d F Y');
    $joinFmt = $employee->joinDate ? \Carbon\Carbon::parse($employee->joinDate)->translatedFormat('d F Y') : '-';

    $companyName = $company['name'] ?? 'PT MAHAKARYA SUKSES INDONESIA';
    $companyCity = $company['city'] ?? 'Jakarta';
    $todayFmt    = date('d F Y');
  @endphp

  <div class="doc-title">SURAT KETERANGAN KERJA</div>
  <div class="doc-no">Nomor: {{ $letterNumber }}</div>

  <p>Yang bertanda tangan di bawah ini:</p>

  <table class="data-table">
    <tr><td class="label">Nama</td><td class="colon">:</td><td class="value"><strong>Hisar Hesti</strong></td></tr>
    <tr><td class="label">Jabatan</td><td class="colon">:</td><td class="value">Human Resources (HR) &amp; Legal Manager</td></tr>
  </table>

  <p>Dengan ini menerangkan bahwa:</p>

  <table class="data-table">
    <tr><td class="label">Nama</td><td class="colon">:</td><td class="value"><strong>{{ $employee->fullName }}</strong></td></tr>
  </table>

  <p>
    Telah bekerja di <strong>{{ $companyName }}</strong> terhitung sejak <strong>{{ $joinFmt }}</strong>
    dengan jabatan terakhir sebagai <strong>{{ $employee->jobPosition ?? '-' }}</strong>.
    Masa kerja di <strong>{{ $companyName }}</strong> telah berakhir pada tanggal <strong>{{ $endFmt }}</strong>.
  </p>

  <p>Demikian surat keterangan ini dibuat dan digunakan sebagaimana mestinya.</p>

  <table class="sign-table">
    <tr>
      <td>
        {{ $companyCity }}, {{ $todayFmt }}<br>
        Hormat Kami,<br>
        <strong>{{ $companyName }}</strong><br>
        @include('pdf.components.hr-sign')
        <strong><u>Hisar Hesti</u></strong><br>
        HR &amp; Legal Manager
      </td>
    </tr>
  </table>

</body>
</html>
